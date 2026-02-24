<?php
/**
 * 通用事件类
 *
 * @package Core\Events
 * @version 1.0.0
 */

namespace Core\Events;

/**
 * 通用事件类
 */
class GenericEvent extends Event
{
    /**
     * 构造函数
     */
    public function __construct(string $name, array $data = [])
    {
        $this->name = $name;
        parent::__construct($data);
    }
}
