package com.starrynight.starrynight.system.novel.service;

import com.starrynight.engine.consistency.ConsistencyChecker;
import com.starrynight.engine.consistency.ConsistencyReport;
import com.starrynight.engine.prompt.CPromptBuilder;
import com.starrynight.engine.prompt.CPromptContext;
import com.starrynight.engine.retrieval.HybridRetriever;
import com.starrynight.engine.token.ContextTruncator;
import com.starrynight.engine.vector.VectorEntry;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.framework.common.exception.ResourceNotFoundException;
import com.starrynight.starrynight.framework.common.util.ThreadLocalUtil;
import com.starrynight.starrynight.services.ai.AiGenerationService;
import com.starrynight.starrynight.system.novel.dto.AiConsistencyCheckDTO;
import com.starrynight.starrynight.system.novel.dto.ChapterWorkshopIntentDTO;
import com.starrynight.starrynight.system.novel.dto.ChapterWorkshopResultDTO;
import com.starrynight.starrynight.system.novel.dto.PlotSuggestionRequestDTO;
import com.starrynight.starrynight.system.novel.dto.PlotSuggestionResultDTO;
import com.starrynight.starrynight.services.engine.NovelVectorMemoryService;
import com.starrynight.starrynight.system.novel.entity.Novel;
import com.starrynight.starrynight.system.novel.repository.NovelRepository;
import org.springframework.stereotype.Service;

import java.util.ArrayList;
import java.util.List;

@Service
public class ChapterWorkshopService {

    private final NovelRepository novelRepository;
    private final AiGenerationService aiGenerationService;
    private final NovelVectorMemoryService novelVectorMemoryService;
    private final HybridRetriever hybridRetriever;
    private final ConsistencyChecker consistencyChecker;
    private final ContextTruncator contextTruncator;


    public ChapterWorkshopService(NovelRepository novelRepository,
                                  AiGenerationService aiGenerationService,
                                  NovelVectorMemoryService novelVectorMemoryService,
                                  HybridRetriever hybridRetriever,
                                  ConsistencyChecker consistencyChecker,
                                  ContextTruncator contextTruncator) {
        this.novelRepository = novelRepository;
        this.aiGenerationService = aiGenerationService;
        this.novelVectorMemoryService = novelVectorMemoryService;
        this.hybridRetriever = hybridRetriever;
        this.consistencyChecker = consistencyChecker;
        this.contextTruncator = contextTruncator;
    }

    public ChapterWorkshopResultDTO preview(ChapterWorkshopIntentDTO dto) {
        WorkshopRuntime runtime = buildRuntime(dto);
        return ChapterWorkshopResultDTO.builder()
                .cPrompt(runtime.cPrompt())
                .recalledContext(runtime.recalled())
                .consistencyReport(runtime.report())
                .generatedDraft(null)
                .generatedDrafts(List.of())
                .generatedDraftLabels(List.of())
                .build();
    }

    public ChapterWorkshopResultDTO generateDraft(ChapterWorkshopIntentDTO dto) {
        WorkshopRuntime runtime = buildRuntime(dto);

        // 调用真正的 AI 服务
        String generatedText = aiGenerationService.generate(runtime.cPrompt());

        List<String> drafts = List.of(generatedText);
        List<String> labels = List.of("AI 生成");

        ConsistencyReport report = consistencyChecker.check(runtime.context(), generatedText);

        return ChapterWorkshopResultDTO.builder()
                .cPrompt(runtime.cPrompt())
                .recalledContext(runtime.recalled())
                .consistencyReport(report)
                .generatedDraft(generatedText)
                .generatedDrafts(drafts)
                .generatedDraftLabels(labels)
                .build();
    }

    public ConsistencyReport checkConsistency(AiConsistencyCheckDTO dto) {
        ChapterWorkshopIntentDTO intent = new ChapterWorkshopIntentDTO();
        intent.setNovelId(dto.getNovelId());
        intent.setCoreEvent(dto.getCoreEvent() == null || dto.getCoreEvent().isBlank() ? "一致性检测" : dto.getCoreEvent());
        intent.setSceneLocation(dto.getSceneLocation());
        intent.setAtmosphere(dto.getAtmosphere());
        intent.setEmotionalTone(dto.getEmotionalTone());
        intent.setGenerationMode(dto.getGenerationMode());
        intent.setPresentCharacterIds(dto.getPresentCharacterIds());
        intent.setRelatedOutlineNodes(dto.getRelatedOutlineNodes());

        WorkshopRuntime runtime = buildRuntime(intent);
        return consistencyChecker.check(runtime.context(), dto.getGeneratedText());
    }

