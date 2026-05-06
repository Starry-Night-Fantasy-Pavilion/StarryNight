package com.starrynight.starrynight.system.novel.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.baomidou.mybatisplus.extension.plugins.pagination.Page;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.framework.common.exception.ResourceNotFoundException;
import com.starrynight.starrynight.framework.common.util.ThreadLocalUtil;
import com.starrynight.starrynight.services.ai.AiGenerationService;
import com.starrynight.starrynight.system.novel.dto.NovelChapterDTO;
import com.starrynight.starrynight.system.novel.dto.NovelDTO;
import com.starrynight.starrynight.system.novel.dto.NovelOutlineDTO;
import com.starrynight.starrynight.system.novel.dto.NovelVolumeDTO;
import com.starrynight.starrynight.system.novel.dto.GenerateOutlineRequestDTO;
import com.starrynight.starrynight.system.novel.entity.Novel;
import com.starrynight.starrynight.system.novel.entity.NovelChapter;
import com.starrynight.starrynight.system.novel.entity.NovelOutline;
import com.starrynight.starrynight.system.novel.entity.NovelVolume;
import com.starrynight.starrynight.system.novel.repository.NovelChapterRepository;
import com.starrynight.starrynight.system.novel.repository.NovelOutlineRepository;
import com.starrynight.starrynight.system.novel.repository.NovelRepository;
import com.starrynight.starrynight.system.novel.repository.NovelVolumeRepository;
import com.starrynight.starrynight.system.auth.realname.RealnameVerificationService;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.beans.BeanUtils;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.time.LocalDateTime;
import java.util.ArrayList;
import java.util.List;
import java.util.stream.Collectors;

import org.apache.poi.xwpf.usermodel.XWPFDocument;
import org.apache.poi.xwpf.usermodel.XWPFParagraph;
import org.apache.poi.xwpf.usermodel.XWPFRun;
import org.apache.poi.xwpf.usermodel.ParagraphAlignment;

@Service
public class NovelService {

    private static final Logger log = LoggerFactory.getLogger(NovelService.class);

    @Autowired
    private NovelRepository novelRepository;

    @Autowired
    private NovelVolumeRepository novelVolumeRepository;

    @Autowired
    private NovelChapterRepository novelChapterRepository;

    @Autowired
    private NovelOutlineRepository novelOutlineRepository;

    @Autowired
    private AiGenerationService aiGenerationService;

    @Autowired
    private RealnameVerificationService realnameVerificationService;

    public Page<NovelDTO> listUserNovels(int page, int size) {
        Long userId = ThreadLocalUtil.getUserId();
        Page<Novel> pageData = novelRepository.selectPage(
                new Page<>(page, size),
                new LambdaQueryWrapper<Novel>()
                        .eq(Novel::getUserId, userId)
                        .eq(Novel::getIsDeleted, 0)
                        .orderByDesc(Novel::getCreateTime)
        );
        Page<NovelDTO> result = new Page<>(pageData.getCurrent(), pageData.getSize(), pageData.getTotal());
        result.setRecords(pageData.getRecords().stream().map(this::toDTO).collect(Collectors.toList()));
        return result;
    }

    public NovelDTO getById(Long id) {
        Novel novel = novelRepository.selectById(id);
        if (novel == null) {
            throw new ResourceNotFoundException("Novel", id);
        }
        Long userId = ThreadLocalUtil.getUserId();
        if (!novel.getUserId().equals(userId)) {
            throw new BusinessException(403, "Access denied");
        }
        return toDTO(novel);
    }

    @Transactional
    public NovelDTO create(NovelDTO dto) {
        Long userId = ThreadLocalUtil.getUserId();

        Novel novel = new Novel();
        BeanUtils.copyProperties(dto, novel);
        novel.setUserId(userId);
        novel.setWordCount(0);
        novel.setChapterCount(0);
        novel.setStatus(0);
        novel.setAuditStatus(0);
        novel.setIsPublished(0);
        novel.setIsDeleted(0);

        novelRepository.insert(novel);
        log.info("create_novel id={} userId={}", novel.getId(), userId);
        return toDTO(novel);
    }

