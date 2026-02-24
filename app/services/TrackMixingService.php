<?php

namespace app\services;

use app\services\StandardExceptionHandler;

/**
 * 音轨混合引擎服务
 * 提供多音轨混合、音量平衡、效果处理等功能
 */
class TrackMixingService
{
    /**
     * 混合多个音轨
     *
     * @param array $parameters
     *   - tracks: 音轨数组，每个音轨包含：
     *     - file_path: 音频文件路径
     *     - volume: 音量（0-1）
     *     - pan: 声像位置（-1到1）
     *     - effects: 效果列表
     *   - output_file: 输出文件路径
     *   - sample_rate: 采样率（默认44100）
     *   - bit_depth: 位深度（默认16）
     * @return array
     */
    public function mixTracks(array $parameters): array
    {
        try {
            if (empty($parameters['tracks']) || !is_array($parameters['tracks'])) {
                throw new \InvalidArgumentException('音轨列表不能为空');
            }
            
            $outputFile = $parameters['output_file'] ?? $this->generateOutputPath();
            $sampleRate = (int)($parameters['sample_rate'] ?? 44100);
            $bitDepth = (int)($parameters['bit_depth'] ?? 16);
            
            // 验证所有音轨文件存在
            foreach ($parameters['tracks'] as $track) {
                if (empty($track['file_path']) || !file_exists($track['file_path'])) {
                    throw new \InvalidArgumentException('音轨文件不存在: ' . ($track['file_path'] ?? 'unknown'));
                }
            }
            
            // 执行混合
            $mixedAudio = $this->performMixing($parameters['tracks'], $outputFile, $sampleRate, $bitDepth);
            
            return [
                'success' => true,
                'output_file' => $mixedAudio,
                'sample_rate' => $sampleRate,
                'bit_depth' => $bitDepth,
                'tracks_mixed' => count($parameters['tracks']),
            ];
        } catch (\Exception $e) {
            return StandardExceptionHandler::handle($e, '音轨混合');
        }
    }
    
    /**
     * 执行混合操作
     *
     * @param array $tracks
     * @param string $outputFile
     * @param int $sampleRate
     * @param int $bitDepth
     * @return string
     */
    private function performMixing(array $tracks, string $outputFile, int $sampleRate, int $bitDepth): string
    {
        $outputDir = dirname($outputFile);
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        
        // 检查FFmpeg是否可用
        $ffmpegPath = $this->findFFmpeg();
        if (!$ffmpegPath) {
            throw new \RuntimeException('FFmpeg未安装或不在PATH中。请安装FFmpeg或设置FFMPEG_PATH环境变量。');
        }
        
        // 构建FFmpeg命令
        // 使用amix滤镜混合多个音频文件
        $filterComplex = [];
        $inputs = [];
        
        foreach ($tracks as $index => $track) {
            $filePath = escapeshellarg($track['file_path']);
            $volume = (float)($track['volume'] ?? 1.0);
            $pan = (float)($track['pan'] ?? 0.0); // -1 (左) 到 1 (右)
            
            // 构建音量调整和声像调整的滤镜
            $volumeFilter = "volume=" . $volume;
            $panFilter = '';
            
            if ($pan != 0.0) {
                // pan滤镜：mono|c0=c0*c0L|c1=c0*c0R
                // 其中 c0L = (1-pan)/2, c0R = (1+pan)/2
                $leftGain = (1 - $pan) / 2;
                $rightGain = (1 + $pan) / 2;
                $panFilter = ",pan=stereo|c0=c0*{$leftGain}|c1=c0*{$rightGain}";
            }
            
            $filterComplex[] = "[{$index}:a]{$volumeFilter}{$panFilter}[a{$index}]";
            $inputs[] = "-i {$filePath}";
        }
        
        // 合并所有音轨
        $inputLabels = implode('', array_map(fn($i) => "[a{$i}]", array_keys($tracks)));
        $amixInputs = count($tracks);
        $filterComplex[] = "{$inputLabels}amix=inputs={$amixInputs}:duration=longest:dropout_transition=2[out]";
        
        $filterComplexStr = implode(';', $filterComplex);
        
        // 构建完整命令
        $inputsStr = implode(' ', $inputs);
        $outputFileEscaped = escapeshellarg($outputFile);
        
        $command = sprintf(
            '%s %s -filter_complex %s -map "[out]" -ar %d -ac 2 -sample_fmt s%dle %s -y',
            escapeshellarg($ffmpegPath),
            $inputsStr,
            escapeshellarg($filterComplexStr),
            $sampleRate,
            $bitDepth,
            $outputFileEscaped
        );
        
        // 执行命令
        $output = [];
        $returnVar = 0;
        exec($command . ' 2>&1', $output, $returnVar);
        
        if ($returnVar !== 0 || !file_exists($outputFile)) {
            $errorMsg = implode("\n", $output);
            throw new \RuntimeException("FFmpeg混音失败: {$errorMsg}");
        }
        
        return $outputFile;
    }
    
