<?php

namespace App\Models;

use App\Services\Database;
use PDO;
use Exception;

class AiMusicTrack
{
    private $table = 'ai_music_track';

    private function getDb(): PDO
    {
        return Database::pdo();
    }

    /**
     * 创建音轨
     */
    public function create(array $data)
    {
        $sql = "INSERT INTO {$this->table} (
            project_id, name, type, instrument, audio_url, midi_data, waveform_data,
            volume, pan, mute, solo, effects, automation, color, height, position
        ) VALUES (
            :project_id, :name, :type, :instrument, :audio_url, :midi_data, :waveform_data,
            :volume, :pan, :mute, :solo, :effects, :automation, :color, :height, :position
        )";
        
        $stmt = $this->getDb()->prepare($sql);
        return $stmt->execute([
            ':project_id' => $data['project_id'],
            ':name' => $data['name'],
            ':type' => $data['type'],
            ':instrument' => $data['instrument'] ?? null,
            ':audio_url' => $data['audio_url'] ?? null,
            ':midi_data' => $data['midi_data'] ?? null,
            ':waveform_data' => $data['waveform_data'] ?? null,
            ':volume' => $data['volume'] ?? 0.00,
            ':pan' => $data['pan'] ?? 0.00,
            ':mute' => $data['mute'] ?? 0,
            ':solo' => $data['solo'] ?? 0,
            ':effects' => $data['effects'] ?? null,
            ':automation' => $data['automation'] ?? null,
            ':color' => $data['color'] ?? null,
            ':height' => $data['height'] ?? 100,
            ':position' => $data['position'] ?? 0
        ]);
    }

    /**
     * 获取音轨详情
     */
    public function getById(int $id)
    {
        $sql = "SELECT t.*, p.title as project_title, p.user_id
                FROM {$this->table} t
                LEFT JOIN ai_music_project p ON t.project_id = p.id
                WHERE t.id = :id";
        
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 根据项目ID获取音轨列表
     */
    public function getByProjectId(int $projectId)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE project_id = :project_id 
                ORDER BY position ASC, created_at ASC";
        
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':project_id' => $projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 根据类型获取音轨
     */
    public function getByType(int $projectId, string $type)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE project_id = :project_id AND type = :type
                ORDER BY position ASC";
        
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([
            ':project_id' => $projectId,
            ':type' => $type
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 更新音轨
     */
    public function update(int $id, array $data)
    {
        $fields = [];
        $params = [':id' => $id];
        
        $allowedFields = [
            'name', 'type', 'instrument', 'audio_url', 'midi_data', 'waveform_data',
            'volume', 'pan', 'mute', 'solo', 'effects', 'automation', 'color', 'height', 'position'
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
     * 更新音轨位置
     */
    public function updatePosition(int $id, int $position)
    {
        $sql = "UPDATE {$this->table} SET position = :position WHERE id = :id";
        $stmt = $this->getDb()->prepare($sql);
        return $stmt->execute([
            ':position' => $position,
            ':id' => $id
        ]);
    }

    /**
     * 批量更新音轨位置
     */
    public function updatePositions(array $trackPositions)
    {
        $this->getDb()->beginTransaction();
        
        try {
            foreach ($trackPositions as $trackId => $position) {
                $sql = "UPDATE {$this->table} SET position = :position WHERE id = :id";
                $stmt = $this->getDb()->prepare($sql);
                $stmt->execute([
                    ':position' => $position,
                    ':id' => $trackId
                ]);
            }
            
            $this->getDb()->commit();
            return true;
        } catch (Exception $e) {
            $this->getDb()->rollback();
            return false;
        }
    }

    /**
     * 删除音轨
     */
    public function delete(int $id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->getDb()->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * 复制音轨
     */
    public function duplicate(int $trackId, string $newName = null): ?int
    {
        $originalTrack = $this->getById($trackId);
        if (!$originalTrack) {
            return null;
        }

        $newTrackData = $originalTrack;
        unset($newTrackData['id']);
        unset($newTrackData['created_at']);
        unset($newTrackData['updated_at']);
        
        $newTrackData['name'] = $newName ?? ($originalTrack['name'] . ' (副本)');
        $newTrackData['position'] = $this->getNextPosition($originalTrack['project_id']);

        if ($this->create($newTrackData)) {
            return $this->getDb()->lastInsertId();
        }

        return null;
    }

    /**
     * 获取下一个位置序号
     */
    public function getNextPosition(int $projectId): int
    {
        $sql = "SELECT MAX(position) FROM {$this->table} WHERE project_id = :project_id";
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':project_id' => $projectId]);
        $maxPosition = $stmt->fetchColumn();
        
        return ($maxPosition !== false) ? (int)$maxPosition + 1 : 0;
    }

    /**
     * 设置音轨静音状态
     */
    public function setMute(int $id, bool $mute)
    {
        $sql = "UPDATE {$this->table} SET mute = :mute WHERE id = :id";
        $stmt = $this->getDb()->prepare($sql);
        return $stmt->execute([
            ':mute' => $mute ? 1 : 0,
            ':id' => $id
        ]);
    }

    /**
     * 设置音轨独奏状态
     */
    public function setSolo(int $id, bool $solo)
    {
        $sql = "UPDATE {$this->table} SET solo = :solo WHERE id = :id";
        $stmt = $this->getDb()->prepare($sql);
        return $stmt->execute([
            ':solo' => $solo ? 1 : 0,
            ':id' => $id
        ]);
    }

    /**
     * 清除项目所有音轨的独奏状态
     */
    public function clearAllSolo(int $projectId)
    {
        $sql = "UPDATE {$this->table} SET solo = 0 WHERE project_id = :project_id";
        $stmt = $this->getDb()->prepare($sql);
        return $stmt->execute([':project_id' => $projectId]);
    }

    /**
     * 更新音轨音量
     */
    public function updateVolume(int $id, float $volume)
    {
        $sql = "UPDATE {$this->table} SET volume = :volume WHERE id = :id";
        $stmt = $this->getDb()->prepare($sql);
        return $stmt->execute([
            ':volume' => $volume,
            ':id' => $id
        ]);
    }

    /**
     * 更新音轨声像
     */
    public function updatePan(int $id, float $pan)
    {
        $sql = "UPDATE {$this->table} SET pan = :pan WHERE id = :id";
        $stmt = $this->getDb()->prepare($sql);
        return $stmt->execute([
            ':pan' => $pan,
            ':id' => $id
        ]);
    }

    /**
     * 更新音轨效果器
     */
    public function updateEffects(int $id, array $effects)
    {
        $sql = "UPDATE {$this->table} SET effects = :effects WHERE id = :id";
        $stmt = $this->getDb()->prepare($sql);
        return $stmt->execute([
            ':effects' => json_encode($effects),
            ':id' => $id
        ]);
    }

    /**
     * 更新音轨自动化
     */
    public function updateAutomation(int $id, array $automation)
    {
        $sql = "UPDATE {$this->table} SET automation = :automation WHERE id = :id";
        $stmt = $this->getDb()->prepare($sql);
        return $stmt->execute([
            ':automation' => json_encode($automation),
            ':id' => $id
        ]);
    }

    /**
     * 获取音轨波形数据
     */
    public function getWaveformData(int $id): ?array
    {
        $sql = "SELECT waveform_data FROM {$this->table} WHERE id = :id";
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? json_decode($result['waveform_data'], true) : null;
    }

    /**
     * 生成波形数据
     */
    public function generateWaveformData(string $audioPath): array
    {
        // 这里应该使用音频处理库生成波形数据
        // 暂时返回模拟数据
        $waveformData = [];
        $samples = 1000; // 采样点数
        
        for ($i = 0; $i < $samples; $i++) {
            $waveformData[] = [
                'time' => $i / $samples,
                'amplitude' => sin($i * 0.1) * 0.5 + sin($i * 0.05) * 0.3
            ];
        }
        
        return $waveformData;
    }

    /**
     * AI音轨分离
     */
    public function separateTracks(int $projectId, string $sourceAudioUrl): array
    {
        // 这里应该调用AI音轨分离服务
        // 暂时返回模拟数据
        $separatedTracks = [
            [
                'type' => 'vocal',
                'name' => '人声',
                'instrument' => 'vocal',
                'audio_url' => $sourceAudioUrl . '_vocal.wav'
            ],
            [
                'type' => 'drums',
                'name' => '鼓组',
                'instrument' => 'drums',
                'audio_url' => $sourceAudioUrl . '_drums.wav'
            ],
            [
                'type' => 'bass',
                'name' => '贝斯',
                'instrument' => 'bass',
                'audio_url' => $sourceAudioUrl . '_bass.wav'
            ],
            [
                'type' => 'melody',
                'name' => '旋律',
                'instrument' => 'piano',
                'audio_url' => $sourceAudioUrl . '_melody.wav'
            ]
        ];

        $createdTracks = [];
        foreach ($separatedTracks as $trackData) {
            $trackData['project_id'] = $projectId;
            $trackData['position'] = $this->getNextPosition($projectId);
            
            if ($this->create($trackData)) {
                $trackData['id'] = $this->getDb()->lastInsertId();
                $createdTracks[] = $trackData;
            }
        }

        return $createdTracks;
    }

    /**
     * 获取项目音轨统计
     */
    public function getProjectTrackStats(int $projectId): array
    {
        $sql = "SELECT 
                    type,
                    COUNT(*) as count,
                    SUM(CASE WHEN mute = 1 THEN 1 ELSE 0 END) as muted_count,
                    SUM(CASE WHEN solo = 1 THEN 1 ELSE 0 END) as solo_count
                FROM {$this->table}
                WHERE project_id = :project_id
                GROUP BY type";
        
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':project_id' => $projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取音轨混合设置
     */
    public function getMixSettings(int $projectId): array
    {
        $sql = "SELECT id, name, type, volume, pan, mute, solo, effects
                FROM {$this->table}
                WHERE project_id = :project_id
                ORDER BY position ASC";
        
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':project_id' => $projectId]);
        $tracks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 解析JSON字段
        foreach ($tracks as &$track) {
            $track['effects'] = $track['effects'] ? json_decode($track['effects'], true) : [];
        }
        
        return $tracks;
    }

    /**
     * 搜索音轨
     */
    public function search(string $keyword, int $page = 1, int $limit = 20)
    {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT t.*, p.title as project_title, p.user_id, u.username
                FROM {$this->table} t
                LEFT JOIN ai_music_project p ON t.project_id = p.id
                LEFT JOIN user u ON p.user_id = u.id
                WHERE p.is_public = 1 AND p.status = 3
                  AND (t.name LIKE :keyword OR t.instrument LIKE :keyword)
                ORDER BY t.created_at DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->getDb()->prepare($sql);
        $searchTerm = "%{$keyword}%";
        $stmt->bindValue(':keyword', $searchTerm);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取音轨总数
     */
    public function getTotalCount(int $projectId = null): int
    {
        $whereClause = "";
        $params = [];
        
        if ($projectId) {
            $whereClause = "WHERE project_id = :project_id";
            $params[':project_id'] = $projectId;
        }
        
        $sql = "SELECT COUNT(*) FROM {$this->table} {$whereClause}";
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute($params);
        
        return (int)$stmt->fetchColumn();
    }

    /**
     * 获取按类型分组的音轨数量
     */
    public function getCountByType(int $projectId): array
    {
        $sql = "SELECT type, COUNT(*) as count 
                FROM {$this->table} 
                WHERE project_id = :project_id 
                GROUP BY type";
        
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':project_id' => $projectId]);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}