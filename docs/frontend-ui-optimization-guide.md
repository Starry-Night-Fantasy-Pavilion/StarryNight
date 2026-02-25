# 前端UI使用后端类优化设计指南

## 概述

本文档介绍如何使用优化后的前端UI与后端类交互设计，包括统一的数据绑定、API响应、模板变量解析等功能。

## 核心服务类

### 1. FrontendDataService - 前端数据服务

提供统一的前端数据绑定、格式化、验证等功能。

#### 主要功能

- **prepareViewData()** - 准备视图数据，自动添加默认值
- **validateInput()** - 验证和清理输入数据
- **createApiResponse()** - 创建统一的API响应
- **formatDateTime()** - 格式化日期时间
- **formatFileSize()** - 格式化文件大小

#### 使用示例

```php
use app\services\FrontendDataService;

// 在控制器中准备视图数据
public function index()
{
    $data = [
        'title' => '用户列表',
        'users' => $this->userService->getAll(),
    ];
    
    // 自动添加默认值（site_name, site_url, current_year等）
    $preparedData = FrontendDataService::prepareViewData($data);
    
    $this->view('user/index', $preparedData);
}

// 验证输入数据
public function create()
{
    $rules = [
        'username' => [
            'required' => true,
            'type' => 'string',
            'min_length' => 3,
            'max_length' => 20,
            'message' => '用户名长度必须在3-20个字符之间',
        ],
        'email' => [
            'required' => true,
            'type' => 'email',
            'message' => '请输入有效的邮箱地址',
        ],
    ];
    
    $result = FrontendDataService::validateInput($_POST, $rules);
    
    if (!$result['valid']) {
        $this->sendError(1001, '验证失败', $result['errors']);
    }
    
    // 使用验证后的数据
    $user = $this->userService->create($result['data']);
    $this->sendSuccess($user, '用户创建成功');
}
```

### 2. TemplateVariableResolver - 模板变量解析器

统一处理各种模板变量格式（{{variable}}、{$variable}等）。

#### 主要功能

- **resolve()** - 解析模板内容并替换变量
- **resolveEmailTemplate()** - 解析邮件模板
- **resolveViewTemplate()** - 解析视图模板
- **extractVariables()** - 提取模板中的变量名
- **validateVariables()** - 验证模板变量是否完整

#### 使用示例

```php
use app\services\TemplateVariableResolver;

// 解析邮件模板
$template = file_get_contents('/path/to/email_template.html');
$variables = [
    'username' => '张三',
    'code' => '123456',
    'time' => '2024-01-01 10:00:00',
];

$content = TemplateVariableResolver::resolveEmailTemplate($template, $variables);

// 在控制器中使用
protected function resolveTemplate(string $content, array $variables = []): string
{
    return TemplateVariableResolver::resolve($content, $variables);
}
```

### 3. FrontendUIComponent - 前端UI组件

封装常用的前端UI逻辑，提供统一的UI组件生成方法。

#### 主要功能

- **renderPagination()** - 生成分页HTML
- **renderTable()** - 生成表格HTML
- **renderFormField()** - 生成表单字段HTML
- **renderAlert()** - 生成消息提示HTML
- **renderEmptyState()** - 生成空状态HTML

#### 使用示例

```php
use app\services\FrontendUIComponent;

// 在视图中使用
<?php
$pagination = [
    'page' => 1,
    'total_pages' => 10,
    'total' => 100,
];

echo FrontendUIComponent::renderPagination($pagination, '/users');
?>

// 生成表格
<?php
$headers = ['ID', '用户名', '邮箱', '操作'];
$rows = [
    [1, '张三', 'zhangsan@example.com', '<a href="/edit/1">编辑</a>'],
    [2, '李四', 'lisi@example.com', '<a href="/edit/2">编辑</a>'],
];

echo FrontendUIComponent::renderTable($headers, $rows, ['class' => 'user-table']);
?>
```

## 控制器优化

### 优化后的控制器基类

`Core\Controller` 和 `app\admin\controller\BaseController` 已经优化，提供统一的方法：

#### 新增方法

