<?php
/**
 * 存储压力优化清理脚本
 * 
 * 用法: php scripts/storage_cleanup.php [type]
 * type: temp_files, expired_drafts, old_logs, abandoned_resources, all
 * 如果不指定类型，将执行所有清理任务
 */

require_once __DIR__ . '/../vendor/autoload.php';

use app\models\StorageCleanupLog;
use app\models\StorageConfig;
use app\models\FileHash;
use app\models\UserStorageQuota;
use app\models\NoticeBar;
use app\services\Database;

// 加载环境变量
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

class StorageCleanupService
{
    private $config;
    private $baseDataPath;
    
    public function __construct()
    {
        $this->config = $this->getCleanupConfig();
        $this->baseDataPath = $this->config['base_path'] ?? '/data';
    }
    
    private function getCleanupConfig(): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        // 获取配置
        $configs = [
            'storage_temp_files_retention' => 7,
            'storage_expired_drafts_retention' => 30,
            'storage_old_logs_retention' => 90,
            'storage_abandoned_resources_delay' => 7,
            'base_path' => '/data'
        ];
        
        foreach ($configs as $key => $default) {
            $stmt = $pdo->prepare("SELECT value FROM `{$prefix}settings` WHERE `key` = :key");
            $stmt->execute([':key' => $key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $value = json_decode($result['value'], true);
                if (isset($value['days'])) {
                    $configs[$key] = (int)$value['days'];
                } elseif (isset($value['path'])) {
                    $configs[$key] = $value['path'];
                }
            }
        }
        
        return $configs;
    }
    
    public function cleanupTempFiles(): array
    {
        $startTime = microtime(true);
        $strategy = $this->getSmartCleanupStrategy();
        $tempStrategy = $strategy['temp_files'] ?? [];
        
        // 使用智能策略的配置，如果没有则使用默认配置
        $retentionDays = $tempStrategy['max_age_days'] ?? $this->config['storage_temp_files_retention'] ?? 7;
        $preserveRecentHours = $tempStrategy['preserve_recent_hours'] ?? 24;
        $sizeThresholdMB = $tempStrategy['size_threshold_mb'] ?? 100;
        
        $deletedCount = 0;
        $freedSpace = 0;
        $errors = [];
        
        $tempPaths = [
            $this->baseDataPath . '/cache/temp',
            $this->baseDataPath . '/uploads/temp',
            $this->baseDataPath . '/generated/temp',
            sys_get_temp_dir() . '/starrynight'
        ];
        
        foreach ($tempPaths as $tempPath) {
            if (!is_dir($tempPath)) {
                continue;
            }
            
            try {
                // 分析文件使用模式
                $analysis = $this->analyzeFileUsage($tempPath);
                
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($tempPath, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::CHILD_FIRST
                );
                
                $cutoffTime = time() - ($retentionDays * 24 * 60 * 60);
                $preserveTime = time() - ($preserveRecentHours * 60 * 60);
                
                foreach ($iterator as $file) {
                    if ($file->isFile()) {
                        $fileTime = $file->getMTime();
                        $fileSize = $file->getSize();
                        $fileSizeMB = $fileSize / (1024 * 1024);
                        
                        // 智能清理策略：
                        // 1. 超过保留期的文件
                        // 2. 大文件（超过阈值）且超过保留期
                        // 3. 最近访问的文件保留
                        $shouldDelete = false;
                        
                        if ($fileTime < $cutoffTime) {
                            // 超过保留期，检查是否应该删除
                            if ($fileTime < $preserveTime) {
                                // 超过最近访问保留期，可以删除
                                $shouldDelete = true;
                            } elseif ($fileSizeMB > $sizeThresholdMB) {
                                // 大文件即使最近访问过，如果超过保留期也删除
                                $shouldDelete = true;
                            }
                        }
                        
                        if ($shouldDelete) {
                            if (unlink($file->getRealPath())) {
                                $deletedCount++;
                                $freedSpace += $fileSize;
                            } else {
                                $errors[] = "无法删除文件: " . $file->getRealPath();
                            }
                        }
                    } elseif ($file->isDir() && $this->isDirEmpty($file->getRealPath())) {
                        rmdir($file->getRealPath());
                    }
                }
            } catch (Exception $e) {
                $errors[] = "清理临时文件时出错: " . $e->getMessage();
            }
        }
        
        $executionTime = microtime(true) - $startTime;
        
        // 记录清理日志
        StorageCleanupLog::logCleanup('temp_files', $deletedCount, $freedSpace, $executionTime, [
            'retention_days' => $retentionDays,
            'errors' => $errors
        ]);
        
        return [
            'deleted_count' => $deletedCount,
            'freed_space' => $freedSpace,
            'execution_time' => $executionTime,
            'errors' => $errors
        ];
    }
    
