<?php

namespace app\services;

use app\models\AnimeAnimation;
use app\services\StandardExceptionHandler;

/**
 * 动画渲染引擎服务
 * 提供动画渲染、视频合成等功能
 */
class AnimationRenderService
{
    /**
     * 渲染动画
     *
     * @param array $parameters
     *   - animation_id: 动画ID
     *   - render_engine: 渲染引擎（ffmpeg, blender, custom等）
     *   - quality: 渲染质量（low, medium, high, ultra）
     *   - output_format: 输出格式（mp4, avi, mov等）
     * @return array
     */
    public function renderAnimation(array $parameters): array
    {
        try {
            if (empty($parameters['animation_id'])) {
                throw new \InvalidArgumentException('动画ID不能为空');
            }
            
            // 获取动画数据
            $animation = AnimeAnimation::getById($parameters['animation_id']);
            if (!$animation) {
                throw new \InvalidArgumentException('动画不存在');
            }
            
            // 更新状态为处理中
            AnimeAnimation::updateStatus($parameters['animation_id'], 'processing');
            
            $renderEngine = $parameters['render_engine'] ?? 'ffmpeg';
            $quality = $parameters['quality'] ?? 'medium';
            $outputFormat = $parameters['output_format'] ?? 'mp4';
            
            // 根据渲染引擎选择渲染方法
            $renderResult = $this->renderWithEngine($animation, $renderEngine, $quality, $outputFormat);
            
            if ($renderResult['success']) {
                // 更新动画记录
                AnimeAnimation::update($parameters['animation_id'], [
                    'file_url' => $renderResult['file_url'],
                    'preview_url' => $renderResult['preview_url'] ?? null,
                    'file_size' => $renderResult['file_size'] ?? 0,
                    'render_time' => $renderResult['render_time'] ?? 0,
                    'render_cost' => $renderResult['render_cost'] ?? 0,
                    'quality_score' => $renderResult['quality_score'] ?? null,
                    'status' => 'completed',
                ]);
                
                return [
                    'success' => true,
                    'animation_id' => $parameters['animation_id'],
                    'file_url' => $renderResult['file_url'],
                    'preview_url' => $renderResult['preview_url'] ?? null,
                    'render_time' => $renderResult['render_time'],
                ];
            } else {
                AnimeAnimation::updateStatus($parameters['animation_id'], 'failed', $renderResult['error'] ?? '渲染失败');
                throw new \RuntimeException($renderResult['error'] ?? '渲染失败');
            }
        } catch (\Exception $e) {
            if (isset($parameters['animation_id'])) {
                AnimeAnimation::updateStatus($parameters['animation_id'], 'failed', $e->getMessage());
            }
            return StandardExceptionHandler::handle($e, '动画渲染');
        }
    }
    
    /**
     * 使用指定引擎渲染
     *
     * @param array $animation
     * @param string $engine
     * @param string $quality
     * @param string $outputFormat
     * @return array
     */
    private function renderWithEngine(array $animation, string $engine, string $quality, string $outputFormat): array
    {
        switch (strtolower($engine)) {
            case 'ffmpeg':
                return $this->renderWithFFmpeg($animation, $quality, $outputFormat);
            case 'blender':
                return $this->renderWithBlender($animation, $quality, $outputFormat);
            case 'custom':
                return $this->renderWithCustomEngine($animation, $quality, $outputFormat);
            default:
                return $this->renderWithFFmpeg($animation, $quality, $outputFormat);
        }
    }
    
