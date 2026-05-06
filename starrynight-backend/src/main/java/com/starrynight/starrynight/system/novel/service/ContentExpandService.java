package com.starrynight.starrynight.system.novel.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.framework.common.exception.ResourceNotFoundException;
import com.starrynight.starrynight.framework.common.util.ThreadLocalUtil;
import com.starrynight.starrynight.services.ai.AiGenerationService;
import com.starrynight.starrynight.system.novel.dto.ContinueWritingRequestDTO;
import com.starrynight.starrynight.system.novel.dto.ContentExpandRequestDTO;
import com.starrynight.starrynight.system.novel.dto.ContentExpandResultDTO;
import com.starrynight.starrynight.system.novel.dto.ContentVersionItemDTO;
import com.starrynight.starrynight.system.novel.dto.ContentVersionSaveDTO;
import com.starrynight.starrynight.system.novel.entity.Novel;
import com.starrynight.starrynight.system.novel.entity.NovelOutline;
import com.starrynight.starrynight.services.engine.NovelVectorMemoryService;
import com.starrynight.starrynight.system.novel.repository.NovelOutlineRepository;
import com.starrynight.starrynight.system.novel.repository.NovelRepository;
import org.springframework.beans.BeanUtils;
import org.springframework.stereotype.Service;

import java.time.format.DateTimeFormatter;
import java.util.ArrayList;
import java.util.Comparator;
import java.util.List;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

@Service
public class ContentExpandService {

    private final NovelOutlineRepository outlineRepository;
    private final NovelRepository novelRepository;
    private final AiGenerationService aiGenerationService;
    private final NovelVectorMemoryService novelVectorMemoryService;
    private static final String CONTENT_VERSION_TYPE = "chapter_content_version";
    private static final String DRAFT_VERSION_TYPE = "chapter_draft_version";

    public ContentExpandService(NovelOutlineRepository outlineRepository,
                               NovelRepository novelRepository,
                               AiGenerationService aiGenerationService,
                               NovelVectorMemoryService novelVectorMemoryService) {
        this.outlineRepository = outlineRepository;
        this.novelRepository = novelRepository;
        this.aiGenerationService = aiGenerationService;
        this.novelVectorMemoryService = novelVectorMemoryService;
    }

    public ContentExpandResultDTO preview(ContentExpandRequestDTO req) {
        NovelOutline outline = loadAndCheckChapterOutline(req.getChapterOutlineId());

        String outlineText = outline.getContent() == null ? "" : outline.getContent();
        if (outlineText.isBlank()) {
            throw new BusinessException(400, "Chapter outline content is empty");
        }

        int ratio = req.getExpandRatio() == null ? 2 : req.getExpandRatio();
        boolean optimizeConnections = req.getOptimizeConnections() == null || req.getOptimizeConnections();
        boolean postProcessEnabled = req.getPostProcessEnabled() == null || req.getPostProcessEnabled();
        ContentExpandResultDTO out = new ContentExpandResultDTO();

        ContentExpandResultDTO.StyleFingerprint fp = null;
        if (req.getStyleSample() != null && !req.getStyleSample().isBlank()) {
            fp = analyzeStyle(req.getStyleSample());
        }
        out.setStyleFingerprint(fp);

        String expandPrompt = buildExpandPrompt(outlineText, ratio, fp);
        Long novelId = outline.getNovelId();
        String memoryPrefix = novelVectorMemoryService.buildRecallConstraintBlock(
                novelId,
                outline.getTitle() != null ? outline.getTitle() + "\n" + outlineText : outlineText,
                null,
                null);
        if (!memoryPrefix.isBlank()) {
            expandPrompt = memoryPrefix + "\n" + expandPrompt;
        }
        String aiContent = aiGenerationService.generate(expandPrompt);

        if (aiContent != null && !aiContent.isBlank()) {
            List<ContentExpandResultDTO.Segment> segments = parseExpandResponse(aiContent);
            out.setSegments(segments);

            String content = String.join("\n\n", segments.stream().map(ContentExpandResultDTO.Segment::getText).toList());
            if (optimizeConnections) {
                content = optimizeConnections(content);
            }
            out.setContent(postProcessEnabled ? postProcess(content) : content);
        } else {
            List<String> points = extractPlotPoints(outlineText);
            if (points.isEmpty()) {
                points = List.of(outlineText.trim());
            }

            List<ContentExpandResultDTO.Segment> segments = new ArrayList<>();
            segments.add(seg("opening", expandParagraph(points, 0, ratio, fp)));
            segments.add(seg("development", expandParagraph(points, 1, ratio, fp)));
            segments.add(seg("climax", expandParagraph(points, 2, ratio, fp)));
            segments.add(seg("ending", expandParagraph(points, 3, ratio, fp)));
            out.setSegments(segments);

            String content = String.join("\n\n", segments.stream().map(ContentExpandResultDTO.Segment::getText).toList());
            if (optimizeConnections) {
                content = optimizeConnections(content);
            }
            out.setContent(postProcessEnabled ? postProcess(content) : content);
        }
        out.setGenerationPlan(buildPlan(ratio, fp));
        out.setWordCount(countWords(out.getContent()));
        return out;
    }

