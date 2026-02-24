<?php

namespace app\frontend\controller;

use app\models\User;
use app\models\MembershipLevel;
use app\models\UserConsistencyConfig;
use app\models\UserTokenBalance;
use app\models\UserLimit;
use app\services\Database;

/**
 * 用户中心控制器
 */
class UserCenterController
{
    private $viewPath;

    public function __construct()
    {
        $this->viewPath = dirname(__DIR__) . '/views';
    }

    /**
     * 检查用户登录状态
     */
    private function checkAuth()
    {
        if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
            header('Location: /login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
        return $_SESSION['user_id'];
    }

    /**
     * 渲染视图
     */
    private function render(string $view, array $data = [])
    {
        $viewFile = $this->viewPath . '/user_center/' . $view . '.php';
        if (!file_exists($viewFile)) {
            \app\services\ErrorHandler::handleNotFound('视图文件不存在');
            return;
        }
        extract($data);
        ob_start();
        include $viewFile;
        $content = ob_get_clean();
        
        // 使用用户中心后台风格布局
        $title = $data['title'] ?? '用户中心';
        $currentPage = $data['currentPage'] ?? 'dashboard';
        $user = $data['user'] ?? null;
        
        // 为头像下拉框准备数据（当用户已登录时）
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

    /**
     * 用户中心首页
     */
    public function index()
    {
        try {
            $userId = $this->checkAuth();
            $user = User::find($userId);
            
            if (!$user) {
                header('Location: /login');
                exit;
            }

            // 获取用户的会员信息
            $membership = null;
            $wallet = null;
            $stats = [];
            
            try {
                $pdo = Database::pdo();
                $prefix = Database::prefix();
                
                // 获取会员信息
                $membershipSql = "SELECT m.*, ml.name as level_name, ml.description as level_description 
                                 FROM `{$prefix}user_memberships` m 
                                 LEFT JOIN `{$prefix}membership_levels` ml ON m.level_id = ml.id 
                                 WHERE m.user_id = :user_id AND m.status = 'active'";
                $stmt = $pdo->prepare($membershipSql);
                $stmt->execute([':user_id' => $userId]);
                $membership = $stmt->fetch(\PDO::FETCH_ASSOC);

                // 获取用户钱包信息
                $walletSql = "SELECT balance FROM `{$prefix}user_wallets` WHERE user_id = :user_id";
                $walletStmt = $pdo->prepare($walletSql);
                $walletStmt->execute([':user_id' => $userId]);
                $wallet = $walletStmt->fetch(\PDO::FETCH_ASSOC);
                
                // 获取用户创作统计
                $stats = $this->getUserStats($userId, $pdo, $prefix);
            } catch (\Exception $e) {
                // 如果数据库表不存在，使用默认值
                error_log('用户中心获取会员/钱包信息失败: ' . $e->getMessage());
            }

            $this->render('index', [
                'title' => '用户中心',
                'currentPage' => 'dashboard',
                'user' => $user,
                'membership' => $membership,
                'wallet' => $wallet,
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            error_log('用户中心页面错误: ' . $e->getMessage());
            \app\services\ErrorHandler::handleServerError($e);
        }
    }

    /**
     * 星夜创作引擎配置页面
     */
    public function starryNightConfig()
    {
        $userId = $this->checkAuth();
        $user = User::find($userId);
        
        if (!$user) {
            header('Location: /login');
            exit;
        }

        // 获取用户的会员等级
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $membershipSql = "SELECT m.level_id, ml.name as level_name 
                         FROM `{$prefix}user_memberships` m 
                         LEFT JOIN `{$prefix}membership_levels` ml ON m.level_id = ml.id 
                         WHERE m.user_id = :user_id AND m.status = 'active'";
        $stmt = $pdo->prepare($membershipSql);
        $stmt->execute([':user_id' => $userId]);
        $membership = $stmt->fetch(\PDO::FETCH_ASSOC);
        $membershipLevelId = $membership ? $membership['level_id'] : null;

        // 获取用户可用的引擎版本（根据后台配置）
        require_once __DIR__ . '/../../services/StarryNightPermissionService.php';
        $permissionService = new \app\services\StarryNightPermissionService();
        $availableVersions = $permissionService->getAvailableVersions($userId, $membershipLevelId);

        // 获取用户自定义配置
        $userConfigSql = "SELECT * FROM `{$prefix}user_starry_night_configs` WHERE user_id = :user_id";
        $userConfigStmt = $pdo->prepare($userConfigSql);
        $userConfigStmt->execute([':user_id' => $userId]);
        $userConfigs = [];
        while ($row = $userConfigStmt->fetch(\PDO::FETCH_ASSOC)) {
            $userConfigs[$row['engine_version']] = $row;
        }

        $this->render('starry_night_config', [
            'title' => '星夜创作引擎配置',
            'currentPage' => 'starry_night_config',
            'user' => $user,
            'membership' => $membership,
            'available_versions' => $availableVersions,
            'user_configs' => $userConfigs
        ]);
    }

    /**
     * 个人中心页面
     */
    public function profile()
    {
        $userId = $this->checkAuth();
        $user = User::find($userId);
        
        if (!$user) {
            header('Location: /login');
            exit;
        }

        // 处理表单提交
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'email' => $_POST['email'] ?? null,
                'nickname' => $_POST['nickname'] ?? null,
                'password' => !empty($_POST['password']) ? $_POST['password'] : null,
                'real_name' => $_POST['real_name'] ?? null,
                'gender' => $_POST['gender'] ?? null,
                'birthdate' => $_POST['birthdate'] ?? null,
                'bio' => $_POST['bio'] ?? null,
            ];

            // 处理头像上传
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../../public/uploads/avatars/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileExt = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                $fileName = 'avatar_' . $userId . '_' . time() . '.' . $fileExt;
                $filePath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $filePath)) {
                    $data['avatar'] = '/uploads/avatars/' . $fileName;
                }
            }

            if (User::update($userId, $data)) {
                header('Location: /user_center/profile?success=1');
                exit;
            } else {
                $error = "更新失败，请重试";
            }
        }