    @Transactional
    public NovelDTO update(Long id, NovelDTO dto) {
        Novel novel = novelRepository.selectById(id);
        if (novel == null) {
            throw new ResourceNotFoundException("Novel", id);
        }

        Long userId = ThreadLocalUtil.getUserId();
        if (!novel.getUserId().equals(userId)) {
            throw new BusinessException(403, "Access denied");
        }

        novel.setTitle(dto.getTitle());
        novel.setSubtitle(dto.getSubtitle());
        novel.setCover(dto.getCover());
        novel.setCategoryId(dto.getCategoryId());
        novel.setGenre(dto.getGenre());
        novel.setStyle(dto.getStyle());
        novel.setSynopsis(dto.getSynopsis());

        novelRepository.updateById(novel);
        log.info("update_novel id={}", id);
        return toDTO(novel);
    }

    @Transactional
    public void delete(Long id) {
        Novel novel = novelRepository.selectById(id);
        if (novel == null) {
            throw new ResourceNotFoundException("Novel", id);
        }

        Long userId = ThreadLocalUtil.getUserId();
        if (!novel.getUserId().equals(userId)) {
            throw new BusinessException(403, "Access denied");
        }

        novel.setIsDeleted(1);
        novelRepository.updateById(novel);
        log.info("delete_novel id={}", id);
    }

    @Transactional
    public void publish(Long id) {
        Novel novel = novelRepository.selectById(id);
        if (novel == null) {
            throw new ResourceNotFoundException("Novel", id);
        }

        Long userId = ThreadLocalUtil.getUserId();
        if (!novel.getUserId().equals(userId)) {
            throw new BusinessException(403, "Access denied");
        }

        novel.setIsPublished(1);
        novel.setPublishTime(LocalDateTime.now());
        novelRepository.updateById(novel);
        log.info("publish_novel id={}", id);
    }

    public List<NovelVolumeDTO> listVolumes(Long novelId) {
        Novel novel = novelRepository.selectById(novelId);
        if (novel == null) {
            throw new ResourceNotFoundException("Novel", novelId);
        }

        return novelVolumeRepository.selectList(
                new LambdaQueryWrapper<NovelVolume>()
                        .eq(NovelVolume::getNovelId, novelId)
                        .orderByAsc(NovelVolume::getVolumeOrder)
        ).stream().map(this::toVolumeDTO).collect(Collectors.toList());
    }

    @Transactional
    public NovelVolumeDTO createVolume(NovelVolumeDTO dto) {
        Novel novel = novelRepository.selectById(dto.getNovelId());
        if (novel == null) {
            throw new ResourceNotFoundException("Novel", dto.getNovelId());
        }

        NovelVolume volume = new NovelVolume();
        BeanUtils.copyProperties(dto, volume);
        volume.setChapterCount(0);
        volume.setWordCount(0);
        volume.setStatus(0);

        novelVolumeRepository.insert(volume);
        log.info("create_volume id={} novelId={}", volume.getId(), dto.getNovelId());
        return toVolumeDTO(volume);
    }

    @Transactional
    public NovelVolumeDTO updateVolume(Long id, NovelVolumeDTO dto) {
        NovelVolume volume = novelVolumeRepository.selectById(id);
        if (volume == null) {
            throw new ResourceNotFoundException("Volume", id);
        }

        volume.setTitle(dto.getTitle());
        volume.setDescription(dto.getDescription());
        volume.setVolumeOrder(dto.getVolumeOrder());

        novelVolumeRepository.updateById(volume);
        log.info("update_volume id={}", id);
        return toVolumeDTO(volume);
    }

    @Transactional
    public void deleteVolume(Long id) {
        NovelVolume volume = novelVolumeRepository.selectById(id);
        if (volume == null) {
            throw new ResourceNotFoundException("Volume", id);
        }

        novelVolumeRepository.deleteById(id);
        log.info("delete_volume id={}", id);
    }

    public List<NovelChapterDTO> listChapters(Long novelId, Long volumeId) {
        LambdaQueryWrapper<NovelChapter> wrapper = new LambdaQueryWrapper<NovelChapter>()
                .eq(NovelChapter::getNovelId, novelId);
        if (volumeId != null) {
            wrapper.eq(NovelChapter::getVolumeId, volumeId);
        }
        wrapper.orderByAsc(NovelChapter::getChapterOrder);

        return novelChapterRepository.selectList(wrapper)
                .stream().map(this::toChapterDTO).collect(Collectors.toList());
    }

