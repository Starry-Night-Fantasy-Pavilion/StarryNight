<?php

namespace app\admin\controller;

use app\models\Community;
use Exception;

class CommunityController extends BaseController
{
    public function index()
    {
        try {
        $page = (int)($_GET['page'] ?? 1);
        $filters = [
            'type' => $_GET['type'] ?? '',
            'status' => $_GET['status'] ?? '',
            'deleted' => $_GET['deleted'] ?? '',
            'category_id' => $_GET['category_id'] ?? '',
            'tag_id' => $_GET['tag_id'] ?? '',
            'q' => $_GET['q'] ?? '',
        ];

        $data = Community::listContents($page, 20, $filters);
        $categories = Community::getCategories();
        $tags = Community::getTags();

        $title = '社区内容';
        $currentPage = 'community';
        $adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '/');

        ob_start();
        require __DIR__ . '/../views/community/index.php';
        $content = ob_get_clean();

        require __DIR__ . '/../views/layout.php';
    
        } catch (Exception $e) {
            // 记录错误日志
            error_log('Controller Error in CommunityController::index: ' . $e->getMessage());
            
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

    public function actions()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->error('请求方法不允许', 405);
            return;
        }

        $adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '/');
        $action = $_POST['action'] ?? '';
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            header("Location: /{$adminPrefix}/community");
            exit;
        }

        switch ($action) {
            case 'pin':
                Community::setPinned($id, true);
                break;
            case 'unpin':
                Community::setPinned($id, false);
                break;
            case 'recommend':
                Community::setRecommended($id, true);
                break;
            case 'unrecommend':
                Community::setRecommended($id, false);
                break;
            case 'delete':
                Community::softDeleteContent($id);
                break;
            case 'restore':
                Community::restoreContent($id);
                break;
        }

        $back = $_SERVER['HTTP_REFERER'] ?? "/{$adminPrefix}/community";
        header("Location: " . $back);
        exit;
    }

    public function categories()
    {
        $adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '/');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'create') {
                $name = trim((string)($_POST['name'] ?? ''));
                $slug = trim((string)($_POST['slug'] ?? ''));
                $sort = (int)($_POST['sort'] ?? 0);
                $active = isset($_POST['is_active']);
                if ($name !== '') {
                    Community::createCategory($name, $slug, $sort, $active);
                }
            } elseif ($action === 'update') {
                $id = (int)($_POST['id'] ?? 0);
                $name = trim((string)($_POST['name'] ?? ''));
                $slug = trim((string)($_POST['slug'] ?? ''));
                $sort = (int)($_POST['sort'] ?? 0);
                $active = isset($_POST['is_active']);
                if ($id > 0 && $name !== '') {
                    Community::updateCategory($id, $name, $slug, $sort, $active);
                }
            } elseif ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    Community::deleteCategory($id);
                }
            }

            header("Location: /{$adminPrefix}/community/categories");
            exit;
        }

        $categories = Community::getCategories();
        $title = '内容分类';
        $currentPage = 'community-categories';

        ob_start();
        require __DIR__ . '/../views/community/categories.php';
        $content = ob_get_clean();

        require __DIR__ . '/../views/layout.php';
    }

    public function tags()
    {
        $adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '/');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'create') {
                $name = trim((string)($_POST['name'] ?? ''));
                $slug = trim((string)($_POST['slug'] ?? ''));
                if ($name !== '') {
                    Community::createTag($name, $slug);
                }
            } elseif ($action === 'update') {
                $id = (int)($_POST['id'] ?? 0);
                $name = trim((string)($_POST['name'] ?? ''));
                $slug = trim((string)($_POST['slug'] ?? ''));
                if ($id > 0 && $name !== '') {
                    Community::updateTag($id, $name, $slug);
                }
            } elseif ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    Community::deleteTag($id);
                }
            }

            header("Location: /{$adminPrefix}/community/tags");
            exit;
        }

        $tags = Community::getTags();
        $title = '内容标签';
        $currentPage = 'community-tags';

        ob_start();
        require __DIR__ . '/../views/community/tags.php';
        $content = ob_get_clean();

        require __DIR__ . '/../views/layout.php';
    }

    public function reports()
    {
        $adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '/');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $status = (string)($_POST['status'] ?? '');
            $note = (string)($_POST['note'] ?? '');
            $adminId = (int)($_SESSION['admin_user']['id'] ?? 0);
            if ($id > 0 && in_array($status, ['pending', 'valid', 'invalid', 'resolved'], true)) {
                Community::handleReport($id, $adminId, $status, $note);
            }
            header("Location: /{$adminPrefix}/community/reports");
            exit;
        }

        $page = (int)($_GET['page'] ?? 1);
        $status = $_GET['status'] ?? '';
        $data = Community::listReports($page, 20, array_filter(['status' => $status]));

        $title = '举报处理';
        $currentPage = 'community-reports';

        ob_start();
        require __DIR__ . '/../views/community/reports.php';
        $content = ob_get_clean();

        require __DIR__ . '/../views/layout.php';
    }

    public function activities()
    {
        $adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '/');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'create') {
                Community::createActivity([
                    'title' => trim((string)($_POST['title'] ?? '')),
                    'description' => (string)($_POST['description'] ?? ''),
                    'start_at' => $_POST['start_at'] ?? '',
                    'end_at' => $_POST['end_at'] ?? '',
                    'status' => $_POST['status'] ?? 'draft',
                    'is_pinned' => isset($_POST['is_pinned']),
                ]);
            } elseif ($action === 'update') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    Community::updateActivity($id, [
                        'title' => trim((string)($_POST['title'] ?? '')),
                        'description' => (string)($_POST['description'] ?? ''),
                        'start_at' => $_POST['start_at'] ?? '',
                        'end_at' => $_POST['end_at'] ?? '',
                        'status' => $_POST['status'] ?? 'draft',
                        'is_pinned' => isset($_POST['is_pinned']),
                    ]);
                }
            } elseif ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    Community::deleteActivity($id);
                }
            }

            header("Location: /{$adminPrefix}/community/activities");
            exit;
        }

        $activities = Community::listActivities();
        $title = '社区活动';
        $currentPage = 'community-activities';

        ob_start();
        require __DIR__ . '/../views/community/activities.php';
        $content = ob_get_clean();

        require __DIR__ . '/../views/layout.php';
    }
}

