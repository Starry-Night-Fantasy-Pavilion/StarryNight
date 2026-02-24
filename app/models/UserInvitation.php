<?php

namespace app\models;

use app\services\Database;
use PDO;

/**
 * 用户邀请模型
 */
class UserInvitation
{
    /**
     * 创建邀请记录
     *
     * @param array $data
     * @return int|false
     */
    public static function create(array $data)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}user_invitations` 
                (inviter_id, invite_code, invitee_email, invitee_phone, status, reward_amount, expires_at) 
                VALUES (:inviter_id, :invite_code, :invitee_email, :invitee_phone, :status, :reward_amount, :expires_at)";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':inviter_id' => $data['inviter_id'],
            ':invite_code' => $data['invite_code'],
            ':invitee_email' => $data['invitee_email'] ?? null,
            ':invitee_phone' => $data['invitee_phone'] ?? null,
            ':status' => $data['status'] ?? 'pending',
            ':reward_amount' => $data['reward_amount'] ?? 0.00,
            ':expires_at' => $data['expires_at'] ?? null
        ]) ? $pdo->lastInsertId() : false;
    }

    /**
     * 根据ID获取邀请记录
     *
     * @param int $id
     * @return array|false
     */
    public static function getById(int $id)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT ui.*, 
                       inviter.username as inviter_username, inviter.nickname as inviter_nickname,
                       invitee.username as invitee_username, invitee.nickname as invitee_nickname
                FROM `{$prefix}user_invitations` ui 
                LEFT JOIN `{$prefix}users` inviter ON ui.inviter_id = inviter.id 
                LEFT JOIN `{$prefix}users` invitee ON ui.invitee_id = invitee.id 
                WHERE ui.id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 根据邀请码获取邀请记录
     *
     * @param string $inviteCode
     * @return array|false
     */
    public static function getByInviteCode(string $inviteCode)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT ui.*, 
                       inviter.username as inviter_username, inviter.nickname as inviter_nickname,
                       invitee.username as invitee_username, invitee.nickname as invitee_nickname
                FROM `{$prefix}user_invitations` ui 
                LEFT JOIN `{$prefix}users` inviter ON ui.inviter_id = inviter.id 
                LEFT JOIN `{$prefix}users` invitee ON ui.invitee_id = invitee.id 
                WHERE ui.invite_code = :invite_code";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':invite_code' => $inviteCode]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 获取用户的邀请列表
     *
     * @param int $inviterId
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public static function getByInviter(int $inviterId, array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $where = ["ui.inviter_id = :inviter_id"];
        $params = [':inviter_id' => $inviterId];

        if (!empty($filters['status'])) {
            $where[] = "ui.status = :status";
            $params[':status'] = $filters['status'];
        }

        $sql = "SELECT ui.*, 
                       invitee.username as invitee_username, invitee.nickname as invitee_nickname
                FROM `{$prefix}user_invitations` ui 
                LEFT JOIN `{$prefix}users` invitee ON ui.invitee_id = invitee.id 
                WHERE " . implode(' AND ', $where) . " 
                ORDER BY ui.created_at DESC 
                LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取邀请排行榜
     *
     * @param string $period 统计周期 (daily/weekly/monthly/all)
     * @param string $type 排行类型 (count/recharge)
     * @param int $limit
     * @return array
     */
    public static function getRanking(string $period = 'weekly', string $type = 'count', int $limit = 10): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $dateCondition = "";
        $orderBy = "";

        switch ($period) {
            case 'daily':
                $dateCondition = "AND DATE(ui.completed_at) = CURDATE()";
                break;
            case 'weekly':
                $dateCondition = "AND ui.completed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'monthly':
                $dateCondition = "AND ui.completed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
        }

        switch ($type) {
            case 'count':
                $orderBy = "ORDER BY invitation_count DESC, total_recharge DESC";
                break;
            case 'recharge':
                $orderBy = "ORDER BY total_recharge DESC, invitation_count DESC";
                break;
        }

        $sql = "SELECT u.id, u.username, u.nickname, u.avatar,
                       COUNT(ui.id) as invitation_count,
                       COALESCE(SUM(ui.invitee_recharge_amount), 0) as total_recharge,
                       COALESCE(SUM(ui.reward_amount), 0) as total_reward
                FROM `{$prefix}users` u
                LEFT JOIN `{$prefix}user_invitations` ui ON u.id = ui.inviter_id AND ui.status = 'completed' {$dateCondition}
                GROUP BY u.id
                HAVING invitation_count > 0
                {$orderBy}
                LIMIT :limit";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 更新邀请记录
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $fields = [];
        $params = [':id' => $id];

        $allowedFields = [
            'invitee_id', 'invitee_email', 'invitee_phone', 'status', 
            'reward_amount', 'invitee_recharge_amount', 'registered_at', 
            'completed_at', 'expires_at'
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "`{$field}` = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE `{$prefix}user_invitations` SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);

        return $stmt->execute($params);
    }

    /**
     * 标记邀请为已注册
     *
     * @param int $id
     * @param int $inviteeId
     * @return bool
     */
    public static function markAsRegistered(int $id, int $inviteeId): bool
    {
        return self::update($id, [
            'invitee_id' => $inviteeId,
            'status' => 'registered',
            'registered_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * 标记邀请为已完成
     *
     * @param int $id
     * @param float $rewardAmount
     * @return bool
     */
    public static function markAsCompleted(int $id, float $rewardAmount = 0.00): bool
    {
        return self::update($id, [
            'status' => 'completed',
            'reward_amount' => $rewardAmount,
            'completed_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * 更新被邀请人充值金额
     *
     * @param int $id
     * @param float $rechargeAmount
     * @return bool
     */
    public static function updateRechargeAmount(int $id, float $rechargeAmount): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "UPDATE `{$prefix}user_invitations` 
                SET invitee_recharge_amount = invitee_recharge_amount + :recharge_amount 
                WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':recharge_amount' => $rechargeAmount
        ]);
    }

    /**
     * 生成唯一邀请码
     *
     * @return string
     */
    public static function generateInviteCode(): string
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        do {
            $code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
            
            $sql = "SELECT COUNT(*) FROM `{$prefix}user_invitations` WHERE invite_code = :code";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':code' => $code]);
            $exists = $stmt->fetchColumn();
        } while ($exists > 0);

        return $code;
    }

    /**
     * 检查邀请码是否有效
     *
     * @param string $inviteCode
     * @return bool
     */
    public static function isInviteCodeValid(string $inviteCode): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT id FROM `{$prefix}user_invitations` 
                WHERE invite_code = :invite_code AND status = 'pending' 
                AND (expires_at IS NULL OR expires_at > NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':invite_code' => $inviteCode]);

        return $stmt->fetchColumn() !== false;
    }

    /**
     * 获取用户邀请统计
     *
     * @param int $inviterId
     * @return array
     */
    public static function getInviterStats(int $inviterId): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT 
                    COUNT(*) as total_invitations,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
                    COUNT(CASE WHEN status = 'registered' THEN 1 END) as registered,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                    COUNT(CASE WHEN status = 'expired' THEN 1 END) as expired,
                    COALESCE(SUM(invitee_recharge_amount), 0) as total_recharge,
                    COALESCE(SUM(reward_amount), 0) as total_reward
                FROM `{$prefix}user_invitations` 
                WHERE inviter_id = :inviter_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':inviter_id' => $inviterId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 获取全局邀请统计
     *
     * @return array
     */
    public static function getGlobalStats(): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT 
                    COUNT(*) as total_invitations,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
                    COUNT(CASE WHEN status = 'registered' THEN 1 END) as registered,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                    COUNT(CASE WHEN status = 'expired' THEN 1 END) as expired,
                    COUNT(DISTINCT inviter_id) as active_inviters,
                    COALESCE(SUM(invitee_recharge_amount), 0) as total_recharge,
                    COALESCE(SUM(reward_amount), 0) as total_reward
                FROM `{$prefix}user_invitations`";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 清理过期邀请
     *
     * @return int 清理的记录数
     */
    public static function cleanExpiredInvitations(): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "UPDATE `{$prefix}user_invitations` 
                SET status = 'expired' 
                WHERE status = 'pending' AND expires_at IS NOT NULL AND expires_at < NOW()";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();

        return $stmt->rowCount();
    }
}