- **apiResponse()** - 返回统一的API响应对象
- **sendSuccess()** - 发送API成功响应
- **sendError()** - 发送API错误响应
- **sendPaginated()** - 发送分页响应
- **validateInput()** - 验证输入数据
- **resolveTemplate()** - 解析模板变量

#### 使用示例

```php
use Core\Controller;
use app\services\FrontendDataService;

class UserController extends Controller
{
    // 旧方式（不推荐）
    public function getUsersOld()
    {
        $users = $this->userService->getAll();
        $this->json([
            'success' => true,
            'data' => $users,
        ]);
    }
    
    // 新方式（推荐）
    public function getUsers()
    {
        $users = $this->userService->getAll();
        $this->sendSuccess($users, '获取用户列表成功');
    }
    
    // 分页响应
    public function getUsersPaginated()
    {
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 20);
        
        $result = $this->userService->getPaginated($page, $perPage);
        
        $this->sendPaginated(
            $result['items'],
            $result['total'],
            $page,
            $perPage,
            '获取用户列表成功'
        );
    }
    
    // 验证输入
    public function createUser()
    {
        $rules = [
            'username' => [
                'required' => true,
                'type' => 'string',
                'min_length' => 3,
            ],
            'email' => [
                'required' => true,
                'type' => 'email',
            ],
        ];
        
        $validation = $this->validateInput($_POST, $rules);
        
        if (!$validation['valid']) {
            $this->sendError(1001, '验证失败', $validation['errors']);
        }
        
        $user = $this->userService->create($validation['data']);
        $this->sendSuccess($user, '用户创建成功');
    }
    
    // 视图渲染（自动准备数据）
    public function index()
    {
        $data = [
            'title' => '用户管理',
            'users' => $this->userService->getAll(),
        ];
        
        // 自动添加默认值
        $this->view('user/index', $data);
    }
}
```

## 迁移指南

### 1. 替换旧的JSON响应

**旧代码：**
```php
$this->json([
    'success' => true,
    'data' => $data,
]);
```

**新代码：**
```php
$this->sendSuccess($data, '操作成功');
```

### 2. 统一错误响应

**旧代码：**
```php
$this->json([
    'success' => false,
    'error' => '错误信息',
], 400);
```

**新代码：**
```php
$this->sendError(1001, '错误信息');
```

### 3. 使用模板变量解析

**旧代码：**
```php
$content = str_replace('{{username}}', $username, $template);
$content = str_replace('{{code}}', $code, $content);
```

**新代码：**
```php
$content = TemplateVariableResolver::resolve($template, [
    'username' => $username,
    'code' => $code,
]);
```

### 4. 使用UI组件

**旧代码：**
```php
// 手动编写分页HTML
echo '<div class="pagination">...</div>';
```

**新代码：**
```php
echo FrontendUIComponent::renderPagination($pagination);
```

## 最佳实践

1. **统一使用ApiResponse类** - 所有API响应都使用 `Core\Api\ApiResponse`
2. **使用FrontendDataService准备数据** - 视图数据统一使用 `prepareViewData()`
3. **验证输入数据** - 使用 `validateInput()` 方法验证所有用户输入
4. **使用UI组件** - 常用UI组件使用 `FrontendUIComponent` 生成
5. **模板变量统一解析** - 使用 `TemplateVariableResolver` 处理所有模板变量

## 注意事项

1. 旧的 `json()` 方法仍然可用，但已标记为 `@deprecated`，建议迁移到新方法
2. 模板变量解析支持多种格式，默认支持 `{{variable}}`、`{variable}` 和 `{$variable}`
3. UI组件生成的HTML需要配合相应的CSS样式才能正常显示
4. 验证规则可以根据需要扩展，支持自定义验证器

## 总结

通过使用这些优化后的服务类，可以：

- ✅ 统一API响应格式
- ✅ 简化数据绑定逻辑
- ✅ 提高代码可维护性
- ✅ 减少重复代码
- ✅ 提供更好的开发体验

建议逐步迁移现有代码到新的设计模式，以提高代码质量和可维护性。
