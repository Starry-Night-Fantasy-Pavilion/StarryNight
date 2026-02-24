<?php

namespace app\services;

use app\models\AnimeAnimation;
use app\services\StandardExceptionHandler;

/**
 * 角色动画引擎服务
 * 提供角色动画生成、关键帧编辑等功能
 */
class CharacterAnimationEngineService
{
    /**
     * 生成角色动画
     *
     * @param array $parameters
     *   - project_id: 项目ID
     *   - character_id: 角色ID
     *   - animation_type: 动画类型（walk, run, jump, idle等）
     *   - duration: 动画时长（秒）
     *   - frame_rate: 帧率（默认24）
     * @return array
     */
    public function animateCharacter(array $parameters): array
    {
        try {
            if (empty($parameters['project_id']) || empty($parameters['character_id'])) {
                throw new \InvalidArgumentException('项目ID和角色ID不能为空');
            }
            
            $animationType = $parameters['animation_type'] ?? 'idle';
            $duration = (float)($parameters['duration'] ?? 5.0);
            $frameRate = (int)($parameters['frame_rate'] ?? 24);
            
            // 生成动画关键帧
            $keyFrames = $this->generateKeyFrames($animationType, $duration, $frameRate);
            
            // 生成动画数据
            $animationData = $this->generateAnimationData($keyFrames, $animationType);
            
            // 保存动画记录
            $animationId = AnimeAnimation::create([
                'project_id' => $parameters['project_id'],
                'character_id' => $parameters['character_id'],
                'animation_type' => $animationType,
                'animation_name' => ucfirst($animationType) . ' Animation',
                'duration' => $duration,
                'frame_rate' => $frameRate,
                'key_frames' => json_encode($keyFrames),
                'animation_data' => json_encode($animationData),
                'status' => 'completed',
            ]);
            
            return [
                'success' => true,
                'animation_id' => $animationId,
                'key_frames' => $keyFrames,
                'animation_data' => $animationData,
            ];
        } catch (\Exception $e) {
            return StandardExceptionHandler::handle($e, '角色动画生成');
        }
    }
    
    /**
     * 生成关键帧
     *
     * @param string $type
     * @param float $duration
     * @param int $frameRate
     * @return array
     */
    private function generateKeyFrames(string $type, float $duration, int $frameRate): array
    {
        $totalFrames = (int)($duration * $frameRate);
        $keyFrames = [];
        
        // 根据动画类型生成关键帧
        switch (strtolower($type)) {
            case 'walk':
                $keyFrames = $this->generateWalkKeyFrames($totalFrames);
                break;
            case 'run':
                $keyFrames = $this->generateRunKeyFrames($totalFrames);
                break;
            case 'jump':
                $keyFrames = $this->generateJumpKeyFrames($totalFrames);
                break;
            default:
                $keyFrames = $this->generateIdleKeyFrames($totalFrames);
        }
        
        return $keyFrames;
    }
    
    /**
     * 生成行走动画关键帧
     *
     * @param int $totalFrames
     * @return array
     */
    private function generateWalkKeyFrames(int $totalFrames): array
    {
        $keyFrames = [];
        $cycleFrames = 12; // 一个行走周期12帧
        
        for ($i = 0; $i < $totalFrames; $i++) {
            $cyclePosition = $i % $cycleFrames;
            $progress = $cyclePosition / $cycleFrames;
            
            $keyFrames[] = [
                'frame' => $i,
                'time' => $i / 24.0,
                'position' => [
                    'x' => $i * 0.5, // 每帧移动0.5单位
                    'y' => 0,
                    'z' => 0,
                ],
                'rotation' => [
                    'x' => 0,
                    'y' => 0,
                    'z' => sin($progress * 2 * M_PI) * 5, // 身体摆动
                ],
                'scale' => ['x' => 1, 'y' => 1, 'z' => 1],
                'bone_positions' => $this->calculateBonePositions('walk', $progress),
            ];
        }
        
        return $keyFrames;
    }
    
