package com.starrynight.starrynight.system.novel.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.starrynight.engine.consistency.ConsistencyReport;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.framework.common.exception.ResourceNotFoundException;
import com.starrynight.starrynight.framework.common.util.ThreadLocalUtil;
import com.starrynight.starrynight.services.ai.AiGenerationService;
import com.starrynight.starrynight.system.novel.dto.AiConsistencyCheckDTO;
import com.starrynight.starrynight.system.novel.dto.ChapterDraftConnectionCheckDTO;
import com.starrynight.starrynight.system.novel.dto.ChapterDraftConnectionIssueDTO;
import com.starrynight.starrynight.system.novel.dto.ChapterDraftDTO;
import com.starrynight.starrynight.system.novel.dto.ChapterDraftGenerateDTO;
import com.starrynight.starrynight.system.novel.entity.Novel;
import com.starrynight.starrynight.system.novel.entity.NovelChapter;
import com.starrynight.starrynight.system.novel.entity.NovelOutline;
import com.starrynight.starrynight.system.novel.entity.NovelVolume;
import com.starrynight.starrynight.system.novel.repository.NovelChapterRepository;
import com.starrynight.starrynight.system.novel.repository.NovelOutlineRepository;
import com.starrynight.starrynight.system.novel.repository.NovelRepository;
import com.starrynight.starrynight.services.engine.NovelVectorMemoryService;
import com.starrynight.starrynight.system.novel.repository.NovelVolumeRepository;
import org.springframework.stereotype.Service;

import java.util.ArrayList;
import java.util.Comparator;
import java.util.List;
import java.util.stream.Collectors;

@Service
public class ChapterDraftService {

    private final NovelVolumeRepository volumeRepository;
    private final NovelRepository novelRepository;
    private final NovelOutlineRepository outlineRepository;
    private final NovelChapterRepository chapterRepository;
    private final ChapterWorkshopService chapterWorkshopService;
    private final AiGenerationService aiGenerationService;
    private final NovelVectorMemoryService novelVectorMemoryService;

    public ChapterDraftService(NovelVolumeRepository volumeRepository,
                               NovelRepository novelRepository,
                               NovelOutlineRepository outlineRepository,
                               NovelChapterRepository chapterRepository,
                               ChapterWorkshopService chapterWorkshopService,
                               AiGenerationService aiGenerationService,
                               NovelVectorMemoryService novelVectorMemoryService) {
        this.volumeRepository = volumeRepository;
        this.novelRepository = novelRepository;
        this.outlineRepository = outlineRepository;
        this.chapterRepository = chapterRepository;
        this.chapterWorkshopService = chapterWorkshopService;
        this.aiGenerationService = aiGenerationService;
        this.novelVectorMemoryService = novelVectorMemoryService;
    }

    public List<ChapterDraftDTO> generate(ChapterDraftGenerateDTO req) {
        NovelVolume volume = volumeRepository.selectById(req.getVolumeId());
        if (volume == null) {
            throw new ResourceNotFoundException("Volume", req.getVolumeId());
        }

        Novel novel = novelRepository.selectById(volume.getNovelId());
        if (novel == null) {
            throw new ResourceNotFoundException("Novel", volume.getNovelId());
        }
        Long userId = ThreadLocalUtil.getUserId();
        if (!novel.getUserId().equals(userId)) {
            throw new BusinessException(403, "Access denied");
        }

        String volumeOutline = loadVolumeOutline(volume.getNovelId(), volume.getId());
        String previousEnding = loadLatestChapterEnding(volume.getNovelId(), volume.getId());
        int targetWordCount = req.getTargetWordCount() == null ? 2500 : req.getTargetWordCount();
        String chapterType = req.getChapterType() == null || req.getChapterType().isBlank()
                ? "standard" : req.getChapterType();

        if (req.getChapterNo() != null && req.getChapterNo() > req.getChapterCount()) {
            throw new BusinessException(400, "Chapter no cannot be greater than chapter count");
        }

        List<ChapterDraftDTO> drafts = new ArrayList<>();
        int start = req.getChapterNo() == null ? 1 : req.getChapterNo();
        int end = req.getChapterNo() == null ? req.getChapterCount() : req.getChapterNo();

        for (int i = start; i <= end; i++) {
            String draftPrompt = buildChapterDraftPrompt(novel, volume, volumeOutline, previousEnding,
                    i, end, targetWordCount, chapterType);
            String memoryPrefix = novelVectorMemoryService.buildRecallConstraintBlock(
                    novel.getId(),
                    "第" + i + "章细纲 · " + (volume.getTitle() != null ? volume.getTitle() : ""),
                    volumeOutline,
                    null);
            String fullPrompt = memoryPrefix.isBlank() ? draftPrompt : memoryPrefix + "\n" + draftPrompt;
            String aiContent = aiGenerationService.generate(fullPrompt);

            ChapterDraftDTO draft;
            if (aiContent != null && !aiContent.isBlank()) {
                draft = parseAiChapterDraft(aiContent, i, volume, targetWordCount, chapterType);
            } else {
                draft = buildDraft(i, req.getChapterCount(), targetWordCount, chapterType, volume, volumeOutline, previousEnding);
            }

            AiConsistencyCheckDTO checkDTO = new AiConsistencyCheckDTO();
            checkDTO.setNovelId(novel.getId());
            checkDTO.setGeneratedText(serializeDraftForCheck(draft));
            checkDTO.setCoreEvent(draft.getCoreEvent());
            if (draft.getSceneSetting() != null) {
                checkDTO.setSceneLocation(draft.getSceneSetting().getLocation());
                checkDTO.setAtmosphere(draft.getSceneSetting().getAtmosphere());
            }

            ConsistencyReport report = chapterWorkshopService.checkConsistency(checkDTO);
            draft.setConsistencyReport(report);

            drafts.add(draft);
        }
        return drafts;
    }

