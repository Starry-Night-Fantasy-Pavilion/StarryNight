<?php

namespace app\services;

use app\services\StandardExceptionHandler;

/**
 * 旋律生成服务
 * 提供AI旋律生成、哼唱识别等功能
 */
class MelodyGenerationService
{
    /**
     * 基于参数生成旋律
     *
     * @param array $parameters 生成参数
     *   - project_id: 项目ID
     *   - style: 风格（pop, rock, jazz等）
     *   - emotion: 情感（happy, sad, energetic等）
     *   - duration: 时长（秒）
     *   - tempo: 速度（BPM，可选）
     *   - key_signature: 调性（可选）
     *   - time_signature: 拍号（可选）
     * @return array 生成的旋律数据
     */
    public function generateMelody(array $parameters): array
    {
        try {
            // 验证必需参数
            if (empty($parameters['project_id'])) {
                throw new \InvalidArgumentException('项目ID不能为空');
            }
            
            // 设置默认值
            $style = $parameters['style'] ?? 'pop';
            $emotion = $parameters['emotion'] ?? 'happy';
            $duration = (int)($parameters['duration'] ?? 180);
            $tempo = (int)($parameters['tempo'] ?? $this->getDefaultTempo($style));
            $keySignature = $parameters['key_signature'] ?? 'C major';
            $timeSignature = $parameters['time_signature'] ?? '4/4';
            
            // 调用AI服务生成旋律
            $melodyData = $this->callAIMelodyGeneration([
                'style' => $style,
                'emotion' => $emotion,
                'duration' => $duration,
                'tempo' => $tempo,
                'key_signature' => $keySignature,
                'time_signature' => $timeSignature,
            ]);
            
            // 保存到数据库
            $melodyModel = new \App\Models\AiMusicMelody();
            $melodyId = $melodyModel->create([
                'project_id' => $parameters['project_id'],
                'midi_data' => $melodyData['midi'],
                'notation_data' => $melodyData['notation'],
                'tempo' => $tempo,
                'key_signature' => $keySignature,
                'time_signature' => $timeSignature,
                'melody_type' => 'generated',
                'is_ai_generated' => 1,
                'generation_parameters' => $parameters,
            ]);
            
            return [
                'success' => true,
                'melody_id' => $melodyId,
                'data' => $melodyData,
            ];
        } catch (\Exception $e) {
            return StandardExceptionHandler::handle($e, '旋律生成');
        }
    }
    
    /**
     * 识别哼唱并转换为旋律
     *
     * @param int $projectId 项目ID
     * @param string $audioFilePath 音频文件路径
     * @return array
     */
    public function recognizeHumming(int $projectId, string $audioFilePath): array
    {
        try {
            if (!file_exists($audioFilePath)) {
                throw new \InvalidArgumentException('音频文件不存在');
            }
            
            // 调用音频识别服务
            $melodyData = $this->callAudioRecognition($audioFilePath);
            
            // 保存到数据库
            $melodyId = $this->saveMelody([
                'project_id' => $projectId,
                'midi_data' => json_encode($melodyData['midi']),
                'notation_data' => json_encode($melodyData['notation']),
                'tempo' => $melodyData['tempo'] ?? 120,
                'key_signature' => $melodyData['key_signature'] ?? 'C major',
                'time_signature' => $melodyData['time_signature'] ?? '4/4',
                'melody_type' => 'humming',
                'source_file' => $audioFilePath,
                'is_ai_generated' => 0,
            ]);
            
            return [
                'success' => true,
                'melody_id' => $melodyId,
                'data' => $melodyData,
            ];
        } catch (\Exception $e) {
            return StandardExceptionHandler::handle($e, '哼唱识别');
        }
    }
    
    /**
     * 调用AI服务生成旋律
     *
     * @param array $params
     * @return array
     */
    private function callAIMelodyGeneration(array $params): array
    {
        // 尝试调用AI服务生成旋律
        $aiResult = $this->callAIService($params);
        
        if ($aiResult && isset($aiResult['success']) && $aiResult['success']) {
            // 如果AI服务返回成功，使用AI生成的数据
            return $aiResult['data'];
        }
        
        // 如果AI服务不可用或失败，使用增强的模拟数据生成
        $notes = $this->generateEnhancedMockNotes($params);
        
        return [
            'midi' => [
                'notes' => $notes,
                'tempo' => $params['tempo'],
                'time_signature' => $params['time_signature'],
            ],
            'notation' => [
                'clef' => 'treble',
                'key' => $params['key_signature'],
                'time' => $params['time_signature'],
                'notes' => $this->convertToNotation($notes),
            ],
        ];
    }
    
