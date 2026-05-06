package com.starrynight.engine.consistency;

import com.starrynight.engine.prompt.CPromptContext;

/**
 * 一致性自检引擎（最小骨架）。
 * 后续可按 core-logic.md 扩展：性格偏移/世界规则冲突/时间线缝合 等。
 */
public class ConsistencyChecker {

    public ConsistencyReport check(CPromptContext context, String generatedText) {
        ConsistencyReport report = new ConsistencyReport();
        report.setPassed(true);

        if (generatedText == null || generatedText.isBlank()) {
            ConsistencyIssue issue = new ConsistencyIssue();
            issue.setCategory("output");
            issue.setSeverity("high");
            issue.setMessage("生成内容为空");
            issue.setSuggestion("请补充写作意图或提高生成长度");
            report.getIssues().add(issue);
            report.setPassed(false);
        }

        if (generatedText != null) {
            checkRuleConflict(report, generatedText);
            checkTimelineConflict(report, generatedText);
            checkPersonalityDrift(report, generatedText);
        }

        if (!report.getIssues().isEmpty()) {
            boolean hasHigh = report.getIssues().stream()
                    .anyMatch(i -> "high".equalsIgnoreCase(i.getSeverity()));
            report.setPassed(!hasHigh);
        }

        return report;
    }

    private void checkRuleConflict(ConsistencyReport report, String text) {
        if (containsAny(text, "违反设定", "违背设定", "无视规则", "打破规则", "禁忌被触发")) {
            ConsistencyIssue issue = new ConsistencyIssue();
            issue.setCategory("rule");
            issue.setSeverity("medium");
            issue.setMessage("检测到可能的世界规则冲突");
            issue.setSuggestion("请核对世界观设定、能力边界和禁忌规则");
            report.getIssues().add(issue);
        }
    }

    private void checkTimelineConflict(ConsistencyReport report, String text) {
        boolean hasMorning = containsAny(text, "清晨", "黎明", "天亮");
        boolean hasNight = containsAny(text, "深夜", "半夜", "子时");
        boolean hasSimultaneous = containsAny(text, "同一时间", "同时");
        boolean hasLongGap = containsAny(text, "三天后", "数月后", "一年后");
        if ((hasMorning && hasNight) || (hasSimultaneous && hasLongGap)) {
            ConsistencyIssue issue = new ConsistencyIssue();
            issue.setCategory("timeline");
            issue.setSeverity("medium");
            issue.setMessage("检测到可能的时间线跳变或冲突");
            issue.setSuggestion("请检查章节内时间推进是否连贯，并补充时间过渡语句");
            report.getIssues().add(issue);
        }
    }

    private void checkPersonalityDrift(ConsistencyReport report, String text) {
        if (containsAny(text, "判若两人", "性格突变", "一反常态且无铺垫")) {
            ConsistencyIssue issue = new ConsistencyIssue();
            issue.setCategory("personality");
            issue.setSeverity("low");
            issue.setMessage("检测到可能的人物性格偏移");
            issue.setSuggestion("请补充角色动机或过渡情节，避免突兀转变");
            report.getIssues().add(issue);
        }
    }

    private boolean containsAny(String text, String... tokens) {
        for (String token : tokens) {
            if (text.contains(token)) {
                return true;
            }
        }
        return false;
    }
}

