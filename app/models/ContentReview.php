<?php

namespace app\models;

use app\services\Database;
use PDO;

class ContentReview
{
    /**
     * 获取审查队列列表
     */
    public static function getQueue(int $page = 1, int $perPage = 15, array $filters = []): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $offset = ($page - 1) * $perPage;

        // 兼容当前实际表结构（012_extra_tables.sql），该表字段为：
        // id, config_id, target_type, target_id, status, payload_json, created_at
        // 不再依赖不存在的 submitter_id 字段，避免 SQL 报错导致 500
        $sql = "SELECT q.* 
                FROM `{$prefix}content_review_queue` q";
        
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = "q.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['content_type'])) {
            // 旧的 content_type 映射到现在的 target_type 字段
            $where[] = "q.target_type = :target_type";
            $params[':target_type'] = $filters['content_type'];
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY q.created_at DESC";

        // 总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}content_review_queue` q";
        if (!empty($where)) {
            $countSql .= " WHERE " . implode(" AND ", $where);
        }
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // 分页
        $sql .= " LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 兼容旧视图所需字段：content_type/title/content_preview/submitter_name 等
        foreach ($items as &$item) {
            // 内容类型：使用 target_type 作为回显
            $item['content_type'] = $item['target_type'] ?? '';

            // 从 payload_json 中尝试提取标题和预览
            $payload = [];
            if (!empty($item['payload_json'])) {
                $decoded = json_decode((string) $item['payload_json'], true);
                if (is_array($decoded)) {
                    $payload = $decoded;
                }
            }

            $rawTitle = $payload['title'] ?? ('内容 #' . ($item['target_id'] ?? ''));
            $item['title'] = (string) $rawTitle;

            $rawContent = $payload['content'] ?? ($payload['text'] ?? '');
            if (is_string($rawContent) && $rawContent !== '') {
                $preview = mb_substr($rawContent, 0, 80, 'UTF-8');
                if (mb_strlen($rawContent, 'UTF-8') > 80) {
                    $preview .= '...';
                }
                $item['content_preview'] = $preview;
            } else {
                $item['content_preview'] = '';
            }

            // 提交者：当前表结构没有用户字段，使用占位
            $item['submitter_name'] = '系统/未知';
        }
        unset($item);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }

    /**
     * 获取单个审查详情
     */
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT q.*, u.username as submitter_name 
                FROM `{$prefix}content_review_queue` q
                LEFT JOIN `{$prefix}users` u ON q.submitter_id = u.id
                WHERE q.id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($item) {
            // 获取日志
            $logSql = "SELECT l.*, a.username as reviewer_name 
                       FROM `{$prefix}content_review_logs` l
                       LEFT JOIN `{$prefix}admin_admins` a ON l.reviewer_id = a.id
                       WHERE l.queue_id = :queue_id
                       ORDER BY l.created_at DESC";
            $logStmt = $pdo->prepare($logSql);
            $logStmt->execute([':queue_id' => $id]);
            $item['logs'] = $logStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $item ?: null;
    }

    /**
     * 执行审查操作
     */
    public static function review(int $id, int $reviewerId, string $action, string $comment = '', array $metadata = []): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        try {
            $pdo->beginTransaction();

            // 更新状态
            $statusMap = [
                'approve' => 'approved',
                'reject' => 'rejected',
                'request_revision' => 'revision_requested'
            ];

            if (isset($statusMap[$action])) {
                $status = $statusMap[$action];
                $updateSql = "UPDATE `{$prefix}content_review_queue` SET status = :status WHERE id = :id";
                $stmt = $pdo->prepare($updateSql);
                $stmt->execute([':status' => $status, ':id' => $id]);
            }

            // 记录日志
            $logSql = "INSERT INTO `{$prefix}content_review_logs` (queue_id, reviewer_id, action, comment, metadata_json) 
                       VALUES (:queue_id, :reviewer_id, :action, :comment, :metadata_json)";
            $stmt = $pdo->prepare($logSql);
            $stmt->execute([
                ':queue_id' => $id,
                ':reviewer_id' => $reviewerId,
                ':action' => $action,
                ':comment' => $comment,
                ':metadata_json' => json_encode($metadata)
            ]);

            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * 获取审查配置
     */
    public static function getConfigs(): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}content_review_configs`";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 更新审查配置
     */
    public static function updateConfig(string $contentType, array $steps, bool $enabled): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}content_review_configs` (content_type, flow_steps_json, is_enabled) 
                VALUES (:type, :steps, :enabled)
                ON DUPLICATE KEY UPDATE flow_steps_json = :steps, is_enabled = :enabled";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':type' => $contentType,
            ':steps' => json_encode($steps),
            ':enabled' => $enabled ? 1 : 0
        ]);
    }
}
