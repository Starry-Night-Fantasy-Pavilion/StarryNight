package com.starrynight.starrynight.services.ai.impl;

import com.starrynight.starrynight.services.ai.model.ChatCompletionRequest;
import com.starrynight.starrynight.services.ai.model.ChatCompletionResponse;
import org.springframework.util.StringUtils;
import reactor.core.publisher.Mono;

import java.util.List;

/**
 * 未配置有效 OpenAI Key 时的本地模拟响应。
 */
public final class OpenAiMockResponses {

    public static final String PLACEHOLDER_KEY = "sk-placeholder-replace-with-real-key";

    private OpenAiMockResponses() {
    }

    public static boolean isRealOpenAiKeyConfigured(String apiKey) {
        if (apiKey == null) {
            return false;
        }
        String k = apiKey.trim();
        return StringUtils.hasText(k) && !PLACEHOLDER_KEY.equals(k);
    }

    public static Mono<ChatCompletionResponse> chatCompletion(ChatCompletionRequest request, String defaultModel) {
        String model = request.getModel() != null && !request.getModel().isEmpty()
                ? request.getModel()
                : defaultModel;

        String content = "这是一个来自 " + model + " 的模拟响应。";

        ChatCompletionResponse.Message message = ChatCompletionResponse.Message.builder()
                .role("assistant")
                .content(content)
                .build();

        ChatCompletionResponse.Choice choice = ChatCompletionResponse.Choice.builder()
                .index(0)
                .message(message)
                .finishReason("stop")
                .build();

        ChatCompletionResponse response = ChatCompletionResponse.builder()
                .id("chatcmpl-mock-" + System.currentTimeMillis())
                .object("chat.completion")
                .created(System.currentTimeMillis() / 1000)
                .model(model)
                .choices(List.of(choice))
                .usage(ChatCompletionResponse.Usage.builder()
                        .promptTokens(50)
                        .completionTokens(50)
                        .totalTokens(100)
                        .build())
                .build();

        return Mono.just(response);
    }
}
