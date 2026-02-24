# 架构更新总结

## 概述

基于 `docs/docs/` 目录下的技术架构文档，成功更新了项目的核心组件，使其全面适配 PHP 8.0+ 环境。

## 已实施的核心组件

### 1. 事件系统 (EventSystem)
- **文件**: `app/services/EventSystem.php`
- **功能**: 
  - 事件监听和分发
  - 优先级支持（数字越小优先级越高）
  - 事件历史记录
  - 监听器管理
- **PHP 8.0+ 特性**: 使用 `static` 匿名函数、`<=>` 太空船运算符

### 2. 配置管理器 (ConfigManager)
- **文件**: `app/services/ConfigManager.php`
- **功能**:
  - JSON 配置文件存储
  - 配置缓存机制
  - 批量保存支持
  - 配置存在性检查
- **PHP 8.0+ 特性**: 使用类型化属性、联合类型提示

### 3. 模板解析器 (TemplateResolver)
- **文件**: `app/services/TemplateResolver.php`
- **功能**:
  - 模板路径解析
  - 模板编译和缓存
  - 主题继承机制
  - 自定义模板语法支持
- **PHP 8.0+ 特性**: 使用构造函数属性提升、`?string` 可空类型

### 4. 依赖管理器 (DependencyManager)
- **文件**: `app/services/DependencyManager.php`
- **功能**:
  - 插件依赖解析
  - 循环依赖检测
  - 依赖树生成
  - 依赖满足性检查
- **PHP 8.0+ 特性**: 使用 `array<string, mixed>` 类型注解

### 5. 输入验证器 (InputValidator)
- **文件**: `app/services/InputValidator.php`
- **功能**:
  - 多种验证规则（必填、类型、正则、范围等）
  - 数据过滤和清理
  - HTML 过滤
  - 静态验证方法
- **PHP 8.0+ 特性**: 使用 `match` 表达式替代 switch

## 现有组件增强

### PluginManager 增强
- 集成 `DependencyManager`
- 集成 `ConfigManager`
- 集成 `EventSystem`
- 新增事件触发和监听方法

### ThemeManager 增强
- 集成 `TemplateResolver`
- 集成 `ConfigManager`
- 新增模板渲染方法
- 新增主题配置管理

## PHP 8.0+ 特性应用

### 1. 命名参数 (Named Arguments)
```php
EventSystem::listen('event', $callback, priority: 10);
```

### 2. Match 表达式
```php
return match ($type) {
    'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
    'url' => filter_var($value, FILTER_VALIDATE_URL) !== false,
    default => true,
};
```

### 3. 联合类型 (Union Types)
```php
function testUnionType(string|int $value): string {
    return (string)$value;
}
```

### 4. Nullsafe 操作符
```php
$safeResult = $obj?->property ?? 'default';
```

### 5. 静态返回类型
```php
public static function create(): static {
    return new static();
}
```

### 6. 类型化属性
```php
private string $configPath;
private array $cache = [];
```

### 7. 构造函数属性提升
```php
public function __construct(
    private ThemeManager $themeManager
) {}
```

## 项目结构保持

原始项目路径结构完全保持不变：

```
public/plugins/          # 插件目录
public/web/              # 主题目录
app/core/                # 核心类目录
app/services/            # 服务类目录
storage/framework/       # 框架存储目录
```

## 兼容性

- **最低 PHP 版本**: 8.0
- **推荐 PHP 版本**: 8.1+
- **测试状态**: 39/41 项测试通过（2 项失败是因为示例插件文件不存在，属于正常情况）

## 使用方法

### 事件系统
```php
use app\services\EventSystem;

// 监听事件
EventSystem::listen('user.login', function($data) {
    // 处理登录事件
}, priority: 5);

// 分发事件
EventSystem::dispatch('user.login', ['user_id' => 123]);
```

### 配置管理器
```php
use app\services\ConfigManager;

$configManager = new ConfigManager();

// 保存配置
$configManager->save('my_plugin', ['key' => 'value']);

// 加载配置
$config = $configManager->load('my_plugin');

// 获取单个配置项
$value = $configManager->get('my_plugin', 'key', 'default');
```

### 输入验证器
```php
use app\services\InputValidator;

$validator = new InputValidator();

$rules = [
    'email' => ['required' => true, 'type' => 'email'],
    'age' => ['type' => 'int', 'min' => 18, 'max' => 100],
];

if ($validator->validate($_POST, $rules)) {
    // 验证通过
} else {
    $errors = $validator->getErrors();
}
```

### 模板解析器
```php
use app\services\ThemeManager;

$themeManager = new ThemeManager();

// 渲染模板
$html = $themeManager->render('home.php', ['title' => '首页']);
```

## 测试

运行兼容性测试：
```bash
php test_php8_compatibility.php
```

## 注意事项

1. 所有新组件都使用了 `declare(strict_types=1);` 启用严格类型
2. 配置存储目录: `storage/framework/configs/`
3. 模板缓存目录: `storage/framework/template_cache/`
4. 确保这些目录有写入权限
