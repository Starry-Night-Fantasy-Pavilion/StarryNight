package com.starrynight.starrynight.services.ai;

import com.starrynight.engine.memcore.MemCoreManager;
import com.starrynight.engine.prompt.CPromptBuilder;
import com.starrynight.engine.prompt.CPromptContext;
import com.starrynight.engine.retrieval.HybridRetriever;
import com.starrynight.starrynight.services.ai.model.ChatCompletionRequest;
import com.starrynight.starrynight.services.ai.model.ChatCompletionResponse;
import com.starrynight.starrynight.system.system.service.RuntimeConfigService;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import reactor.core.publisher.Mono;

import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.Optional;

@Service
public class AiGenerationService {

    @Autowired
    private AiApiClient aiApiClient;

    @Autowired
    private MemCoreManager memCoreManager;

    @Autowired
    private HybridRetriever hybridRetriever;

    @Autowired
    private AiCacheService aiCacheService;

    @Autowired
    private RuntimeConfigService runtimeConfigService;

    private String defaultModel() {
        return runtimeConfigService.getString("openai.api.model", "gpt-4o-mini");
    }

    private double defaultTemperature() {
        return runtimeConfigService.getDouble("ai.generation.temperature", 0.7);
    }

    private int defaultMaxTokens() {
        return runtimeConfigService.getInt("ai.generation.max-tokens", 4096);
    }

    private boolean cacheEnabled() {
        return runtimeConfigService.getBoolean("ai.cache.enabled", true);
    }

    public String generate(String prompt) {
        Map<String, Object> cacheKey = new HashMap<>();
        cacheKey.put("prompt", prompt);
        cacheKey.put("model", defaultModel());
        cacheKey.put("temperature", defaultTemperature());

        if (cacheEnabled()) {
            Optional<String> cached = aiCacheService.get("generate", cacheKey, String.class);
            if (cached.isPresent()) {
                return cached.get();
            }
        }

        ChatCompletionRequest request = ChatCompletionRequest.builder()
                .model(defaultModel())
                .messages(List.of(
                        new ChatCompletionRequest.Message("system", "你是一个专业的小说写作助手。"),
                        new ChatCompletionRequest.Message("user", prompt)
                ))
                .temperature(defaultTemperature())
                .maxTokens(defaultMaxTokens())
                .build();

        String result = aiApiClient.chatCompletion(request)
                .map(response -> {
                    if (response != null && response.getChoices() != null && !response.getChoices().isEmpty()) {
                        ChatCompletionResponse.Choice choice = response.getChoices().get(0);
                        if (choice != null && choice.getMessage() != null && choice.getMessage().getContent() != null) {
                            return choice.getMessage().getContent();
                        }
                    }
                    return "AI 未能生成有效内容。";
                })
                .block();

        if (cacheEnabled() && result != null) {
            aiCacheService.put("generate", cacheKey, result);
        }

        return result;
    }

    public Mono<String> generate(String model, String prompt, CPromptContext context) {
        String actualModel = model != null && !model.isEmpty() ? model : defaultModel();

        Map<String, Object> cacheKey = new HashMap<>();
        cacheKey.put("model", actualModel);
        cacheKey.put("prompt", prompt);
        if (context != null) {
            cacheKey.put("contextType", context.getClass().getSimpleName());
        }
        cacheKey.put("temperature", defaultTemperature());

        CPromptBuilder builder = new CPromptBuilder();
        String basePrompt = builder.build(context);
        String fullPrompt = basePrompt + "\n\n【用户提示】\n" + prompt;

        if (cacheEnabled()) {
            Optional<String> cached = aiCacheService.get("generateWithContext", cacheKey, String.class);
            if (cached.isPresent()) {
                return Mono.just(cached.get());
            }
        }

        ChatCompletionRequest request = ChatCompletionRequest.builder()
                .model(actualModel)
                .messages(List.of(
                        new ChatCompletionRequest.Message("system", "你是一个专业的小说写作助手。"),
                        new ChatCompletionRequest.Message("user", fullPrompt)
                ))
                .temperature(defaultTemperature())
                .maxTokens(defaultMaxTokens())
                .build();

        return aiApiClient.chatCompletion(request)
                .map(response -> {
                    if (response.getChoices() != null && !response.getChoices().isEmpty()) {
                        return response.getChoices().get(0).getMessage().getContent();
                    }
                    return "AI 未能生成有效内容。";
                })
                .doOnNext(result -> {
                    if (cacheEnabled() && result != null) {
                        aiCacheService.put("generateWithContext", cacheKey, result);
                    }
                });
    }
}
