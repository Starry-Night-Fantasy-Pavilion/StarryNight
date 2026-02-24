<?php

namespace App\Models;

use App\Services\Database;
use PDO;

class AiMusicLyrics
{
    private $table = 'ai_music_lyrics';

    private function getDb(): PDO
    {
        return Database::pdo();
    }

    /**
     * 创建歌词
     */
    public function create(array $data)
    {
        $sql = "INSERT INTO {$this->table} (
            project_id, content, emotion_analysis, structure, rhyme_scheme, 
            syllable_count, language, is_ai_generated, generation_prompt
        ) VALUES (
            :project_id, :content, :emotion_analysis, :structure, :rhyme_scheme,
            :syllable_count, :language, :is_ai_generated, :generation_prompt
        )";
        
        $stmt = $this->getDb()->prepare($sql);
        return $stmt->execute([
            ':project_id' => $data['project_id'],
            ':content' => $data['content'],
            ':emotion_analysis' => $data['emotion_analysis'] ?? null,
            ':structure' => $data['structure'] ?? null,
            ':rhyme_scheme' => $data['rhyme_scheme'] ?? null,
            ':syllable_count' => $data['syllable_count'] ?? null,
            ':language' => $data['language'] ?? 'zh-CN',
            ':is_ai_generated' => $data['is_ai_generated'] ?? 0,
            ':generation_prompt' => $data['generation_prompt'] ?? null
        ]);
    }

    /**
     * 获取歌词详情
     */
    public function getById(int $id)
    {
        $sql = "SELECT l.*, p.title as project_title, p.user_id
                FROM {$this->table} l
                LEFT JOIN ai_music_project p ON l.project_id = p.id
                WHERE l.id = :id";
        
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 根据项目ID获取歌词
     */
    public function getByProjectId(int $projectId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE project_id = :project_id ORDER BY created_at DESC";
        
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':project_id' => $projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取项目的主要歌词
     */
    public function getMainLyricsByProjectId(int $projectId)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE project_id = :project_id 
                ORDER BY created_at DESC 
                LIMIT 1";
        
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':project_id' => $projectId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 更新歌词
     */
    public function update(int $id, array $data)
    {
        $fields = [];
        $params = [':id' => $id];
        
        $allowedFields = [
            'content', 'emotion_analysis', 'structure', 'rhyme_scheme',
            'syllable_count', 'language', 'is_ai_generated', 'generation_prompt'
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
     * 删除歌词
     */
    public function delete(int $id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->getDb()->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * 分析歌词情感
     */
    public function analyzeEmotion(string $content): array
    {
        // 这里可以集成AI服务进行情感分析
        // 暂时返回模拟数据
        $emotions = [
            'happy' => 0.3,
            'sad' => 0.1,
            'angry' => 0.05,
            'fear' => 0.05,
            'surprise' => 0.1,
            'disgust' => 0.05,
            'neutral' => 0.35
        ];

        // 简单的关键词分析
        $happyKeywords = ['快乐', '开心', '幸福', '欢乐', '愉快', '喜悦'];
        $sadKeywords = ['悲伤', '难过', '伤心', '痛苦', '忧郁', '哀伤'];
        $angryKeywords = ['愤怒', '生气', '恼火', '愤恨', '怒火', '暴怒'];
        
        foreach ($happyKeywords as $keyword) {
            if (strpos($content, $keyword) !== false) {
                $emotions['happy'] += 0.1;
                $emotions['neutral'] -= 0.05;
            }
        }
        
        foreach ($sadKeywords as $keyword) {
            if (strpos($content, $keyword) !== false) {
                $emotions['sad'] += 0.1;
                $emotions['neutral'] -= 0.05;
            }
        }
        
        foreach ($angryKeywords as $keyword) {
            if (strpos($content, $keyword) !== false) {
                $emotions['angry'] += 0.1;
                $emotions['neutral'] -= 0.05;
            }
        }

        // 归一化
        $total = array_sum($emotions);
        foreach ($emotions as $key => $value) {
            $emotions[$key] = round($value / $total, 3);
        }

        // 获取主要情感
        $primaryEmotion = array_keys($emotions, max($emotions))[0];

        return [
            'emotions' => $emotions,
            'primary_emotion' => $primaryEmotion,
            'valence' => $emotions['happy'] + $emotions['surprise'] - $emotions['sad'] - $emotions['angry'] - $emotions['fear'],
            'arousal' => $emotions['angry'] + $emotions['fear'] + $emotions['surprise'] - $emotions['sad'],
            'dominance' => $emotions['happy'] + $emotions['angry'] - $emotions['fear'] - $emotions['sad']
        ];
    }

    /**
     * 分析歌词结构
     */
    public function analyzeStructure(string $content): array
    {
        $lines = explode("\n", trim($content));
        $structure = [];
        $currentSection = null;
        $sectionLines = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                if ($currentSection && !empty($sectionLines)) {
                    $structure[$currentSection] = $sectionLines;
                    $sectionLines = [];
                }
                continue;
            }

            // 检测段落标记
            if (preg_match('/^(主歌|verse|副歌|chorus|桥段|bridge|前奏|intro|结尾|outro)/i', $line, $matches)) {
                if ($currentSection && !empty($sectionLines)) {
                    $structure[$currentSection] = $sectionLines;
                }
                $currentSection = $matches[1];
                $sectionLines = [];
                continue;
            }

            if ($currentSection) {
                $sectionLines[] = $line;
            } else {
                // 如果没有明确的段落标记，默认为主歌
                if (!$currentSection) {
                    $currentSection = 'verse';
                }
                $sectionLines[] = $line;
            }
        }

        // 添加最后一个段落
        if ($currentSection && !empty($sectionLines)) {
            $structure[$currentSection] = $sectionLines;
        }

        // 如果没有检测到结构，按行数简单分段
        if (empty($structure)) {
            $totalLines = count($lines);
            if ($totalLines <= 8) {
                $structure['verse'] = $lines;
            } else {
                $mid = floor($totalLines / 2);
                $structure['verse'] = array_slice($lines, 0, $mid);
                $structure['chorus'] = array_slice($lines, $mid);
            }
        }

        return $structure;
    }

    /**
     * 分析韵律
     */
    public function analyzeRhyme(string $content): array
    {
        $lines = explode("\n", trim($content));
        $rhymeScheme = [];
        $rhymeGroups = [];
        $currentScheme = 'A';

        foreach ($lines as $index => $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // 获取每行的最后一个字（简化处理）
            $lastChar = mb_substr($line, -1);
            $pinyin = $this->getPinyin($lastChar);
            
            if (empty($pinyin)) {
                $rhymeScheme[] = '-';
                continue;
            }

            // 获取韵母（简化处理）
            $rhyme = $this->getRhyme($pinyin);
            
            // 检查是否与之前的行押韵
            $foundRhyme = false;
            foreach ($rhymeGroups as $scheme => $existingRhyme) {
                if ($this->isRhyming($rhyme, $existingRhyme)) {
                    $rhymeScheme[] = $scheme;
                    $foundRhyme = true;
                    break;
                }
            }

            if (!$foundRhyme) {
                $rhymeScheme[] = $currentScheme;
                $rhymeGroups[$currentScheme] = $rhyme;
                $currentScheme = chr(ord($currentScheme) + 1);
                if ($currentScheme > 'Z') {
                    $currentScheme = 'A';
                }
            }
        }

        return [
            'scheme' => $rhymeScheme,
            'rhyme_groups' => $rhymeGroups,
            'rhyme_density' => $this->calculateRhymeDensity($rhymeScheme)
        ];
    }

    /**
     * 计算音节数
     */
    public function countSyllables(string $content): int
    {
        // 简化的中文音节计算
        $chars = preg_split('//u', $content, -1, PREG_SPLIT_NO_EMPTY);
        $syllableCount = 0;

        foreach ($chars as $char) {
            // 中文字符通常是一个音节
            if (preg_match('/[\x{4e00}-\x{9fff}]/u', $char)) {
                $syllableCount++;
            }
            // 英文单词的音节计算（简化）
            elseif (preg_match('/[a-zA-Z]/', $char)) {
                // 这里可以添加更复杂的英文音节计算逻辑
                $syllableCount += 0.5; // 简化处理
            }
        }

        return (int)$syllableCount;
    }

    /**
     * AI生成歌词
     */
    public function generateLyrics(array $params): array
    {
        // 这里应该调用AI服务生成歌词
        // 暂时返回模拟数据
        $theme = $params['theme'] ?? '爱情';
        $emotion = $params['emotion'] ?? 'happy';
        $style = $params['style'] ?? 'pop';
        $wordCount = $params['word_count'] ?? 200;

        $templates = [
            'happy' => [
                '爱情' => '阳光洒在你的脸上，温暖如初春的微风\n心中的花朵为你绽放，世界因你而美丽\n每一次心跳都在诉说，对你的爱意永不改变\n愿与你携手走过，每一个春夏秋冬',
                '友情' => '朋友是生命中的阳光，照亮前行的路\n风雨同舟的日子里，有你陪伴不孤单\n分享快乐分担忧愁，友谊之树常青\n愿我们的友谊，如星辰般永恒闪耀',
                '励志' => '追逐梦想的路上，虽有荆棘但不停步\n每一次跌倒都是成长，每一次努力都有回报\n相信自己相信未来，成功就在前方等待\n用汗水浇灌希望，用坚持创造奇迹'
            ],
            'sad' => [
                '爱情' => '雨夜里的思念，如潮水般涌上心头\n曾经的誓言已随风，只剩下回忆的痛\n孤独的街灯下，身影被拉得很长\n愿时间能够治愈，这颗破碎的心',
                '友情' => '时光荏苒岁月如梭，曾经的伙伴已远走\n留下的只有回忆，在心中慢慢泛黄\n偶尔想起那些日子，嘴角会不自觉上扬\n愿你在他乡安好，友谊永存心间',
                '励志' => '人生路上多坎坷，难免会有失落时\n但请相信黑暗过后，黎明终将会到来\n擦干眼泪继续前行，风雨过后见彩虹\n坚持就是胜利，明天会更好'
            ]
        ];

        $content = $templates[$emotion][$theme] ?? '这是一首关于' . $theme . '的歌曲，充满了' . $emotion . '的情感。';

        // 分析生成的歌词
        $emotionAnalysis = $this->analyzeEmotion($content);
        $structure = $this->analyzeStructure($content);
        $rhymeScheme = $this->analyzeRhyme($content);
        $syllableCount = $this->countSyllables($content);

        return [
            'content' => $content,
            'emotion_analysis' => $emotionAnalysis,
            'structure' => $structure,
            'rhyme_scheme' => $rhymeScheme,
            'syllable_count' => $syllableCount,
            'generation_prompt' => json_encode($params)
        ];
    }

    /**
     * 获取拼音（简化实现）
     */
    private function getPinyin(string $char): string
    {
        // 这里应该使用完整的拼音库，简化处理
        $pinyinMap = [
            '爱' => 'ai', '你' => 'ni', '我' => 'wo', '他' => 'ta', '她' => 'ta',
            '心' => 'xin', '情' => 'qing', '梦' => 'meng', '想' => 'xiang', '光' => 'guang',
            '风' => 'feng', '雨' => 'yu', '花' => 'hua', '月' => 'yue', '星' => 'xing',
            '天' => 'tian', '地' => 'di', '山' => 'shan', '水' => 'shui', '云' => 'yun'
        ];
        
        return $pinyinMap[$char] ?? '';
    }

    /**
     * 获取韵母（简化实现）
     */
    private function getRhyme(string $pinyin): string
    {
        // 简化的韵母提取
        if (strlen($pinyin) < 2) {
            return $pinyin;
        }
        
        $vowels = ['a', 'e', 'i', 'o', 'u', 'ü'];
        $rhyme = '';
        
        for ($i = strlen($pinyin) - 1; $i >= 0; $i--) {
            if (in_array($pinyin[$i], $vowels)) {
                $rhyme = substr($pinyin, $i);
                break;
            }
        }
        
        return $rhyme ?: substr($pinyin, -2);
    }

    /**
     * 判断是否押韵（简化实现）
     */
    private function isRhyming(string $rhyme1, string $rhyme2): bool
    {
        return $rhyme1 === $rhyme2 || 
               (strlen($rhyme1) > 1 && strlen($rhyme2) > 1 && 
                substr($rhyme1, -1) === substr($rhyme2, -1));
    }

    /**
     * 计算韵律密度
     */
    private function calculateRhymeDensity(array $rhymeScheme): float
    {
        if (empty($rhymeScheme)) {
            return 0.0;
        }

        $rhymeCount = 0;
        $totalLines = count($rhymeScheme);

        foreach ($rhymeScheme as $scheme) {
            if ($scheme !== '-') {
                $rhymeCount++;
            }
        }

        return $totalLines > 0 ? round($rhymeCount / $totalLines, 2) : 0.0;
    }

    /**
     * 搜索歌词
     */
    public function search(string $keyword, int $page = 1, int $limit = 20)
    {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT l.*, p.title as project_title, p.user_id, u.username
                FROM {$this->table} l
                LEFT JOIN ai_music_project p ON l.project_id = p.id
                LEFT JOIN user u ON p.user_id = u.id
                WHERE p.is_public = 1 AND p.status = 3
                  AND l.content LIKE :keyword
                ORDER BY l.created_at DESC
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
     * 获取歌词总数
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
}