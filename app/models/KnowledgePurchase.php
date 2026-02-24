<?php

namespace app\models;

use app\services\Database;
use PDO;

class KnowledgePurchase
{
    /**
     * 创建购买记录
     *
     * @param array $data
     * @return int|false
     */
    public static function create(array $data)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        try {
            $pdo->beginTransaction();

            // 扣除用户星夜币
            $updateBalance = $pdo->prepare("UPDATE `{$prefix}user_wallets` SET balance = balance - :coins WHERE user_id = :user_id AND balance >= :coins");
            $updateBalance->execute([
                ':coins' => $data['coins_spent'],
                ':user_id' => $data['user_id']
            ]);

            if ($updateBalance->rowCount() === 0) {
                $pdo->rollBack();
                return false; // 余额不足
            }

            // 创建购买记录
            $sql = "INSERT INTO `{$prefix}knowledge_purchases` 
                    (user_id, knowledge_base_id, seller_id, price, coins_spent, purchase_type, status) 
                    VALUES (:user_id, :knowledge_base_id, :seller_id, :price, :coins_spent, :purchase_type, :status)";

            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                ':user_id' => $data['user_id'],
                ':knowledge_base_id' => $data['knowledge_base_id'],
                ':seller_id' => $data['seller_id'],
                ':price' => $data['price'],
                ':coins_spent' => $data['coins_spent'],
                ':purchase_type' => $data['purchase_type'] ?? 'one_time',
                ':status' => 'completed'
            ]);

            if (!$result) {
                $pdo->rollBack();
                return false;
            }

            $purchaseId = $pdo->lastInsertId();

            // 记录交易日志
            $recordTransaction = $pdo->prepare("INSERT INTO `{$prefix}coin_transactions` 
                    (user_id, type, amount, balance_after, description, created_at) 
                    VALUES (:user_id, 'purchase', :amount, 
                           (SELECT balance FROM `{$prefix}user_wallets` WHERE user_id = :user_id), 
                           :description, NOW())");

            $recordTransaction->execute([
                ':user_id' => $data['user_id'],
                ':amount' => -$data['coins_spent'],
                ':description' => "购买知识库: {$data['knowledge_base_id']}"
            ]);

            // 给卖家增加星夜币
            $updateSellerBalance = $pdo->prepare("UPDATE `{$prefix}user_wallets` SET balance = balance + :coins WHERE user_id = :user_id");
            $updateSellerBalance->execute([
                ':coins' => $data['coins_spent'],
                ':user_id' => $data['seller_id']
            ]);

            // 记录卖家收入日志
            $recordSellerTransaction = $pdo->prepare("INSERT INTO `{$prefix}coin_transactions` 
                    (user_id, type, amount, balance_after, description, created_at) 
                    VALUES (:user_id, 'sale', :amount, 
                           (SELECT balance FROM `{$prefix}user_wallets` WHERE user_id = :user_id), 
                           :description, NOW())");

            $recordSellerTransaction->execute([
                ':user_id' => $data['seller_id'],
                ':amount' => $data['coins_spent'],
                ':description' => "出售知识库: {$data['knowledge_base_id']}"
            ]);

            $pdo->commit();
            return $purchaseId;
        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * 检查用户是否已购买知识库
     *
     * @param int $userId
     * @param int $knowledgeBaseId
     * @return bool
     */
    public static function hasPurchased(int $userId, int $knowledgeBaseId): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT COUNT(*) FROM `{$prefix}knowledge_purchases` 
                WHERE user_id = :user_id AND knowledge_base_id = :knowledge_base_id AND status = 'completed'";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':knowledge_base_id' => $knowledgeBaseId
        ]);

        return $stmt->fetchColumn() > 0;
    }

    /**
     * 获取用户的购买记录
     *
     * @param int $userId
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public static function getByUser(int $userId, int $page = 1, int $perPage = 15): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $offset = ($page - 1) * $perPage;

        $sql = "SELECT kp.*, kb.title as knowledge_base_title, kb.description as kb_description,
                u.username as seller_username, u.nickname as seller_nickname
                FROM `{$prefix}knowledge_purchases` kp
                LEFT JOIN `{$prefix}knowledge_bases` kb ON kp.knowledge_base_id = kb.id
                LEFT JOIN `{$prefix}users` u ON kp.seller_id = u.id
                WHERE kp.user_id = :user_id
                ORDER BY kp.created_at DESC";

        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}knowledge_purchases` WHERE user_id = :user_id";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute([':user_id' => $userId]);
        $totalRecords = $countStmt->fetchColumn();

        // 获取分页数据
        $sql .= " LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
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
     * 获取知识库的销售记录
     *
     * @param int $sellerId
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public static function getBySeller(int $sellerId, int $page = 1, int $perPage = 15): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $offset = ($page - 1) * $perPage;

        $sql = "SELECT kp.*, kb.title as knowledge_base_title,
                u.username as buyer_username, u.nickname as buyer_nickname
                FROM `{$prefix}knowledge_purchases` kp
                LEFT JOIN `{$prefix}knowledge_bases` kb ON kp.knowledge_base_id = kb.id
                LEFT JOIN `{$prefix}users` u ON kp.user_id = u.id
                WHERE kp.seller_id = :seller_id
                ORDER BY kp.created_at DESC";

        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}knowledge_purchases` WHERE seller_id = :seller_id";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute([':seller_id' => $sellerId]);
        $totalRecords = $countStmt->fetchColumn();

        // 获取分页数据
        $sql .= " LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':seller_id', $sellerId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'sales' => $sales,
            'total' => $totalRecords,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($totalRecords / $perPage)
        ];
    }

    /**
     * 申请退款
     *
     * @param int $purchaseId
     * @param string $reason
     * @return bool
     */
    public static function requestRefund(int $purchaseId, string $reason): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        try {
            $pdo->beginTransaction();

            // 获取购买记录
            $getPurchase = $pdo->prepare("SELECT * FROM `{$prefix}knowledge_purchases` WHERE id = ? AND status = 'completed'");
            $getPurchase->execute([$purchaseId]);
            $purchase = $getPurchase->fetch(PDO::FETCH_ASSOC);

            if (!$purchase) {
                $pdo->rollBack();
                return false;
            }

            // 更新购买记录状态
            $updatePurchase = $pdo->prepare("UPDATE `{$prefix}knowledge_purchases` 
                    SET status = 'refunded', refund_reason = ?, refunded_at = NOW() 
                    WHERE id = ?");
            $updatePurchase->execute([$reason, $purchaseId]);

            // 退还星夜币给买家
            $updateBuyerBalance = $pdo->prepare("UPDATE `{$prefix}user_wallets` SET balance = balance + ? WHERE user_id = ?");
            $updateBuyerBalance->execute([$purchase['coins_spent'], $purchase['user_id']]);

            // 记录退款交易日志
            $recordBuyerTransaction = $pdo->prepare("INSERT INTO `{$prefix}coin_transactions` 
                    (user_id, type, amount, balance_after, description, created_at) 
                    VALUES (?, 'refund', ?, (SELECT balance FROM `{$prefix}user_wallets` WHERE user_id = ?), ?, NOW())");

            $recordBuyerTransaction->execute([
                $purchase['user_id'],
                $purchase['coins_spent'],
                $purchase['user_id'],
                "退款知识库: {$purchase['knowledge_base_id']}"
            ]);

            // 从卖家扣除星夜币
            $updateSellerBalance = $pdo->prepare("UPDATE `{$prefix}user_wallets` SET balance = balance - ? WHERE user_id = ?");
            $updateSellerBalance->execute([$purchase['coins_spent'], $purchase['seller_id']]);

            // 记录卖家扣除日志
            $recordSellerTransaction = $pdo->prepare("INSERT INTO `{$prefix}coin_transactions` 
                    (user_id, type, amount, balance_after, description, created_at) 
                    VALUES (?, 'refund_deduction', ?, (SELECT balance FROM `{$prefix}user_wallets` WHERE user_id = ?), ?, NOW())");

            $recordSellerTransaction->execute([
                $purchase['seller_id'],
                -$purchase['coins_spent'],
                $purchase['seller_id'],
                "退款扣除: {$purchase['knowledge_base_id']}"
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