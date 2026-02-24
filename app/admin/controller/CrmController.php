<?php

namespace app\admin\controller;

use app\models\User;
use app\services\Auth;
use app\services\Router;
use Exception;

class CrmController extends BaseController
{
    public function index()
    {
        try {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $searchTerm = $_GET['search'] ?? null;
        $sortBy = $_GET['sort_by'] ?? 'id';
        $sortOrder = $_GET['sort_order'] ?? 'desc';

        // 高级筛选条件
        $filters = [];
        if (!empty($_GET['filter_status'])) {
            $filters['status'] = $_GET['filter_status'];
        }
        if (!empty($_GET['filter_membership_level'])) {
            $filters['membership_level'] = $_GET['filter_membership_level'];
        }
        if (!empty($_GET['filter_created_from'])) {
            $filters['created_from'] = $_GET['filter_created_from'];
        }
        if (!empty($_GET['filter_created_to'])) {
            $filters['created_to'] = $_GET['filter_created_to'];
        }
        if (!empty($_GET['filter_last_login_from'])) {
            $filters['last_login_from'] = $_GET['filter_last_login_from'];
        }
        if (!empty($_GET['filter_last_login_to'])) {
            $filters['last_login_to'] = $_GET['filter_last_login_to'];
        }

        $data = User::getAll($page, 15, $searchTerm, $sortBy, $sortOrder, $filters);

        // 获取会员等级列表用于筛选
        $membershipLevels = [];
        try {
            $membershipLevels = \app\models\MembershipLevel::getAll();
        } catch (\Exception $e) {
            // 忽略错误，继续执行
        }

        $title = '用户管理';
        $currentPage = 'crm-users';

        ob_start();
        require __DIR__ . '/../views/crm/users.php';
        $content = ob_get_clean();

        require __DIR__ . '/../views/layout.php';
    
        } catch (Exception $e) {
            // 记录错误日志
            error_log('Controller Error in CrmController::index: ' . $e->getMessage());
            
            // 返回友好的错误信息
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                return json_encode([
                    'success' => false,
                    'message' => '操作失败，请稍后重试',
                    'error' => app()->isDebug() ? $e->getMessage() : '系统内部错误'
                ]);
            } else {
                // 对于普通请求，显示错误页面
                http_response_code(500);
                echo '<h1>系统错误</h1><p>抱歉，系统遇到了一些问题，请稍后重试。</p>';
                if (app()->isDebug()) {
                    echo '<p>错误详情: ' . htmlspecialchars($e->getMessage()) . '</p>';
                }
                exit;
            }
        }
    }

    public function details($id)
    {
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
        $adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '/');
        
        $user = User::find($id);

        if (!$user) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => '用户不存在']);
                exit;
            }
            header("Location: /{$adminPrefix}/crm/users");
            exit;
        }

        // 获取用户交易记录
        $transactions = [];
        try {
            $pdo = \app\services\Database::pdo();
            $prefix = \app\services\Database::prefix();
            $sql = "SELECT * FROM `{$prefix}coin_transactions` WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 20";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':user_id' => $id]);
            $transactions = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('Error fetching transactions: ' . $e->getMessage());
        }

        // 获取会员历史
        $membershipHistory = [];
        try {
            $pdo = \app\services\Database::pdo();
            $prefix = \app\services\Database::prefix();
            $sql = "SELECT um.*, ml.name as level_name 
                    FROM `{$prefix}user_memberships` um 
                    LEFT JOIN `{$prefix}membership_levels` ml ON um.level_id = ml.id 
                    WHERE um.user_id = :user_id 
                    ORDER BY um.created_at DESC LIMIT 10";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':user_id' => $id]);
            $membershipHistory = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('Error fetching membership history: ' . $e->getMessage());
        }

        // AJAX 请求返回弹窗内容
        if ($isAjax) {
            set_error_handler(function($severity, $message, $file, $line) {
                throw new \ErrorException($message, 0, $severity, $file, $line);
            });
            
            try {
                if (ob_get_level() > 0) {
                    ob_end_clean();
                }
                
                ob_start();
                require __DIR__ . '/../views/crm/user_details.php';
                $content = ob_get_clean();
                
                restore_error_handler();
                
                header('Content-Type: text/html; charset=utf-8');
                echo $content;
                exit;
            } catch (\Throwable $e) {
                if (ob_get_level() > 0) {
                    ob_end_clean();
                }
                
                restore_error_handler();
                
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => '加载用户详情失败: ' . $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => function_exists('app') && app()->isDebug() ? $e->getTraceAsString() : null
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        $title = '用户详情';
        $currentPage = 'crm-users';

        ob_start();
        require __DIR__ . '/../views/crm/user_details.php';
        $content = ob_get_clean();

        require __DIR__ . '/../views/layout.php';
    }

    public function add()
    {
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
        $adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '/');
        
        // 如果是 AJAX GET 请求，直接返回表单
        if ($isAjax && $_SERVER['REQUEST_METHOD'] === 'GET') {
            // 确保 $error 变量已初始化
            $error = null;
            
            // 清除所有输出缓冲
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            // 设置错误处理
            $previousErrorHandler = set_error_handler(function($severity, $message, $file, $line) {
                // 只捕获致命错误
                if ($severity & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR)) {
                    throw new \ErrorException($message, 0, $severity, $file, $line);
                }
                return false; // 继续执行默认错误处理
            });
            
            try {
                ob_start();
                
                // 检查文件是否存在
                $viewFile = __DIR__ . '/../views/crm/user_add.php';
                if (!file_exists($viewFile)) {
                    throw new \Exception("视图文件不存在: {$viewFile}");
                }
                
                // 检查文件是否可读
                if (!is_readable($viewFile)) {
                    throw new \Exception("视图文件不可读: {$viewFile}");
                }
                
                // 包含视图文件
                include $viewFile;
                $content = ob_get_clean();
                
                // 恢复错误处理
                if ($previousErrorHandler !== null) {
                    set_error_handler($previousErrorHandler);
                } else {
                    restore_error_handler();
                }
                
                // 检查内容是否为空
                if (empty($content)) {
                    throw new \Exception("视图文件输出为空");
                }
                
                header('Content-Type: text/html; charset=utf-8');
                echo $content;
                exit;
            } catch (\Throwable $e) {
                // 捕获所有错误和异常
                while (ob_get_level() > 0) {
                    ob_end_clean();
                }
                
                // 恢复错误处理
                if ($previousErrorHandler !== null) {
                    set_error_handler($previousErrorHandler);
                } else {
                    restore_error_handler();
                }
                
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(500);
                
                $errorInfo = [
                    'success' => false,
                    'message' => '加载表单失败: ' . $e->getMessage(),
                    'file' => str_replace('\\', '/', $e->getFile()),
                    'line' => $e->getLine(),
                    'type' => get_class($e),
                ];
                
                // 尝试获取调试信息
                try {
                    if (function_exists('app')) {
                        $app = app();
                        if (method_exists($app, 'isDebug') && $app->isDebug()) {
                            $errorInfo['trace'] = $e->getTraceAsString();
                        }
                    }
                } catch (\Exception $debugError) {
                    // 忽略调试信息获取错误
                }
                
                echo json_encode($errorInfo, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                exit;
            }
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'username' => $_POST['username'] ?? null,
                'email' => $_POST['email'] ?? null,
                'phone' => $_POST['phone'] ?? null,
                'password' => $_POST['password'] ?? null,
                'nickname' => $_POST['nickname'] ?? null,
                'status' => $_POST['status'] ?? 'active',
                'real_name' => $_POST['real_name'] ?? null,
                'gender' => $_POST['gender'] ?? null,
                'birthdate' => $_POST['birthdate'] ?? null,
                'bio' => $_POST['bio'] ?? null,
            ];

            // 验证必填字段
            if (empty($data['username']) || empty($data['password'])) {
                $error = "用户名和密码为必填项";
            } elseif (empty($data['email']) && empty($data['phone'])) {
                $error = "邮箱和手机号至少需要填写一项";
            } else {
                // 检查用户名是否已存在
                $pdo = \app\services\Database::pdo();
                $prefix = \app\services\Database::prefix();
                $stmt = $pdo->prepare("SELECT id FROM `{$prefix}users` WHERE username = :username");
                $stmt->execute([':username' => $data['username']]);
                if ($stmt->fetch()) {
                    $error = "用户名已存在";
                } else {
                    // 检查邮箱是否已存在
                    if (!empty($data['email'])) {
                        $stmt = $pdo->prepare("SELECT id FROM `{$prefix}users` WHERE email = :email");
                        $stmt->execute([':email' => $data['email']]);
                        if ($stmt->fetch()) {
                            $error = "邮箱已被使用";
                        }
                    }
                    
                    // 检查手机号是否已存在
                    if (!empty($data['phone']) && empty($error)) {
                        $stmt = $pdo->prepare("SELECT id FROM `{$prefix}users` WHERE phone = :phone");
                        $stmt->execute([':phone' => $data['phone']]);
                        if ($stmt->fetch()) {
                            $error = "手机号已被使用";
                        }
                    }

                    if (empty($error)) {
                        // 创建用户
                        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
                        $sql = "INSERT INTO `{$prefix}users` (username, email, phone, password, nickname, status, real_name, gender, birthdate, bio, created_at)
                                VALUES (:username, :email, :phone, :password, :nickname, :status, :real_name, :gender, :birthdate, :bio, NOW())";
                        $stmt = $pdo->prepare($sql);
                        $result = $stmt->execute([
                            ':username' => $data['username'],
                            ':email' => $data['email'],
                            ':phone' => $data['phone'],
                            ':password' => $hashedPassword,
                            ':nickname' => $data['nickname'] ?? $data['username'],
                            ':status' => $data['status'],
                            ':real_name' => $data['real_name'],
                            ':gender' => $data['gender'],
                            ':birthdate' => $data['birthdate'],
                            ':bio' => $data['bio'],
                        ]);

                        if ($result) {
                            $newUserId = $pdo->lastInsertId();
                            if ($isAjax) {
                                header('Content-Type: application/json');
                                echo json_encode([
                                    'success' => true,
                                    'message' => '用户创建成功',
                                    'redirect' => "/{$adminPrefix}/crm/users"
                                ]);
                                exit;
                            }
                            header("Location: /{$adminPrefix}/crm/users");
                            exit;
                        } else {
                            $error = "创建用户失败，请重试";
                        }
                    }
                }
            }
            
            // POST 请求有错误，如果是 AJAX 返回错误
            if ($isAjax && isset($error)) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => $error
                ]);
                exit;
            }
        }

        // AJAX 请求返回弹窗内容
        if ($isAjax) {
            // 设置错误处理
            set_error_handler(function($severity, $message, $file, $line) {
                throw new \ErrorException($message, 0, $severity, $file, $line);
            });
            
            try {
                // 确保 $error 变量已初始化
                if (!isset($error)) {
                    $error = null;
                }
                
                // 清除之前的输出
                if (ob_get_level() > 0) {
                    ob_end_clean();
                }
                
                ob_start();
                
                // 检查文件是否存在
                $viewFile = __DIR__ . '/../views/crm/user_add.php';
                if (!file_exists($viewFile)) {
                    throw new \Exception("视图文件不存在: {$viewFile}");
                }
                
                require $viewFile;
                $content = ob_get_clean();
                
                // 恢复错误处理
                restore_error_handler();
                
                header('Content-Type: text/html; charset=utf-8');
                echo $content;
                exit;
            } catch (\Throwable $e) {
                // 捕获所有错误和异常
                if (ob_get_level() > 0) {
                    ob_end_clean();
                }
                
                // 恢复错误处理
                restore_error_handler();
                
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(500);
                
                $errorInfo = [
                    'success' => false,
                    'message' => '加载表单失败: ' . $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ];
                
                if (function_exists('app') && app()->isDebug()) {
                    $errorInfo['trace'] = $e->getTraceAsString();
                }
                
                echo json_encode($errorInfo, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                exit;
            }
        }

        $title = '添加新用户';
        $currentPage = 'crm-users';

        ob_start();
        require __DIR__ . '/../views/crm/user_add.php';
        $content = ob_get_clean();

        require __DIR__ . '/../views/layout.php';
    }

    public function edit($id)
    {
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
        $adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '/');
        
        $user = User::find($id);

        if (!$user) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => '用户不存在']);
                exit;
            }
            header("Location: /{$adminPrefix}/crm/users");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'email' => $_POST['email'] ?? null,
                'nickname' => $_POST['nickname'] ?? null,
                'password' => !empty($_POST['password']) ? $_POST['password'] : null,
                'status' => $_POST['status'] ?? 'active',
                'real_name' => $_POST['real_name'] ?? null,
                'gender' => $_POST['gender'] ?? null,
                'birthdate' => $_POST['birthdate'] ?? null,
                'bio' => $_POST['bio'] ?? null,
            ];

            if (User::update($id, $data)) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => '用户更新成功',
                        'redirect' => "/{$adminPrefix}/crm/users"
                    ]);
                    exit;
                }
                header("Location: /{$adminPrefix}/crm/users");
                exit;
            } else {
                $error = "更新失败，请重试";
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $error]);
                    exit;
                }
            }
        }

        // AJAX 请求返回弹窗内容
        if ($isAjax) {
            set_error_handler(function($severity, $message, $file, $line) {
                throw new \ErrorException($message, 0, $severity, $file, $line);
            });
            
            try {
                // 确保 $error 变量已初始化
                if (!isset($error)) {
                    $error = null;
                }
                
                if (ob_get_level() > 0) {
                    ob_end_clean();
                }
                
                ob_start();
                require __DIR__ . '/../views/crm/user_edit.php';
                $content = ob_get_clean();
                
                restore_error_handler();
                
                header('Content-Type: text/html; charset=utf-8');
                echo $content;
                exit;
            } catch (\Throwable $e) {
                if (ob_get_level() > 0) {
                    ob_end_clean();
                }
                
                restore_error_handler();
                
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => '加载编辑表单失败: ' . $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => function_exists('app') && app()->isDebug() ? $e->getTraceAsString() : null
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        $title = '编辑用户';
        $currentPage = 'crm-users';

        ob_start();
        require __DIR__ . '/../views/crm/user_edit.php';
        $content = ob_get_clean();

        require __DIR__ . '/../views/layout.php';
    }

    public function toggleStatus($id)
    {
        if (User::toggleStatus($id)) {
            header('Location: ' . $_SERVER['HTTP_REFERER']);
        } else {
            $this->error('操作失败，请重试', 500);
        }
        exit;
    }

    public function adjustBalance($id)
    {
        $user = User::find($id);

        if (!$user) {
            header('Location: /admin/crm/users');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $amount = (float)($_POST['amount'] ?? 0);
            $description = $_POST['description'] ?? 'System Adjustment';

            if ($amount != 0) {
                if (User::adjustBalance($id, $amount, $description)) {
                    header("Location: /admin/crm/user/{$id}");
                    exit;
                } else {
                    $error = "调整失败，请重试";
                }
            }
        }

        $title = '调整余额';
        $currentPage = 'crm-users';

        ob_start();
        require __DIR__ . '/../views/crm/user_balance.php';
        $content = ob_get_clean();

        require __DIR__ . '/../views/layout.php';
    }

    public function batchAction()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/crm/users');
            exit;
        }

        $action = $_POST['action'] ?? '';
        $userIds = $_POST['user_ids'] ?? [];

        if (empty($userIds) || !is_array($userIds)) {
            header('Location: /admin/crm/users');
            exit;
        }

        $userIds = array_map('intval', $userIds);
        $userIds = array_filter($userIds);

        if (empty($userIds)) {
            header('Location: /admin/crm/users');
            exit;
        }

        $success = false;
        $message = '';

        switch ($action) {
            case 'enable':
                $count = User::batchUpdateStatus($userIds, 'active');
                $success = $count > 0;
                $message = "成功启用 {$count} 个用户";
                break;
            case 'disable':
                $count = User::batchUpdateStatus($userIds, 'disabled');
                $success = $count > 0;
                $message = "成功禁用 {$count} 个用户";
                break;
            case 'freeze':
                $count = User::batchUpdateStatus($userIds, 'frozen');
                $success = $count > 0;
                $message = "成功冻结 {$count} 个用户";
                break;
            case 'delete':
                $count = User::batchUpdateStatus($userIds, 'deleted');
                $success = $count > 0;
                $message = "成功删除 {$count} 个用户";
                break;
            default:
                $message = '无效的操作';
                break;
        }

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => $success,
                'message' => $message
            ]);
        } else {
            header('Location: /admin/crm/users?message=' . urlencode($message));
        }
        exit;
    }

    public function freeze($id)
    {
        if (User::freeze($id)) {
            header('Location: ' . $_SERVER['HTTP_REFERER'] ?? '/admin/crm/users');
        } else {
            $this->error('操作失败，请重试', 500);
        }
        exit;
    }

    public function unfreeze($id)
    {
        if (User::unfreeze($id)) {
            header('Location: ' . $_SERVER['HTTP_REFERER'] ?? '/admin/crm/users');
        } else {
            $this->error('操作失败，请重试', 500);
        }
        exit;
    }

    public function delete($id)
    {
        if (User::delete($id)) {
            header('Location: /admin/crm/users');
        } else {
            $this->error('删除失败，请重试', 500);
        }
        exit;
    }

    public function restore($id)
    {
        if (User::restore($id)) {
            header('Location: ' . $_SERVER['HTTP_REFERER'] ?? '/admin/crm/users');
        } else {
            $this->error('恢复失败，请重试', 500);
        }
        exit;
    }
}