    /**
     * 查找FFmpeg可执行文件路径
     *
     * @return string|null
     */
    private function findFFmpeg(): ?string
    {
        // 优先使用环境变量
        $envPath = get_env('FFMPEG_PATH');
        if ($envPath && file_exists($envPath) && is_executable($envPath)) {
            return $envPath;
        }
        
        // 尝试常见路径
        $commonPaths = [
            '/usr/bin/ffmpeg',
            '/usr/local/bin/ffmpeg',
            'ffmpeg', // 如果在PATH中
        ];
        
        foreach ($commonPaths as $path) {
            if ($path === 'ffmpeg') {
                // 检查是否在PATH中
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
     * 生成输出文件路径
     *
     * @return string
     */
    private function generateOutputPath(): string
    {
        $outputDir = __DIR__ . '/../../storage/app/mixed';
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        
        return $outputDir . '/mixed_' . time() . '.wav';
    }
    
    /**
     * 平衡音量
     *
     * @param array $tracks
     * @return array 调整后的音轨列表
     */
    public function balanceVolumes(array $tracks): array
    {
        // 分析每个音轨的RMS（均方根）值
        $rmsValues = [];
        foreach ($tracks as $index => $track) {
            $rmsValues[$index] = $this->calculateRMS($track['file_path']);
        }
        
        // 找到最大RMS值
        $maxRMS = max($rmsValues);
        
        // 调整每个音轨的音量
        $balancedTracks = [];
        foreach ($tracks as $index => $track) {
            if ($rmsValues[$index] > 0) {
                $ratio = $maxRMS / $rmsValues[$index];
                $track['volume'] = min(1.0, ($track['volume'] ?? 1.0) * $ratio);
            }
            $balancedTracks[] = $track;
        }
        
        return $balancedTracks;
    }
    
    /**
     * 计算音频RMS值
     *
     * @param string $audioFile
     * @return float
     */
    private function calculateRMS(string $audioFile): float
    {
        if (!file_exists($audioFile)) {
            return 0.0;
        }
        
        // 检查FFmpeg是否可用
        $ffmpegPath = $this->findFFmpeg();
        if (!$ffmpegPath) {
            // FFmpeg不可用时返回默认值
            error_log('FFmpeg not found, using default RMS value');
            return 0.5;
        }
        
        // 使用FFmpeg的astats滤镜计算RMS
        $command = sprintf(
            '%s -i %s -af "astats=metadata=1:reset=1" -f null - 2>&1',
            escapeshellarg($ffmpegPath),
            escapeshellarg($audioFile)
        );
        
        $output = [];
        exec($command, $output, $returnVar);
        
        if ($returnVar !== 0) {
            error_log('FFmpeg RMS calculation failed: ' . implode("\n", $output));
            return 0.5;
        }
        
        // 解析FFmpeg输出中的RMS值
        $rmsValue = 0.0;
        foreach ($output as $line) {
            // 查找类似 "RMS level dB: -XX.XX" 的行
            if (preg_match('/RMS level dB:\s*([-\d.]+)/', $line, $matches)) {
                $rmsDb = (float)$matches[1];
                // 将dB转换为线性值（0-1范围）
                // RMS (linear) = 10^(dB/20)，然后归一化到0-1
                $rmsLinear = pow(10, $rmsDb / 20);
                // 假设最大RMS为1.0（0dB），归一化
                $rmsValue = min(1.0, max(0.0, $rmsLinear));
                break;
            }
        }
        
        // 如果没有找到RMS值，尝试使用另一种方法：计算平均音量
        if ($rmsValue == 0.0) {
            // 使用volumedetect获取平均音量
            $volCommand = sprintf(
                '%s -i %s -af "volumedetect" -f null - 2>&1',
                escapeshellarg($ffmpegPath),
                escapeshellarg($audioFile)
            );
            
            $volOutput = [];
            exec($volCommand, $volOutput, $volReturnVar);
            
            if ($volReturnVar === 0) {
                foreach ($volOutput as $line) {
                    if (preg_match('/mean_volume:\s*([-\d.]+)\s*dB/', $line, $matches)) {
                        $meanDb = (float)$matches[1];
                        $rmsLinear = pow(10, $meanDb / 20);
                        $rmsValue = min(1.0, max(0.0, $rmsLinear));
                        break;
                    }
                }
            }
        }
        
        // 如果仍然没有值，返回默认值
        return $rmsValue > 0 ? $rmsValue : 0.5;
    }
}
