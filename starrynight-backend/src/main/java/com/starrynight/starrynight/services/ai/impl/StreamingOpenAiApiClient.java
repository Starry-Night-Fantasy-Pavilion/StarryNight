package com.starrynight.starrynight.services.ai.impl;

import com.starrynight.starrynight.services.ai.AiApiClient;
import com.starrynight.starrynight.services.ai.model.ChatCompletionRequest;
import com.starrynight.starrynight.services.ai.model.ChatCompletionResponse;
import com.starrynight.starrynight.system.system.service.RuntimeConfigService;
import org.springframework.http.HttpHeaders;
import org.springframework.http.MediaType;
import org.springframework.http.codec.ServerSentEvent;
import org.springframework.web.reactive.function.client.WebClient;
import reactor.core.publisher.Flux;
import reactor.core.publisher.Mono;

public class StreamingOpenAiApiClient implements AiApiClient {

    private final WebClient.Builder webClientBuilder;
    private final RuntimeConfigService runtimeConfigService;

    public StreamingOpenAiApiClient(WebClient.Builder webClientBuilder,
                                    RuntimeConfigService runtimeConfigService) {
        this.webClientBuilder = webClientBuilder;
        this.runtimeConfigService = runtimeConfigService;
    }

    public boolean isOpenAiConfigured() {
        return OpenAiMockResponses.isRealOpenAiKeyConfigured(runtimeConfigService.getProperty("openai.api.key"));
    }

    @Override
    public Mono<ChatCompletionResponse> chatCompletion(ChatCompletionRequest request) {
        String defaultModel = runtimeConfigService.getString("openai.api.model", "gpt-4o-mini");
        if (request.getModel() == null || request.getModel().isEmpty()) {
            request.setModel(defaultModel);
        }

        if (Boolean.TRUE.equals(request.getStream())) {
            return Mono.error(new UnsupportedOperationException(
                    "Use chatCompletionStream for streaming requests"));
        }

        String apiKeyRaw = runtimeConfigService.getProperty("openai.api.key");
        if (!OpenAiMockResponses.isRealOpenAiKeyConfigured(apiKeyRaw)) {
            return Mono.error(new IllegalStateException("未配置有效的 OpenAI API Key，无法使用非流式 OpenAI 直连（请使用主 AiApiClient 或配置密钥）。"));
        }

        WebClient webClient = buildWebClient(apiKeyRaw.trim());

        return webClient.post()
                .uri("/chat/completions")
                .body(Mono.just(request), ChatCompletionRequest.class)
                .retrieve()
                .bodyToMono(ChatCompletionResponse.class);
    }

    public Flux<ChatCompletionResponse> chatCompletionStream(ChatCompletionRequest request) {
        String defaultModel = runtimeConfigService.getString("openai.api.model", "gpt-4o-mini");
        if (request.getModel() == null || request.getModel().isEmpty()) {
            request.setModel(defaultModel);
        }
        request.setStream(true);

        String apiKeyRaw = runtimeConfigService.getProperty("openai.api.key");
        if (!OpenAiMockResponses.isRealOpenAiKeyConfigured(apiKeyRaw)) {
            return Flux.error(new IllegalStateException("未配置有效的 OpenAI API Key，无法使用流式生成。"));
        }

        WebClient webClient = buildWebClient(apiKeyRaw.trim());

        return webClient.post()
                .uri("/chat/completions")
                .bodyValue(request)
                .accept(MediaType.TEXT_EVENT_STREAM)
                .retrieve()
                .bodyToFlux(String.class)
                .filter(line -> line.startsWith("data: "))
                .map(line -> line.substring(6))
                .filter(line -> !"[DONE]".equals(line.trim()))
                .map(this::parseSSEvent);
    }

    private WebClient buildWebClient(String apiKey) {
        String baseUrl = runtimeConfigService.getString("openai.api.base-url", "https://api.openai.com/v1");
        return webClientBuilder
                .baseUrl(baseUrl)
                .defaultHeader(HttpHeaders.AUTHORIZATION, "Bearer " + apiKey)
                .defaultHeader(HttpHeaders.CONTENT_TYPE, MediaType.APPLICATION_JSON_VALUE)
                .build();
    }

    private ChatCompletionResponse parseSSEvent(String data) {
        if (data == null || data.trim().isEmpty()) {
            return null;
        }

        try {
            return parseResponse(data);
        } catch (Exception e) {
            return null;
        }
    }

    private ChatCompletionResponse parseResponse(String json) {
        com.alibaba.fastjson2.JSONObject root = com.alibaba.fastjson2.JSON.parseObject(json);

        ChatCompletionResponse response = new ChatCompletionResponse();
        response.setId(root.getString("id"));
        response.setObject("chat.completion.chunk");
        response.setCreated(root.getLong("created"));
        response.setModel(root.getString("model"));

        com.alibaba.fastjson2.JSONArray choices = root.getJSONArray("choices");
        if (choices != null && !choices.isEmpty()) {
            com.alibaba.fastjson2.JSONObject choiceObj = choices.getJSONObject(0);

            ChatCompletionResponse.Choice choice = new ChatCompletionResponse.Choice();
            choice.setIndex(choiceObj.getIntValue("index"));
            choice.setFinishReason(choiceObj.getString("finish_reason"));

            com.alibaba.fastjson2.JSONObject delta = choiceObj.getJSONObject("delta");
            if (delta != null) {
                ChatCompletionResponse.Message message = new ChatCompletionResponse.Message();
                message.setRole(delta.getString("role"));
                message.setContent(delta.getString("content"));
                choice.setMessage(message);
            }

            response.setChoices(java.util.List.of(choice));
        }

        com.alibaba.fastjson2.JSONObject usageObj = root.getJSONObject("usage");
        if (usageObj != null) {
            ChatCompletionResponse.Usage usage = new ChatCompletionResponse.Usage();
            usage.setPromptTokens(usageObj.getIntValue("prompt_tokens"));
            usage.setCompletionTokens(usageObj.getIntValue("completion_tokens"));
            usage.setTotalTokens(usageObj.getIntValue("total_tokens"));
            response.setUsage(usage);
        }

        return response;
    }
}
