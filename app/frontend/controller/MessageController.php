<?php

namespace app\frontend\controller;

use app\models\Announcement;
use Core\Controller;

class MessageController extends Controller
{
    /**
     * 消息接收页面（整合站内公告）
     */
    public function index()
    {
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        $userId = $_SESSION['user_id'];
        
        // 获取公告列表
        $page = intval($_GET['page'] ?? 1);
        $perPage = intval($_GET['per_page'] ?? 20);
        
        $result = Announcement::getUserAnnouncements($userId, $page, $perPage);
        
        // 获取未读数量
        $unreadCount = Announcement::getUnreadCount($userId);
        
        $this->view('message/index', [
            'announcements' => $result['announcements'],
            'total' => $result['total'],
            'page' => $result['page'],
            'perPage' => $result['perPage'],
            'totalPages' => $result['totalPages'],
            'unreadCount' => $unreadCount
        ]);
    }

    /**
     * 标记公告为已读
     */
    public function markRead()
    {
        if (!$this->isLoggedIn()) {
            $this->json([
                'success' => false,
                'message' => '请先登录'
            ]);
            return;
        }

        $userId = $_SESSION['user_id'];
        $announcementId = intval($_POST['announcement_id'] ?? 0);

        if ($announcementId <= 0) {
            $this->json([
                'success' => false,
                'message' => '公告ID无效'
            ]);
            return;
        }

        $success = Announcement::markAsRead($userId, $announcementId);
        
        if ($success) {
            // 重新获取未读数量
            $unreadCount = Announcement::getUnreadCount($userId);
            $this->json([
                'success' => true,
                'message' => '已标记为已读',
                'data' => [
                    'unread_count' => $unreadCount
                ]
            ]);
        } else {
            $this->json([
                'success' => false,
                'message' => '标记失败'
            ]);
        }
    }

    /**
     * 获取未读数量 API
     */
    public function unreadCount()
    {
        if (!$this->isLoggedIn()) {
            $this->json([
                'success' => false,
                'message' => '请先登录',
                'data' => ['unread_count' => 0]
            ]);
            return;
        }

        $userId = $_SESSION['user_id'];
        $unreadCount = Announcement::getUnreadCount($userId);
        
        $this->json([
            'success' => true,
            'data' => [
                'unread_count' => $unreadCount
            ]
        ]);
    }

    /**
     * 获取消息列表（按分类）API - 用于弹窗
     */
    public function getMessages()
    {
        if (!$this->isLoggedIn()) {
            $this->json([
                'success' => false,
                'message' => '请先登录',
                'data' => []
            ]);
            return;
        }

        $userId = $_SESSION['user_id'];
        $category = $_GET['category'] ?? 'announcement'; // announcement, friend
        
        $data = [];
        
        if ($category === 'announcement') {
            // 获取站内公告
            $result = Announcement::getUserAnnouncements($userId, 1, 50);
            $data = [
                'category' => 'announcement',
                'name' => '站内公告',
                'items' => array_map(function($item) {
                    return [
                        'id' => (int)$item['id'],
                        'title' => $item['title'],
                        'content' => strip_tags($item['content']),
                        'time' => $item['published_at'] ?? $item['created_at'],
                        'is_read' => (bool)$item['is_read'],
                        'is_top' => (bool)$item['is_top'],
                        'category' => $item['category'] ?? 'system_update'
                    ];
                }, $result['announcements']),
                'unread_count' => Announcement::getUnreadCount($userId)
            ];
        } elseif ($category === 'friend') {
            // 好友消息（预留，后续接入好友系统）
            $data = [
                'category' => 'friend',
                'name' => '好友消息',
                'items' => [],
                'unread_count' => 0
            ];
        }
        
        $this->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * 获取所有分类的未读数量汇总
     */
    public function getUnreadSummary()
    {
        if (!$this->isLoggedIn()) {
            $this->json([
                'success' => false,
                'message' => '请先登录',
                'data' => []
            ]);
            return;
        }

        $userId = $_SESSION['user_id'];
        
        $this->json([
            'success' => true,
            'data' => [
                'announcement' => Announcement::getUnreadCount($userId),
                'friend' => 0  // 预留，后续接入好友系统
            ]
        ]);
    }

    /**
     * 获取需要弹窗显示的公告
     */
    public function getPopupAnnouncement()
    {
        if (!$this->isLoggedIn()) {
            $this->json([
                'success' => false,
                'message' => '请先登录',
                'data' => null
            ]);
            return;
        }

        $userId = $_SESSION['user_id'];
        
        // 获取前端传来的已查看弹窗ID列表（从localStorage）
        $viewedPopupIds = json_decode($_GET['viewed_ids'] ?? '[]', true);
        if (!is_array($viewedPopupIds)) {
            $viewedPopupIds = [];
        }
        
        $announcement = Announcement::getPopupAnnouncement($userId);
        
        if ($announcement && !in_array($announcement['id'], $viewedPopupIds)) {
            $this->json([
                'success' => true,
                'data' => [
                    'id' => (int)$announcement['id'],
                    'title' => $announcement['title'],
                    'content' => $announcement['content'],
                    'category' => $announcement['category'] ?? 'system_update',
                    'published_at' => $announcement['published_at'] ?? $announcement['created_at']
                ]
            ]);
        } else {
            $this->json([
                'success' => true,
                'data' => null
            ]);
        }
    }

    /**
     * 标记弹窗公告已查看
     */
    public function markPopupViewed()
    {
        if (!$this->isLoggedIn()) {
            $this->json([
                'success' => false,
                'message' => '请先登录'
            ]);
            return;
        }

        $announcementId = intval($_POST['announcement_id'] ?? 0);
        
        if ($announcementId <= 0) {
            $this->json([
                'success' => false,
                'message' => '公告ID无效'
            ]);
            return;
        }

        // 记录到Session
        if (!isset($_SESSION['viewed_popup_announcements'])) {
            $_SESSION['viewed_popup_announcements'] = [];
        }
        
        if (!in_array($announcementId, $_SESSION['viewed_popup_announcements'])) {
            $_SESSION['viewed_popup_announcements'][] = $announcementId;
        }

        $this->json([
            'success' => true,
            'message' => '已记录'
        ]);
    }
}
