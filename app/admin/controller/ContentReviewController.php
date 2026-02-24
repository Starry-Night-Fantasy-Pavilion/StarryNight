<?php

namespace app\admin\controller;

use app\models\ContentReview;
use Exception;

class ContentReviewController extends BaseController
{
    /**
     * 审查队列列表
     */
    public function index()
    {
        try {
        $page = (int)($_GET['page'] ?? 1);
        $status = $_GET['status'] ?? '';
        $type = $_GET['content_type'] ?? '';

        $filters = array_filter([
            'status' => $status,
            'content_type' => $type
        ]);

        $data = ContentReview::getQueue($page, 15, $filters);
        
        $title = '审查队列';
        $currentPage = 'content-review';
        $adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '/');

        ob_start();
        require __DIR__ . '/../views/content_review/index.php';
        $content = ob_get_clean();

        require __DIR__ . '/../views/layout.php';
    
        } catch (Exception $e) {
            // 记录错误日志
            error_log('Controller Error in ContentReviewController::index: ' . $e->getMessage());
            
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
    }

    /**
     * 审查详情与操作
     */
    public function details($id)
    {
        $item = ContentReview::find((int)$id);

        if (!$item) {
            $adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '/');
            header("Location: /{$adminPrefix}/content-review");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $comment = $_POST['comment'] ?? '';
            $reviewerId = $_SESSION['admin_user']['id'] ?? 0;

            if (ContentReview::review((int)$id, (int)$reviewerId, $action, $comment)) {
                $adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '/');
                header("Location: /{$adminPrefix}/content-review/details/{$id}");
                exit;
            }
        }

        $title = '审查详情';
        $currentPage = 'content-review';
        $adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '/');

        ob_start();
        require __DIR__ . '/../views/content_review/details.php';
        $content = ob_get_clean();

        require __DIR__ . '/../views/layout.php';
    }

    /**
     * 批量审查操作
     */
    public function batchReview()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => '仅支持POST请求'], 405);
            return;
        }

        $idsInput = $_POST['ids'] ?? [];
        // 处理可能是逗号分隔的字符串或数组
        if (is_string($idsInput)) {
            $ids = array_filter(array_map('intval', explode(',', $idsInput)));
        } else {
            $ids = array_filter(array_map('intval', (array)$idsInput));
        }
        
        $action = $_POST['action'] ?? '';
        $comment = $_POST['comment'] ?? '';
        $reviewerId = $_SESSION['admin_user']['id'] ?? 0;

        if (empty($ids)) {
            $this->json(['success' => false, 'message' => '请选择要操作的内容'], 400);
            return;
        }

        if (empty($action) || !in_array($action, ['approve', 'reject', 'request_revision'], true)) {
            $this->json(['success' => false, 'message' => '无效的操作类型'], 400);
            return;
        }

        $successCount = 0;
        $failedIds = [];

        foreach ($ids as $id) {
            if ($id <= 0) {
                continue;
            }

            if (ContentReview::review($id, $reviewerId, $action, $comment)) {
                $successCount++;
            } else {
                $failedIds[] = $id;
            }
        }

        $this->json([
            'success' => true,
            'message' => "成功处理 {$successCount} 条记录" . (!empty($failedIds) ? "，失败 " . count($failedIds) . " 条" : ''),
            'success_count' => $successCount,
            'failed_ids' => $failedIds
        ]);
    }

    /**
     * 审查流程配置
     */
    public function configs()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $type = $_POST['content_type'] ?? '';
            $steps = $_POST['steps'] ?? [];
            $enabled = isset($_POST['is_enabled']);

            if ($type) {
                ContentReview::updateConfig($type, $steps, $enabled);
            }
            
            $adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '/');
            header("Location: /{$adminPrefix}/content-review/configs");
            exit;
        }

        $configs = ContentReview::getConfigs();
        
        $title = '审查流程配置';
        $currentPage = 'content-review-configs';
        $adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '/');

        ob_start();
        require __DIR__ . '/../views/content_review/configs.php';
        $content = ob_get_clean();

        require __DIR__ . '/../views/layout.php';
    }
}