    /**
     * 调用AI服务（使用LLM生成旋律描述，然后转换为音符）
     *
     * @param array $params
     * @return array|null
     */
    private function callAIService(array $params): ?array
    {
        try {
            // 构建提示词，让AI生成旋律描述
            $prompt = $this->buildMelodyPrompt($params);
            
            // 调用AI服务（使用类似NovelAIService的方式）
            $aiResponse = $this->callLLM($prompt);
            
            if ($aiResponse && isset($aiResponse['success']) && $aiResponse['success']) {
                // 解析AI返回的旋律描述并转换为音符
                $notes = $this->parseAIMelodyResponse($aiResponse['content'], $params);
                
                return [
                    'success' => true,
                    'data' => [
                        'midi' => [
                            'notes' => $notes,
                            'tempo' => $params['tempo'],
                            'time_signature' => $params['time_signature'],
                        ],
                        'notation' => [
                            'clef' => 'treble',
                            'key' => $params['key_signature'],
                            'time' => $params['time_signature'],
                            'notes' => $this->convertToNotation($notes),
                        ],
                    ],
                ];
            }
        } catch (\Exception $e) {
            error_log('AI melody generation failed: ' . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * 构建旋律生成提示词
     */
    private function buildMelodyPrompt(array $params): string
    {
        $style = $params['style'] ?? 'pop';
        $emotion = $params['emotion'] ?? 'happy';
        $duration = $params['duration'] ?? 180;
        $tempo = $params['tempo'] ?? 120;
        $keySignature = $params['key_signature'] ?? 'C major';
        
        return "请生成一段{$style}风格的旋律，要求：
1. 情感色彩：{$emotion}
2. 时长：约{$duration}秒
3. 速度：{$tempo} BPM
4. 调性：{$keySignature}
5. 请以JSON格式返回音符序列，每个音符包含：pitch（音高，如C4、D4等）、start_time（开始时间，秒）、duration（持续时间，秒）、velocity（力度，0-127）

返回格式示例：
{
  \"notes\": [
    {\"pitch\": \"C4\", \"start_time\": 0, \"duration\": 0.5, \"velocity\": 80},
    {\"pitch\": \"D4\", \"start_time\": 0.5, \"duration\": 0.5, \"velocity\": 80}
  ]
}";
    }
    
    /**
     * 调用LLM服务
     */
    private function callLLM(string $prompt): ?array
    {
        try {
            // 使用类似NovelAIService的方式调用AI
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
                'temperature' => 0.8,
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
     * 解析AI返回的旋律响应
     */
    private function parseAIMelodyResponse(string $content, array $params): array
    {
        // 尝试从JSON中提取音符
        $jsonMatch = [];
        if (preg_match('/\{[\s\S]*"notes"[\s\S]*\}/', $content, $jsonMatch)) {
            $jsonData = json_decode($jsonMatch[0], true);
            if ($jsonData && isset($jsonData['notes']) && is_array($jsonData['notes'])) {
                return $jsonData['notes'];
            }
        }
        
        // 如果解析失败，使用增强的模拟数据
        return $this->generateEnhancedMockNotes($params);
    }
    
    /**
     * 生成增强的模拟音符数据（更符合音乐理论）
     */
    private function generateEnhancedMockNotes(array $params): array
    {
        $tempo = $params['tempo'] ?? 120;
        $style = $params['style'] ?? 'pop';
        $emotion = $params['emotion'] ?? 'happy';
        $duration = $params['duration'] ?? 180;
        $keySignature = $params['key_signature'] ?? 'C major';
        
        // 根据调性确定音阶
        $scale = $this->getScale($keySignature);
        
        // 根据风格和情感确定音符模式
        $pattern = $this->getMelodyPattern($style, $emotion);
        
        $beatsPerSecond = $tempo / 60;
        $totalBeats = ($duration / 60) * $tempo;
        $notes = [];
        $startTime = 0;
        
        // 生成旋律
        $patternIndex = 0;
        while ($startTime < $duration) {
            $patternNote = $pattern[$patternIndex % count($pattern)];
            $pitch = $scale[$patternNote['scale_degree'] % count($scale)];
            $octave = 4 + floor($patternNote['scale_degree'] / count($scale));
            
            $noteDuration = $patternNote['duration'] * (60 / $tempo);
            
            $notes[] = [
                'pitch' => $pitch . $octave,
                'start_time' => $startTime,
                'duration' => $noteDuration,
                'velocity' => $patternNote['velocity'] ?? 80,
            ];
            
            $startTime += $noteDuration;
            $patternIndex++;
        }
        
        return $notes;
    }
    
    /**
     * 获取音阶
     */
    private function getScale(string $keySignature): array
    {
        // 简化的音阶映射
        $scales = [
            'C major' => ['C', 'D', 'E', 'F', 'G', 'A', 'B'],
            'G major' => ['G', 'A', 'B', 'C', 'D', 'E', 'F#'],
            'D major' => ['D', 'E', 'F#', 'G', 'A', 'B', 'C#'],
            'A major' => ['A', 'B', 'C#', 'D', 'E', 'F#', 'G#'],
            'E major' => ['E', 'F#', 'G#', 'A', 'B', 'C#', 'D#'],
        ];
        
        return $scales[$keySignature] ?? $scales['C major'];
    }
    
    /**
     * 获取旋律模式
     */
    private function getMelodyPattern(string $style, string $emotion): array
    {
        // 根据风格和情感返回不同的音符模式
        $patterns = [
            'pop-happy' => [
                ['scale_degree' => 0, 'duration' => 0.5, 'velocity' => 80],
                ['scale_degree' => 2, 'duration' => 0.5, 'velocity' => 85],
                ['scale_degree' => 4, 'duration' => 0.5, 'velocity' => 90],
                ['scale_degree' => 2, 'duration' => 0.5, 'velocity' => 85],
                ['scale_degree' => 0, 'duration' => 1, 'velocity' => 80],
            ],
            'pop-sad' => [
                ['scale_degree' => 0, 'duration' => 1, 'velocity' => 60],
                ['scale_degree' => 2, 'duration' => 0.5, 'velocity' => 65],
                ['scale_degree' => 1, 'duration' => 0.5, 'velocity' => 60],
                ['scale_degree' => 0, 'duration' => 1, 'velocity' => 55],
            ],
            'rock-energetic' => [
                ['scale_degree' => 0, 'duration' => 0.25, 'velocity' => 100],
                ['scale_degree' => 2, 'duration' => 0.25, 'velocity' => 105],
                ['scale_degree' => 4, 'duration' => 0.5, 'velocity' => 110],
                ['scale_degree' => 2, 'duration' => 0.5, 'velocity' => 105],
            ],
        ];
        
        $key = strtolower($style) . '-' . strtolower($emotion);
        return $patterns[$key] ?? $patterns['pop-happy'];
    }
    
    /**
     * 调用音频识别服务
     *
     * @param string $audioFilePath
     * @return array
     */
    private function callAudioRecognition(string $audioFilePath): array
    {
        try {
            // 尝试使用AI服务进行音频识别（哼唱转MIDI）
            // 方案1：使用AI语音识别 + 音高检测
            $aiResult = $this->callAIAudioRecognition($audioFilePath);
            
            if ($aiResult && isset($aiResult['success']) && $aiResult['success']) {
                return $aiResult['data'];
            }
            
            // 方案2：使用FFmpeg进行基础音高检测（降级方案）
            $ffmpegResult = $this->analyzeAudioWithFFmpeg($audioFilePath);
            
            if ($ffmpegResult) {
                return $ffmpegResult;
            }
            
            // 方案3：返回基于规则的模拟数据
            return $this->generateMockRecognitionResult($audioFilePath);
            
        } catch (\Exception $e) {
            error_log("音频识别失败: " . $e->getMessage());
            return $this->generateMockRecognitionResult($audioFilePath);
        }
    }
    
    /**
     * 调用AI音频识别服务
     *
     * @param string $audioFilePath
     * @return array|null
     */
    private function callAIAudioRecognition(string $audioFilePath): ?array
    {
        try {
            // 获取AI通道配置
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
            
            // 将音频文件转换为base64
            $audioData = file_get_contents($audioFilePath);
            $audioBase64 = base64_encode($audioData);
            $mimeType = mime_content_type($audioFilePath) ?: 'audio/mpeg';
            
            // 调用OpenAI Whisper API进行音频转文本，然后使用LLM转换为MIDI
            // 注意：这里需要Whisper API支持，或者使用其他音频识别服务
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, rtrim($baseUrl, '/') . '/v1/audio/transcriptions');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            
            $postData = [
                'file' => new \CURLFile($audioFilePath, $mimeType),
                'model' => 'whisper-1',
                'response_format' => 'json'
            ];
            
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $apiKey
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
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
            if (!$data || !isset($data['text'])) {
                return null;
            }
            
            // 使用LLM将文本描述转换为MIDI数据
            $transcription = $data['text'];
            $midiPrompt = "请将以下哼唱描述转换为MIDI音符序列（JSON格式）：\n{$transcription}\n\n返回格式：{\"notes\": [{\"pitch\": \"C4\", \"start_time\": 0, \"duration\": 0.5, \"velocity\": 80}], \"tempo\": 120, \"time_signature\": \"4/4\"}";
            
            $llmResult = $this->callLLM($midiPrompt);
            if ($llmResult && isset($llmResult['content'])) {
                $midiData = $this->parseAIMelodyResponse($llmResult['content'], ['tempo' => 120]);
                
                return [
                    'success' => true,
                    'data' => [
                        'midi' => [
                            'notes' => $midiData,
                            'tempo' => 120,
                            'time_signature' => '4/4',
                        ],
                        'notation' => [
                            'clef' => 'treble',
                            'key' => 'C major',
                            'time' => '4/4',
                            'notes' => $this->convertToNotation($midiData),
                        ],
                    ]
                ];
            }
            
            return null;
            
        } catch (\Exception $e) {
            error_log("AI音频识别异常: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 使用FFmpeg分析音频（基础音高检测）
     *
     * @param string $audioFilePath
     * @return array|null
     */
    private function analyzeAudioWithFFmpeg(string $audioFilePath): ?array
    {
        // 检查FFmpeg是否可用
        $ffmpegPath = $this->findFFmpeg();
        if (!$ffmpegPath) {
            return null;
        }
        
        try {
            // 使用FFmpeg的acompressor和astats分析音频特征
            $command = sprintf(
                '%s -i %s -af "astats=metadata=1:reset=1" -f null - 2>&1',
                escapeshellarg($ffmpegPath),
                escapeshellarg($audioFilePath)
            );
            
            $output = [];
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0) {
                return null;
            }
            
            // 解析FFmpeg输出，提取基础信息
            // 这里简化处理，实际应该使用更专业的音高检测算法
            $duration = $this->getAudioDuration($audioFilePath);
            $tempo = 120; // 默认值，实际应该通过节拍检测
            
            // 生成基础音符序列
            $notes = [];
            $beatsPerSecond = $tempo / 60;
            $beatDuration = 60 / $tempo;
            
            for ($i = 0; $i < min($duration, 30); $i += $beatDuration) {
                $notes[] = [
                    'pitch' => 'C4', // 简化：实际应该检测音高
                    'start_time' => $i,
                    'duration' => $beatDuration,
                    'velocity' => 80,
                ];
            }
            
            return [
                'midi' => [
                    'notes' => $notes,
                    'tempo' => $tempo,
                    'time_signature' => '4/4',
                ],
                'notation' => [
                    'clef' => 'treble',
                    'key' => 'C major',
                    'time' => '4/4',
                    'notes' => $this->convertToNotation($notes),
                ],
            ];
            
        } catch (\Exception $e) {
            error_log("FFmpeg音频分析失败: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 获取音频时长
     *
     * @param string $audioFilePath
     * @return float
     */
    private function getAudioDuration(string $audioFilePath): float
    {
        $ffmpegPath = $this->findFFmpeg();
        if (!$ffmpegPath) {
            return 0.0;
        }
        
        $command = sprintf(
            '%s -i %s 2>&1 | grep "Duration"',
            escapeshellarg($ffmpegPath),
            escapeshellarg($audioFilePath)
        );
        
        $output = [];
        exec($command, $output);
        
        if (empty($output)) {
            return 0.0;
        }
        
        // 解析时长（格式：Duration: HH:MM:SS.mmm）
        if (preg_match('/Duration: (\d+):(\d+):(\d+\.\d+)/', $output[0], $matches)) {
            $hours = (int)$matches[1];
            $minutes = (int)$matches[2];
            $seconds = (float)$matches[3];
            return $hours * 3600 + $minutes * 60 + $seconds;
        }
        
        return 0.0;
    }
    
    /**
     * 查找FFmpeg可执行文件
     *
     * @return string|null
     */
    private function findFFmpeg(): ?string
    {
        $envPath = get_env('FFMPEG_PATH');
        if ($envPath && file_exists($envPath) && is_executable($envPath)) {
            return $envPath;
        }
        
        $commonPaths = ['/usr/bin/ffmpeg', '/usr/local/bin/ffmpeg', 'ffmpeg'];
        foreach ($commonPaths as $path) {
            if ($path === 'ffmpeg') {
                $whichOutput = [];
                exec('which ffmpeg 2>/dev/null', $whichOutput);
                if (!empty($whichOutput) && file_exists($whichOutput[0])) {
                    return $whichOutput[0];
                }
            } elseif (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }
        
        return null;
    }
    
    /**
     * 生成模拟识别结果
     *
     * @param string $audioFilePath
     * @return array
     */
    private function generateMockRecognitionResult(string $audioFilePath): array
    {
        return [
            'midi' => [
                'notes' => $this->generateMockNotes(['tempo' => 120]),
                'tempo' => 120,
                'time_signature' => '4/4',
            ],
            'notation' => [
                'clef' => 'treble',
                'key' => 'C major',
                'time' => '4/4',
                'notes' => [],
            ],
        ];
    }
    
    /**
     * 生成模拟音符
     *
     * @param array $params
     * @return array
     */
    private function generateMockNotes(array $params): array
    {
        $tempo = $params['tempo'] ?? 120;
        $duration = 30; // 默认30秒
        $notes = [];
        $beatDuration = 60 / $tempo;
        
        for ($i = 0; $i < $duration; $i += $beatDuration) {
            $notes[] = [
                'pitch' => 'C4',
                'start_time' => $i,
                'duration' => $beatDuration,
                'velocity' => 80,
            ];
        }
        
        return $notes;
    }
    
    
    /**
     * 转换为乐谱表示
     *
     * @param array $notes
     * @return array
     */
    private function convertToNotation(array $notes): array
    {
        $notation = [];
        foreach ($notes as $note) {
            $notation[] = [
                'pitch' => $note['pitch'],
                'duration' => $this->getNotationDuration($note['duration']),
            ];
        }
        return $notation;
    }
    
    /**
     * 获取乐谱时值
     *
     * @param float $duration
     * @return string
     */
    private function getNotationDuration(float $duration): string
    {
        if ($duration >= 2.0) return 'whole';
        if ($duration >= 1.0) return 'half';
        if ($duration >= 0.5) return 'quarter';
        if ($duration >= 0.25) return 'eighth';
        return 'sixteenth';
    }
    
    /**
     * 根据风格获取默认速度
     *
     * @param string $style
     * @return int
     */
    private function getDefaultTempo(string $style): int
    {
        $tempos = [
            'pop' => 120,
            'rock' => 140,
            'jazz' => 110,
            'classical' => 100,
            'electronic' => 130,
            'ballad' => 80,
        ];
        
        return $tempos[strtolower($style)] ?? 120;
    }
    
    /**
     * 保存旋律到数据库
     *
     * @param array $data
     * @return int|false
     */
    private function saveMelody(array $data)
    {
        $melodyModel = new \App\Models\AiMusicMelody();
        return $melodyModel->create($data);
    }
}