        $this->render('profile', [
            'title' => '个人中心',
            'currentPage' => 'profile',
            'user' => $user,
            'error' => $error ?? null,
            'success' => isset($_GET['success'])
        ]);
    }

    /**
     * 保存用户自定义配置
     */
    public function saveStarryNightConfig()
    {
        $userId = $this->checkAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => '不支持的请求方法']);
            exit;
        }

        $engineVersion = $_POST['engine_version'] ?? '';
        $customConfig = $_POST['custom_config'] ?? '{}';
        $isEnabled = isset($_POST['is_enabled']) ? (int)$_POST['is_enabled'] : 1;

        if (empty($engineVersion)) {
            echo json_encode(['success' => false, 'error' => '引擎版本不能为空']);
            exit;
        }

        // 验证JSON格式
        $configArray = json_decode($customConfig, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['success' => false, 'error' => '配置格式错误']);
            exit;
        }

        $pdo = Database::pdo();
        $prefix = Database::prefix();

        try {
            // 检查是否已存在
            $checkSql = "SELECT id FROM `{$prefix}user_starry_night_configs` WHERE user_id = :user_id AND engine_version = :version";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([':user_id' => $userId, ':version' => $engineVersion]);
            $exists = $checkStmt->fetch(\PDO::FETCH_ASSOC);

            if ($exists) {
                // 更新
                $updateSql = "UPDATE `{$prefix}user_starry_night_configs` 
                             SET custom_config = :config, is_enabled = :enabled, updated_at = NOW() 
                             WHERE id = :id";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([
                    ':id' => $exists['id'],
                    ':config' => $customConfig,
                    ':enabled' => $isEnabled
                ]);
            } else {
                // 插入
                $insertSql = "INSERT INTO `{$prefix}user_starry_night_configs` 
                             (user_id, engine_version, custom_config, is_enabled) 
                             VALUES (:user_id, :version, :config, :enabled)";
                $insertStmt = $pdo->prepare($insertSql);
                $insertStmt->execute([
                    ':user_id' => $userId,
                    ':version' => $engineVersion,
                    ':config' => $customConfig,
                    ':enabled' => $isEnabled
                ]);
            }

            echo json_encode(['success' => true, 'message' => '配置已保存']);
        } catch (\Exception $e) {
            error_log('保存星夜创作引擎配置失败: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => '保存失败，请重试']);
        }
    }

    /**
     * 一致性检查配置页面
     */
    public function consistencyConfig()
    {
        $userId = $this->checkAuth();
        $user = User::find($userId);
        
        if (!$user) {
            header('Location: /login');
            exit;
        }

        $config = UserConsistencyConfig::getOrCreateByUserId($userId);
        $dbModeOptions = UserConsistencyConfig::getDbModeOptions();
        $checkFrequencyOptions = UserConsistencyConfig::getCheckFrequencyOptions();
        $checkScopeOptions = UserConsistencyConfig::getCheckScopeOptions();
        $embeddingModelOptions = UserConsistencyConfig::getEmbeddingModelOptions();

        $this->render('consistency_config', [
            'title' => '一致性检查配置 - 用户中心',
            'currentPage' => 'consistency_config',
            'user' => $user,
            'config' => $config,
            'dbModeOptions' => $dbModeOptions,
            'checkFrequencyOptions' => $checkFrequencyOptions,
            'checkScopeOptions' => $checkScopeOptions,
            'embeddingModelOptions' => $embeddingModelOptions,
        ]);
    }

    /**
     * 保存一致性检查配置
     */
    public function saveConsistencyConfig()
    {
        $userId = $this->checkAuth();

        $data = [
            'is_enabled' => isset($_POST['is_enabled']) ? 1 : 0,
            'db_mode' => $_POST['db_mode'] ?? 'single',
            'embedding_model' => $_POST['embedding_model'] ?? 'openai',
            'custom_embedding_api_key' => $_POST['custom_embedding_api_key'] ?? null,
            'custom_embedding_base_url' => $_POST['custom_embedding_base_url'] ?? null,
            'custom_embedding_model_name' => $_POST['custom_embedding_model_name'] ?? null,
            'check_frequency' => $_POST['check_frequency'] ?? 'realtime',
            'check_scope' => $_POST['check_scope'] ?? 'chapter',
            'sensitivity' => floatval($_POST['sensitivity'] ?? 0.70),
        ];

        $validation = UserConsistencyConfig::validateConfig($data);
        if (!$validation['valid']) {
            echo json_encode(['success' => false, 'errors' => $validation['errors']]);
            exit;
        }

        $result = UserConsistencyConfig::updateByUserId($userId, $data);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => '配置已保存']);
        } else {
            echo json_encode(['success' => false, 'message' => '保存失败，请重试']);
        }
    }
    
    /**
     * 获取用户创作统计数据
     */
    private function getUserStats(int $userId, \PDO $pdo, string $prefix): array
    {
        $stats = [
            'novels' => 0,
            'novel_words' => 0,
            'music' => 0,
            'anime' => 0,
            'templates' => 0,
            'agents' => 0,
        ];
        
        try {
            // 获取小说统计
            $booksTable = $prefix . 'books';
            if ($this->tableExists($pdo, $booksTable)) {
                $stmt = $pdo->prepare("SELECT COUNT(*) as count, SUM(COALESCE(word_count, 0)) as words FROM `{$booksTable}` WHERE user_id = :user_id");
                $stmt->execute([':user_id' => $userId]);
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                $stats['novels'] = (int)($result['count'] ?? 0);
                $stats['novel_words'] = (int)($result['words'] ?? 0);
            }
            
            // 获取音乐项目统计
            $musicTable = $prefix . 'music_tracks';
            if ($this->tableExists($pdo, $musicTable)) {
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM `{$musicTable}` WHERE user_id = :user_id");
                $stmt->execute([':user_id' => $userId]);
                $stats['music'] = (int)($stmt->fetchColumn() ?: 0);
            }
            
            // 获取动画项目统计
            $animeTable = $prefix . 'anime_projects';
            if ($this->tableExists($pdo, $animeTable)) {
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM `{$animeTable}` WHERE user_id = :user_id");
                $stmt->execute([':user_id' => $userId]);
                $stats['anime'] = (int)($stmt->fetchColumn() ?: 0);
            }
            
            // 获取模板统计
            $templatesTable = $prefix . 'templates';
            if ($this->tableExists($pdo, $templatesTable)) {
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM `{$templatesTable}` WHERE user_id = :user_id");
                $stmt->execute([':user_id' => $userId]);
                $stats['templates'] = (int)($stmt->fetchColumn() ?: 0);
            }
            
            // 获取智能体统计
            $agentsTable = $prefix . 'agents';
            if ($this->tableExists($pdo, $agentsTable)) {
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM `{$agentsTable}` WHERE user_id = :user_id");
                $stmt->execute([':user_id' => $userId]);
                $stats['agents'] = (int)($stmt->fetchColumn() ?: 0);
            }
        } catch (\Exception $e) {
            error_log('获取用户统计数据失败: ' . $e->getMessage());
        }
        
        return $stats;
    }
    
    /**
     * 检查表是否存在
     */
    private function tableExists(\PDO $pdo, string $tableName): bool
    {
        try {
            $stmt = $pdo->prepare("SHOW TABLES LIKE :table");
            $stmt->execute([':table' => $tableName]);
            return $stmt->rowCount() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
}
