<?php

namespace Plugins\Apps\MyApp\Services;

use Core\Services\ChannelService;
use Core\Services\KnowledgeBaseService;
use Plugins\Apps\MyApp\Plugin;

class WritingService
{
    private $plugin;
    private $knowledgeBaseService;

    public function __construct()
    {
        $this->plugin = new Plugin('my_app');
        $this->knowledgeBaseService = new KnowledgeBaseService();
    }

    public function rewriteChapter(int $userId, string $originalContent, string $model, string $instructions): array
    {
        try {
            // 1. 从知识库检索相关上下文 (RAG)
            $relevantContext = $this->knowledgeBaseService->intelligentRetrieve($originalContent, [], 3);
            
            $contextString = '';
            if (!empty($relevantContext)) {
                $contextString .= "为了确保内容一致性，请参考以下背景设定：
";
                foreach ($relevantContext as $item) {
                    $contextString .= "- " . $item['title'] . ": " . $item['summary'] . "
";
                }
            }

            // 2. 构建最终的Prompt
            $systemPrompt = "你是一位资深的网文编辑器，你的任务是根据用户的要求，对提供的章节内容进行重写或润色。请严格遵守参考的背景设定，保持人物性格和世界观的一致性。";
            $userPrompt = "## 用户指令：
{$instructions}

## 参考背景设定：
{$contextString}

## 原始章节内容：
{$originalContent}

请根据以上所有信息，输出重写后的章节内容。";

            // 3. 准备API请求
            $requestPayload = [
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt]
                ],
                'temperature' => 0.7,
                'max_tokens' => 4000,
            ];

            // 4. 调用渠道服务执行请求
            $result = ChannelService::executeRequest($userId, $model, $requestPayload);

            if (!$result['success']) {
                throw new \Exception($result['error'] ?? '服务调用失败');
            }

            // 5. 返回成功结果
            return [
                'success' => true,
                'content' => $result['data']['choices'][0]['message']['content'] ?? '',
                'cost' => $result['cost']
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
