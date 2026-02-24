<?php
/**
 * AI配置服务类
 * 处理AI配置相关的业务逻辑
 *
 * @package Core\Services
 */

namespace Core\Services;

use Core\Database;

class AIConfigService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAIConfigs(): array
    {
        $sql = "SELECT * FROM sn_ai_configs ORDER BY category, sort_order ASC";
        $result = $this->db->query($sql);

        $configs = [];
        foreach ($result as $config) {
            $configs[$config['category']][$config['key']] = $config;
        }

        return $configs;
    }

    public function getAIConfig(string $key): ?array
    {
        $sql = "SELECT * FROM sn_ai_configs WHERE `key` = :config_key";
        $result = $this->db->query($sql, [':config_key' => $key]);

        return $result[0] ?? null;
    }

    public function updateAIConfig(string $key, string $value): array
    {
        $sql = "UPDATE sn_ai_configs 
                SET value = :value, 
                    updated_at = NOW() 
                WHERE `key` = :config_key";

        $params = [
            ':value' => $value,
            ':config_key' => $key
        ];

        $result = $this->db->query($sql, $params);

        if ($result) {
            return ['success' => true, 'message' => 'AI配置更新成功'];
        }

        return ['success' => false, 'message' => 'AI配置更新失败'];
    }

    public function updateAIConfigs(array $configs): array
    {
        $this->db->beginTransaction();

        try {
            foreach ($configs as $key => $value) {
                $sql = "UPDATE sn_ai_configs 
                        SET value = :value, 
                            updated_at = NOW() 
                        WHERE `key` = :config_key";

                $params = [
                    ':value' => $value,
                    ':config_key' => $key
                ];

                $this->db->query($sql, $params);
            }

            $this->db->commit();
            return ['success' => true, 'message' => 'AI配置批量更新成功'];
        } catch (\Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'AI配置批量更新失败: ' . $e->getMessage()];
        }
    }

    public function getOpenAIConfigs(): array
    {
        $sql = "SELECT * FROM sn_ai_configs WHERE category = 'openai' ORDER BY sort_order ASC";
        return $this->db->query($sql);
    }

    public function getAnthropicConfigs(): array
    {
        $sql = "SELECT * FROM sn_ai_configs WHERE category = 'anthropic' ORDER BY sort_order ASC";
        return $this->db->query($sql);
    }

    public function getStabilityConfigs(): array
    {
        $sql = "SELECT * FROM sn_ai_configs WHERE category = 'stability' ORDER BY sort_order ASC";
        return $this->db->query($sql);
    }

    public function getMidjourneyConfigs(): array
    {
        $sql = "SELECT * FROM sn_ai_configs WHERE category = 'midjourney' ORDER BY sort_order ASC";
        return $this->db->query($sql);
    }

    public function getMusicAIConfigs(): array
    {
        $sql = "SELECT * FROM sn_ai_configs WHERE category = 'music' ORDER BY sort_order ASC";
        return $this->db->query($sql);
    }

    public function testAIConnection(string $provider, array $config): array
    {
        switch ($provider) {
            case 'openai':
                return $this->testOpenAIConnection($config);
            case 'anthropic':
                return $this->testAnthropicConnection($config);
            case 'stability':
                return $this->testStabilityConnection($config);
            case 'midjourney':
                return $this->testMidjourneyConnection($config);
            case 'music':
                return $this->testMusicAIConnection($config);
            default:
                return ['success' => false, 'message' => '不支持的AI服务商'];
        }
    }

    private function testOpenAIConnection(array $config): array
    {
        $apiKey = $config['api_key'] ?? '';
        $endpoint = $config['endpoint'] ?? 'https://api.openai.com/v1/chat/completions';

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'model' => 'gpt-3.5-turbo',
                'messages' => [['role' => 'user', 'content' => 'Hello']],
                'max_tokens' => 10
            ]));
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                return ['success' => false, 'message' => 'OpenAI连接测试失败: ' . $error];
            }

            if ($httpCode === 200 || $httpCode === 401) {
                return ['success' => true, 'message' => 'OpenAI连接测试成功'];
            } else {
                return ['success' => false, 'message' => 'OpenAI连接测试失败: HTTP ' . $httpCode];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'OpenAI连接测试失败: ' . $e->getMessage()];
        }
    }

    private function testAnthropicConnection(array $config): array
    {
        $apiKey = $config['api_key'] ?? '';

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.anthropic.com/v1/messages');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01'
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'model' => 'claude-3-sonnet-20240229',
                'max_tokens' => 10,
                'messages' => [['role' => 'user', 'content' => 'Hello']]
            ]));
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 || $httpCode === 401) {
                return ['success' => true, 'message' => 'Anthropic连接测试成功'];
            } else {
                return ['success' => false, 'message' => 'Anthropic连接测试失败: HTTP ' . $httpCode];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Anthropic连接测试失败: ' . $e->getMessage()];
        }
    }

    private function testStabilityConnection(array $config): array
    {
        $apiKey = $config['api_key'] ?? '';

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.stability.ai/v1/user/account');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $apiKey
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 || $httpCode === 401) {
                return ['success' => true, 'message' => 'Stability AI连接测试成功'];
            } else {
                return ['success' => false, 'message' => 'Stability AI连接测试失败: HTTP ' . $httpCode];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Stability AI连接测试失败: ' . $e->getMessage()];
        }
    }

    private function testMidjourneyConnection(array $config): array
    {
        $apiKey = $config['api_key'] ?? '';

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.midjourney.com/v1/account');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $apiKey
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 || $httpCode === 401) {
                return ['success' => true, 'message' => 'Midjourney连接测试成功'];
            } else {
                return ['success' => false, 'message' => 'Midjourney连接测试失败: HTTP ' . $httpCode];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Midjourney连接测试失败: ' . $e->getMessage()];
        }
    }

    private function testMusicAIConnection(array $config): array
    {
        $apiKey = $config['api_key'] ?? '';

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.music-ai.com/v1/account');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $apiKey
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                return ['success' => false, 'message' => '音乐AI连接测试失败: ' . $error];
            }

            if ($httpCode === 200 || $httpCode === 401) {
                return ['success' => true, 'message' => '音乐AI连接测试成功'];
            } else {
                return ['success' => false, 'message' => '音乐AI连接测试失败: HTTP ' . $httpCode];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => '音乐AI连接测试失败: ' . $e->getMessage()];
        }
    }

    public function getAIUsageStats(): array
    {
        $sql = "SELECT 
                    SUM(CASE WHEN provider = 'openai' THEN tokens_used ELSE 0 END) as openai_tokens,
                    SUM(CASE WHEN provider = 'anthropic' THEN tokens_used ELSE 0 END) as anthropic_tokens,
                    SUM(CASE WHEN provider = 'stability' THEN tokens_used ELSE 0 END) as stability_tokens,
                    SUM(CASE WHEN provider = 'midjourney' THEN tokens_used ELSE 0 END) as midjourney_tokens,
                    SUM(CASE WHEN provider = 'music' THEN tokens_used ELSE 0 END) as music_tokens,
                    SUM(CASE WHEN provider = 'openai' THEN cost ELSE 0 END) as openai_cost,
                    SUM(CASE WHEN provider = 'anthropic' THEN cost ELSE 0 END) as anthropic_cost,
                    SUM(CASE WHEN provider = 'stability' THEN cost ELSE 0 END) as stability_cost,
                    SUM(CASE WHEN provider = 'midjourney' THEN cost ELSE 0 END) as midjourney_cost,
                    SUM(CASE WHEN provider = 'music' THEN cost ELSE 0 END) as music_cost
                FROM sn_ai_usage_logs
                WHERE DATE(created_at) >= DATE_SUB(NOW(), INTERVAL 30 DAY)";

        $result = $this->db->query($sql);

        return $result[0] ?? [
            'openai_tokens' => 0,
            'anthropic_tokens' => 0,
            'stability_tokens' => 0,
            'midjourney_tokens' => 0,
            'music_tokens' => 0,
            'openai_cost' => 0,
            'anthropic_cost' => 0,
            'stability_cost' => 0,
            'midjourney_cost' => 0,
            'music_cost' => 0
        ];
    }
}