    private String buildExpandPrompt(String outlineText, int ratio, ContentExpandResultDTO.StyleFingerprint fp) {
        StringBuilder prompt = new StringBuilder();
        prompt.append("你是一位专业的小说写作助手，擅长根据章节细纲扩写正文。\n\n");
        prompt.append("章节细纲：\n").append(outlineText).append("\n\n");
        prompt.append("扩写要求：\n");
        prompt.append("- 扩写比例：").append(ratio).append("倍\n");
        prompt.append("- 保持细纲中的核心事件、人物和情节走向\n");
        prompt.append("- 开篇要迅速建立场景和冲突\n");
        prompt.append("- 发展段要逐步加深冲突和压力\n");
        prompt.append("- 高潮段要有情绪爆发或关键转折\n");
        prompt.append("- 结尾段要收束并为下一章留下钩子\n\n");

        if (fp != null) {
            prompt.append("风格参考：\n");
            if (fp.getPacingType() != null) {
                prompt.append("- 节奏：").append(fp.getPacingType()).append("\n");
            }
            if (fp.getDialogueRatio() != null) {
                prompt.append("- 对话比例：").append(String.format("%.0f%%", fp.getDialogueRatio() * 100)).append("\n");
            }
        }

        prompt.append("\n请生成完整的章节正文，保持故事的连贯性和代入感。");
        return prompt.toString();
    }

    private List<ContentExpandResultDTO.Segment> parseExpandResponse(String aiContent) {
        List<ContentExpandResultDTO.Segment> segments = new ArrayList<>();

        String[] paragraphs = aiContent.split("\n\n");
        int sectionIndex = 0;
        String[] sectionNames = {"opening", "development", "climax", "ending"};

        for (String para : paragraphs) {
            para = para.trim();
            if (para.isEmpty()) continue;

            String type = sectionIndex < sectionNames.length ? sectionNames[sectionIndex] : "content_" + sectionIndex;
            segments.add(seg(type, para));
            sectionIndex++;
        }

        if (segments.isEmpty()) {
            segments.add(seg("content", aiContent));
        }

        return segments;
    }