    public PlotSuggestionResultDTO suggestPlot(PlotSuggestionRequestDTO dto) {
        Novel novel = novelRepository.selectById(dto.getNovelId());
        if (novel == null || Integer.valueOf(1).equals(novel.getIsDeleted())) {
            throw new ResourceNotFoundException("Novel", dto.getNovelId());
        }
        Long userId = ThreadLocalUtil.getUserId();
        if (!novel.getUserId().equals(userId)) {
            throw new BusinessException(403, "Access denied");
        }

        String anchor = trimToSentence(dto.getCurrentContent());
        String core = nullToFallback(dto.getCoreEvent(), "当前冲突");
        String scene = nullToFallback(dto.getSceneLocation(), "当前场景");
        String tone = nullToFallback(dto.getEmotionalTone(), "紧张");

        PlotSuggestionResultDTO out = new PlotSuggestionResultDTO();
        out.getSuggestions().add("推进主线：围绕\u201c" + core + "\u201d加入一个即时阻碍，让角色必须当场做选择。");
        out.getSuggestions().add("场景利用：把\u201c" + scene + "\u201d中的可互动元素转为剧情触发器，制造信息差。");
        out.getSuggestions().add("情绪拉升：在\u201c" + tone + "\u201d基调下插入一次误判或反转，强化读者预期落差。");
        out.getSuggestions().add("伏笔回收：从当前段\u201c" + anchor + "\u201d抽取关键词，回收一个旧伏笔并抛出新钩子。");
        out.getSuggestions().add("章末钩子：以未完成目标或新威胁收束本章，驱动下一章开场冲突。");
        return out;
    }

    private WorkshopRuntime buildRuntime(ChapterWorkshopIntentDTO dto) {
        Novel novel = novelRepository.selectById(dto.getNovelId());
        if (novel == null || Integer.valueOf(1).equals(novel.getIsDeleted())) {
            throw new ResourceNotFoundException("Novel", dto.getNovelId());
        }
        Long userId = ThreadLocalUtil.getUserId();
        if (!novel.getUserId().equals(userId)) {
            throw new BusinessException(403, "Access denied");
        }

        novelVectorMemoryService.seedNarrativeMemory(dto.getNovelId());

        com.starrynight.engine.workflow.ContextWeaver contextWeaver = new com.starrynight.engine.workflow.ContextWeaver(hybridRetriever, contextTruncator);
        CPromptBuilder promptBuilder = new CPromptBuilder();

        com.starrynight.engine.workflow.WritingIntent intent = toIntent(dto);
        CPromptContext context = contextWeaver.weave(intent);
        String cPrompt = promptBuilder.build(context);
        List<String> recalled = context.getConstraints().stream()
                .map(VectorEntry::getChunk)
                .toList();
        ConsistencyReport report = consistencyChecker.check(context, cPrompt);
        return new WorkshopRuntime(context, cPrompt, recalled, report);
    }

    private com.starrynight.engine.workflow.WritingIntent toIntent(ChapterWorkshopIntentDTO dto) {
        com.starrynight.engine.workflow.WritingIntent intent = new com.starrynight.engine.workflow.WritingIntent();
        intent.setNovelId(dto.getNovelId());
        intent.setCoreEvent(dto.getCoreEvent());
        intent.setEmotionalTone(dto.getEmotionalTone());
        intent.setGenerationMode(dto.getGenerationMode());
        intent.setRelatedOutlineNodes(dto.getRelatedOutlineNodes() == null ? new ArrayList<>() : dto.getRelatedOutlineNodes());

        com.starrynight.engine.workflow.WritingIntent.CurrentState state = new com.starrynight.engine.workflow.WritingIntent.CurrentState();
        state.setSceneLocation(dto.getSceneLocation());
        state.setAtmosphere(dto.getAtmosphere());
        intent.setCurrentState(state);

        List<com.starrynight.engine.workflow.WritingIntent.PresentCharacter> characters = new ArrayList<>();
        if (dto.getPresentCharacterIds() != null) {
            for (String id : dto.getPresentCharacterIds()) {
                if (id == null || id.isBlank()) {
                    continue;
                }
                com.starrynight.engine.workflow.WritingIntent.PresentCharacter c = new com.starrynight.engine.workflow.WritingIntent.PresentCharacter();
                c.setCharacterId(id);
                characters.add(c);
            }
        }
        intent.setPresentCharacters(characters);
        return intent;
    }

    private String nullToFallback(String value, String fallback) {
        if (value == null || value.isBlank()) {
            return fallback;
        }
        return value;
    }

    private String trimToSentence(String text) {
        if (text == null || text.isBlank()) {
            return "";
        }
        String line = text.replace("\n", " ").replace("\r", " ").trim();
        return line.length() > 120 ? line.substring(0, 120) + "..." : line;
    }

    private record WorkshopRuntime(CPromptContext context,
                                   String cPrompt,
                                   List<String> recalled,
                                   ConsistencyReport report) {
    }
}