    private String buildChapterDraftPrompt(Novel novel, NovelVolume volume, String volumeOutline,
                                           String previousEnding, int chapterNo, int totalChapters,
                                           int targetWordCount, String chapterType) {
        StringBuilder prompt = new StringBuilder();
        prompt.append("你是一位资深网文写作助手，擅长设计章节细纲。\n\n");
        prompt.append("作品信息：\n");
        prompt.append("- 作品名称：").append(novel.getTitle() != null ? novel.getTitle() : "未命名").append("\n");
        prompt.append("- 作品类型：").append(novel.getGenre() != null ? novel.getGenre() : "通用").append("\n");
        prompt.append("- 卷标题：").append(volume.getTitle()).append("\n\n");

        prompt.append("卷纲摘要：\n").append(volumeOutline).append("\n\n");

        prompt.append("前章结尾：\n").append(previousEnding).append("\n\n");

        prompt.append("章节要求：\n");
        prompt.append("- 章节序号：第").append(chapterNo).append("章（共").append(totalChapters).append("章）\n");
        prompt.append("- 目标字数：").append(targetWordCount).append("字\n");
        prompt.append("- 章节类型：").append(chapterType).append("\n\n");

        prompt.append("请为这一章生成细纲，包括：\n");
        prompt.append("1. 章节标题\n");
        prompt.append("2. 核心事件\n");
        prompt.append("3. 场景设定（地点、时间、氛围）\n");
        prompt.append("4. 出场角色\n");
        prompt.append("5. 情节点（至少5个：opening/development/turning_point/climax/ending）\n");
        prompt.append("6. 关键对白\n");
        prompt.append("7. 伏笔设置\n\n");

        prompt.append("请用JSON格式输出，字段包括：title, coreEvent, sceneSetting{location,time,atmosphere}, ");
        prompt.append("charactersPresent[{name,chapterGoal,status}], ");
        prompt.append("plotPoints[{order,type,description,emotionalChange}], ");
        prompt.append("keyDialogues[{speaker,content,purpose}], ");
        prompt.append("foreshadowing[{setup,type}]\n");

        return prompt.toString();
    }

