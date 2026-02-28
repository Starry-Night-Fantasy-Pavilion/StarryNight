<?php

namespace App\Models;

use app\services\Database;
use PDO;
use Exception;

class AiMusicMelody
{
    private $table = 'ai_music_melody';

    private function getDb(): PDO
    {
        return Database::pdo();
    }

    /**
     * 创建旋律
     */
    public function create(array $data)
    {
        $sql = "INSERT INTO {$this->table} (
            project_id, midi_data, notation_data, tempo, key_signature, time_signature,
            melody_type, source_file, is_ai_generated, generation_parameters
        ) VALUES (
            :project_id, :midi_data, :notation_data, :tempo, :key_signature, :time_signature,
            :melody_type, :source_file, :is_ai_generated, :generation_parameters
        )";
        
        $stmt = $this->getDb()->prepare($sql);
        $result = $stmt->execute([
            ':project_id' => $data['project_id'],
            ':midi_data' => isset($data['midi_data']) ? (is_string($data['midi_data']) ? $data['midi_data'] : json_encode($data['midi_data'])) : null,
            ':notation_data' => isset($data['notation_data']) ? (is_string($data['notation_data']) ? $data['notation_data'] : json_encode($data['notation_data'])) : null,
            ':tempo' => $data['tempo'] ?? 120,
            ':key_signature' => $data['key_signature'] ?? 'C major',
            ':time_signature' => $data['time_signature'] ?? '4/4',
            ':melody_type' => $data['melody_type'] ?? 'generated',
            ':source_file' => $data['source_file'] ?? null,
            ':is_ai_generated' => $data['is_ai_generated'] ?? 0,
            ':generation_parameters' => isset($data['generation_parameters']) ? (is_string($data['generation_parameters']) ? $data['generation_parameters'] : json_encode($data['generation_parameters'])) : null,
        ]);
        
        return $result ? $this->getDb()->lastInsertId() : false;
    }

    /**
     * 根据ID获取旋律
     */
    public function findById(int $id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 根据项目ID获取旋律列表
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
     * 更新旋律
     */
    public function update(int $id, array $data)
    {
        $fields = [];
        $params = [':id' => $id];
        
        $allowedFields = [
            'midi_data', 'notation_data', 'tempo', 'key_signature', 
            'time_signature', 'melody_type', 'source_file', 'generation_parameters'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "{$field} = :{$field}";
                if (in_array($field, ['midi_data', 'notation_data', 'generation_parameters'])) {
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
     * 删除旋律
     */
    public function delete(int $id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->getDb()->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * 获取项目的默认旋律
     */
    public function getDefaultMelody(int $projectId)
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
     * 解析MIDI数据
     */
    public function parseMidiData(int $id): ?array
    {
        $melody = $this->findById($id);
        if (!$melody || !$melody['midi_data']) {
            return null;
        }
        
        return json_decode($melody['midi_data'], true);
    }

    /**
     * 解析乐谱数据
     */
    public function parseNotationData(int $id): ?array
    {
        $melody = $this->findById($id);
        if (!$melody || !$melody['notation_data']) {
            return null;
        }
        
        return json_decode($melody['notation_data'], true);
    }

    /**
     * 获取旋律统计信息
     */
    public function getMelodyStats(int $projectId): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_count,
                    SUM(CASE WHEN is_ai_generated = 1 THEN 1 ELSE 0 END) as ai_generated_count,
                    SUM(CASE WHEN melody_type = 'humming' THEN 1 ELSE 0 END) as humming_count,
                    SUM(CASE WHEN melody_type = 'manual' THEN 1 ELSE 0 END) as manual_count
                FROM {$this->table}
                WHERE project_id = :project_id";
        
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':project_id' => $projectId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
