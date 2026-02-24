<?php

namespace app\services;

use app\models\AnimeStoryboard;
use app\services\StandardExceptionHandler;

/**
 * 分镜自动生成服务
 * 根据剧本自动生成分镜
 */
class StoryboardGeneratorService
{
    /**
     * 从剧本生成分镜
     *
     * @param string $script 剧本内容
     * @param int $projectId 项目ID
     * @param int|null $episodeScriptId 剧集脚本ID（可选）
     * @param int $sceneNumber 场景编号（从1开始）
     * @return array
     */
    public function generateFromScript(string $script, int $projectId, ?int $episodeScriptId = null, int $sceneNumber = 1): array
    {
        try {
            if (empty($script)) {
                throw new \InvalidArgumentException('剧本内容不能为空');
            }
            
            // 解析剧本
            $scriptParts = $this->parseScript($script);
            
            // 生成分镜
            $storyboards = [];
            $shotNumber = 1;
            
            foreach ($scriptParts as $index => $part) {
                $storyboard = $this->generateStoryboardFrame($part, $shotNumber, $sceneNumber);
                
                // 映射到AnimeStoryboard的字段结构
                $storyboardData = [
                    'project_id' => $projectId,
                    'episode_script_id' => $episodeScriptId,
                    'scene_number' => $sceneNumber,
                    'shot_number' => $shotNumber,
                    'shot_type' => $this->mapShotType($storyboard['camera_movement']),
                    'camera_angle' => $this->mapCameraAngle($storyboard['camera_movement']),
                    'camera_movement' => $this->mapCameraMovement($storyboard['camera_movement']),
                    'duration' => $storyboard['timing']['duration'] ?? 0.0,
                    'description' => $storyboard['description'],
                    'action_description' => $part['type'] === 'action' ? $part['text'] : null,
                    'dialogue' => $part['type'] === 'character_dialogue' ? $part['text'] : null,
                    'storyboard_image_url' => $storyboard['image_url'],
                    'status' => 'draft',
                    'sort_order' => $shotNumber,
                ];
                
                // 保存分镜
                $storyboardId = AnimeStoryboard::create($storyboardData);
                
                if ($storyboardId) {
                    $storyboards[] = [
                        'id' => $storyboardId,
                        'shot_number' => $shotNumber,
                        'description' => $storyboard['description'],
                        'camera_movement' => $storyboard['camera_movement'],
                        'timing' => $storyboard['timing'],
                        'image_url' => $storyboard['image_url'],
                    ];
                    $shotNumber++;
                }
            }
            
            return [
                'success' => true,
                'storyboards' => $storyboards,
                'total_frames' => count($storyboards),
            ];
        } catch (\Exception $e) {
            return StandardExceptionHandler::handle($e, '分镜生成');
        }
    }
    
    /**
     * 解析剧本
     *
     * @param string $script
     * @return array
     */
    private function parseScript(string $script): array
    {
        // 按段落分割剧本
        $paragraphs = preg_split('/\n\s*\n/', trim($script));
        
        $parts = [];
        foreach ($paragraphs as $paragraph) {
            if (trim($paragraph)) {
                $parts[] = [
                    'text' => trim($paragraph),
                    'type' => $this->detectScriptType($paragraph),
                ];
            }
        }
        
        return $parts;
    }
    
    /**
     * 检测剧本类型
     *
     * @param string $text
     * @return string
     */
    private function detectScriptType(string $text): string
    {
        if (preg_match('/^(INT\.|EXT\.)/i', $text)) {
            return 'scene_header';
        }
        if (preg_match('/^[A-Z\s]+$/m', $text)) {
            return 'character_dialogue';
        }
        return 'action';
    }
    
    /**
     * 生成分镜帧
     *
     * @param array $part
     * @param int $shotNumber
     * @param int $sceneNumber
     * @return array
     */
    private function generateStoryboardFrame(array $part, int $shotNumber, int $sceneNumber): array
    {
        $cameraMovement = $this->suggestCameraMovement($part);
        $timing = $this->estimateTiming($part);
        
        return [
            'description' => $this->generateDescription($part),
            'image_url' => $this->generateStoryboardImage($part, $shotNumber, $sceneNumber),
            'camera_movement' => $cameraMovement,
            'timing' => $timing,
        ];
    }
    
    /**
     * 生成描述
     *
     * @param array $part
     * @return string
     */
    private function generateDescription(array $part): string
    {
        $text = $part['text'];
        $type = $part['type'];
        
        if ($type === 'scene_header') {
            return "场景：" . trim($text);
        }
        
        if ($type === 'character_dialogue') {
            return "对话：" . substr($text, 0, 50) . '...';
        }
        
        return "动作：" . substr($text, 0, 100) . '...';
    }
    
    /**
     * 建议摄像机运动
     *
     * @param array $part
     * @return array
     */
    private function suggestCameraMovement(array $part): array
    {
        $text = strtolower($part['text']);
        
        $movements = [];
        
        if (strpos($text, 'close') !== false || strpos($text, '特写') !== false) {
            $movements[] = 'close_up';
        }
        if (strpos($text, 'wide') !== false || strpos($text, '全景') !== false) {
            $movements[] = 'wide_shot';
        }
        if (strpos($text, 'pan') !== false || strpos($text, '摇') !== false) {
            $movements[] = 'pan';
        }
        if (strpos($text, 'zoom') !== false || strpos($text, '推') !== false) {
            $movements[] = 'zoom';
        }
        
        return $movements ?: ['medium_shot'];
    }
    
