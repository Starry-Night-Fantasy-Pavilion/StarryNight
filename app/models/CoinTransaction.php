<?php

declare(strict_types=1);

namespace app\models;

use Core\Orm\ModelAdapter;

/**
 * 代币交易记录模型
 * 
 * @package app\models
 */
class CoinTransaction extends ModelAdapter
{
    /**
     * 表名（不含前缀）
     */
    protected string $table = 'coin_transactions';

    /**
     * 主键名
     */
    protected string $primaryKey = 'id';

    /**
     * 可填充属性
     */
    protected array $fillable = [
        'user_id',
        'type',
        'amount',
        'balance_after',
        'remark',
        'related_id',
        'related_type',
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
     * 根据用户ID获取交易记录
     *
     * @param int $userId 用户ID
     * @param int $limit 限制数量
     * @return array
     */
    public static function getRecentByUserId(int $userId, int $limit = 10): array
    {
        return static::newQuery()
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
