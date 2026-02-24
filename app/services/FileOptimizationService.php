<?php

namespace app\services;

use app\models\FileHash;
use app\models\StorageConfig;

class FileOptimizationService
{
    private $compressionEnabled;
    private $maxCompressionLevel;
    
    public function __construct()
    {
        $this->loadConfig();
    }
    
    /**
     * 加载配置
     */
    private function loadConfig(): void
    {
        $config = StorageConfig::getActive();
        if ($config) {
            $configValue = json_decode($config['config_value'], true);
            $this->compressionEnabled = $configValue['compression_enabled'] ?? true;
            $this->maxCompressionLevel = $configValue['max_compression_level'] ?? 6;
        } else {
            $this->compressionEnabled = true;
            $this->maxCompressionLevel = 6;
        }
    }
    
    /**
     * 处理上传的文件，进行去重和压缩
     */
    public function processUploadedFile(string $filePath, ?int $userId = null): array
    {
        // 1. 检查文件是否已存在（去重）
        $fileHash = FileHash::calculateFileHash($filePath);
        $existingFile = FileHash::findByHash($fileHash);
        
        if ($existingFile) {
            // 文件已存在，增加引用计数
            FileHash::incrementReference($existingFile['id']);
            
            // 删除新上传的重复文件
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            return [
                'status' => 'duplicate',
                'file_id' => $existingFile['id'],
                'file_path' => $existingFile['file_path'],
                'message' => '文件已存在，使用现有文件'
            ];
        }
        
        // 2. 压缩文件（如果启用且文件类型支持）
        if ($this->compressionEnabled && $this->shouldCompress($filePath)) {
            $compressedPath = $this->compressFile($filePath);
            if ($compressedPath && $compressedPath !== $filePath) {
                // 压缩成功，使用压缩后的文件
                $filePath = $compressedPath;
            }
        }
        
        // 3. 创建文件记录
        $fileRecord = FileHash::getOrCreateFile($filePath, $userId);
        
        return [
            'status' => 'success',
            'file_id' => $fileRecord['id'],
            'file_path' => $fileRecord['file_path'],
            'message' => '文件处理成功'
        ];
    }
    
    /**
     * 判断文件是否应该压缩
     */
    private function shouldCompress(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }
        
        $fileSize = filesize($filePath);
        $mimeType = mime_content_type($filePath);
        
        // 只压缩大于1MB的文件
        if ($fileSize < 1024 * 1024) {
            return false;
        }
        