    /**
     * 使用FFmpeg渲染
     *
     * @param array $animation
     * @param string $quality
     * @param string $outputFormat
     * @return array
     */
    private function renderWithFFmpeg(array $animation, string $quality, string $outputFormat): array
    {
        try {
            // 检查FFmpeg是否可用
            if (!$this->isFFmpegAvailable()) {
                return [
                    'success' => false,
                    'error' => 'FFmpeg不可用，请先安装FFmpeg'
                ];
            }
            
            // 准备输出路径
            $outputDir = $this->getOutputDirectory($animation['project_id']);
            $outputFile = $outputDir . '/animation_' . $animation['id'] . '_' . time() . '.' . $outputFormat;
            
            // 生成中间帧序列（这里简化处理，实际应该根据关键帧生成）
            $frameSequence = $this->generateFrameSequence($animation);
            
            // 构建FFmpeg命令
            $command = $this->buildFFmpegCommand($frameSequence, $outputFile, $animation, $quality);
            
            // 执行渲染
            $startTime = microtime(true);
            $output = [];
            $returnCode = 0;
            exec($command . ' 2>&1', $output, $returnCode);
            $renderTime = microtime(true) - $startTime;
            
            if ($returnCode !== 0) {
                return [
                    'success' => false,
                    'error' => 'FFmpeg渲染失败: ' . implode("\n", $output)
                ];
            }
            
            // 生成预览图
            $previewUrl = $this->generatePreview($outputFile, $animation['project_id']);
            
            return [
                'success' => true,
                'file_url' => $outputFile,
                'preview_url' => $previewUrl,
                'file_size' => filesize($outputFile),
                'render_time' => $renderTime,
                'render_cost' => $this->calculateRenderCost($renderTime, $quality),
                'quality_score' => $this->calculateQualityScore($quality),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 使用Blender渲染
     *
     * @param array $animation
     * @param string $quality
     * @param string $outputFormat
     * @return array
     */
    private function renderWithBlender(array $animation, string $quality, string $outputFormat): array
    {
        try {
            // 检查Blender是否可用
            if (!$this->isBlenderAvailable()) {
                return [
                    'success' => false,
                    'error' => 'Blender不可用，请先安装Blender并确保在系统PATH中'
                ];
            }
            
            // 准备输出路径
            $outputDir = $this->getOutputDirectory($animation['project_id']);
            $outputFile = $outputDir . '/animation_' . $animation['id'] . '_blender_' . time() . '.' . $outputFormat;
            
            // 生成Blender Python脚本
            $blenderScript = $this->generateBlenderScript($animation, $outputFile, $quality, $outputFormat);
            $scriptFile = $outputDir . '/blender_script_' . $animation['id'] . '_' . time() . '.py';
            file_put_contents($scriptFile, $blenderScript);
            
            // 构建Blender命令
            $blenderPath = $this->findBlender();
            $command = sprintf(
                '%s --background --python %s 2>&1',
                escapeshellarg($blenderPath),
                escapeshellarg($scriptFile)
            );
            
            // 执行渲染
            $startTime = microtime(true);
            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);
            $renderTime = microtime(true) - $startTime;
            
            // 清理临时脚本文件
            if (file_exists($scriptFile)) {
                @unlink($scriptFile);
            }
            
            if ($returnCode !== 0 || !file_exists($outputFile)) {
                return [
                    'success' => false,
                    'error' => 'Blender渲染失败: ' . implode("\n", array_slice($output, -10))
                ];
            }
            
            // 生成预览图
            $previewUrl = $this->generatePreview($outputFile, $animation['project_id']);
            
            return [
                'success' => true,
                'file_url' => $outputFile,
                'preview_url' => $previewUrl,
                'file_size' => filesize($outputFile),
                'render_time' => $renderTime,
                'render_cost' => $this->calculateRenderCost($renderTime, $quality) * 2, // Blender渲染成本更高
                'quality_score' => $this->calculateQualityScore($quality) + 0.5, // Blender质量更高
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Blender渲染异常: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 检查Blender是否可用
     */
    private function isBlenderAvailable(): bool
    {
        $blenderPath = $this->findBlender();
        if (!$blenderPath) {
            return false;
        }
        
        $command = escapeshellarg($blenderPath) . ' --version 2>&1';
        $output = [];
        $returnCode = 0;
        @exec($command, $output, $returnCode);
        return $returnCode === 0;
    }
    
    /**
     * 查找Blender可执行文件路径
     */
    private function findBlender(): ?string
    {
        // 检查环境变量
        $blenderPath = getenv('BLENDER_PATH');
        if ($blenderPath && file_exists($blenderPath)) {
            return $blenderPath;
        }
        
        // 检查常见路径
        $commonPaths = [
            'blender',
            '/usr/bin/blender',
            '/usr/local/bin/blender',
            'C:\\Program Files\\Blender Foundation\\Blender\\blender.exe',
            'C:\\Program Files (x86)\\Blender Foundation\\Blender\\blender.exe',
        ];
        
        foreach ($commonPaths as $path) {
            $command = escapeshellarg($path) . ' --version 2>&1';
            $output = [];
            $returnCode = 0;
            @exec($command, $output, $returnCode);
            if ($returnCode === 0) {
                return $path;
            }
        }
        
        return null;
    }
    
    /**
     * 生成Blender Python脚本
     */
    private function generateBlenderScript(array $animation, string $outputFile, string $quality, string $outputFormat): string
    {
        $keyFrames = $animation['key_frames'] ?? [];
        $frameRate = $animation['frame_rate'] ?? 24;
        $duration = $animation['duration'] ?? 5.0;
        $resolution = $animation['resolution'] ?? '1920x1080';
        list($width, $height) = explode('x', $resolution);
        
        // 根据质量设置渲染参数
        $qualitySettings = $this->getBlenderQualitySettings($quality);
        
        $script = <<<PYTHON
import bpy
import json

# 清理场景
bpy.ops.object.select_all(action='SELECT')
bpy.ops.object.delete(use_global=False)

# 设置渲染参数
scene = bpy.context.scene
scene.render.resolution_x = {$width}
scene.render.resolution_y = {$height}
scene.render.fps = {$frameRate}
scene.render.image_settings.file_format = 'PNG' if '{$outputFormat}' == 'png' else 'FFMPEG'
scene.render.ffmpeg.format = 'MPEG4' if '{$outputFormat}' == 'mp4' else 'AVI'
scene.render.ffmpeg.codec = 'H264'

# 设置质量参数
scene.render.engine = 'CYCLES'
scene.cycles.samples = {$qualitySettings['samples']}
scene.cycles.use_denoising = {$qualitySettings['denoising']}

# 创建基础对象（占位）
bpy.ops.mesh.primitive_cube_add(location=(0, 0, 0))
cube = bpy.context.active_object
cube.name = "AnimationObject"

# 设置动画关键帧
key_frames_data = {$this->formatKeyFramesForBlender($keyFrames)}

# 设置帧范围
scene.frame_start = 1
scene.frame_end = int({$duration} * {$frameRate})

# 设置输出路径
scene.render.filepath = r"{$outputFile}"

# 渲染动画
bpy.ops.render.render(animation=True)

print("Blender渲染完成: " + scene.render.filepath)
PYTHON;
        
        return $script;
    }
    
    /**
     * 获取Blender质量设置
     */
    private function getBlenderQualitySettings(string $quality): array
    {
        $settings = [
            'low' => ['samples' => 32, 'denoising' => 'False'],
            'medium' => ['samples' => 128, 'denoising' => 'True'],
            'high' => ['samples' => 512, 'denoising' => 'True'],
            'ultra' => ['samples' => 2048, 'denoising' => 'True'],
        ];
        
        return $settings[strtolower($quality)] ?? $settings['medium'];
    }
    
    /**
     * 格式化关键帧数据供Blender使用
     */
    private function formatKeyFramesForBlender(array $keyFrames): string
    {
        // 简化处理：只返回关键帧的基本信息
        $formatted = [];
        foreach ($keyFrames as $keyFrame) {
            $formatted[] = [
                'frame' => $keyFrame['frame'] ?? 0,
                'position' => $keyFrame['position'] ?? ['x' => 0, 'y' => 0, 'z' => 0],
                'rotation' => $keyFrame['rotation'] ?? ['x' => 0, 'y' => 0, 'z' => 0],
            ];
        }
        return json_encode($formatted);
    }
    
    /**
     * 使用自定义渲染引擎
     * 支持云渲染服务集成（如AWS Batch、Google Cloud Render、阿里云渲染等）
     *
     * @param array $animation
     * @param string $quality
     * @param string $outputFormat
     * @return array
     */
    private function renderWithCustomEngine(array $animation, string $quality, string $outputFormat): array
    {
        try {
            // 获取渲染设置
            $renderSettings = $animation['render_settings'] ?? [];
            $engineType = $renderSettings['custom_engine_type'] ?? 'cloud';
            $engineConfig = $renderSettings['custom_engine_config'] ?? [];
            
            // 根据引擎类型选择实现
            switch (strtolower($engineType)) {
                case 'cloud':
                case 'aws':
                case 'gcp':
                case 'aliyun':
                    return $this->renderWithCloudService($animation, $quality, $outputFormat, $engineType, $engineConfig);
                case 'api':
                    return $this->renderWithApiService($animation, $quality, $outputFormat, $engineConfig);
                default:
                    return [
                        'success' => false,
                        'error' => "不支持的渲染引擎类型: {$engineType}"
                    ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => '自定义渲染引擎异常: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 使用云渲染服务
     */
    private function renderWithCloudService(array $animation, string $quality, string $outputFormat, string $engineType, array $config): array
    {
        // 检查配置
        if (empty($config['api_key']) && empty($config['access_key'])) {
            return [
                'success' => false,
                'error' => '云渲染服务配置不完整，需要API密钥或访问密钥'
            ];
        }
        
        // 准备渲染任务数据
        $taskData = $this->prepareCloudRenderTask($animation, $quality, $outputFormat);
        
        // 根据引擎类型调用相应的云服务
        switch (strtolower($engineType)) {
            case 'aws':
                return $this->submitToAWSBatch($taskData, $config);
            case 'gcp':
                return $this->submitToGCPRender($taskData, $config);
            case 'aliyun':
                return $this->submitToAliyunRender($taskData, $config);
            default:
                // 通用云渲染API
                return $this->submitToGenericCloudAPI($taskData, $config);
        }
    }
    
    /**
     * 使用API服务渲染
     */
    private function renderWithApiService(array $animation, string $quality, string $outputFormat, array $config): array
    {
        if (empty($config['api_url'])) {
            return [
                'success' => false,
                'error' => 'API服务URL未配置'
            ];
        }
        
        // 准备请求数据
        $requestData = [
            'animation_id' => $animation['id'],
            'animation_data' => $animation,
            'quality' => $quality,
            'output_format' => $outputFormat,
            'key_frames' => $animation['key_frames'] ?? [],
        ];
        
        // 发送API请求
        $ch = curl_init($config['api_url']);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($requestData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . ($config['api_key'] ?? ''),
            ],
            CURLOPT_TIMEOUT => 300,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'error' => 'API请求失败: ' . $error
            ];
        }
        
        if ($httpCode !== 200) {
            return [
                'success' => false,
                'error' => "API返回错误: HTTP {$httpCode}"
            ];
        }
        
        $result = json_decode($response, true);
        if (!$result || !isset($result['success'])) {
            return [
                'success' => false,
                'error' => 'API返回数据格式错误'
            ];
        }
        
        return $result;
    }
    
    /**
     * 准备云渲染任务数据
     */
    private function prepareCloudRenderTask(array $animation, string $quality, string $outputFormat): array
    {
        $outputDir = $this->getOutputDirectory($animation['project_id']);
        $outputFile = $outputDir . '/animation_' . $animation['id'] . '_cloud_' . time() . '.' . $outputFormat;
        
        return [
            'animation_id' => $animation['id'],
            'project_id' => $animation['project_id'],
            'key_frames' => $animation['key_frames'] ?? [],
            'animation_data' => $animation['animation_data'] ?? [],
            'frame_rate' => $animation['frame_rate'] ?? 24,
            'duration' => $animation['duration'] ?? 5.0,
            'resolution' => $animation['resolution'] ?? '1920x1080',
            'quality' => $quality,
            'output_format' => $outputFormat,
            'output_path' => $outputFile,
            'render_settings' => $animation['render_settings'] ?? [],
        ];
    }
    
    /**
     * 提交到AWS Batch（示例实现）
     */
    private function submitToAWSBatch(array $taskData, array $config): array
    {
        // 这里需要AWS SDK，暂时返回提示
        return [
            'success' => false,
            'error' => 'AWS Batch集成需要安装AWS SDK for PHP。请安装 aws/aws-sdk-php 包。',
            'task_data' => $taskData, // 返回任务数据供后续实现
        ];
    }
    
    /**
     * 提交到GCP Render（示例实现）
     */
    private function submitToGCPRender(array $taskData, array $config): array
    {
        // 这里需要Google Cloud SDK，暂时返回提示
        return [
            'success' => false,
            'error' => 'GCP Render集成需要安装Google Cloud SDK。请安装 google/cloud 包。',
            'task_data' => $taskData,
        ];
    }
    
    /**
     * 提交到阿里云渲染（示例实现）
     */
    private function submitToAliyunRender(array $taskData, array $config): array
    {
        // 这里需要阿里云SDK，暂时返回提示
        return [
            'success' => false,
            'error' => '阿里云渲染集成需要安装阿里云SDK。请安装 alibabacloud/sdk 包。',
            'task_data' => $taskData,
        ];
    }
    
    /**
     * 提交到通用云渲染API
     */
    private function submitToGenericCloudAPI(array $taskData, array $config): array
    {
        if (empty($config['api_endpoint'])) {
            return [
                'success' => false,
                'error' => '云渲染API端点未配置'
            ];
        }
        
        // 发送任务到云渲染服务
        $ch = curl_init($config['api_endpoint'] . '/render/submit');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($taskData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-API-Key: ' . ($config['api_key'] ?? ''),
            ],
            CURLOPT_TIMEOUT => 30,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'error' => '云渲染API请求失败: ' . $error
            ];
        }
        
        if ($httpCode !== 200) {
            return [
                'success' => false,
                'error' => "云渲染API返回错误: HTTP {$httpCode}",
                'response' => $response
            ];
        }
        
        $result = json_decode($response, true);
        if (!$result) {
            return [
                'success' => false,
                'error' => '云渲染API返回数据格式错误'
            ];
        }
        
        // 如果返回的是任务ID，需要轮询任务状态
        if (isset($result['task_id'])) {
            return $this->pollCloudRenderTask($result['task_id'], $config, $taskData['output_path']);
        }
        
        return $result;
    }
    
    /**
     * 轮询云渲染任务状态
     */
    private function pollCloudRenderTask(string $taskId, array $config, string $outputPath): array
    {
        $maxAttempts = 60; // 最多轮询60次
        $interval = 5; // 每5秒轮询一次
        
        for ($i = 0; $i < $maxAttempts; $i++) {
            sleep($interval);
            
            $ch = curl_init($config['api_endpoint'] . '/render/status/' . $taskId);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'X-API-Key: ' . ($config['api_key'] ?? ''),
                ],
                CURLOPT_TIMEOUT => 10,
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $status = json_decode($response, true);
                if ($status && isset($status['status'])) {
                    if ($status['status'] === 'completed') {
                        // 下载渲染结果
                        if (isset($status['download_url'])) {
                            $this->downloadRenderResult($status['download_url'], $outputPath);
                        }
                        
                        return [
                            'success' => true,
                            'file_url' => $outputPath,
                            'render_time' => $status['render_time'] ?? 0,
                            'render_cost' => $status['render_cost'] ?? 0,
                        ];
                    } elseif ($status['status'] === 'failed') {
                        return [
                            'success' => false,
                            'error' => $status['error'] ?? '云渲染任务失败'
                        ];
                    }
                    // 继续轮询
                }
            }
        }
        
        return [
            'success' => false,
            'error' => '云渲染任务超时'
        ];
    }
    
    /**
     * 下载渲染结果
     */
    private function downloadRenderResult(string $downloadUrl, string $outputPath): bool
    {
        $ch = curl_init($downloadUrl);
        $fp = fopen($outputPath, 'w');
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_TIMEOUT => 300,
        ]);
        
        $success = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);
        
        if (!$success || $httpCode !== 200) {
            @unlink($outputPath);
            return false;
        }
        
        return true;
    }
    
