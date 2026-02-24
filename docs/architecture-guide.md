# 架构重构指南

本文档介绍项目架构重构的内容，包括ORM框架、路由系统、Service/Repository分层、Redis缓存增强和API规范化。

## 目录

1. [ORM框架](#orm框架)
2. [路由系统](#路由系统)
3. [Service/Repository分层](#servicerepository分层)
4. [Redis缓存增强](#redis缓存增强)
5. [API规范化](#api规范化)

---

## ORM框架

### 概述

引入了轻量级ORM框架，实现Active Record模式，提供流畅的数据库操作接口。

### 核心类

- `Core\Orm\Model` - 基础模型类
- `Core\Orm\QueryBuilder` - 查询构建器
- `Core\Orm\Repository` - 数据仓库基类

### 使用示例

#### 定义模型

```php
<?php

namespace app\models;

use Core\Orm\Model;

class User extends Model
{
    protected string $table = 'users';
    protected string $primaryKey = 'id';
    protected array $fillable = ['username', 'email', 'nickname', 'status'];
    protected array $hidden = ['password'];
    protected bool $timestamps = true;
}
```

#### 基础查询

```php
// 查找单条
$user = User::find(1);
$user = User::where('email', 'test@example.com')->first();

// 查找所有
$users = User::all();
$users = User::where('status', 'active')->get();

// 条件查询
$users = User::where('status', 'active')
    ->where('vip_type', '!=', 'normal')
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->get();

// 分页
$users = User::forPage(1, 15)->get();
```

#### 创建和更新

```php
// 创建
$user = User::create([
    'username' => 'test',
    'email' => 'test@example.com',
    'password' => password_hash('123456', PASSWORD_DEFAULT),
]);

// 更新
$user->nickname = '新昵称';
$user->save();

// 批量更新
User::where('status', 'pending')->update(['status' => 'active']);
```

#### 删除

```php
// 删除单条
$user->delete();

// 条件删除
User::where('status', 'deleted')->delete();
```

#### 聚合函数

```php
$count = User::count();
$count = User::where('status', 'active')->count();
$sum = User::sum('balance');
$avg = User::avg('age');
$max = User::max('created_at');
```

---

## 路由系统

### 概述

统一的路由管理系统，支持RESTful路由、路由分组、中间件和路由缓存。

### 核心类

- `Core\Routing\Router` - 路由器
- `Core\Routing\RouteManager` - 路由管理器

### 路由配置

路由配置文件位于 `config/routes.php`：

```php
return [
    'api' => [
        'prefix' => '/api',
        'middleware' => ['api'],
        'routes' => [
            ['GET', '/users', [\app\frontend\controller\UserController::class, 'index']],
            ['POST', '/users', [\app\frontend\controller\UserController::class, 'store']],
            ['GET', '/users/{id}', [\app\frontend\controller\UserController::class, 'show']],
            ['PUT', '/users/{id}', [\app\frontend\controller\UserController::class, 'update']],
            ['DELETE', '/users/{id}', [\app\frontend\controller\UserController::class, 'destroy']],
        ],
    ],
];
```

### 使用示例

```php
use Core\Routing\Router;
use Core\Routing\RouteManager;

// 创建路由管理器
$router = new Router();
$routeManager = new RouteManager($router);

// 加载配置并注册路由
$routeManager->load()->register();

// 分发请求
$result = $routeManager->dispatch();

// 生成URL
$url = $routeManager->url('user.profile', ['id' => 1]);
```

### RESTful资源路由

```php
// API资源路由（排除create和edit）
$routeManager->apiResource('users', UserController::class);

// 完整资源路由
$routeManager->resource('posts', PostController::class);
```

---

## Service/Repository分层

### 概述

采用Service/Repository分层架构，分离业务逻辑和数据访问。

### 核心类

- `Core\Orm\Repository` - Repository基类
- `Core\Services\BaseService` - Service基类

### Repository层

负责数据访问，封装数据库操作：

```php
<?php

namespace app\repositories;

use Core\Orm\Repository;

class UserRepository extends Repository
{
    protected string $table = 'users';
    protected string $primaryKey = 'id';

    // 自定义查询方法
    public function findByEmail(string $email): ?array
    {
        return $this->findFirstBy(['email' => $email]);
    }

    public function getActiveUsers(int $limit = 10): array
    {
        return $this->query()
            ->where('status', 'active')
            ->orderBy('last_login_at', 'DESC')
            ->limit($limit)
            ->get();
    }
}
```

### Service层

负责业务逻辑，调用Repository进行数据操作：

```php
<?php

namespace app\services;

use Core\Services\BaseService;
use app\repositories\UserRepository;

class UserService extends BaseService
{
    public function __construct(?UserRepository $repository = null)
    {
        parent::__construct($repository ?? new UserRepository());
    }

    public function login(string $email, string $password): array
    {
        $user = $this->repository->findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => '认证失败'];
        }

        // 更新登录时间
        $this->repository->updateLastLogin($user['id']);

        return ['success' => true, 'user' => $user];
    }

    // 使用缓存
    public function getUserInfo(int $userId): ?array
    {
        return $this->remember("user_info:{$userId}", function () use ($userId) {
            return $this->repository->find($userId);
        });
    }
}
```

---

## Redis缓存增强

### 概述

增强版缓存服务，支持缓存标签、分布式锁、批量操作等高级功能。

### 核心类

- `Core\Services\EnhancedCacheService` - 增强版缓存服务

### 使用示例

#### 基础操作

```php
use Core\Services\EnhancedCacheService;

$cache = new EnhancedCacheService();

// 获取/设置
$value = $cache->get('key');
$value = $cache->get('key', fn() => expensiveOperation(), 3600);
$cache->set('key', 'value', 3600);

// 删除
$cache->delete('key');
$cache->deleteByPattern('user:*');

// 检查
$exists = $cache->has('key');
```

#### 缓存标签

```php
// 设置带标签的缓存
$cache->tags(['user', 'profile'])->setWithTag('user:1:profile', $profile);

// 清除标签下的所有缓存
$cache->clearTag('user');
```

#### 分布式锁

```php
// 获取锁
$token = $cache->lock('resource:1', 30);
if ($token) {
    try {
        // 执行需要锁保护的操作
        doSomething();
    } finally {
        $cache->unlock('resource:1', $token);
    }
}

// 或者使用便捷方法
$result = $cache->withLock('resource:1', function () {
    return doSomething();
});
```

#### 批量操作

```php
// 批量获取
$values = $cache->multiGet(['key1', 'key2', 'key3']);

// 批量设置
$cache->multiSet([
    'key1' => 'value1',
    'key2' => 'value2',
], 3600);
```

#### 自增/自减

```php
$cache->increment('counter');
$cache->increment('counter', 5);
$cache->decrement('counter');
```

---

## API规范化

### 概述

统一的API响应格式，支持成功/错误响应、分页数据、错误码映射。

### 核心类

- `Core\Api\ApiResponse` - API响应类

### 响应格式

#### 成功响应

```json
{
    "success": true,
    "code": 0,
    "message": "操作成功",
    "data": { ... },
    "timestamp": 1708400000,
    "version": "1.0"
}
```

#### 错误响应

```json
{
    "success": false,
    "code": 1001,
    "message": "验证失败",
    "errors": {
        "email": ["邮箱格式不正确"]
    },
    "timestamp": 1708400000,
    "version": "1.0"
}
```

#### 分页响应

```json
{
    "success": true,
    "code": 0,
    "message": "获取成功",
    "data": [...],
    "pagination": {
        "total": 100,
        "page": 1,
        "per_page": 15,
        "total_pages": 7,
        "has_more": true
    },
    "timestamp": 1708400000,
    "version": "1.0"
}
```

### 使用示例

```php
use Core\Api\ApiResponse;

// 成功响应
ApiResponse::success(['user' => $user], '获取成功')->send();

// 错误响应
ApiResponse::error(ErrorCode::VALIDATION_ERROR, '验证失败', $errors)->send();

// 分页响应
ApiResponse::paginated($items, $total, $page, $perPage)->send();

// 链式调用
ApiResponse::success($data)
    ->message('自定义消息')
    ->meta(['extra' => 'data'])
    ->send();

// 快捷方法
ApiResponse::created($data)->send();          // 201 Created
ApiResponse::noContent()->send();             // 204 No Content
ApiResponse::unauthorized()->send();          // 401 Unauthorized
ApiResponse::forbidden()->send();             // 403 Forbidden
ApiResponse::notFound()->send();              // 404 Not Found
```

---

## 迁移指南

### 从旧代码迁移

1. **Model迁移**
   - 将现有的Model类继承 `Core\Orm\Model`
   - 定义 `$table`、`$primaryKey`、`$fillable` 等属性
   - 使用查询构建器替代原生SQL

2. **创建Repository**
   - 为每个Model创建对应的Repository
   - 将数据访问逻辑从Controller移到Repository

3. **创建Service**
   - 将业务逻辑从Controller移到Service
   - 通过依赖注入使用Repository

4. **更新Controller**
   - 使用Service处理业务逻辑
   - 使用ApiResponse返回统一格式响应

### 示例对比

**旧代码：**

```php
class UserController
{
    public function show($id)
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        echo json_encode(['success' => true, 'data' => $user]);
    }
}
```

**新代码：**

```php
class UserController
{
    private UserService $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    public function show($id)
    {
        $user = $this->userService->getUserInfo((int)$id);
        
        if (!$user) {
            ApiResponse::notFound('用户不存在')->send();
        }
        
        ApiResponse::success(['user' => $user])->send();
    }
}
```

---

## 最佳实践

1. **单一职责**：每个类只负责一件事
2. **依赖注入**：通过构造函数注入依赖
3. **接口隔离**：使用接口定义契约
4. **缓存策略**：合理使用缓存，注意缓存失效
5. **错误处理**：使用统一的错误码和异常处理
6. **日志记录**：在Service层记录关键操作日志

---

## 文件结构

```
core/
├── Orm/
│   ├── Model.php
│   ├── ModelInterface.php
│   ├── QueryBuilder.php
│   ├── Repository.php
│   └── RepositoryInterface.php
├── Routing/
│   ├── Router.php
│   └── RouteManager.php
├── Services/
│   ├── BaseService.php
│   └── EnhancedCacheService.php
└── Api/
    └── ApiResponse.php

app/
├── models/           # 数据模型
├── repositories/     # 数据仓库
├── services/         # 业务服务
└── frontend/
    └── controller/   # 控制器

config/
└── routes.php        # 路由配置
```
