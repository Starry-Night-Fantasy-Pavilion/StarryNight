package com.starrynight.starrynight.system.knowledge.dto;

import lombok.Data;

@Data
public class KnowledgeCapacityDTO {

    private String used;

    private String total;

    public static KnowledgeCapacityDTO of(long usedBytes, long totalBytes) {
        KnowledgeCapacityDTO dto = new KnowledgeCapacityDTO();
        dto.used = formatBytes(usedBytes);
        dto.total = formatBytes(totalBytes);
        return dto;
    }

    private static String formatBytes(long bytes) {
        if (bytes < 1024) return bytes + " B";
        if (bytes < 1024 * 1024) return String.format("%.1f KB", bytes / 1024.0);
        if (bytes < 1024 * 1024 * 1024) return String.format("%.1f MB", bytes / (1024.0 * 1024));
        return String.format("%.1f GB", bytes / (1024.0 * 1024 * 1024));
    }
}