<?php

namespace app\models;

use app\services\Database;
use PDO;

class AIResourceAudit
{
    public static function getQueue(int $page = 1, int $perPage = 15, array $filters = []): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT q.*, u.username as submitter_name
                FROM `{$prefix}ai_resource_audit_queue` q
                LEFT JOIN `{$prefix}users` u ON q.submitter_id = u.id";

        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = "q.status = :status";
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['resource_type'])) {
            $where[] = "q.resource_type = :resource_type";
            $params[':resource_type'] = $filters['resource_type'];
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY q.created_at DESC";

        $countSql = "SELECT COUNT(*) FROM `{$prefix}ai_resource_audit_queue` q";
        if (!empty($where)) {
            $countSql .= " WHERE " . implode(" AND ", $where);
        }
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $sql .= " LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => (int)ceil($total / $perPage),
        ];
    }

    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT q.*, u.username as submitter_name
                FROM `{$prefix}ai_resource_audit_queue` q
                LEFT JOIN `{$prefix}users` u ON q.submitter_id = u.id
                WHERE q.id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($item) {
            $logSql = "SELECT l.*, a.username AS reviewer_name
                       FROM `{$prefix}ai_resource_audit_logs` l
                       LEFT JOIN `{$prefix}admin_admins` a ON a.id = l.reviewer_admin_id
                       WHERE l.queue_id = :qid
                       ORDER BY l.id DESC";
            $logStmt = $pdo->prepare($logSql);
            $logStmt->execute([':qid' => $id]);
            $item['logs'] = $logStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $item ?: null;
    }

    public static function review(int $id, int $reviewerAdminId, string $action, string $comment = '', array $metadata = []): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        try {
            $pdo->beginTransaction();

            $statusMap = [
                'approve' => 'approved',
                'reject' => 'rejected',
            ];

            if (isset($statusMap[$action])) {
                $upd = $pdo->prepare("UPDATE `{$prefix}ai_resource_audit_queue` SET status = :status WHERE id = :id");
                $upd->execute([':status' => $statusMap[$action], ':id' => $id]);
            }

            $logSql = "INSERT INTO `{$prefix}ai_resource_audit_logs`
                       (queue_id, reviewer_admin_id, action, comment, metadata_json)
                       VALUES
                       (:qid, :rid, :action, :comment, :meta)";
            $stmt = $pdo->prepare($logSql);
            $stmt->execute([
                ':qid' => $id,
                ':rid' => $reviewerAdminId > 0 ? $reviewerAdminId : null,
                ':action' => $action,
                ':comment' => $comment,
                ':meta' => !empty($metadata) ? json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            ]);

            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log($e->getMessage());
            return false;
        }
    }
}