    /**
     * 检查FFmpeg是否可用
     */
    private function isFFmpegAvailable(): bool
    {
        $command = 'ffmpeg -version 2>&1';
        $output = [];
        $returnCode = 0;
        @exec($command, $output, $returnCode);
        return $returnCode === 0;
    }
    
    /**
     * 获取输出目录
     */
    private function getOutputDirectory(int $projectId): string
    {
        $baseDir = dirname(__DIR__, 2) . '/public/uploads/anime_renders';
        $projectDir = $baseDir . '/project_' . $projectId;
        
        if (!is_dir($projectDir)) {
            mkdir($projectDir, 0755, true);
        }
        
        return $projectDir;
    }
    
    /**
     * 生成帧序列
     * 根据关键帧生成完整的帧序列，使用插值算法计算中间帧
     */
    private function generateFrameSequence(array $animation): array
    {
        $keyFrames = $animation['key_frames'] ?? [];
        $frameRate = $animation['frame_rate'] ?? 24;
        $duration = $animation['duration'] ?? 5.0;
        $totalFrames = (int)($duration * $frameRate);
        
        // 如果没有关键帧，生成默认关键帧
        if (empty($keyFrames)) {
            $keyFrames = $this->generateDefaultKeyFrames($totalFrames, $frameRate, $duration);
        }
        
        // 按帧号排序关键帧
        usort($keyFrames, function($a, $b) {
            return ($a['frame'] ?? 0) - ($b['frame'] ?? 0);
        });
        
        $frames = [];
        for ($i = 0; $i < $totalFrames; $i++) {
            $currentTime = $i / $frameRate;
            
            // 计算当前帧的插值数据
            $interpolatedData = $this->interpolateFrame($keyFrames, $i, $currentTime, $frameRate);
            
            $frames[] = [
                'frame' => $i,
                'time' => $currentTime,
                'data' => $interpolatedData,
                'image_path' => $this->generateFrameImage($animation, $i, $interpolatedData),
            ];
        }
        
        return $frames;
    }
    