    /**
     * 智能续写：在现有文本末尾生成后续片段，并保持语气与风格（可选）。
     *
     * 说明：当前实现使用“扩写”算法的轻量变体：
     * - 以 sourceContent 的最后一句/末尾片段作为续写锚点
     * - 使用相同的分段生成与衔接优化逻辑
     */
    public ContentExpandResultDTO continueWriting(ContinueWritingRequestDTO req) {
        if (req == null || req.getSourceContent() == null || req.getSourceContent().isBlank()) {
            throw new BusinessException(400, "sourceContent is required");
        }

        String base = req.getSourceContent().trim();
        int ratio = req.getExpandRatio() == null ? 2 : req.getExpandRatio();
        boolean optimizeConnections = req.getOptimizeConnections() == null || req.getOptimizeConnections();
        boolean postProcessEnabled = req.getPostProcessEnabled() == null || req.getPostProcessEnabled();

        ContentExpandResultDTO out = new ContentExpandResultDTO();

        ContentExpandResultDTO.StyleFingerprint fp = null;
        if (req.getStyleSample() != null && !req.getStyleSample().isBlank()) {
            fp = analyzeStyle(req.getStyleSample());
        }
        out.setStyleFingerprint(fp);

        String anchor = extractLastAnchor(base);
        if (anchor.isBlank()) {
            anchor = base;
        }

        String continuePrompt = buildContinuePrompt(base, anchor, ratio, fp);
        if (req.getNovelId() != null) {
            String memoryPrefix = novelVectorMemoryService.buildRecallConstraintBlock(
                    req.getNovelId(),
                    anchor,
                    null,
                    null);
            if (!memoryPrefix.isBlank()) {
                continuePrompt = memoryPrefix + "\n" + continuePrompt;
            }
        }
        String aiContinuation = aiGenerationService.generate(continuePrompt);

        String continuation;
        if (aiContinuation != null && !aiContinuation.isBlank()) {
            continuation = aiContinuation;
        } else {
            List<String> points = List.of(anchor);
            List<ContentExpandResultDTO.Segment> segments = new ArrayList<>();
            segments.add(seg("continue_opening", expandParagraph(points, 0, ratio, fp)));
            segments.add(seg("continue_development", expandParagraph(points, 1, ratio, fp)));
            segments.add(seg("continue_climax", expandParagraph(points, 2, ratio, fp)));
            segments.add(seg("continue_ending", expandParagraph(points, 3, ratio, fp)));
            continuation = String.join("\n\n", segments.stream().map(ContentExpandResultDTO.Segment::getText).toList());
        }

        if (optimizeConnections) {
            continuation = optimizeConnections(continuation);
        }
        continuation = postProcessEnabled ? postProcess(continuation) : continuation;

        out.setContent(base + "\n\n" + continuation);
        out.setWordCount(countWords(out.getContent()));
        return out;
    }

    private String buildContinuePrompt(String sourceContent, String anchor, int ratio, ContentExpandResultDTO.StyleFingerprint fp) {
        StringBuilder prompt = new StringBuilder();
        prompt.append("你是一位专业的小说写作助手，擅长续写故事。\n\n");
        prompt.append("当前正文结尾：\n").append(anchor).append("\n\n");
        prompt.append("续写要求：\n");
        prompt.append("- 续写长度：约").append(ratio * 500).append("字\n");
        prompt.append("- 保持与前文的连贯性和风格一致\n");
        prompt.append("- 自然衔接前文的情节和情绪\n");
        prompt.append("- 可以在结尾设置悬念或钩子\n\n");

        if (fp != null) {
            prompt.append("风格参考：\n");
            if (fp.getPacingType() != null) {
                prompt.append("- 节奏：").append(fp.getPacingType()).append("\n");
            }
            if (fp.getDialogueRatio() != null) {
                prompt.append("- 对话比例：").append(String.format("%.0f%%", fp.getDialogueRatio() * 100)).append("\n");
            }
        }

        prompt.append("\n请续写故事，保持代入感和故事张力。");
        return prompt.toString();
    }

