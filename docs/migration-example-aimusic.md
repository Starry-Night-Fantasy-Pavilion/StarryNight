# AiMusicController 迁移示例

## 迁移概述

本文档展示了如何将 `AiMusicController` 从旧的设计模式迁移到新的优化设计模式。

## 主要改动

### 1. 继承基类

**迁移前：**
```php
class AiMusicController
{
    // ...
}
```

**迁移后：**
```php
use Core\Controller;
use Core\Exceptions\ErrorCode;
use app\services\FrontendDataService;

class AiMusicController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        // ...
    }
}
```

### 2. 统一认证检查

**迁移前：**
```php
private function checkAuth()
{
    if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
        $redirectUrl = $_SERVER['REQUEST_URI'] ?? '/ai_music';
        header('Location: /login?redirect=' . urlencode($redirectUrl));
        exit;
    }
    return $_SESSION['user_id'];
}
```

**迁移后：**
```php
private function checkAuth(): int
{
    if (!$this->isLoggedIn()) {
        $redirectUrl = $_SERVER['REQUEST_URI'] ?? '/ai_music';
        $this->redirect('/login?redirect=' . urlencode($redirectUrl));
    }
    return $this->getUserId();
}
```

### 3. 统一API响应

**迁移前：**
```php
private function jsonResponse($data, int $statusCode = 200): void
{
    header('Content-Type: application/json');
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// 使用
$this->jsonResponse([
    'success' => true,
    'data' => $project
]);
```

**迁移后：**
```php
// 直接使用基类方法
$this->sendSuccess($project, '项目创建成功');
```

### 4. 统一错误响应

**迁移前：**
```php
$this->jsonResponse([
    'success' => false,
    'error' => '项目不存在'
], 404);
```

**迁移后：**
```php
$this->sendError(ErrorCode::RESOURCE_NOT_FOUND, '项目不存在');
```

### 5. 统一输入验证

**迁移前：**
```php
private function validateRequired(array $data, array $required): array
{
    $missing = [];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        $this->jsonResponse([
            'success' => false,
            'error' => '缺少必需参数: ' . implode(', ', $missing)
        ], 400);
    }
    
    return $data;
}
```

**迁移后：**
```php
private function validateRequired(array $data, array $required): array
{
    $rules = [];
    foreach ($required as $field) {
        $rules[$field] = [
            'required' => true,
            'type' => 'string',
            'message' => "字段 {$field} 是必填的",
        ];
    }
    
    $validation = FrontendDataService::validateInput($data, $rules);
    
    if (!$validation['valid']) {
        $this->sendError(
            ErrorCode::INVALID_PARAMETER,
            '缺少必需参数: ' . implode(', ', array_keys($validation['errors'])),
            $validation['errors']
        );
    }
    
    return $validation['data'];
}
```

### 6. 统一分页响应

**迁移前：**
```php
$this->jsonResponse([
    'success' => true,
    'data' => $projects,
    'pagination' => [
        'page' => $page,
        'limit' => $limit,
        'total' => $totalCount,
        'pages' => ceil($totalCount / $limit)
    ]
]);
```

**迁移后：**
```php
$this->sendPaginated($projects, $totalCount, $page, $limit, '获取项目列表成功');
```

## 完整方法对比

### createProject 方法

**迁移前：**
```php
public function createProject(): void
{
    $data = json_decode(file_get_contents('php://input'), true);
    $data = $this->validateRequired($data, ['title']);
    
    $data['user_id'] = $this->getCurrentUserId();
    
    if ($this->projectModel->create($data)) {
        $projectId = $this->getDb()->lastInsertId();
        $project = $this->projectModel->getById($projectId);
        
        $this->jsonResponse([
            'success' => true,
            'data' => $project
        ]);
    } else {
        $this->jsonResponse([
            'success' => false,
            'error' => '创建项目失败'
        ], 500);
    }
}
```

**迁移后：**
```php
public function createProject(): void
{
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    $data = $this->validateRequired($data, ['title']);
    
    $data['user_id'] = $this->getCurrentUserId();
    
    if ($this->projectModel->create($data)) {
        $projectId = $this->getDb()->lastInsertId();
        $project = $this->projectModel->getById($projectId);
        
        $this->sendSuccess($project, '项目创建成功');
    } else {
        $this->sendError(ErrorCode::SYSTEM_ERROR, '创建项目失败');
    }
}
```

### getProject 方法

**迁移前：**
```php
public function getProject(int $id): void
{
    $project = $this->projectModel->getById($id);
    
    if (!$project) {
        $this->jsonResponse([
            'success' => false,
            'error' => '项目不存在'
        ], 404);
    }
    
    if ($project['user_id'] !== $this->getCurrentUserId() && !$project['is_public']) {
        $this->jsonResponse([
            'success' => false,
            'error' => '无权访问此项目'
        ], 403);
    }
    
    // ... 获取数据
    
    $this->jsonResponse([
        'success' => true,
        'data' => $data
    ]);
}
```

**迁移后：**
```php
public function getProject(int $id): void
{
    $project = $this->projectModel->getById($id);
    
    if (!$project) {
        $this->sendError(ErrorCode::RESOURCE_NOT_FOUND, '项目不存在');
    }
    
    if ($project['user_id'] !== $this->getCurrentUserId() && !$project['is_public']) {
        $this->sendError(ErrorCode::AUTH_PERMISSION_DENIED, '无权访问此项目');
    }
    
    // ... 获取数据
    
    $this->sendSuccess($data, '获取项目详情成功');
}
```

## 迁移收益

1. **代码更简洁**：减少了大量重复的响应代码
2. **统一格式**：所有API响应使用统一的格式
3. **错误处理**：使用标准错误码，便于前端处理
4. **类型安全**：使用类型声明，提高代码质量
5. **易于维护**：统一的验证和响应方法，便于维护

## 注意事项

1. 所有 `jsonResponse` 调用都已替换为 `sendSuccess` 或 `sendError`
2. HTTP状态码通过错误码自动映射，无需手动指定
3. 输入验证使用 `FrontendDataService::validateInput` 统一处理
4. 分页响应使用 `sendPaginated` 方法，自动格式化分页信息

## 下一步

可以按照相同的方式迁移其他控制器：
- FeedbackController
- NovelCreationController
- MembershipController
- 等等
