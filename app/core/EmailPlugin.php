<?php

namespace Core;

/**
 * 邮件插件基类
 */
abstract class EmailPlugin extends PluginBase
{
    /**
     * 发送邮件
     * @param string $to 收件人邮箱
     * @param string $subject 邮件主题
     * @param string $body 邮件内容
     * @param array $options 其他选项（如附件、抄送等）
     * @return bool 发送是否成功
     */
    abstract public function send(string $to, string $subject, string $body, array $options = []): bool;
}
