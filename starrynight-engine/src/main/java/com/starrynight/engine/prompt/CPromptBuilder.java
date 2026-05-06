package com.starrynight.engine.prompt;

import com.starrynight.engine.vector.VectorEntry;

public class CPromptBuilder {

    public String build(CPromptContext ctx) {
        StringBuilder sb = new StringBuilder();
        sb.append("你是一位资深网文编辑与写作教练。请严格遵守以下约束生成内容。\n\n");

        if (ctx.getIntentSummary() != null && !ctx.getIntentSummary().isBlank()) {
            sb.append("【写作意图】\n").append(ctx.getIntentSummary()).append("\n\n");
        }

        int constraintTokens = calculateConstraintTokens(ctx.getConstraints());
        int systemPromptTokens = estimateSystemPromptTokens(sb.toString());
        int outputSectionTokens = estimateOutputSectionTokens();
        int reservedTokens = systemPromptTokens + outputSectionTokens + 100;

        int availableForConstraints = ctx.getMaxTokens() - reservedTokens;
        int usedTokens = 0;

        sb.append("【必须遵守的约束】(Token: ").append(constraintTokens).append("/").append(availableForConstraints).append(")\n");
        int i = 1;
        for (VectorEntry e : ctx.getConstraints()) {
            String chunk = e.getChunk();
            if (chunk == null || chunk.isBlank()) {
                continue;
            }

            int entryTokens = estimateEntryTokens(chunk, i);
            if (usedTokens + entryTokens > availableForConstraints) {
                sb.append("\n[上下文已被截断，保留最高优先级约束...]");
                break;
            }

            sb.append(i++).append(". ").append(chunk).append("\n");
            usedTokens += entryTokens;
        }

        sb.append("\n【输出要求】\n");
        sb.append("- 不要编造与约束冲突的设定/能力/时间线\n");
        sb.append("- 若信息不足，先提出最少的澄清问题或给出保守版本\n");
        sb.append("- 保持角色性格一致，注意世界规则约束\n");

        if (ctx.isTruncated()) {
            sb.append("\n[警告：上下文已截断，部分历史信息可能丢失]\n");
        }

        return sb.toString();
    }

    private int calculateConstraintTokens(java.util.List<VectorEntry> constraints) {
        if (constraints == null || constraints.isEmpty()) {
            return 0;
        }
        int total = 0;
        for (VectorEntry e : constraints) {
            if (e != null && e.getChunk() != null) {
                total += estimateEntryTokens(e.getChunk(), 0);
            }
        }
        return total;
    }

    private int estimateEntryTokens(String chunk, int index) {
        int baseTokens = chunk != null ? chunk.length() / 2 : 0;
        int indexOverhead = index > 0 ? 5 : 0;
        return baseTokens + indexOverhead;
    }

    private int estimateSystemPromptTokens(String currentPrompt) {
        return currentPrompt.length() / 4;
    }

    private int estimateOutputSectionTokens() {
        return 80;
    }
}

