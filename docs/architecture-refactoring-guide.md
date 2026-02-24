# 架构重构指南

## 概述

本文档详细说明了针对代码审查报告中提出的问题的解决方案，包括：
1. 模型层统一
2. 命名空间重构
3. 数据库服务依赖注入
4. API版本控制

---

## 1. 模型层统一

### 问题分析

项目中存在两套模型实现：

| 实现方式 | 位置 | 特点 |
|---------|------|------|
| `Core\Orm\Model` | `core/Orm/Model.php` | Active Record模式，支持软删除、脏检查、时间戳 |
| `app\models\User` | `app/models/User.php` | 静态方法 + PDO直接操作 |

### 解决方案

创建 `Core\Orm\ModelAdapter` 基类，提供向后兼容的静态方法，同时支持依赖注入：

```php
// 新的模型基类
abstract class ModelAdapter extends Model
{
    protected static ?DatabaseService $dbService = null;

    public static function setDatabaseService(DatabaseService $service): void
    {
        static::$dbService = $service;
    }

    // 提供向后兼容的静态方法
    protected static function getPdo(): PDO;
    protected static function getPrefix(): string;
    protected static function queryAll(string $sql, array $params = []): array;
    // ...
}
```

### 迁移步骤

**步骤1：更新模型继承**

```php
// 旧代码
class User 
{
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        // ...
    }
}

// 新代码
class UserModel extends ModelAdapter
{
    protected string $table = 'users';
    
    // 使用ORM方法
    public static function findByUsername(string $username): ?static
    {
        return static::findBy(['username' => $username]);
    }
}
```

**步骤2：更新调用方式**

```php
// 旧方式
$user = User::find(1);

// 新方式（推荐）
$user = UserModel::find(1);
$user = UserModel::findByUsername('john');

// 兼容方式（过渡期）
$user = User::find(1); // 仍然可用，但标记为deprecated
```

---

## 2. 命名空间重构

### 问题分析

当前命名空间混乱：

| 命名空间 | 用途 | 问题 |
|---------|------|------|
| `Core\` | 核心框架 | 与其他框架冲突 |
| `app\` | 应用层 | 小写，不符合PSR-4 |
| `app\core\` | 应用核心 | 与`Core\`冲突 |
| `StarryNightEngine\` | AI引擎 | 独立命名空间 |

### 解决方案

统一使用 `StarryNight` 作为根命名空间：

```
StarryNight\
├── Core\           # 核心框架 (原 Core\)
│   ├── Orm\
│   ├── Routing\
│   ├── Services\
│   └── Security\
├── App\            # 应用层 (原 app\)
│   ├── Models\
│   ├── Services\
│   └── Controllers\
└── Engine\         # AI引擎 (原 StarryNightEngine\)
```

### 迁移配置

**composer.json**

```json
{
    "autoload": {
        "psr-4": {
            "StarryNight\\Core\\": "core/",
            "StarryNight\\App\\": "app/",
            "StarryNight\\Engine\\": "core/StarryNightEngine/src/"
        }
    }
}
```

**兼容层（过渡期）**

```php
// core/compat.php
class_alias(StarryNight\Core\Orm\Model::class, Core\Orm\Model::class);
class_alias(StarryNight\App\Models\User::class, app\models\User::class);
```

---

## 3. 数据库服务依赖注入

### 问题分析

静态方法难以测试：

```php
// 问题代码
class User 
{
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo(); // 静态依赖，难以mock
        // ...
    }
}
```

### 解决方案

创建可注入的 `DatabaseService`：

```php
// core/Services/DatabaseService.php
class DatabaseService implements ContainerInterface
{
    private ?PDO $pdo = null;
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'host' => '127.0.0.1',
            'port' => 3306,
            // ...
        ], $config);
    }

    public function getPdo(): PDO
    {
        if ($this->pdo === null) {
            $this->pdo = new PDO(/* ... */);
        }
        return $this->pdo;
    }

    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
}
```

### 使用方式

**在控制器中注入**

```php
class UserController
{
    private DatabaseService $db;

    public function __construct(DatabaseService $db)
    {
        $this->db = $db;
    }

    public function show(int $id): array
    {
        return $this->db->queryOne(
            "SELECT * FROM users WHERE id = ?",
            [$id]
        );
    }
}
```

**在模型中使用**

```php
class UserModel extends ModelAdapter
{
    public static function getDatabaseService(): DatabaseService
    {
        // 从容器获取或创建实例
        return Container::get('database') ?? DatabaseService::fromEnvironment();
    }
}
```

**测试时注入Mock**

```php
class UserModelTest extends TestCase
{
    public function testFindUser()
    {
        $mockDb = $this->createMock(DatabaseService::class);
        $mockDb->method('queryOne')
            ->willReturn(['id' => 1, 'username' => 'test']);

        UserModel::setDatabaseService($mockDb);

        $user = UserModel::find(1);
        $this->assertEquals('test', $user->username);
    }
}
```

---

## 4. API版本控制

### 问题分析

当前API无版本号：

```
/api/users        # 无版本号
/api/membership   # 无版本号
```

### 解决方案

创建 `ApiVersionManager`：

```php
// core/Api/ApiVersionManager.php
class ApiVersionManager
{
    private array $versions = ['v1'];
    private string $currentVersion = 'v1';
    private array $versionConfig = [];

