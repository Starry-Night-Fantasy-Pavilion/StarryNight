<?php

namespace app\models;

use app\services\Database;
use PDO;

class TokenConsumptionRecord
{
    /**
     * 创建星夜币消费记录
     *
     * @param array $data
     * @return int|false
     */
    public static function create(array $data)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        try {
            $sql = "INSERT INTO `{$prefix}token_consumption_records`
                        (user_id, tokens, balance_before, balance_after, consumption_type, related_id, related_type, description, ip_address, user_agent, created_at)
                    VALUES
                        (:user_id, :tokens, :balance_before, :balance_after, :consumption_type, :related_id, :related_type, :description, :ip_address, :user_agent, NOW())";
            
            $stmt = $pdo->prepare($sql);
            
            $stmt->execute([
                ':user_id' => $data['user_id'],
                ':tokens' => $data['tokens'],
                ':balance_before' => $data['balance_before'],
                ':balance_after' => $data['balance_after'],
                ':consumption_type' => $data['consumption_type'],
                ':related_id' => $data['related_id'] ?? null,
                ':related_type' => $data['related_type'] ?? null,
                ':description' => $data['description'] ?? '',
                ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]);

            return (int)$pdo->lastInsertId();
        } catch (\Exception $e) {
            error_log('Error creating token consumption record: ' . $e->getMessage());
            return false;
        }
    }
}
