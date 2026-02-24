<?php

namespace app\frontend\controller;

use app\models\NoticeBar;
use Core\Controller;

class NoticeBarController extends Controller
{
    /**
     * 获取通知栏数据 API
     */
    public function api()
    {
        $lang = $_GET['lang'] ?? 'zh-CN';
        
        // 获取当前有效的通知
        $notices = NoticeBar::getAll($lang, 'enabled');
        
        // 过滤时间有效的通知
        $currentTime = date('Y-m-d H:i:s');
        $validNotices = [];
        
        foreach ($notices as $notice) {
            $startTime = $notice['display_from'] ?? null;
            $endTime = $notice['display_to'] ?? null;
            
            // 检查时间范围
            $timeValid = true;
            if ($startTime && $startTime > $currentTime) {
                $timeValid = false;
            }
            if ($endTime && $endTime < $currentTime) {
                $timeValid = false;
            }
            
            if ($timeValid) {
                $validNotices[] = [
                    'id' => $notice['id'],
                    'content' => $notice['content'],
                    'link' => $notice['link'] ?? null,
                    'priority' => $notice['priority']
                ];
            }
        }
        
        // 按优先级和时间排序
        usort($validNotices, function($a, $b) {
            if ($a['priority'] == $b['priority']) {
                return 0;
            }
            return ($a['priority'] > $b['priority']) ? -1 : 1;
        });
        
        $this->json([
            'success' => true,
            'data' => $validNotices
        ]);
    }
    
    /**
     * 通知栏管理页面（管理员）
     */
    public function index()
    {
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }
        
        // 检查是否为管理员
        if (!$this->isAdmin()) {
            $this->json(['success' => false, 'message' => '权限不足']);
            return;
        }
        
        $page = intval($_GET['page'] ?? 1);
        $perPage = intval($_GET['per_page'] ?? 20);
        $status = $_GET['status'] ?? '';
        $lang = $_GET['lang'] ?? '';
        
        // 获取所有通知（包括已禁用和已过期的）
        $notices = NoticeBar::getAll($lang, $status);
        
        // 分页处理
        $total = count($notices);
        $notices = array_slice($notices, ($page - 1) * $perPage, $perPage);
        
