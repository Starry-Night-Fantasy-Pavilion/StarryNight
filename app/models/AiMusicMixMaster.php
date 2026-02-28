<?php

namespace App\Models;

use app\services\Database;
use PDO;

class AiMusicMixMaster
{
    private $table = 'ai_music_mix_master';

    private function getDb(): PDO
    {
        return Database::pdo();
    }

    /**
     * 创建混音/母带记录
     */
    public function create(array $data)
    {
        $sql = "INSERT INTO {$this->table} (
            project_id, type, name, settings, output_audio_url, output_format, 
            output_quality, loudness, peak_level, is_ai_processed, processing_time
        ) VALUES (
            :project_id, :type, :name, :settings, :output_audio_url, :output_format,
            :output_quality, :loudness, :peak_level, :is_ai_processed, :processing_time
        )";
        
        $stmt = $this->getDb()->prepare($sql);
        return $stmt->execute([
            ':project_id' => $data['project_id'],
            ':type' => $data['type'],
            ':name' => $data['name'],
            ':settings' => $data['settings'] ?? null,
            ':output_audio_url' => $data['output_audio_url'] ?? null,
            ':output_format' => $data['output_format'] ?? 'wav',
            ':output_quality' => $data['output_quality'] ?? 'high',
            ':loudness' => $data['loudness'] ?? null,
            ':peak_level' => $data['peak_level'] ?? null,
            ':is_ai_processed' => $data['is_ai_processed'] ?? 0,
            ':processing_time' => $data['processing_time'] ?? null
        ]);
    }

    /**
     * 获取混音/母带详情
     */
    public function getById(int $id)
    {
        $sql = "SELECT m.*, p.title as project_title, p.user_id
                FROM {$this->table} m
                LEFT JOIN ai_music_project p ON m.project_id = p.id
                WHERE m.id = :id";
        
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 根据项目ID获取混音/母带列表
     */
    public function getByProjectId(int $projectId, string $type = null)
    {
        $whereClause = "WHERE project_id = :project_id";
        $params = [':project_id' => $projectId];
        
        if ($type) {
            $whereClause .= " AND type = :type";
            $params[':type'] = $type;
        }
        
        $sql = "SELECT * FROM {$this->table} 
                {$whereClause}
                ORDER BY created_at DESC";
        
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取项目的最新混音
     */
    public function getLatestMix(int $projectId)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE project_id = :project_id AND type = 'mix'
                ORDER BY created_at DESC 
                LIMIT 1";
        
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':project_id' => $projectId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 获取项目的最新母带
     */
    public function getLatestMaster(int $projectId)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE project_id = :project_id AND type = 'master'
                ORDER BY created_at DESC 
                LIMIT 1";
        
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':project_id' => $projectId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 更新混音/母带记录
     */
    public function update(int $id, array $data)
    {
        $fields = [];
        $params = [':id' => $id];
        
        $allowedFields = [
            'name', 'settings', 'output_audio_url', 'output_format', 'output_quality',
            'loudness', 'peak_level', 'is_ai_processed', 'processing_time'
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
     * 删除混音/母带记录
     */
    public function delete(int $id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->getDb()->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * AI自动混音
     */
    public function autoMix(int $projectId, array $params = []): array
    {
        // 获取项目音轨
        $trackModel = new AiMusicTrack();
        $tracks = $trackModel->getMixSettings($projectId);
        
        if (empty($tracks)) {
            return ['error' => '项目没有音轨'];
        }

        // AI混音算法（简化实现）
        $mixSettings = $this->generateAIMixSettings($tracks, $params);
        
        // 保存混音设置
        $mixData = [
            'project_id' => $projectId,
            'type' => 'mix',
            'name' => 'AI自动混音',
            'settings' => json_encode($mixSettings),
            'is_ai_processed' => 1,
            'processing_time' => time()
        ];

        if ($this->create($mixData)) {
            $mixId = $this->getDb()->lastInsertId();
            
            // 应用混音设置到音轨
            $this->applyMixSettings($tracks, $mixSettings);
            
            return [
                'success' => true,
                'mix_id' => $mixId,
                'settings' => $mixSettings
            ];
        }

        return ['error' => '混音失败'];
    }

    /**
     * AI自动母带
     */
    public function autoMaster(int $projectId, array $params = []): array
    {
        // 获取最新混音
        $latestMix = $this->getLatestMix($projectId);
        if (!$latestMix) {
            return ['error' => '没有找到混音文件'];
        }

        // AI母带算法（简化实现）
        $masterSettings = $this->generateAIMasterSettings($latestMix, $params);
        
        // 保存母带设置
        $masterData = [
            'project_id' => $projectId,
            'type' => 'master',
            'name' => 'AI自动母带',
            'settings' => json_encode($masterSettings),
            'is_ai_processed' => 1,
            'processing_time' => time()
        ];

        if ($this->create($masterData)) {
            $masterId = $this->getDb()->lastInsertId();
            
            return [
                'success' => true,
                'master_id' => $masterId,
                'settings' => $masterSettings
            ];
        }

        return ['error' => '母带处理失败'];
    }

    /**
     * 生成AI混音设置
     */
    private function generateAIMixSettings(array $tracks, array $params): array
    {
        $style = $params['style'] ?? 'balanced';
        $targetLoudness = $params['target_loudness'] ?? -14;
        
        $mixSettings = [];
        
        foreach ($tracks as $track) {
            $trackSettings = [
                'volume' => $track['volume'],
                'pan' => $track['pan'],
                'effects' => $track['effects'] ?: []
            ];

            // 根据音轨类型调整设置
            switch ($track['type']) {
                case 'vocal':
                    $trackSettings['volume'] = $this->adjustVolume($trackSettings['volume'], -3, -6);
                    $trackSettings['effects'][] = [
                        'type' => 'eq',
                        'params' => [
                            'high_pass' => 80,
                            'presence' => 2,
                            'air' => 3
                        ]
                    ];
                    $trackSettings['effects'][] = [
                        'type' => 'compression',
                        'params' => [
                            'ratio' => 3,
                            'threshold' => -18,
                            'attack' => 0.003,
                            'release' => 0.1
                        ]
                    ];
                    break;
                    
                case 'drums':
                    $trackSettings['volume'] = $this->adjustVolume($trackSettings['volume'], -2, -4);
                    $trackSettings['effects'][] = [
                        'type' => 'eq',
                        'params' => [
                            'low_shelf' => 60,
                            'low_mid' => 200,
                            'high_mid' => 2000,
                            'high_shelf' => 8000
                        ]
                    ];
                    break;
                    
                case 'bass':
                    $trackSettings['volume'] = $this->adjustVolume($trackSettings['volume'], -1, -3);
                    $trackSettings['effects'][] = [
                        'type' => 'compression',
                        'params' => [
                            'ratio' => 4,
                            'threshold' => -20,
                            'attack' => 0.01,
                            'release' => 0.2
                        ]
                    ];
                    break;
                    
                case 'melody':
                    $trackSettings['volume'] = $this->adjustVolume($trackSettings['volume'], -5, -8);
                    $trackSettings['effects'][] = [
                        'type' => 'reverb',
                        'params' => [
                            'room_size' => 0.3,
                            'damping' => 0.5,
                            'wet_level' => 0.2
                        ]
                    ];
                    break;
            }

            $mixSettings[$track['id']] = $trackSettings;
        }

        // 添加总线设置
        $mixSettings['bus'] = [
            'master_volume' => 0,
            'master_loudness' => $targetLoudness,
            'master_eq' => [
                'low_shelf' => 60,
                'high_shelf' => 12000
            ],
            'master_compression' => [
                'ratio' => 2,
                'threshold' => -12,
                'attack' => 0.01,
                'release' => 0.5
            ],
            'master_limiter' => [
                'threshold' => -1,
                'release' => 0.1
            ]
        ];

        return $mixSettings;
    }

    /**
     * 生成AI母带设置
     */
    private function generateAIMasterSettings(array $mix, array $params): array
    {
        $style = $params['style'] ?? 'balanced';
        $targetLoudness = $params['target_loudness'] ?? -14;
        
        $masterSettings = [
            'target_loudness' => $targetLoudness,
            'target_peak' => -1.0,
            'eq' => [],
            'compression' => [],
            'limiter' => [],
            'stereo_enhancement' => []
        ];

        // 根据风格调整设置
        switch ($style) {
            case 'warm':
                $masterSettings['eq'] = [
                    'low_shelf' => ['frequency' => 100, 'gain' => 2],
                    'high_shelf' => ['frequency' => 10000, 'gain' => -1]
                ];
                $masterSettings['compression'] = [
                    'ratio' => 2.5,
                    'threshold' => -16,
                    'attack' => 0.02,
                    'release' => 0.3
                ];
                break;
                
            case 'bright':
                $masterSettings['eq'] = [
                    'high_shelf' => ['frequency' => 8000, 'gain' => 3],
                    'air' => ['frequency' => 12000, 'gain' => 2]
                ];
                $masterSettings['compression'] = [
                    'ratio' => 3,
                    'threshold' => -14,
                    'attack' => 0.01,
                    'release' => 0.2
                ];
                break;
                
            case 'punchy':
                $masterSettings['eq'] = [
                    'low_mid' => ['frequency' => 200, 'gain' => 2],
                    'high_mid' => ['frequency' => 3000, 'gain' => 1]
                ];
                $masterSettings['compression'] = [
                    'ratio' => 4,
                    'threshold' => -12,
                    'attack' => 0.005,
                    'release' => 0.1
                ];
                break;
                
            default: // balanced
                $masterSettings['eq'] = [
                    'low_shelf' => ['frequency' => 80, 'gain' => 1],
                    'high_shelf' => ['frequency' => 10000, 'gain' => 1]
                ];
                $masterSettings['compression'] = [
                    'ratio' => 2,
                    'threshold' => -14,
                    'attack' => 0.01,
                    'release' => 0.3
                ];
        }

        $masterSettings['limiter'] = [
            'threshold' => -1.0,
            'release' => 0.1,
            'lookahead' => 0.005
        ];

        $masterSettings['stereo_enhancement'] = [
            'width' => 1.1,
            'mid_solo' => false
        ];

        return $masterSettings;
    }

    /**
     * 应用混音设置到音轨
     */
    private function applyMixSettings(array $tracks, array $mixSettings): void
    {
        $trackModel = new AiMusicTrack();
        
        foreach ($tracks as $track) {
            if (isset($mixSettings[$track['id']])) {
                $settings = $mixSettings[$track['id']];
                
                $trackModel->update($track['id'], [
                    'volume' => $settings['volume'],
                    'pan' => $settings['pan'],
                    'effects' => $settings['effects']
                ]);
            }
        }
    }

    /**
     * 调整音量
     */
    private function adjustVolume(float $currentVolume, float $minReduction, float $maxReduction): float
    {
        $reduction = rand($minReduction * 10, $maxReduction * 10) / 10;
        return max(-100, min(100, $currentVolume - $reduction));
    }

    /**
     * 分析音频质量
     */
    public function analyzeAudioQuality(string $audioUrl): array
    {
        // 这里应该使用音频分析库
        // 暂时返回模拟数据
        return [
            'loudness' => -14.5,
            'peak_level' => -1.2,
            'dynamic_range' => 12.3,
            'frequency_balance' => [
                'low' => 0.3,
                'mid' => 0.4,
                'high' => 0.3
            ],
            'stereo_width' => 1.1,
            'thd' => 0.001,
            'snr' => 75.2
        ];
    }

    /**
     * 比较两个版本的差异
     */
    public function compareVersions(int $version1Id, int $version2Id): array
    {
        $version1 = $this->getById($version1Id);
        $version2 = $this->getById($version2Id);
        
        if (!$version1 || !$version2) {
            return ['error' => '版本不存在'];
        }

        $comparison = [
            'version1' => [
                'id' => $version1['id'],
                'name' => $version1['name'],
                'created_at' => $version1['created_at'],
                'loudness' => $version1['loudness'],
                'peak_level' => $version1['peak_level']
            ],
            'version2' => [
                'id' => $version2['id'],
                'name' => $version2['name'],
                'created_at' => $version2['created_at'],
                'loudness' => $version2['loudness'],
                'peak_level' => $version2['peak_level']
            ],
            'differences' => []
        ];

        // 比较响度差异
        if ($version1['loudness'] && $version2['loudness']) {
            $loudnessDiff = $version2['loudness'] - $version1['loudness'];
            $comparison['differences']['loudness'] = [
                'value' => $loudnessDiff,
                'description' => $loudnessDiff > 0 ? '更响' : '更安静'
            ];
        }

        // 比较峰值差异
        if ($version1['peak_level'] && $version2['peak_level']) {
            $peakDiff = $version2['peak_level'] - $version1['peak_level'];
            $comparison['differences']['peak_level'] = [
                'value' => $peakDiff,
                'description' => $peakDiff > 0 ? '峰值更高' : '峰值更低'
            ];
        }

        return $comparison;
    }

    /**
     * 获取混音/母带统计
     */
    public function getStats(int $projectId): array
    {
        $sql = "SELECT 
                    type,
                    COUNT(*) as count,
                    AVG(processing_time) as avg_processing_time,
                    MAX(created_at) as latest_created
                FROM {$this->table}
                WHERE project_id = :project_id
                GROUP BY type";
        
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':project_id' => $projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取总数
     */
    public function getTotalCount(int $projectId = null, string $type = null): int
    {
        $whereClause = "";
        $params = [];
        
        if ($projectId) {
            $whereClause .= " WHERE project_id = :project_id";
            $params[':project_id'] = $projectId;
        }
        
        if ($type) {
            $whereClause .= ($whereClause ? " AND" : " WHERE") . " type = :type";
            $params[':type'] = $type;
        }
        
        $sql = "SELECT COUNT(*) FROM {$this->table}{$whereClause}";
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute($params);
        
        return (int)$stmt->fetchColumn();
    }
}