    public ContentVersionItemDTO saveVersion(ContentVersionSaveDTO req) {
        NovelOutline chapterOutline = loadAndCheckChapterOutline(req.getChapterOutlineId());
        int nextVersion = nextVersion(chapterOutline.getId(), CONTENT_VERSION_TYPE);

        NovelOutline item = new NovelOutline();
        item.setNovelId(chapterOutline.getNovelId());
        item.setVolumeId(chapterOutline.getVolumeId());
        item.setChapterId(chapterOutline.getChapterId());
        item.setType(CONTENT_VERSION_TYPE);
        item.setTitle("扩写版本 V" + nextVersion + " @ " + DateTimeFormatter.ofPattern("MM-dd HH:mm").format(java.time.LocalDateTime.now()));
        item.setContent(req.getContent());
        item.setSortOrder(nextVersion);
        item.setParentId(chapterOutline.getId());
        item.setVersion(nextVersion);
        outlineRepository.insert(item);

        ContentVersionItemDTO dto = new ContentVersionItemDTO();
        BeanUtils.copyProperties(item, dto);
        dto.setSourceType("expanded");
        dto.setWordCount(countWords(item.getContent()));
        return dto;
    }

    public ContentVersionItemDTO saveDraftVersion(ContentVersionSaveDTO req) {
        NovelOutline chapterOutline = loadAndCheckChapterOutline(req.getChapterOutlineId());
        int nextVersion = nextVersion(chapterOutline.getId(), DRAFT_VERSION_TYPE);

        NovelOutline item = new NovelOutline();
        item.setNovelId(chapterOutline.getNovelId());
        item.setVolumeId(chapterOutline.getVolumeId());
        item.setChapterId(chapterOutline.getChapterId());
        item.setType(DRAFT_VERSION_TYPE);
        item.setTitle("草稿版本 V" + nextVersion + " @ " + DateTimeFormatter.ofPattern("MM-dd HH:mm").format(java.time.LocalDateTime.now()));
        item.setContent(req.getContent());
        item.setSortOrder(nextVersion);
        item.setParentId(chapterOutline.getId());
        item.setVersion(nextVersion);
        outlineRepository.insert(item);

        ContentVersionItemDTO dto = new ContentVersionItemDTO();
        BeanUtils.copyProperties(item, dto);
        dto.setSourceType("draft");
        dto.setWordCount(countWords(item.getContent()));
        return dto;
    }

    public List<ContentVersionItemDTO> listVersions(Long chapterOutlineId) {
        loadAndCheckChapterOutline(chapterOutlineId);
        List<NovelOutline> list = outlineRepository.selectList(
                new LambdaQueryWrapper<NovelOutline>()
                        .eq(NovelOutline::getType, CONTENT_VERSION_TYPE)
                        .eq(NovelOutline::getParentId, chapterOutlineId)
                        .orderByDesc(NovelOutline::getSortOrder)
                        .orderByDesc(NovelOutline::getId)
        );
        List<ContentVersionItemDTO> out = new ArrayList<>();
        for (NovelOutline n : list) {
            ContentVersionItemDTO dto = new ContentVersionItemDTO();
            BeanUtils.copyProperties(n, dto);
            dto.setSourceType("expanded");
            dto.setWordCount(countWords(n.getContent()));
            out.add(dto);
        }
        return out;
    }

    public List<ContentVersionItemDTO> listTimeline(Long chapterOutlineId) {
        loadAndCheckChapterOutline(chapterOutlineId);
        List<NovelOutline> list = outlineRepository.selectList(
                new LambdaQueryWrapper<NovelOutline>()
                        .in(NovelOutline::getType, CONTENT_VERSION_TYPE, DRAFT_VERSION_TYPE)
                        .eq(NovelOutline::getParentId, chapterOutlineId)
                        .orderByDesc(NovelOutline::getCreateTime)
                        .orderByDesc(NovelOutline::getId)
        );
        List<ContentVersionItemDTO> out = new ArrayList<>();
        for (NovelOutline n : list) {
            ContentVersionItemDTO dto = new ContentVersionItemDTO();
            BeanUtils.copyProperties(n, dto);
            dto.setSourceType(CONTENT_VERSION_TYPE.equals(n.getType()) ? "expanded" : "draft");
            dto.setWordCount(countWords(n.getContent()));
            out.add(dto);
        }
        out.sort(Comparator.comparing(ContentVersionItemDTO::getCreateTime, Comparator.nullsLast(Comparator.naturalOrder())).reversed());
        return out;
    }

