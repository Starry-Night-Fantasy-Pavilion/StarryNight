package com.starrynight.starrynight.services.ai.impl;

import com.starrynight.starrynight.services.ai.AiApiClient;
import com.starrynight.starrynight.services.ai.model.ChatCompletionRequest;
import com.starrynight.starrynight.services.ai.model.ChatCompletionResponse;
import com.starrynight.starrynight.system.system.service.RuntimeConfigService;
import org.springframework.http.HttpHeaders;
import org.springframework.http.MediaType;
import org.springframework.web.reactive.function.client.WebClient;
import org.springframework.web.reactive.function.client.WebClientResponseException;
import reactor.core.publisher.Mono;

public class OpenAiApiClient implements AiApiClient {

    private final WebClient.Builder webClientBuilder;
    private final RuntimeConfigService runtimeConfigService;

    public OpenAiApiClient(WebClient.Builder webClientBuilder,
                           RuntimeConfigService runtimeConfigService) {
        this.webClientBuilder = webClientBuilder;
        this.runtimeConfigService = runtimeConfigService;
    }

    @Override
    public Mono<ChatCompletionResponse> chatCompletion(ChatCompletionRequest request) {
        String defaultModel = runtimeConfigService.getString("openai.api.model", "gpt-4o-mini");
        String apiKeyRaw = runtimeConfigService.getProperty("openai.api.key");
        if (!OpenAiMockResponses.isRealOpenAiKeyConfigured(apiKeyRaw)) {
            return OpenAiMockResponses.chatCompletion(request, defaultModel);
        }

        if (request.getModel() == null || request.getModel().isEmpty()) {
            request.setModel(defaultModel);
        }

        String baseUrl = runtimeConfigService.getString("openai.api.base-url", "https://api.openai.com/v1");
        WebClient webClient = webClientBuilder
                .baseUrl(baseUrl)
                .defaultHeader(HttpHeaders.AUTHORIZATION, "Bearer " + apiKeyRaw.trim())
                .defaultHeader(HttpHeaders.CONTENT_TYPE, MediaType.APPLICATION_JSON_VALUE)
                .build();

        return webClient.post()
                .uri("/chat/completions")
                .body(Mono.just(request), ChatCompletionRequest.class)
                .retrieve()
                .bodyToMono(ChatCompletionResponse.class)
                .onErrorResume(WebClientResponseException.class, e -> {
                    if (e.getStatusCode().value() == 401) {
                        return Mono.error(new IllegalStateException(
                                "OpenAI API authentication failed. Please check your API key is valid. "
                                        + "Error: " + e.getResponseBodyAsString()
                        ));
                    }
                    if (e.getStatusCode().value() == 429) {
                        return Mono.error(new IllegalStateException(
                                "OpenAI API rate limit exceeded. Please try again later."
                        ));
                    }
                    return Mono.error(e);
                });
    }
}
