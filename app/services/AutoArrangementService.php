<?php

namespace app\services;

use app\services\StandardExceptionHandler;

/**
 * 自动编曲服务
 * 根据旋律和风格自动生成伴奏
 */
class AutoArrangementService
{
    /**
     * 根据旋律和风格自动编曲
     *
     * @param array $parameters
     *   - project_id: 项目ID
     *   - melody_id: 旋律ID
     *   - style: 编曲风格（pop, rock, jazz等）
     *   - density: 织体密度（sparse, medium, dense）
     * @return array
     */
    public function arrange(array $parameters): array
    {
        try {
            if (empty($parameters['project_id']) || empty($parameters['melody_id'])) {
                throw new \InvalidArgumentException('项目ID和旋律ID不能为空');
            }
            
            $style = $parameters['style'] ?? 'pop';
            $density = $parameters['density'] ?? 'medium';
            
            // 获取旋律数据
            $melody = $this->getMelodyData($parameters['melody_id']);
            if (!$melody) {
                throw new \InvalidArgumentException('旋律不存在');
            }
            
            // 生成编曲
            $arrangement = $this->generateArrangement($melody, $style, $density);
            
            // 保存编曲数据
            $arrangementModel = new \App\Models\AiMusicArrangement();
            $arrangementId = $arrangementModel->create([
                'project_id' => $parameters['project_id'],
                'arrangement_data' => $arrangement['arrangement'],
                'style' => $style,
                'instrument_config' => $arrangement['instruments'],
                'chord_progression' => $arrangement['chords'],
                'rhythm_pattern' => $arrangement['rhythm'],
                'density' => $density,
                'is_ai_generated' => 1,
                'generation_parameters' => $parameters,
            ]);
            
            // 创建音轨
            $tracks = $this->createTracks($parameters['project_id'], $arrangement);
            
            return [
                'success' => true,
                'arrangement_id' => $arrangementId,
                'tracks' => $tracks,
                'data' => $arrangement,
            ];
        } catch (\Exception $e) {
            return StandardExceptionHandler::handle($e, '自动编曲');
        }
    }
    
    /**
     * 获取旋律数据
     *
     * @param int $melodyId
     * @return array|null
     */
    private function getMelodyData(int $melodyId): ?array
    {
        $melodyModel = new \App\Models\AiMusicMelody();
        $melody = $melodyModel->findById($melodyId);
        
        if (!$melody) {
            return null;
        }
        
        return [
            'midi' => json_decode($melody['midi_data'], true),
            'notation' => json_decode($melody['notation_data'], true),
            'tempo' => $melody['tempo'],
            'key_signature' => $melody['key_signature'],
        ];
    }
    
    
    /**
     * 生成编曲
     *
     * @param array $melody
     * @param string $style
     * @param string $density
     * @return array
     */
    private function generateArrangement(array $melody, string $style, string $density): array
    {
        // 尝试调用AI服务生成编曲
        $aiResult = $this->callAIArrangementService($melody, $style, $density);
        
        if ($aiResult && isset($aiResult['success']) && $aiResult['success']) {
            return $aiResult['data'];
        }
        
        // 如果AI服务不可用，使用规则引擎生成
        $chords = $this->generateChordProgression($melody, $style);
        $instruments = $this->selectInstruments($style, $density);
        $rhythm = $this->generateRhythmPattern($style, $density);
        
        return [
            'arrangement' => [
                'structure' => $this->getStructure($style),
                'sections' => $this->generateSections($melody, $style),
            ],
            'chords' => $chords,
            'instruments' => $instruments,
            'rhythm' => $rhythm,
        ];
    }
    
