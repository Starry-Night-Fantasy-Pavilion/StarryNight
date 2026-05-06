package com.starrynight.starrynight.services.engine;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.starrynight.engine.memcore.MemCoreManager;
import com.starrynight.engine.prompt.CPromptContext;
import com.starrynight.engine.retrieval.HybridRetriever;
import com.starrynight.engine.token.ContextTruncator;
import com.starrynight.engine.vector.EntityIds;
import com.starrynight.engine.vector.EntryType;
import com.starrynight.engine.vector.VectorCollections;
import com.starrynight.engine.vector.VectorEntry;
import com.starrynight.engine.vector.VectorMetadata;
import com.starrynight.engine.vector.embed.EmbeddingModel;
import com.starrynight.engine.workflow.ContextWeaver;
import com.starrynight.engine.workflow.WritingIntent;
import com.starrynight.starrynight.system.novel.entity.Novel;
import com.starrynight.starrynight.system.novel.entity.NovelChapter;
import com.starrynight.starrynight.system.novel.entity.NovelOutline;
import com.starrynight.starrynight.system.novel.repository.NovelChapterRepository;
import com.starrynight.starrynight.system.novel.repository.NovelOutlineRepository;
import com.starrynight.starrynight.system.novel.repository.NovelRepository;
import org.springframework.stereotype.Service;

import java.util.ArrayList;
import java.util.List;

/**
 * 作品级向量记忆：入库（seed）与按 novelId 隔离的召回片段，供车间/草稿/扩写/流式等复用。
 */
@Service
public class NovelVectorMemoryService {

    private final NovelRepository novelRepository;
    private final NovelOutlineRepository novelOutlineRepository;
    private final NovelChapterRepository novelChapterRepository;
    private final MemCoreManager memCoreManager;
    private final EmbeddingModel embeddingModel;
    private final HybridRetriever hybridRetriever;
    private final ContextTruncator contextTruncator;

    public NovelVectorMemoryService(NovelRepository novelRepository,
                                    NovelOutlineRepository novelOutlineRepository,
                                    NovelChapterRepository novelChapterRepository,
                                    MemCoreManager memCoreManager,
                                    EmbeddingModel embeddingModel,
                                    HybridRetriever hybridRetriever,
                                    ContextTruncator contextTruncator) {
        this.novelRepository = novelRepository;
        this.novelOutlineRepository = novelOutlineRepository;
        this.novelChapterRepository = novelChapterRepository;
        this.memCoreManager = memCoreManager;
        this.embeddingModel = embeddingModel;
        this.hybridRetriever = hybridRetriever;
        this.contextTruncator = contextTruncator;
    }

    /**
     * 将当前作品的大纲与近期章节写入叙事向量库（幂等 upsert，按条目 id 覆盖）。
     */
    public void seedNarrativeMemory(Long novelId) {
        if (novelId == null) {
            return;
        }
        Novel novel = novelRepository.selectById(novelId);
        if (novel != null) {
            StringBuilder world = new StringBuilder();
            if (novel.getTitle() != null && !novel.getTitle().isBlank()) {
                world.append("作品：").append(novel.getTitle()).append('\n');
            }
            if (novel.getGenre() != null && !novel.getGenre().isBlank()) {
                world.append("类型：").append(novel.getGenre()).append('\n');
            }
            if (novel.getSynopsis() != null && !novel.getSynopsis().isBlank()) {
                world.append("简介：").append(novel.getSynopsis());
            }
            String worldText = world.toString().trim();
            if (!worldText.isBlank()) {
                memCoreManager.upsert(VectorCollections.SETTINGS,
                        buildEntry(
                                worldText,
                                embeddingModel.embed(worldText),
                                EntryType.WORLD_SETTING,
                                novelId,
                                null,
                                null
                        )
                );
            }
        }

        List<NovelOutline> outlines = novelOutlineRepository.selectList(
                new LambdaQueryWrapper<NovelOutline>()
                        .eq(NovelOutline::getNovelId, novelId)
                        .orderByAsc(NovelOutline::getSortOrder)
                        .last("limit 30")
        );
        for (NovelOutline outline : outlines) {
            if (outline.getContent() == null || outline.getContent().isBlank()) {
                continue;
            }
            memCoreManager.upsert(VectorCollections.NARRATIVE,
                    buildEntry(
                            outline.getTitle() + "\n" + outline.getContent(),
                            embeddingModel.embed(outline.getContent()),
                            EntryType.EVENT,
                            novelId,
                            outline.getId(),
                            null
                    )
            );
        }

        List<NovelChapter> chapters = novelChapterRepository.selectList(
                new LambdaQueryWrapper<NovelChapter>()
                        .eq(NovelChapter::getNovelId, novelId)
                        .orderByDesc(NovelChapter::getId)
                        .last("limit 10")
        );
        for (NovelChapter chapter : chapters) {
            String text = chapter.getOutline();
            if (text == null || text.isBlank()) {
                text = chapter.getContent();
            }
            if (text == null || text.isBlank()) {
                continue;
            }
            String chunk = chapter.getTitle() + "\n" + text;
            memCoreManager.upsert(VectorCollections.NARRATIVE,
                    buildEntry(
                            chunk,
                            embeddingModel.embed(chunk),
                            EntryType.CHAPTER_SUMMARY,
                            novelId,
                            null,
                            chapter.getId()
                    )
            );
        }
    }