    /**
     * 生成跑步动画关键帧
     *
     * @param int $totalFrames
     * @return array
     */
    private function generateRunKeyFrames(int $totalFrames): array
    {
        $keyFrames = [];
        $cycleFrames = 8; // 跑步周期更短
        
        for ($i = 0; $i < $totalFrames; $i++) {
            $cyclePosition = $i % $cycleFrames;
            $progress = $cyclePosition / $cycleFrames;
            
            $keyFrames[] = [
                'frame' => $i,
                'time' => $i / 24.0,
                'position' => [
                    'x' => $i * 1.0, // 每帧移动1单位（更快）
                    'y' => abs(sin($progress * 2 * M_PI)) * 0.3, // 上下跳动
                    'z' => 0,
                ],
                'rotation' => [
                    'x' => sin($progress * 2 * M_PI) * 10,
                    'y' => 0,
                    'z' => sin($progress * 2 * M_PI) * 8,
                ],
                'scale' => ['x' => 1, 'y' => 1, 'z' => 1],
                'bone_positions' => $this->calculateBonePositions('run', $progress),
            ];
        }
        
        return $keyFrames;
    }
    
    /**
     * 生成跳跃动画关键帧
     *
     * @param int $totalFrames
     * @return array
     */
    private function generateJumpKeyFrames(int $totalFrames): array
    {
        $keyFrames = [];
        $jumpDuration = 24; // 跳跃持续24帧（1秒）
        
        for ($i = 0; $i < $totalFrames; $i++) {
            $progress = min(1.0, $i / $jumpDuration);
            
            // 抛物线运动
            $height = 4 * $progress * (1 - $progress);
            
            $keyFrames[] = [
                'frame' => $i,
                'time' => $i / 24.0,
                'position' => [
                    'x' => $i * 0.3,
                    'y' => $height,
                    'z' => 0,
                ],
                'rotation' => [
                    'x' => $progress < 0.5 ? $progress * 20 : (1 - $progress) * 20,
                    'y' => 0,
                    'z' => 0,
                ],
                'scale' => ['x' => 1, 'y' => 1, 'z' => 1],
                'bone_positions' => $this->calculateBonePositions('jump', $progress),
            ];
        }
        
        return $keyFrames;
    }
    
    /**
     * 生成待机动画关键帧
     *
     * @param int $totalFrames
     * @return array
     */
    private function generateIdleKeyFrames(int $totalFrames): array
    {
        $keyFrames = [];
        $cycleFrames = 60; // 待机动画周期较长
        
        for ($i = 0; $i < $totalFrames; $i++) {
            $progress = ($i % $cycleFrames) / $cycleFrames;
            
            $keyFrames[] = [
                'frame' => $i,
                'time' => $i / 24.0,
                'position' => [
                    'x' => 0,
                    'y' => sin($progress * 2 * M_PI) * 0.1, // 轻微上下浮动
                    'z' => 0,
                ],
                'rotation' => [
                    'x' => 0,
                    'y' => sin($progress * 2 * M_PI) * 2, // 轻微左右摆动
                    'z' => 0,
                ],
                'scale' => ['x' => 1, 'y' => 1, 'z' => 1],
                'bone_positions' => $this->calculateBonePositions('idle', $progress),
            ];
        }
        
        return $keyFrames;
    }
    
    /**
     * 计算骨骼位置
     *
     * @param string $type
     * @param float $progress
     * @return array
     */
    private function calculateBonePositions(string $type, float $progress): array
    {
        // 根据动画类型和进度计算骨骼位置
        $basePositions = [
            'spine' => ['x' => 0, 'y' => 1, 'z' => 0],
            'left_arm' => ['x' => -0.5, 'y' => 1.2, 'z' => 0],
            'right_arm' => ['x' => 0.5, 'y' => 1.2, 'z' => 0],
            'left_leg' => ['x' => -0.2, 'y' => 0.5, 'z' => 0],
            'right_leg' => ['x' => 0.2, 'y' => 0.5, 'z' => 0],
        ];
        
        switch (strtolower($type)) {
            case 'walk':
                return $this->calculateWalkBonePositions($basePositions, $progress);
            case 'run':
                return $this->calculateRunBonePositions($basePositions, $progress);
            case 'jump':
                return $this->calculateJumpBonePositions($basePositions, $progress);
            default:
                return $this->calculateIdleBonePositions($basePositions, $progress);
        }
    }
    
