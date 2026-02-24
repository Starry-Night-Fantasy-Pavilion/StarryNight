<?php

namespace App\Models;

use App\Services\Database;
use PDO;

class AiMusicExport
{
    private $table = 'ai_music_export';

    private function getDb(): PDO
    {
        return Database::pdo();
    }

    /**
     * 创建导出记录
     */
    public function create(array $data)
    {
        $sql = "INSERT INTO {$this->table} (
            project_id, mix_master_id, format, quality, file_url, file_size,
            duration, sample_rate, bit_rate, channels, export_settings
        ) VALUES (
            :project_id, :mix_master_id, :format, :quality, :file_url, :file_size,
            :duration, :sample_rate, :bit_rate, :channels, :export_settings
        )";
        
        $stmt = $this->getDb()->prepare($sql);
        return $stmt->execute([
            ':project_id' => $data['project_id'],
            ':mix_master_id' => $data['mix_master_id'] ?? null,
            ':format' => $data['format'],
            ':quality' => $data['quality'],
            ':file_url' => $data['file_url'],
            ':file_size' => $data['file_size'] ?? null,
            ':duration' => $data['duration'] ?? null,
            ':sample_rate' => $data['sample_rate'] ?? null,
            ':bit_rate' => $data['bit_rate'] ?? null,
            ':channels' => $data['channels'] ?? 2,
            ':export_settings' => $data['export_settings'] ?? null
        ]);
    }

    /**
     * 获取导出记录详情
     */
    public function getById(int $id)
    {
        $sql = "SELECT e.*, p.title as project_title, p.user_id, u.username,
                       m.name as mix_master_name, m.type as mix_master_type
                FROM {$this->table} e
                LEFT JOIN ai_music_project p ON e.project_id = p.id
                LEFT JOIN user u ON p.user_id = u.id
                LEFT JOIN ai_music_mix_master m ON e.mix_master_id = m.id
                WHERE e.id = :id";
        
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 根据项目ID获取导出记录
     */
    public function getByProjectId(int $projectId, string $format = null)
    {
        $whereClause = "WHERE project_id = :project_id";
        $params = [':project_id' => $projectId];
        
        if ($format) {
            $whereClause .= " AND format = :format";
            $params[':format'] = $format;
        }
        
        $sql = "SELECT * FROM {$this->table} 
                {$whereClause}
                ORDER BY created_at DESC";
        
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取项目的最新导出
     */
    public function getLatestByProjectId(int $projectId, string $format = null)
    {
        $whereClause = "WHERE project_id = :project_id";
        $params = [':project_id' => $projectId];
        
        if ($format) {
            $whereClause .= " AND format = :format";
            $params[':format'] = $format;
        }
        
        $sql = "SELECT * FROM {$this->table} 
                {$whereClause}
                ORDER BY created_at DESC 
                LIMIT 1";
        
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 更新导出记录
     */
    public function update(int $id, array $data)
    {
        $fields = [];
        $params = [':id' => $id];
        
        $allowedFields = [
            'mix_master_id', 'format', 'quality', 'file_url', 'file_size',
            'duration', 'sample_rate', 'bit_rate', 'channels', 'export_settings',
            'download_count'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->getDb()->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * 删除导出记录
     */
    public function delete(int $id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->getDb()->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * 增加下载次数
     */
    public function incrementDownloadCount(int $id)
    {
        $sql = "UPDATE {$this->table} SET download_count = download_count + 1 WHERE id = :id";
        $stmt = $this->getDb()->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * 导出音频文件
     */
    public function exportAudio(int $projectId, array $params): array
    {
        $mixMasterModel = new AiMusicMixMaster();
        $mixMasterId = $params['mix_master_id'] ?? null;
        
        // 如果没有指定混音/母带ID，获取最新的
        if (!$mixMasterId) {
            $latestMix = $mixMasterModel->getLatestMix($projectId);
            $latestMaster = $mixMasterModel->getLatestMaster($projectId);
            
            // 优先使用母带，其次使用混音
            $mixMasterId = $latestMaster['id'] ?? $latestMix['id'] ?? null;
        }
        
        if (!$mixMasterId) {
            return ['error' => '没有找到可用的混音/母带文件'];
        }

        $format = $params['format'] ?? 'mp3';
        $quality = $params['quality'] ?? '320kbps';
        
        // 获取导出设置
        $exportSettings = $this->getExportSettings($format, $quality, $params);
        
        // 模拟导出过程
        $exportResult = $this->processExport($projectId, $mixMasterId, $exportSettings);
        
        if ($exportResult['success']) {
            // 保存导出记录
            $exportData = [
                'project_id' => $projectId,
                'mix_master_id' => $mixMasterId,
                'format' => $format,
                'quality' => $quality,
                'file_url' => $exportResult['file_url'],
                'file_size' => $exportResult['file_size'],
                'duration' => $exportResult['duration'],
                'sample_rate' => $exportResult['sample_rate'],
                'bit_rate' => $exportResult['bit_rate'],
                'channels' => $exportResult['channels'],
                'export_settings' => json_encode($exportSettings)
            ];
            
            if ($this->create($exportData)) {
                $exportId = $this->getDb()->lastInsertId();
                return [
                    'success' => true,
                    'export_id' => $exportId,
                    'file_url' => $exportResult['file_url'],
                    'file_size' => $exportResult['file_size']
                ];
            }
        }
        
        return ['error' => '导出失败'];
    }

    /**
     * 获取导出设置
     */
    private function getExportSettings(string $format, string $quality, array $params): array
    {
        $settings = [
            'format' => $format,
            'quality' => $quality,
            'normalize' => $params['normalize'] ?? true,
            'fade_in' => $params['fade_in'] ?? 0,
            'fade_out' => $params['fade_out'] ?? 0,
            'trim_start' => $params['trim_start'] ?? 0,
            'trim_end' => $params['trim_end'] ?? 0
        ];

        // 根据格式设置具体参数
        switch ($format) {
            case 'mp3':
                $settings['codec'] = 'mp3';
                $settings['bit_rate'] = $this->getMp3BitRate($quality);
                $settings['sample_rate'] = 44100;
                break;
                
            case 'wav':
                $settings['codec'] = 'pcm';
                $settings['bit_depth'] = 16;
                $settings['sample_rate'] = 44100;
                break;
                
            case 'flac':
                $settings['codec'] = 'flac';
                $settings['compression_level'] = $this->getFlacCompressionLevel($quality);
                $settings['sample_rate'] = 44100;
                break;
                
            case 'aac':
                $settings['codec'] = 'aac';
                $settings['bit_rate'] = $this->getAacBitRate($quality);
                $settings['sample_rate'] = 44100;
                break;
        }

        return $settings;
    }

    /**
     * 获取MP3比特率
     */
    private function getMp3BitRate(string $quality): int
    {
        $bitRates = [
            '128kbps' => 128,
            '192kbps' => 192,
            '256kbps' => 256,
            '320kbps' => 320
        ];
        
        return $bitRates[$quality] ?? 320;
    }

    /**
     * 获取FLAC压缩级别
     */
    private function getFlacCompressionLevel(string $quality): int
    {
        $levels = [
            'fast' => 0,
            'medium' => 5,
            'high' => 8
        ];
        
        return $levels[$quality] ?? 5;
    }

    /**
     * 获取AAC比特率
     */
    private function getAacBitRate(string $quality): int
    {
        $bitRates = [
            '128kbps' => 128,
            '192kbps' => 192,
            '256kbps' => 256,
            '320kbps' => 320
        ];
        
        return $bitRates[$quality] ?? 256;
    }

    /**
     * 处理导出
     */
    private function processExport(int $projectId, int $mixMasterId, array $settings): array
    {
        // 这里应该调用音频处理库进行实际导出
        // 暂时返回模拟数据
        
        $mixMasterModel = new AiMusicMixMaster();
        $mixMaster = $mixMasterModel->getById($mixMasterId);
        
        if (!$mixMaster) {
            return ['success' => false, 'error' => '混音/母带文件不存在'];
        }

        // 模拟文件生成
        $fileName = "export_{$projectId}_{$mixMasterId}_{$settings['format']}_{$settings['quality']}_" . time();
        $fileUrl = "/exports/audio/{$fileName}.{$settings['format']}";
        
        // 模拟文件大小计算
        $duration = 180; // 3分钟
        $fileSize = $this->calculateFileSize($duration, $settings);
        
        return [
            'success' => true,
            'file_url' => $fileUrl,
            'file_size' => $fileSize,
            'duration' => $duration,
            'sample_rate' => $settings['sample_rate'],
            'bit_rate' => $settings['bit_rate'] ?? null,
            'channels' => 2
        ];
    }

    /**
     * 计算文件大小
     */
    private function calculateFileSize(int $duration, array $settings): int
    {
        $format = $settings['format'];
        
        switch ($format) {
            case 'mp3':
            case 'aac':
                $bitRate = $settings['bit_rate'] ?? 320;
                return (int)(($duration * $bitRate * 1000) / 8);
                
            case 'wav':
                $sampleRate = $settings['sample_rate'] ?? 44100;
                $bitDepth = $settings['bit_depth'] ?? 16;
                $channels = 2;
                return (int)($duration * $sampleRate * $bitDepth * $channels / 8);
                
            case 'flac':
                // FLAC文件大小约为WAV的50-70%
                $sampleRate = $settings['sample_rate'] ?? 44100;
                $bitDepth = $settings['bit_depth'] ?? 16;
                $channels = 2;
                $wavSize = $duration * $sampleRate * $bitDepth * $channels / 8;
                return (int)($wavSize * 0.6);
                
            default:
                return 0;
        }
    }

    /**
     * 批量导出
     */
    public function batchExport(int $projectId, array $formats): array
    {
        $results = [];
        
        foreach ($formats as $formatConfig) {
            $exportResult = $this->exportAudio($projectId, $formatConfig);
            $results[] = $exportResult;
        }
        
        return $results;
    }

    /**
     * 获取支持的格式
     */
    public function getSupportedFormats(): array
    {
        return [
            'mp3' => [
                'name' => 'MP3',
                'description' => '通用音频格式，文件较小',
                'qualities' => ['128kbps', '192kbps', '256kbps', '320kbps'],
                'default_quality' => '320kbps'
            ],
            'wav' => [
                'name' => 'WAV',
                'description' => '无损音频格式，文件较大',
                'qualities' => ['16bit', '24bit', '32bit'],
                'default_quality' => '16bit'
            ],
            'flac' => [
                'name' => 'FLAC',
                'description' => '无损压缩格式，文件适中',
                'qualities' => ['fast', 'medium', 'high'],
                'default_quality' => 'medium'
            ],
            'aac' => [
                'name' => 'AAC',
                'description' => '高效音频格式，质量较好',
                'qualities' => ['128kbps', '192kbps', '256kbps', '320kbps'],
                'default_quality' => '256kbps'
            ]
        ];
    }

    /**
     * 获取导出统计
     */
    public function getExportStats(int $projectId = null): array
    {
        $whereClause = "";
        $params = [];
        
        if ($projectId) {
            $whereClause = "WHERE project_id = :project_id";
            $params[':project_id'] = $projectId;
        }
        
        $sql = "SELECT 
                    format,
                    quality,
                    COUNT(*) as count,
                    SUM(file_size) as total_size,
                    AVG(file_size) as avg_size,
                    SUM(download_count) as total_downloads
                FROM {$this->table}
                {$whereClause}
                GROUP BY format, quality
                ORDER BY count DESC";
        
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取用户导出历史
     */
    public function getUserExportHistory(int $userId, int $page = 1, int $limit = 20)
    {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT e.*, p.title as project_title
                FROM {$this->table} e
                LEFT JOIN ai_music_project p ON e.project_id = p.id
                WHERE p.user_id = :user_id
                ORDER BY e.created_at DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->getDb()->prepare($sql);
        $stmt->bindValue(':user_id', $userId);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取总数
     */
    public function getTotalCount(int $projectId = null, string $format = null): int
    {
        $whereClause = "";
        $params = [];
        
        if ($projectId) {
            $whereClause .= " WHERE project_id = :project_id";
            $params[':project_id'] = $projectId;
        }
        
        if ($format) {
            $whereClause .= ($whereClause ? " AND" : " WHERE") . " format = :format";
            $params[':format'] = $format;
        }
        
        $sql = "SELECT COUNT(*) FROM {$this->table}{$whereClause}";
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute($params);
        
        return (int)$stmt->fetchColumn();
    }
}