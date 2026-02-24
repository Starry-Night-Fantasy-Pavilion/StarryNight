<?php

declare(strict_types=1);

namespace app\services;

use Core\Services\BaseService;
use app\repositories\UserRepository;
use app\repositories\UserRepositoryInterface;
use app\services\CacheService;

/**
 * 用户服务
 * 示例：展示如何使用Service层
 */
class UserService extends BaseService
{
    /**
     * 缓存前缀
     */
    protected string $cachePrefix = 'user_service';

    /**
     * 默认缓存时间
     */
    protected int $defaultCacheTtl = 3600;

    /**
     * 用户Repository实例
     */
    protected UserRepositoryInterface $userRepository;

    /**
     * 构造函数
     *
     * @param UserRepositoryInterface|null $repository 用户Repository
     */
    public function __construct(?UserRepositoryInterface $repository = null)
    {
        $repository = $repository ?? new UserRepository();
        parent::__construct($repository);
        $this->userRepository = $repository;
    }

    /**
     * 用户登录
     *
     * @param string $username 用户名
     * @param string $password 密码
     * @return array{success: bool, user: array|null, message: string}
     */
    public function login(string $username, string $password): array
    {
        $user = $this->userRepository->findByUsername($username);

        if (!$user) {
            return [
                'success' => false,
                'user' => null,
                'message' => '用户不存在',
            ];
        }

        // 检查状态
        if (($user['status'] ?? '') !== 'active') {
            return [
                'success' => false,
                'user' => null,
                'message' => '账户已被禁用或冻结',
            ];
        }

        // 验证密码
        if (!password_verify($password, $user['password'] ?? '')) {
            return [
                'success' => false,
                'user' => null,
                'message' => '密码错误',
            ];
        }

        // 更新最后登录时间
        $this->userRepository->updateLastLogin((int)$user['id']);

        // 移除敏感信息
        unset($user['password']);

        return [
            'success' => true,
            'user' => $user,
            'message' => '登录成功',
        ];
    }

    /**
     * 用户注册
     *
     * @param array $data 注册数据
     * @return array{success: bool, user: array|null, message: string}
     */
    public function register(array $data): array
    {
        // 验证数据
        $validation = $this->validate($data, [
            'username' => ['required', 'min:3', 'max:20'],
            'email' => ['required', 'email'],
            'password' => ['required', 'min:6'],
        ]);

        if (!$validation['valid']) {
            return [
                'success' => false,
                'user' => null,
                'message' => '验证失败',
                'errors' => $validation['errors'],
            ];
        }

        // 检查用户名是否存在
        if ($this->userRepository->usernameExists($data['username'])) {
            return [
                'success' => false,
                'user' => null,
                'message' => '用户名已存在',
            ];
        }

        // 检查邮箱是否存在
        if ($this->userRepository->emailExists($data['email'])) {
            return [
                'success' => false,
                'user' => null,
                'message' => '邮箱已被注册',
            ];
        }

        // 创建用户
        $userData = [
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'nickname' => $data['nickname'] ?? $data['username'],
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $user = $this->userRepository->create($userData);

        if (!$user) {
            return [
                'success' => false,
                'user' => null,
                'message' => '注册失败，请稍后重试',
            ];
        }

        // 移除敏感信息
        unset($user['password']);

        return [
            'success' => true,
            'user' => $user,
            'message' => '注册成功',
        ];
    }

    /**
     * 获取用户信息（带缓存）
     *
     * @param int $userId 用户ID
     * @return array|null
     */
    public function getUserInfo(int $userId): ?array
    {
        return $this->remember("user_info:{$userId}", function () use ($userId) {
            $user = $this->userRepository->find($userId);
            if ($user) {
                unset($user['password']);
            }
            return $user;
        });
    }

    /**
     * 更新用户资料
     *
     * @param int $userId 用户ID
     * @param array $data 更新数据
     * @return array{success: bool, message: string}
     */
    public function updateProfile(int $userId, array $data): array
    {
        // 验证数据
        $validation = $this->validate($data, [
            'nickname' => ['max:50'],
            'email' => ['email'],
        ]);

        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => '验证失败',
                'errors' => $validation['errors'],
            ];
        }

        // 检查邮箱是否被其他用户使用
        if (isset($data['email']) && $this->userRepository->emailExists($data['email'], $userId)) {
            return [
                'success' => false,
                'message' => '邮箱已被其他用户使用',
            ];
        }

        // 更新
        $result = $this->userRepository->update($userId, $data);

        if ($result) {
            // 清除缓存
            $this->deleteCache("user_info:{$userId}");
            return [
                'success' => true,
                'message' => '更新成功',
            ];
        }

