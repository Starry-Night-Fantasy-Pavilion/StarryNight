<?php

namespace app\frontend\controller;

use app\services\ThemeManager;
use app\config\FrontendConfig;
use app\models\ResourceShare;
use app\models\ResourcePurchase;
use app\models\ResourceRating;
use app\models\ResourceFavorite;
use app\models\KnowledgeBase;
use app\models\AIPromptTemplate;
use app\models\CreationTemplate;
use app\models\AIAgent;
use app\models\UserTokenBalance;
use app\models\Setting;

class ShareController
{
    private function render(string $template, array $viewVars = []): void
    {
        header('Content-Type: text/html; charset=utf-8');

        $siteName = (string) Setting::get('site_name') ?: (string) get_env('APP_NAME', '星夜阁');
        $viewPath = dirname(__DIR__) . '/views';

        // 为分享相关页面注入专用 CSS / JS（主题包 CSS + 静态 JS）
        $extraCss = $viewVars['extra_css'] ?? [];
        $extraJs = $viewVars['extra_js'] ?? [];
        $extraCss[] = FrontendConfig::getThemeCssUrl('pages/share.css');
        $extraJs[] = FrontendConfig::getAssetUrl(FrontendConfig::PATH_STATIC_FRONTEND_WEB_JS . '/modules/share.js');
        $viewVars['extra_css'] = $extraCss;
        $viewVars['extra_js'] = $extraJs;
        
        // 尝试从主题包查找模板
        $themeManager = new ThemeManager();
        $theme = $themeManager->loadActiveThemeInstance();
        $content = null;
        
        if ($theme) {
            try {
                $content = $theme->renderTemplate($template, $viewVars);
            } catch (\Exception $e) {
                // 主题包中找不到，继续尝试从 app/frontend/views 查找
            }
        }
        
        // 如果主题包中找不到，从 app/frontend/views 查找
        if ($content === null) {
            $viewFile = $viewPath . '/' . $template . '.php';
            if (file_exists($viewFile)) {
                extract($viewVars);
                ob_start();
                include $viewFile;
                $content = ob_get_clean();
            } else {
                echo $viewVars['content'] ?? ('模板渲染失败: ' . htmlspecialchars($template));
                return;
            }
        }

        // 使用 layout.php
        $title = (string)($viewVars['title'] ?? $siteName);
        $page_class = (string)($viewVars['page_class'] ?? '');
        extract($viewVars);
        include $viewPath . '/layout.php';
    }

