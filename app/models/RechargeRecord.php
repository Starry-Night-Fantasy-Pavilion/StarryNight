<?php

namespace app\models;

use app\services\Database;
use PDO;

class RechargeRecord
{
    /**
     * 创建充值记录（生成订单）
     *
     * @param int $userId
     * @param int $packageId
     * @param float $amount
     * @param string $paymentMethod
     * @return string|false The order number or false on failure.
     */
    public static function create(int $userId, int $packageId, string $paymentMethod): ?string
    {
        $actualPackageDetails = RechargePackage::getActualPrice($userId, $packageId);
        if (!$actualPackageDetails) {
            return null;
        }

        $pdo = Database::pdo();
        $prefix = Database::prefix();

        try {
            $orderNo = self::generateOrderNumber($userId);

            $sql = "INSERT INTO `{$prefix}recharge_records`
                        (user_id, package_id, order_no, tokens, bonus_tokens, total_tokens, original_price, actual_price, discount_amount, payment_method, payment_status, ip_address, user_agent, created_at)
                    VALUES
                        (:user_id, :package_id, :order_no, :tokens, :bonus_tokens, :total_tokens, :original_price, :actual_price, :discount_amount, :payment_method, 'pending', :ip_address, :user_agent, NOW())";
            
            $stmt = $pdo->prepare($sql);
            
            $stmt->execute([
                ':user_id' => $userId,
                ':package_id' => $packageId,
                ':order_no' => $orderNo,
                ':tokens' => $actualPackageDetails['base_tokens'],
                ':bonus_tokens' => $actualPackageDetails['bonus_tokens'],
                ':total_tokens' => $actualPackageDetails['total_tokens'],
                ':original_price' => $actualPackageDetails['original_price'],
                ':actual_price' => $actualPackageDetails['actual_price'],
                ':discount_amount' => $actualPackageDetails['saved_amount'],
                ':payment_method' => $paymentMethod,
                ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]);

            return $orderNo;
        } catch (\Exception $e) {
            error_log('Error creating recharge record: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 更新订单状态
     *
     * @param string $orderNo
     * @param string $status
     * @param string|null $transactionId
     * @return bool
     */
    public static function updateStatus(string $orderNo, string $status, ?string $transactionId = null): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "UPDATE `{$prefix}recharge_records` SET payment_status = :status, transaction_id = :transaction_id, payment_time = NOW() WHERE order_no = :order_no AND payment_status = 'pending'";
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute([
            ':status' => $status,
            ':transaction_id' => $transactionId,
            ':order_no' => $orderNo
        ]);
    }

    /**
     * 根据订单号获取记录
     *
     * @param string $orderNo
     * @return array|null
     */
    public static function getByOrderNo(string $orderNo): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}recharge_records` WHERE order_no = :order_no";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':order_no' => $orderNo]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        return $record ?: null;
    }

    /**
     * 根据用户ID获取充值记录
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
        $countSql = "SELECT COUNT(*) FROM `{$prefix}recharge_records` WHERE user_id = :user_id";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute([':user_id' => $userId]);
        $total = $countStmt->fetchColumn();

        // 获取分页数据
        $sql = "SELECT * FROM `{$prefix}recharge_records` WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
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

    /**
     * 生成唯一订单号
     *
     * @param int $userId
     * @return string
     */
    private static function generateOrderNumber(int $userId): string
    {
        return 'RE' . date('YmdHis') . $userId . mt_rand(1000, 9999);
    }
}
