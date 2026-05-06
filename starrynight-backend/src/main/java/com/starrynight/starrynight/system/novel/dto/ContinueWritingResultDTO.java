package com.starrynight.starrynight.system.novel.dto;

import lombok.Data;
import lombok.EqualsAndHashCode;

/**
 * 目前与 {@link ContentExpandResultDTO} 字段结构保持一致：
 * - content / wordCount
 * - styleFingerprint
 * - segments
 * - generationPlan
 *
 * 后续如需扩展（例如追加保留“继续点/衔接句”等），可以在此基础上演进。
 */
@Data
@EqualsAndHashCode(callSuper = true)
public class ContinueWritingResultDTO extends ContentExpandResultDTO {
}

