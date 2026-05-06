package com.starrynight.starrynight.services.ai;

import com.starrynight.starrynight.services.ai.impl.StreamingOpenAiApiClient;
import com.starrynight.starrynight.services.ai.model.ChatCompletionRequest;
import com.starrynight.starrynight.services.ai.model.ChatCompletionResponse;
import com.starrynight.starrynight.system.billing.dto.ChargeRequest;
import com.starrynight.starrynight.system.billing.dto.ChargeResult;
import com.starrynight.starrynight.system.billing.dto.EstimateResult;
import com.starrynight.starrynight.system.billing.service.BillingService;
import lombok.extern.slf4j.Slf4j;
import org.springframework.stereotype.Service;
import reactor.core.publisher.Flux;
import reactor.core.publisher.Mono;

@Slf4j
@Service
public class AiBillingService {

    private final AiApiClient aiApiClient;
    private final StreamingOpenAiApiClient streamingClient;
    private final BillingService billingService;

    public AiBillingService(
            AiApiClient aiApiClient,
            StreamingOpenAiApiClient streamingClient,
            BillingService billingService) {
        this.aiApiClient = aiApiClient;
        this.streamingClient = streamingClient;
        this.billingService = billingService;
    }

    public AiGenerationResult generateWithBilling(AiGenerationRequest request) {
        if (!streamingClient.isOpenAiConfigured()) {
            return AiGenerationResult.error("未在 system_config 中配置有效的 OpenAI API Key（openai.api.key），无法进行计费生成。");
        }
        EstimateResult estimate = billingService.estimateCost(
                request.getContentType(),
                request.getUserId(),
                request.getInputTokens(),
                request.getOutputTokens()
        );

        if ("INSUFFICIENT".equals(estimate.getScenario())) {
            return AiGenerationResult.insufficient(estimate.getMessage());
        }

        ChargeRequest chargeReq = new ChargeRequest();
        chargeReq.setUserId(request.getUserId());
        chargeReq.setContentType(request.getContentType());
        chargeReq.setChannelId(request.getChannelId());
        chargeReq.setInputTokens(request.getInputTokens());
        chargeReq.setOutputTokens(request.getOutputTokens());

        ChargeResult chargeResult = billingService.charge(chargeReq);

        if (!chargeResult.getSuccess()) {
            return AiGenerationResult.insufficient(chargeResult.getMessage());
        }

        try {
            ChatCompletionRequest aiRequest = buildAiRequest(request);
            aiRequest.setStream(false);

            ChatCompletionResponse aiResponse = aiApiClient.chatCompletion(aiRequest).block();

            if (aiResponse == null || aiResponse.getChoices() == null || aiResponse.getChoices().isEmpty()) {
                billingService.rollback(chargeResult.getRecordNo(), "Empty AI response");
                return AiGenerationResult.error("AI 未能生成有效内容");
            }

            ChatCompletionResponse.Choice choice = aiResponse.getChoices().get(0);
            String content = choice.getMessage() != null ? choice.getMessage().getContent() : "";

            return AiGenerationResult.success(
                    content,
                    chargeResult,
                    aiResponse.getUsage() != null ? aiResponse.getUsage().getTotalTokens() : 0
            );

        } catch (Exception e) {
            log.error("AI generation failed: {}", e.getMessage(), e);
            billingService.rollback(chargeResult.getRecordNo(), "Exception: " + e.getMessage());
            return AiGenerationResult.error("生成失败: " + e.getMessage());
        }
    }

    public Flux<ChatCompletionResponse> generateStream(AiGenerationRequest request) {
        if (!streamingClient.isOpenAiConfigured()) {
            return Flux.error(new IllegalStateException(
                    "未配置有效的 OpenAI API Key，无法使用流式生成。"));
        }
        ChatCompletionRequest aiRequest = buildAiRequest(request);
        aiRequest.setStream(true);

        return streamingClient.chatCompletionStream(aiRequest);
    }

    public EstimateResult estimate(String contentType, Long userId, Integer inputTokens, Integer outputTokens) {
        return billingService.estimateCost(contentType, userId, inputTokens, outputTokens);
    }

    public void rollbackIfNeeded(String recordNo, String reason) {
        billingService.rollback(recordNo, reason);
    }

    private ChatCompletionRequest buildAiRequest(AiGenerationRequest request) {
        return ChatCompletionRequest.builder()
                .model(request.getModel())
                .messages(request.getMessages())
                .temperature(request.getTemperature())
                .maxTokens(request.getMaxTokens())
                .stream(false)
                .build();
    }

    @lombok.Data
    @lombok.Builder
    public static class AiGenerationRequest {
        private Long userId;
        private String contentType;
        private Long channelId;
        private String model;
        private java.util.List<ChatCompletionRequest.Message> messages;
        private Double temperature;
        private Integer maxTokens;
        private Integer inputTokens;
        private Integer outputTokens;
    }

    @lombok.Data
    public static class AiGenerationResult {
        private Boolean success;
        private String content;
        private String message;
        private String errorCode;
        private ChargeResult chargeResult;
        private Integer totalTokens;
        private Boolean streamFinished;

        public static AiGenerationResult success(String content, ChargeResult chargeResult, Integer totalTokens) {
            AiGenerationResult result = new AiGenerationResult();
            result.setSuccess(true);
            result.setContent(content);
            result.setChargeResult(chargeResult);
            result.setTotalTokens(totalTokens);
            return result;
        }

        public static AiGenerationResult insufficient(String message) {
            AiGenerationResult result = new AiGenerationResult();
            result.setSuccess(false);
            result.setErrorCode("INSUFFICIENT_BALANCE");
            result.setMessage(message);
            return result;
        }

        public static AiGenerationResult error(String message) {
            AiGenerationResult result = new AiGenerationResult();
            result.setSuccess(false);
            result.setErrorCode("GENERATION_ERROR");
            result.setMessage(message);
            return result;
        }

        public static AiGenerationResult streaming(String content, Boolean finished, ChargeResult chargeResult) {
            AiGenerationResult result = new AiGenerationResult();
            result.setSuccess(true);
            result.setContent(content);
            result.setStreamFinished(finished);
            result.setChargeResult(chargeResult);
            return result;
        }
    }
}