        $this->view('notice_bar/index', [
            'notices' => $notices,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'status' => $status,
            'lang' => $lang
        ]);
    }
    
    /**
     * 创建通知
     */
    public function create()
    {
        if (!$this->isLoggedIn()) {
            $this->json(['success' => false, 'message' => '请先登录']);
            return;
        }
        
        if (!$this->isAdmin()) {
            $this->json(['success' => false, 'message' => '权限不足']);
            return;
        }
        
        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => '请求方法错误']);
            return;
        }
        
        $content = trim($_POST['content'] ?? '');
        $link = trim($_POST['link'] ?? '');
        $priority = intval($_POST['priority'] ?? 3);
        $startTime = $_POST['start_time'] ?? null;
        $endTime = $_POST['end_time'] ?? null;
        $status = $_POST['status'] ?? 'enabled';
        $lang = $_POST['lang'] ?? 'zh-CN';
        
        if (empty($content)) {
            $this->json(['success' => false, 'message' => '通知内容不能为空']);
            return;
        }
        
        $data = [
            'content' => $content,
            'link' => $link ?: null,
            'priority' => $priority,
            'display_from' => $startTime ?: null,
            'display_to' => $endTime ?: null,
            'status' => $status,
            'lang' => $lang
        ];
        
        try {
            $id = NoticeBar::create($data);
            if ($id) {
                $this->json(['success' => true, 'message' => '通知创建成功', 'id' => $id]);
            } else {
                $this->json(['success' => false, 'message' => '通知创建失败']);
            }
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => '通知创建失败: ' . $e->getMessage()]);
        }
    }
    
    /**
     * 更新通知
     */
    public function update()
    {
        if (!$this->isLoggedIn()) {
            $this->json(['success' => false, 'message' => '请先登录']);
            return;
        }
        
        if (!$this->isAdmin()) {
            $this->json(['success' => false, 'message' => '权限不足']);
            return;
        }
        
        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => '请求方法错误']);
            return;
        }
        
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            $this->json(['success' => false, 'message' => '无效的通知ID']);
            return;
        }
        
        $notice = NoticeBar::find($id);
        if (!$notice) {
            $this->json(['success' => false, 'message' => '通知不存在']);
            return;
        }
        
        $content = trim($_POST['content'] ?? '');
        $link = trim($_POST['link'] ?? '');
        $priority = intval($_POST['priority'] ?? 3);
        $startTime = $_POST['start_time'] ?? null;
        $endTime = $_POST['end_time'] ?? null;
        $status = $_POST['status'] ?? 'enabled';
        $lang = $_POST['lang'] ?? 'zh-CN';
        
        if (empty($content)) {
            $this->json(['success' => false, 'message' => '通知内容不能为空']);
            return;
        }
        
        $data = [
            'content' => $content,
            'link' => $link ?: null,
            'priority' => $priority,
            'display_from' => $startTime ?: null,
            'display_to' => $endTime ?: null,
            'status' => $status,
            'lang' => $lang
        ];
        
        try {
            $success = NoticeBar::update($id, $data);
            if ($success) {
                $this->json(['success' => true, 'message' => '通知更新成功']);
            } else {
                $this->json(['success' => false, 'message' => '通知更新失败']);
            }
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => '通知更新失败: ' . $e->getMessage()]);
        }
    }
    
    /**
     * 删除通知
     */
    public function delete()
    {
        if (!$this->isLoggedIn()) {
            $this->json(['success' => false, 'message' => '请先登录']);
            return;
        }
        
        if (!$this->isAdmin()) {
            $this->json(['success' => false, 'message' => '权限不足']);
            return;
        }
        
        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => '请求方法错误']);
            return;
        }
        
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            $this->json(['success' => false, 'message' => '无效的通知ID']);
            return;
        }
        
        $notice = NoticeBar::find($id);
        if (!$notice) {
            $this->json(['success' => false, 'message' => '通知不存在']);
            return;
        }
        
        try {
            $success = NoticeBar::delete($id);
            if ($success) {
                $this->json(['success' => true, 'message' => '通知删除成功']);
            } else {
                $this->json(['success' => false, 'message' => '通知删除失败']);
            }
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => '通知删除失败: ' . $e->getMessage()]);
        }
    }
    
    /**
     * 切换通知状态
     */
    public function toggle()
    {
        if (!$this->isLoggedIn()) {
            $this->json(['success' => false, 'message' => '请先登录']);
            return;
        }
        
        if (!$this->isAdmin()) {
            $this->json(['success' => false, 'message' => '权限不足']);
            return;
        }
        
        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => '请求方法错误']);
            return;
        }
        
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            $this->json(['success' => false, 'message' => '无效的通知ID']);
            return;
        }
        
        $notice = NoticeBar::find($id);
        if (!$notice) {
            $this->json(['success' => false, 'message' => '通知不存在']);
            return;
        }
        
        try {
            $success = NoticeBar::toggleStatus($id);
            if ($success) {
                $this->json(['success' => true, 'message' => '状态切换成功']);
            } else {
                $this->json(['success' => false, 'message' => '状态切换失败']);
            }
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => '状态切换失败: ' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取通知详情
     */
    public function detail()
    {
        if (!$this->isLoggedIn()) {
            $this->json(['success' => false, 'message' => '请先登录']);
            return;
        }
        
        if (!$this->isAdmin()) {
            $this->json(['success' => false, 'message' => '权限不足']);
            return;
        }
        
        $id = intval($_GET['id'] ?? 0);
        if (!$id) {
            $this->json(['success' => false, 'message' => '无效的通知ID']);
            return;
        }
        
        $notice = NoticeBar::find($id);
        if (!$notice) {
            $this->json(['success' => false, 'message' => '通知不存在']);
            return;
        }
        
        $this->json([
            'success' => true,
            'data' => $notice
        ]);
    }
}