    public List<NovelOutlineDTO> listOutlines(Long novelId, String type, Long volumeId, Long chapterId) {
        Novel novel = novelRepository.selectById(novelId);
        if (novel == null) {
            throw new ResourceNotFoundException("Novel", novelId);
        }
        Long userId = ThreadLocalUtil.getUserId();
        if (!novel.getUserId().equals(userId)) {
            throw new BusinessException(403, "Access denied");
        }

        LambdaQueryWrapper<NovelOutline> wrapper = new LambdaQueryWrapper<NovelOutline>()
                .eq(NovelOutline::getNovelId, novelId)
                .eq(NovelOutline::getType, type);
        if (volumeId != null) {
            wrapper.eq(NovelOutline::getVolumeId, volumeId);
        }
        if (chapterId != null) {
            wrapper.eq(NovelOutline::getChapterId, chapterId);
        }
        wrapper.orderByAsc(NovelOutline::getSortOrder).orderByAsc(NovelOutline::getId);

        return novelOutlineRepository.selectList(wrapper).stream()
                .map(this::toOutlineDTO)
                .collect(Collectors.toList());
    }

    /**
     * AI 生成大纲建议（不落库，返回建议内容给前端确认）。
     */
    public NovelOutlineDTO generateOutline(GenerateOutlineRequestDTO req) {
        Novel novel = novelRepository.selectById(req.getNovelId());
        if (novel == null || Integer.valueOf(1).equals(novel.getIsDeleted())) {
            throw new ResourceNotFoundException("Novel", req.getNovelId());
        }
        Long userId = ThreadLocalUtil.getUserId();
        if (!novel.getUserId().equals(userId)) {
            throw new BusinessException(403, "Access denied");
        }

        String coreIdea = req.getCoreIdea();
        if (coreIdea == null || coreIdea.isBlank()) {
            coreIdea = (novel.getSynopsis() == null || novel.getSynopsis().isBlank()) ? "主角在危机中成长并完成自我突破" : novel.getSynopsis();
        }
        coreIdea = coreIdea.replace("\n", " ").trim();
        if (coreIdea.length() > 120) {
            coreIdea = coreIdea.substring(0, 120) + "...";
        }

        String genre = (req.getGenre() == null || req.getGenre().isBlank()) ? nullToFallback(novel.getGenre(), "通用") : req.getGenre().trim();
        String style = (req.getStyle() == null || req.getStyle().isBlank()) ? nullToFallback(novel.getStyle(), "均衡叙事") : req.getStyle().trim();

        String outlinePrompt = buildOutlinePrompt(coreIdea, genre, style, req.getTemplate());
        String aiContent = aiGenerationService.generate(outlinePrompt);

        NovelOutlineDTO out = new NovelOutlineDTO();
        out.setNovelId(req.getNovelId());
        out.setType("outline");
        out.setTitle("AI大纲建议：" + nullToFallback(novel.getTitle(), "未命名作品"));
        out.setSortOrder(0);
        out.setVersion(1);
        out.setContent(aiContent != null ? aiContent : buildOutlineContent(coreIdea, genre, style));
        return out;
    }

    private String buildOutlinePrompt(String coreIdea, String genre, String style, String template) {
        StringBuilder prompt = new StringBuilder();
        prompt.append("你是一位资深网文编辑，擅长设计引人入胜的故事结构。\n\n");
        prompt.append("请根据以下信息生成一个完整的【").append(genre).append("】类型小说大纲：\n\n");
        prompt.append("核心创意：").append(coreIdea).append("\n");
        prompt.append("写作风格：").append(style).append("\n");

        if (template != null && !template.isBlank()) {
            prompt.append("大纲模板：").append(template).append("\n");
        }

        prompt.append("\n要求：\n");
        prompt.append("1. 大纲应包含清晰的三幕结构（建置/对抗/解决）\n");
        prompt.append("2. 每幕需包含核心冲突、关键事件、人物弧线\n");
        prompt.append("3. 主线清晰，支线为辅\n");
        prompt.append("4. 结局要有高潮和情感释放\n");
        prompt.append("5. 符合网文读者的阅读习惯\n\n");
        prompt.append("请生成详细的大纲内容，包括：\n");
        prompt.append("- 小说标题\n");
        prompt.append("- 三幕结构详情（每幕的名称、概括、关键事件）\n");
        prompt.append("- 主要角色列表\n");
        prompt.append("- 世界观简述\n");

        return prompt.toString();
    }

