<?php

namespace app\admin\controller;

use app\admin\controller\BaseController;
use app\models\KnowledgeBase;
use app\models\KnowledgeItem;
use app\models\KnowledgeCategory;
use app\models\KnowledgePurchase;
use app\models\KnowledgeRating;
use app\models\AIPromptTemplate;
use Exception;

class KnowledgeController extends BaseController
{
    /**
     * 知识库列表页面
     */
    public function index()
    {
        try {
        $page = $_GET['page'] ?? 1;
        $perPage = $_GET['per_page'] ?? 15;
        $searchTerm = $_GET['search'] ?? null;
        $category = $_GET['category'] ?? null;
        $visibility = $_GET['visibility'] ?? null;
        $sortBy = $_GET['sort_by'] ?? 'created_at';
        $sortOrder = $_GET['sort_order'] ?? 'desc';

        $result = KnowledgeBase::getAll(
            (int)$page,
            (int)$perPage,
            $searchTerm,
            $category,
            $visibility,
            $sortBy,
            $sortOrder
        );

        $categories = KnowledgeCategory::getAll();

        include __DIR__ . '/../views/knowledge/index.php';
    
        } catch (Exception $e) {
            // 记录错误日志
            error_log('Controller Error in KnowledgeController::index: ' . $e->getMessage());
            
            // 返回友好的错误信息
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                return json_encode([
                    'success' => false,
                    'message' => '操作失败，请稍后重试',
                    'error' => (defined('DEBUG') && DEBUG) ? $e->getMessage() : '系统内部错误'
                ]);
            } else {
                // 对于普通请求，显示错误页面
                http_response_code(500);
                echo '<h1>系统错误</h1><p>抱歉，系统遇到了一些问题，请稍后重试。</p>';
                if (defined('DEBUG') && DEBUG) {
                    echo '<p>错误详情: ' . htmlspecialchars($e->getMessage()) . '</p>';
                }
                exit;
            }
        }
    }

    /**
     * 知识库详情页面
     */
    public function details($id)
    {
        $knowledgeBase = KnowledgeBase::find((int)$id);
        if (!$knowledgeBase) {
            header('Location: /admin/knowledge?error=not_found');
            exit;
        }

        $page = $_GET['page'] ?? 1;
        $perPage = $_GET['per_page'] ?? 20;
        $itemsResult = KnowledgeItem::getByKnowledgeBase((int)$id, (int)$page, (int)$perPage);
        $stats = KnowledgeItem::getStats((int)$id);
        $ratingsResult = KnowledgeRating::getByKnowledgeBase((int)$id, 1, 10);
        $purchasesResult = KnowledgePurchase::getBySeller($knowledgeBase['user_id'], 1, 10);

        include __DIR__ . '/../views/knowledge/details.php';
    }

    /**
     * 创建知识库页面
     */
    public function create()
    {
        try {
        $categories = KnowledgeCategory::getAll();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'user_id' => $_SESSION['admin_user_id'] ?? 1, // 临时使用管理员ID
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'category' => $_POST['category'] ?? '',
                'tags' => $_POST['tags'] ?? '',
                'visibility' => $_POST['visibility'] ?? 'private',
                'price' => $_POST['price'] ?? 0.00,
                'status' => $_POST['status'] ?? 'draft'
            ];

            $id = KnowledgeBase::create($data);
            if ($id) {
                header('Location: /admin/knowledge/edit/' . $id . '?success=created');
                exit;
            } else {
                $error = '创建知识库失败';
            }
        }

        include __DIR__ . '/../views/knowledge/create.php';
    
        } catch (Exception $e) {
            // 记录错误日志
            error_log('Controller Error in KnowledgeController::create: ' . $e->getMessage());
            
            // 返回友好的错误信息
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                return json_encode([
                    'success' => false,
                    'message' => '操作失败，请稍后重试',
                    'error' => (defined('DEBUG') && DEBUG) ? $e->getMessage() : '系统内部错误'
                ]);
            } else {
                // 对于普通请求，显示错误页面
                http_response_code(500);
                echo '<h1>系统错误</h1><p>抱歉，系统遇到了一些问题，请稍后重试。</p>';
                if (defined('DEBUG') && DEBUG) {
                    echo '<p>错误详情: ' . htmlspecialchars($e->getMessage()) . '</p>';
                }
                exit;
            }
        }
    }

    /**
     * 编辑知识库页面
     */
    public function edit($id)
    {
        $knowledgeBase = KnowledgeBase::find((int)$id);
        if (!$knowledgeBase) {
            header('Location: /admin/knowledge?error=not_found');
            exit;
        }

        $categories = KnowledgeCategory::getAll();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'category' => $_POST['category'] ?? '',
                'tags' => $_POST['tags'] ?? '',
                'visibility' => $_POST['visibility'] ?? 'private',
                'price' => $_POST['price'] ?? 0.00,
                'status' => $_POST['status'] ?? 'draft'
            ];

            if (KnowledgeBase::update((int)$id, $data)) {
                header('Location: /admin/knowledge/edit/' . $id . '?success=updated');
                exit;
            } else {
                $error = '更新知识库失败';
            }
        }

        include __DIR__ . '/../views/knowledge/edit.php';
    }

    /**
     * 删除知识库
     */
    public function delete($id)
    {
        try {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (KnowledgeBase::delete((int)$id)) {
                header('Location: /admin/knowledge?success=deleted');
                exit;
            } else {
                header('Location: /admin/knowledge?error=delete_failed');
                exit;
            }
        }
    
        } catch (Exception $e) {
            // 记录错误日志
            error_log('Controller Error in KnowledgeController::delete: ' . $e->getMessage());
            
            // 返回友好的错误信息
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                return json_encode([
                    'success' => false,
                    'message' => '操作失败，请稍后重试',
                    'error' => (defined('DEBUG') && DEBUG) ? $e->getMessage() : '系统内部错误'
                ]);
            } else {
                // 对于普通请求，显示错误页面
                http_response_code(500);
                echo '<h1>系统错误</h1><p>抱歉，系统遇到了一些问题，请稍后重试。</p>';
                if (defined('DEBUG') && DEBUG) {
                    echo '<p>错误详情: ' . htmlspecialchars($e->getMessage()) . '</p>';
                }
                exit;
            }
        }
    }

    /**
     * 知识条目管理页面
     */
    public function items($knowledgeBaseId)
    {
        $knowledgeBase = KnowledgeBase::find((int)$knowledgeBaseId);
        if (!$knowledgeBase) {
            header('Location: /admin/knowledge?error=not_found');
            exit;
        }

        $page = $_GET['page'] ?? 1;
        $perPage = $_GET['per_page'] ?? 20;
        $itemsResult = KnowledgeItem::getByKnowledgeBase((int)$knowledgeBaseId, (int)$page, (int)$perPage);

        include __DIR__ . '/../views/knowledge/items.php';
    }

    /**
     * 创建知识条目
     */
    public function createItem($knowledgeBaseId)
    {
        $knowledgeBase = KnowledgeBase::find((int)$knowledgeBaseId);
        if (!$knowledgeBase) {
            header('Location: /admin/knowledge?error=not_found');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'knowledge_base_id' => (int)$knowledgeBaseId,
                'title' => $_POST['title'] ?? '',
                'content' => $_POST['content'] ?? '',
                'content_type' => $_POST['content_type'] ?? 'text',
                'tags' => $_POST['tags'] ?? '',
                'order_index' => $_POST['order_index'] ?? 0
            ];

            // 处理文件上传
            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'uploads/knowledge/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $fileName = time() . '_' . basename($_FILES['file']['name']);
                $filePath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
                    $data['file_path'] = $filePath;
                    $data['file_size'] = $_FILES['file']['size'];
                    $data['file_type'] = $_FILES['file']['type'];
                }
            }

            $id = KnowledgeItem::create($data);
            if ($id) {
                header('Location: /admin/knowledge/items/' . $knowledgeBaseId . '?success=item_created');
                exit;
            } else {
                $error = '创建知识条目失败';
            }
        }

        include __DIR__ . '/../views/knowledge/create_item.php';
    }

    /**
     * 编辑知识条目
     */
    public function editItem($id)
    {
        $item = KnowledgeItem::find((int)$id);
        if (!$item) {
            header('Location: /admin/knowledge?error=item_not_found');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'title' => $_POST['title'] ?? '',
                'content' => $_POST['content'] ?? '',
                'content_type' => $_POST['content_type'] ?? 'text',
                'tags' => $_POST['tags'] ?? '',
                'order_index' => $_POST['order_index'] ?? 0
            ];

            // 处理文件上传
            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'uploads/knowledge/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $fileName = time() . '_' . basename($_FILES['file']['name']);
                $filePath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
                    $data['file_path'] = $filePath;
                    $data['file_size'] = $_FILES['file']['size'];
                    $data['file_type'] = $_FILES['file']['type'];
                }
            }

            if (KnowledgeItem::update((int)$id, $data)) {
                header('Location: /admin/knowledge/items/' . $item['knowledge_base_id'] . '?success=item_updated');
                exit;
            } else {
                $error = '更新知识条目失败';
            }
        }

        include __DIR__ . '/../views/knowledge/edit_item.php';
    }

    /**
     * 删除知识条目
     */
    public function deleteItem($id)
    {
        $item = KnowledgeItem::find((int)$id);
        if (!$item) {
            header('Location: /admin/knowledge?error=item_not_found');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (KnowledgeItem::delete((int)$id)) {
                header('Location: /admin/knowledge/items/' . $item['knowledge_base_id'] . '?success=item_deleted');
                exit;
            } else {
                header('Location: /admin/knowledge/items/' . $item['knowledge_base_id'] . '?error=item_delete_failed');
                exit;
            }
        }
    }

    /**
     * 分类管理页面
     */
    public function categories()
    {
        $categories = KnowledgeCategory::getTree();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => $_POST['name'] ?? '',
                'description' => $_POST['description'] ?? '',
                'icon' => $_POST['icon'] ?? '',
                'parent_id' => $_POST['parent_id'] ?? 0,
                'sort_order' => $_POST['sort_order'] ?? 0,
                'is_active' => $_POST['is_active'] ?? 1
            ];

            $id = KnowledgeCategory::create($data);
            if ($id) {
                header('Location: /admin/knowledge/categories?success=category_created');
                exit;
            } else {
                $error = '创建分类失败';
            }
        }

        include __DIR__ . '/../views/knowledge/categories.php';
    }

    /**
     * AI提示词模板管理页面
     */
    public function templates()
    {
        $page = $_GET['page'] ?? 1;
        $perPage = $_GET['per_page'] ?? 15;
        $category = $_GET['category'] ?? null;
        $isSystem = isset($_GET['is_system']) ? ($_GET['is_system'] === '1') : null;
        $isActive = isset($_GET['is_active']) ? ($_GET['is_active'] === '1') : null;
        $searchTerm = $_GET['search'] ?? null;

        $result = AIPromptTemplate::getAll(
            (int)$page,
            (int)$perPage,
            $category,
            $isSystem,
            $isActive,
            $searchTerm
        );

        $categories = AIPromptTemplate::getCategories();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => $_POST['name'] ?? '',
                'category' => $_POST['category'] ?? '',
                'description' => $_POST['description'] ?? '',
                'template_content' => $_POST['template_content'] ?? '',
                'variables' => $_POST['variables'] ?? '',
                'is_system' => $_POST['is_system'] ?? 0,
                'is_active' => $_POST['is_active'] ?? 1,
                'created_by' => $_SESSION['admin_user_id'] ?? 1
            ];

            // 解析变量
            if (!empty($data['variables'])) {
                $variableLines = explode("\n", trim($data['variables']));
                $variables = [];
                foreach ($variableLines as $line) {
                    if (strpos($line, ':') !== false) {
                        list($key, $desc) = explode(':', $line, 2);
                        $variables[trim($key)] = trim($desc);
                    }
                }
                $data['variables'] = $variables;
            }

            $id = AIPromptTemplate::create($data);
            if ($id) {
                header('Location: /admin/knowledge/templates?success=template_created');
                exit;
            } else {
                $error = '创建模板失败';
            }
        }

        include __DIR__ . '/../views/knowledge/templates.php';
    }

    /**
     * 统计页面
     */
    public function statistics()
    {
        // 获取各种统计数据
        $totalKnowledgeBases = KnowledgeBase::getAll(1, 1)['total'] ?? 0;
        $totalItems = KnowledgeItem::getByKnowledgeBase(1, 1, 1)['total'] ?? 0; // 临时统计
        $totalPurchases = KnowledgePurchase::getBySeller(1, 1, 1)['total'] ?? 0; // 临时统计
        $totalRatings = KnowledgeRating::getByKnowledgeBase(1, 1, 1)['total'] ?? 0; // 临时统计

        include __DIR__ . '/../views/knowledge/statistics.php';
    }
}