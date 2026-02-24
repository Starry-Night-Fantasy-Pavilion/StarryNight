# SMTP 邮箱服务插件

## 简介

SMTP 邮箱服务插件是一个基于 PHPMailer 实现的邮件发送插件，支持通过 SMTP 协议发送系统邮件。该插件支持 SSL/TLS 加密、重试机制、附件发送等功能。

## 功能特性

- ✅ 支持 SSL/TLS 加密连接
- ✅ 支持多种邮件服务商（QQ邮箱、163邮箱、Gmail等）
- ✅ 自动重试机制，提高发送成功率
- ✅ 支持附件、抄送、密送
- ✅ 灵活的SSL证书验证配置
- ✅ 连接超时和保持连接配置
- ✅ 详细的错误处理和日志记录

## 安装要求

- PHP >= 8.0
- PHP 扩展：openssl, mbstring
- Composer 依赖：phpmailer/phpmailer (^6.0)

## 配置说明

### 基本配置

1. **SMTP 服务器地址** (`host`)
   - 例如：`smtp.qq.com`、`smtp.163.com`、`smtp.gmail.com`
   - 必填项

2. **SMTP 端口** (`port`)
   - SSL: 465
   - TLS: 587
   - 不加密: 25
   - 必填项

3. **SMTP 登录用户名** (`username`)
   - 通常是完整的邮箱地址
   - 必填项

4. **SMTP 登录密码** (`password`)
   - 部分邮箱需要使用授权码而非登录密码
   - 必填项

5. **加密方式** (`smtpsecure`)
   - SSL（常用端口 465）
   - TLS（常用端口 587）
   - 不加密
   - 必填项

6. **发件人名称** (`fromname`)
   - 显示在收件人邮箱中的发件人名称
   - 可选

7. **系统邮箱地址** (`systememail`)
   - 用于作为发件人邮箱地址，需与 SMTP 账号匹配
   - 必填项

8. **邮件编码** (`charset`)
   - 默认：utf-8
   - 可选

### 高级配置

9. **连接超时时间** (`timeout`)
   - 单位：秒
   - 默认：30
   - 范围：5-120

10. **保持连接** (`keepalive`)
    - 启用后保持SMTP连接，提高发送效率
    - 默认：关闭

11. **重试次数** (`retry_attempts`)
    - 发送失败时的重试次数
    - 默认：3
    - 范围：1-10

12. **重试延迟** (`retry_delay`)
    - 每次重试之间的延迟时间（秒）
    - 默认：5
    - 范围：1-60

13. **验证SSL证书** (`verify_peer`)
    - 启用后验证SSL证书
    - 如果连接失败可尝试关闭此选项
    - 默认：关闭

14. **验证SSL证书名称** (`verify_peer_name`)
    - 启用后验证SSL证书名称
    - 如果连接失败可尝试关闭此选项
    - 默认：关闭

15. **调试级别** (`debug`)
    - 0 = 关闭
    - 1 = 客户端消息
    - 2 = 客户端和服务器消息
    - 3 = 连接信息
    - 4 = 详细调试
    - 默认：0

## 常用邮箱配置示例

### QQ邮箱

```json
{
  "host": "smtp.qq.com",
  "port": 465,
  "username": "your_email@qq.com",
  "password": "授权码",
  "smtpsecure": "ssl",
  "systememail": "your_email@qq.com",
  "fromname": "星夜阁"
}
```

### 163邮箱

```json
{
  "host": "smtp.163.com",
  "port": 465,
  "username": "your_email@163.com",
  "password": "授权码",
  "smtpsecure": "ssl",
  "systememail": "your_email@163.com",
  "fromname": "星夜阁"
}
```

### Gmail

```json
{
  "host": "smtp.gmail.com",
  "port": 587,
  "username": "your_email@gmail.com",
  "password": "应用专用密码",
  "smtpsecure": "tls",
  "systememail": "your_email@gmail.com",
  "fromname": "星夜阁"
}
```

## 使用方法

### 在代码中调用

```php
// 使用全局辅助函数
$result = send_system_mail(
    'recipient@example.com',  // 收件人
    '邮件主题',                // 主题
    '<h1>邮件内容</h1>',       // HTML内容
    $errorMsg                 // 错误信息（输出参数）
);

if ($result) {
    echo '邮件发送成功';
} else {
    echo '邮件发送失败: ' . $errorMsg;
}
```

### 发送带附件的邮件

```php
// 直接使用插件实例
$plugin = new \Plugins\Email\Smtp_service\Plugin();
$result = $plugin->send(
    'recipient@example.com',
    '邮件主题',
    '<h1>邮件内容</h1>',
    [
        'attachments' => [
            '/path/to/file1.pdf',
            '/path/to/file2.jpg'
        ],
        'cc' => 'cc@example.com',
        'bcc' => 'bcc@example.com'
    ]
);
```

## 故障排查

### 连接失败

1. 检查 SMTP 服务器地址和端口是否正确
2. 检查防火墙是否允许连接
3. 尝试关闭 SSL 证书验证（`verify_peer` 和 `verify_peer_name` 设为 false）

### 认证失败

1. 确认用户名和密码正确
2. 部分邮箱需要使用授权码而非登录密码
3. 检查邮箱是否开启了 SMTP 服务

### 发送超时

1. 增加 `timeout` 配置值
2. 检查网络连接是否稳定
3. 尝试使用不同的加密方式

## 更新日志

### v1.0.0 (2026-02-21)
- 初始版本发布
- 支持基本的 SMTP 邮件发送功能
- 支持 SSL/TLS 加密
- 支持重试机制
- 支持附件、抄送、密送

## 技术支持

如有问题，请查看错误日志：`storage/logs/error.log`

## 许可证

MIT License