    /**
     * AI 分卷建议（不落库，返回建议列表给前端确认）。
     */
    public List<NovelVolumeDTO> generateVolumes(Long novelId, Integer volumeCount) {
        Novel novel = novelRepository.selectById(novelId);
        if (novel == null || Integer.valueOf(1).equals(novel.getIsDeleted())) {
            throw new ResourceNotFoundException("Novel", novelId);
        }
        Long userId = ThreadLocalUtil.getUserId();
        if (!novel.getUserId().equals(userId)) {
            throw new BusinessException(403, "Access denied");
        }

        int count = volumeCount == null ? 3 : Math.max(1, Math.min(12, volumeCount));
        String outlineSummary = "主线冲突推进";
        NovelOutline rootOutline = novelOutlineRepository.selectOne(
                new LambdaQueryWrapper<NovelOutline>()
                        .eq(NovelOutline::getNovelId, novelId)
                        .eq(NovelOutline::getType, "outline")
                        .orderByDesc(NovelOutline::getVersion)
                        .orderByDesc(NovelOutline::getId)
                        .last("limit 1")
        );
        if (rootOutline != null && rootOutline.getContent() != null && !rootOutline.getContent().isBlank()) {
            outlineSummary = rootOutline.getContent().replace("\n", " ").trim();
            if (outlineSummary.length() > 80) {
                outlineSummary = outlineSummary.substring(0, 80) + "...";
            }
        } else if (novel.getSynopsis() != null && !novel.getSynopsis().isBlank()) {
            outlineSummary = novel.getSynopsis().trim();
            if (outlineSummary.length() > 80) {
                outlineSummary = outlineSummary.substring(0, 80) + "...";
            }
        }

        List<NovelVolumeDTO> out = new ArrayList<>();
        String volumePrompt = buildVolumePrompt(novel.getTitle(), outlineSummary, novel.getGenre(), count);
        String aiContent = aiGenerationService.generate(volumePrompt);

        if (aiContent != null && !aiContent.isBlank()) {
            out.addAll(parseVolumeResponse(aiContent, novelId, count));
        }
        if (out.isEmpty()) {
            for (int i = 1; i <= count; i++) {
                NovelVolumeDTO dto = new NovelVolumeDTO();
                dto.setNovelId(novelId);
                dto.setVolumeOrder(i);
                dto.setTitle("第" + i + "卷：" + inferVolumeTitle(i, count));
                dto.setDescription("围绕" + outlineSummary + "推进第" + i + "阶段冲突与人物弧线。");
                dto.setChapterCount(0);
                dto.setWordCount(0);
                dto.setStatus(0);
                out.add(dto);
            }
        }
        return out;
    }

    private String buildVolumePrompt(String novelTitle, String outlineSummary, String genre, int count) {
        StringBuilder prompt = new StringBuilder();
        prompt.append("你是一位资深网文编辑，擅长设计分卷结构。\n\n");
        prompt.append("作品名称：").append(novelTitle != null ? novelTitle : "未命名作品").append("\n");
        prompt.append("作品类型：").append(genre != null ? genre : "通用").append("\n");
        prompt.append("大纲摘要：").append(outlineSummary).append("\n");
        prompt.append("分卷数量：").append(count).append("卷\n\n");
        prompt.append("请为每一卷生成：\n");
        prompt.append("1. 卷标题（如：第1卷：入门与觉醒）\n");
        prompt.append("2. 卷摘要（20-50字描述本卷核心冲突和发展）\n\n");
        prompt.append("请以以下格式输出：\n");
        for (int i = 1; i <= count; i++) {
            prompt.append("【第").append(i).append("卷】\n");
            prompt.append("标题：第").append(i).append("卷：xxx\n");
            prompt.append("摘要：xxx\n\n");
        }
        return prompt.toString();
    }

    private List<NovelVolumeDTO> parseVolumeResponse(String aiContent, Long novelId, int count) {
        List<NovelVolumeDTO> volumes = new ArrayList<>();
        String[] lines = aiContent.split("\n");
        int currentVolume = 0;
        String currentTitle = null;
        StringBuilder currentDesc = new StringBuilder();

        for (String line : lines) {
            line = line.trim();
            if (line.matches("【第\\d+卷】")) {
                if (currentVolume > 0 && currentTitle != null) {
                    volumes.add(createVolumeDto(novelId, currentVolume, currentTitle, currentDesc.toString()));
                }
                currentVolume++;
                currentTitle = null;
                currentDesc = new StringBuilder();
            } else if (line.startsWith("标题：")) {
                currentTitle = line.substring(3).trim();
            } else if (line.startsWith("摘要：")) {
                currentDesc.append(line.substring(3).trim());
            } else if (currentDesc.length() > 0 && !line.isEmpty()) {
                currentDesc.append(line);
            }
        }

        if (currentVolume > 0 && currentTitle != null) {
            volumes.add(createVolumeDto(novelId, currentVolume, currentTitle, currentDesc.toString()));
        }

        for (int i = volumes.size() + 1; i <= count; i++) {
            volumes.add(createVolumeDto(novelId, i, "第" + i + "卷：" + inferVolumeTitle(i, count),
                    "围绕主线冲突推进第" + i + "阶段发展"));
        }

        return volumes;
    }

