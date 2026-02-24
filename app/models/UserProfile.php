<?php

declare(strict_types=1);

namespace app\models;

use Core\Orm\ModelAdapter;

/**
 * 用户资料模型
 * 
 * @package app\models
 */
class UserProfile extends ModelAdapter
{
    /**
     * 表名（不含前缀）
     */
    protected string $table = 'user_profiles';

    /**
     * 主键名
     */
    protected string $primaryKey = 'id';

    /**
     * 可填充属性
     */
    protected array $fillable = [
        'user_id',
        'real_name',
        'avatar',
        'gender',
        'birthdate',
        'bio',
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
     * 根据用户ID获取资料
     *
     * @param int $userId 用户ID
     * @return static|null
     */
    public static function findByUserId(int $userId): ?static
    {
        return static::findBy(['user_id' => $userId]);
    }
}
