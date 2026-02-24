<?php

declare(strict_types=1);

namespace app\repositories;

use Core\Orm\Repository;

/**
 * 用户Repository
 * 示例：展示如何使用Repository模式
 */
class UserRepository extends Repository implements UserRepositoryInterface
{
    /**
     * 模型类名
     */
    protected string $model = \app\models\User::class;

    /**
     * 表名
     */
    protected string $table = 'users';

    /**
     * 主键名
     */
    protected string $primaryKey = 'id';

    /**
     * 缓存前缀
     */
    protected string $cachePrefix = 'user';

    /**
     * 根据用户名查找用户
     *
     * @param string $username 用户名
     * @return array|object|null
     */
    public function findByUsername(string $username): array|object|null
    {
        return $this->findFirstBy(['username' => $username]);
    }

    /**
     * 根据邮箱查找用户
     *
     * @param string $email 邮箱
     * @return array|object|null
     */
    public function findByEmail(string $email): array|object|null
    {
        return $this->findFirstBy(['email' => $email]);
    }

    /**
     * 获取活跃用户
     *
     * @param int $limit 限制数量
     * @return array
     */
    public function getActiveUsers(int $limit = 10): array
    {
        return $this->query()
            ->where('status', 'active')
            ->orderBy('last_login_at', 'DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * 获取VIP用户
     *
     * @param int $limit 限制数量
     * @return array
     */
    public function getVipUsers(int $limit = 10): array
    {
        return $this->query()
            ->where('vip_type', '!=', 'normal')
            ->where('vip_expire_at', '>', date('Y-m-d H:i:s'))
            ->orderBy('vip_expire_at', 'DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * 搜索用户
     *
     * @param string $keyword 关键词
     * @param int $page 页码
     * @param int $perPage 每页数量
     * @return array
     */
    public function search(string $keyword, int $page = 1, int $perPage = 15): array
    {
        $query = $this->query()
            ->whereRaw(
                "(username LIKE :keyword OR nickname LIKE :keyword OR email LIKE :keyword)",
                [':keyword' => "%{$keyword}%"]
            );

        // 获取总数
        $total = $query->clone()->count();

        // 获取分页数据
        $items = $query->forPage($page, $perPage)->get();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $perPage > 0 ? (int)ceil($total / $perPage) : 0,
        ];
    }

    /**
     * 更新最后登录时间
     *
     * @param int $userId 用户ID
     * @return bool
     */
    public function updateLastLogin(int $userId): bool
    {
        return $this->update($userId, [
            'last_login_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 更新用户状态
     *
     * @param int $userId 用户ID
     * @param string $status 状态
     * @return bool
     */
    public function updateStatus(int $userId, string $status): bool
    {
        return $this->update($userId, ['status' => $status]);
    }

    /**
     * 批量更新状态
     *
     * @param array $userIds 用户ID数组
     * @param string $status 状态
     * @return int 影响行数
     */
    public function batchUpdateStatus(array $userIds, string $status): int
    {
        return $this->query()
            ->whereIn('id', $userIds)
            ->update(['status' => $status]);
    }

    /**
     * 获取用户统计
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return [
            'total' => $this->count(),
            'active' => $this->count(['status' => 'active']),
            'disabled' => $this->count(['status' => 'disabled']),
            'frozen' => $this->count(['status' => 'frozen']),
            'vip' => $this->query()
                ->where('vip_type', '!=', 'normal')
                ->where('vip_expire_at', '>', date('Y-m-d H:i:s'))
                ->count(),
        ];
    }

    /**
     * 检查用户名是否存在
     *
     * @param string $username 用户名
     * @param int|null $excludeId 排除的用户ID
     * @return bool
     */
    public function usernameExists(string $username, ?int $excludeId = null): bool
    {
        $query = $this->query()->where('username', $username);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * 检查邮箱是否存在
     *
     * @param string $email 邮箱
     * @param int|null $excludeId 排除的用户ID
     * @return bool
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $query = $this->query()->where('email', $email);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
