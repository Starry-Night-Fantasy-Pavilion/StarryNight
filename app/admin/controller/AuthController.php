<?php

namespace app\admin\controller;

use app\services\Database;
use app\models\AdminLog;

class AuthController
{
    public function loginForm()
    {
        $adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '/');
        $action = '/' . $adminPrefix . '/login';
        // Map error codes to user-friendly messages
        $messages = [
            '1' => '用户名或密码错误', // Invalid credentials
            '4' => '登录已过期，请重新登录', // Session expired
        ];
        $error = isset($_GET['error']) && isset($messages[$_GET['error']]) ? $messages[$_GET['error']] : '';

        require __DIR__ . '/../views/login.php';
    }

    public function login()
    {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        $adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '/');

        if (empty($username) || empty($password)) {
            header('Location: /' . $adminPrefix . '/login?error=1');
            exit;
        }

        try {
            $pdo = Database::pdo();
            $table = Database::prefix() . 'admin_admins';

            // 支持用户名或邮箱登录
            $stmt = $pdo->prepare("SELECT * FROM `{$table}` WHERE `username` = :username OR `email` = :username LIMIT 1");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                AdminLog::loginLog((int)$user['id'], $username, 'success');
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_user_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_last_activity'] = time();
                
                header('Location: /' . $adminPrefix);
                exit;
            }
        } catch (\PDOException $e) {
            error_log('Login database error: ' . $e->getMessage());
        }

        AdminLog::loginLog(null, $username, 'fail');
        header('Location: /' . $adminPrefix . '/login?error=1');
        exit;
    }

    public function logout()
    {
        unset($_SESSION['admin_logged_in']);
        $adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '/');
        header('Location: /' . $adminPrefix . '/login');
        exit;
    }
}