    /**
     * 计算行走时的骨骼位置
     */
    private function calculateWalkBonePositions(array $base, float $progress): array
    {
        $swing = sin($progress * 2 * M_PI);
        return [
            'spine' => ['x' => $base['spine']['x'], 'y' => $base['spine']['y'], 'z' => $base['spine']['z'] + $swing * 0.1],
            'left_arm' => ['x' => $base['left_arm']['x'], 'y' => $base['left_arm']['y'], 'z' => $base['left_arm']['z'] - $swing * 0.3],
            'right_arm' => ['x' => $base['right_arm']['x'], 'y' => $base['right_arm']['y'], 'z' => $base['right_arm']['z'] + $swing * 0.3],
            'left_leg' => ['x' => $base['left_leg']['x'], 'y' => $base['left_leg']['y'], 'z' => $base['left_leg']['z'] + $swing * 0.4],
            'right_leg' => ['x' => $base['right_leg']['x'], 'y' => $base['right_leg']['y'], 'z' => $base['right_leg']['z'] - $swing * 0.4],
        ];
    }
    
    /**
     * 计算跑步时的骨骼位置
     */
    private function calculateRunBonePositions(array $base, float $progress): array
    {
        $swing = sin($progress * 2 * M_PI);
        return [
            'spine' => ['x' => $base['spine']['x'], 'y' => $base['spine']['y'], 'z' => $base['spine']['z'] + $swing * 0.15],
            'left_arm' => ['x' => $base['left_arm']['x'], 'y' => $base['left_arm']['y'], 'z' => $base['left_arm']['z'] - $swing * 0.5],
            'right_arm' => ['x' => $base['right_arm']['x'], 'y' => $base['right_arm']['y'], 'z' => $base['right_arm']['z'] + $swing * 0.5],
            'left_leg' => ['x' => $base['left_leg']['x'], 'y' => $base['left_leg']['y'], 'z' => $base['left_leg']['z'] + $swing * 0.6],
            'right_leg' => ['x' => $base['right_leg']['x'], 'y' => $base['right_leg']['y'], 'z' => $base['right_leg']['z'] - $swing * 0.6],
        ];
    }
    
    /**
     * 计算跳跃时的骨骼位置
     */
    private function calculateJumpBonePositions(array $base, float $progress): array
    {
        $lift = $progress < 0.5 ? $progress * 2 : (1 - $progress) * 2;
        return [
            'spine' => ['x' => $base['spine']['x'], 'y' => $base['spine']['y'] + $lift * 0.5, 'z' => $base['spine']['z']],
            'left_arm' => ['x' => $base['left_arm']['x'], 'y' => $base['left_arm']['y'] + $lift * 0.3, 'z' => $base['left_arm']['z']],
            'right_arm' => ['x' => $base['right_arm']['x'], 'y' => $base['right_arm']['y'] + $lift * 0.3, 'z' => $base['right_arm']['z']],
            'left_leg' => ['x' => $base['left_leg']['x'], 'y' => $base['left_leg']['y'] + $lift * 0.4, 'z' => $base['left_leg']['z']],
            'right_leg' => ['x' => $base['right_leg']['x'], 'y' => $base['right_leg']['y'] + $lift * 0.4, 'z' => $base['right_leg']['z']],
        ];
    }
    
    /**
     * 计算待机时的骨骼位置
     */
    private function calculateIdleBonePositions(array $base, float $progress): array
    {
        $breath = sin($progress * 2 * M_PI) * 0.05;
        return [
            'spine' => ['x' => $base['spine']['x'], 'y' => $base['spine']['y'] + $breath, 'z' => $base['spine']['z']],
            'left_arm' => $base['left_arm'],
            'right_arm' => $base['right_arm'],
            'left_leg' => $base['left_leg'],
            'right_leg' => $base['right_leg'],
        ];
    }
    
