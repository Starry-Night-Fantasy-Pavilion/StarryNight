<?php

namespace StarryNightEngine\Contracts;

final class UserTier
{
    public const REGULAR = 'regular';
    public const VIP = 'vip';

    /**
     * @param self::REGULAR|self::VIP $value
     */
    public function __construct(public string $value)
    {
        if (!in_array($value, [self::REGULAR, self::VIP], true)) {
            throw new \InvalidArgumentException('Invalid user tier: ' . $value);
        }
    }
}