    private NovelVolumeDTO createVolumeDto(Long novelId, int volumeOrder, String title, String description) {
        NovelVolumeDTO dto = new NovelVolumeDTO();
        dto.setNovelId(novelId);
        dto.setVolumeOrder(volumeOrder);
        dto.setTitle(title);
        dto.setDescription(description != null ? description : "");
        dto.setChapterCount(0);
        dto.setWordCount(0);
        dto.setStatus(0);
        return dto;
    }

    @Transactional
    public NovelOutlineDTO upsertOutline(NovelOutlineDTO dto) {
        Novel novel = novelRepository.selectById(dto.getNovelId());
        if (novel == null) {
            throw new ResourceNotFoundException("Novel", dto.getNovelId());
        }
        Long userId = ThreadLocalUtil.getUserId();
        if (!novel.getUserId().equals(userId)) {
            throw new BusinessException(403, "Access denied");
        }

        if (dto.getId() != null) {
            NovelOutline entity = novelOutlineRepository.selectById(dto.getId());
            if (entity == null) {
                throw new ResourceNotFoundException("Outline", dto.getId());
            }
            entity.setTitle(dto.getTitle());
            entity.setContent(dto.getContent());
            entity.setSortOrder(dto.getSortOrder() == null ? 0 : dto.getSortOrder());
            entity.setParentId(dto.getParentId());
            entity.setVersion(entity.getVersion() == null ? 1 : entity.getVersion() + 1);
            novelOutlineRepository.updateById(entity);
            return toOutlineDTO(entity);
        }

        // 对于核心大纲，按 (novelId,type) 保证只有一条根节点：若存在则更新，否则创建
        if ("outline".equals(dto.getType())) {
            NovelOutline exists = novelOutlineRepository.selectOne(
                    new LambdaQueryWrapper<NovelOutline>()
                            .eq(NovelOutline::getNovelId, dto.getNovelId())
                            .eq(NovelOutline::getType, dto.getType())
                            .isNull(NovelOutline::getParentId)
            );
            if (exists != null) {
                exists.setTitle(dto.getTitle());
                exists.setContent(dto.getContent());
                exists.setSortOrder(dto.getSortOrder() == null ? 0 : dto.getSortOrder());
                exists.setVersion(exists.getVersion() == null ? 1 : exists.getVersion() + 1);
                novelOutlineRepository.updateById(exists);
                return toOutlineDTO(exists);
            }
        }

        NovelOutline entity = new NovelOutline();
        BeanUtils.copyProperties(dto, entity);
        entity.setSortOrder(dto.getSortOrder() == null ? 0 : dto.getSortOrder());
        entity.setVersion(1);
        novelOutlineRepository.insert(entity);
        return toOutlineDTO(entity);
    }

    @Transactional
    public void deleteOutline(Long id) {
        NovelOutline entity = novelOutlineRepository.selectById(id);
        if (entity == null) {
            throw new ResourceNotFoundException("Outline", id);
        }
        Novel novel = novelRepository.selectById(entity.getNovelId());
        if (novel == null) {
            throw new ResourceNotFoundException("Novel", entity.getNovelId());
        }
        Long userId = ThreadLocalUtil.getUserId();
        if (!novel.getUserId().equals(userId)) {
            throw new BusinessException(403, "Access denied");
        }
        novelOutlineRepository.deleteById(id);
    }

    public NovelChapterDTO getChapter(Long id) {
        NovelChapter chapter = novelChapterRepository.selectById(id);
        if (chapter == null) {
            throw new ResourceNotFoundException("Chapter", id);
        }
        return toChapterDTO(chapter);
    }