        // 只压缩特定类型的文件
        $compressibleTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'text/plain',
            'text/html',
            'text/css',
            'text/javascript',
            'application/javascript',
            'application/json',
            'application/xml'
        ];
        
        return in_array($mimeType, $compressibleTypes);
    }
    
    /**
     * 压缩文件
     */
    private function compressFile(string $filePath): ?string
    {
        if (!file_exists($filePath)) {
            return null;
        }
        
        $mimeType = mime_content_type($filePath);
        $pathInfo = pathinfo($filePath);
        $compressedPath = $filePath;
        
        try {
            switch ($mimeType) {
                case 'image/jpeg':
                    $compressedPath = $this->compressImage($filePath, 'jpeg');
                    break;
                    
                case 'image/png':
                    $compressedPath = $this->compressImage($filePath, 'png');
                    break;
                    
                case 'image/gif':
                    $compressedPath = $this->compressImage($filePath, 'gif');
                    break;
                    
                case 'image/webp':
                    $compressedPath = $this->compressImage($filePath, 'webp');
                    break;
                    
                case 'text/plain':
                case 'text/html':
                case 'text/css':
                case 'text/javascript':
                case 'application/javascript':
                case 'application/json':
                case 'application/xml':
                    $compressedPath = $this->compressTextFile($filePath);
                    break;
                    
                default:
                    return $filePath;
            }
            
            // 检查压缩是否成功（文件是否变小）
            if ($compressedPath !== $filePath && file_exists($compressedPath)) {
                $originalSize = filesize($filePath);
                $compressedSize = filesize($compressedPath);
                
                if ($compressedSize >= $originalSize) {
                    // 压缩后文件更大，删除压缩文件，使用原文件
                    if ($compressedPath !== $filePath) {
                        unlink($compressedPath);
                    }
                    return $filePath;
                }
                
                return $compressedPath;
            }
            
            return $filePath;
        } catch (Exception $e) {
            error_log("文件压缩失败: " . $e->getMessage());
            return $filePath;
        }
    }
    
    /**
     * 压缩图片
     */
    private function compressImage(string $filePath, string $format): string
    {
        $pathInfo = pathinfo($filePath);
        $compressedPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_compressed.' . $format;
        
        // 检查是否支持GD库
        if (!extension_loaded('gd')) {
            return $filePath;
        }
        
        $imageInfo = getimagesize($filePath);
        if (!$imageInfo) {
            return $filePath;
        }
        
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $type = $imageInfo[2];
        
        // 创建图像资源
        switch ($type) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($filePath);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($filePath);
                break;
            case IMAGETYPE_GIF:
                $image = imagecreatefromgif($filePath);
                break;
            default:
                return $filePath;
        }
        
        if (!$image) {
            return $filePath;
        }
        
        // 压缩并保存
        $success = false;
        switch ($format) {
            case 'jpeg':
                $success = imagejpeg($image, $compressedPath, $this->maxCompressionLevel);
                break;
            case 'png':
                $success = imagepng($image, $compressedPath, $this->maxCompressionLevel);
                break;
            case 'gif':
                $success = imagegif($image, $compressedPath);
                break;
            case 'webp':
                if (function_exists('imagewebp')) {
                    $success = imagewebp($image, $compressedPath, $this->maxCompressionLevel);
                }
                break;
        }
        
        imagedestroy($image);
        
        return $success ? $compressedPath : $filePath;
    }
    
    /**
     * 压缩文本文件
     */
    private function compressTextFile(string $filePath): string
    {
        $pathInfo = pathinfo($filePath);
        $compressedPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.gz';
        
        // 检查是否支持zlib
        if (!extension_loaded('zlib')) {
            return $filePath;
        }
        
        $originalContent = file_get_contents($filePath);
        $compressedContent = gzencode($originalContent, $this->maxCompressionLevel);
        
        if ($compressedContent === false) {
            return $filePath;
        }
        
        if (file_put_contents($compressedPath, $compressedContent) !== false) {
            return $compressedPath;
        }
        
        return $filePath;
    }
    
    /**
     * 批量优化文件
     */
    public function batchOptimizeFiles(array $filePaths): array
    {
        $results = [];
        $totalOriginalSize = 0;
        $totalCompressedSize = 0;
        $duplicatesFound = 0;
        
        foreach ($filePaths as $filePath) {
            if (!file_exists($filePath)) {
                $results[] = [
                    'file_path' => $filePath,
                    'status' => 'error',
                    'message' => '文件不存在'
                ];
                continue;
            }
            
            $originalSize = filesize($filePath);
            $totalOriginalSize += $originalSize;
            
            $result = $this->processUploadedFile($filePath);
            
            if ($result['status'] === 'duplicate') {
                $duplicatesFound++;
                $totalCompressedSize += filesize($result['file_path']);
            } elseif ($result['status'] === 'success') {
                $compressedSize = filesize($result['file_path']);
                $totalCompressedSize += $compressedSize;
                
                if ($compressedSize < $originalSize) {
                    $result['compression_ratio'] = round((1 - $compressedSize / $originalSize) * 100, 2);
                } else {
                    $result['compression_ratio'] = 0;
                }
            }
            
            $results[] = $result;
        }
        
        $totalSaved = $totalOriginalSize - $totalCompressedSize;
        $overallCompressionRatio = $totalOriginalSize > 0 ? round(($totalSaved / $totalOriginalSize) * 100, 2) : 0;
        
        return [
            'results' => $results,
            'summary' => [
                'total_files' => count($filePaths),
                'duplicates_found' => $duplicatesFound,
                'total_original_size' => $totalOriginalSize,
                'total_compressed_size' => $totalCompressedSize,
                'total_saved' => $totalSaved,
                'overall_compression_ratio' => $overallCompressionRatio
            ]
        ];
    }
    
    /**
     * 清理重复文件
     */
    public function cleanupDuplicates(): array
    {
        $duplicates = FileHash::getDuplicateFiles();
        $cleanedCount = 0;
        $freedSpace = 0;
        $errors = [];
        
        foreach ($duplicates as $duplicate) {
            $duplicateFiles = FileHash::getDuplicatesByHash($duplicate['file_hash']);
            
            if (count($duplicateFiles) <= 1) {
                continue;
            }
            
            // 保留第一个文件，删除其余的重复文件
            $filesToDelete = array_slice($duplicateFiles, 1);
            
            foreach ($filesToDelete as $file) {
                try {
                    if (file_exists($file['file_path'])) {
                        if (unlink($file['file_path'])) {
                            $freedSpace += $file['file_size'];
                            $cleanedCount++;
                        } else {
                            $errors[] = "无法删除重复文件: " . $file['file_path'];
                        }
                    }
                    
                    // 删除数据库记录
                    FileHash::delete($file['id']);
                } catch (Exception $e) {
                    $errors[] = "删除重复文件 {$file['file_path']} 时出错: " . $e->getMessage();
                }
            }
        }
        
        return [
            'cleaned_count' => $cleanedCount,
            'freed_space' => $freedSpace,
            'errors' => $errors
        ];
    }
    
    /**
     * 格式化字节大小
     */
    public static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * 统一错误处理
     */
    protected function handleError(\Exception $e, $operation = '') {
        $errorMessage = $operation ? $operation . '失败: ' . $e->getMessage() : $e->getMessage();
        
        // 记录错误日志
        error_log('Service Error: ' . $errorMessage);
        
        // 抛出自定义异常
        throw new \Exception($errorMessage, $e->getCode(), $e);
    }
}
