<?php

namespace app\frontend\controller;

use app\models\User;
use app\models\Announcement;
use Core\Controller;

class AnnouncementController extends Controller
{
    /**
     * 公告中心页面
     */
    public function index()
    {
        try {
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        $userId = $_SESSION['user_id'];
        
        // 获取公告列表
        $page = intval($_GET['page'] ?? 1);
        $perPage = intval($_GET['per_page'] ?? 20);
        $category = $_GET['category'] ?? '';
        $status = $_GET['status'] ?? '';

        $announcements = Announcement::getList($page, $perPage, $category, $status);
        
        // 获取用户未读公告数量
        $unreadCount = Announcement::getUnreadCount($userId);
        
        $this->view('announcement/index', [
            'announcements' => $announcements,
            'categories' => Announcement::getCategories(),
            'category' => $category,
            'status' => $status,
            'page' => $page,
            'perPage' => $perPage,
            'unreadCount' => $unreadCount
        ]);
    
        } catch (Exception $e) {
            // 记录错误日志
            error_log('Controller Error in AnnouncementController::index: ' . $e->getMessage());
            
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
     * 公告详情页面
     */
    public function detail()
    {
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        $announcementId = intval($_GET['id'] ?? 0);
        
        $announcement = Announcement::getById($announcementId);
        
        if (!$announcement) {
            $this->json(['success' => false, 'message' => '公告不存在']);
            return;
        }

        // 检查权限
        if ($announcement['status'] != 1) {
            $this->json(['success' => false, 'message' => '公告未发布']);
            return;
        }

        // 记录用户已读
        $userId = $_SESSION['user_id'];
        Announcement::markAsRead($userId, $announcementId);

        $this->view('announcement/detail', [
            'announcement' => $announcement
        ]);
    }

    /**
     * 标记公告为已读
     */
    public function markAsRead()
    {
        if (!$this->isLoggedIn()) {
            $this->json(['success' => false, 'message' => '请先登录']);
            return;
        }

        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => '请求方法错误']);
            return;
        }

        $userId = $_SESSION['user_id'];
        $announcementId = intval($_POST['id'] ?? 0);

        $success = Announcement::markAsRead($userId, $announcementId);
        
        if ($success) {
            $this->json(['success' => true, 'message' => '标记成功']);
        } else {
            $this->json(['success' => false, 'message' => '标记失败']);
        }
    }

    /**
     * 获取用户未读公告列表
     */
    public function unread()
    {
        if (!$this->isLoggedIn()) {
            $this->json(['success' => false, 'message' => '请先登录']);
            return;
        }

        $userId = $_SESSION['user_id'];
        $limit = intval($_GET['limit'] ?? 10);
        $unreadAnnouncements = Announcement::getReadAnnouncements($userId, $limit);

        $this->view('announcement/unread', [
            'announcements' => $unreadAnnouncements
        ]);
    }
}