    public function registerVersion(string $version, array $config = []): static
    {
        $this->versions[] = $version;
        $this->versionConfig[$version] = array_merge([
            'deprecated' => false,
            'sunset_date' => null,
            'migration_guide' => null,
        ], $config);
        return $this;
    }

    public function parseVersionFromUri(string $uri): array
    {
        // /api/v1/users -> ['v1', 'users']
        // /api/users -> ['v1', 'users'] (使用默认版本)
    }
}
```

### API路由规范

**新版本路由**

```
/api/v1/users           # 版本1
/api/v2/users           # 版本2
/api/v1/membership      # 版本1
```

**版本废弃通知**

```php
// 注册废弃版本
$versionManager->registerVersion('v1', [
    'deprecated' => true,
    'sunset_date' => '2024-12-31',
    'migration_guide' => 'https://docs.example.com/migration/v1-to-v2',
]);

// 自动添加响应头
// X-API-Deprecated: true
// X-API-Sunset: 2024-12-31
// Link: <https://docs.example.com/migration/v1-to-v2>; rel="deprecation-guide"
```

### 控制器组织

```
app/
└── Api/
    ├── v1/
    │   └── Controllers/
    │       ├── UserController.php
    │       └── MembershipController.php
    └── v2/
        └── Controllers/
            ├── UserController.php
            └── MembershipController.php
```

---

## 5. 迁移计划

### 阶段1：基础设施（1-2天）

- [x] 创建 `DatabaseService`
- [x] 创建 `ModelAdapter`
- [x] 创建 `ApiVersionManager`

### 阶段2：模型迁移（3-5天）

- [ ] 创建 `UserModel` 继承 `ModelAdapter`
- [ ] 创建 `UserTokenBalanceModel`
- [ ] 创建其他模型
- [ ] 更新控制器使用新模型

### 阶段3：命名空间重构（2-3天）

- [ ] 更新 `composer.json`
- [ ] 批量替换命名空间
- [ ] 创建兼容层
- [ ] 更新自动加载

### 阶段4：API版本化（1-2天）

- [ ] 重命名API目录
- [ ] 更新路由配置
- [ ] 添加版本响应头

### 阶段5：测试与文档（2-3天）

- [ ] 编写单元测试
- [ ] 更新API文档
- [ ] 更新开发指南

---

## 6. 向后兼容策略

### 兼容层

在过渡期保留旧的静态方法：

```php
// app/services/Database.php (保留)
class Database
{
    public static function pdo(): PDO
    {
        // 委托给新服务
        return DatabaseService::fromEnvironment()->getPdo();
    }
}
```

### 废弃警告

```php
/**
 * @deprecated 使用 UserModel::find() 替代
 * @see UserModel::find()
 */
public static function find(int $id): ?array
{
    trigger_error('User::find() is deprecated, use UserModel::find()', E_USER_DEPRECATED);
    return UserModel::find($id)?->toArray();
}
```

---

## 7. 最佳实践

### 模型设计

```php
// ✅ 推荐：使用ORM模型
$user = UserModel::find(1);
$user->nickname = 'new name';
$user->save();

// ❌ 避免：直接SQL操作
$pdo = Database::pdo();
$pdo->prepare("UPDATE users SET nickname = ? WHERE id = ?");
```

### 依赖注入

```php
// ✅ 推荐：构造函数注入
class UserService
{
    public function __construct(
        private DatabaseService $db,
        private CacheService $cache
    ) {}
}

// ❌ 避免：静态依赖
class UserService
{
    public function getUsers()
    {
        return Database::queryAll("SELECT * FROM users");
    }
}
```

### API版本控制

```php
// ✅ 推荐：版本化路由
$router->group(['prefix' => 'api/v1'], function ($group) {
    $group->get('/users', [UserController::class, 'index']);
});

// ❌ 避免：无版本路由
$router->get('/api/users', [UserController::class, 'index']);
```

---

## 8. 相关文件

| 文件 | 说明 |
|------|------|
| `core/Services/DatabaseService.php` | 数据库服务类 |
| `core/Orm/ModelAdapter.php` | 模型适配器基类 |
| `core/Api/ApiVersionManager.php` | API版本管理器 |
| `app/models/UserModel.php` | 用户模型示例 |

---

## 9. 参考资料

- [PSR-4: Autoloader Standard](https://www.php-fig.org/psr/psr-4/)
- [PSR-11: Container Interface](https://www.php-fig.org/psr/psr-11/)
- [Active Record Pattern](https://martinfowler.com/eaaCatalog/activeRecord.html)
- [API Versioning Best Practices](https://www.postman.com/api-platform/api-versioning/)