    /**
     * 生成可拼接到任意大模型 prompt 前的「同一作品」约束片段（内部会先 seed 再 weave）。
     */
    public String buildRecallConstraintBlock(Long novelId,
                                             String coreEvent,
                                             String sceneLocation,
                                             List<String> presentCharacterIds) {
        if (novelId == null) {
            return "";
        }
        seedNarrativeMemory(novelId);

        WritingIntent intent = new WritingIntent();
        intent.setNovelId(novelId);
        intent.setCoreEvent(coreEvent == null || coreEvent.isBlank() ? "当前创作任务" : coreEvent);

        WritingIntent.CurrentState state = new WritingIntent.CurrentState();
        state.setSceneLocation(sceneLocation);
        intent.setCurrentState(state);

        if (presentCharacterIds != null) {
            for (String id : presentCharacterIds) {
                if (id == null || id.isBlank()) {
                    continue;
                }
                WritingIntent.PresentCharacter c = new WritingIntent.PresentCharacter();
                c.setCharacterId(id);
                intent.getPresentCharacters().add(c);
            }
        }

        ContextWeaver weaver = new ContextWeaver(hybridRetriever, contextTruncator);
        CPromptContext ctx = weaver.weave(intent);
        if (ctx.getConstraints() == null || ctx.getConstraints().isEmpty()) {
            return "";
        }
        StringBuilder sb = new StringBuilder();
        sb.append("【向量记忆召回（已按 novelId 隔离）】\n");
        for (VectorEntry e : ctx.getConstraints()) {
            String chunk = e.getChunk() == null ? "" : e.getChunk().replace("\r", "").trim();
            if (chunk.isEmpty()) {
                continue;
            }
            int max = Math.min(280, chunk.length());
            sb.append("- ").append(chunk.substring(0, max));
            if (chunk.length() > max) {
                sb.append('…');
            }
            sb.append('\n');
        }
        return sb.toString();
    }

    private VectorEntry buildEntry(String chunk,
                                   float[] dense,
                                   EntryType type,
                                   Long novelId,
                                   Long outlineId,
                                   Long chapterId) {
        VectorEntry entry = new VectorEntry();
        entry.setChunk(chunk);
        entry.setDenseVector(dense);

        VectorMetadata metadata = new VectorMetadata();
        metadata.setType(type);
        EntityIds ids = new EntityIds();
        ids.setNovelId(String.valueOf(novelId));
        if (outlineId != null) {
            ids.setOutlineNodeId(String.valueOf(outlineId));
        }
        if (chapterId != null) {
            ids.setChapterId(String.valueOf(chapterId));
        }
        metadata.setEntityIds(ids);
        entry.setMetadata(metadata);
        return entry;
    }
}
