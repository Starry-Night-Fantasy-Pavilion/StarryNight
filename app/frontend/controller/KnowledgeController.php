<?php

namespace app\frontend\controller;

use app\services\Database;
use app\services\ThemeManager;
use app\models\KnowledgeBase;
use app\models\KnowledgeItem;
use app\models\KnowledgeCategory;
use app\models\KnowledgePurchase;
use app\models\KnowledgeRating;
use app\models\KnowledgeUsageLog;
use app\models\AIPromptTemplate;
use app\models\User;

class KnowledgeController
{
    /**
     * 渲染主题视图（layout + content）
     */
    private function render(string $template, array $viewVars = []): void
    {
        header('Content-Type: text/html; charset=utf-8');

        $siteName = (string) get_env('APP_NAME', '星夜阁');
        $themeManager = new ThemeManager();
        $theme = $themeManager->loadActiveThemeInstance();

        if (!$theme) {
            // 没有主题时，直接输出简单内容
            echo $viewVars['content'] ?? ('模板渲染失败: ' . htmlspecialchars($template));
            return;
        }

        $navItems = [
            ['title' => '首页', 'url' => '/', 'active' => false],
            ['title' => '知识库', 'url' => '/knowledge', 'active' => str_starts_with((string)($_SERVER['REQUEST_URI'] ?? ''), '/knowledge')],
        ];
        if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
            $navItems[] = ['title' => '登录', 'url' => '/login', 'active' => false];
            $navItems[] = ['title' => '注册', 'url' => '/register', 'active' => false];
        } else {
            $navItems[] = ['title' => '用户中心', 'url' => '/user_center', 'active' => false];
        }

