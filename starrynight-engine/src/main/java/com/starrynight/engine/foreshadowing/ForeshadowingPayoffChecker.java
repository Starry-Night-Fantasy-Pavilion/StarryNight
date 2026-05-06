package com.starrynight.engine.foreshadowing;

import org.springframework.stereotype.Component;

import java.util.*;
import java.util.regex.Pattern;
import java.util.stream.Collectors;

@Component
public class ForeshadowingPayoffChecker {

    private static final float KEYWORD_MATCH_THRESHOLD = 0.6f;
    private static final float SEMANTIC_MATCH_THRESHOLD = 0.7f;

    public PayoffCheckResult checkPayoff(Foreshadowing foreshadowing, List<String> subsequentContents) {
        PayoffCheckResult result = new PayoffCheckResult();
        List<PayoffCheckResult.PayoffMatch> allMatches = new ArrayList<>();

        List<String> keywords = foreshadowing.getRelatedKeywords();
        if (keywords == null || keywords.isEmpty()) {
            keywords = extractKeywordsFromContent(foreshadowing.getSetupContent());
        }

        for (String content : subsequentContents) {
            PayoffCheckResult.PayoffMatch keywordMatch = keywordMatching(foreshadowing, content);
            if (keywordMatch != null) {
                allMatches.add(keywordMatch);
            }
        }

        if (!allMatches.isEmpty()) {
            PayoffCheckResult.PayoffMatch bestMatch = allMatches.stream()
                    .max(Comparator.comparing(PayoffCheckResult.PayoffMatch::getSimilarityScore))
                    .orElse(null);

            if (bestMatch != null) {
                result.setPaidOff(true);
                result.setMatchType(bestMatch.getMatchType());
                result.setMatchedChapter(bestMatch.getChapterNo());
                result.setConfidence(bestMatch.getSimilarityScore());
                result.setMatchedContent(bestMatch.getMatchedContent());
                return result;
            }
        }

        result.setPaidOff(false);
        result.setConfidence(0f);
        result.setSuggestions(generatePayoffSuggestions(foreshadowing));
        return result;
    }

    private PayoffCheckResult.PayoffMatch keywordMatching(Foreshadowing foreshadowing, String content) {
        List<String> keywords = foreshadowing.getRelatedKeywords();
        if (keywords == null || keywords.isEmpty()) {
            keywords = extractKeywordsFromContent(foreshadowing.getSetupContent());
        }

        int matchedCount = 0;
        String matchedText = "";
        for (String keyword : keywords) {
            Pattern pattern = Pattern.compile(keyword);
            if (pattern.matcher(content).find()) {
                matchedCount++;
                matchedText = findContextWithKeyword(content, keyword);
            }
        }

        if (matchedCount > 0) {
            float score = (float) matchedCount / keywords.size();
            PayoffCheckResult.PayoffMatch match = new PayoffCheckResult.PayoffMatch();
            match.setChapterNo(foreshadowing.getChapterNo());
            match.setMatchedContent(matchedText.length() > 100 ? matchedText.substring(0, 100) + "..." : matchedText);
            match.setSimilarityScore(score);
            match.setMatchType("keyword");
            return match;
        }

        return null;
    }

    private String findContextWithKeyword(String content, String keyword) {
        int index = content.indexOf(keyword);
        if (index == -1) return "";

        int start = Math.max(0, index - 30);
        int end = Math.min(content.length(), index + keyword.length() + 30);
        return content.substring(start, end);
    }

    private List<String> extractKeywordsFromContent(String content) {
        Set<String> keywords = new HashSet<>();

        String[] patterns = {
                "[\\u4e00-\\u9fa5]{2,6}",
                "(戒指|玉佩|古书|卷轴|令牌|宝石|秘密|身份|力量|命运|血缘)",
        };

        for (String pattern : patterns) {
            Pattern p = Pattern.compile(pattern);
            java.util.regex.Matcher m = p.matcher(content);
            while (m.find()) {
                String word = m.group();
                if (word.length() >= 2 && word.length() <= 6) {
                    keywords.add(word);
                }
            }
        }

        return keywords.stream().limit(5).collect(Collectors.toList());
    }

    public List<String> generatePayoffSuggestions(Foreshadowing foreshadowing) {
        List<String> suggestions = new ArrayList<>();

        switch (foreshadowing.getType()) {
            case ITEM:
                suggestions.add("让神秘物品在关键时刻发挥作用");
                suggestions.add("揭示物品的真正来历和用途");
                suggestions.add("物品破碎或消失，象征某种终结");
                break;
            case IDENTITY:
                suggestions.add("通过某个事件揭露真实身份");
                suggestions.add("让他人发现并揭示秘密");
                suggestions.add("身份被敌人利用引发冲突");
                break;
            case RELATIONSHIP:
                suggestions.add("通过血缘或命运事件揭示关系");
                suggestions.add("关系成为剧情转折的关键");
                suggestions.add("关系被背叛或重新定义");
                break;
            case ABILITY:
                suggestions.add("能力在危机时刻觉醒");
                suggestions.add("能力失控引发危机");
                suggestions.add("能力来源被揭示");
                break;
            case PLOT:
                suggestions.add("谜题通过调查解开");
                suggestions.add("多个伏笔联动形成高潮");
                suggestions.add("伏笔被敌人利用形成冲突");
                break;
            case WORLD:
                suggestions.add("世界观设定在关键时刻展开");
                suggestions.add("规则被打破引发危机");
                suggestions.add("隐藏的世界秘密被揭露");
                break;
        }

        if (foreshadowing.getExpectedChapterNo() != null) {
            suggestions.add("在第" + foreshadowing.getExpectedChapterNo() + "章前后安排回收");
        }

        Collections.shuffle(suggestions);
        return suggestions.stream().limit(3).collect(Collectors.toList());
    }
}