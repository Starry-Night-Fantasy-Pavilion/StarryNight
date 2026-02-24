<?php

declare(strict_types=1);

namespace app\repositories;

use Core\Orm\RepositoryInterface;

/**
 * 用户Repository接口
 * 定义用户数据访问的专用方法
 */
interface UserRepositoryInterface extends RepositoryInterface
{
    /**
     * 根据用户名查找用户
     *
     * @param string $username 用户名
     * @return array|object|null
     */
    public function findByUsername(string $username): array|object|null;

    /**
     * 根据邮箱查找用户
     *
     * @param string $email 邮箱
     * @return array|object|null
     */
    public function findByEmail(string $email): array|object|null;

    /**
     * 获取活跃用户
     *
     * @param int $limit 限制数量
     * @return array
     */
    public function getActiveUsers(int $limit = 10): array;

    /**
     * 获取VIP用户
     *
     * @param int $limit 限制数量
     * @return array
     */
    public function getVipUsers(int $limit = 10): array;

    /**
     * 搜索用户
     *
     * @param string $keyword 关键词
     * @param int $page 页码
     * @param int $perPage 每页数量
     * @return array
     */
    public function search(string $keyword, int $page = 1, int $perPage = 15): array;

    /**
     * 更新最后登录时间
     *
     * @param int $userId 用户ID
     * @return bool
     */
    public function updateLastLogin(int $userId): bool;

    /**
     * 更新用户状态
     *
     * @param int $userId 用户ID
     * @param string $status 状态
     * @return bool
     */
    public function updateStatus(int $userId, string $status): bool;

    /**
     * 批量更新状态
     *
     * @param array $userIds 用户ID数组
     * @param string $status 状态
     * @return int 影响行数
     */
    public function batchUpdateStatus(array $userIds, string $status): int;

    /**
     * 获取用户统计
     *
     * @return array
     */
    public function getStatistics(): array;

    /**
     * 检查用户名是否存在
     *
     * @param string $username 用户名
     * @param int|null $excludeId 排除的用户ID
     * @return bool
     */
    public function usernameExists(string $username, ?int $excludeId = null): bool;

    /**
     * 检查邮箱是否存在
     *
     * @param string $email 邮箱
     * @param int|null $excludeId 排除的用户ID
     * @return bool
     */
    public function emailExists(string $email, ?int $excludeId = null): bool;
}
