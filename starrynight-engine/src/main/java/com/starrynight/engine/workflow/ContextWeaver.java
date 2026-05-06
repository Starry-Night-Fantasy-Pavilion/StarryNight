package com.starrynight.engine.workflow;

import com.starrynight.engine.prompt.CPromptContext;
import com.starrynight.engine.retrieval.HybridRetriever;
import com.starrynight.engine.token.ContextTruncator;
import com.starrynight.engine.vector.EntryType;
import com.starrynight.engine.vector.VectorCollections;
import com.starrynight.engine.vector.VectorEntry;
import com.starrynight.engine.vector.search.VectorSearchRequest;
import com.starrynight.engine.vector.search.VectorSearchResult;

import java.util.ArrayList;
import java.util.List;

/**
 * 上下文编织（Context Weaving）完整实现：
 * - 按写作意图召回 settings/entities/narrative 中的约束片段
 * - 按优先级排序上下文片段
 * - 按Token数量智能截断到模型可用长度
 * - 拼成 CPromptContext，供后续生成/自检使用
 */
public class ContextWeaver {

    private final HybridRetriever hybridRetriever;
    private final ContextTruncator truncator;

    public ContextWeaver(HybridRetriever hybridRetriever, ContextTruncator truncator) {
        this.hybridRetriever = hybridRetriever;
        this.truncator = truncator;
    }

    public CPromptContext weave(WritingIntent intent) {
        CPromptContext ctx = new CPromptContext();
        ctx.setIntentSummary(buildIntentSummary(intent));

        List<VectorEntry> constraints = new ArrayList<>();

        // 1) 场景地点 -> settings 召回世界观/地点设定
        if (intent.getCurrentState() != null && intent.getCurrentState().getSceneLocation() != null) {
            VectorSearchRequest r1 = req(VectorCollections.SETTINGS,
                    intent.getCurrentState().getSceneLocation(),
                    EntryType.WORLD_SETTING,
                    6);
            applyNovelScope(intent, r1);
            constraints.addAll(topN(hybridRetriever.search(r1), 6));
            VectorSearchRequest r2 = req(VectorCollections.ENTITIES,
                    intent.getCurrentState().getSceneLocation(),
                    EntryType.LOCATION,
                    4);
            applyNovelScope(intent, r2);
            constraints.addAll(topN(hybridRetriever.search(r2), 4));
        }

        // 2) 核心事件 -> narrative 召回近期事件/章节摘要
        if (intent.getCoreEvent() != null) {
            VectorSearchRequest r3 = req(VectorCollections.NARRATIVE,
                    intent.getCoreEvent(),
                    EntryType.EVENT,
                    6);
            applyNovelScope(intent, r3);
            constraints.addAll(topN(hybridRetriever.search(r3), 6));
            VectorSearchRequest r4 = req(VectorCollections.NARRATIVE,
                    intent.getCoreEvent(),
                    EntryType.CHAPTER_SUMMARY,
                    3);
            applyNovelScope(intent, r4);
            constraints.addAll(topN(hybridRetriever.search(r4), 3));
        }

        // 3) 出场角色 -> entities 召回角色基线
        if (intent.getPresentCharacters() != null) {
            for (WritingIntent.PresentCharacter c : intent.getPresentCharacters()) {
                if (c.getCharacterId() == null || c.getCharacterId().isBlank()) continue;
                VectorSearchRequest r = req(VectorCollections.ENTITIES, c.getCharacterId(), EntryType.CHARACTER, 1);
                applyNovelScope(intent, r);
                r.getMetadataEquals().put("characterId", c.getCharacterId());
                constraints.addAll(topN(hybridRetriever.search(r), 1));
            }
        }

        // 4) 按优先级排序
        List<VectorEntry> sortedConstraints = ContextTruncator.sortByPriority(constraints);

        // 5) 截断到模型可用Token数量
        List<VectorEntry> truncatedConstraints = truncateToTokenLimit(sortedConstraints);

        ctx.setConstraints(truncatedConstraints);

        // 设置上下文统计信息
        ctx.setTotalTokens(truncator.calculateTotalTokens(truncatedConstraints));
        ctx.setMaxTokens(truncator.getMaxTokens());

        return ctx;
    }

    /**
     * 截断到模型可用Token数量
     * @param contextParts 排序后的上下文片段
     * @return 截断后的上下文片段
     */
    private List<VectorEntry> truncateToTokenLimit(List<VectorEntry> contextParts) {
        return truncator.truncateToTokenLimit(contextParts);
    }

    private VectorSearchRequest req(String collection, String query, EntryType type, int limit) {
        VectorSearchRequest r = new VectorSearchRequest();
        r.setCollection(collection);
        r.setQueryText(query);
        r.setType(type);
        r.setLimit(limit);
        return r;
    }

    private void applyNovelScope(WritingIntent intent, VectorSearchRequest r) {
        if (intent.getNovelId() != null) {
            r.getMetadataEquals().put("novelId", String.valueOf(intent.getNovelId()));
        }
    }

    private List<com.starrynight.engine.vector.VectorEntry> topN(List<VectorSearchResult> results, int n) {
        List<com.starrynight.engine.vector.VectorEntry> out = new ArrayList<>();
        for (int i = 0; i < Math.min(n, results.size()); i++) {
            out.add(results.get(i).getEntry());
        }
        return out;
    }

    private String buildIntentSummary(WritingIntent intent) {
        StringBuilder sb = new StringBuilder();
        sb.append("核心事件：").append(nullToEmpty(intent.getCoreEvent())).append("\n");
        if (intent.getCurrentState() != null) {
            sb.append("场景地点：").append(nullToEmpty(intent.getCurrentState().getSceneLocation())).append("\n");
            sb.append("氛围：").append(nullToEmpty(intent.getCurrentState().getAtmosphere())).append("\n");
        }
        if (intent.getEmotionalTone() != null) {
            sb.append("情绪基调：").append(intent.getEmotionalTone()).append("\n");
        }
        if (intent.getGenerationMode() != null) {
            sb.append("生成模式：").append(intent.getGenerationMode()).append("\n");
        }
        return sb.toString();
    }

    private String nullToEmpty(String s) {
        return s == null ? "" : s;
    }
}

