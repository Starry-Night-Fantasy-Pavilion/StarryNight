<?php

namespace app\frontend\controller;

use Core\Controller;
use app\models\SiteMessage;

class NotificationsController extends Controller
{
    /**
     * 站内信息：仅展示“退回通知”（按关键字过滤）
     */
    public function index()
    {
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        $userId = (int)($_SESSION['user_id'] ?? 0);
        $page = intval($_GET['page'] ?? 1);
        $perPage = intval($_GET['per_page'] ?? 20);

        $keywords = ['退回', '驳回', '要求修改', '审核未通过', '未通过'];
        $result = SiteMessage::getUserMessages($userId, $page, $perPage, $keywords);
        $unreadCount = SiteMessage::getUnreadCount($userId, $keywords);

        $this->view('notifications/index', [
            'items' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'perPage' => $result['perPage'],
            'totalPages' => $result['totalPages'],
            'unreadCount' => $unreadCount,
        ]);
    }

    public function markRead()
    {
        if (!$this->isLoggedIn()) {
            $this->json(['success' => false, 'message' => '请先登录']);
            return;
        }

        $userId = (int)($_SESSION['user_id'] ?? 0);
        $messageId = intval($_POST['message_id'] ?? 0);
        if ($messageId <= 0) {
            $this->json(['success' => false, 'message' => '消息ID无效']);
            return;
        }

        $ok = SiteMessage::markAsRead($userId, $messageId);
        $keywords = ['退回', '驳回', '要求修改', '审核未通过', '未通过'];
        $unreadCount = SiteMessage::getUnreadCount($userId, $keywords);

        $this->json([
            'success' => (bool)$ok,
            'message' => $ok ? '已标记为已读' : '标记失败',
            'data' => ['unread_count' => $unreadCount],
        ]);
    }

    public function unreadCount()
    {
        if (!$this->isLoggedIn()) {
            $this->json(['success' => false, 'message' => '请先登录', 'data' => ['unread_count' => 0]]);
            return;
        }
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $keywords = ['退回', '驳回', '要求修改', '审核未通过', '未通过'];
        $unreadCount = SiteMessage::getUnreadCount($userId, $keywords);
        $this->json(['success' => true, 'data' => ['unread_count' => $unreadCount]]);
    }
}