    /**
     * 生成默认关键帧（当没有提供关键帧时）
     */
    private function generateDefaultKeyFrames(int $totalFrames, int $frameRate, float $duration): array
    {
        return [
            [
                'frame' => 0,
                'time' => 0.0,
                'position' => ['x' => 0, 'y' => 0, 'z' => 0],
                'rotation' => ['x' => 0, 'y' => 0, 'z' => 0],
                'scale' => ['x' => 1, 'y' => 1, 'z' => 1],
                'expression' => 'neutral',
                'pose' => 'standing'
            ],
            [
                'frame' => intval($totalFrames / 2),
                'time' => round($totalFrames / 2 / $frameRate, 2),
                'position' => ['x' => 50, 'y' => 10, 'z' => 0],
                'rotation' => ['x' => 5, 'y' => 15, 'z' => 0],
                'scale' => ['x' => 1.1, 'y' => 1.1, 'z' => 1],
                'expression' => 'smile',
                'pose' => 'walking'
            ],
            [
                'frame' => $totalFrames - 1,
                'time' => $duration,
                'position' => ['x' => 100, 'y' => 0, 'z' => 0],
                'rotation' => ['x' => 0, 'y' => 30, 'z' => 0],
                'scale' => ['x' => 1, 'y' => 1, 'z' => 1],
                'expression' => 'happy',
                'pose' => 'standing'
            ]
        ];
    }
    