    /**
     * 估算时长
     *
     * @param array $part
     * @return array
     */
    private function estimateTiming(array $part): array
    {
        $text = $part['text'];
        $wordCount = str_word_count($text);
        
        // 对话：平均每分钟150词
        // 动作：平均每10词1秒
        if ($part['type'] === 'character_dialogue') {
            $duration = ($wordCount / 150) * 60; // 转换为秒
        } else {
            $duration = $wordCount / 10;
        }
        
        return [
            'start' => 0, // 需要根据前一个分镜计算
            'duration' => max(1, round($duration, 2)),
        ];
    }
    
    /**
     * 生成分镜图
     *
     * @param array $part
     * @param int $shotNumber
     * @param int $sceneNumber
     * @return string|null
     */
    private function generateStoryboardImage(array $part, int $shotNumber, int $sceneNumber): ?string
    {
        try {
            // 构建分镜图提示词
            $prompt = $this->buildStoryboardPrompt($part, $shotNumber, $sceneNumber);
            
            // 调用AI图像生成服务
            $imageUrl = $this->callAIImageGeneration($prompt);
            
            if ($imageUrl) {
                return $imageUrl;
            }
            
            // 如果AI生成失败，返回null（前端可以显示占位图）
            return null;
        } catch (\Exception $e) {
            error_log("生成分镜图失败: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 构建分镜图提示词
     *
     * @param array $part
     * @param int $shotNumber
     * @param int $sceneNumber
     * @return string
     */
    private function buildStoryboardPrompt(array $part, int $shotNumber, int $sceneNumber): string
    {
        $text = $part['text'];
        $type = $part['type'];
        $cameraMovement = $this->suggestCameraMovement($part);
        
        $prompt = "Storyboard frame for scene {$sceneNumber}, shot {$shotNumber}. ";
        
        if ($type === 'scene_header') {
            $prompt .= "Scene setting: " . trim($text) . ". ";
        } elseif ($type === 'character_dialogue') {
            $prompt .= "Character dialogue scene: " . substr($text, 0, 100) . ". ";
        } else {
            $prompt .= "Action scene: " . substr($text, 0, 150) . ". ";
        }
        
        // 添加摄像机运动描述
        if (!empty($cameraMovement)) {
            $movementDesc = implode(', ', $cameraMovement);
            $prompt .= "Camera: {$movementDesc}. ";
        }
        
        $prompt .= "Style: anime storyboard sketch, black and white, simple line art, clear composition, professional storyboard style.";
        
        return $prompt;
    }
    
    /**
     * 调用AI图像生成服务
     *
     * @param string $prompt
     * @return string|null 生成的图片URL
     */
    private function callAIImageGeneration(string $prompt): ?string
    {
        try {
            // 获取AI通道配置（优先使用图像生成通道）
            $channels = \app\models\AIChannel::all();
            $channels = array_filter($channels, function($ch) {
                return ($ch['status'] ?? '') === 'enabled';
            });
            
            if (empty($channels)) {
                return null;
            }
            
            $channel = $channels[0];
            $baseUrl = $channel['base_url'] ?? '';
            $apiKey = $channel['api_key'] ?? '';
            
            if (empty($baseUrl) || empty($apiKey)) {
                return null;
            }
            
            // 尝试使用OpenAI DALL-E API
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, rtrim($baseUrl, '/') . '/v1/images/generations');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'model' => 'dall-e-3',
                'prompt' => $prompt,
                'n' => 1,
                'size' => '1024x1024',
                'quality' => 'standard',
            ]));
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error || $httpCode !== 200) {
                error_log("AI图像生成API调用失败: HTTP {$httpCode}, Error: {$error}");
                return null;
            }
            
            $data = json_decode($response, true);
            if (!$data || !isset($data['data'][0]['url'])) {
                return null;
            }
            
            // 下载图片并保存到本地
            $imageUrl = $data['data'][0]['url'];
            $localPath = $this->downloadAndSaveImage($imageUrl);
            
            return $localPath ?: $imageUrl;
            
        } catch (\Exception $e) {
            error_log("AI图像生成异常: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 下载并保存图片到本地
     *
     * @param string $imageUrl
     * @return string|null 本地文件路径
     */
    private function downloadAndSaveImage(string $imageUrl): ?string
    {
        try {
            $storageDir = __DIR__ . '/../../storage/app/storyboards';
            if (!is_dir($storageDir)) {
                mkdir($storageDir, 0755, true);
            }
            
            $filename = 'storyboard_' . time() . '_' . uniqid() . '.png';
            $localPath = $storageDir . '/' . $filename;
            
            // 下载图片
            $imageData = file_get_contents($imageUrl);
            if ($imageData === false) {
                return null;
            }
            
            // 保存到本地
            if (file_put_contents($localPath, $imageData) === false) {
                return null;
            }
            
            // 返回相对于public目录的路径
            return '/storage/app/storyboards/' . $filename;
            
        } catch (\Exception $e) {
            error_log("下载图片失败: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 映射镜头类型
     *
     * @param array $cameraMovement
     * @return string
     */
    private function mapShotType(array $cameraMovement): string
    {
        if (in_array('close_up', $cameraMovement)) {
            return 'close_up';
        }
        if (in_array('wide_shot', $cameraMovement)) {
            return 'wide';
        }
        return 'medium';
    }
    
    /**
     * 映射摄像机角度
     *
     * @param array $cameraMovement
     * @return string
     */
    private function mapCameraAngle(array $cameraMovement): string
    {
        // 根据摄像机运动推断角度
        if (in_array('zoom', $cameraMovement)) {
            return 'low_angle';
        }
        return 'eye_level';
    }
    
    /**
     * 映射摄像机运动
     *
     * @param array $cameraMovement
     * @return string
     */
    private function mapCameraMovement(array $cameraMovement): string
    {
        if (in_array('pan', $cameraMovement)) {
            return 'pan';
        }
        if (in_array('zoom', $cameraMovement)) {
            return 'zoom';
        }
        return 'static';
    }
}
