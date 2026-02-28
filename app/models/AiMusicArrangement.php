<?php

namespace App\Models;

use app\services\Database;
use PDO;
use Exception;

class AiMusicArrangement
{
    private $table = 'ai_music_arrangement';

    private function getDb(): PDO
    {
        return Database::pdo();
    }

    /**
     * 创建编曲
     */
    public function create(array $data)
    {
        $sql = "INSERT INTO {$this->table} (
            project_id, arrangement_data, style, instrument_config, chord_progression,
            rhythm_pattern, density, is_ai_generated, generation_parameters
        ) VALUES (
            :project_id, :arrangement_data, :style, :instrument_config, :chord_progression,
            :rhythm_pattern, :density, :is_ai_generated, :generation_parameters
        )";
        
        $stmt = $this->getDb()->prepare($sql);
        $result = $stmt->execute([
            ':project_id' => $data['project_id'],
            ':arrangement_data' => isset($data['arrangement_data']) ? (is_string($data['arrangement_data']) ? $data['arrangement_data'] : json_encode($data['arrangement_data'])) : null,
            ':style' => $data['style'] ?? null,
            ':instrument_config' => isset($data['instrument_config']) ? (is_string($data['instrument_config']) ? $data['instrument_config'] : json_encode($data['instrument_config'])) : null,
            ':chord_progression' => isset($data['chord_progression']) ? (is_string($data['chord_progression']) ? $data['chord_progression'] : json_encode($data['chord_progression'])) : null,
            ':rhythm_pattern' => isset($data['rhythm_pattern']) ? (is_string($data['rhythm_pattern']) ? $data['rhythm_pattern'] : json_encode($data['rhythm_pattern'])) : null,
            ':density' => $data['density'] ?? 'medium',
            ':is_ai_generated' => $data['is_ai_generated'] ?? 0,
            ':generation_parameters' => isset($data['generation_parameters']) ? (is_string($data['generation_parameters']) ? $data['generation_parameters'] : json_encode($data['generation_parameters'])) : null,
        ]);
        
        return $result ? $this->getDb()->lastInsertId() : false;
    }

    /**
     * 根据ID获取编曲
     */
    public function findById(int $id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 根据项目ID获取编曲列表
     */
    public function getByProjectId(int $projectId)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE project_id = :project_id 
                ORDER BY created_at DESC";
        
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':project_id' => $projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取项目的默认编曲
     */
    public function getDefaultArrangement(int $projectId)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE project_id = :project_id 
                ORDER BY is_ai_generated DESC, created_at DESC 
                LIMIT 1";
        
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':project_id' => $projectId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 更新编曲
     */
    public function update(int $id, array $data)
    {
        $fields = [];
        $params = [':id' => $id];
        
        $allowedFields = [
            'arrangement_data', 'style', 'instrument_config', 'chord_progression',
            'rhythm_pattern', 'density', 'generation_parameters'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "{$field} = :{$field}";
                if (in_array($field, ['arrangement_data', 'instrument_config', 'chord_progression', 'rhythm_pattern', 'generation_parameters'])) {
                    $params[":{$field}"] = is_string($data[$field]) ? $data[$field] : json_encode($data[$field]);
                } else {
                    $params[":{$field}"] = $data[$field];
                }
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
     * 删除编曲
     */
    public function delete(int $id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->getDb()->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * 解析编曲数据
     */
    public function parseArrangementData(int $id): ?array
    {
        $arrangement = $this->findById($id);
        if (!$arrangement || !$arrangement['arrangement_data']) {
            return null;
        }
        
        return json_decode($arrangement['arrangement_data'], true);
    }

    /**
     * 解析乐器配置
     */
    public function parseInstrumentConfig(int $id): ?array
    {
        $arrangement = $this->findById($id);
        if (!$arrangement || !$arrangement['instrument_config']) {
            return null;
        }
        
        return json_decode($arrangement['instrument_config'], true);
    }

    /**
     * 解析和弦进行
     */
    public function parseChordProgression(int $id): ?array
    {
        $arrangement = $this->findById($id);
        if (!$arrangement || !$arrangement['chord_progression']) {
            return null;
        }
        
        return json_decode($arrangement['chord_progression'], true);
    }

    /**
     * 解析节奏型
     */
    public function parseRhythmPattern(int $id): ?array
    {
        $arrangement = $this->findById($id);
        if (!$arrangement || !$arrangement['rhythm_pattern']) {
            return null;
        }
        
        return json_decode($arrangement['rhythm_pattern'], true);
    }

    /**
     * 获取编曲统计信息
     */
    public function getArrangementStats(int $projectId): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_count,
                    SUM(CASE WHEN is_ai_generated = 1 THEN 1 ELSE 0 END) as ai_generated_count,
                    COUNT(DISTINCT style) as style_count
                FROM {$this->table}
                WHERE project_id = :project_id";
        
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':project_id' => $projectId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