    private ChapterDraftDTO parseAiChapterDraft(String aiContent, int chapterNo, NovelVolume volume,
                                                int targetWordCount, String chapterType) {
        ChapterDraftDTO dto = new ChapterDraftDTO();
        dto.setChapterNo(chapterNo);
        dto.setStatus("draft");
        dto.setVersion(1);

        try {
            dto.setTitle(extractJsonField(aiContent, "title", "第" + chapterNo + "章·" + inferStageTitle(chapterNo, 10)));
            dto.setCoreEvent(extractJsonField(aiContent, "coreEvent", "围绕主线推进关键转折"));

            String sceneJson = extractJsonObject(aiContent, "sceneSetting");
            if (sceneJson != null) {
                ChapterDraftDTO.SceneSetting setting = new ChapterDraftDTO.SceneSetting();
                setting.setLocation(extractJsonField(sceneJson, "location", volume.getTitle() + "相关场景"));
                setting.setTime(extractJsonField(sceneJson, "time", "日"));
                setting.setAtmosphere(extractJsonField(sceneJson, "atmosphere", "紧张"));
                dto.setSceneSetting(setting);
            }
        } catch (Exception e) {
        }

        if (dto.getTitle() == null) {
            dto.setTitle("第" + chapterNo + "章·" + inferStageTitle(chapterNo, 10));
        }
        if (dto.getCoreEvent() == null) {
            dto.setCoreEvent("围绕主线推进关键转折");
        }
        if (dto.getSceneSetting() == null) {
            ChapterDraftDTO.SceneSetting setting = new ChapterDraftDTO.SceneSetting();
            setting.setLocation(volume.getTitle() + "相关场景");
            setting.setTime("日");
            setting.setAtmosphere("紧张");
            dto.setSceneSetting(setting);
        }

        dto.setCharactersPresent(buildCharacters(chapterNo));
        dto.setPlotPoints(buildPlotPoints(chapterNo, targetWordCount, chapterType));
        dto.setKeyDialogues(buildDialogues(chapterNo));
        dto.setForeshadowing(buildForeshadowing(chapterNo, 10));

        return dto;
    }

    private String extractJsonField(String json, String fieldName, String defaultValue) {
        String pattern = "\"" + fieldName + "\"\\s*:\\s*\"([^\"]+)\"";
        java.util.regex.Pattern p = java.util.regex.Pattern.compile(pattern);
        java.util.regex.Matcher m = p.matcher(json);
        if (m.find()) {
            return m.group(1);
        }
        return defaultValue;
    }

    private String extractJsonObject(String json, String objectName) {
        String pattern = "\"" + objectName + "\"\\s*:\\s*\\{([^}]+)\\}";
        java.util.regex.Pattern p = java.util.regex.Pattern.compile(pattern);
        java.util.regex.Matcher m = p.matcher(json);
        if (m.find()) {
            return "{" + m.group(1) + "}";
        }
        return null;
    }

    public List<ChapterDraftConnectionIssueDTO> checkConnections(ChapterDraftConnectionCheckDTO req) {
        NovelVolume volume = volumeRepository.selectById(req.getVolumeId());
        if (volume == null) {
            throw new ResourceNotFoundException("Volume", req.getVolumeId());
        }
        Novel novel = novelRepository.selectById(volume.getNovelId());
        if (novel == null) {
            throw new ResourceNotFoundException("Novel", volume.getNovelId());
        }
        Long userId = ThreadLocalUtil.getUserId();
        if (!novel.getUserId().equals(userId)) {
            throw new BusinessException(403, "Access denied");
        }

        List<ChapterDraftDTO> drafts = new ArrayList<>(req.getDrafts());
        drafts.sort(Comparator.comparingInt(d -> d.getChapterNo() == null ? Integer.MAX_VALUE : d.getChapterNo()));

        List<ChapterDraftConnectionIssueDTO> issues = new ArrayList<>();
        for (int i = 0; i < drafts.size(); i++) {
            ChapterDraftDTO current = drafts.get(i);
            if (current.getChapterNo() == null) {
                issues.add(issue(null, "error", "存在未设置 chapterNo 的细纲卡片", "请为卡片补齐章节序号"));
                continue;
            }
            if (current.getTitle() == null || current.getTitle().isBlank()) {
                issues.add(issue(current.getChapterNo(), "warn", "章节标题为空", "请补齐章节标题，便于后续管理"));
            }
            if (current.getCoreEvent() == null || current.getCoreEvent().isBlank()) {
                issues.add(issue(current.getChapterNo(), "warn", "核心事件为空", "请补齐本章核心事件"));
            }
            if (current.getPlotPoints() == null || current.getPlotPoints().size() < 5) {
                issues.add(issue(current.getChapterNo(), "warn", "情节点不足（建议至少5个）", "补齐 opening/development/turning_point/climax/ending"));
            }
        }

        for (int i = 1; i < drafts.size(); i++) {
            ChapterDraftDTO prev = drafts.get(i - 1);
            ChapterDraftDTO next = drafts.get(i);
            if (prev.getChapterNo() == null || next.getChapterNo() == null) {
                continue;
            }

            String note = next.getConnectionNote() == null ? "" : next.getConnectionNote();
            if (!note.contains("上章")) {
                issues.add(issue(next.getChapterNo(), "warn",
                        "未检测到明确的上章衔接说明",
                        "建议在 connectionNote 中补充承接点，如\u201c承接第" + prev.getChapterNo() + "章结尾状态...\u201d"));
            }

            String prevEnding = plotDesc(prev, "ending");
            String nextOpening = plotDesc(next, "opening");
            if (!prevEnding.isBlank() && !nextOpening.isBlank() && commonPrefixLen(prevEnding, nextOpening) >= 6) {
                issues.add(issue(next.getChapterNo(), "info",
                        "本章 opening 与上章 ending 相似度偏高",
                        "建议增强过渡：换一个切入视角/动作承接/时间推进句"));
            }
        }

        return issues;
    }

