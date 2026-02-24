<?php

namespace app\models;

use app\services\Database;
use PDO;

class NovelChapter
{
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("SELECT * FROM `{$prefix}novel_chapters` WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByNovel(int $novelId): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("SELECT * FROM `{$prefix}novel_chapters` WHERE novel_id = :novel_id ORDER BY sort_order ASC, chapter_number ASC");
        $stmt->execute([':novel_id' => $novelId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create(array $data): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        // 计算字数
        $wordCount = mb_strlen(strip_tags($data['content'] ?? ''), 'UTF-8');

        $sql = "INSERT INTO `{$prefix}novel_chapters` 
                (novel_id, chapter_number, title, content, word_count, status, sort_order)
                VALUES 
                (:novel_id, :chapter_number, :title, :content, :word_count, :status, :sort_order)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':novel_id' => $data['novel_id'],
            ':chapter_number' => (int)($data['chapter_number'] ?? 1),
            ':title' => $data['title'] ?? null,
            ':content' => $data['content'] ?? '',
            ':word_count' => $wordCount,
            ':status' => $data['status'] ?? 'draft',
            ':sort_order' => (int)($data['sort_order'] ?? 0),
        ]);

        $chapterId = (int)$pdo->lastInsertId();

        // 保存版本历史
        self::saveVersion($chapterId, $data['content'] ?? '', $wordCount);

        // 更新小说总字数
        Novel::updateWordCount($data['novel_id']);

        return $chapterId;
    }

    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $fields = [];
        $params = [':id' => $id];

        $allowedFields = ['chapter_number', 'title', 'content', 'status', 'sort_order'];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "`{$field}` = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }

        // 如果更新了内容，重新计算字数并保存版本
        if (isset($data['content'])) {
            $wordCount = mb_strlen(strip_tags($data['content']), 'UTF-8');
            $fields[] = "word_count = :word_count";
            $params[':word_count'] = $wordCount;
            self::saveVersion($id, $data['content'], $wordCount);

            // 更新小说总字数
            $chapter = self::find($id);
            if ($chapter) {
                Novel::updateWordCount($chapter['novel_id']);
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE `{$prefix}novel_chapters` SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public static function saveVersion(int $chapterId, string $content, int $wordCount, string $note = ''): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}novel_chapter_versions` 
                (chapter_id, content, word_count, version_note)
                VALUES 
                (:chapter_id, :content, :word_count, :version_note)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':chapter_id' => $chapterId,
            ':content' => $content,
            ':word_count' => $wordCount,
            ':version_note' => $note,
        ]);

        return (int)$pdo->lastInsertId();
    }

    public static function getVersions(int $chapterId): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("SELECT * FROM `{$prefix}novel_chapter_versions` WHERE chapter_id = :chapter_id ORDER BY created_at DESC");
        $stmt->execute([':chapter_id' => $chapterId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $chapter = self::find($id);
        if (!$chapter) {
            return false;
        }

        $stmt = $pdo->prepare("DELETE FROM `{$prefix}novel_chapters` WHERE id = :id");
        $result = $stmt->execute([':id' => $id]);

        if ($result) {
            Novel::updateWordCount($chapter['novel_id']);
        }

        return $result;
    }
}
