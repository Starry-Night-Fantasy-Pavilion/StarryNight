<?php

namespace app\admin\controller;

use app\models\NoticeBar;
use app\models\UserFeedback;
use app\models\Announcement;
use app\models\AnnouncementCategory;
use app\services\Database;
use Exception;

/**
 * 1.8 通知栏管理、1.9 用户反馈管理、1.10 公告管理
 */
class NoticeAnnouncementController extends BaseController
{
    private function render(string $view, array $vars = []): void
    {
        $title = $vars['title'] ?? '内容管理';
        $currentPage = $vars['currentPage'] ?? 'notice';
        ob_start();
        extract($vars);
        require __DIR__ . '/../views/notice_announcement/' . $view . '.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layout.php';
    }

    private function tableExists(string $table): bool
    {
        $prefix = Database::prefix();
        $pdo = Database::pdo();
        $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($prefix . $table));
        return $stmt->fetch() !== false;
    }

    // ---------- 1.8 通知栏 ----------
    public function index()
    {
        try {
        header('Location: /' . trim((string)get_env('ADMIN_PATH', 'admin'), '/') . '/notice/list');
        exit;
    
        } catch (Exception $e) {
            // 记录错误日志
            error_log('Controller Error in NoticeAnnouncementController::index: ' . $e->getMessage());
            
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

    public function noticeList()
    {
        if (!$this->tableExists('notice_bar')) {
            $this->render('message', ['title' => '通知栏', 'message' => '请先执行数据库迁移 013。']);
            return;
        }
        $lang = isset($_GET['lang']) ? trim($_GET['lang']) : null;
        $status = isset($_GET['status']) ? trim($_GET['status']) : null;
        $list = NoticeBar::getAll($lang, $status);
        $this->render('notice_list', [
            'title' => '通知栏管理',
            'currentPage' => 'notice-list',
            'list' => $list,
        ]);
    }

    public function noticeEdit($id)
    {
        $item = $id === 'new' ? null : NoticeBar::find((int)$id);
        if ($item === null && $id !== 'new') {
            header('Location: /' . trim((string)get_env('ADMIN_PATH', 'admin'), '/') . '/notice/list');
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $loopWeightRaw = trim((string)($_POST['loop_weight'] ?? ''));
            $loopWeight = $loopWeightRaw === '' ? null : (int)$loopWeightRaw;
            $data = [
                'content' => $_POST['content'] ?? '',
                'priority' => (int)($_POST['priority'] ?? 0),
                'loop_weight' => $loopWeight,
                'display_from' => !empty($_POST['display_from']) ? $_POST['display_from'] : null,
                'display_to' => !empty($_POST['display_to']) ? $_POST['display_to'] : null,
                'status' => isset($_POST['status']) && $_POST['status'] === 'enabled' ? 'enabled' : 'disabled',
                'lang' => $_POST['lang'] ?? 'zh-CN',
            ];
            if ($id === 'new') {
                $newId = NoticeBar::create($data);
                header('Location: /' . trim((string)get_env('ADMIN_PATH', 'admin'), '/') . '/notice/edit/' . $newId);
                exit;
            }
            NoticeBar::update((int)$id, $data);
            header('Location: /' . trim((string)get_env('ADMIN_PATH', 'admin'), '/') . '/notice/list');
            exit;
        }
        $this->render('notice_edit', [
            'title' => $item ? '编辑通知' : '发布通知',
            'currentPage' => 'notice-list',
            'item' => $item,
        ]);
    }

    public function noticeDelete($id)
    {
        if (NoticeBar::delete((int)$id)) {
            header('Location: /' . trim((string)get_env('ADMIN_PATH', 'admin'), '/') . '/notice/list');
            exit;
        }
        $this->render('message', ['title' => '错误', 'message' => '删除失败']);
    }

    public function noticeToggle($id)
    {
        NoticeBar::toggleStatus((int)$id);
        header('Location: /' . trim((string)get_env('ADMIN_PATH', 'admin'), '/') . '/notice/list');
        exit;
    }

    // ---------- 1.9 用户反馈 ----------
    public function feedbackList()
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $type = isset($_GET['type']) ? trim($_GET['type']) : null;
        $status = isset($_GET['status']) ? trim($_GET['status']) : null;
        $data = UserFeedback::getList($page, 20, $type, $status);
        $this->render('feedback_list', [
            'title' => '用户反馈管理',
            'currentPage' => 'feedback-list',
            'list' => $data['list'],
            'page' => $page,
            'hasMore' => $data['hasMore'],
        ]);
    }

    public function feedbackDetail($id)
    {
        $feedback = UserFeedback::find((int)$id);
        if (!$feedback) {
            header('Location: /' . trim((string)get_env('ADMIN_PATH', 'admin'), '/') . '/feedback/list');
            exit;
        }
        $attachments = UserFeedback::getAttachments((int)$id);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['_action'] ?? '';
            if ($action === 'reply') {
                $reply = trim($_POST['admin_reply'] ?? '');
                if ($reply !== '') {
                    $adminId = $_SESSION['admin_user_id'] ?? 0;
                    UserFeedback::reply((int)$id, $reply, (int)$adminId);
                }
            } elseif ($action === 'status') {
                $status = $_POST['status'] ?? '';
                if (in_array($status, ['open', 'in_progress', 'resolved', 'closed'], true)) {
                    UserFeedback::updateStatus((int)$id, $status);
                }
            }
            header('Location: /' . trim((string)get_env('ADMIN_PATH', 'admin'), '/') . '/feedback/detail/' . $id);
            exit;
        }
        $this->render('feedback_detail', [
            'title' => '反馈详情',
            'currentPage' => 'feedback-list',
            'feedback' => $feedback,
            'attachments' => $attachments,
        ]);
    }

    public function feedbackDelete($id)
    {
        if (UserFeedback::delete((int)$id)) {
            header('Location: /' . trim((string)get_env('ADMIN_PATH', 'admin'), '/') . '/feedback/list');
            exit;
        }
        $this->render('message', ['title' => '错误', 'message' => '删除失败']);
    }

    // ---------- 1.10 公告 ----------
    public function announcementList()
    {
        if (!$this->tableExists('announcements')) {
            $this->render('message', ['title' => '公告', 'message' => '请先执行数据库迁移 013。']);
            return;
        }
        $categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
        $status = isset($_GET['status']) ? trim($_GET['status']) : null;
        $list = Announcement::getAll($categoryId ?: null, $status);
        $categories = AnnouncementCategory::getAll();
        $this->render('announcement_list', [
            'title' => '公告管理',
            'currentPage' => 'announcement-list',
            'list' => $list,
            'categories' => $categories,
        ]);
    }

    public function announcementEdit($id)
    {
        $item = $id === 'new' ? null : Announcement::find((int)$id);
        if ($item === null && $id !== 'new') {
            header('Location: /' . trim((string)get_env('ADMIN_PATH', 'admin'), '/') . '/announcement/list');
            exit;
        }
        $categories = AnnouncementCategory::getAll();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
                'title' => $_POST['title'] ?? '',
                'content' => $_POST['content'] ?? '',
                'publish_at' => !empty($_POST['publish_at']) ? $_POST['publish_at'] : null,
                'status' => isset($_POST['status']) && $_POST['status'] === 'enabled' ? 'enabled' : 'disabled',
                'is_pinned' => isset($_POST['is_pinned']) ? 1 : 0,
                'is_popup' => isset($_POST['is_popup']) ? 1 : 0,
                'sort_order' => (int)($_POST['sort_order'] ?? 0),
            ];
            if ($id === 'new') {
                $newId = Announcement::create($data);
                header('Location: /' . trim((string)get_env('ADMIN_PATH', 'admin'), '/') . '/announcement/edit/' . $newId);
                exit;
            }
            Announcement::update((int)$id, $data);
            header('Location: /' . trim((string)get_env('ADMIN_PATH', 'admin'), '/') . '/announcement/list');
            exit;
        }
        $this->render('announcement_edit', [
            'title' => $item ? '编辑公告' : '发布公告',
            'currentPage' => 'announcement-list',
            'item' => $item,
            'categories' => $categories,
        ]);
    }

    public function announcementDelete($id)
    {
        if (Announcement::delete((int)$id)) {
            header('Location: /' . trim((string)get_env('ADMIN_PATH', 'admin'), '/') . '/announcement/list');
            exit;
        }
        $this->render('message', ['title' => '错误', 'message' => '删除失败']);
    }

    public function announcementToggle($id)
    {
        Announcement::toggleStatus((int)$id);
        header('Location: /' . trim((string)get_env('ADMIN_PATH', 'admin'), '/') . '/announcement/list');
        exit;
    }

    public function announcementCategories()
    {
        if (!$this->tableExists('announcement_categories')) {
            $this->render('message', ['title' => '公告分类', 'message' => '请先执行数据库迁移 013。']);
            return;
        }
        $list = AnnouncementCategory::getAll();
        $this->render('announcement_categories', [
            'title' => '公告分类',
            'currentPage' => 'announcement-list',
            'list' => $list,
        ]);
    }

    public function announcementCategoryEdit($id)
    {
        $item = $id === 'new' ? null : AnnouncementCategory::find((int)$id);
        if ($item === null && $id !== 'new') {
            header('Location: /' . trim((string)get_env('ADMIN_PATH', 'admin'), '/') . '/announcement/categories');
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = ['name' => $_POST['name'] ?? '', 'sort_order' => (int)($_POST['sort_order'] ?? 0)];
            if ($id === 'new') {
                $newId = AnnouncementCategory::create($data);
                header('Location: /' . trim((string)get_env('ADMIN_PATH', 'admin'), '/') . '/announcement/category/' . $newId);
                exit;
            }
            AnnouncementCategory::update((int)$id, $data);
            header('Location: /' . trim((string)get_env('ADMIN_PATH', 'admin'), '/') . '/announcement/categories');
            exit;
        }
        $this->render('announcement_category_edit', [
            'title' => $item ? '编辑分类' : '新增分类',
            'currentPage' => 'announcement-list',
            'item' => $item,
        ]);
    }

    public function announcementCategoryDelete($id)
    {
        if (AnnouncementCategory::delete((int)$id)) {
            header('Location: /' . trim((string)get_env('ADMIN_PATH', 'admin'), '/') . '/announcement/categories');
            exit;
        }
        $this->render('message', ['title' => '错误', 'message' => '删除失败']);
    }
}