    /**
     * 插值计算当前帧的数据
     * 使用线性插值和缓动函数
     */
    private function interpolateFrame(array $keyFrames, int $currentFrame, float $currentTime, int $frameRate): array
    {
        // 找到当前帧所在的关键帧区间
        $prevKeyFrame = null;
        $nextKeyFrame = null;
        
        for ($i = 0; $i < count($keyFrames); $i++) {
            $keyFrame = $keyFrames[$i];
            $keyFrameNum = $keyFrame['frame'] ?? 0;
            
            if ($keyFrameNum <= $currentFrame) {
                $prevKeyFrame = $keyFrame;
            }
            if ($keyFrameNum >= $currentFrame && $nextKeyFrame === null) {
                $nextKeyFrame = $keyFrame;
                break;
            }
        }
        
        // 如果当前帧就是关键帧，直接返回
        if ($prevKeyFrame && ($prevKeyFrame['frame'] ?? 0) === $currentFrame) {
            return $prevKeyFrame;
        }
        
        // 如果没有下一个关键帧，使用最后一个关键帧
        if ($nextKeyFrame === null) {
            $nextKeyFrame = $prevKeyFrame;
        }
        
        // 如果没有前一个关键帧，使用第一个关键帧
        if ($prevKeyFrame === null) {
            $prevKeyFrame = $nextKeyFrame;
        }
        
        // 计算插值比例（0-1之间）
        $prevFrame = $prevKeyFrame['frame'] ?? 0;
        $nextFrame = $nextKeyFrame['frame'] ?? 1;
        
        if ($nextFrame === $prevFrame) {
            $t = 0;
        } else {
            $t = ($currentFrame - $prevFrame) / ($nextFrame - $prevFrame);
        }
        
        // 应用缓动函数（ease-in-out）
        $t = $this->easeInOut($t);
        
        // 插值各个属性
        $result = [
            'frame' => $currentFrame,
            'time' => $currentTime,
        ];
        
        // 插值位置
        if (isset($prevKeyFrame['position']) && isset($nextKeyFrame['position'])) {
            $result['position'] = [
                'x' => $this->lerp($prevKeyFrame['position']['x'] ?? 0, $nextKeyFrame['position']['x'] ?? 0, $t),
                'y' => $this->lerp($prevKeyFrame['position']['y'] ?? 0, $nextKeyFrame['position']['y'] ?? 0, $t),
                'z' => $this->lerp($prevKeyFrame['position']['z'] ?? 0, $nextKeyFrame['position']['z'] ?? 0, $t),
            ];
        }
        
        // 插值旋转
        if (isset($prevKeyFrame['rotation']) && isset($nextKeyFrame['rotation'])) {
            $result['rotation'] = [
                'x' => $this->lerp($prevKeyFrame['rotation']['x'] ?? 0, $nextKeyFrame['rotation']['x'] ?? 0, $t),
                'y' => $this->lerp($prevKeyFrame['rotation']['y'] ?? 0, $nextKeyFrame['rotation']['y'] ?? 0, $t),
                'z' => $this->lerp($prevKeyFrame['rotation']['z'] ?? 0, $nextKeyFrame['rotation']['z'] ?? 0, $t),
            ];
        }
        
        // 插值缩放
        if (isset($prevKeyFrame['scale']) && isset($nextKeyFrame['scale'])) {
            $result['scale'] = [
                'x' => $this->lerp($prevKeyFrame['scale']['x'] ?? 1, $nextKeyFrame['scale']['x'] ?? 1, $t),
                'y' => $this->lerp($prevKeyFrame['scale']['y'] ?? 1, $nextKeyFrame['scale']['y'] ?? 1, $t),
                'z' => $this->lerp($prevKeyFrame['scale']['z'] ?? 1, $nextKeyFrame['scale']['z'] ?? 1, $t),
            ];
        }
        
        // 表情和姿态：选择最近的关键帧
        if ($t < 0.5) {
            $result['expression'] = $prevKeyFrame['expression'] ?? 'neutral';
            $result['pose'] = $prevKeyFrame['pose'] ?? 'standing';
        } else {
            $result['expression'] = $nextKeyFrame['expression'] ?? 'neutral';
            $result['pose'] = $nextKeyFrame['pose'] ?? 'standing';
        }
        
        return $result;
    }
    
