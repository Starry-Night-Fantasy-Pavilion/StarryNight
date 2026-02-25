# 前端UI统一设计指南

## 概述

前端UI现在使用和后台相同的设计系统，确保用户体验的一致性。所有UI组件都使用 `nt-*` 前缀的类名，与后台管理界面保持一致。

## 设计系统

### CSS类名前缀

所有前端UI组件使用 `nt-*` 前缀（Notification Templates的缩写，与后台保持一致）：

- `nt-btn` - 按钮
- `nt-btn-primary` - 主要按钮
- `nt-btn-secondary` - 次要按钮
- `nt-alert` - 提示框
- `nt-card` - 卡片
- `nt-table` - 表格
- `nt-input` - 输入框
- `nt-form-item` - 表单项
- `nt-empty-state` - 空状态

### 样式文件

前端需要引入后台的样式文件：

```php
<link rel="stylesheet" href="/static/admin/css/style.css">
<link rel="stylesheet" href="/static/admin/css/notification-templates.css">
```

## 使用 FrontendUIComponent

### 1. 按钮组件

```php
use app\services\FrontendUIComponent;

// 主要按钮
echo FrontendUIComponent::renderButton('新增项目', '/projects/create', [
    'type' => 'primary',
    'icon' => '<svg>...</svg>',
]);

// 次要按钮
echo FrontendUIComponent::renderButton('取消', '', [
    'type' => 'secondary',
    'onclick' => 'closeModal()',
]);
```

### 2. 提示框组件

```php
// 成功提示
echo FrontendUIComponent::renderAlert('操作成功！', 'success', [
    'dismissible' => true,
]);

// 错误提示
echo FrontendUIComponent::renderAlert('操作失败，请重试', 'error');

// 信息提示
echo FrontendUIComponent::renderAlert('这是一条提示信息', 'info');
```

### 3. 表格组件

```php
$headers = ['ID', '标题', '创建时间', '操作'];
$rows = [
    [1, '项目A', '2024-01-01', '<a href="/edit/1">编辑</a>'],
    [2, '项目B', '2024-01-02', '<a href="/edit/2">编辑</a>'],
];

echo FrontendUIComponent::renderTable($headers, $rows, [
    'id' => 'project-table',
    'empty_text' => '暂无项目数据',
]);
```

### 4. 表单字段组件

```php
echo FrontendUIComponent::renderFormField(
    'title',
    '项目标题',
    'text',
    $project['title'] ?? '',
    [
        'required' => true,
        'placeholder' => '请输入项目标题',
        'help' => '项目标题将显示在列表中',
        'error' => $errors['title'] ?? '',
    ]
);
```

### 5. 分页组件

```php
$pagination = [
    'page' => 1,
    'total_pages' => 10,
    'total' => 100,
];

echo FrontendUIComponent::renderPagination($pagination, '/projects');
```

### 6. 空状态组件

```php
echo FrontendUIComponent::renderEmptyState(
    '您还没有创建任何项目，点击下方按钮开始创建。',
    '<svg>...</svg>',
    [
        'title' => '暂无项目数据',
        'action_text' => '创建第一个项目',
        'action_url' => '/projects/create',
    ]
);
```

### 7. 卡片组件

```php
$content = '<p>这是卡片内容</p>';
echo FrontendUIComponent::renderCard($content, [
    'title' => '项目列表',
    'header' => '<button>刷新</button>',
    'footer' => '<button>更多</button>',
]);
```

## 完整示例

### 项目列表页面

```php
<?php
use app\services\FrontendUIComponent;
use app\services\FrontendDataService;

// 准备数据
$data = FrontendDataService::prepareViewData([
    'title' => '我的项目',
    'projects' => $projects,
    'pagination' => $pagination,
]);
?>

<div class="nt-layout">
    <!-- 页面头部 -->
    <div class="nt-header">
        <div class="nt-header-content">
            <div class="nt-header-title-group">
                <div class="nt-header-icon">
                    <svg>...</svg>
                </div>
                <div class="nt-header-text">
                    <h1 class="nt-title"><?= htmlspecialchars($data['title']) ?></h1>
                    <p class="nt-subtitle">管理您的所有项目</p>
                </div>
            </div>
            <div class="nt-header-actions">
                <?= FrontendUIComponent::renderButton('新增项目', '/projects/create', [
                    'type' => 'primary',
                ]) ?>
            </div>
        </div>
    </div>

    <!-- 提示信息 -->
    <?php if (!empty($successMessage)): ?>
        <?= FrontendUIComponent::renderAlert($successMessage, 'success', ['dismissible' => true]) ?>
    <?php endif; ?>

    <!-- 内容区域 -->
    <div class="nt-body">
        <?php if (empty($data['projects'])): ?>
            <?= FrontendUIComponent::renderEmptyState(
                '您还没有创建任何项目，点击下方按钮开始创建。',
                '<svg>...</svg>',
                [
                    'title' => '暂无项目',
                    'action_text' => '创建第一个项目',
                    'action_url' => '/projects/create',
                ]
            ) ?>
        <?php else: ?>
            <?= FrontendUIComponent::renderCard(
                FrontendUIComponent::renderTable(
                    ['ID', '标题', '创建时间', '操作'],
                    array_map(function($project) {
                        return [
                            $project['id'],
                            $project['title'],
                            FrontendDataService::formatDateTime($project['created_at']),
                            '<a href="/projects/' . $project['id'] . '">查看</a>',
                        ];
                    }, $data['projects']),
                    ['id' => 'projects-table']
                ),
                ['title' => '项目列表']
            ) ?>
            
            <!-- 分页 -->
            <?= FrontendUIComponent::renderPagination($data['pagination'], '/projects') ?>
        <?php endif; ?>
    </div>
</div>
```

## 在控制器中使用

```php
use Core\Controller;
use app\services\FrontendDataService;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = $this->projectService->getAll();
        $pagination = [
            'page' => 1,
            'total_pages' => 10,
            'total' => 100,
        ];
        
        $data = FrontendDataService::prepareViewData([
            'title' => '我的项目',
            'projects' => $projects,
            'pagination' => $pagination,
        ]);
        
        $this->view('project/index', $data);
    }
}
```

## 样式引入

在视图文件或布局文件中引入样式：

```php
<!-- 基础样式 -->
<link rel="stylesheet" href="/static/admin/css/style.css?v=<?= time() ?>">

<!-- UI组件样式 -->
<link rel="stylesheet" href="/static/admin/css/notification-templates.css?v=<?= time() ?>">
```

## 优势

1. **统一设计**：前端和后台使用相同的设计系统
2. **一致性**：用户体验保持一致
3. **可维护性**：统一的组件类，易于维护
4. **可扩展性**：易于添加新组件
5. **响应式**：所有组件都支持响应式设计

## 注意事项

1. 确保引入后台的CSS文件
2. 使用 `FrontendUIComponent` 生成所有UI组件
3. 使用 `FrontendDataService` 准备视图数据
4. 所有类名使用 `nt-*` 前缀
5. 保持HTML结构与后台一致

## 迁移指南

### 旧代码

```php
<div class="alert alert-success">
    操作成功
</div>
```

### 新代码

```php
<?= FrontendUIComponent::renderAlert('操作成功', 'success') ?>
```

### 旧代码

```php
<table class="table">
    <thead>...</thead>
    <tbody>...</tbody>
</table>
```

### 新代码

```php
<?= FrontendUIComponent::renderTable($headers, $rows) ?>
```

通过使用统一的设计系统，前端UI现在与后台保持一致，提供更好的用户体验。