        return [
            'success' => false,
            'message' => '更新失败',
        ];
    }

    /**
     * 修改密码
     *
     * @param int $userId 用户ID
     * @param string $oldPassword 旧密码
     * @param string $newPassword 新密码
     * @return array{success: bool, message: string}
     */
    public function changePassword(int $userId, string $oldPassword, string $newPassword): array
    {
        $user = $this->userRepository->find($userId);

        if (!$user) {
            return [
                'success' => false,
                'message' => '用户不存在',
            ];
        }

        // 验证旧密码
        if (!password_verify($oldPassword, $user['password'] ?? '')) {
            return [
                'success' => false,
                'message' => '原密码错误',
            ];
        }

        // 更新密码
        $result = $this->userRepository->update($userId, [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT),
        ]);

        if ($result) {
            // 清除缓存
            $this->deleteCache("user_info:{$userId}");
            return [
                'success' => true,
                'message' => '密码修改成功',
            ];
        }

        return [
            'success' => false,
            'message' => '密码修改失败',
        ];
    }

    /**
     * 获取用户列表
     *
     * @param int $page 页码
     * @param int $perPage 每页数量
     * @param array $filters 筛选条件
     * @return array
     */
    public function getUserList(int $page = 1, int $perPage = 15, array $filters = []): array
    {
        $conditions = [];

        if (!empty($filters['status'])) {
            $conditions['status'] = $filters['status'];
        }

        if (!empty($filters['vip_type'])) {
            $conditions['vip_type'] = $filters['vip_type'];
        }

        return $this->userRepository->paginate($page, $perPage, $conditions);
    }

    /**
     * 搜索用户
     *
     * @param string $keyword 关键词
     * @param int $page 页码
     * @param int $perPage 每页数量
     * @return array
     */
    public function searchUsers(string $keyword, int $page = 1, int $perPage = 15): array
    {
        return $this->userRepository->search($keyword, $page, $perPage);
    }

    /**
     * 获取活跃用户
     *
     * @param int $limit 限制数量
     * @return array
     */
    public function getActiveUsers(int $limit = 10): array
    {
        return $this->remember("active_users:{$limit}", function () use ($limit) {
            return $this->userRepository->getActiveUsers($limit);
        }, 300); // 缓存5分钟
    }

    /**
     * 获取VIP用户
     *
     * @param int $limit 限制数量
     * @return array
     */
    public function getVipUsers(int $limit = 10): array
    {
        return $this->remember("vip_users:{$limit}", function () use ($limit) {
            return $this->userRepository->getVipUsers($limit);
        }, 300); // 缓存5分钟
    }

    /**
     * 获取用户统计
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return $this->remember('statistics', function () {
            return $this->userRepository->getStatistics();
        }, 600); // 缓存10分钟
    }

    /**
     * 禁用用户
     *
     * @param int $userId 用户ID
     * @return array{success: bool, message: string}
     */
    public function disableUser(int $userId): array
    {
        $result = $this->userRepository->updateStatus($userId, 'disabled');

        if ($result) {
            $this->deleteCache("user_info:{$userId}");
            return [
                'success' => true,
                'message' => '用户已禁用',
            ];
        }

        return [
            'success' => false,
            'message' => '操作失败',
        ];
    }

    /**
     * 启用用户
     *
     * @param int $userId 用户ID
     * @return array{success: bool, message: string}
     */
    public function enableUser(int $userId): array
    {
        $result = $this->userRepository->updateStatus($userId, 'active');

        if ($result) {
            $this->deleteCache("user_info:{$userId}");
            return [
                'success' => true,
                'message' => '用户已启用',
            ];
        }

        return [
            'success' => false,
            'message' => '操作失败',
        ];
    }

    /**
     * 批量更新用户状态
     *
     * @param array $userIds 用户ID数组
     * @param string $status 状态
     * @return array{success: bool, affected: int, message: string}
     */
    public function batchUpdateStatus(array $userIds, string $status): array
    {
        $affected = $this->userRepository->batchUpdateStatus($userIds, $status);

        // 清除相关缓存
        foreach ($userIds as $userId) {
            $this->deleteCache("user_info:{$userId}");
        }

        return [
            'success' => $affected > 0,
            'affected' => $affected,
            'message' => "已更新 {$affected} 个用户",
        ];
    }

    /**
     * 删除用户（软删除）
     *
     * @param int $userId 用户ID
     * @return array{success: bool, message: string}
     */
    public function deleteUser(int $userId): array
    {
        $result = $this->userRepository->updateStatus($userId, 'deleted');

        if ($result) {
            $this->deleteCache("user_info:{$userId}");
            return [
                'success' => true,
                'message' => '用户已删除',
            ];
        }

        return [
            'success' => false,
            'message' => '删除失败',
        ];
    }
}
