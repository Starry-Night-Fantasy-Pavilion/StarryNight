import {
    MLCEngine,
    CreateMLCEngine,
} from "@mlc-ai/web-llm";
import type {
    ChatCompletionRequest,
    ChatCompletion,
    ChatCompletionChunk,
    InitProgressReport,
} from "@mlc-ai/web-llm";

// 定义模型配置
const SELECTED_MODEL = "Llama-3-8B-Instruct-q4f16_1-MLC";

// 定义回调函数类型
type ProgressCallback = (progress: string, text: string) => void;

class WebLLMService {
    private engine: MLCEngine | undefined;
    private progressCallback: ProgressCallback | undefined;

    constructor() {
        this.initialize();
    }

    private async initialize() {
        // CreateMLCEngine 会自动处理 Web Worker
        this.engine = await CreateMLCEngine(
            SELECTED_MODEL,
            {
                initProgressCallback: (progress: InitProgressReport) => {
                    this.handleProgress(progress);
                }
            }
        );
        console.log("WebLLM Engine initialized.");
    }

    private handleProgress(progress: InitProgressReport) {
        const progressReport = `[${(progress.progress * 100).toFixed(2)}%] ${progress.text}`;
        console.log(progressReport);
        if (this.progressCallback) {
            this.progressCallback(progressReport, progress.text);
        }
    }

    /**
     * 注册一个回调函数来接收加载进度更新
     * @param callback - 进度回调函数
     */
    public onProgress(callback: ProgressCallback) {
        this.progressCallback = callback;
    }

    /**
     * 检查引擎是否已准备好
     */
    public isReady(): boolean {
        return this.engine !== undefined;
    }


    /**
     * 生成聊天回复 (流式)
     * @param request - 聊天请求
     * @returns 异步迭代器，用于逐块返回聊天回复
     */
    public async * chatCompletionStream(
        request: ChatCompletionRequest
    ): AsyncGenerator<ChatCompletionChunk, void, unknown> {
        if (!this.engine) {
            throw new Error("WebLLM engine is not initialized.");
        }

        const streamRequest = { ...request, stream: true };

        const stream = await this.engine.chat.completions.create(streamRequest) as AsyncIterable<ChatCompletionChunk>;
        for await (const chunk of stream) {
            yield chunk;
        }
    }

    /**
     * 生成聊天回复 (非流式)
     * @param request - 聊天请求
     * @returns 完整的聊天回复
     */
    public async chatCompletion(
        request: ChatCompletionRequest
    ): Promise<ChatCompletion> {
        if (!this.engine) {
            throw new Error("WebLLM engine is not initialized.");
        }

        const nonStreamRequest = { ...request, stream: false };

        return await this.engine.chat.completions.create(nonStreamRequest) as ChatCompletion;
    }

    /**
     * 获取引擎统计信息
     */
    public async getStats() {
        if (!this.engine) {
            throw new Error("WebLLM engine is not initialized.");
        }
        return await this.engine.runtimeStatsText();
    }

    /**
     * 重置聊天会话
     */
    public async reset() {
        if (this.engine) {
            await this.engine.resetChat();
        }
    }
}

export const webLLMService = new WebLLMService();
