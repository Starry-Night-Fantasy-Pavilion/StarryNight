# 管理端图标目录

此目录用于存放管理端专用的 SVG 图标文件。

## 图标规范

- 格式：SVG
- 尺寸：24x24（默认 viewBox="0 0 24 24"）
- 样式：使用 stroke 绘制，支持 currentColor 继承颜色
- 命名：小写字母，单词间用连字符分隔（如 `dashboard.svg`）

## 当前所需图标

管理端布局文件 `app/admin/views/layout.php` 中使用的图标：

| 图标名称 | 用途 |
|---------|------|
| dashboard | 运营仪表盘 |
| users | 用户管理 |
| mail | 通知模板 |
| book | 内容审查/知识库管理/系统配置 |
| activity | 社区内容/通知与公告/未来功能 |
| plugins | AI资源配置/插件管理 |
| themes | 主题管理 |
| check-circle | 一致性检查 |
| logout | 退出登录 |
| bell | 通知 |
| user | 个人资料 |

## 使用方式

在 PHP 模板中使用 `icon()` 辅助函数：

```php
<?= icon('dashboard') ?>
<?= icon('users', ['width' => '20', 'height' => '20']) ?>
```

## 注意事项

- 管理端图标与前台主题包图标完全隔离
- 管理端请求时，系统会自动从此目录加载图标
- 前台请求时，系统会从主题包的 `assets/icons/` 目录加载图标
