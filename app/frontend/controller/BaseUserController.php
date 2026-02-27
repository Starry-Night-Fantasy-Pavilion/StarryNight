<?php

namespace app\frontend\controller;

use Core\Controller;
use app\services\Database;
use app\models\UserTokenBalance;
use app\models\UserLimit;

/**
 * 前端用户基础控制器
 * 提供统一的布局和认证检查
 */
class BaseUserController extends Controller
{
    protected $viewPath;
    protected $currentPage = 'dashboard';

    public function __construct()
    {
        $this->viewPath = dirname(__DIR__) . '/views';
    }

    /**
     * 检查用户登录状态
     */
    protected function checkAuth()
    {
        if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
            header('Location: /login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
        return $_SESSION['user_id'];
    }

    /**
     * 获取当前用户信息
     */
    protected function getCurrentUser()
    {
        $userId = $this->checkAuth();
        return \app\models\User::find($userId);
    }

    /**
     * 渲染视图（使用用户中心布局，含左侧菜单栏）
     */
    protected function render(string $view, array $data = [])
    {
        $viewFile = $this->viewPath . '/' . $view . '.php';
        if (!file_exists($viewFile)) {
            \app\services\ErrorHandler::handleNotFound('视图文件不存在: ' . $view);
            return;
        }
        
        extract($data);
        ob_start();
        include $viewFile;
        $content = ob_get_clean();
        
        // 使用用户中心后台风格布局（左侧菜单栏）
        $title = $data['title'] ?? '用户中心';
        $currentPage = $data['currentPage'] ?? $this->currentPage;
        $user = $data['user'] ?? $this->getCurrentUser();
        
        // 为头像下拉框准备数据
        $dropdownMembership = $data['membership'] ?? null;
        $dropdownWallet = $data['wallet'] ?? null;
        $dropdownTokenBalance = $data['tokenBalance'] ?? null;
        if ($user && $user['id']) {
            if ($dropdownMembership === null) {
                try {
                    $pdo = Database::pdo();
                    $prefix = Database::prefix();
                    $stmt = $pdo->prepare("SELECT m.*, ml.name as level_name FROM `{$prefix}user_memberships` m LEFT JOIN `{$prefix}membership_levels` ml ON m.level_id = ml.id WHERE m.user_id = :user_id AND m.status = 'active'");
                    $stmt->execute([':user_id' => $user['id']]);
                    $dropdownMembership = $stmt->fetch(\PDO::FETCH_ASSOC);
                } catch (\Throwable $e) { $dropdownMembership = null; }
            }
            if ($dropdownWallet === null) {
                try {
                    $pdo = Database::pdo();
                    $prefix = Database::prefix();
                    $stmt = $pdo->prepare("SELECT balance FROM `{$prefix}user_wallets` WHERE user_id = :user_id");
                    $stmt->execute([':user_id' => $user['id']]);
                    $dropdownWallet = $stmt->fetch(\PDO::FETCH_ASSOC);
                } catch (\Throwable $e) { $dropdownWallet = null; }
            }
            if ($dropdownTokenBalance === null) {
                $dropdownTokenBalance = UserTokenBalance::getByUserId((int)$user['id']);
            }
        }
        $dropdownLimits = [];
        if ($user && $user['id']) {
            try {
                $dropdownLimits = UserLimit::getLimitsByUserId((int)$user['id']);
            } catch (\Throwable $e) {
                $dropdownLimits = ['daily_word_limit' => 10000];
            }
        }
        $dropdownTodayConsumed = 0;
        if ($user && $user['id']) {
            try {
                $pdo = Database::pdo();
                $prefix = Database::prefix();
                $stmt = $pdo->prepare("SELECT COALESCE(SUM(ABS(tokens)), 0) as total FROM `{$prefix}token_consumption_records` WHERE user_id = :user_id AND tokens < 0 AND DATE(created_at) = CURDATE()");
                $stmt->execute([':user_id' => $user['id']]);
                $dropdownTodayConsumed = (int)($stmt->fetchColumn() ?: 0);
            } catch (\Throwable $e) { $dropdownTodayConsumed = 0; }
        }
        
        include $this->viewPath . '/user_center_layout.php';
    }
}