    /**
     * 线性插值
     */
    private function lerp(float $a, float $b, float $t): float
    {
        return $a + ($b - $a) * $t;
    }
    
    /**
     * 缓动函数（ease-in-out）
     */
    private function easeInOut(float $t): float
    {
        return $t < 0.5 ? 2 * $t * $t : -1 + (4 - 2 * $t) * $t;
    }
    
    /**
     * 生成单帧图像
     * 根据关键帧和动画数据生成单帧图像
     */
    private function generateFrameImage(array $animation, int $frameNumber, array $frameData = []): string
    {
        $outputDir = $this->getOutputDirectory($animation['project_id']) . '/frames';
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        
        $frameFile = $outputDir . '/frame_' . str_pad($frameNumber, 6, '0', STR_PAD_LEFT) . '.png';
        
        // 如果文件已存在且较新，直接返回
        if (file_exists($frameFile) && filemtime($frameFile) > time() - 3600) {
            return $frameFile;
        }
        
        // 解析分辨率
        $resolution = $animation['resolution'] ?? '1920x1080';
        list($width, $height) = explode('x', $resolution);
        $width = (int)$width;
        $height = (int)$height;
        
        // 创建图像
        if (!function_exists('imagecreatetruecolor')) {
            throw new \RuntimeException('GD库未安装，无法生成帧图像');
        }
        
        $image = imagecreatetruecolor($width, $height);
        
        // 设置背景色（从render_settings或使用默认值）
        $renderSettings = $animation['render_settings'] ?? [];
        $bgColor = $this->parseColor($renderSettings['background_color'] ?? '#323232', $image);
        imagefill($image, 0, 0, $bgColor);
        
        // 如果有帧数据，绘制动画元素
        if (!empty($frameData)) {
            $this->drawFrameContent($image, $animation, $frameData, $width, $height);
        } else {
            // 如果没有帧数据，绘制占位信息
            $this->drawPlaceholder($image, $animation, $frameNumber, $width, $height);
        }
        
        // 保存图像
        imagepng($image, $frameFile);
        imagedestroy($image);
        
        return $frameFile;
    }
    
