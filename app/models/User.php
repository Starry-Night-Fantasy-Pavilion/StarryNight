<?php

namespace app\models;

use app\services\Database;
use PDO;

class User
{
    /**
     * Get all users with pagination and search.
     *
     * @param int $page
     * @param int $perPage
     * @param string|null $searchTerm
     * @param string|null $sortBy
     * @param string|null $sortOrder
     * @return array
     */
    /**
     * Find a user by ID with detailed info.
     *
     * @param int $id
     * @return array|null
     */
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT
                    u.*,
                    w.balance as coin_balance,
                    tb.balance as token_balance,
                    tb.total_recharged,
                    tb.total_consumed,
                    tb.total_bonus,
                    p.real_name, p.avatar, p.gender, p.birthdate, p.bio
                FROM `{$prefix}users` u
                LEFT JOIN `{$prefix}user_wallets` w ON u.id = w.user_id
                LEFT JOIN `{$prefix}user_token_balance` tb ON u.id = tb.user_id
                LEFT JOIN `{$prefix}user_profiles` p ON u.id = p.user_id
                WHERE u.id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    /**
     * Update user and profile.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        try {
            $pdo->beginTransaction();

            // Update users table
            $userFields = ['nickname', 'email', 'status'];
            $userUpdates = [];
            $userParams = [':id' => $id];

            foreach ($userFields as $field) {
                if (isset($data[$field])) {
                    $userUpdates[] = "`$field` = :$field";
                    $userParams[":$field"] = $data[$field];
                }
            }

            if (!empty($data['password'])) {
                $userUpdates[] = "`password` = :password";
                $userParams[':password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            if (!empty($userUpdates)) {
                $sql = "UPDATE `{$prefix}users` SET " . implode(', ', $userUpdates) . " WHERE `id` = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($userParams);
            }

            // Update user_profiles table
            $profileFields = ['real_name', 'gender', 'birthdate', 'bio', 'avatar'];
            $profileUpdates = [];
            $profileParams = [':user_id' => $id];
            $hasProfileData = false;

            foreach ($profileFields as $field) {
                if (isset($data[$field])) {
                    $profileUpdates[] = "`$field` = :$field";
                    $profileParams[":$field"] = $data[$field];
                    $hasProfileData = true;
                }
            }

            if ($hasProfileData) {
                // Check if profile exists
                $checkSql = "SELECT 1 FROM `{$prefix}user_profiles` WHERE `user_id` = :user_id";
                $checkStmt = $pdo->prepare($checkSql);
                $checkStmt->execute([':user_id' => $id]);

                if ($checkStmt->fetch()) {
                    if (!empty($profileUpdates)) {
                        $sql = "UPDATE `{$prefix}user_profiles` SET " . implode(', ', $profileUpdates) . " WHERE `user_id` = :user_id";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute($profileParams);
                    }
                } else {
                    $cols = ['user_id'];
                    $vals = [':user_id'];
                    foreach ($profileFields as $field) {
                        if (isset($data[$field])) {
                            $cols[] = "`$field`";
                            $vals[] = ":$field";
                        }
                    }
                    $sql = "INSERT INTO `{$prefix}user_profiles` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ")";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($profileParams);
                }
            }

            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Toggle user status.
     *
     * @param int $id
     * @return bool
     */
    public static function toggleStatus(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "UPDATE `{$prefix}users` SET `status` = CASE WHEN `status` = 'active' THEN 'disabled' ELSE 'active' END WHERE `id` = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Batch update user status.
     *
     * @param array $userIds
     * @param string $status
     * @return int Number of affected rows
     */
    public static function batchUpdateStatus(array $userIds, string $status): int
    {
        if (empty($userIds) || !in_array($status, ['active', 'disabled', 'frozen', 'deleted'], true)) {
            return 0;
        }

        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $placeholders = [];
        $params = [':status' => $status];
        foreach ($userIds as $index => $id) {
            $placeholders[] = ":id_{$index}";
            $params[":id_{$index}"] = (int)$id;
        }

        $sql = "UPDATE `{$prefix}users` SET `status` = :status WHERE `id` IN (" . implode(', ', $placeholders) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->rowCount();
    }

    /**
     * Freeze user account.
     *
     * @param int $id
     * @return bool
     */
    public static function freeze(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "UPDATE `{$prefix}users` SET `status` = 'frozen' WHERE `id` = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Unfreeze user account.
     *
     * @param int $id
     * @return bool
     */
    public static function unfreeze(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "UPDATE `{$prefix}users` SET `status` = 'active' WHERE `id` = :id AND `status` = 'frozen'";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Delete user (soft delete).
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "UPDATE `{$prefix}users` SET `status` = 'deleted' WHERE `id` = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Restore deleted user.
     *
     * @param int $id
     * @return bool
     */
    public static function restore(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "UPDATE `{$prefix}users` SET `status` = 'active' WHERE `id` = :id AND `status` = 'deleted'";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Adjust user coin balance.
     *
     * @param int $userId
     * @param float $amount
     * @param string $description
     * @return bool
     */
    public static function adjustBalance(int $userId, float $amount, string $description): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        try {
            $pdo->beginTransaction();

            // Check if wallet exists, create if not
            $checkWallet = $pdo->prepare("SELECT balance FROM `{$prefix}user_wallets` WHERE user_id = ?");
            $checkWallet->execute([$userId]);
            $currentBalance = $checkWallet->fetchColumn();

            if ($currentBalance === false) {
                $currentBalance = 0.00;
                $createWallet = $pdo->prepare("INSERT INTO `{$prefix}user_wallets` (user_id, balance) VALUES (?, 0.00)");
                $createWallet->execute([$userId]);
            }

            $newBalance = $currentBalance + $amount;

            // Update wallet
            $updateWallet = $pdo->prepare("UPDATE `{$prefix}user_wallets` SET balance = ? WHERE user_id = ?");
            $updateWallet->execute([$newBalance, $userId]);

            // Record transaction
            $recordTransaction = $pdo->prepare("INSERT INTO `{$prefix}coin_transactions` (user_id, type, amount, balance_after, remark, created_at) VALUES (?, 'system_adjust', ?, ?, ?, NOW())");
            $recordTransaction->execute([$userId, $amount, $newBalance, $description]);

            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log($e->getMessage());
            return false;
        }
    }

    public static function getAll(int $page = 1, int $perPage = 15, ?string $searchTerm = null, ?string $sortBy = 'id', ?string $sortOrder = 'desc', ?array $filters = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $offset = ($page - 1) * $perPage;
        $filters = $filters ?? [];

        try {
            // 丰富版查询：带钱包与代币统计，前提是相关表结构已就绪
            $sql = "SELECT
                        u.id, u.username, u.nickname, u.email, u.created_at, u.last_login_at, u.status,
                        u.vip_type, u.vip_expire_at, u.vip_start_at, u.auto_renew, u.membership_source,
                        w.balance as coin_balance,
                        tb.balance as token_balance,
                        tb.total_recharged,
                        tb.total_consumed,
                        tb.total_bonus,
                        ml.name as membership_level_name
                    FROM `{$prefix}users` u
                    LEFT JOIN `{$prefix}user_wallets` w ON u.id = w.user_id
                    LEFT JOIN `{$prefix}user_token_balance` tb ON u.id = tb.user_id
                    LEFT JOIN `{$prefix}user_memberships` um ON u.id = um.user_id AND um.status = 'active'
                    LEFT JOIN `{$prefix}membership_levels` ml ON um.level_id = ml.id";

            $where = [];
            $params = [];

            if ($searchTerm) {
                $where[] = "(u.username LIKE :term OR u.nickname LIKE :term OR u.email LIKE :term OR u.id = :term_id)";
                $params[':term'] = '%' . $searchTerm . '%';
                $params[':term_id'] = $searchTerm;
            }

            // 状态筛选
            if (!empty($filters['status']) && in_array($filters['status'], ['active', 'disabled', 'frozen', 'deleted'], true)) {
                $where[] = "u.status = :status";
                $params[':status'] = $filters['status'];
            }

            // 会员等级筛选
            if (!empty($filters['membership_level'])) {
                if (is_numeric($filters['membership_level'])) {
                    $where[] = "um.level_id = :membership_level_id";
                    $params[':membership_level_id'] = (int)$filters['membership_level'];
                } else {
                    // 筛选非会员用户
                    $where[] = "um.user_id IS NULL";
                }
            }

            // 注册时间范围筛选
            if (!empty($filters['created_from'])) {
                $where[] = "u.created_at >= :created_from";
                $params[':created_from'] = $filters['created_from'];
            }
            if (!empty($filters['created_to'])) {
                $where[] = "u.created_at <= :created_to";
                $params[':created_to'] = $filters['created_to'] . ' 23:59:59';
            }

            // 最后登录时间范围筛选
            if (!empty($filters['last_login_from'])) {
                $where[] = "u.last_login_at >= :last_login_from";
                $params[':last_login_from'] = $filters['last_login_from'];
            }
            if (!empty($filters['last_login_to'])) {
                $where[] = "u.last_login_at <= :last_login_to";
                $params[':last_login_to'] = $filters['last_login_to'] . ' 23:59:59';
            }

            if (!empty($where)) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }

            // Sorting
            $allowedSortBy = ['id', 'username', 'nickname', 'email', 'created_at', 'last_login_at', 'status', 'vip_type', 'vip_expire_at', 'coin_balance', 'token_balance', 'membership_level_name'];
            if (!in_array($sortBy, $allowedSortBy, true)) {
                $sortBy = 'id';
            }

            // Map sortable columns to their fully qualified names or aliases to avoid ambiguity
            $sortColumnMap = [
                'id' => 'u.id',
                'username' => 'u.username',
                'nickname' => 'u.nickname',
                'email' => 'u.email',
                'created_at' => 'u.created_at',
                'last_login_at' => 'u.last_login_at',
                'status' => 'u.status',
                'vip_type' => 'u.vip_type',
                'vip_expire_at' => 'u.vip_expire_at',
                'coin_balance' => 'coin_balance',
                'token_balance' => 'token_balance',
                'membership_level_name' => 'membership_level_name',
            ];

            $sortColumn = $sortColumnMap[$sortBy] ?? 'u.id';

            $sortOrder = strtolower($sortOrder) === 'asc' ? 'ASC' : 'DESC';
            $sql .= " ORDER BY {$sortColumn} {$sortOrder}";

            // Count total records
            $countSql = str_replace(
                "SELECT
                        u.id, u.username, u.nickname, u.email, u.created_at, u.last_login_at, u.status,
                        u.vip_type, u.vip_expire_at, u.vip_start_at, u.auto_renew, u.membership_source,
                        w.balance as coin_balance,
                        tb.balance as token_balance,
                        tb.total_recharged,
                        tb.total_consumed,
                        tb.total_bonus,
                        ml.name as membership_level_name",
                "SELECT COUNT(DISTINCT u.id)",
                $sql
            );

            $countStmt = $pdo->prepare($countSql);
            $countStmt->execute($params);
            $totalRecords = (int)$countStmt->fetchColumn();

            // Fetch paginated records
            $sql .= " LIMIT :limit OFFSET :offset";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            // 如果扩展表还未创建或字段不匹配，降级为只查 users 表，避免 500
            error_log('User::getAll fallback query: ' . $e->getMessage());

            $baseSql = "SELECT u.id, u.username, u.nickname, u.email, u.created_at, u.last_login_at, u.status
                        FROM `{$prefix}users` u";
            $where = [];
            $params = [];

            if ($searchTerm) {
                $where[] = "(u.username LIKE :term OR u.nickname LIKE :term OR u.email LIKE :term OR u.id = :term_id)";
                $params[':term'] = '%' . $searchTerm . '%';
                $params[':term_id'] = $searchTerm;
            }

            // 状态筛选
            if (!empty($filters['status']) && in_array($filters['status'], ['active', 'disabled', 'frozen', 'deleted'], true)) {
                $where[] = "u.status = :status";
                $params[':status'] = $filters['status'];
            }

            // 注册时间范围筛选
            if (!empty($filters['created_from'])) {
                $where[] = "u.created_at >= :created_from";
                $params[':created_from'] = $filters['created_from'];
            }
            if (!empty($filters['created_to'])) {
                $where[] = "u.created_at <= :created_to";
                $params[':created_to'] = $filters['created_to'] . ' 23:59:59';
            }

            if (!empty($where)) {
                $baseSql .= " WHERE " . implode(" AND ", $where);
            }

            $sortOrder = strtolower($sortOrder) === 'asc' ? 'ASC' : 'DESC';
            $baseSql .= " ORDER BY u.id {$sortOrder}";

            // 统计总数
            $countSql = "SELECT COUNT(DISTINCT u.id) FROM `{$prefix}users` u";
            if (!empty($where)) {
                $countSql .= " WHERE " . implode(" AND ", $where);
            }
            $countStmt = $pdo->prepare($countSql);
            $countStmt->execute($params);
            $totalRecords = (int)$countStmt->fetchColumn();

            // 分页数据
            $baseSql .= " LIMIT :limit OFFSET :offset";
            $stmt = $pdo->prepare($baseSql);
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return [
            'users' => $users ?: [],
            'total' => $totalRecords ?? 0,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $perPage > 0 ? (int)ceil(($totalRecords ?? 0) / $perPage) : 0,
        ];
    }

    /**
     * 判断用户是否为会员
     *
     * @param int $userId
     * @return bool
     */
    public static function isVip(int $userId): bool
    {
        $user = self::find($userId);
        if (!$user) {
            return false;
        }

        return Feature::isVip($user);
    }

    /**
     * 获取用户会员信息
     *
     * @param int $userId
     * @return array|null
     */
    public static function getMembershipInfo(int $userId): ?array
    {
        return MembershipPackage::getUserMembership($userId);
    }

    /**
     * 激活用户会员
     *
     * @param int $userId
     * @param int $packageId
     * @param string $source
     * @return bool
     */
    public static function activateMembership(int $userId, int $packageId, string $source = 'purchase'): bool
    {
        return MembershipPackage::activateMembership($userId, $packageId, $source);
    }

    /**
     * 续费用户会员
     *
     * @param int $userId
     * @param int $packageId
     * @return bool
     */
    public static function renewMembership(int $userId, int $packageId): bool
    {
        return MembershipPackage::renewMembership($userId, $packageId);
    }

    /**
     * 取消用户会员
     *
     * @param int $userId
     * @return bool
     */
    public static function cancelMembership(int $userId): bool
    {
        return MembershipPackage::cancelMembership($userId);
    }

    /**
     * 获取用户星夜币余额
     *
     * @param int $userId
     * @return array
     */
    public static function getTokenBalance(int $userId): array
    {
        return UserTokenBalance::getOrCreateByUserId($userId);
    }

    /**
     * 检查用户是否有权限使用某功能
     *
     * @param int $userId
     * @param string $featureKey
     * @return array
     */
    public static function checkFeatureAccess(int $userId, string $featureKey): array
    {
        return Feature::checkUserAccess($userId, $featureKey);
    }

    /**
     * 获取用户可使用的功能列表
     *
     * @param int $userId
     * @return array
     */
    public static function getAvailableFeatures(int $userId): array
    {
        return Feature::getUserAvailableFeatures($userId);
    }

    /**
     * 获取用户限制状态
     *
     * @param int $userId
     * @return array
     */
    public static function getLimitStatus(int $userId): array
    {
        return UserLimit::getUserLimitStatus($userId);
    }

    /**
     * 检查用户是否超出限制
     *
     * @param int $userId
     * @param string $limitType
     * @param int $currentValue
     * @return array
     */
    public static function checkLimit(int $userId, string $limitType, int $currentValue): array
    {
        return UserLimit::checkLimit($userId, $limitType, $currentValue);
    }

    /**
     * 获取用户会员权益
     *
     * @param int $userId
     * @return array
     */
    public static function getVipBenefits(int $userId): array
    {
        return VipBenefit::getUserBenefits($userId);
    }

    /**
     * 检查用户是否有特定权益
     *
     * @param int $userId
     * @param string $benefitKey
     * @return bool
     */
    public static function hasVipBenefit(int $userId, string $benefitKey): bool
    {
        return VipBenefit::hasBenefit($userId, $benefitKey);
    }
}
