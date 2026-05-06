package com.starrynight.starrynight.services.ai.model;

import lombok.AllArgsConstructor;
import lombok.Builder;
import lombok.Data;
import lombok.NoArgsConstructor;

import java.util.List;
import java.util.Map;

@Data
@Builder
@NoArgsConstructor
@AllArgsConstructor
public class ChatCompletionRequest {

    /**
     * 模型名称
     */
    private String model;

    /**
     * 消息列表
     */
    private List<Message> messages;

    /**
     * 温度
     */
    private Double temperature;

    /**
     * Top P
     */
    private Double topP;

    /**
     * 返回结果数量
     */
    private Integer n;

    /**
     * 是否流式传输
     */
    private Boolean stream;

    /**
     * 停止序列
     */
    private List<String> stop;

    /**
     * 最大 token 数
     */
    private Integer maxTokens;

    /**
     * 惩罚因子
     */
    private Double presencePenalty;

    /**
     * 频率惩罚因子
     */
    private Double frequencyPenalty;

    /**
     * 用户 ID
     */
    private String user;

    @Data
    @Builder
    @NoArgsConstructor
    @AllArgsConstructor
    public static class Message {
        private String role;
        private String content;
    }
}