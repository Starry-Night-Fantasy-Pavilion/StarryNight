<?php

namespace app\models;

use app\services\Database;
use PDO;
use app\models\TokenConsumptionRecord;

class UserTokenBalance
{
    /**
     * 获取用户星夜币余额
     *
     * @param int $userId
     * @return array|null
     */
    public static function getByUserId(int $userId): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}user_token_balance` WHERE user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $balance = $stmt->fetch(PDO::FETCH_ASSOC);

        return $balance ?: null;
    }

    /**
     * 获取或创建用户星夜币余额
     *
     * @param int $userId
     * @return array
     */
    public static function getOrCreateByUserId(int $userId): array
    {
        $balance = self::getByUserId($userId);
        
        if (!$balance) {
            self::create(['user_id' => $userId]);
            $balance = self::getByUserId($userId);
        }
        
        return $balance;
    }

    /**
     * 创建用户星夜币余额记录
     *
     * @param array $data
     * @return bool
     */
    public static function create(array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        try {
            $sql = "INSERT INTO `{$prefix}user_token_balance` 
                    (user_id, balance, total_recharged, total_consumed, total_bonus) 
                    VALUES 
                    (:user_id, :balance, :total_recharged, :total_consumed, :total_bonus)";
            
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([
                ':user_id' => $data['user_id'],
                ':balance' => $data['balance'] ?? 0,
                ':total_recharged' => $data['total_recharged'] ?? 0,
                ':total_consumed' => $data['total_consumed'] ?? 0,
                ':total_bonus' => $data['total_bonus'] ?? 0
            ]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * 增加用户星夜币余额
     *
     * @param int $userId
     * @param int $tokens
     * @param string $type
     * @param string $description
     * @param int|null $relatedId
     * @param string|null $relatedType
     * @return bool
     */
    public static function addTokens(int $userId, int $tokens, string $type, string $description, ?int $relatedId = null, ?string $relatedType = null): bool
    {
        if ($tokens <= 0) {
            return false;
        }

        $pdo = Database::pdo();
        $prefix = Database::prefix();

        try {
            $pdo->beginTransaction();

            // 获取或创建余额记录
            $balance = self::getOrCreateByUserId($userId);
            $balanceBefore = $balance['balance'];
            $balanceAfter = $balanceBefore + $tokens;

            // 构建更新语句
            $updateFields = ['balance = :balance'];
            $params = [
                ':balance' => $balanceAfter,
                ':user_id' => $userId
            ];

            if ($type === 'recharge') {
                $updateFields[] = 'total_recharged = total_recharged + :tokens';
                $updateFields[] = 'last_recharge_time = NOW()';
                $params[':tokens'] = $tokens;
            } elseif ($type === 'bonus') {
                $updateFields[] = 'total_bonus = total_bonus + :tokens';
                $params[':tokens'] = $tokens;
            } elseif ($type === 'refund') {
                $updateFields[] = 'total_consumed = total_consumed - :tokens';
                $params[':tokens'] = $tokens;
            }

            $sql = "UPDATE `{$prefix}user_token_balance` SET " . implode(', ', $updateFields) . " WHERE user_id = :user_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            // 记录流水
            TokenConsumptionRecord::create([
                'user_id' => $userId,
                'tokens' => $tokens,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'consumption_type' => $type,
                'related_id' => $relatedId,
                'related_type' => $relatedType,
                'description' => $description
            ]);

            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log('Error adding tokens: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 扣除用户星夜币余额
     *
     * @param int $userId
     * @param int $tokens
     * @param string $type
     * @param string $description
     * @param int|null $relatedId
     * @param string|null $relatedType
     * @return bool
     */
    public static function consumeTokens(int $userId, int $tokens, string $type, string $description, ?int $relatedId = null, ?string $relatedType = null): bool
    {
        if ($tokens <= 0) {
            return false;
        }

        $pdo = Database::pdo();
        $prefix = Database::prefix();

        try {
            $pdo->beginTransaction();

            // 获取余额记录并加锁
            $stmt = $pdo->prepare("SELECT * FROM `{$prefix}user_token_balance` WHERE user_id = :user_id FOR UPDATE");
            $stmt->execute([':user_id' => $userId]);
            $balance = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$balance || $balance['balance'] < $tokens) {
                $pdo->rollBack();
                return false; // Insufficient balance
            }

            $balanceBefore = $balance['balance'];
            $balanceAfter = $balanceBefore - $tokens;

            // 更新余额
            $sql = "UPDATE `{$prefix}user_token_balance` SET
                    balance = :balance,
                    total_consumed = total_consumed + :tokens,
                    last_consumption_time = NOW()
                    WHERE user_id = :user_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':balance' => $balanceAfter,
                ':tokens' => $tokens,
                ':user_id' => $userId
            ]);

            // 记录消费记录
            TokenConsumptionRecord::create([
                'user_id' => $userId,
                'tokens' => -$tokens, // 记录为负数
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'consumption_type' => $type,
                'related_id' => $relatedId,
                'related_type' => $relatedType,
                'description' => $description
            ]);

            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log('Error consuming tokens: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 检查用户余额是否足够
     *
     * @param int $userId
     * @param int $tokens
     * @return bool
     */
    public static function hasEnoughTokens(int $userId, int $tokens): bool
    {
        $balance = self::getByUserId($userId);
        return $balance && $balance['balance'] >= $tokens;
    }

    /**
     * 获取用户消费记录
     *
     * @param int $userId
     * @param int $page
     * @param int $perPage
     * @param string|null $type
     * @return array
     */
    public static function getConsumptionRecords(int $userId, int $page = 1, int $perPage = 20, ?string $type = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $offset = ($page - 1) * $perPage;

        $sql = "SELECT * FROM `{$prefix}token_consumption_records` WHERE user_id = :user_id";
        $params = [':user_id' => $userId];

        if ($type) {
            $sql .= " AND consumption_type = :type";
            $params[':type'] = $type;
        }

        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}token_consumption_records` WHERE user_id = :user_id";
        $countParams = [':user_id' => $userId];

        if ($type) {
            $countSql .= " AND consumption_type = :type";
            $countParams[':type'] = $type;
        }

        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($countParams);
        $total = $countStmt->fetchColumn();

        return [
            'records' => $records,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }

    /**
     * 获取用户消费统计
     *
     * @param int $userId
     * @param string $period
     * @return array
     */
    public static function getConsumptionStats(int $userId, string $period = 'month'): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $dateFormat = $period === 'day' ? '%Y-%m-%d' : ($period === 'week' ? '%Y-%u' : '%Y-%m');
        
        $sql = "SELECT 
                    DATE_FORMAT(created_at, '$dateFormat') as period,
                    SUM(CASE WHEN tokens > 0 THEN tokens ELSE 0 END) as added,
                    SUM(CASE WHEN tokens < 0 THEN ABS(tokens) ELSE 0 END) as consumed,
                    COUNT(*) as record_count
                FROM `{$prefix}token_consumption_records` 
                WHERE user_id = :user_id 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 1 $period)
                GROUP BY DATE_FORMAT(created_at, '$dateFormat')
                ORDER BY period DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取消费类型列表
     *
     * @return array
     */
    public static function getConsumptionTypes(): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT DISTINCT consumption_type FROM `{$prefix}token_consumption_records` ORDER BY consumption_type";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * 获取消费类型的中文描述
     *
     * @param string $type
     * @return string
     */
    public static function getConsumptionTypeDescription(string $type): string
    {
        $descriptions = [
            'ai_generation' => 'AI生成',
            'file_upload' => '文件上传',
            'storage_premium' => '高级存储',
            'feature_unlock' => '功能解锁',
            'recharge' => '充值',
            'bonus' => '赠送',
            'refund' => '退款',
            'system_adjust' => '系统调整'
        ];

        return $descriptions[$type] ?? $type;
    }
}