    /**
     * 绘制帧内容（基于帧数据）
     */
    private function drawFrameContent($image, array $animation, array $frameData, int $width, int $height): void
    {
        // 获取角色信息
        $characterId = $animation['character_id'] ?? null;
        $characterName = $animation['character_name'] ?? 'Character';
        
        // 计算中心位置（基于position）
        $position = $frameData['position'] ?? ['x' => 0, 'y' => 0, 'z' => 0];
        $centerX = ($width / 2) + ($position['x'] ?? 0);
        $centerY = ($height / 2) + ($position['y'] ?? 0);
        
        // 获取缩放
        $scale = $frameData['scale'] ?? ['x' => 1, 'y' => 1, 'z' => 1];
        $scaleX = $scale['x'] ?? 1;
        $scaleY = $scale['y'] ?? 1;
        
        // 绘制角色占位（圆形或矩形）
        $charSize = 100 * min($scaleX, $scaleY);
        $charColor = imagecolorallocate($image, 100, 150, 255);
        $charBorderColor = imagecolorallocate($image, 50, 100, 200);
        
        // 绘制角色主体（椭圆）
        imagefilledellipse($image, (int)$centerX, (int)$centerY, (int)$charSize, (int)$charSize, $charColor);
        imageellipse($image, (int)$centerX, (int)$centerY, (int)$charSize, (int)$charSize, $charBorderColor);
        
        // 绘制表情指示
        $expression = $frameData['expression'] ?? 'neutral';
        $expressionColor = $this->getExpressionColor($expression, $image);
        imagefilledellipse($image, (int)$centerX, (int)($centerY - $charSize * 0.2), 20, 20, $expressionColor);
        
        // 绘制姿态指示
        $pose = $frameData['pose'] ?? 'standing';
        $this->drawPoseIndicator($image, $pose, (int)$centerX, (int)$centerY, (int)$charSize);
        
        // 绘制帧信息文本
        $textColor = imagecolorallocate($image, 255, 255, 255);
        $frameInfo = sprintf('Frame: %d | %s | %s', $frameData['frame'] ?? 0, $expression, $pose);
        imagestring($image, 3, 10, 10, $frameInfo, $textColor);
    }
    
    /**
     * 绘制占位信息
     */
    private function drawPlaceholder($image, array $animation, int $frameNumber, int $width, int $height): void
    {
        $textColor = imagecolorallocate($image, 150, 150, 150);
        $centerX = $width / 2;
        $centerY = $height / 2;
        
        // 绘制占位文本
        $text = sprintf('Frame %d', $frameNumber);
        $textWidth = imagefontwidth(5) * strlen($text);
        imagestring($image, 5, (int)($centerX - $textWidth / 2), (int)($centerY - 10), $text, $textColor);
        
        $animationName = $animation['animation_name'] ?? 'Animation';
        $nameWidth = imagefontwidth(3) * strlen($animationName);
        imagestring($image, 3, (int)($centerX - $nameWidth / 2), (int)($centerY + 10), $animationName, $textColor);
    }
    
    /**
     * 绘制姿态指示器
     */
    private function drawPoseIndicator($image, string $pose, int $x, int $y, int $size): void
    {
        $poseColor = imagecolorallocate($image, 200, 200, 200);
        
        switch (strtolower($pose)) {
            case 'walking':
                // 绘制行走姿态（两条线）
                imageline($image, $x - 20, $y + $size / 2, $x - 10, $y + $size / 2 + 10, $poseColor);
                imageline($image, $x + 10, $y + $size / 2, $x + 20, $y + $size / 2 + 10, $poseColor);
                break;
            case 'running':
                // 绘制跑步姿态
                imageline($image, $x - 25, $y + $size / 2, $x - 15, $y + $size / 2 + 15, $poseColor);
                imageline($image, $x + 15, $y + $size / 2, $x + 25, $y + $size / 2 + 15, $poseColor);
                break;
            case 'jumping':
                // 绘制跳跃姿态
                imageline($image, $x, $y + $size / 2, $x, $y + $size / 2 + 20, $poseColor);
                break;
            default:
                // standing - 不需要额外绘制
                break;
        }
    }
    
    /**
     * 获取表情颜色
     */
    private function getExpressionColor(string $expression, $image): int
    {
        $colors = [
            'happy' => [255, 200, 0],
            'smile' => [255, 150, 0],
            'neutral' => [200, 200, 200],
            'sad' => [100, 100, 255],
            'angry' => [255, 50, 50],
        ];
        
        $color = $colors[strtolower($expression)] ?? [200, 200, 200];
        return imagecolorallocate($image, $color[0], $color[1], $color[2]);
    }
    
