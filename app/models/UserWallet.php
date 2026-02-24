<?php

declare(strict_types=1);

namespace app\models;

use Core\Orm\ModelAdapter;

/**
 * 用户钱包模型
 * 
 * @package app\models
 */
class UserWallet extends ModelAdapter
{
    /**
     * 表名（不含前缀）
     */
    protected string $table = 'user_wallets';

    /**
     * 主键名
     */
    protected string $primaryKey = 'id';

    /**
     * 可填充属性
     */
    protected array $fillable = [
        'user_id',
        'balance',
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
     * 根据用户ID获取钱包
     *
     * @param int $userId 用户ID
     * @return static|null
     */
    public static function findByUserId(int $userId): ?static
    {
        return static::findBy(['user_id' => $userId]);
    }

    /**
     * 获取或创建用户钱包
     *
     * @param array $attributes 查询条件
     * @param array $values 创建时的默认值
     * @return static
     */
    public static function firstOrCreate(array $attributes, array $values = []): static
    {
        $model = static::findBy($attributes);
        
        if ($model !== null) {
            return $model;
        }
        
        $data = array_merge($attributes, $values);
        static::create($data);
        
        return static::findBy($attributes);
    }

    /**
     * 增加余额
     *
     * @param float $amount 金额
     * @return bool
     */
    public function incrementBalance(float $amount): bool
    {
        $this->balance += $amount;
        return $this->save();
    }

    /**
     * 减少余额
     *
     * @param float $amount 金额
     * @return bool
     */
    public function decrementBalance(float $amount): bool
    {
        $this->balance -= $amount;
        return $this->save();
    }
}