    public function cleanupExpiredDrafts(): array
    {
        $startTime = microtime(true);
        $retentionDays = $this->config['storage_expired_drafts_retention'];
        $deletedCount = 0;
        $freedSpace = 0;
        $errors = [];
        
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        try {
            // 清理小说草稿
            $stmt = $pdo->prepare("
                SELECT id, content, created_at FROM `{$prefix}novels` 
                WHERE status = 'draft' 
                AND created_at < DATE_SUB(NOW(), INTERVAL :days DAY)
            ");
            $stmt->execute([':days' => $retentionDays]);
            $novelDrafts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($novelDrafts as $draft) {
                // 删除相关文件
                $this->deleteNovelFiles($draft['id']);
                
                // 删除数据库记录
                $deleteStmt = $pdo->prepare("DELETE FROM `{$prefix}novels` WHERE id = :id");
                if ($deleteStmt->execute([':id' => $draft['id']])) {
                    $deletedCount++;
                }
            }
            
            // 清理动漫草稿
            $stmt = $pdo->prepare("
                SELECT id, created_at FROM `{$prefix}anime_projects` 
                WHERE status = 'draft' 
                AND created_at < DATE_SUB(NOW(), INTERVAL :days DAY)
            ");
            $stmt->execute([':days' => $retentionDays]);
            $animeDrafts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($animeDrafts as $draft) {
                // 删除相关文件
                $this->deleteAnimeFiles($draft['id']);
                
                // 删除数据库记录
                $deleteStmt = $pdo->prepare("DELETE FROM `{$prefix}anime_projects` WHERE id = :id");
                if ($deleteStmt->execute([':id' => $draft['id']])) {
                    $deletedCount++;
                }
            }
            
            // 清理音乐草稿
            $stmt = $pdo->prepare("
                SELECT id, created_at FROM `{$prefix}ai_music_projects` 
                WHERE status = 'draft' 
                AND created_at < DATE_SUB(NOW(), INTERVAL :days DAY)
            ");
            $stmt->execute([':days' => $retentionDays]);
            $musicDrafts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($musicDrafts as $draft) {
                // 删除相关文件
                $this->deleteMusicFiles($draft['id']);
                
                // 删除数据库记录
                $deleteStmt = $pdo->prepare("DELETE FROM `{$prefix}ai_music_projects` WHERE id = :id");
                if ($deleteStmt->execute([':id' => $draft['id']])) {
                    $deletedCount++;
                }
            }
            
        } catch (Exception $e) {
            $errors[] = "清理过期草稿时出错: " . $e->getMessage();
        }
        
        $executionTime = microtime(true) - $startTime;
        
        // 记录清理日志
        StorageCleanupLog::logCleanup('expired_drafts', $deletedCount, $freedSpace, $executionTime, [
            'retention_days' => $retentionDays,
            'errors' => $errors
        ]);
        
        return [
            'deleted_count' => $deletedCount,
            'freed_space' => $freedSpace,
            'execution_time' => $executionTime,
            'errors' => $errors
        ];
    }
    
    public function cleanupOldLogs(): array
    {
        $startTime = microtime(true);
        $retentionDays = $this->config['storage_old_logs_retention'];
        $deletedCount = 0;
        $freedSpace = 0;
        $errors = [];
        
        $logPaths = [
            $this->baseDataPath . '/logs',
            __DIR__ . '/../logs'
        ];
        
        foreach ($logPaths as $logPath) {
            if (!is_dir($logPath)) {
                continue;
            }
            
            try {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($logPath, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::CHILD_FIRST
                );
                
                foreach ($iterator as $file) {
                    if ($file->isFile() && preg_match('/\.log$/', $file->getFilename())) {
                        $fileTime = $file->getMTime();
                        $cutoffTime = time() - ($retentionDays * 24 * 60 * 60);
                        
                        if ($fileTime < $cutoffTime) {
                            $fileSize = $file->getSize();
                            if (unlink($file->getRealPath())) {
                                $deletedCount++;
                                $freedSpace += $fileSize;
                            } else {
                                $errors[] = "无法删除日志文件: " . $file->getRealPath();
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                $errors[] = "清理旧日志时出错: " . $e->getMessage();
            }
        }
        
        // 清理数据库中的旧日志记录
        try {
            $pdo = Database::pdo();
            $prefix = Database::prefix();
            
            // 清理旧的存储清理日志
            $deletedRows = StorageCleanupLog::deleteOldLogs($retentionDays);
            $deletedCount += $deletedRows;
            
        } catch (Exception $e) {
            $errors[] = "清理数据库日志时出错: " . $e->getMessage();
        }
        
        $executionTime = microtime(true) - $startTime;
        
        // 记录清理日志
        StorageCleanupLog::logCleanup('old_logs', $deletedCount, $freedSpace, $executionTime, [
            'retention_days' => $retentionDays,
            'errors' => $errors
        ]);
        
        return [
            'deleted_count' => $deletedCount,
            'freed_space' => $freedSpace,
            'execution_time' => $executionTime,
            'errors' => $errors
        ];
    }
    
    public function cleanupAbandonedResources(): array
    {
        $startTime = microtime(true);
        $delayDays = $this->config['storage_abandoned_resources_delay'];
        $deletedCount = 0;
        $freedSpace = 0;
        $errors = [];
        
        try {
            // 清理无引用的文件
            $result = FileHash::cleanupUnreferencedFiles();
            $deletedCount += $result['deleted_count'];
            $freedSpace += $result['freed_space'];
            $errors = array_merge($errors, $result['errors']);
            
            // 清理用户删除作品后的相关文件
            $pdo = Database::pdo();
            $prefix = Database::prefix();
            
            // 查找已删除用户的相关文件
            $stmt = $pdo->prepare("
                SELECT fh.* FROM `{$prefix}file_hashes` fh
                LEFT JOIN `{$prefix}users` u ON fh.upload_user_id = u.id
                WHERE u.id IS NULL
                AND fh.created_at < DATE_SUB(NOW(), INTERVAL :days DAY)
            ");
            $stmt->execute([':days' => $delayDays]);
            $orphanedFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($orphanedFiles as $file) {
                if (file_exists($file['file_path'])) {
                    if (unlink($file['file_path'])) {
                        $freedSpace += $file['file_size'];
                        $deletedCount++;
                    } else {
                        $errors[] = "无法删除孤立文件: " . $file['file_path'];
                    }
                }
                
                // 删除数据库记录
                FileHash::delete($file['id']);
            }
            
        } catch (Exception $e) {
            $errors[] = "清理废弃资源时出错: " . $e->getMessage();
        }
        
        $executionTime = microtime(true) - $startTime;
        
        // 记录清理日志
        StorageCleanupLog::logCleanup('abandoned_resources', $deletedCount, $freedSpace, $executionTime, [
            'delay_days' => $delayDays,
            'errors' => $errors
        ]);
        
        return [
            'deleted_count' => $deletedCount,
            'freed_space' => $freedSpace,
            'execution_time' => $executionTime,
            'errors' => $errors
        ];
    }
    
    public function runAll(): array
    {
        $results = [];
        $totalDeleted = 0;
        $totalFreed = 0;
        $totalErrors = [];
        
        echo "开始执行存储清理任务...\n";
        
        // 清理临时文件
        echo "清理临时文件...\n";
        $results['temp_files'] = $this->cleanupTempFiles();
        $totalDeleted += $results['temp_files']['deleted_count'];
        $totalFreed += $results['temp_files']['freed_space'];
        $totalErrors = array_merge($totalErrors, $results['temp_files']['errors']);
        
        // 清理过期草稿
        echo "清理过期草稿...\n";
        $results['expired_drafts'] = $this->cleanupExpiredDrafts();
        $totalDeleted += $results['expired_drafts']['deleted_count'];
        $totalFreed += $results['expired_drafts']['freed_space'];
        $totalErrors = array_merge($totalErrors, $results['expired_drafts']['errors']);
        
        // 清理旧日志
        echo "清理旧日志...\n";
        $results['old_logs'] = $this->cleanupOldLogs();
        $totalDeleted += $results['old_logs']['deleted_count'];
        $totalFreed += $results['old_logs']['freed_space'];
        $totalErrors = array_merge($totalErrors, $results['old_logs']['errors']);
        
        // 清理废弃资源
        echo "清理废弃资源...\n";
        $results['abandoned_resources'] = $this->cleanupAbandonedResources();
        $totalDeleted += $results['abandoned_resources']['deleted_count'];
        $totalFreed += $results['abandoned_resources']['freed_space'];
        $totalErrors = array_merge($totalErrors, $results['abandoned_resources']['errors']);
        
        // 创建通知栏消息
        if ($totalDeleted > 0 || $totalFreed > 0) {
            $this->createCleanupNotification($totalDeleted, $totalFreed);
        }
        
        echo "\n清理完成!\n";
        echo "总删除文件数: {$totalDeleted}\n";
        echo "总释放空间: " . StorageCleanupLog::formatBytes($totalFreed) . "\n";
        
        if (!empty($totalErrors)) {
            echo "错误数量: " . count($totalErrors) . "\n";
            foreach ($totalErrors as $error) {
                echo "  - {$error}\n";
            }
        }
        
        return [
            'results' => $results,
            'total_deleted' => $totalDeleted,
            'total_freed' => $totalFreed,
            'total_errors' => $totalErrors
        ];
    }
    
    private function createCleanupNotification(int $deletedCount, int $freedSpace): void
    {
        $content = "系统存储优化完成，已清理 {$deletedCount} 个文件，释放 " . 
                   StorageCleanupLog::formatBytes($freedSpace) . " 空间。";
        
        $data = [
            'content' => $content,
            'priority' => 2,
            'status' => 'enabled',
            'lang' => 'zh-CN',
            'start_time' => date('Y-m-d H:i:s'),
            'end_time' => date('Y-m-d H:i:s', strtotime('+1 day'))
        ];
        
        NoticeBar::create($data);
    }
    
    private function isDirEmpty(string $dir): bool
    {
        if (!is_readable($dir)) {
            return false;
        }
        
        $handle = opendir($dir);
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                closedir($handle);
                return false;
            }
        }
        closedir($handle);
        return true;
    }
    
    private function deleteNovelFiles(int $novelId): void
    {
        $novelPath = $this->baseDataPath . "/uploads/users/novels/{$novelId}";
        if (is_dir($novelPath)) {
            $this->deleteDirectory($novelPath);
        }
    }
    
    private function deleteAnimeFiles(int $animeId): void
    {
        $animePath = $this->baseDataPath . "/uploads/users/anime/{$animeId}";
        if (is_dir($animePath)) {
            $this->deleteDirectory($animePath);
        }
    }
    
    private function deleteMusicFiles(int $musicId): void
    {
        $musicPath = $this->baseDataPath . "/uploads/users/music/{$musicId}";
        if (is_dir($musicPath)) {
            $this->deleteDirectory($musicPath);
        }
    }
    
    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}

// 主执行逻辑
try {
    $cleanupService = new StorageCleanupService();
    $cleanupType = $argv[1] ?? 'all';
    
    switch ($cleanupType) {
        case 'temp_files':
            $result = $cleanupService->cleanupTempFiles();
            break;
        case 'expired_drafts':
            $result = $cleanupService->cleanupExpiredDrafts();
            break;
        case 'old_logs':
            $result = $cleanupService->cleanupOldLogs();
            break;
        case 'abandoned_resources':
            $result = $cleanupService->cleanupAbandonedResources();
            break;
        case 'all':
        default:
            $result = $cleanupService->runAll();
            break;
    }
    
    if (isset($result['total_errors']) && !empty($result['total_errors'])) {
        exit(1);
    }
    
    exit(0);
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
    exit(1);


    /**
     * 智能缓存清理策略
     * 根据文件访问频率和大小制定清理优先级
     */
    private function getSmartCleanupStrategy(): array
    {
        return [
            'temp_files' => [
                'priority' => 1,
                'max_age_days' => 7,
                'size_threshold_mb' => 100,
                'preserve_recent_hours' => 24
            ],
            'expired_drafts' => [
                'priority' => 2,
                'max_age_days' => 30,
                'preserve_modified_hours' => 168 // 一周内修改的保留
            ],
            'old_logs' => [
                'priority' => 3,
                'max_age_days' => 90,
                'compress_after_days' => 30,
                'archive_after_days' => 60
            ],
            'abandoned_resources' => [
                'priority' => 4,
                'max_orphan_days' => 180,
                'check_references' => true
            ]
        ];
    }

    /**
     * 分析文件使用模式
     */
    private function analyzeFileUsage(string $directory): array
    {
        $analysis = [
            'total_files' => 0,
            'total_size' => 0,
            'recent_accessed' => 0,
            'rarely_accessed' => 0,
            'candidates_for_cleanup' => []
        ];

        if (!is_dir($directory)) {
            return $analysis;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory)
        );

        $now = time();
        $recentThreshold = $now - (7 * 24 * 3600); // 7天内访问

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $analysis['total_files']++;
                $size = $file->getSize();
                $analysis['total_size'] += $size;

                // 检查最后访问时间
                $lastAccess = $file->getATime();
                if ($lastAccess > $recentThreshold) {
                    $analysis['recent_accessed']++;
                } else {
                    $analysis['rarely_accessed']++;
                    $analysis['candidates_for_cleanup'][] = [
                        'path' => $file->getPathname(),
                        'size' => $size,
                        'last_access' => $lastAccess,
                        'score' => $this->calculateCleanupScore($file, $size, $lastAccess)
                    ];
                }
            }
        }

        // 按清理分数排序
        usort($analysis['candidates_for_cleanup'], function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return $analysis;
    }

    /**
     * 计算清理优先级分数
     */
    private function calculateCleanupScore(\SplFileInfo $file, int $size, int $lastAccess): float
    {
        $score = 0;
        
        // 文件大小权重 (越大越应该清理)
        $sizeMB = $size / (1024 * 1024);
        $score += min($sizeMB / 100, 1.0) * 0.4;
        
        // 未访问时间权重 (越久越应该清理)
        $daysSinceAccess = (time() - $lastAccess) / (24 * 3600);
        $score += min($daysSinceAccess / 30, 1.0) * 0.4;
        
        // 文件类型权重
        $extension = strtolower($file->getExtension());
        $typeWeights = [
            'tmp' => 1.0,
            'cache' => 0.8,
            'log' => 0.6,
            'bak' => 0.7
        ];
        $score += ($typeWeights[$extension] ?? 0.3) * 0.2;
        
        return $score;
    }

    /**
     * 执行智能清理
     */
    public function performSmartCleanup(string $type = 'all'): array
    {
        $strategy = $this->getSmartCleanupStrategy();
        $results = [];
        
        if ($type === 'all') {
            // 按优先级排序执行清理
            uasort($strategy, function($a, $b) {
                return $a['priority'] <=> $b['priority'];
            });
            
            foreach ($strategy as $cleanupType => $config) {
                $results[$cleanupType] = $this->executeSmartCleanupType($cleanupType, $config);
            }
        } else {
            if (isset($strategy[$type])) {
                $results[$type] = $this->executeSmartCleanupType($type, $strategy[$type]);
            }
        }
        
        return $results;
    }

    /**
     * 执行特定类型的智能清理
     */
    private function executeSmartCleanupType(string $type, array $config): array
    {
        $result = [
            'type' => $type,
            'analyzed' => 0,
            'cleaned' => 0,
            'space_freed_mb' => 0,
            'files_processed' => []
        ];

        // 根据类型确定清理路径
        $paths = $this->getCleanupPaths($type);
        
        foreach ($paths as $path) {
            if (!is_dir($path)) continue;
            
            $analysis = $this->analyzeFileUsage($path);
            $result['analyzed'] += $analysis['total_files'];
            
            // 清理高分候选文件
            $maxCandidates = max(10, intval($analysis['candidates_for_cleanup'] * 0.3)); // 清理最多30%的候选文件
            $candidatesToClean = array_slice($analysis['candidates_for_cleanup'], 0, $maxCandidates);
            
            foreach ($candidatesToClean as $candidate) {
                if (unlink($candidate['path'])) {
                    $result['cleaned']++;
                    $result['space_freed_mb'] += $candidate['size'] / (1024 * 1024);
                    $result['files_processed'][] = $candidate['path'];
                }
            }
        }
        
        // 记录清理日志
        $this->logCleanupAction($result);
        
        return $result;
    }

    /**
     * 获取不同类型清理的路径
     */
    private function getCleanupPaths(string $type): array
    {
        $baseDataPath = $this->config['base_path'] ?? '/data';
        $paths = [];
        
        switch ($type) {
            case 'temp_files':
                $paths = [
                    $baseDataPath . '/temp',
                    sys_get_temp_dir(),
                    $baseDataPath . '/cache/tmp'
                ];
                break;
            case 'expired_drafts':
                $paths = [$baseDataPath . '/drafts'];
                break;
            case 'old_logs':
                $paths = [$baseDataPath . '/logs'];
                break;
            case 'abandoned_resources':
                $paths = [$baseDataPath . '/uploads'];
                break;
        }
        
        return array_filter($paths, 'is_dir');
    }

    /**
     * 记录清理操作日志
     */
    private function logCleanupAction(array $result): void
    {
        try {
            $logEntry = new StorageCleanupLog();
            $logEntry->type = $result['type'];
            $logEntry->files_analyzed = $result['analyzed'];
            $logEntry->files_cleaned = $result['cleaned'];
            $logEntry->space_freed_mb = round($result['space_freed_mb'], 2);
            $logEntry->details = json_encode([
                'files_processed' => array_slice($result['files_processed'], 0, 10) // 只记录前10个文件
            ]);
            $logEntry->created_at = date('Y-m-d H:i:s');
            $logEntry->save();
        } catch (\Exception $e) {
            error_log('Failed to log cleanup action: ' . $e->getMessage());
        }
    }

}
// 添加智能清理选项
if ($argc > 1) {
    $option = $argv[1];
    $cleanupService = new StorageCleanupService();
    
    switch ($option) {
        case 'smart':
            echo "执行智能清理...\n";
            $results = $cleanupService->performSmartCleanup();
            foreach ($results as $type => $result) {
                echo sprintf(
                    "%s: 分析%d个文件，清理%d个文件，释放%.2f MB空间\n",
                    $type,
                    $result['analyzed'],
                    $result['cleaned'],
                    $result['space_freed_mb']
                );
            }
            break;
            
        case 'analyze':
            echo "分析存储使用情况...\n";
            $paths = [
                '/data/temp',
                '/data/cache',
                '/data/logs',
                '/data/uploads'
            ];
            
            foreach ($paths as $path) {
                if (is_dir($path)) {
                    $analysis = $cleanupService->analyzeFileUsage($path);
                    echo sprintf(
                        "%s: %d个文件，总计%.2f MB，%d个近期访问，%d个很少访问\n",
                        $path,
                        $analysis['total_files'],
                        $analysis['total_size'] / (1024 * 1024),
                        $analysis['recent_accessed'],
                        $analysis['rarely_accessed']
                    );
                }
            }
            break;
    }
}