    /**
     * 章节版本时间线（草稿 + 扩写），对齐开发文档的“章节版本历史”接口语义。
     *
     * 说明：当前项目将 {@code chapter_outline} 作为“章节ID”来源，版本的 parentId 也挂载在该节点上。
     */
    public List<ContentVersionItemDTO> listTimelineForChapter(Long novelId, Long chapterOutlineId) {
        NovelOutline outline = loadAndCheckChapterOutline(chapterOutlineId);
        if (novelId == null || !novelId.equals(outline.getNovelId())) {
            throw new BusinessException(403, "Access denied");
        }
        return listTimeline(chapterOutlineId);
    }

    public ContentVersionItemDTO rollbackVersion(Long versionId) {
        NovelOutline version = outlineRepository.selectById(versionId);
        if (version == null || (!CONTENT_VERSION_TYPE.equals(version.getType()) && !DRAFT_VERSION_TYPE.equals(version.getType()))) {
            throw new ResourceNotFoundException("ContentVersion", versionId);
        }
        Novel novel = novelRepository.selectById(version.getNovelId());
        if (novel == null) {
            throw new ResourceNotFoundException("Novel", version.getNovelId());
        }
        Long userId = ThreadLocalUtil.getUserId();
        if (!novel.getUserId().equals(userId)) {
            throw new BusinessException(403, "Access denied");
        }
        ContentVersionItemDTO dto = new ContentVersionItemDTO();
        BeanUtils.copyProperties(version, dto);
        dto.setSourceType(CONTENT_VERSION_TYPE.equals(version.getType()) ? "expanded" : "draft");
        dto.setWordCount(countWords(version.getContent()));
        return dto;
    }

    /**
     * 对齐开发文档的“章节回滚”接口：回滚必须属于指定章节节点。
     */
    public ContentVersionItemDTO rollbackVersionForChapter(Long novelId, Long chapterOutlineId, Long versionId) {
        NovelOutline outline = loadAndCheckChapterOutline(chapterOutlineId);
        if (novelId == null || !novelId.equals(outline.getNovelId())) {
            throw new BusinessException(403, "Access denied");
        }
        NovelOutline version = outlineRepository.selectById(versionId);
        if (version == null || (!CONTENT_VERSION_TYPE.equals(version.getType()) && !DRAFT_VERSION_TYPE.equals(version.getType()))) {
            throw new ResourceNotFoundException("ContentVersion", versionId);
        }
        if (version.getParentId() == null || !version.getParentId().equals(chapterOutlineId)) {
            throw new BusinessException(400, "Version not belong to this chapter");
        }
        return rollbackVersion(versionId);
    }

    private int nextVersion(Long chapterOutlineId, String type) {
        return outlineRepository.selectCount(
                new LambdaQueryWrapper<NovelOutline>()
                        .eq(NovelOutline::getType, type)
                        .eq(NovelOutline::getParentId, chapterOutlineId)
        ).intValue() + 1;
    }

    private String extractLastAnchor(String text) {
        if (text == null) return "";
        String t = text.trim();
        if (t.isEmpty()) return "";

        // 优先截取最后一个句末标点后的内容
        int[] candidates = new int[]{
                Math.max(t.lastIndexOf('。'), t.lastIndexOf('！')),
                Math.max(t.lastIndexOf('？'), t.lastIndexOf('!')),
                Math.max(t.lastIndexOf('?'), t.lastIndexOf('.'))
        };
        int last = Math.max(candidates[0], Math.max(candidates[1], candidates[2]));
        if (last >= 0 && last + 1 < t.length()) {
            String tail = t.substring(last + 1).trim();
            if (!tail.isBlank()) return tail;
        }

        // 兜底：取末尾 120 字以内片段作为续写锚点
        int start = Math.max(0, t.length() - 120);
        return t.substring(start).trim();
    }