    /**
     * 调用AI编曲服务
     *
     * @param array $melody
     * @param string $style
     * @param string $density
     * @return array|null
     */
    private function callAIArrangementService(array $melody, string $style, string $density): ?array
    {
        try {
            // 构建提示词
            $prompt = $this->buildArrangementPrompt($melody, $style, $density);
            
            // 调用AI服务
            $aiResponse = $this->callLLM($prompt);
            
            if ($aiResponse && isset($aiResponse['success']) && $aiResponse['success']) {
                // 解析AI返回的编曲数据
                $arrangement = $this->parseAIArrangementResponse($aiResponse['content'], $melody, $style, $density);
                
                return [
                    'success' => true,
                    'data' => $arrangement,
                ];
            }
        } catch (\Exception $e) {
            error_log('AI arrangement generation failed: ' . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * 构建编曲提示词
     */
    private function buildArrangementPrompt(array $melody, string $style, string $density): string
    {
        $tempo = $melody['tempo'] ?? 120;
        $keySignature = $melody['key_signature'] ?? 'C major';
        $noteCount = count($melody['midi']['notes'] ?? []);
        
        return "请为以下旋律生成{$style}风格的编曲，要求：
1. 织体密度：{$density}
2. 速度：{$tempo} BPM
3. 调性：{$keySignature}
4. 旋律包含约{$noteCount}个音符

请以JSON格式返回编曲数据，包含：
- chords: 和弦进行数组，每个和弦包含chord（和弦名称）、bar（小节号）、start_time（开始时间）
- instruments: 乐器列表数组
- rhythm: 节奏型对象，包含kick、snare、hihat等打击乐的节奏位置

返回格式示例：
{
  \"chords\": [
    {\"chord\": \"C\", \"bar\": 1, \"start_time\": 0},
    {\"chord\": \"Am\", \"bar\": 2, \"start_time\": 4}
  ],
  \"instruments\": [\"piano\", \"bass\", \"drums\"],
  \"rhythm\": {
    \"kick\": [1, 3],
    \"snare\": [2, 4],
    \"hihat\": [1, 2, 3, 4]
  }
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
            $error = curl_error($ch);
            curl_close($ch);

            if ($error || $httpCode !== 200) {
                return null;
            }

            $data = json_decode($response, true);
            if (!$data || !isset($data['choices'][0]['message']['content'])) {
                return null;
            }

            return [
                'success' => true,
                'content' => $data['choices'][0]['message']['content'],
                'usage' => $data['usage'] ?? null
            ];

        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * 解析AI返回的编曲响应
     */
    private function parseAIArrangementResponse(string $content, array $melody, string $style, string $density): array
    {
        // 尝试从JSON中提取编曲数据
        $jsonMatch = [];
        if (preg_match('/\{[\s\S]*\}/', $content, $jsonMatch)) {
            $jsonData = json_decode($jsonMatch[0], true);
            if ($jsonData && isset($jsonData['chords']) && isset($jsonData['instruments'])) {
                return [
                    'arrangement' => [
                        'structure' => $this->getStructure($style),
                        'sections' => $this->generateSections($melody, $style),
                    ],
                    'chords' => $jsonData['chords'],
                    'instruments' => $jsonData['instruments'],
                    'rhythm' => $jsonData['rhythm'] ?? $this->generateRhythmPattern($style, $density),
                ];
            }
        }
        
        // 如果解析失败，使用规则引擎生成
        $chords = $this->generateChordProgression($melody, $style);
        $instruments = $this->selectInstruments($style, $density);
        $rhythm = $this->generateRhythmPattern($style, $density);
        
        return [
            'arrangement' => [
                'structure' => $this->getStructure($style),
                'sections' => $this->generateSections($melody, $style),
            ],
            'chords' => $chords,
            'instruments' => $instruments,
            'rhythm' => $rhythm,
        ];
    }
    
    /**
     * 生成和弦进行
     *
     * @param array $melody
     * @param string $style
     * @return array
     */
    private function generateChordProgression(array $melody, string $style): array
    {
        // 根据风格生成和弦进行
        $progressions = [
            'pop' => ['C', 'Am', 'F', 'G'],
            'rock' => ['E', 'A', 'D', 'G'],
            'jazz' => ['Cmaj7', 'Am7', 'Dm7', 'G7'],
        ];
        
        $baseProgression = $progressions[strtolower($style)] ?? $progressions['pop'];
        
        // 根据旋律长度扩展和弦进行
        $chords = [];
        $beats = count($melody['midi']['notes'] ?? []) * 0.5; // 假设每个音符0.5拍
        $bars = ceil($beats / 4);
        
        for ($i = 0; $i < $bars; $i++) {
            $chords[] = [
                'chord' => $baseProgression[$i % count($baseProgression)],
                'bar' => $i + 1,
                'start_time' => $i * 4,
            ];
        }
        
        return $chords;
    }
    
    /**
     * 选择乐器配置
     *
     * @param string $style
     * @param string $density
     * @return array
     */
    private function selectInstruments(string $style, string $density): array
    {
        $instruments = [
            'pop' => ['piano', 'bass', 'drums', 'strings'],
            'rock' => ['electric_guitar', 'bass', 'drums', 'keyboard'],
            'jazz' => ['piano', 'double_bass', 'drums', 'saxophone'],
        ];
        
        $baseInstruments = $instruments[strtolower($style)] ?? $instruments['pop'];
        
        // 根据密度调整
        if ($density === 'sparse') {
            return array_slice($baseInstruments, 0, 2);
        } elseif ($density === 'dense') {
            return array_merge($baseInstruments, ['brass', 'synth']);
        }
        
        return $baseInstruments;
    }
    
    /**
     * 生成节奏型
     *
     * @param string $style
     * @param string $density
     * @return array
     */
    private function generateRhythmPattern(string $style, string $density): array
    {
        $patterns = [
            'pop' => ['kick' => [1, 3], 'snare' => [2, 4], 'hihat' => [1, 2, 3, 4]],
            'rock' => ['kick' => [1, 3], 'snare' => [2, 4], 'hihat' => [1, 1.5, 2, 2.5, 3, 3.5, 4]],
            'jazz' => ['kick' => [1], 'snare' => [3], 'hihat' => [1, 2, 3, 4]],
        ];
        
        return $patterns[strtolower($style)] ?? $patterns['pop'];
    }
    
    /**
     * 获取曲式结构
     *
     * @param string $style
     * @return array
     */
    private function getStructure(string $style): array
    {
        return [
            'intro' => 4,      // 4小节
            'verse' => 8,      // 8小节
            'chorus' => 8,     // 8小节
            'bridge' => 4,     // 4小节
            'outro' => 4,      // 4小节
        ];
    }
    
    /**
     * 生成段落
     *
     * @param array $melody
     * @param string $style
     * @return array
     */
    private function generateSections(array $melody, string $style): array
    {
        return [
            'intro' => ['bars' => 4, 'instruments' => ['piano']],
            'verse' => ['bars' => 8, 'instruments' => ['piano', 'bass']],
            'chorus' => ['bars' => 8, 'instruments' => ['piano', 'bass', 'drums', 'strings']],
            'bridge' => ['bars' => 4, 'instruments' => ['piano', 'strings']],
            'outro' => ['bars' => 4, 'instruments' => ['piano']],
        ];
    }
    
    /**
     * 创建音轨
     *
     * @param int $projectId
     * @param array $arrangement
     * @return array
     */
    private function createTracks(int $projectId, array $arrangement): array
    {
        $tracks = [];
        $instruments = $arrangement['instruments'];
        
        // 使用AiMusicTrack模型
        $trackModel = new \App\Models\AiMusicTrack();
        
        foreach ($instruments as $index => $instrument) {
            $trackId = $trackModel->create([
                'project_id' => $projectId,
                'name' => ucfirst($instrument) . ' Track',
                'type' => $this->getTrackType($instrument),
                'instrument' => $instrument,
                'volume' => 0.8,
                'pan' => $this->getPanPosition($index, count($instruments)),
                'mute' => 0,
                'solo' => 0,
                'position' => $index + 1,
            ]);
            
            if ($trackId) {
                $tracks[] = is_bool($trackId) ? $index + 1 : $trackId;
            }
        }
        
        return $tracks;
    }
    
    /**
     * 获取音轨类型
     *
     * @param string $instrument
     * @return string
     */
    private function getTrackType(string $instrument): string
    {
        $types = [
            'piano' => 'melody',
            'bass' => 'bass',
            'drums' => 'drums',
            'guitar' => 'melody',
            'strings' => 'harmony',
        ];
        
        return $types[strtolower($instrument)] ?? 'melody';
    }
    
    /**
     * 获取声像位置
     *
     * @param int $index
     * @param int $total
     * @return float
     */
    private function getPanPosition(int $index, int $total): float
    {
        if ($total === 1) return 0;
        
        $step = 2.0 / ($total - 1);
        return -1.0 + ($index * $step);
    }
}
