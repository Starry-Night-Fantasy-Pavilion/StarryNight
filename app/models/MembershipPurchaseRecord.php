<?php

namespace app\models;

use app\services\Database;
use PDO;

class MembershipPurchaseRecord
{
    /**
     * 创建会员购买记录
     *
     * @param array $data
     * @return int|false
     */
    public static function create(array $data)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        try {
            $order_no = 'MP' . date('YmdHis') . $data['user_id'] . mt_rand(1000, 9999);

            $sql = "INSERT INTO `{$prefix}membership_purchase_records`
                        (user_id, package_id, membership_level_id, membership_type, membership_name, original_price, actual_price, discount_amount, duration_days, start_time, end_time, payment_method, payment_status, payment_time, transaction_id, order_no, auto_renew, ip_address, user_agent, original_vip_expire_at)
                    VALUES
                        (:user_id, :package_id, :membership_level_id, :membership_type, :membership_name, :original_price, :actual_price, :discount_amount, :duration_days, :start_time, :end_time, :payment_method, :payment_status, :payment_time, :transaction_id, :order_no, :auto_renew, :ip_address, :user_agent, :original_vip_expire_at)";
            
            $stmt = $pdo->prepare($sql);
            
            $stmt->execute([
                ':user_id' => $data['user_id'],
                ':package_id' => $data['package_id'],
                ':membership_level_id' => $data['membership_level_id'],
                ':membership_type' => $data['membership_type'],
                ':membership_name' => $data['membership_name'],
                ':original_price' => $data['original_price'],
                ':actual_price' => $data['actual_price'],
                ':discount_amount' => $data['discount_amount'] ?? 0.00,
                ':duration_days' => $data['duration_days'],
                ':start_time' => $data['start_time'],
                ':end_time' => $data['end_time'],
                ':payment_method' => $data['payment_method'] ?? 'system',
                ':payment_status' => $data['payment_status'] ?? 'completed',
                ':payment_time' => $data['start_time'],
                ':transaction_id' => $data['transaction_id'] ?? null,
                ':order_no' => $data['order_no'] ?? $order_no,
                ':auto_renew' => $data['auto_renew'] ?? 0,
                ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                ':original_vip_expire_at' => $data['original_vip_expire_at'] ?? null
            ]);

            return (int)$pdo->lastInsertId();
        } catch (\Exception $e) {
            error_log('Error creating membership purchase record: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 根据用户ID获取购买记录
     *
     * @param int $userId
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public static function getByUserId(int $userId, int $page = 1, int $perPage = 15): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $offset = ($page - 1) * $perPage;

        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}membership_purchase_records` WHERE user_id = :user_id";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute([':user_id' => $userId]);
        $total = $countStmt->fetchColumn();

        // 获取分页数据
        $sql = "SELECT * FROM `{$prefix}membership_purchase_records` WHERE user_id = :user_id ORDER BY purchased_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'records' => $records,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }
}