    @Transactional
    public NovelChapterDTO createChapter(NovelChapterDTO dto) {
        Novel novel = novelRepository.selectById(dto.getNovelId());
        if (novel == null) {
            throw new ResourceNotFoundException("Novel", dto.getNovelId());
        }

        NovelChapter chapter = new NovelChapter();
        BeanUtils.copyProperties(dto, chapter);
        chapter.setWordCount(dto.getContent() != null ? dto.getContent().length() : 0);
        chapter.setStatus(0);
        chapter.setVersion(1);

        novelChapterRepository.insert(chapter);

        novel.setChapterCount(novel.getChapterCount() + 1);
        novel.setWordCount(novel.getWordCount() + chapter.getWordCount());
        novelRepository.updateById(novel);

        log.info("create_chapter id={} novelId={}", chapter.getId(), dto.getNovelId());
        return toChapterDTO(chapter);
    }

    @Transactional
    public NovelChapterDTO updateChapter(Long id, NovelChapterDTO dto) {
        NovelChapter chapter = novelChapterRepository.selectById(id);
        if (chapter == null) {
            throw new ResourceNotFoundException("Chapter", id);
        }

        int oldWordCount = chapter.getWordCount();

        chapter.setTitle(dto.getTitle());
        chapter.setContent(dto.getContent());
        chapter.setOutline(dto.getOutline());
        chapter.setVolumeId(dto.getVolumeId());
        chapter.setWordCount(dto.getContent() != null ? dto.getContent().length() : 0);

        novelChapterRepository.updateById(chapter);

        Novel novel = novelRepository.selectById(chapter.getNovelId());
        if (novel != null) {
            novel.setWordCount(novel.getWordCount() - oldWordCount + chapter.getWordCount());
            novelRepository.updateById(novel);
        }

        log.info("update_chapter id={}", id);
        return toChapterDTO(chapter);
    }

    @Transactional
    public void deleteChapter(Long id) {
        NovelChapter chapter = novelChapterRepository.selectById(id);
        if (chapter == null) {
            throw new ResourceNotFoundException("Chapter", id);
        }

        Novel novel = novelRepository.selectById(chapter.getNovelId());
        if (novel != null) {
            novel.setChapterCount(novel.getChapterCount() - 1);
            novel.setWordCount(novel.getWordCount() - chapter.getWordCount());
            novelRepository.updateById(novel);
        }

        novelChapterRepository.deleteById(id);
        log.info("delete_chapter id={}", id);
    }

    public String exportNovel(Long id, String format) {
        Novel novel = novelRepository.selectById(id);
        if (novel == null) {
            throw new ResourceNotFoundException("Novel", id);
        }
        Long userId = ThreadLocalUtil.getUserId();
        if (!novel.getUserId().equals(userId)) {
            throw new BusinessException(403, "Access denied");
        }
        realnameVerificationService.requireVerifiedForContentExport(userId);

        StringBuilder sb = new StringBuilder();

        if ("html".equalsIgnoreCase(format)) {
            sb.append("<html><head><meta charset=\"UTF-8\"><title>").append(escapeHtml(novel.getTitle())).append("</title></head><body>");
            sb.append("<h1>").append(escapeHtml(novel.getTitle())).append("</h1>");
            if (novel.getSubtitle() != null && !novel.getSubtitle().isBlank()) {
                sb.append("<h2>").append(escapeHtml(novel.getSubtitle())).append("</h2>");
            }
            if (novel.getSynopsis() != null && !novel.getSynopsis().isBlank()) {
                sb.append("<div class=\"synopsis\"><h3>简介</h3><p>").append(escapeHtml(novel.getSynopsis())).append("</p></div>");
            }
        } else {
            sb.append(novel.getTitle());
            if (novel.getSubtitle() != null && !novel.getSubtitle().isBlank()) {
                sb.append("\n").append(novel.getSubtitle());
            }
            sb.append("\n\n");
            if (novel.getSynopsis() != null && !novel.getSynopsis().isBlank()) {
                sb.append("【简介】\n").append(novel.getSynopsis()).append("\n\n");
            }
        }

        List<NovelVolume> volumes = novelVolumeRepository.selectList(
                new LambdaQueryWrapper<NovelVolume>()
                        .eq(NovelVolume::getNovelId, id)
                        .orderByAsc(NovelVolume::getVolumeOrder)
        );

        for (NovelVolume volume : volumes) {
            if ("html".equalsIgnoreCase(format)) {
                sb.append("<div class=\"volume\"><h2>").append(escapeHtml(volume.getTitle())).append("</h2>");
                if (volume.getDescription() != null && !volume.getDescription().isBlank()) {
                    sb.append("<p>").append(escapeHtml(volume.getDescription())).append("</p>");
                }
            } else {
                sb.append("\n===== ").append(volume.getTitle()).append(" =====\n");
                if (volume.getDescription() != null && !volume.getDescription().isBlank()) {
                    sb.append(volume.getDescription()).append("\n");
                }
            }

            List<NovelChapter> chapters = novelChapterRepository.selectList(
                    new LambdaQueryWrapper<NovelChapter>()
                            .eq(NovelChapter::getNovelId, id)
                            .eq(NovelChapter::getVolumeId, volume.getId())
                            .orderByAsc(NovelChapter::getChapterOrder)
            );

            for (NovelChapter chapter : chapters) {
                if ("html".equalsIgnoreCase(format)) {
                    sb.append("<div class=\"chapter\"><h3>").append(escapeHtml(chapter.getTitle())).append("</h3>");
                    if (chapter.getContent() != null && !chapter.getContent().isBlank()) {
                        sb.append("<p>").append(escapeHtml(chapter.getContent()).replace("\n", "<br>")).append("</p>");
                    }
                    sb.append("</div>");
                } else {
                    sb.append("\n--- ").append(chapter.getTitle()).append(" ---\n\n");
                    if (chapter.getContent() != null && !chapter.getContent().isBlank()) {
                        sb.append(chapter.getContent());
                    }
                    sb.append("\n");
                }
            }

            if ("html".equalsIgnoreCase(format)) {
                sb.append("</div>");
            }
        }

        if ("html".equalsIgnoreCase(format)) {
            sb.append("</body></html>");
        }

        return sb.toString();
    }

