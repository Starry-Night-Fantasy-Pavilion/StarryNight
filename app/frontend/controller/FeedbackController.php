<?php

namespace app\frontend\controller;

use app\models\User;
use app\models\UserFeedback;
use Core\Controller;

class FeedbackController extends Controller
{
    /**
     * 反馈中心页面
     */
    public function index()
    {
        try {
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        $userId = $_SESSION['user_id'];
        
        // 获取用户反馈列表
        $page = intval($_GET['page'] ?? 1);
        $perPage = intval($_GET['per_page'] ?? 20);
        $type = $_GET['type'] ?? '';
        $status = $_GET['status'] ?? '';

        $feedbacks = UserFeedback::getByUserId($userId, $page, $perPage, $type, $status);
        
        $this->view('feedback/index', [
            'feedbacks' => $feedbacks,
            'types' => UserFeedback::getTypes(),
            'statuses' => UserFeedback::getStatuses(),
            'type' => $type,
            'status' => $status,
            'page' => $page,
            'perPage' => $perPage
        ]);
    
        } catch (Exception $e) {
            // 记录错误日志
            error_log('Controller Error in FeedbackController::index: ' . $e->getMessage());
            
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
     * 提交反馈
     */
    public function submit()
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
        $data = [
            'user_id' => $userId,
            'type' => $_POST['type'] ?? 'suggestion',
            'title' => $_POST['title'] ?? '',
            'content' => $_POST['content'] ?? '',
            'attachments' => []
        ];

        // 处理附件
        if (isset($_FILES['attachments'])) {
            $attachments = [];
            foreach ($_FILES['attachments']['name'] as $index => $name) {
                if ($_FILES['attachments']['error'][$index] === UPLOAD_ERR_OK) {
                    $attachments[] = [
                        'name' => $name,
                        'url' => $this->uploadAttachment($_FILES['attachments']['tmp_name'][$index], $name)
                    ];
                }
            }
            $data['attachments'] = json_encode($attachments);
        }

        $feedbackId = UserFeedback::create($data);
        
        if ($feedbackId) {
            $this->json([
                'success' => true,
                'message' => '反馈提交成功',
                'feedback_id' => $feedbackId
            ]);
        } else {
            $this->json(['success' => false, 'message' => '反馈提交失败']);
        }
    }

    /**
     * 获取反馈详情
     */
    public function detail()
    {
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        $feedbackId = intval($_GET['id'] ?? 0);
        
        $feedback = UserFeedback::getById($feedbackId);
        
        if (!$feedback) {
            $this->json(['success' => false, 'message' => '反馈不存在']);
            return;
        }

        // 检查权限
        if ($feedback['user_id'] !== $_SESSION['user_id']) {
            $this->json(['success' => false, 'message' => '无权限查看此反馈']);
            return;
        }

        $this->view('feedback/detail', [
            'feedback' => $feedback
        ]);
    }

    /**
     * 上传附件
     *
     * @param string $tmpName
     * @param string $originalName
     * @return string
     */
    private function uploadAttachment(string $tmpName, string $originalName): string
    {
        $uploadDir = 'uploads/feedback/' . date('Y/m/d');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $newName = uniqid() . '.' . $extension;
        $uploadPath = $uploadDir . '/' . $newName;

        if (move_uploaded_file($tmpName, $uploadPath)) {
            return '/uploads/feedback/' . date('Y/m/d') . '/' . $newName;
        }

        return '';
    }

    /**
     * 获取我的反馈统计
     */
    public function statistics()
    {
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        $userId = $_SESSION['user_id'];
        $statistics = UserFeedback::getStatistics($userId);
        
        $this->view('feedback/statistics', [
            'statistics' => $statistics
        ]);
    }

    /**
     * 获取所有反馈（管理员用）
     */
    public function all()
    {
        if (!$this->isAdmin()) {
            $this->json(['success' => false, 'message' => '权限不足']);
            return;
        }

        $page = intval($_GET['page'] ?? 1);
        $perPage = intval($_GET['per_page'] ?? 20);
        $type = $_GET['type'] ?? '';
        $status = $_GET['status'] ?? '';

        $feedbacks = UserFeedback::getAll($page, $perPage, $type, $status);
        
        $this->view('feedback/all', [
            'feedbacks' => $feedbacks,
            'types' => UserFeedback::getTypes(),
            'statuses' => UserFeedback::getStatuses(),
            'type' => $type,
            'status' => $status,
            'page' => $page,
            'perPage' => $perPage
        ]);
    }

    /**
     * 更新反馈状态（管理员用）
     */
    public function updateStatus()
    {
        if (!$this->isAdmin()) {
            $this->json(['success' => false, 'message' => '权限不足']);
            return;
        }

        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => '请求方法错误']);
            return;
        }

        $feedbackId = intval($_POST['id'] ?? 0);
        $status = intval($_POST['status'] ?? 1);
        $adminReply = $_POST['admin_reply'] ?? '';

        $success = UserFeedback::updateStatus($feedbackId, $status, $adminReply);
        
        if ($success) {
            $this->json(['success' => true, 'message' => '状态更新成功']);
        } else {
            $this->json(['success' => false, 'message' => '状态更新失败']);
        }
    }

    /**
     * 删除反馈（管理员用）
     */
    public function delete()
    {
        try {
        if (!$this->isAdmin()) {
            $this->json(['success' => false, 'message' => '权限不足']);
            return;
        }

        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => '请求方法错误']);
            return;
        }

        $feedbackId = intval($_POST['id'] ?? 0);
        $success = UserFeedback::delete($feedbackId);
        
        if ($success) {
            $this->json(['success' => true, 'message' => '删除成功']);
        } else {
            $this->json(['success' => false, 'message' => '删除失败']);
        }
    
        } catch (Exception $e) {
            // 记录错误日志
            error_log('Controller Error in FeedbackController::delete: ' . $e->getMessage());
            
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
}