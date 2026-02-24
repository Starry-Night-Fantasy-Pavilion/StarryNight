<?php

declare(strict_types=1);

namespace app\models;

use Core\Orm\ModelAdapter;

/**
 * 用户代币余额模型 (ORM版本)
 * 
 * @package app\models
 */
class UserTokenBalanceModel extends ModelAdapter
{
    /**
     * 表名（不含前缀）
     */
    protected string $table = 'user_token_balance';

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
        'total_recharged',
        'total_consumed',
        'total_bonus',
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
     * 根据用户ID获取余额记录
     *
     * @param int $userId 用户ID
     * @return static|null
     */
    public static function findByUserId(int $userId): ?static
    {
        return static::findBy(['user_id' => $userId]);
    }

    /**
     * 获取或创建用户余额记录
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
     * 增加代币余额
     *
     * @param int $tokens 代币数量
     * @return bool
     */
    public function addTokens(int $tokens): bool
    {
        $this->balance += $tokens;
        return $this->save();
    }

    /**
     * 消费代币
     *
     * @param int $tokens 代币数量
     * @return bool
     */
    public function consumeTokens(int $tokens): bool
    {
        if ($this->balance < $tokens) {
            return false;
        }
        
        $this->balance -= $tokens;
        $this->total_consumed += $tokens;
        return $this->save();
    }
}