    private ChapterDraftConnectionIssueDTO issue(Integer chapterNo, String level, String message, String suggestion) {
        ChapterDraftConnectionIssueDTO i = new ChapterDraftConnectionIssueDTO();
        i.setChapterNo(chapterNo);
        i.setLevel(level);
        i.setMessage(message);
        i.setSuggestion(suggestion);
        return i;
    }

    private String plotDesc(ChapterDraftDTO d, String type) {
        if (d.getPlotPoints() == null) {
            return "";
        }
        for (ChapterDraftDTO.PlotPoint p : d.getPlotPoints()) {
            if (p == null || p.getType() == null) continue;
            if (type.equals(p.getType()) && p.getDescription() != null) {
                return p.getDescription();
            }
        }
        return "";
    }

    private int commonPrefixLen(String a, String b) {
        int n = Math.min(a.length(), b.length());
        int i = 0;
        for (; i < n; i++) {
            if (a.charAt(i) != b.charAt(i)) break;
        }
        return i;
    }

    private String loadVolumeOutline(Long novelId, Long volumeId) {
        NovelOutline outline = outlineRepository.selectOne(
                new LambdaQueryWrapper<NovelOutline>()
                        .eq(NovelOutline::getNovelId, novelId)
                        .eq(NovelOutline::getVolumeId, volumeId)
                        .eq(NovelOutline::getType, "volume_outline")
                        .orderByDesc(NovelOutline::getId)
                        .last("limit 1")
        );
        if (outline == null || outline.getContent() == null || outline.getContent().isBlank()) {
            return "本卷冲突升级，推进主线并埋设下卷伏笔";
        }
        return outline.getContent();
    }

    private String loadLatestChapterEnding(Long novelId, Long volumeId) {
        NovelChapter chapter = chapterRepository.selectOne(
                new LambdaQueryWrapper<NovelChapter>()
                        .eq(NovelChapter::getNovelId, novelId)
                        .eq(NovelChapter::getVolumeId, volumeId)
                        .orderByDesc(NovelChapter::getChapterOrder)
                        .orderByDesc(NovelChapter::getId)
                        .last("limit 1")
        );
        if (chapter == null || chapter.getContent() == null || chapter.getContent().isBlank()) {
            return "暂无前章内容，需在本章快速建立冲突背景。";
        }
        String text = chapter.getContent().replace("\n", " ").trim();
        return text.length() > 90 ? text.substring(0, 90) + "..." : text;
    }

    private ChapterDraftDTO buildDraft(int chapterNo,
                                       int chapterTotal,
                                       int targetWordCount,
                                       String chapterType,
                                       NovelVolume volume,
                                       String volumeOutline,
                                       String previousEnding) {
        ChapterDraftDTO dto = new ChapterDraftDTO();
        dto.setChapterNo(chapterNo);
        dto.setTitle("第" + chapterNo + "章\u00b7" + inferStageTitle(chapterNo, chapterTotal));
        dto.setCoreEvent("围绕\u300c" + extractCore(volumeOutline) + "\u300d推进关键转折");
        dto.setConnectionNote("上章结尾衔接: " + previousEnding);
        dto.setStatus("draft");
        dto.setVersion(1);

        ChapterDraftDTO.SceneSetting setting = new ChapterDraftDTO.SceneSetting();
        setting.setLocation(volume.getTitle() + "相关场景");
        setting.setTime("夜");
        setting.setAtmosphere(chapterNo == chapterTotal ? "压迫后释放" : "渐进紧张");
        dto.setSceneSetting(setting);

        dto.setCharactersPresent(buildCharacters(chapterNo));
        dto.setPlotPoints(buildPlotPoints(chapterNo, targetWordCount, chapterType));
        dto.setKeyDialogues(buildDialogues(chapterNo));
        dto.setForeshadowing(buildForeshadowing(chapterNo, chapterTotal));
        return dto;
    }