    /**
     * 解析颜色字符串（支持hex和rgb）
     */
    private function parseColor(string $color, $image): int
    {
        // 如果是hex颜色
        if (preg_match('/^#([0-9a-fA-F]{6})$/', $color, $matches)) {
            $hex = $matches[1];
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            return imagecolorallocate($image, $r, $g, $b);
        }
        
        // 如果是rgb格式
        if (preg_match('/rgb\((\d+),\s*(\d+),\s*(\d+)\)/', $color, $matches)) {
            return imagecolorallocate($image, (int)$matches[1], (int)$matches[2], (int)$matches[3]);
        }
        
        // 默认灰色
        return imagecolorallocate($image, 50, 50, 50);
    }
    
    /**
     * 构建FFmpeg命令
     */
    private function buildFFmpegCommand(array $frameSequence, string $outputFile, array $animation, string $quality): string
    {
        $frameRate = $animation['frame_rate'] ?? 24;
        $resolution = $animation['resolution'] ?? '1920x1080';
        
        // 获取第一帧路径（用于构建输入模式）
        $firstFrame = $frameSequence[0]['image_path'] ?? '';
        $framePattern = dirname($firstFrame) . '/frame_%06d.png';
        
        // 根据质量设置编码参数
        $qualityParams = $this->getQualityParams($quality);
        
        $command = sprintf(
            'ffmpeg -y -framerate %d -i %s -c:v libx264 -preset %s -crf %d -pix_fmt yuv420p -s %s %s',
            $frameRate,
            escapeshellarg($framePattern),
            $qualityParams['preset'],
            $qualityParams['crf'],
            $resolution,
            escapeshellarg($outputFile)
        );
        
        return $command;
    }
    
    /**
     * 获取质量参数
     */
    private function getQualityParams(string $quality): array
    {
        $params = [
            'low' => ['preset' => 'ultrafast', 'crf' => 28],
            'medium' => ['preset' => 'medium', 'crf' => 23],
            'high' => ['preset' => 'slow', 'crf' => 18],
            'ultra' => ['preset' => 'veryslow', 'crf' => 15],
        ];
        
        return $params[strtolower($quality)] ?? $params['medium'];
    }
    
    /**
     * 生成预览图
     */
    private function generatePreview(string $videoFile, int $projectId): ?string
    {
        try {
            if (!$this->isFFmpegAvailable()) {
                return null;
            }
            
            $previewDir = $this->getOutputDirectory($projectId) . '/previews';
            if (!is_dir($previewDir)) {
                mkdir($previewDir, 0755, true);
            }
            
            $previewFile = $previewDir . '/preview_' . basename($videoFile, pathinfo($videoFile, PATHINFO_EXTENSION)) . 'jpg';
            
            // 从视频中提取第一帧作为预览图
            $command = sprintf(
                'ffmpeg -i %s -ss 00:00:01 -vframes 1 -q:v 2 %s 2>&1',
                escapeshellarg($videoFile),
                escapeshellarg($previewFile)
            );
            
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0 && file_exists($previewFile)) {
                return $previewFile;
            }
        } catch (\Exception $e) {
            error_log('Preview generation failed: ' . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * 计算渲染成本
     */
    private function calculateRenderCost(float $renderTime, string $quality): float
    {
        // 根据渲染时间和质量计算成本（星夜币）
        $baseCost = 0.01; // 基础成本
        $timeCost = $renderTime * 0.001; // 时间成本
        
        $qualityMultiplier = [
            'low' => 1.0,
            'medium' => 1.5,
            'high' => 2.0,
            'ultra' => 3.0,
        ];
        
        $multiplier = $qualityMultiplier[strtolower($quality)] ?? 1.5;
        
        return ($baseCost + $timeCost) * $multiplier;
    }
    
    /**
     * 计算质量分数
     */
    private function calculateQualityScore(string $quality): float
    {
        $scores = [
            'low' => 6.0,
            'medium' => 7.5,
            'high' => 8.5,
            'ultra' => 9.5,
        ];
        
        return $scores[strtolower($quality)] ?? 7.5;
    }
    
    /**
     * 批量渲染动画
     *
     * @param array $animationIds
     * @param array $renderParams
     * @return array
     */
    public function batchRender(array $animationIds, array $renderParams = []): array
    {
        $results = [];
        
        foreach ($animationIds as $animationId) {
            $params = array_merge($renderParams, ['animation_id' => $animationId]);
            $results[$animationId] = $this->renderAnimation($params);
        }
        
        return [
            'success' => true,
            'results' => $results,
            'total' => count($animationIds),
            'succeeded' => count(array_filter($results, function($r) { return $r['success'] ?? false; })),
            'failed' => count(array_filter($results, function($r) { return !($r['success'] ?? false); })),
        ];
    }
}
