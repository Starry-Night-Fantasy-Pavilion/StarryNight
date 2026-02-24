<?php

namespace Core;

/**
 * 短信插件基类
 */
abstract class SMSPlugin extends PluginBase
{
    /**
     * 发送短信
     * @param string $phone 手机号码
     * @param string $message 短信内容
     * @param array $options 其他选项
     * @return bool 发送是否成功
     */
    abstract public function send(string $phone, string $message, array $options = []): bool;
}