    private List<ChapterDraftDTO.CharacterPresent> buildCharacters(int chapterNo) {
        List<ChapterDraftDTO.CharacterPresent> list = new ArrayList<>();
        ChapterDraftDTO.CharacterPresent protagonist = new ChapterDraftDTO.CharacterPresent();
        protagonist.setName("主角");
        protagonist.setChapterGoal(chapterNo % 2 == 0 ? "突破当前困局" : "确认关键线索");
        protagonist.setStatus("承压推进");
        list.add(protagonist);

        ChapterDraftDTO.CharacterPresent supporting = new ChapterDraftDTO.CharacterPresent();
        supporting.setName("关键配角");
        supporting.setChapterGoal("提供信息并制造分歧");
        supporting.setStatus("立场摇摆");
        list.add(supporting);
        return list;
    }

    private List<ChapterDraftDTO.PlotPoint> buildPlotPoints(int chapterNo, int targetWordCount, String chapterType) {
        List<ChapterDraftDTO.PlotPoint> list = new ArrayList<>();
        list.add(point(1, "opening", "交代当前局势与目标，建立本章张力", "平稳\u2192紧张"));
        list.add(point(2, "development", "推进调查/行动，暴露新障碍（目标字数约" + targetWordCount + "）", "紧张\u2192压迫"));
        list.add(point(3, "turning_point", "关键线索反转，角色决策出现分歧（类型:" + chapterType + "）", "压迫\u2192震荡"));
        list.add(point(4, "climax", "正面对抗或情绪爆发，主线冲突达到峰值", "震荡\u2192高点"));
        list.add(point(5, "ending", "收束后抛出新问题，为下章留钩子", "高点\u2192悬念"));
        return list;
    }

    private ChapterDraftDTO.PlotPoint point(int order, String type, String description, String emotion) {
        ChapterDraftDTO.PlotPoint p = new ChapterDraftDTO.PlotPoint();
        p.setOrder(order);
        p.setType(type);
        p.setDescription(description);
        p.setEmotionalChange(emotion);
        return p;
    }

    private List<ChapterDraftDTO.KeyDialogue> buildDialogues(int chapterNo) {
        List<ChapterDraftDTO.KeyDialogue> list = new ArrayList<>();
        ChapterDraftDTO.KeyDialogue d1 = new ChapterDraftDTO.KeyDialogue();
        d1.setSpeaker("主角");
        d1.setContent("我不是在赌命，我是在赌我们还有明天。");
        d1.setPurpose("明确主角决心");
        list.add(d1);

        ChapterDraftDTO.KeyDialogue d2 = new ChapterDraftDTO.KeyDialogue();
        d2.setSpeaker("关键配角");
        d2.setContent(chapterNo % 2 == 0 ? "你终于承认自己害怕了。" : "你终于开始像个领队了。");
        d2.setPurpose("制造人物关系张力");
        list.add(d2);
        return list;
    }

    private List<ChapterDraftDTO.Foreshadowing> buildForeshadowing(int chapterNo, int total) {
        List<ChapterDraftDTO.Foreshadowing> list = new ArrayList<>();
        ChapterDraftDTO.Foreshadowing f = new ChapterDraftDTO.Foreshadowing();
        f.setSetup(chapterNo == total ? "结尾出现异常坐标，指向下一卷核心秘密" : "次要道具被反复提及但用途未明");
        f.setType("plot");
        list.add(f);
        return list;
    }

    private String inferStageTitle(int chapterNo, int total) {
        if (chapterNo == 1) {
            return "开局立势";
        }
        if (chapterNo == total) {
            return "悬念封口";
        }
        if (chapterNo >= (int) Math.ceil(total * 0.7)) {
            return "冲突逼近";
        }
        return "局势推进";
    }

    private String extractCore(String outline) {
        String text = outline.replace("\n", " ").trim();
        if (text.isBlank()) {
            return "主线冲突";
        }
        return text.length() > 18 ? text.substring(0, 18) + "..." : text;
    }

    private String serializeDraftForCheck(ChapterDraftDTO draft) {
        StringBuilder sb = new StringBuilder();
        sb.append("Title: ").append(draft.getTitle()).append("\n");
        sb.append("Core Event: ").append(draft.getCoreEvent()).append("\n");
        if (draft.getPlotPoints() != null) {
            String plotPoints = draft.getPlotPoints().stream()
                    .map(p -> p.getType() + ": " + p.getDescription())
                    .collect(Collectors.joining("\n- ", "\nPlot Points:\n- ", ""));
            sb.append(plotPoints);
        }
        return sb.toString();
    }
}
