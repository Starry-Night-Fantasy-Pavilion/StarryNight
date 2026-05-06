package com.starrynight.starrynight.system.stylesample.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.fasterxml.jackson.core.JsonProcessingException;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.framework.common.exception.ResourceNotFoundException;
import com.starrynight.starrynight.framework.common.util.ThreadLocalUtil;
import com.starrynight.starrynight.system.stylesample.dto.StyleExpandRequestDTO;
import com.starrynight.starrynight.system.stylesample.dto.StyleExpandResultDTO;
import com.starrynight.starrynight.system.stylesample.dto.StyleSampleDTO;
import com.starrynight.starrynight.system.stylesample.entity.StyleSample;
import com.starrynight.starrynight.system.stylesample.repository.StyleSampleRepository;
import org.springframework.beans.BeanUtils;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.util.*;
import java.util.stream.Collectors;

@Service
public class StyleSampleService {

    @Autowired
    private StyleSampleRepository styleSampleRepository;

    @Autowired
    private ObjectMapper objectMapper;

    public List<StyleSampleDTO> list() {
        Long userId = ThreadLocalUtil.getUserId();
        LambdaQueryWrapper<StyleSample> wrapper = new LambdaQueryWrapper<>();
        wrapper.eq(StyleSample::getUserId, userId);
        wrapper.orderByDesc(StyleSample::getCreateTime);
        return styleSampleRepository.selectList(wrapper).stream().map(this::toDTO).collect(Collectors.toList());
    }

    @Transactional
    public StyleSampleDTO create(StyleSampleDTO dto) {
        Long userId = ThreadLocalUtil.getUserId();
        StyleSample entity = new StyleSample();
        BeanUtils.copyProperties(dto, entity);
        entity.setUserId(userId);
        entity.setWordCount(dto.getContent() != null ? dto.getContent().length() : 0);

        // 自动分析风格指纹
        if (dto.getContent() != null && !dto.getContent().isBlank()) {
            Map<String, Object> fingerprint = analyzeStyleFingerprint(dto.getContent());
            try {
                entity.setStyleFingerprint(objectMapper.writeValueAsString(fingerprint));
            } catch (JsonProcessingException e) {
                entity.setStyleFingerprint("{}");
            }
        }

        styleSampleRepository.insert(entity);
        return toDTO(entity);
    }

    @Transactional
    public void delete(Long id) {
        StyleSample entity = styleSampleRepository.selectById(id);
        if (entity == null) {
            throw new ResourceNotFoundException("样本不存在");
        }
        if (styleSampleRepository.deleteById(id) <= 0) {
            throw new BusinessException("删除失败");
        }
    }

    // ==================== 风格分析 ====================

    /**
     * 分析文本的风格指纹
     */
    public Map<String, Object> analyzeStyleFingerprint(String text) {
        Map<String, Object> fingerprint = new LinkedHashMap<>();

        // 1. 平均句长
        String[] sentences = text.split("[。！？\n]+");
        double avgSentenceLength = 0;
        if (sentences.length > 0) {
            avgSentenceLength = Arrays.stream(sentences)
                    .mapToInt(String::length)
                    .average()
                    .orElse(0);
        }
        fingerprint.put("avgSentenceLength", Math.round(avgSentenceLength * 10) / 10.0);

        // 2. 词汇丰富度（去重字符比例）
        Set<Character> uniqueChars = new HashSet<>();
        for (char c : text.toCharArray()) {
            if (Character.isLetter(c)) {
                uniqueChars.add(c);
            }
        }
        double richness = text.length() > 0 ? (double) uniqueChars.size() / text.length() : 0;
        fingerprint.put("vocabularyRichness", Math.round(richness * 100) / 100.0);

        // 3. 对话比例（引号内容占比）
        int dialogueChars = 0;
        boolean inDialogue = false;
        for (char c : text.toCharArray()) {
            if (c == '“' || c == '「' || c == '\"') {
                inDialogue = true;
            } else if (c == '”' || c == '」' || c == '\"') {
                inDialogue = false;
                dialogueChars++;
            } else if (inDialogue) {
                dialogueChars++;
            }
        }
        double dialogueRatio = text.length() > 0 ? (double) dialogueChars / text.length() : 0;
        fingerprint.put("dialogueRatio", Math.round(dialogueRatio * 100) / 100.0);

        // 4. 描述性比例（形容词/副词密度 - 简单估算）
        String[] descIndicators = {"的", "地", "得", "很", "非常", "极其", "格外", "十分"};
        int descCount = 0;
        for (String indicator : descIndicators) {
            int idx = 0;
            while ((idx = text.indexOf(indicator, idx)) != -1) {
                descCount++;
                idx += indicator.length();
            }
        }
        double descDensity = text.length() > 0 ? (double) descCount / text.length() : 0;
        fingerprint.put("descriptiveDensity", Math.round(descDensity * 100) / 100.0);

        // 5. 风格标签推断
        String styleLabel = inferStyleLabel(avgSentenceLength, dialogueRatio, descDensity);
        fingerprint.put("inferredStyle", styleLabel);

        return fingerprint;
    }

