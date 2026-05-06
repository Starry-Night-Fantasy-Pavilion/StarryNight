package com.starrynight.starrynight.services.ai;

import com.starrynight.starrynight.services.ai.model.ChatCompletionRequest;
import com.starrynight.starrynight.services.ai.model.ChatCompletionResponse;
import reactor.core.publisher.Mono;

/**
 * AI 模型服务客户端接口
 */
public interface AiApiClient {

    /**
     * 发起聊天补全请求
     *
     * @param request 请求参数
     * @return 响应结果
     */
    Mono<ChatCompletionResponse> chatCompletion(ChatCompletionRequest request);

}