    /**
     * 使用AI生成关键帧
     *
     * @param array $parameters
     * @return array
     */
    public function generateKeyFramesWithAI(array $parameters): array
    {
        try {
            // 构建AI提示词
            $prompt = $this->buildKeyFramePrompt($parameters);
            
            // 调用AI服务
            $aiResponse = $this->callLLM($prompt);
            
            if ($aiResponse && isset($aiResponse['success']) && $aiResponse['success']) {
                // 解析AI返回的关键帧数据
                $keyFrames = $this->parseAIKeyFrameResponse($aiResponse['content'], $parameters);
                return $keyFrames;
            }
            
            // 如果AI服务不可用，使用规则引擎
            return $this->generateKeyFrames(
                $parameters['animation_type'] ?? 'idle',
                $parameters['duration'] ?? 5.0,
                $parameters['frame_rate'] ?? 24
            );
        } catch (\Exception $e) {
            error_log('AI keyframe generation failed: ' . $e->getMessage());
            // 降级到规则引擎
            return $this->generateKeyFrames(
                $parameters['animation_type'] ?? 'idle',
                $parameters['duration'] ?? 5.0,
                $parameters['frame_rate'] ?? 24
            );
        }
    }
    
    /**
     * 构建关键帧生成提示词
     */
    private function buildKeyFramePrompt(array $params): string
    {
        $type = $params['animation_type'] ?? 'idle';
        $duration = $params['duration'] ?? 5.0;
        $frameRate = $params['frame_rate'] ?? 24;
        
        return "请为{$type}动画生成关键帧数据，要求：
1. 动画时长：{$duration}秒
2. 帧率：{$frameRate} FPS
3. 动画类型：{$type}

请以JSON格式返回关键帧数组，每个关键帧包含：
- frame: 帧号
- time: 时间（秒）
- position: {x, y, z} 位置
- rotation: {x, y, z} 旋转
- scale: {x, y, z} 缩放
- bone_positions: 骨骼位置对象

返回格式示例：
{
  \"key_frames\": [
    {\"frame\": 0, \"time\": 0, \"position\": {\"x\": 0, \"y\": 0, \"z\": 0}, ...}
  ]
}";
    }
    
    /**
     * 调用LLM服务
     */
    private function callLLM(string $prompt): ?array
    {
        try {
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

            $model = 'gpt-3.5-turbo';
            $modelsText = $channel['models_text'] ?? '';
            if ($modelsText) {
                $models = array_filter(array_map('trim', explode("\n", $modelsText)));
                $model = $models[0] ?? 'gpt-3.5-turbo';
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, rtrim($baseUrl, '/') . '/v1/chat/completions');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'model' => $model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_tokens' => 2000,
                'temperature' => 0.7,
            ]));
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                return null;
            }

            $data = json_decode($response, true);
            if (!$data || !isset($data['choices'][0]['message']['content'])) {
                return null;
            }

            return [
                'success' => true,
                'content' => $data['choices'][0]['message']['content'],
            ];

        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * 解析AI返回的关键帧响应
     */
    private function parseAIKeyFrameResponse(string $content, array $params): array
    {
        // 尝试从JSON中提取关键帧
        $jsonMatch = [];
        if (preg_match('/\{[\s\S]*"key_frames"[\s\S]*\}/', $content, $jsonMatch)) {
            $jsonData = json_decode($jsonMatch[0], true);
            if ($jsonData && isset($jsonData['key_frames']) && is_array($jsonData['key_frames'])) {
                return $jsonData['key_frames'];
            }
        }
        
        // 如果解析失败，使用规则引擎
        return $this->generateKeyFrames(
            $params['animation_type'] ?? 'idle',
            $params['duration'] ?? 5.0,
            $params['frame_rate'] ?? 24
        );
    }
    
    /**
     * 生成动画数据
     *
     * @param array $keyFrames
     * @param string $type
     * @return array
     */
    private function generateAnimationData(array $keyFrames, string $type): array
    {
        return [
            'interpolation_method' => 'bezier',
            'easing' => 'ease-in-out',
            'loop' => true,
            'blend_mode' => 'linear',
            'blend_duration' => 0.2,
        ];
    }
}