    /**
     * 根据分析数据推断风格标签
     */
    private String inferStyleLabel(double avgSentenceLen, double dialogueRatio, double descDensity) {
        if (dialogueRatio > 0.4) return "对话驱动型";
        if (avgSentenceLen > 80 && descDensity > 0.08) return "细腻描写型";
        if (avgSentenceLen < 30) return "简洁明快型";
        if (descDensity > 0.06) return "华丽辞藻型";
        return "平实叙述型";
    }

    // ==================== 风格扩写 ====================

    /**
     * 风格扩写 - 基于样本或风格标签
     */
    public StyleExpandResultDTO expand(StyleExpandRequestDTO request) {
        String text = request.getText();
        String style = request.getStyle();
        Integer intensity = request.getIntensity() != null ? request.getIntensity() : 5;
        Long sampleId = request.getSampleId();

        // 如果有样本 ID，基于样本分析风格
        if (sampleId != null) {
            StyleSample sample = styleSampleRepository.selectById(sampleId);
            if (sample != null) {
                style = sample.getStyleLabel() != null ? sample.getStyleLabel() : style;
            }
        }

        // 构建扩写结果
        String result = expandByStyle(text, style, intensity);
        return StyleExpandResultDTO.of(result, style, text.length());
    }

    /**
     * 根据风格类型进行扩写
     */
    private String expandByStyle(String text, String style, int intensity) {
        StringBuilder sb = new StringBuilder(text);

        // 根据风格添加不同的扩写内容
        switch (style) {
            case "细腻描写型":
                sb.append("\n\n").append(generateDescriptiveExpand(intensity));
                break;
            case "对话驱动型":
                sb.append("\n\n").append(generateDialogueExpand(intensity));
                break;
            case "简洁明快型":
                sb.append("\n\n").append(generateConciseExpand(intensity));
                break;
            case "华丽辞藻型":
                sb.append("\n\n").append(generateFloridExpand(intensity));
                break;
            case "平实叙述型":
            default:
                sb.append("\n\n").append(generateNarrativeExpand(intensity));
                break;
        }

        return sb.toString();
    }

    private String generateDescriptiveExpand(int intensity) {
        String[] templates = {
                "阳光透过窗棂洒落，在地板上投下斑驳的光影，空气中漂浮着细小的尘埃，一切都显得那么静谧而安详。",
                "微风拂过树梢，带来阵阵花香，远处传来隐约的鸟鸣声，仿佛整个世界都在这一刻静止了。",
                "他的目光深邃而悠远，仿佛能看穿时空的阻隔，直达那遥远的彼岸。"
        };
        return templates[intensity % templates.length];
    }

    private String generateDialogueExpand(int intensity) {
        String[] templates = {
                "\"你说什么？\"他猛地抬起头，眼中闪过一丝难以置信的神色。\n\"我说，这一切都是真的。\"她平静地回答，声音里带着不容置疑的坚定。",
                "\"为什么？\"他低声问道，声音有些颤抖。\n她沉默了片刻，终于开口：\"因为，这是唯一的办法。\""
        };
        return templates[intensity % templates.length];
    }

    private String generateConciseExpand(int intensity) {
        String[] templates = {
                "他转身离去。夕阳将他的影子拉得很长很长。",
                "一切都结束了。新的篇章，即将开始。"
        };
        return templates[intensity % templates.length];
    }

    private String generateFloridExpand(int intensity) {
        String[] templates = {
                "那如诗如画的景致，宛如一幅泼墨山水画卷徐徐展开，令人心驰神往，沉醉其中不能自拔。",
                "岁月如歌，时光似水，在这浩瀚无垠的天地间，每一个生命都在谱写属于自己的华彩乐章。"
        };
        return templates[intensity % templates.length];
    }

    private String generateNarrativeExpand(int intensity) {
        String[] templates = {
                "时间一分一秒地过去，事情的发展超出了所有人的预料。",
                "就这样，故事翻开了新的一页。前方的路还很长，但至少，他们已经迈出了第一步。"
        };
        return templates[intensity % templates.length];
    }

    // ==================== DTO 转换 ====================

    private StyleSampleDTO toDTO(StyleSample entity) {
        StyleSampleDTO dto = new StyleSampleDTO();
        BeanUtils.copyProperties(entity, dto);
        if (entity.getStyleFingerprint() != null && !entity.getStyleFingerprint().isBlank()) {
            try {
                dto.setStyleFingerprint(objectMapper.readTree(entity.getStyleFingerprint()));
            } catch (JsonProcessingException e) {
                dto.setStyleFingerprint(entity.getStyleFingerprint());
            }
        }
        return dto;
    }
}