    private NovelOutline loadAndCheckChapterOutline(Long chapterOutlineId) {
        NovelOutline outline = outlineRepository.selectById(chapterOutlineId);
        if (outline == null) {
            throw new ResourceNotFoundException("Outline", chapterOutlineId);
        }
        if (!"chapter_outline".equals(outline.getType())) {
            throw new BusinessException(400, "Outline type must be chapter_outline");
        }
        Novel novel = novelRepository.selectById(outline.getNovelId());
        if (novel == null) {
            throw new ResourceNotFoundException("Novel", outline.getNovelId());
        }
        Long userId = ThreadLocalUtil.getUserId();
        if (!novel.getUserId().equals(userId)) {
            throw new BusinessException(403, "Access denied");
        }
        return outline;
    }

    private List<ContentExpandResultDTO.GenerationPlanItem> buildPlan(int ratio, ContentExpandResultDTO.StyleFingerprint fp) {
        List<ContentExpandResultDTO.GenerationPlanItem> plan = new ArrayList<>();
        plan.add(planItem("opening", ratio, fp, "建立场景与初始冲突"));
        plan.add(planItem("development", ratio, fp, "推进行动并加压"));
        plan.add(planItem("climax", ratio, fp, "核心对抗与情绪峰值"));
        plan.add(planItem("ending", ratio, fp, "收束并抛出下章钩子"));
        return plan;
    }

    private ContentExpandResultDTO.GenerationPlanItem planItem(String type,
                                                               int ratio,
                                                               ContentExpandResultDTO.StyleFingerprint fp,
                                                               String strategy) {
        ContentExpandResultDTO.GenerationPlanItem item = new ContentExpandResultDTO.GenerationPlanItem();
        item.setType(type);
        item.setSentenceCount(Math.max(3, ratio * 3));
        String pacing = fp == null ? "balanced" : fp.getPacingType();
        item.setStrategy(strategy + "（节奏：" + pacing + "）");
        return item;
    }

    private ContentExpandResultDTO.Segment seg(String type, String text) {
        ContentExpandResultDTO.Segment s = new ContentExpandResultDTO.Segment();
        s.setType(type);
        s.setText(text);
        return s;
    }

    private List<String> extractPlotPoints(String outlineText) {
        List<String> points = new ArrayList<>();
        for (String line : outlineText.split("\n")) {
            String t = line.trim();
            if (t.isBlank()) continue;
            if (t.startsWith("-")) {
                points.add(t.substring(1).trim());
            } else if (t.matches("^\\d+\\..*")) {
                points.add(t.replaceFirst("^\\d+\\.", "").trim());
            } else if (t.startsWith("情节点：") || t.startsWith("关键对白：") || t.startsWith("伏笔：")) {
                // skip headings
            } else if (t.contains("[opening]") || t.contains("[development]") || t.contains("[turning_point]") || t.contains("[climax]") || t.contains("[ending]")) {
                points.add(t);
            }
        }
        return points;
    }

    private String expandParagraph(List<String> points, int sectionIndex, int ratio, ContentExpandResultDTO.StyleFingerprint fp) {
        String seed = points.get(Math.min(points.size() - 1, sectionIndex));
        StringBuilder sb = new StringBuilder();
        String opener = switch (sectionIndex) {
            case 0 -> "开头段：";
            case 1 -> "发展段：";
            case 2 -> "高潮段：";
            default -> "结尾段：";
        };
        sb.append(opener).append(seed).append("\n");

        int sentences = Math.max(3, ratio * 3);
        for (int i = 0; i < sentences; i++) {
            sb.append(buildSentence(seed, sectionIndex, i, fp));
            sb.append(i == sentences - 1 ? "。" : "，");
        }
        sb.append("\n");
        return sb.toString().trim();
    }