    public byte[] exportNovelToWord(Long id) {
        Novel novel = novelRepository.selectById(id);
        if (novel == null) {
            throw new ResourceNotFoundException("Novel", id);
        }
        Long userId = ThreadLocalUtil.getUserId();
        if (!novel.getUserId().equals(userId)) {
            throw new BusinessException(403, "Access denied");
        }
        realnameVerificationService.requireVerifiedForContentExport(userId);

        try (XWPFDocument document = new XWPFDocument()) {
            XWPFParagraph titlePara = document.createParagraph();
            titlePara.setAlignment(ParagraphAlignment.CENTER);
            XWPFRun titleRun = titlePara.createRun();
            titleRun.setText(novel.getTitle());
            titleRun.setBold(true);
            titleRun.setFontSize(24);

            if (novel.getSubtitle() != null && !novel.getSubtitle().isBlank()) {
                XWPFParagraph subtitlePara = document.createParagraph();
                subtitlePara.setAlignment(ParagraphAlignment.CENTER);
                XWPFRun subtitleRun = subtitlePara.createRun();
                subtitleRun.setText(novel.getSubtitle());
                subtitleRun.setFontSize(16);
            }

            if (novel.getSynopsis() != null && !novel.getSynopsis().isBlank()) {
                document.createParagraph();
                XWPFParagraph synopsisTitle = document.createParagraph();
                XWPFRun synopsisTitleRun = synopsisTitle.createRun();
                synopsisTitleRun.setText("简介");
                synopsisTitleRun.setBold(true);
                synopsisTitleRun.setFontSize(14);

                XWPFParagraph synopsisPara = document.createParagraph();
                XWPFRun synopsisRun = synopsisPara.createRun();
                synopsisRun.setText(novel.getSynopsis());
                synopsisRun.setFontSize(12);
            }

            List<NovelVolume> volumes = novelVolumeRepository.selectList(
                    new LambdaQueryWrapper<NovelVolume>()
                            .eq(NovelVolume::getNovelId, id)
                            .orderByAsc(NovelVolume::getVolumeOrder)
            );

            for (NovelVolume volume : volumes) {
                document.createParagraph();
                XWPFParagraph volumeTitle = document.createParagraph();
                XWPFRun volumeTitleRun = volumeTitle.createRun();
                volumeTitleRun.setText(volume.getTitle());
                volumeTitleRun.setBold(true);
                volumeTitleRun.setFontSize(18);

                if (volume.getDescription() != null && !volume.getDescription().isBlank()) {
                    XWPFParagraph volumeDesc = document.createParagraph();
                    XWPFRun volumeDescRun = volumeDesc.createRun();
                    volumeDescRun.setText(volume.getDescription());
                    volumeDescRun.setFontSize(11);
                    volumeDescRun.setItalic(true);
                }

                List<NovelChapter> chapters = novelChapterRepository.selectList(
                        new LambdaQueryWrapper<NovelChapter>()
                                .eq(NovelChapter::getNovelId, id)
                                .eq(NovelChapter::getVolumeId, volume.getId())
                                .orderByAsc(NovelChapter::getChapterOrder)
                );

                for (NovelChapter chapter : chapters) {
                    document.createParagraph();
                    XWPFParagraph chapterTitle = document.createParagraph();
                    XWPFRun chapterTitleRun = chapterTitle.createRun();
                    chapterTitleRun.setText(chapter.getTitle());
                    chapterTitleRun.setBold(true);
                    chapterTitleRun.setFontSize(14);

                    if (chapter.getContent() != null && !chapter.getContent().isBlank()) {
                        XWPFParagraph chapterContent = document.createParagraph();
                        chapterContent.setAlignment(ParagraphAlignment.BOTH);
                        XWPFRun contentRun = chapterContent.createRun();
                        contentRun.setText(chapter.getContent());
                        contentRun.setFontSize(12);
                    }
                }
            }

            document.createParagraph();
            XWPFParagraph endPara = document.createParagraph();
            endPara.setAlignment(ParagraphAlignment.CENTER);
            XWPFRun endRun = endPara.createRun();
            endRun.setText("—— 完 ——");
            endRun.setItalic(true);

            java.io.ByteArrayOutputStream out = new java.io.ByteArrayOutputStream();
            document.write(out);
            return out.toByteArray();
        } catch (Exception e) {
            log.error("Failed to export novel to Word: id={}", id, e);
            throw new BusinessException(500, "导出Word文档失败: " + e.getMessage());
        }
    }

