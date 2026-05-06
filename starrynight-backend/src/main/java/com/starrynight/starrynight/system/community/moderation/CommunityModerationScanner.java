package com.starrynight.starrynight.system.community.moderation;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.starrynight.starrynight.system.ai.entity.AiSensitiveWord;
import com.starrynight.starrynight.system.ai.repository.AiSensitiveWordRepository;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Component;
import org.springframework.util.StringUtils;

import java.util.Comparator;
import java.util.List;
import java.util.stream.Collectors;

/**
 * 基于 {@code ai_sensitive_word}（启用中）的社区内容自动审核。
 * <ul>
 *   <li>未命中任何词：自动通过</li>
 *   <li>命中 level=1：进入人工复核队列（帖子/评论待审）</li>
 *   <li>命中 level≥2：高风险，帖子自动驳回；评论拒绝发布（抛业务异常）</li>
 * </ul>
 */
@Component
public class CommunityModerationScanner {

    /** 无敏感词配置或全部禁用时，视为全部自动通过 */
    public static final int LEVEL_REVIEW = 1;
    public static final int LEVEL_BLOCK = 2;

    @Autowired
    private AiSensitiveWordRepository aiSensitiveWordRepository;

    public ScanResult scanPost(String title, String content) {
        return scan(concat(title, content));
    }

    public ScanResult scanComment(String content) {
        return scan(content == null ? "" : content);
    }

    private static String concat(String title, String content) {
        String t = title == null ? "" : title;
        String c = content == null ? "" : content;
        return t + "\n" + c;
    }

    private ScanResult scan(String text) {
        if (!StringUtils.hasText(text)) {
            return ScanResult.pass();
        }
        List<AiSensitiveWord> words = aiSensitiveWordRepository.selectList(
                new LambdaQueryWrapper<AiSensitiveWord>()
                        .eq(AiSensitiveWord::getEnabled, 1)
                        .orderByDesc(AiSensitiveWord::getLevel));
        if (words.isEmpty()) {
            return ScanResult.pass();
        }
        List<AiSensitiveWord> sorted = words.stream()
                .filter(w -> w.getWord() != null && StringUtils.hasText(w.getWord().trim()))
                .sorted(Comparator.comparing(w -> -w.getWord().trim().length()))
                .collect(Collectors.toList());
        for (AiSensitiveWord w : sorted) {
            String needle = w.getWord().trim();
            if (text.contains(needle)) {
                int lv = w.getLevel() == null ? LEVEL_REVIEW : w.getLevel();
                if (lv >= LEVEL_BLOCK) {
                    return ScanResult.blocked(needle);
                }
                return ScanResult.review(needle);
            }
        }
        return ScanResult.pass();
    }

    public enum Verdict {
        PASS,
        NEEDS_REVIEW,
        BLOCKED
    }

    public static final class ScanResult {
        private final Verdict verdict;
        private final String matchedWord;

        private ScanResult(Verdict verdict, String matchedWord) {
            this.verdict = verdict;
            this.matchedWord = matchedWord;
        }

        public static ScanResult pass() {
            return new ScanResult(Verdict.PASS, null);
        }

        public static ScanResult review(String word) {
            return new ScanResult(Verdict.NEEDS_REVIEW, word);
        }

        public static ScanResult blocked(String word) {
            return new ScanResult(Verdict.BLOCKED, word);
        }

        public Verdict getVerdict() {
            return verdict;
        }

        public String getMatchedWord() {
            return matchedWord;
        }
    }
}
