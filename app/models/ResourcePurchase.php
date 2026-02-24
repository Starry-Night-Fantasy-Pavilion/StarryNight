<?php

namespace app\models;

use app\services\Database;
use PDO;

/**
 * 资源购买记录模型
 */
class ResourcePurchase
{
    /**
     * 创建资源购买记录
     *
     * @param array $data
     * @return int|false
     */
    public static function create(array $data)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}resource_purchases` 
                (resource_type, resource_id, share_id, buyer_id, seller_id, price, coins_spent, purchase_time) 
                VALUES (:resource_type, :resource_id, :share_id, :buyer_id, :seller_id, :price, :coins_spent, :purchase_time)";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':resource_type' => $data['resource_type'],
            ':resource_id' => $data['resource_id'],
            ':share_id' => $data['share_id'] ?? null,
            ':buyer_id' => $data['buyer_id'],
            ':seller_id' => $data['seller_id'],
            ':price' => $data['price'],
            ':coins_spent' => $data['coins_spent'],
            ':purchase_time' => $data['purchase_time'] ?? date('Y-m-d H:i:s')
        ]) ? $pdo->lastInsertId() : false;
    }

    /**
     * 根据ID获取资源购买记录
     *
     * @param int $id
     * @return array|null
     */
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT rp.*, 
                       buyer.username as buyer_username, buyer.nickname as buyer_nickname,
                       seller.username as seller_username, seller.nickname as seller_nickname,
                       CASE 
                           WHEN rp.resource_type = 'knowledge' THEN (SELECT title FROM `{$prefix}knowledge_bases` WHERE id = rp.resource_id)
                           WHEN rp.resource_type = 'prompt' THEN (SELECT title FROM `{$prefix}ai_prompt_templates` WHERE id = rp.resource_id)
                           WHEN rp.resource_type = 'template' THEN (SELECT title FROM `{$prefix}creation_templates` WHERE id = rp.resource_id)
                           WHEN rp.resource_type = 'agent' THEN (SELECT name FROM `{$prefix}ai_agents` WHERE id = rp.resource_id)
                       END as resource_title
                FROM `{$prefix}resource_purchases` rp
                LEFT JOIN `{$prefix}users` buyer ON rp.buyer_id = buyer.id
                LEFT JOIN `{$prefix}users` seller ON rp.seller_id = seller.id
                WHERE rp.id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * 检查用户是否已购买资源
     *
     * @param int $userId
     * @param string $resourceType
     * @param int $resourceId
     * @return array|null
     */
    public static function getUserPurchase(int $userId, string $resourceType, int $resourceId): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}resource_purchases` 
                WHERE buyer_id = :user_id AND resource_type = :resource_type AND resource_id = :resource_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':resource_type' => $resourceType,
            ':resource_id' => $resourceId
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * 获取用户的购买记录
     *
     * @param int $userId
     * @param int $page
     * @param int $perPage
     * @param string|null $resourceType
     * @return array
     */
    public static function getByBuyer(int $userId, int $page = 1, int $perPage = 15, ?string $resourceType = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $offset = ($page - 1) * $perPage;

        $sql = "SELECT rp.*, 
                       seller.username as seller_username, seller.nickname as seller_nickname,
                       CASE 
                           WHEN rp.resource_type = 'knowledge' THEN (SELECT title FROM `{$prefix}knowledge_bases` WHERE id = rp.resource_id)
                           WHEN rp.resource_type = 'prompt' THEN (SELECT title FROM `{$prefix}ai_prompt_templates` WHERE id = rp.resource_id)
                           WHEN rp.resource_type = 'template' THEN (SELECT title FROM `{$prefix}creation_templates` WHERE id = rp.resource_id)
                           WHEN rp.resource_type = 'agent' THEN (SELECT name FROM `{$prefix}ai_agents` WHERE id = rp.resource_id)
                       END as resource_title
                FROM `{$prefix}resource_purchases` rp
                LEFT JOIN `{$prefix}users` seller ON rp.seller_id = seller.id
                WHERE rp.buyer_id = :user_id";

        $params = [':user_id' => $userId];

        if ($resourceType) {
            $sql .= " AND rp.resource_type = :resource_type";
            $params[':resource_type'] = $resourceType;
        }

        $sql .= " ORDER BY rp.purchase_time DESC";

        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}resource_purchases` WHERE buyer_id = :user_id";
        $countParams = [':user_id' => $userId];

        if ($resourceType) {
            $countSql .= " AND resource_type = :resource_type";
            $countParams[':resource_type'] = $resourceType;
        }

        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($countParams);
        $totalRecords = $countStmt->fetchColumn();

        // 获取分页数据
        $sql .= " LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            if ($key !== ':user_id') {
                $stmt->bindValue($key, $value);
            }
        }
        $stmt->execute();
        $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'purchases' => $purchases,
            'total' => $totalRecords,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($totalRecords / $perPage)
        ];
    }

    /**
     * 获取用户的销售记录
     *
     * @param int $userId
     * @param int $page
     * @param int $perPage
     * @param string|null $resourceType
     * @return array
     */
    public static function getBySeller(int $userId, int $page = 1, int $perPage = 15, ?string $resourceType = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $offset = ($page - 1) * $perPage;

        $sql = "SELECT rp.*, 
                       buyer.username as buyer_username, buyer.nickname as buyer_nickname,
                       CASE 
                           WHEN rp.resource_type = 'knowledge' THEN (SELECT title FROM `{$prefix}knowledge_bases` WHERE id = rp.resource_id)
                           WHEN rp.resource_type = 'prompt' THEN (SELECT title FROM `{$prefix}ai_prompt_templates` WHERE id = rp.resource_id)
                           WHEN rp.resource_type = 'template' THEN (SELECT title FROM `{$prefix}creation_templates` WHERE id = rp.resource_id)
                           WHEN rp.resource_type = 'agent' THEN (SELECT name FROM `{$prefix}ai_agents` WHERE id = rp.resource_id)
                       END as resource_title
                FROM `{$prefix}resource_purchases` rp
                LEFT JOIN `{$prefix}users` buyer ON rp.buyer_id = buyer.id
                WHERE rp.seller_id = :user_id";

        $params = [':user_id' => $userId];

        if ($resourceType) {
            $sql .= " AND rp.resource_type = :resource_type";
            $params[':resource_type'] = $resourceType;
        }

        $sql .= " ORDER BY rp.purchase_time DESC";

        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}resource_purchases` WHERE seller_id = :user_id";
        $countParams = [':user_id' => $userId];

        if ($resourceType) {
            $countSql .= " AND resource_type = :resource_type";
            $countParams[':resource_type'] = $resourceType;
        }

        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($countParams);
        $totalRecords = $countStmt->fetchColumn();

        // 获取分页数据
        $sql .= " LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            if ($key !== ':user_id') {
                $stmt->bindValue($key, $value);
            }
        }
        $stmt->execute();
        $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'purchases' => $purchases,
            'total' => $totalRecords,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($totalRecords / $perPage)
        ];
    }

    /**
     * 获取资源的购买记录
     *
     * @param string $resourceType
     * @param int $resourceId
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public static function getByResource(string $resourceType, int $resourceId, int $page = 1, int $perPage = 15): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $offset = ($page - 1) * $perPage;

        $sql = "SELECT rp.*, 
                       buyer.username as buyer_username, buyer.nickname as buyer_nickname,
                       seller.username as seller_username, seller.nickname as seller_nickname
                FROM `{$prefix}resource_purchases` rp
                LEFT JOIN `{$prefix}users` buyer ON rp.buyer_id = buyer.id
                LEFT JOIN `{$prefix}users` seller ON rp.seller_id = seller.id
                WHERE rp.resource_type = :resource_type AND rp.resource_id = :resource_id
                ORDER BY rp.purchase_time DESC";

        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}resource_purchases` 
                     WHERE resource_type = :resource_type AND resource_id = :resource_id";

        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute([
            ':resource_type' => $resourceType,
            ':resource_id' => $resourceId
        ]);
        $totalRecords = $countStmt->fetchColumn();

        // 获取分页数据
        $sql .= " LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':resource_type', $resourceType);
        $stmt->bindValue(':resource_id', $resourceId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'purchases' => $purchases,
            'total' => $totalRecords,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($totalRecords / $perPage)
        ];
    }

    /**
     * 删除资源购买记录
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "DELETE FROM `{$prefix}resource_purchases` WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * 获取购买统计
     *
     * @param int|null $userId
     * @param string|null $resourceType
     * @param string|null $dateRange
     * @return array
     */
    public static function getPurchaseStats(?int $userId = null, ?string $resourceType = null, ?string $dateRange = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT 
                    resource_type,
                    COUNT(*) as total_purchases,
                    SUM(price) as total_revenue,
                    SUM(coins_spent) as total_coins_spent,
                    AVG(price) as avg_price
                FROM `{$prefix}resource_purchases`";

        $params = [];
        $where = [];

        if ($userId) {
            $where[] = "buyer_id = :user_id";
            $params[':user_id'] = $userId;
        }

        if ($resourceType) {
            $where[] = "resource_type = :resource_type";
            $params[':resource_type'] = $resourceType;
        }

        if ($dateRange) {
            switch ($dateRange) {
                case 'today':
                    $where[] = "DATE(purchase_time) = CURDATE()";
                    break;
                case 'week':
                    $where[] = "purchase_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                    break;
                case 'month':
                    $where[] = "purchase_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                    break;
                case 'year':
                    $where[] = "purchase_time >= DATE_SUB(NOW(), INTERVAL 365 DAY)";
                    break;
            }
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " GROUP BY resource_type";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stats = [];
        foreach ($results as $result) {
            $stats[$result['resource_type']] = [
                'total_purchases' => (int)$result['total_purchases'],
                'total_revenue' => (float)$result['total_revenue'],
                'total_coins_spent' => (int)$result['total_coins_spent'],
                'avg_price' => round((float)$result['avg_price'], 2)
            ];
        }

        return $stats;
    }

    /**
     * 获取最新购买记录
     *
     * @param int $limit
     * @param string|null $resourceType
     * @return array
     */
    public static function getLatestPurchases(int $limit = 10, ?string $resourceType = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT rp.*, 
                       buyer.username as buyer_username, buyer.nickname as buyer_nickname,
                       seller.username as seller_username, seller.nickname as seller_nickname,
                       CASE 
                           WHEN rp.resource_type = 'knowledge' THEN (SELECT title FROM `{$prefix}knowledge_bases` WHERE id = rp.resource_id)
                           WHEN rp.resource_type = 'prompt' THEN (SELECT title FROM `{$prefix}ai_prompt_templates` WHERE id = rp.resource_id)
                           WHEN rp.resource_type = 'template' THEN (SELECT title FROM `{$prefix}creation_templates` WHERE id = rp.resource_id)
                           WHEN rp.resource_type = 'agent' THEN (SELECT name FROM `{$prefix}ai_agents` WHERE id = rp.resource_id)
                       END as resource_title
                FROM `{$prefix}resource_purchases` rp
                LEFT JOIN `{$prefix}users` buyer ON rp.buyer_id = buyer.id
                LEFT JOIN `{$prefix}users` seller ON rp.seller_id = seller.id";

        $params = [];

        if ($resourceType) {
            $sql .= " WHERE rp.resource_type = :resource_type";
            $params[':resource_type'] = $resourceType;
        }

        $sql .= " ORDER BY rp.purchase_time DESC LIMIT :limit";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取资源类型列表
     */
    public static function getResourceTypes(): array
    {
        return [
            'knowledge' => '知识库',
            'prompt' => '提示词模板',
            'template' => '创作模板',
            'agent' => 'AI智能体'
        ];
    }
}