    private String escapeHtml(String text) {
        if (text == null) return "";
        return text.replace("&", "&amp;")
                .replace("<", "&lt;")
                .replace(">", "&gt;")
                .replace("\"", "&quot;")
                .replace("'", "&#x27;");
    }

    private NovelDTO toDTO(Novel novel) {
        NovelDTO dto = new NovelDTO();
        BeanUtils.copyProperties(novel, dto);
        return dto;
    }

    private NovelVolumeDTO toVolumeDTO(NovelVolume volume) {
        NovelVolumeDTO dto = new NovelVolumeDTO();
        BeanUtils.copyProperties(volume, dto);
        return dto;
    }

    private NovelChapterDTO toChapterDTO(NovelChapter chapter) {
        NovelChapterDTO dto = new NovelChapterDTO();
        BeanUtils.copyProperties(chapter, dto);
        return dto;
    }

    private NovelOutlineDTO toOutlineDTO(NovelOutline outline) {
        NovelOutlineDTO dto = new NovelOutlineDTO();
        BeanUtils.copyProperties(outline, dto);
        return dto;
    }

    private String inferVolumeTitle(int index, int total) {
        if (total <= 1) {
            return "序章与开局";
        }
        if (index == 1) {
            return "开局与立势";
        }
        if (index == total) {
            return "终局与收束";
        }
        if (index == (total + 1) / 2) {
            return "转折与升级";
        }
        return "推进篇";
    }

    private String buildOutlineContent(String coreIdea, String genre, String style) {
        return String.join("\n",
                "核心创意：" + coreIdea,
                "题材：" + genre + "｜风格：" + style,
                "",
                "第一幕（建置）：",
                "- 主角被迫进入冲突中心，明确短期目标与代价",
                "- 建立主要角色关系与初始矛盾",
                "",
                "第二幕（对抗）：",
                "- 冲突升级，主角连续受挫并获得关键线索",
                "- 支线推动主线，揭示更大敌人与规则限制",
                "",
                "第三幕（解决）：",
                "- 主角完成能力与认知跃迁，正面对抗核心矛盾",
                "- 结局解决当前主线并留下下一阶段钩子",
                "",
                "关键角色弧线：",
                "- 主角：从被动应对到主动布局",
                "- 对手：从压制主角到暴露致命弱点",
                "",
                "世界观钩子：",
                "- 当前规则背后存在更高层设定，后续可展开"
        );
    }

    private String nullToFallback(String value, String fallback) {
        if (value == null || value.isBlank()) {
            return fallback;
        }
        return value;
    }
}