    private String buildSentence(String seed, int sectionIndex, int idx, ContentExpandResultDTO.StyleFingerprint fp) {
        boolean preferDialogue = fp != null && fp.getDialogueRatio() != null && fp.getDialogueRatio() > 0.25 && idx % 3 == 1;
        boolean preferDescription = fp == null || fp.getDescriptionDensity() == null || fp.getDescriptionDensity() >= 0.3;

        if (preferDialogue) {
            return "“" + brief(seed) + "？”他低声问";
        }
        if (preferDescription) {
            return switch (sectionIndex) {
                case 0 -> "空气里有一丝不安在发酵，" + brief(seed);
                case 1 -> "行动推进得并不顺利，细节逐一咬合，" + brief(seed);
                case 2 -> "冲突被推到最尖锐的边缘，" + brief(seed);
                default -> "答案落下时并不喧哗，" + brief(seed);
            };
        }
        return brief(seed);
    }

    private String brief(String s) {
        String t = s.replace("\n", " ").trim();
        return t.length() > 26 ? t.substring(0, 26) + "..." : t;
    }

    private ContentExpandResultDTO.StyleFingerprint analyzeStyle(String sample) {
        ContentExpandResultDTO.StyleFingerprint fp = new ContentExpandResultDTO.StyleFingerprint();
        String text = sample.trim();

        double avgSentenceLength = averageSentenceLength(text);
        fp.setAvgSentenceLength(avgSentenceLength);

        double dialogueRatio = calculateDialogueRatio(text);
        fp.setDialogueRatio(dialogueRatio);

        double descriptionDensity = calculateDescriptionDensity(text);
        fp.setDescriptionDensity(descriptionDensity);

        String pacing = avgSentenceLength >= 20 ? "slow" : avgSentenceLength <= 12 ? "fast" : "balanced";
        fp.setPacingType(pacing);
        return fp;
    }

    private double averageSentenceLength(String text) {
        String[] sentences = text.split("[。！？!?]+");
        int count = 0;
        int sum = 0;
        for (String s : sentences) {
            String t = s.trim();
            if (t.isBlank()) continue;
            count++;
            sum += t.length();
        }
        return count == 0 ? 0.0d : sum / (double) count;
    }

    private double calculateDialogueRatio(String text) {
        int quotePairs = 0;
        Matcher m = Pattern.compile("“[^”]{1,200}”").matcher(text);
        while (m.find()) quotePairs++;
        int total = Math.max(1, text.length());
        int dialogueChars = Math.min(total, quotePairs * 30);
        return dialogueChars / (double) total;
    }

    private double calculateDescriptionDensity(String text) {
        // very rough: adjectives-like tokens or sensory words
        int hits = 0;
        for (String token : new String[]{"冰冷", "潮湿", "昏暗", "刺耳", "压迫", "微光", "气息", "影子"}) {
            if (text.contains(token)) hits++;
        }
        return Math.min(1.0d, hits / 8.0d);
    }

    private String postProcess(String content) {
        return content.replace("\r\n", "\n").trim();
    }

    private String optimizeConnections(String content) {
        String[] blocks = content.split("\\n\\n");
        if (blocks.length < 2) {
            return content;
        }
        StringBuilder sb = new StringBuilder();
        for (int i = 0; i < blocks.length; i++) {
            if (i > 0) {
                sb.append("\n\n").append("过渡：情绪余波尚未散去，下一段冲突已经贴近。").append("\n");
            }
            sb.append(blocks[i]);
        }
        return sb.toString();
    }

    private int countWords(String text) {
        if (text == null || text.isBlank()) return 0;
        return text.replaceAll("\\s+", "").length();
    }
}

