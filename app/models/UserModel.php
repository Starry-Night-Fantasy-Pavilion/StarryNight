<?php

declare(strict_types=1);

namespace app\models;

use Core\Orm\ModelAdapter;
use Core\Orm\Attributes\HasMany;
use Core\Orm\Attributes\BelongsTo;

/**
 * 用户模型 - 统一ORM实现示例
 * 
 * 该模型展示如何使用统一的ORM Model基类
 * 替代原有的静态方法 + PDO直接操作方式
 * 
 * @package app\models
 * @version 2.0.0
 */
class UserModel extends ModelAdapter
{
    /**
     * 表名（不含前缀）
     */
    protected string $table = 'users';

    /**
     * 主键名
     */
    protected string $primaryKey = 'id';

    /**
     * 可填充属性
     */
    protected array $fillable = [
        'username',
        'nickname',
        'email',
        'password',
        'status',
        'vip_type',
        'vip_expire_at',
        'vip_start_at',
        'auto_renew',
        'membership_source',
    ];

    /**
     * 隐藏属性（序列化时）
     */
    protected array $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * 保护属性（不可填充）
     */
    protected array $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    /**
     * 软删除字段
     */
    protected ?string $softDeleteColumn = 'deleted_at';

    /**
     * 是否使用时间戳
     */
    protected bool $timestamps = true;

    // ==================== 关联关系 ====================

    /**
     * 用户钱包
     */
    public function wallet(): ?UserWallet
    {
        return UserWallet::findBy(['user_id' => $this->id]);
    }

    /**
     * 用户代币余额
     */
    public function tokenBalance(): ?UserTokenBalanceModel
    {
        return UserTokenBalanceModel::findBy(['user_id' => $this->id]);
    }

    /**
     * 用户资料
     */
    public function profile(): ?UserProfile
    {
        return UserProfile::findBy(['user_id' => $this->id]);
    }

    /**
     * 用户会员信息
     */
    public function membership(): ?UserMembership
    {
        return UserMembership::findBy(['user_id' => $this->id]);
    }

    // ==================== 业务方法 ====================

    /**
     * 检查用户是否为VIP
     *
     * @return bool
     */
    public function isVip(): bool
    {
        if ($this->vip_type === 'lifetime') {
            return true;
        }

        if ($this->vip_type === 'annual' || $this->vip_type === 'monthly') {
            return $this->vip_expire_at !== null && strtotime($this->vip_expire_at) > time();
        }

        return false;
    }

    /**
     * 检查用户是否激活
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * 检查用户是否被冻结
     *
     * @return bool
     */
    public function isFrozen(): bool
    {
        return $this->status === 'frozen';
    }

    /**
     * 检查用户是否被删除
     *
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->status === 'deleted' || $this->deleted_at !== null;
    }

    /**
     * 冻结用户
     *
     * @return bool
     */
    public function freeze(): bool
    {
        $this->status = 'frozen';
        return $this->save();
    }

    /**
     * 解冻用户
     *
     * @return bool
     */
    public function unfreeze(): bool
    {
        $this->status = 'active';
        return $this->save();
    }

    /**
     * 验证密码
     *
     * @param string $password 密码
     * @return bool
     */
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }

    /**
     * 设置密码
     *
     * @param string $password 密码
     * @return static
     */
    public function setPassword(string $password): static
    {
        $this->password = password_hash($password, PASSWORD_DEFAULT);
        return $this;
    }

    // ==================== 静态查询方法 ====================

    /**
     * 根据用户名查找
     *
     * @param string $username 用户名
     * @return static|null
     */
    public static function findByUsername(string $username): ?static
    {
        return static::findBy(['username' => $username]);
    }

    /**
     * 根据邮箱查找
     *
     * @param string $email 邮箱
     * @return static|null
     */
    public static function findByEmail(string $email): ?static
    {
        return static::findBy(['email' => $email]);
    }

    /**
     * 获取用户详细信息（包含关联数据）
     *
     * @param int $id 用户ID
     * @return array|null
     */
    public static function findWithDetails(int $id): ?array
    {
        $user = static::find($id);

        if ($user === null) {
            return null;
        }

        $data = $user->toArray();
        $data['wallet'] = $user->wallet()?->toArray();
        $data['token_balance'] = $user->tokenBalance()?->toArray();
        $data['profile'] = $user->profile()?->toArray();
        $data['is_vip'] = $user->isVip();

        return $data;
    }

    /**
     * 分页获取用户列表
     *
     * @param int $page 页码
     * @param int $perPage 每页数量
     * @param string|null $search 搜索关键词
     * @param array $filters 筛选条件
     * @param string $sortBy 排序字段
     * @param string $sortOrder 排序方向
     * @return array
     */
    public static function getPaginatedList(
        int $page = 1,
        int $perPage = 15,
        ?string $search = null,
        array $filters = [],
        string $sortBy = 'id',
        string $sortOrder = 'desc'
    ): array {
        $query = static::newQuery();

        // 搜索条件
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'LIKE', "%{$search}%")
                    ->orWhere('nickname', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        // 筛选条件
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['vip_type'])) {
            $query->where('vip_type', $filters['vip_type']);
        }

        if (!empty($filters['created_from'])) {
            $query->where('created_at', '>=', $filters['created_from']);
        }

        if (!empty($filters['created_to'])) {
            $query->where('created_at', '<=', $filters['created_to'] . ' 23:59:59');
        }

        // 获取总数
        $totalQuery = clone $query;
        $total = $totalQuery->count();

        // 分页获取数据
        $offset = ($page - 1) * $perPage;
        $items = $query->orderBy($sortBy, $sortOrder)
            ->limit($perPage)
            ->offset($offset)
            ->get();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $perPage > 0 ? (int) ceil($total / $perPage) : 0,
        ];
    }

    /**
     * 批量更新状态
     *
     * @param array $userIds 用户ID列表
     * @param string $status 状态
     * @return int 影响行数
     */
    public static function batchUpdateStatus(array $userIds, string $status): int
    {
        $validStatuses = ['active', 'disabled', 'frozen', 'deleted'];

        if (!in_array($status, $validStatuses, true)) {
            return 0;
        }

        return static::batchUpdate($userIds, ['status' => $status]);
    }

    /**
     * 调整用户余额
     *
     * @param int $userId 用户ID
     * @param float $amount 金额
     * @param string $description 描述
     * @return bool
     */
    public static function adjustBalance(int $userId, float $amount, string $description): bool
    {
        return static::transaction(function () use ($userId, $amount, $description) {
            $wallet = UserWallet::firstOrCreate(['user_id' => $userId], ['balance' => 0]);
            $wallet->balance += $amount;
            $wallet->save();

            // 记录交易
            CoinTransaction::create([
                'user_id' => $userId,
                'type' => 'system_adjust',
                'amount' => $amount,
                'balance_after' => $wallet->balance,
                'remark' => $description,
            ]);

            return true;
        });
    }

    // ==================== 访问器 ====================

    /**
     * 获取显示名称
     *
     * @return string
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->nickname ?: $this->username;
    }

    /**
     * 获取VIP状态描述
     *
     * @return string
     */
    public function getVipStatusAttribute(): string
    {
        if (!$this->isVip()) {
            return '普通用户';
        }

        $types = [
            'monthly' => '月度会员',
            'annual' => '年度会员',
            'lifetime' => '终身会员',
        ];

        return $types[$this->vip_type] ?? '会员用户';
    }
}
