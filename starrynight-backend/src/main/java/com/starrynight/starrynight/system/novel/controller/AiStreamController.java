package com.starrynight.starrynight.system.novel.controller;

import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.services.ai.AiBillingService;
import com.starrynight.starrynight.services.ai.model.ChatCompletionRequest;
import com.starrynight.starrynight.services.ai.model.ChatCompletionResponse;
import com.starrynight.starrynight.services.engine.NovelVectorMemoryService;
import com.starrynight.starrynight.system.billing.dto.EstimateResult;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.http.MediaType;
import org.springframework.web.bind.annotation.*;
import reactor.core.publisher.Flux;

import java.util.ArrayList;
import java.util.List;

@Slf4j
@RestController
@RequestMapping("/api/ai")
@RequiredArgsConstructor
public class AiStreamController {

    private final AiBillingService aiBillingService;
    private final NovelVectorMemoryService novelVectorMemoryService;

    @PostMapping(value = "/generate/stream", produces = MediaType.TEXT_EVENT_STREAM_VALUE)
    public Flux<String> generateStream(@RequestBody AiStreamRequest request) {
        List<ChatCompletionRequest.Message> messages = augmentMessagesWithNovelMemory(request);

        AiBillingService.AiGenerationRequest genRequest = AiBillingService.AiGenerationRequest.builder()
                .userId(request.getUserId())
                .contentType(request.getContentType())
                .channelId(request.getChannelId())
                .model(request.getModel())
                .messages(messages)
                .temperature(request.getTemperature())
                .maxTokens(request.getMaxTokens())
                .inputTokens(request.getInputTokens())
                .outputTokens(request.getOutputTokens())
                .build();

        return aiBillingService.generateStream(genRequest)
                .map(response -> {
                    if (response.getChoices() != null && !response.getChoices().isEmpty()) {
                        ChatCompletionResponse.Choice choice = response.getChoices().get(0);
                        String content = choice.getMessage() != null ? choice.getMessage().getContent() : "";
                        String finishReason = choice.getFinishReason();

                        String sseData = String.format("data: {\"content\":\"%s\",\"finish\":\"%s\"}\n\n",
                                escapeJson(content), finishReason != null ? finishReason : "");
                        return sseData;
                    }
                    return "";
                })
                .doOnComplete(() -> log.info("Stream completed for user {}", request.getUserId()))
                .doOnError(e -> log.error("Stream error for user {}: {}", request.getUserId(), e.getMessage()));
    }

    private List<ChatCompletionRequest.Message> augmentMessagesWithNovelMemory(AiStreamRequest request) {
        List<ChatCompletionRequest.Message> original = request.getMessages();
        if (original == null || request.getNovelId() == null) {
            return original;
        }
        String hint = request.getMemoryQueryHint();
        if (hint == null || hint.isBlank()) {
            hint = "对话创作";
        }
        String block = novelVectorMemoryService.buildRecallConstraintBlock(
                request.getNovelId(),
                hint,
                null,
                null);
        if (block.isBlank()) {
            return original;
        }
        List<ChatCompletionRequest.Message> out = new ArrayList<>(original.size() + 1);
        out.add(new ChatCompletionRequest.Message("system", block));
        out.addAll(original);
        return out;
    }

    @GetMapping("/estimate")
    public ResponseVO<EstimateResult> estimateCost(
            @RequestParam Long userId,
            @RequestParam String contentType,
            @RequestParam(defaultValue = "500") Integer inputTokens,
            @RequestParam(defaultValue = "1000") Integer outputTokens) {
        EstimateResult result = aiBillingService.estimate(contentType, userId, inputTokens, outputTokens);
        return ResponseVO.success(result);
    }

    private String escapeJson(String text) {
        if (text == null) return "";
        return text.replace("\\", "\\\\")
                .replace("\"", "\\\"")
                .replace("\n", "\\n")
                .replace("\r", "\\r")
                .replace("\t", "\\t");
    }

    @lombok.Data
    public static class AiStreamRequest {
        private Long userId;
        private String contentType;
        private Long channelId;
        private String model;
        private List<ChatCompletionRequest.Message> messages;
        private Double temperature;
        private Integer maxTokens;
        private Integer inputTokens;
        private Integer outputTokens;

        /** 可选：注入该作品的向量记忆（与车间一致，按 novelId 隔离） */
        private Long novelId;

        /** 可选：用于向量召回的核心提示语（缺省为「对话创作」） */
        private String memoryQueryHint;
    }
}