    private function checkAuth()
    {
        if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
            header('Location: /login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
        return $_SESSION['user_id'];
    }

    /**
     * 资源分享平台首页
     */
    public function index()
    {
        $resourceType = $_GET['type'] ?? 'all';
        $page = $_GET['page'] ?? 1;
        $perPage = $_GET['per_page'] ?? 12;
        $searchTerm = $_GET['search'] ?? null;
        $sortBy = $_GET['sort_by'] ?? 'created_at';
        $sortOrder = $_GET['sort_order'] ?? 'desc';

        $result = ResourceShare::getAll(
            (int)$page,
            (int)$perPage,
            $searchTerm,
            $resourceType !== 'all' ? $resourceType : null,
            true, // 只显示公开资源
            $sortBy,
            $sortOrder
        );

        $resourceTypes = [
            'all' => '全部资源',
            'knowledge' => '知识库',
            'prompt' => '提示词',
            'template' => '模板',
            'agent' => '智能体'
        ];

        $this->render('share/index', [
            'title' => '资源分享平台 - 星夜阁',
            'resources' => $result['shares'] ?? [],
            'pagination' => [
                'total' => $result['total'] ?? 0,
                'page' => $result['page'] ?? 1,
                'perPage' => $result['perPage'] ?? 12,
                'totalPages' => $result['totalPages'] ?? 1,
            ],
            'resourceTypes' => $resourceTypes,
            'currentType' => $resourceType,
            'searchTerm' => $searchTerm,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
        ]);
    }

    /**
     * 知识库分享
     */
    public function knowledge()
    {
        $page = $_GET['page'] ?? 1;
        $perPage = $_GET['per_page'] ?? 12;
        $searchTerm = $_GET['search'] ?? null;

        $result = KnowledgeBase::getAll(
            (int)$page,
            (int)$perPage,
            $searchTerm,
            null,
            true, // 只显示公开知识库
            'created_at',
            'desc'
        );

        $this->render('share/knowledge', [
            'title' => '知识库分享 - 资源分享平台',
            'knowledgeBases' => $result['knowledge_bases'] ?? [],
            'pagination' => [
                'total' => $result['total'] ?? 0,
                'page' => $result['page'] ?? 1,
                'perPage' => $result['perPage'] ?? 12,
                'totalPages' => $result['totalPages'] ?? 1,
            ],
            'searchTerm' => $searchTerm,
        ]);
    }

    /**
     * 提示词分享
     */
    public function prompts()
    {
        $page = $_GET['page'] ?? 1;
        $perPage = $_GET['per_page'] ?? 12;
        $searchTerm = $_GET['search'] ?? null;
        $category = $_GET['category'] ?? null;

        $result = AIPromptTemplate::getAll(
            (int)$page,
            (int)$perPage,
            $searchTerm,
            $category,
            true, // 只显示公开提示词
            'created_at',
            'desc'
        );

        $this->render('share/prompts', [
            'title' => '提示词分享 - 资源分享平台',
            'prompts' => $result['templates'] ?? [],
            'pagination' => [
                'total' => $result['total'] ?? 0,
                'page' => $result['page'] ?? 1,
                'perPage' => $result['perPage'] ?? 12,
                'totalPages' => $result['totalPages'] ?? 1,
            ],
            'searchTerm' => $searchTerm,
            'currentCategory' => $category,
        ]);
    }

    /**
     * 模板分享
     */
    public function templates()
    {
        $page = $_GET['page'] ?? 1;
        $perPage = $_GET['per_page'] ?? 12;
        $searchTerm = $_GET['search'] ?? null;
        $category = $_GET['category'] ?? null;

        $result = CreationTemplate::getAll(
            (int)$page,
            (int)$perPage,
            $searchTerm,
            $category,
            null,
            true, // 只显示公开模板
            'created_at',
            'desc'
        );

        $this->render('share/templates', [
            'title' => '模板分享 - 资源分享平台',
            'templates' => $result['templates'] ?? [],
            'pagination' => [
                'total' => $result['total'] ?? 0,
                'page' => $result['page'] ?? 1,
                'perPage' => $result['perPage'] ?? 12,
                'totalPages' => $result['totalPages'] ?? 1,
            ],
            'searchTerm' => $searchTerm,
            'currentCategory' => $category,
        ]);
    }

    /**
     * 智能体分享
     */
    public function agents()
    {
        $page = $_GET['page'] ?? 1;
        $perPage = $_GET['per_page'] ?? 12;
        $searchTerm = $_GET['search'] ?? null;
        $category = $_GET['category'] ?? null;

        $result = AIAgent::getAll(
            (int)$page,
            (int)$perPage,
            $searchTerm,
            $category,
            null,
            true, // 只显示公开智能体
            'created_at',
            'desc'
        );

        $this->render('share/agents', [
            'title' => '智能体分享 - 资源分享平台',
            'agents' => $result['agents'] ?? [],
            'pagination' => [
                'total' => $result['total'] ?? 0,
                'page' => $result['page'] ?? 1,
                'perPage' => $result['perPage'] ?? 12,
                'totalPages' => $result['totalPages'] ?? 1,
            ],
            'searchTerm' => $searchTerm,
            'currentCategory' => $category,
        ]);
    }

    /**
     * 资源详情页
     */
    public function show()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /share');
            exit;
        }

        $resource = ResourceShare::find((int)$id);
        if (!$resource) {
            header('Location: /share');
            exit;
        }

        // 根据资源类型获取详细信息
        $detail = null;
        switch ($resource['resource_type']) {
            case 'knowledge':
                $detail = KnowledgeBase::find($resource['resource_id']);
                break;
            case 'prompt':
                $detail = AIPromptTemplate::find($resource['resource_id']);
                break;
            case 'template':
                $detail = CreationTemplate::find($resource['resource_id']);
                break;
            case 'agent':
                $detail = AIAgent::find($resource['resource_id']);
                break;
        }

        // 增加查看次数
        ResourceShare::incrementViewCount((int)$id);

