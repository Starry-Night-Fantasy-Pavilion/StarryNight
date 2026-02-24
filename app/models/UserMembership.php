<?php

declare(strict_types=1);

namespace app\models;

use Core\Orm\ModelAdapter;

/**
 * 用户会员模型
 * 
 * @package app\models
 */
class UserMembership extends ModelAdapter
{
    /**
     * 表名（不含前缀）
     */
    protected string $table = 'user_memberships';

    /**
     * 主键名
     */
    protected string $primaryKey = 'id';

    /**
     * 可填充属性
     */
    protected array $fillable = [
        'user_id',
        'level_id',
        'status',
        'started_at',
        'expires_at',
    ];

    /**
     * 隐藏属性（序列化时）
     */
    protected array $hidden = [];

    /**
     * 保护属性（不可填充）
     */
    protected array $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    /**
     * 是否使用时间戳
     */
    protected bool $timestamps = true;

    /**
     * 根据用户ID获取会员信息
     *
     * @param int $userId 用户ID
     * @return static|null
     */
    public static function findByUserId(int $userId): ?static
    {
        return static::findBy(['user_id' => $userId]);
    }

    /**
     * 检查会员是否有效
     *
     * @return bool
     */
    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->expires_at === null) {
            return true; // 永久会员
        }

        return strtotime($this->expires_at) > time();
    }
}
