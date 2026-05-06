package com.starrynight.engine.token;

import com.starrynight.engine.vector.VectorEntry;

import java.util.ArrayList;
import java.util.Comparator;
import java.util.List;

/**
 * 上下文智能截断器：
 * - 按优先级排序上下文片段
 * - 按Token数量限制截断
 * - 确保高优先级内容优先保留
 */
public class ContextTruncator {

    private final Tokenizer tokenizer;
    private final int maxTokens;

    public ContextTruncator(Tokenizer tokenizer, int maxTokens) {
        this.tokenizer = tokenizer;
        this.maxTokens = maxTokens;
    }

    public int getMaxTokens() {
        return maxTokens;
    }

    public Tokenizer getTokenizer() {
        return tokenizer;
    }

    /**
     * 截断VectorEntry列表到Token限制
     * @param entries 上下文片段列表
     * @return 截断后的列表
     */
    public List<VectorEntry> truncateToTokenLimit(List<VectorEntry> entries) {
        if (entries == null || entries.isEmpty()) {
            return new ArrayList<>();
        }

        List<VectorEntry> result = new ArrayList<>();
        int currentTokens = 0;

        for (VectorEntry entry : entries) {
            String chunk = entry.getChunk();
            if (chunk == null || chunk.isBlank()) {
                continue;
            }

            int entryTokens = tokenizer.countTokens(chunk);
            int overheadTokens = estimateEntryOverhead(entry);

            if (currentTokens + entryTokens + overheadTokens > maxTokens) {
                if (result.isEmpty()) {
                    String truncatedChunk = tokenizer.truncate(chunk, maxTokens - overheadTokens);
                    if (!truncatedChunk.isBlank()) {
                        VectorEntry truncatedEntry = createTruncatedEntry(entry, truncatedChunk);
                        result.add(truncatedEntry);
                    }
                }
                break;
            }

            result.add(entry);
            currentTokens += entryTokens + overheadTokens;
        }

        return result;
    }

    /**
     * 估算每条目的固定开销（序号、标注等）
     */
    private int estimateEntryOverhead(VectorEntry entry) {
        return 15;
    }

    /**
     * 创建截断后的Entry副本
     */
    private VectorEntry createTruncatedEntry(VectorEntry original, String truncatedChunk) {
        VectorEntry copy = new VectorEntry();
        copy.setId(original.getId());
        copy.setChunk(truncatedChunk);
        copy.setDenseVector(original.getDenseVector());
        copy.setSparseVector(original.getSparseVector());
        copy.setMetadata(original.getMetadata());
        return copy;
    }

    /**
     * 优先级排序常量
     */
    public static final List<String> PRIORITY_ORDER = List.of(
            "character_baseline",
            "character_snapshot",
            "world_rule",
            "location_detail",
            "outline_node",
            "recent_chapter",
            "foreshadowing",
            "recent_event"
    );

    /**
     * 根据优先级排序上下文片段
     */
    public static List<VectorEntry> sortByPriority(List<VectorEntry> entries) {
        if (entries == null || entries.isEmpty()) {
            return new ArrayList<>();
        }

        return entries.stream()
                .sorted(Comparator.comparingInt(e -> getPriorityIndex(e)))
                .toList();
    }

    private static int getPriorityIndex(VectorEntry entry) {
        if (entry == null || entry.getMetadata() == null || entry.getMetadata().getType() == null) {
            return PRIORITY_ORDER.size();
        }

        String typeName = entry.getMetadata().getType().name();
        int index = PRIORITY_ORDER.indexOf(typeName.toLowerCase());
        return index >= 0 ? index : PRIORITY_ORDER.size();
    }

    /**
     * 计算当前上下文总Token数
     */
    public int calculateTotalTokens(List<VectorEntry> entries) {
        if (entries == null || entries.isEmpty()) {
            return 0;
        }

        int total = 0;
        for (VectorEntry entry : entries) {
            if (entry != null && entry.getChunk() != null) {
                total += tokenizer.countTokens(entry.getChunk());
                total += estimateEntryOverhead(entry);
            }
        }
        return total;
    }
}