        $content = $theme->renderTemplate($template, $viewVars);
        echo $theme->renderTemplate('layout', [
            'title' => (string)($viewVars['title'] ?? $siteName),
            'site_name' => $siteName,
            'page_class' => (string)($viewVars['page_class'] ?? ''),
            'nav_items' => $navItems,
            'content' => $content,
        ]);
    }

    /**
     * 检查用户登录状态
     */
    private function checkAuth()
    {
        if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
            header('Location: /login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
        return $_SESSION['user_id'];
    }

    /**
     * 知识库首页
     */
    public function index()
    {
        try {
        $page = $_GET['page'] ?? 1;
        $perPage = $_GET['per_page'] ?? 12;
        $searchTerm = $_GET['search'] ?? null;
        $category = $_GET['category'] ?? null;
        $sortBy = $_GET['sort_by'] ?? 'created_at';
        $sortOrder = $_GET['sort_order'] ?? 'desc';

        // 只显示公开和已购买的知识库
        $userId = null;
        if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in']) {
            $userId = $_SESSION['user_id'];
        }
        $result = KnowledgeBase::getAll(
            (int)$page,
            (int)$perPage,
            $searchTerm,
            $category,
            null, // 不限制可见性，在视图中处理
            $sortBy,
            $sortOrder
        );

        $categories = KnowledgeCategory::getAll();

        // 获取热门知识库
        $popularKBs = KnowledgeUsageLog::getPopularKnowledgeBases(5);

        $viewVars = [
            'knowledgeBases' => $result['knowledge_bases'],
            'pagination' => [
                'total' => $result['total'],
                'page' => $result['page'],
                'perPage' => $result['perPage'],
                'totalPages' => $result['totalPages']
            ],
            'categories' => $categories,
            'popularKBs' => $popularKBs,
            'searchTerm' => $searchTerm,
            'selectedCategory' => $category,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
            'title' => '知识库 - 星夜阁'
        ];
        $this->render('knowledge/index', $viewVars);
    
        } catch (Exception $e) {
            // 记录错误日志
            error_log('Controller Error in KnowledgeController::index: ' . $e->getMessage());
            
            // 返回友好的错误信息
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                return json_encode([
                    'success' => false,
                    'message' => '操作失败，请稍后重试',
                    'error' => DEBUG ? $e->getMessage() : '系统内部错误'
                ]);
            } else {
                // 对于普通请求，显示错误页面
                http_response_code(500);
                echo '<h1>系统错误</h1><p>抱歉，系统遇到了一些问题，请稍后重试。</p>';
                if (DEBUG) {
                    echo '<p>错误详情: ' . htmlspecialchars($e->getMessage()) . '</p>';
                }
                exit;
            }
        }

    /**
     * 知识库详情页面
     */
    public function view($id)
    {
        $knowledgeBase = KnowledgeBase::find((int)$id);
        if (!$knowledgeBase) {
            $this->render('errors/404', ['title' => '知识库不存在']);
            return;
        }

        // 检查访问权限（允许未登录用户查看公开知识库）
        $userId = null;
        if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in']) {
            $userId = $_SESSION['user_id'];
        }
        
        // 检查访问权限
        if (!KnowledgeBase::hasAccess($userId, (int)$id)) {
            if (!$userId) {
                header('Location: /login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
                exit;
            } else {
                // 需要购买
                header('Location: /knowledge/purchase/' . $id);
                exit;
            }
        }

        // 增加浏览次数
        KnowledgeBase::incrementViewCount((int)$id);

        $page = $_GET['page'] ?? 1;
        $perPage = $_GET['per_page'] ?? 10;
        $itemsResult = KnowledgeItem::getByKnowledgeBase((int)$id, (int)$page, (int)$perPage);
        $stats = KnowledgeItem::getStats((int)$id);
        $ratingsResult = KnowledgeRating::getByKnowledgeBase((int)$id, 1, 5);
        
        // 获取用户评分
        $userRating = $userId ? KnowledgeRating::getUserRating($userId, (int)$id) : null;
        
        // 检查是否已购买
        $hasPurchased = $userId ? KnowledgePurchase::hasPurchased($userId, (int)$id) : false;

        $viewVars = [
            'knowledgeBase' => $knowledgeBase,
            'items' => $itemsResult['items'],
            'pagination' => [
                'total' => $itemsResult['total'],
                'page' => $itemsResult['page'],
                'perPage' => $itemsResult['perPage'],
                'totalPages' => $itemsResult['totalPages']
            ],
            'stats' => $stats,
            'ratings' => $ratingsResult['ratings'],
            'userRating' => $userRating,
            'hasPurchased' => $hasPurchased,
            'title' => $knowledgeBase['title'] . ' - 知识库'
        ];
        $this->render('knowledge/view', $viewVars);
    }

    /**
     * 用户的知识库管理页面
     */
    public function myKnowledge()
    {
        $userId = $this->checkAuth();
        
        $page = $_GET['page'] ?? 1;
        $perPage = $_GET['per_page'] ?? 10;
        $result = KnowledgeBase::getByUser($userId, (int)$page, (int)$perPage);

        $viewVars = [
            'knowledgeBases' => $result['knowledge_bases'],
            'pagination' => [
                'total' => $result['total'],
                'page' => $result['page'],
                'perPage' => $result['perPage'],
                'totalPages' => $result['totalPages']
            ],
            'title' => '我的知识库 - 星夜阁'
        ];
        $this->render('knowledge/my', $viewVars);
    }

    /**
     * 创建知识库页面
     */
    public function create()
    {
        try {
        $userId = $this->checkAuth();
        $categories = KnowledgeCategory::getAll();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'user_id' => $userId,
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'category' => $_POST['category'] ?? '',
                'tags' => $_POST['tags'] ?? '',
                'visibility' => $_POST['visibility'] ?? 'private',
                'price' => $_POST['price'] ?? 0.00,
                'status' => 'published' // 用户创建直接发布
            ];

            $id = KnowledgeBase::create($data);
            if ($id) {
                header('Location: /knowledge/manage/' . $id . '?success=created');
                exit;
            } else {
                $error = '创建知识库失败';
            }
        }

        $viewVars = [
            'categories' => $categories,
            'error' => $error ?? null,
            'title' => '创建知识库 - 星夜阁'
        ];
        $this->render('knowledge/create', $viewVars);
    
        } catch (Exception $e) {
            // 记录错误日志
            error_log('Controller Error in KnowledgeController::create: ' . $e->getMessage());
            
            // 返回友好的错误信息
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                return json_encode([
                    'success' => false,
                    'message' => '操作失败，请稍后重试',
                    'error' => DEBUG ? $e->getMessage() : '系统内部错误'
                ]);
            } else {
                // 对于普通请求，显示错误页面
                http_response_code(500);
                echo '<h1>系统错误</h1><p>抱歉，系统遇到了一些问题，请稍后重试。</p>';
                if (DEBUG) {
                    echo '<p>错误详情: ' . htmlspecialchars($e->getMessage()) . '</p>';
                }
                exit;
            }
        }

    /**
     * 管理知识库页面
     */
    public function manage($id)
    {
        $userId = $this->checkAuth();
        
        $knowledgeBase = KnowledgeBase::find((int)$id);
        if (!$knowledgeBase || $knowledgeBase['user_id'] != $userId) {
            header('Location: /knowledge/my?error=access_denied');
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
                header('Location: /knowledge/manage/' . $id . '?success=updated');
                exit;
            } else {
                $error = '更新知识库失败';
            }
        }

        $viewVars = [
            'knowledgeBase' => $knowledgeBase,
            'categories' => $categories,
            'error' => $error ?? null,
            'success' => $_GET['success'] ?? null,
            'title' => '管理知识库 - ' . $knowledgeBase['title']
        ];
        $this->render('knowledge/manage', $viewVars);
    }

    /**
     * 管理知识条目页面
     */
    public function manageItems($knowledgeBaseId)
    {
        $userId = $this->checkAuth();
        
        $knowledgeBase = KnowledgeBase::find((int)$knowledgeBaseId);
        if (!$knowledgeBase || $knowledgeBase['user_id'] != $userId) {
            header('Location: /knowledge/my?error=access_denied');
            exit;
        }

        $page = $_GET['page'] ?? 1;
        $perPage = $_GET['per_page'] ?? 20;
        $itemsResult = KnowledgeItem::getByKnowledgeBase((int)$knowledgeBaseId, (int)$page, (int)$perPage);

        $viewVars = [
            'knowledgeBase' => $knowledgeBase,
            'items' => $itemsResult['items'],
            'pagination' => [
                'total' => $itemsResult['total'],
                'page' => $itemsResult['page'],
                'perPage' => $itemsResult['perPage'],
                'totalPages' => $itemsResult['totalPages']
            ],
            'title' => '管理条目 - ' . $knowledgeBase['title']
        ];
        $this->render('knowledge/items', $viewVars);
    }

    /**
     * 购买知识库页面
     */
    public function purchase($id)
    {
        $userId = $this->checkAuth();
        
        $knowledgeBase = KnowledgeBase::find((int)$id);
        if (!$knowledgeBase || $knowledgeBase['visibility'] !== 'premium') {
            header('Location: /knowledge/view/' . $id);
            exit;
        }

        // 检查是否已购买
        if (KnowledgePurchase::hasPurchased($userId, (int)$id)) {
            header('Location: /knowledge/view/' . $id);
            exit;
        }

        // 获取用户余额
        $user = User::find($userId);
        $userBalance = $user['coin_balance'] ?? 0;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($userBalance < $knowledgeBase['price']) {
                $error = '余额不足，请先充值';
            } else {
                $data = [
                    'user_id' => $userId,
                    'knowledge_base_id' => (int)$id,
                    'seller_id' => $knowledgeBase['user_id'],
                    'price' => $knowledgeBase['price'],
                    'coins_spent' => (int)$knowledgeBase['price']
                ];

                $purchaseId = KnowledgePurchase::create($data);
                if ($purchaseId) {
                    header('Location: /knowledge/view/' . $id . '?success=purchased');
                    exit;
                } else {
                    $error = '购买失败，请重试';
                }
            }
        }

        $viewVars = [
            'knowledgeBase' => $knowledgeBase,
            'userBalance' => $userBalance,
            'error' => $error ?? null,
            'title' => '购买知识库 - ' . $knowledgeBase['title']
        ];
        $this->render('knowledge/purchase', $viewVars);
    }

    /**
     * 搜索知识库
     */
    public function search()
    {
        $query = $_GET['q'] ?? '';
        $page = $_GET['page'] ?? 1;
        $perPage = $_GET['per_page'] ?? 10;
        $userId = $_SESSION['user_id'] ?? null;

        if (empty($query)) {
            header('Location: /knowledge');
            exit;
        }

        // 搜索知识库
        $result = KnowledgeBase::getAll(
            (int)$page,
            (int)$perPage,
            $query
        );

        // 搜索知识条目
        $items = KnowledgeItem::globalSearch($userId ?? 0, $query, [], 20);

        $viewVars = [
            'query' => $query,
            'knowledgeBases' => $result['knowledge_bases'],
            'items' => $items,
            'pagination' => [
                'total' => $result['total'],
                'page' => $result['page'],
                'perPage' => $result['perPage'],
                'totalPages' => $result['totalPages']
            ],
            'title' => '搜索结果: ' . $query . ' - 知识库'
        ];
        $this->render('knowledge/search', $viewVars);
    }

    /**
     * AI辅助创作页面
     */
    public function aiAssist()
    {
        $userId = $this->checkAuth();
        
        // 获取用户有权限访问的知识库
        $userKBs = KnowledgeBase::getByUser($userId, 1, 100)['knowledge_bases'];
        $publicKBs = KnowledgeBase::getAll(1, 100, null, null, 'public')['knowledge_bases'];
        $purchasedKBs = [];
        
        // 获取已购买的知识库
        $purchases = KnowledgePurchase::getByUser($userId, 1, 100)['purchases'];
        foreach ($purchases as $purchase) {
            $kb = KnowledgeBase::find($purchase['knowledge_base_id']);
            if ($kb) {
                $purchasedKBs[] = $kb;
            }
        }
        
        $allKBs = array_merge($userKBs, $publicKBs, $purchasedKBs);
        
        // 获取AI提示词模板
        $templates = AIPromptTemplate::getAll(1, 50, null, null, true)['templates'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $knowledgeBaseId = $_POST['knowledge_base_id'] ?? '';
            $templateId = $_POST['template_id'] ?? '';
            $currentText = $_POST['current_text'] ?? '';
            $requirement = $_POST['requirement'] ?? '';

            // 搜索相关知识
            $knowledgeItems = [];
            if ($knowledgeBaseId) {
                $items = KnowledgeItem::getByKnowledgeBase((int)$knowledgeBaseId, 1, 50)['items'];
                $knowledgeItems = array_slice($items, 0, 10); // 限制数量
            }

            // 渲染提示词
            $prompt = AIPromptTemplate::render((int)$templateId, [
                'knowledge_items' => implode("\n", array_map(function($item) {
                    return "- {$item['title']}: " . substr($item['content'], 0, 200) . "...";
                }, $knowledgeItems)),
                'current_text' => $currentText,
                'requirement' => $requirement
            ]);

            // 记录使用日志
            KnowledgeUsageLog::create([
                'user_id' => $userId,
                'knowledge_base_id' => (int)$knowledgeBaseId,
                'knowledge_item_ids' => implode(',', array_column($knowledgeItems, 'id')),
                'usage_type' => 'reference',
                'context' => $currentText,
                'ai_response' => $prompt
            ]);

            $aiResponse = $prompt; // 这里应该调用AI API，暂时使用提示词作为响应
            
            $viewVars = [
                'knowledgeBases' => $allKBs,
                'templates' => $templates,
                'aiResponse' => $aiResponse,
                'formData' => $_POST,
                'title' => 'AI辅助创作 - 星夜阁'
            ];
            $this->render('knowledge/ai_assist', $viewVars);
            return;
        }
        
        $viewVars = [
            'knowledgeBases' => $allKBs,
            'templates' => $templates,
            'title' => 'AI辅助创作 - 星夜阁'
        ];
        $this->render('knowledge/ai_assist', $viewVars);
    }

    /**
     * 我的购买记录
     */
    public function myPurchases()
    {
        $userId = $this->checkAuth();
        
        $page = $_GET['page'] ?? 1;
        $perPage = $_GET['per_page'] ?? 10;
        $result = KnowledgePurchase::getByUser($userId, (int)$page, (int)$perPage);

        $viewVars = [
            'purchases' => $result['purchases'],
            'pagination' => [
                'total' => $result['total'],
                'page' => $result['page'],
                'perPage' => $result['perPage'],
                'totalPages' => $result['totalPages']
            ],
            'title' => '我的购买 - 星夜阁'
        ];
        $this->render('knowledge/purchases', $viewVars);
    }

    /**
     * 评分知识库
     */
    public function rate($id)
    {
        $userId = $this->checkAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /knowledge/view/' . $id);
            exit;
        }

        $rating = $_POST['rating'] ?? '';
        $review = $_POST['review'] ?? '';

        if (empty($rating) || $rating < 1 || $rating > 5) {
            header('Location: /knowledge/view/' . $id . '?error=invalid_rating');
            exit;
        }

        // 检查是否已评分
        $existingRating = KnowledgeRating::getUserRating($userId, (int)$id);
        if ($existingRating) {
            // 更新评分
            KnowledgeRating::update($existingRating['id'], [
                'rating' => (int)$rating,
                'review' => $review
            ]);
        } else {
            // 创建评分
            KnowledgeRating::create([
                'knowledge_base_id' => (int)$id,
                'user_id' => $userId,
                'rating' => (int)$rating,
                'review' => $review
            ]);
        }

        header('Location: /knowledge/view/' . $id . '?success=rated');
        exit;
    }
}