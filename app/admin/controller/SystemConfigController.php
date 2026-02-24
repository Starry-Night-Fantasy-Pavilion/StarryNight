<?php

namespace app\admin\controller;

use app\models\Setting;
use app\models\AdminRole;
use app\models\AdminLog;
use app\services\Database;

/**
 * 1.7 系统配置与安全审计
 * 基础设置、存储配置、角色权限、操作/异常/登录日志、安全设置
 */
class SystemConfigController extends BaseController
{
    private function render(string $view, array $vars = []): void
    {
        $title = $vars['title'] ?? '系统配置';
        $currentPage = $vars['currentPage'] ?? 'system';
        ob_start();
        extract($vars);
        require __DIR__ . '/../views/system_config/' . $view . '.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layout.php';
    }

    private function tableExists(string $table): bool
    {
        $prefix = Database::prefix();
        $full = $prefix . $table;
        $pdo = Database::pdo();
        $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($full));
        return $stmt->fetch() !== false;
    }

    public function index()
    {
        header('Location: /' . trim((string)get_env('ADMIN_PATH', 'admin'), '/') . '/system/basic-settings');
        exit;
    }

    // ---------- 基础设置 ----------
    public function basicSettings()
    {
        // 仅站点基础信息
        $keys = ['site_name', 'site_logo', 'icp_info', 'contact_info', 'customer_service_config', 'currency_name'];
        $values = Setting::getMany($keys);
        $data = [];
        foreach ($keys as $k) $data[$k] = $values[$k] ?? '';
        if ($data['currency_name'] === '') $data['currency_name'] = '星夜币';
        if ($data['site_name'] === '') $data['site_name'] = (string)get_env('APP_NAME', '星夜阁');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            foreach ($keys as $k) {
                if (array_key_exists($k, $_POST)) Setting::set($k, $_POST[$k]);
            }
            $adminId = $_SESSION['admin_user_id'] ?? null;
            if ($this->tableExists('admin_operation_logs')) {
                AdminLog::operationLog(
                    $adminId ? (int)$adminId : null,
                    'system.basic',
                    'save',
                    json_encode(array_intersect_key($_POST, array_flip($keys)), JSON_UNESCAPED_UNICODE),
                    'success'
                );
            }
            header('Location: /' . trim((string)get_env('ADMIN_PATH', 'admin'), '/') . '/system/basic-settings');
            exit;
        }
        $this->render('basic_settings', [
            'title' => '基础设置',
            'currentPage' => 'system-basic',
            'data' => $data,
        ]);
    }

    // ---------- 注册设置 ----------
    public function registerSettings()
    {
        $keys = ['register_email_enabled', 'register_phone_enabled', 'register_default_method', 'register_code_expire_minutes'];
        $values = Setting::getMany($keys);
        $data = [];
        foreach ($keys as $k) $data[$k] = $values[$k] ?? '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            foreach ($keys as $k) {
                if (!array_key_exists($k, $_POST)) {
                    continue;
                }
                $val = $_POST[$k];
                if ($k === 'register_code_expire_minutes') {
                    $minutes = (int)$val;
                    if ($minutes <= 0) {
                        $minutes = 10;
                    }
                    if ($minutes > 60) {
                        $minutes = 60;
                    }
                    $val = (string)$minutes;
                }
                Setting::set($k, $val);
            }
            $adminId = $_SESSION['admin_user_id'] ?? null;
            if ($this->tableExists('admin_operation_logs')) {
                AdminLog::operationLog(
                    $adminId ? (int)$adminId : null,
                    'system.register',
                    'save',
                    json_encode(array_intersect_key($_POST, array_flip($keys)), JSON_UNESCAPED_UNICODE),
                    'success'
                );
            }
            header('Location: /' . trim((string)get_env('ADMIN_PATH', 'admin'), '/') . '/system/register-settings');
            exit;
        }

        $this->render('register_settings', [
            'title' => '注册设置',
            'currentPage' => 'system-register',
            'data' => $data,
        ]);
    }

    // ---------- 协议设置 ----------
    public function legalSettings()
    {
        $keys = ['user_agreement_txt_path', 'privacy_policy_txt_path'];
        $values = Setting::getMany($keys);
        $data = [];
        foreach ($keys as $k) $data[$k] = $values[$k] ?? '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $publicRoot = realpath(__DIR__ . '/../../../public');
            $uploadDir = $publicRoot . DIRECTORY_SEPARATOR . 'static' . DIRECTORY_SEPARATOR . 'errors' . DIRECTORY_SEPARATOR . 'txt';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }

            $handleTxtUpload = function (string $fileKey, string $settingKey, string $prefix) use ($uploadDir): void {
                if (empty($_FILES[$fileKey]) || !is_array($_FILES[$fileKey])) {
                    return;
                }
                $f = $_FILES[$fileKey];
                if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    return;
                }

                $name = (string)($f['name'] ?? '');
                $tmp = (string)($f['tmp_name'] ?? '');
                if ($name === '' || $tmp === '' || !is_uploaded_file($tmp)) {
                    return;
                }

                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if ($ext !== 'txt') {
                    return;
                }

                // 限制大小：1MB
                $size = (int)($f['size'] ?? 0);
                if ($size <= 0 || $size > 1024 * 1024) {
                    return;
                }

                $filename = $prefix . '_' . date('Ymd_His') . '.txt';
                $dest = $uploadDir . DIRECTORY_SEPARATOR . $filename;
                if (@move_uploaded_file($tmp, $dest)) {
                    // 只保存文件名，路径固定为 /static/errors/txt/
                    Setting::set($settingKey, $filename);
                }
            };

            $handleTxtUpload('user_agreement_file', 'user_agreement_txt_path', 'user_agreement');
            $handleTxtUpload('privacy_policy_file', 'privacy_policy_txt_path', 'privacy_policy');

            $adminId = $_SESSION['admin_user_id'] ?? null;
            if ($this->tableExists('admin_operation_logs')) {
                AdminLog::operationLog(
                    $adminId ? (int)$adminId : null,
                    'system.legal',
                    'save',
                    json_encode(['updated' => true], JSON_UNESCAPED_UNICODE),
                    'success'
                );
            }
            header('Location: /' . trim((string)get_env('ADMIN_PATH', 'admin'), '/') . '/system/legal-settings');
            exit;
        }

        $this->render('legal_settings', [
            'title' => '协议设置',
            'currentPage' => 'system-legal',
            'data' => $data,
        ]);
    }

    // ---------- 存储配置 ----------
    public function storageConfig()
    {
        $keys = ['storage_local_path', 'storage_cleanup_policy'];
        $values = Setting::getMany($keys);
        $data = ['storage_local_path' => $values['storage_local_path'] ?? '', 'storage_cleanup_policy' => $values['storage_cleanup_policy'] ?? ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Setting::set('storage_local_path', $_POST['storage_local_path'] ?? '');
            Setting::set('storage_cleanup_policy', $_POST['storage_cleanup_policy'] ?? '');
            header('Location: /' . trim((string)get_env('ADMIN_PATH', 'admin'), '/') . '/system/storage-config');
            exit;
        }
        $this->render('storage_config', [
            'title' => '存储配置',
            'currentPage' => 'system-storage',
            'data' => $data,
        ]);
    }

    // ---------- 角色管理 ----------
    public function roles()
    {
        if (!$this->tableExists('admin_roles')) {
            $this->render('message', ['title' => '角色管理', 'message' => '请先执行数据库迁移 012。']);
            return;
        }
        $roles = AdminRole::getAll();
        $this->render('roles', [
            'title' => '角色管理',
            'currentPage' => 'system-roles',
            'roles' => $roles,
        ]);
    }

    public function roleEdit($id)
    {
        $role = $id === 'new' ? null : AdminRole::find((int)$id);
        if ($role === null && $id !== 'new') {
            header('Location: /' . trim((string)get_env('ADMIN_PATH', 'admin'), '/') . '/system/roles');
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => $_POST['name'] ?? '',
                'description' => $_POST['description'] ?? '',
                'data_scope' => $_POST['data_scope'] ?? 'all',
                'sort_order' => (int)($_POST['sort_order'] ?? 0),
            ];
            $permKeys = isset($_POST['permissions']) && is_array($_POST['permissions']) ? array_filter($_POST['permissions']) : [];
            if ($id === 'new') {
                $newId = AdminRole::create($data);
                AdminRole::setPermissions($newId, $permKeys);
                header('Location: /' . trim((string)get_env('ADMIN_PATH', 'admin'), '/') . '/system/role/' . $newId);
                exit;
            }
            AdminRole::update((int)$id, $data);
            AdminRole::setPermissions((int)$id, $permKeys);
            header('Location: /' . trim((string)get_env('ADMIN_PATH', 'admin'), '/') . '/system/roles');
            exit;
        }
        $permissions = $role ? AdminRole::getPermissions((int)$id) : [];
        $this->render('role_edit', [
            'title' => $role ? '编辑角色' : '新增角色',
            'currentPage' => 'system-roles',
            'role' => $role,
            'permissions' => $permissions,
            'allPermissionKeys' => $this->getAllPermissionKeys(),
        ]);
    }

    public function roleDelete($id)
    {
        if (AdminRole::delete((int)$id)) {
            header('Location: /' . trim((string)get_env('ADMIN_PATH', 'admin'), '/') . '/system/roles');
            exit;
        }
        $this->render('message', ['title' => '错误', 'message' => '删除失败或该角色为系统内置']);
    }

    private function getAllPermissionKeys(): array
    {
        return [
            'dashboard' => '仪表盘',
            'crm.users' => '用户管理',
            'crm.users.edit' => '用户编辑',
            'crm.users.balance' => '用户余额调整',
            'content-review' => '内容审查',
            'community' => '社区内容',
            'ai.channels' => 'AI渠道',
            'ai.model-prices' => 'AI定价',
            'ai.agents' => 'AI智能体',
            'ai.audits' => 'AI审核',
            'finance.membership-levels' => '会员等级',
            'finance.coin-packages' => '充值套餐',
            'finance.orders' => '充值记录',
            'finance.orders.refund' => '充值退款',
            'finance.coin-spend' => '星夜币消耗',
            'finance.coupons' => '优惠券',
            'finance.activities' => '活动配置',
            'finance.promotion' => '推广链接',
            'finance.messages' => '站内信',
            'finance.templates' => '通知模板',
            'system.basic' => '基础设置',
            'system.storage' => '存储配置',
            'system.roles' => '角色管理',
            'system.operation-logs' => '操作日志',
            'system.login-logs' => '登录日志',
            'system.exception-logs' => '异常日志',
            'system.security' => '安全设置',
            'plugins' => '插件管理',
            'themes' => '主题管理',
        ];
    }

    // ---------- 操作日志 ----------
    public function operationLogs()
    {
        if (!$this->tableExists('admin_operation_logs')) {
            $this->render('message', ['title' => '操作日志', 'message' => '请先执行数据库迁移 012。']);
            return;
        }
        $page = max(1, (int)($_GET['page'] ?? 1));
        $adminId = isset($_GET['admin_id']) ? (int)$_GET['admin_id'] : null;
        $module = isset($_GET['module']) ? trim($_GET['module']) : null;
        $result = isset($_GET['result']) ? trim($_GET['result']) : null;
        $data = AdminLog::getOperationLogs($page, 20, $adminId, $module, $result);
        $this->render('operation_logs', [
            'title' => '操作日志',
            'currentPage' => 'system-logs',
            'list' => $data['list'],
            'page' => $page,
            'hasMore' => $data['hasMore'],
        ]);
    }

    public function loginLogs()
    {
        if (!$this->tableExists('admin_login_logs')) {
            $this->render('message', ['title' => '登录日志', 'message' => '请先执行数据库迁移 012。']);
            return;
        }
        $page = max(1, (int)($_GET['page'] ?? 1));
        $username = isset($_GET['username']) ? trim($_GET['username']) : null;
        $result = isset($_GET['result']) ? trim($_GET['result']) : null;
        $data = AdminLog::getLoginLogs($page, 20, $username, $result);
        $this->render('login_logs', [
            'title' => '登录日志',
            'currentPage' => 'system-logs',
            'list' => $data['list'],
            'page' => $page,
            'hasMore' => $data['hasMore'],
        ]);
    }

    public function exceptionLogs()
    {
        if (!$this->tableExists('admin_exception_logs')) {
            $this->render('message', ['title' => '异常日志', 'message' => '请先执行数据库迁移 012。']);
            return;
        }
        $page = max(1, (int)($_GET['page'] ?? 1));
        $level = isset($_GET['level']) ? trim($_GET['level']) : null;
        $data = AdminLog::getExceptionLogs($page, 20, $level);
        $this->render('exception_logs', [
            'title' => '异常日志',
            'currentPage' => 'system-logs',
            'list' => $data['list'],
            'page' => $page,
            'hasMore' => $data['hasMore'],
        ]);
    }

    // ---------- 安全设置 ----------
    public function securitySettings()
    {
        $keys = ['security_ip_whitelist', 'security_ip_blacklist', 'security_2fa_enabled', 'security_password_policy'];
        $values = Setting::getMany($keys);
        $data = [];
        foreach ($keys as $k) $data[$k] = $values[$k] ?? '';
        if ($data['security_password_policy'] === '') $data['security_password_policy'] = json_encode(['min_length' => 8, 'require_upper' => 1, 'require_lower' => 1, 'require_number' => 1, 'require_special' => 0], JSON_UNESCAPED_UNICODE);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Setting::set('security_ip_whitelist', $_POST['security_ip_whitelist'] ?? '');
            Setting::set('security_ip_blacklist', $_POST['security_ip_blacklist'] ?? '');
            Setting::set('security_2fa_enabled', isset($_POST['security_2fa_enabled']) ? '1' : '0');
            Setting::set('security_password_policy', $_POST['security_password_policy'] ?? '');
            header('Location: /' . trim((string)get_env('ADMIN_PATH', 'admin'), '/') . '/system/security');
            exit;
        }
        $this->render('security_settings', [
            'title' => '安全设置',
            'currentPage' => 'system-security',
            'data' => $data,
        ]);
    }

    // ---------- 星夜创作引擎配置 ----------
    public function starryNightEngineConfig()
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        // 获取所有会员等级
        $membershipLevels = [];
        if ($this->tableExists('membership_levels')) {
            $stmt = $pdo->query("SELECT id, name FROM `{$prefix}membership_levels` ORDER BY sort_order ASC, id ASC");
            $membershipLevels = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        // 获取所有配置
        $permissions = [];
        if ($this->tableExists('starry_night_engine_permissions')) {
            $stmt = $pdo->query("SELECT * FROM `{$prefix}starry_night_engine_permissions` ORDER BY 
                CASE engine_version 
                    WHEN 'basic' THEN 1
                    WHEN 'standard' THEN 2
                    WHEN 'premium' THEN 3
                    WHEN 'enterprise' THEN 4
                    ELSE 5
                END, membership_level_id ASC");
            $permissions = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        // 处理POST请求
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            
            if ($action === 'save') {
                $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
                $engineVersion = $_POST['engine_version'] ?? '';
                $membershipLevelId = !empty($_POST['membership_level_id']) ? (int)$_POST['membership_level_id'] : null;
                $isEnabled = isset($_POST['is_enabled']) ? 1 : 0;
                $description = $_POST['description'] ?? '';
                $customConfig = $_POST['custom_config'] ?? '{}';

                // 验证JSON格式
                $configArray = json_decode($customConfig, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $customConfig = '{}';
                }

                if (empty($engineVersion)) {
                    $this->render('message', ['title' => '错误', 'message' => '引擎版本不能为空']);
                    return;
                }

                try {
                    if ($id > 0) {
                        // 更新
                        $sql = "UPDATE `{$prefix}starry_night_engine_permissions` 
                               SET engine_version = :version, membership_level_id = :level_id, 
                                   is_enabled = :enabled, description = :desc, 
                                   custom_config = :config, updated_at = NOW() 
                               WHERE id = :id";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            ':id' => $id,
                            ':version' => $engineVersion,
                            ':level_id' => $membershipLevelId,
                            ':enabled' => $isEnabled,
                            ':desc' => $description,
                            ':config' => $customConfig
                        ]);
                    } else {
                        // 插入
                        $sql = "INSERT INTO `{$prefix}starry_night_engine_permissions` 
                               (engine_version, membership_level_id, is_enabled, description, custom_config) 
                               VALUES (:version, :level_id, :enabled, :desc, :config)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            ':version' => $engineVersion,
                            ':level_id' => $membershipLevelId,
                            ':enabled' => $isEnabled,
                            ':desc' => $description,
                            ':config' => $customConfig
                        ]);
                    }

                    $adminId = $_SESSION['admin_user_id'] ?? null;
                    if ($this->tableExists('admin_operation_logs')) {
                        AdminLog::operationLog($adminId ? (int)$adminId : null, 'system.starry-night', 'save', json_encode(['version' => $engineVersion, 'level_id' => $membershipLevelId], JSON_UNESCAPED_UNICODE), 'success');
                    }

                    header('Location: /' . trim((string)get_env('ADMIN_PATH', 'admin'), '/') . '/system/starry-night-engine');
                    exit;
                } catch (\Exception $e) {
                    error_log('保存星夜创作引擎配置失败: ' . $e->getMessage());
                    $this->render('message', ['title' => '错误', 'message' => '保存失败: ' . $e->getMessage()]);
                    return;
                }
            } elseif ($action === 'delete') {
                $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
                if ($id > 0) {
                    $sql = "DELETE FROM `{$prefix}starry_night_engine_permissions` WHERE id = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([':id' => $id]);
                    
                    $adminId = $_SESSION['admin_user_id'] ?? null;
                    if ($this->tableExists('admin_operation_logs')) {
                        AdminLog::operationLog($adminId ? (int)$adminId : null, 'system.starry-night', 'delete', json_encode(['id' => $id], JSON_UNESCAPED_UNICODE), 'success');
                    }
                }
                header('Location: /' . trim((string)get_env('ADMIN_PATH', 'admin'), '/') . '/system/starry-night-engine');
                exit;
            }
        }

        $this->render('starry_night_engine_config', [
            'title' => '星夜创作引擎配置',
            'currentPage' => 'system-starry-night',
            'membership_levels' => $membershipLevels,
            'permissions' => $permissions,
        ]);
    }

    public function starryNightEngineConfigEdit($id)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        // 获取所有会员等级
        $membershipLevels = [];
        if ($this->tableExists('membership_levels')) {
            $stmt = $pdo->query("SELECT id, name FROM `{$prefix}membership_levels` ORDER BY sort_order ASC, id ASC");
            $membershipLevels = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        // 获取配置信息
        $permission = null;
        if ($id !== 'new' && $this->tableExists('starry_night_engine_permissions')) {
            $stmt = $pdo->prepare("SELECT * FROM `{$prefix}starry_night_engine_permissions` WHERE id = :id");
            $stmt->execute([':id' => (int)$id]);
            $permission = $stmt->fetch(\PDO::FETCH_ASSOC);
        }

        $this->render('starry_night_engine_config_edit', [
            'title' => $permission ? '编辑配置' : '新增配置',
            'currentPage' => 'system-starry-night',
            'membership_levels' => $membershipLevels,
            'permission' => $permission,
            'id' => $id,
        ]);
    }

    // ---------- 首页设置 ----------
    public function homeSettings()
    {
        // 首页所有可配置项
        $keys = [
            // 英雄区域
            'home_hero_badge',
            'home_hero_title',
            'home_hero_subtitle',
            'home_hero_cta_primary',
            'home_hero_cta_secondary',
            
            // 统计数据
            'home_stat_users',
            'home_stat_novels',
            'home_stat_words',
            
            // SEO设置
            'home_meta_description',
            'home_meta_keywords',
            
            // 页脚联系信息
            'site_contact_email',
            'site_contact_phone',
            'site_contact_hours',
            
            // 社交媒体链接
            'site_social_wechat',
            'site_social_weibo',
            'site_social_qq',
            'site_social_bilibili',
        ];
        $values = Setting::getMany($keys);
        $data = [];
        foreach ($keys as $k) $data[$k] = $values[$k] ?? '';

        // 设置默认值
        if ($data['home_hero_badge'] === '') $data['home_hero_badge'] = 'AI创作革命';
        if ($data['home_hero_title'] === '') $data['home_hero_title'] = 'AI智能创作平台';
        if ($data['home_hero_subtitle'] === '') $data['home_hero_subtitle'] = '专为创作者打造的AI增效工具，辅助10000+作者在各大平台创作优质内容。通过尖端AI技术，让您的创作灵感无限延伸。';
        if ($data['home_hero_cta_primary'] === '') $data['home_hero_cta_primary'] = '立即免费试用';
        if ($data['home_hero_cta_secondary'] === '') $data['home_hero_cta_secondary'] = '观看演示';
        if ($data['home_stat_users'] === '') $data['home_stat_users'] = '10000';
        if ($data['home_stat_novels'] === '') $data['home_stat_novels'] = '50000';
        if ($data['home_stat_words'] === '') $data['home_stat_words'] = '10000000';
        if ($data['site_contact_email'] === '') $data['site_contact_email'] = 'support@starrynight.com';
        if ($data['site_contact_phone'] === '') $data['site_contact_phone'] = '400-888-8888';
        if ($data['site_contact_hours'] === '') $data['site_contact_hours'] = '工作日 9:00-18:00';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            foreach ($keys as $k) {
                if (array_key_exists($k, $_POST)) {
                    Setting::set($k, $_POST[$k]);
                }
            }
            $adminId = $_SESSION['admin_user_id'] ?? null;
            if ($this->tableExists('admin_operation_logs')) {
                AdminLog::operationLog(
                    $adminId ? (int)$adminId : null,
                    'system.home',
                    'save',
                    json_encode(array_intersect_key($_POST, array_flip($keys)), JSON_UNESCAPED_UNICODE),
                    'success'
                );
            }
            header('Location: /' . trim((string)get_env('ADMIN_PATH', 'admin'), '/') . '/system/home-settings');
            exit;
        }
        $this->render('home_settings', [
            'title' => '首页设置',
            'currentPage' => 'system-home',
            'data' => $data,
        ]);
    }
}
