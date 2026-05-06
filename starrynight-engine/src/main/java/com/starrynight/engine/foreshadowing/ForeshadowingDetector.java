package com.starrynight.engine.foreshadowing;

import org.springframework.stereotype.Component;

import java.util.*;
import java.util.regex.Matcher;
import java.util.regex.Pattern;
import java.util.stream.Collectors;

@Component
public class ForeshadowingDetector {

    private static final List<Pattern> ITEM_PATTERNS = Arrays.asList(
            Pattern.compile("(戒指|玉佩|古书|卷轴|令牌|宝石|符咒|印章|钥匙|地图|日记|遗物)"),
            Pattern.compile("(散发|闪烁|隐隐|似乎)着.*(光芒|气息|力量)"),
            Pattern.compile("神秘的.{0,5}(物品|东西|道具)"),
            Pattern.compile("(古老|神秘|尘封|封印)的.{0,10}")
    );

    private static final List<Pattern> DIALOGUE_PATTERNS = Arrays.asList(
            Pattern.compile("\"(你|他|她).{0,20}(不|永远|迟早|总有一天).{0,30}\""),
            Pattern.compile("\".{0,10}的秘密.{0,10}\""),
            Pattern.compile("\"总有一天.{0,20}\""),
            Pattern.compile("\".{0,10}不要忘记.{0,10}\""),
            Pattern.compile("\".{0,10}你会明白的.{0,10}\"")
    );

    private static final List<Pattern> MYSTERY_PATTERNS = Arrays.asList(
            Pattern.compile("然而.{0,20}并未.{0,20}(说明|解释|提及)"),
            Pattern.compile("这个.{0,10}(谜|秘密|疑问).{0,10}(一直|始终|至今)"),
            Pattern.compile(".{0,10}始终是个谜"),
            Pattern.compile("没有人知道.{0,20}的真相")
    );

    private static final List<Pattern> IDENTITY_PATTERNS = Arrays.asList(
            Pattern.compile("(真实身份|真正身份|隐藏身份)"),
            Pattern.compile("(其实是|原来是|真正是).{0,15}"),
            Pattern.compile(".{0,5}(隐藏|隐瞒)着.{0,10}秘密")
    );

    private static final List<Pattern> RELATIONSHIP_PATTERNS = Arrays.asList(
            Pattern.compile("(血缘|亲子|兄弟|姐妹|师徒|恩人)关系"),
            Pattern.compile("(前世|今生|宿命)的.{0,10}联系"),
            Pattern.compile(".{0,5}命中注定.{0,10}")
    );

    private static final List<Pattern> ABILITY_PATTERNS = Arrays.asList(
            Pattern.compile("(觉醒|苏醒|觉醒).{0,10}力量"),
            Pattern.compile("沉睡的.{0,10}(能力|力量|力量)"),
            Pattern.compile("(特殊|神秘|强大)的能力")
    );

    public List<Foreshadowing> detect(String content, Long novelId, Integer chapterNo) {
        List<Foreshadowing> results = new ArrayList<>();
        List<MatchResult> allMatches = new ArrayList<>();

        allMatches.addAll(ruleBasedDetection(content, chapterNo, Foreshadowing.ForeshadowingType.ITEM, ITEM_PATTERNS));
        allMatches.addAll(ruleBasedDetection(content, chapterNo, Foreshadowing.ForeshadowingType.DIALOGUE, DIALOGUE_PATTERNS));
        allMatches.addAll(ruleBasedDetection(content, chapterNo, Foreshadowing.ForeshadowingType.PLOT, MYSTERY_PATTERNS));
        allMatches.addAll(ruleBasedDetection(content, chapterNo, Foreshadowing.ForeshadowingType.IDENTITY, IDENTITY_PATTERNS));
        allMatches.addAll(ruleBasedDetection(content, chapterNo, Foreshadowing.ForeshadowingType.RELATIONSHIP, RELATIONSHIP_PATTERNS));
        allMatches.addAll(ruleBasedDetection(content, chapterNo, Foreshadowing.ForeshadowingType.ABILITY, ABILITY_PATTERNS));

        for (MatchResult match : allMatches) {
            Foreshadowing fs = new Foreshadowing();
            fs.setId(UUID.randomUUID().toString());
            fs.setNovelId(novelId);
            fs.setChapterNo(chapterNo);
            fs.setSetupContent(match.content);
            fs.setSetupLocation(match.location);
            fs.setType(match.type);
            fs.setStatus(Foreshadowing.ForeshadowingStatus.PENDING);
            fs.setConfidence(0.85f);
            fs.setDetectedAt(java.time.LocalDateTime.now());
            fs.setUserEdited(false);
            fs.setRelatedKeywords(extractRelatedKeywords(match.content));
            results.add(fs);
        }

        return deduplicate(results);
    }

    private List<MatchResult> ruleBasedDetection(String content, Integer chapterNo,
            Foreshadowing.ForeshadowingType type, List<Pattern> patterns) {
        List<MatchResult> results = new ArrayList<>();

        for (Pattern pattern : patterns) {
            Matcher matcher = pattern.matcher(content);
            while (matcher.find()) {
                String matchedText = matcher.group();
                int start = matcher.start();
                float location = (float) start / content.length();

                if (matchedText.length() >= 5 && !isCommonPhrase(matchedText)) {
                    results.add(new MatchResult(matchedText.trim(), location, type));
                }
            }
        }

        return results;
    }

    private boolean isCommonPhrase(String text) {
        String common = "神秘的";
        return text.contains(common) && text.length() < 15;
    }

    private List<String> extractRelatedKeywords(String content) {
        Set<String> keywords = new HashSet<>();

        String[] words = content.split("[,，.。\"\"''\\s]");
        for (String word : words) {
            if (word.length() >= 2 && word.length() <= 6) {
                keywords.add(word);
            }
        }

        return keywords.stream().limit(5).collect(Collectors.toList());
    }

    private List<Foreshadowing> deduplicate(List<Foreshadowing> list) {
        Map<String, Foreshadowing> unique = new LinkedHashMap<>();
        for (Foreshadowing fs : list) {
            String key = fs.getChapterNo() + ":" + fs.getSetupContent().substring(0, Math.min(20, fs.getSetupContent().length()));
            unique.putIfAbsent(key, fs);
        }
        return new ArrayList<>(unique.values());
    }

    private static class MatchResult {
        String content;
        float location;
        Foreshadowing.ForeshadowingType type;

        MatchResult(String content, float location, Foreshadowing.ForeshadowingType type) {
            this.content = content;
            this.location = location;
            this.type = type;
        }
    }
}