# 基础验证码插件

## 概述

基础验证码插件融合了两个免费API服务：
- 中文数字运算验证码：https://xxapi.cn/api/chineseCaptcha
- 随机验证码：https://xxapi.cn/api/captcha

插件会随机选择其中一个API来生成验证码，提供多样化的验证方式。

## 特性

- **随机调用**：随机选择中文数字运算或随机验证码API
- **灵活配置**：可单独启用/禁用每种验证码类型
- **本地缓存**：验证码数据存储在本地，减少API调用
- **自动过期**：验证码有过期机制，提高安全性
- **完整日志**：记录所有验证码生成和验证操作

## 安装

1. 将插件文件夹复制到：`public/plugins/verification/basic/recaptcha/`
2. 在管理后台的插件管理中安装并启用插件

## 配置

插件提供以下配置选项：

| 配置项 | 类型 | 默认值 | 说明 |
|---------|------|---------|------|
| api_timeout | number | 10 | API请求超时时间（秒） |
| cache_duration | number | 300 | 验证码缓存时间（秒） |
| use_chinese | boolean | true | 是否启用中文数字运算验证码 |
| use_random | boolean | true | 是否启用随机验证码 |

## 使用方法

### 生成验证码

```php
$plugin = new \Plugins\Verification\Basic\Recaptcha\RecaptchaPlugin();
$captcha = $plugin->generate();

// 返回数据结构：
[
    'token' => '验证令牌',
    'type' => 'chinese' 或 'random',
    'image' => '验证码图片URL',
    'question' => '验证码问题文本',
    'answer' => '正确答案',
    'expires_at' => 过期时间戳
]
```

### 验证验证码

```php
$plugin = new \Plugins\Verification\Basic\Recaptcha\RecaptchaPlugin();
$isValid = $plugin->verify($token, $userInput);

// 返回 true 或 false
```

### 获取前端组件

```php
$plugin = new \Plugins\Verification\Basic\Recaptcha\RecaptchaPlugin();
$widget = $plugin->getWidget();

// 返回包含验证码图片/问题和输入框的HTML代码
```

## API说明

### 中文数字运算验证码API

**端点**：`https://xxapi.cn/api/chineseCaptcha`

**功能**：生成中文数字运算题，如"三加五等于多少？"

**响应格式**：
```json
{
    "success": true,
    "data": {
        "question": "三加五等于多少？",
        "answer": "八"
    }
}
```

### 随机验证码API

**端点**：`https://xxapi.cn/api/captcha`

**功能**：生成随机字符验证码图片

**响应格式**：
```json
{
    "success": true,
    "data": {
        "image": "https://example.com/captcha/xxx.png"
    }
}
```

## 文件结构

```
recaptcha/
├── Plugin.php          # 插件主类
├── plugin.json         # 插件配置文件
└── README.md          # 说明文档
```

## 存储结构

插件使用以下存储目录：

- `storage/plugins/basic_captcha/cache/` - 验证码缓存文件
- `storage/plugins/basic_captcha/logs/` - 插件日志文件

## 安全性

- 验证码使用随机生成的令牌，防止猜测
- 验证码有过期机制，默认5分钟
- 验证成功后立即删除缓存数据
- 所有API请求使用HTTPS加密传输

## 日志记录

插件记录以下操作：
- 验证码生成成功/失败
- 验证码验证成功/失败
- 验证码过期
- API请求错误

日志文件按日期分割，便于管理和查看。

## 错误处理

插件会处理以下错误情况：
- API请求超时
- API响应解析失败
- 验证码不存在或已过期
- 验证码验证失败

## 许可证

MIT License

## 作者

StarryNight Team

## 版本历史

### v1.0.0 (2026-01-05)
- 初始版本
- 支持中文数字运算验证码
- 支持随机验证码
- 随机调用机制
- 本地缓存和日志功能