        // 获取用户相关信息（可选，未登录也能查看）
        $userId = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] ? $_SESSION['user_id'] : null;
        $userPurchase = null;
        $userRating = null;
        $userFavorite = null;
        $ratingStats = null;

        if ($userId) {
            $userPurchase = ResourcePurchase::getUserPurchase($userId, $resource['resource_type'], $resource['resource_id']);
            $userRating = ResourceRating::getUserRating($userId, $resource['resource_type'], $resource['resource_id']);
            $userFavorite = ResourceFavorite::getUserFavorite($userId, $resource['resource_type'], $resource['resource_id']);
        }

        // 获取评分统计
        $ratingStats = ResourceRating::getResourceRatingStats($resource['resource_type'], $resource['resource_id']);

        // 获取评价列表
        $ratingsResult = ResourceRating::getByResource($resource['resource_type'], $resource['resource_id'], 1, 10);

        $this->render('share/show', [
            'title' => $resource['title'] . ' - 资源分享',
            'resource' => $resource,
            'detail' => $detail,
            'userPurchase' => $userPurchase,
            'userRating' => $userRating,
            'userFavorite' => $userFavorite,
            'ratingStats' => $ratingStats,
            'ratings' => $ratingsResult['ratings'] ?? [],
        ]);
    }

    /**
     * 购买资源
     */
    public function purchase()
    {
        $userId = $this->checkAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => '请求方法错误']);
            return;
        }

        $shareId = intval($_POST['share_id'] ?? 0);
        if (!$shareId) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => '参数错误']);
            return;
        }

        $resource = ResourceShare::find($shareId);
        if (!$resource || !$resource['is_public'] || $resource['status'] != 1) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => '资源不存在或不可购买']);
            return;
        }

        // 检查是否已购买
        $existingPurchase = ResourcePurchase::getUserPurchase($userId, $resource['resource_type'], $resource['resource_id']);
        if ($existingPurchase) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => '您已购买过此资源']);
            return;
        }

        // 检查是否是自己分享的资源
        if ($resource['user_id'] == $userId) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => '不能购买自己分享的资源']);
            return;
        }

        // 检查价格
        $price = intval($resource['price'] ?? 0);
        if ($price <= 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => '此资源为免费资源，无需购买']);
            return;
        }

        // 检查余额
        if (!UserTokenBalance::hasEnoughTokens($userId, $price)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => '星夜币余额不足']);
            return;
        }

        $pdo = \app\services\Database::pdo();
        $prefix = \app\services\Database::prefix();

        try {
            $pdo->beginTransaction();

            // 扣除买家星夜币
            if (!UserTokenBalance::consumeTokens($userId, $price, 'resource_purchase', "购买资源: {$resource['title']}", $shareId, 'resource_share')) {
                $pdo->rollBack();
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => '扣除星夜币失败']);
                return;
            }

            // 增加卖家星夜币
            $sellerBalance = UserTokenBalance::getByUserId($resource['user_id']);
            if ($sellerBalance) {
                UserTokenBalance::addTokens($resource['user_id'], $price, 'resource_sale', "出售资源: {$resource['title']}", $shareId, 'resource_share');
            }

            // 创建购买记录
            $purchaseId = ResourcePurchase::create([
                'resource_type' => $resource['resource_type'],
                'resource_id' => $resource['resource_id'],
                'share_id' => $shareId,
                'buyer_id' => $userId,
                'seller_id' => $resource['user_id'],
                'price' => $price,
                'coins_spent' => $price,
                'purchase_time' => date('Y-m-d H:i:s')
            ]);

            if (!$purchaseId) {
                $pdo->rollBack();
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => '创建购买记录失败']);
                return;
            }

            $pdo->commit();

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => '购买成功',
                'purchase_id' => $purchaseId
            ]);
        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log('Purchase error: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => '购买失败：' . $e->getMessage()]);
        }
    }

    /**
     * 评价资源
     */
    public function rate()
    {
        $userId = $this->checkAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => '请求方法错误']);
            return;
        }

        $shareId = intval($_POST['share_id'] ?? 0);
        $rating = intval($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');

        if (!$shareId || $rating < 1 || $rating > 5) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => '参数错误']);
            return;
        }

        $resource = ResourceShare::find($shareId);
        if (!$resource || !$resource['is_public'] || $resource['status'] != 1) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => '资源不存在']);
            return;
        }

        // 检查是否已评价
        $existingRating = ResourceRating::getUserRating($userId, $resource['resource_type'], $resource['resource_id']);
        
        if ($existingRating) {
            // 更新评价
            $result = ResourceRating::update($existingRating['id'], [
                'rating' => $rating,
                'comment' => $comment
            ]);
        } else {
            // 创建新评价
            $result = ResourceRating::create([
                'resource_type' => $resource['resource_type'],
                'resource_id' => $resource['resource_id'],
                'share_id' => $shareId,
                'user_id' => $userId,
                'rating' => $rating,
                'comment' => $comment
            ]);
        }

        if ($result) {
            // 更新资源评分统计
            ResourceShare::updateRating($shareId);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => '评价成功']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => '评价失败']);
        }
    }

    /**
     * 收藏/取消收藏资源
     */
    public function favorite()
    {
        $userId = $this->checkAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => '请求方法错误']);
            return;
        }

        $shareId = intval($_POST['share_id'] ?? 0);
        $action = $_POST['action'] ?? 'toggle'; // toggle, add, remove

        if (!$shareId) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => '参数错误']);
            return;
        }

        $resource = ResourceShare::find($shareId);
        if (!$resource || !$resource['is_public'] || $resource['status'] != 1) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => '资源不存在']);
            return;
        }

        $existingFavorite = ResourceFavorite::getUserFavorite($userId, $resource['resource_type'], $resource['resource_id']);

        if ($action === 'remove' || ($action === 'toggle' && $existingFavorite)) {
            // 取消收藏
            if ($existingFavorite) {
                $result = ResourceFavorite::deleteByUserAndResource($userId, $resource['resource_type'], $resource['resource_id']);
                if ($result) {
                    ResourceShare::updateFavoriteCount($shareId);
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => '已取消收藏', 'is_favorited' => false]);
                } else {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => '取消收藏失败']);
                }
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => '未收藏此资源']);
            }
        } else {
            // 添加收藏
            if ($existingFavorite) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => '已收藏此资源']);
                return;
            }

            $result = ResourceFavorite::create([
                'resource_type' => $resource['resource_type'],
                'resource_id' => $resource['resource_id'],
                'share_id' => $shareId,
                'user_id' => $userId
            ]);

            if ($result) {
                ResourceShare::updateFavoriteCount($shareId);
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => '收藏成功', 'is_favorited' => true]);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => '收藏失败']);
            }
        }
    }

    /**
     * 导入资源到私有库
     */
    public function import()
    {
        $userId = $this->checkAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => '请求方法错误']);
            return;
        }

        $shareId = intval($_POST['share_id'] ?? 0);
        if (!$shareId) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => '参数错误']);
            return;
        }

        $resource = ResourceShare::find($shareId);
        if (!$resource || !$resource['is_public'] || $resource['status'] != 1) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => '资源不存在']);
            return;
        }

        // 检查是否已购买（如果是付费资源）
        if ($resource['price'] > 0) {
            $purchase = ResourcePurchase::getUserPurchase($userId, $resource['resource_type'], $resource['resource_id']);
            if (!$purchase) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => '请先购买此资源']);
                return;
            }
        }

        try {
            $detail = null;
            $newResourceId = null;

            switch ($resource['resource_type']) {
                case 'knowledge':
                    $detail = KnowledgeBase::find($resource['resource_id']);
                    if ($detail) {
                        // 复制知识库
                        $newResourceId = KnowledgeBase::create([
                            'user_id' => $userId,
                            'title' => $detail['title'] . ' (导入)',
                            'description' => $detail['description'],
                            'category' => $detail['category'],
                            'tags' => $detail['tags'],
                            'is_public' => 0, // 导入后为私有
                            'status' => 1
                        ]);
                        // TODO: 复制知识条目
                    }
                    break;

                case 'prompt':
                    $detail = AIPromptTemplate::find($resource['resource_id']);
                    if ($detail) {
                        $newResourceId = AIPromptTemplate::create([
                            'user_id' => $userId,
                            'title' => $detail['title'] . ' (导入)',
                            'description' => $detail['description'] ?? null,
                            'category' => $detail['category'] ?? 'general',
                            'type' => $detail['type'] ?? 'custom',
                            'content' => $detail['content'],
                            'variables' => $detail['variables'] ?? null,
                            'tags' => $detail['tags'] ?? null,
                            'is_public' => 0,
                            'status' => 1
                        ]);
                    }
                    break;

                case 'template':
                    $detail = CreationTemplate::find($resource['resource_id']);
                    if ($detail) {
                        $newResourceId = CreationTemplate::create([
                            'user_id' => $userId,
                            'title' => $detail['title'] . ' (导入)',
                            'description' => $detail['description'] ?? null,
                            'category' => $detail['category'] ?? 'general',
                            'type' => $detail['type'] ?? 'custom',
                            'content' => $detail['content'],
                            'structure' => $detail['structure'] ?? null,
                            'tags' => $detail['tags'] ?? null,
                            'is_public' => 0,
                            'status' => 1
                        ]);
                    }
                    break;

                case 'agent':
                    $detail = AIAgent::find($resource['resource_id']);
                    if ($detail) {
                        $newResourceId = AIAgent::create([
                            'user_id' => $userId,
                            'name' => $detail['name'] . ' (导入)',
                            'description' => $detail['description'] ?? null,
                            'category' => $detail['category'] ?? 'general',
                            'type' => $detail['type'] ?? 'custom',
                            'system_prompt' => $detail['system_prompt'] ?? $detail['prompt'] ?? '',
                            'model_config' => $detail['model_config'] ?? null,
                            'capabilities' => $detail['capabilities'] ?? null,
                            'avatar' => $detail['avatar'] ?? null,
                            'is_public' => 0,
                            'status' => 1
                        ]);
                    }
                    break;
            }

            if ($newResourceId) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => '导入成功',
                    'resource_id' => $newResourceId,
                    'resource_type' => $resource['resource_type']
                ]);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => '导入失败：资源不存在或复制失败']);
            }
        } catch (\Exception $e) {
            error_log('Import error: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => '导入失败：' . $e->getMessage()]);
        }
    }
}
