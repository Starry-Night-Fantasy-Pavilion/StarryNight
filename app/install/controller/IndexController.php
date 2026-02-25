<?php
namespace app\install\controller;

class IndexController
{
    public function __construct()
    {
        // 定义应用根目录的绝对路径
        if (!defined('APP_PATH')) {
            define('APP_PATH', __DIR__ . '/../../');
        }
        // 开启 session
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function index()
    {
        $step = isset($_GET['step']) ? (int)$_GET['step'] : 0;

        // 如果已安装，并且不是访问最终的完成页面，则重定向到首页
        if (file_exists(APP_PATH . 'install/install.lock') && $step !== 8) {
            header('Location: /');
            exit;
        }

        switch ($step) {
            case 1:
                $this->_step1();
                break;
            case 2:
                $this->_step2();
                break;
            case 3:
                $this->_step3();
                break;
            case 4:
                $this->_step4();
                break;
            case 5:
                $this->_step5();
                break;
            case 6:
                $this->_step6();
                break;
            case 7:
                $this->_step7();
                break;
            case 8:
                $this->_step8();
                break;
            default:
                $this->_welcome();
                break;
        }
    }

    /**
     * 欢迎页面
     */
    private function _welcome()
    {
        require_once APP_PATH . 'install/views/index.php';
    }

    /**
     * 步骤1: 环境检查
     */
    private function _step1()
    {
        $data = [];
        $data['env'] = $this->_checkEnv();
        $data['dir'] = $this->_checkDir();
        $data['func'] = $this->_checkFunc();

        // 检查所有项目是否都通过
        $all_passed = true;
        foreach (array_merge($data['env'], $data['dir'], $data['func']) as $item) {
            if (!$item['pass']) {
                $all_passed = false;
                break;
            }
        }
        $data['all_passed'] = $all_passed;

        require_once APP_PATH . 'install/views/step1.php';
    }

    /**
     * 步骤2: 管理员设置
     */
    private function _step2()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 来自步骤1或重新提交
            $admin_info = [
                'username' => htmlspecialchars($_POST['admin']['username'] ?? ''),
                'password' => $_POST['admin']['password'] ?? '',
                'password_confirm' => $_POST['admin']['password_confirm'] ?? '',
                'email' => htmlspecialchars($_POST['admin']['email'] ?? ''),
            ];
            $site_name_raw = trim($_POST['site']['name'] ?? '');
            $site_name = htmlspecialchars($site_name_raw);
            $admin_path = htmlspecialchars($_POST['admin_path'] ?? 'admin');

            // 存入草稿，以便回填
            $_SESSION['install_admin_draft'] = [
                'site_name' => $site_name_raw,
                'username' => $_POST['admin']['username'] ?? '',
                'email' => $_POST['admin']['email'] ?? '',
                'admin_path' => $admin_path,
            ];

            // 验证管理员信息
            if ($site_name_raw === '') {
                $_SESSION['install_error'] = '站点名称不能为空。';
                header('Location: ?step=2');
                exit;
            }
            if (empty($admin_info['username']) || empty($admin_info['password']) || empty($admin_info['password_confirm']) || empty($admin_info['email'])) {
                $_SESSION['install_error'] = '管理员信息不能为空。';
                header('Location: ?step=2');
                exit;
            }
            if (!filter_var($_POST['admin']['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
                $_SESSION['install_error'] = '管理员邮箱格式不正确。';
                header('Location: ?step=2');
                exit;
            }
            if ($admin_info['password'] !== $admin_info['password_confirm']) {
                $_SESSION['install_error'] = '两次输入的密码不一致。';
                header('Location: ?step=2');
                exit;
            }

            // 存入 session
            $_SESSION['install_admin_info'] = $admin_info;
            $_SESSION['install_site_name'] = $site_name;
            $_SESSION['install_config']['admin_path'] = $admin_path;
             
            header('Location: ?step=3');
            exit;
        }

        require_once APP_PATH . 'install/views/step2.php';
    }

    /**
     * 步骤3: RabbitMQ 配置
     */
    private function _step3()
    {
        if (empty($_SESSION['install_admin_info'])) {
            header('Location: ?step=2');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 处理 RabbitMQ 配置
            $rabbitmq_enabled = isset($_POST['rabbitmq']['enabled']) && $_POST['rabbitmq']['enabled'] == '1';
            $rabbitmq_config = [
                'enabled' => $rabbitmq_enabled ? '1' : '0',
                'host' => htmlspecialchars($_POST['rabbitmq']['host'] ?? '127.0.0.1'),
                'port' => htmlspecialchars($_POST['rabbitmq']['port'] ?? '5672'),
                'user' => htmlspecialchars($_POST['rabbitmq']['user'] ?? 'guest'),
                'pass' => $_POST['rabbitmq']['pass'] ?? 'guest',
            ];

            if ($rabbitmq_enabled) {
                if (empty($rabbitmq_config['host']) || empty($rabbitmq_config['port']) || empty($rabbitmq_config['user'])) {
                    $_SESSION['install_error'] = '启用 RabbitMQ 时，请填写所有必填的连接配置。';
                    header('Location: ?step=3');
                    exit;
                }

                // 尝试简单的 Socket 连接测试 RabbitMQ 端口
                $connection = @fsockopen($rabbitmq_config['host'], $rabbitmq_config['port'], $errno, $errstr, 2);
                if (!$connection) {
                    $_SESSION['install_error'] = "无法连接到 RabbitMQ 服务器 {$rabbitmq_config['host']}:{$rabbitmq_config['port']} ($errstr)";
                    header('Location: ?step=3');
                    exit;
                }
                fclose($connection);
            }

            $_SESSION['install_config']['rabbitmq'] = $rabbitmq_config;
             
            header('Location: ?step=4');
            exit;
        }

        require_once APP_PATH . 'install/views/step3.php';
    }

    /**
     * 步骤4: Redis 配置
     */
    private function _step4()
    {
        if (empty($_SESSION['install_config']['rabbitmq'])) {
            header('Location: ?step=3');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $redis_config = [
                'host' => htmlspecialchars($_POST['redis']['host'] ?? '127.0.0.1'),
                'port' => htmlspecialchars($_POST['redis']['port'] ?? '6379'),
                'pass' => $_POST['redis']['pass'] ?? '',
            ];

            if (empty($redis_config['host']) || empty($redis_config['port'])) {
                $_SESSION['install_error'] = '请填写所有必填的 Redis 配置。';
                header('Location: ?step=4');
                exit;
            }

            // 尝试连接 Redis
            if (extension_loaded('redis')) {
                try {
                    $redisClass = '\Redis';
                    $redis = new $redisClass();
                    if (!$redis->connect($redis_config['host'], (int)$redis_config['port'], 2.0)) {
                        throw new \Exception("无法连接到 Redis 服务器");
                    }
                    if (!empty($redis_config['pass'])) {
                        if (!$redis->auth($redis_config['pass'])) {
                            throw new \Exception("Redis 密码验证失败");
                        }
                    }
                    $redis->ping();
                    $redis->close();
                } catch (\Exception $e) {
                    $_SESSION['install_error'] = 'Redis 连接验证失败: ' . $e->getMessage();
                    header('Location: ?step=4');
                    exit;
                }
            }

            $_SESSION['install_config']['redis'] = $redis_config;
             
            header('Location: ?step=5');
            exit;
        }

        require_once APP_PATH . 'install/views/step4.php';
    }

    /**
     * 步骤5: 存储配置
     */
    private function _step5()
    {
        if (empty($_SESSION['install_config']['redis'])) {
            header('Location: ?step=4');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 处理存储配置
            $storage_type = $_POST['storage_type'] ?? 'local';
            $storage_config = [
                'storage_type' => $storage_type,
                'base_path' => htmlspecialchars($_POST['base_path'] ?? '/data'),
                'url_prefix' => htmlspecialchars($_POST['url_prefix'] ?? '/data'),
                'compression_enabled' => isset($_POST['compression_enabled']) && $_POST['compression_enabled'] == '1',
                'max_compression_level' => intval($_POST['max_compression_level'] ?? 6),
            ];

            // 如果选择OSS存储，验证相关配置
            if ($storage_type === 'oss') {
                $oss_config = [
                    'endpoint' => htmlspecialchars($_POST['oss']['endpoint'] ?? ''),
                    'access_key' => htmlspecialchars($_POST['oss']['access_key'] ?? ''),
                    'secret_key' => htmlspecialchars($_POST['oss']['secret_key'] ?? ''),
                    'bucket_name' => htmlspecialchars($_POST['oss']['bucket_name'] ?? ''),
                ];

                if (empty($oss_config['endpoint']) || empty($oss_config['access_key']) ||
                    empty($oss_config['secret_key']) || empty($oss_config['bucket_name'])) {
                    $_SESSION['install_error'] = '启用 OSS 存储时，请填写所有必填的 OSS 配置。';
                    header('Location: ?step=5');
                    exit;
                }

                $storage_config['oss'] = $oss_config;
            }

            // 验证基础路径
            if (empty($storage_config['base_path'])) {
                $_SESSION['install_error'] = '存储基础路径不能为空。';
                header('Location: ?step=5');
                exit;
            }

            $_SESSION['install_config']['storage'] = $storage_config;
             
            header('Location: ?step=6');
            exit;
        }

        require_once APP_PATH . 'install/views/step5.php';
    }

    /**
     * 步骤6: 数据库配置
     */
    private function _step6()
    {
        if (empty($_SESSION['install_config']['storage'])) {
            header('Location: ?step=5');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $db_config = [
                'host' => htmlspecialchars($_POST['db']['host'] ?? '127.0.0.1'),
                'port' => htmlspecialchars($_POST['db']['port'] ?? '3306'),
                'name' => htmlspecialchars($_POST['db']['name'] ?? ''),
                'user' => htmlspecialchars($_POST['db']['user'] ?? ''),
                'pass' => $_POST['db']['pass'] ?? '',
                'prefix' => htmlspecialchars($_POST['db']['prefix'] ?? 'sn_'),
            ];

            if (empty($db_config['host']) || empty($db_config['port']) || empty($db_config['name']) || empty($db_config['user'])) {
                $_SESSION['install_error'] = '请填写所有必填的数据库配置。';
                header('Location: ?step=6');
                exit;
            }

            // 尝试连接数据库以验证配置
            try {
                $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};charset=utf8mb4";
                $pdo = new \PDO($dsn, $db_config['user'], $db_config['pass'], [
                    \PDO::ATTR_TIMEOUT => 5,
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
                ]);
                
                // 尝试创建数据库（如果不存在）或切换到该数据库，以验证权限
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_config['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `{$db_config['name']}`");
            } catch (\PDOException $e) {
                $_SESSION['install_error'] = '数据库连接或权限验证失败: ' . $e->getMessage();
                header('Location: ?step=6');
                exit;
            }

            $existing_tables = $this->_getExistingProjectTables($pdo, $db_config['name'], $db_config['prefix']);

            $_SESSION['install_config']['db'] = $db_config;
            $_SESSION['install_db_detect'] = [
                'existing_tables' => $existing_tables,
                'existing_count' => count($existing_tables),
            ];
             
            header('Location: ?step=7');
            exit;
        }

        require_once APP_PATH . 'install/views/step6.php';
    }

    /**
     * 步骤7: 执行安装
     */
    private function _step7()
    {
        if (empty($_SESSION['install_config']['db'])) {
            header('Location: ?step=6');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'execute') {
            $this->_executeInstallStream();
            return;
        }

        $db_detect = $_SESSION['install_db_detect'] ?? ['existing_count' => 0, 'existing_tables' => []];
        require_once APP_PATH . 'install/views/step7.php';
    }

    /**
     * 步骤8: 完成页面
     */
    private function _step8()
    {
        // 为了安全，再次检查 lock 文件
        if (!file_exists(APP_PATH . 'install/install.lock')) {
            header('Location: /');
            exit;
        }

        $admin_path = $_SESSION['install_admin_path'] ?? 'admin';
        $site_url = $_SESSION['install_final_site_url'] ?? '';
        $admin_username = $_SESSION['install_admin_username'] ?? '';
        $full_admin_url = rtrim($site_url, '/') . '/' . ltrim($admin_path, '/');

        require_once APP_PATH . 'install/views/step8.php';

        // 安装完成后销毁session
        if (session_status() == PHP_SESSION_ACTIVE) {
            // 清理所有会话变量
            $_SESSION = [];
            // 销毁会话
            session_destroy();
        }
    }

    /**
     * 检查服务器环境
     */
    private function _checkEnv()
    {
        return [
            'os'      => ['name' => '操作系统', 'required' => '不限', 'current' => PHP_OS, 'pass' => true],
            'php'     => ['name' => 'PHP版本', 'required' => '8.0+', 'current' => PHP_VERSION, 'pass' => version_compare(PHP_VERSION, '8.0.0', '>=')],
        ];
    }

    /**
     * 检查目录权限
     */
    private function _checkDir()
    {
        $items = [
            ['path' => realpath(APP_PATH . '../storage/framework'), 'name' => '/storage/framework', 'required' => '可写'],
            ['path' => realpath(APP_PATH . '../storage/logs'), 'name' => '/storage/logs', 'required' => '可写'],
            ['path' => realpath(APP_PATH . '../public/uploads'), 'name' => '/public/uploads', 'required' => '可写'],
        ];

        foreach ($items as &$item) {
            $path = $item['path'];
            if ($path && is_dir($path)) {
                $item['current'] = is_writable($path) ? '可写' : '不可写';
                $item['pass'] = is_writable($path);
            } else {
                $item['current'] = '不存在';
                $item['pass'] = false;
            }
        }
        return $items;
    }

    /**
     * 检查PHP扩展和函数
     */
    private function _checkFunc()
    {
        $items = [
            ['name' => 'pdo_mysql', 'required' => '支持', 'pass' => extension_loaded('pdo_mysql')],
            ['name' => 'openssl', 'required' => '支持', 'pass' => extension_loaded('openssl')],
            ['name' => 'gd', 'required' => '支持', 'pass' => extension_loaded('gd')],
            ['name' => 'redis', 'required' => '支持', 'pass' => extension_loaded('redis')],
            ['name' => 'json', 'required' => '支持', 'pass' => extension_loaded('json')],
            ['name' => 'curl', 'required' => '支持', 'pass' => extension_loaded('curl')],
            ['name' => 'fileinfo', 'required' => '支持', 'pass' => extension_loaded('fileinfo')],
        ];

        foreach ($items as &$item) {
            $item['current'] = $item['pass'] ? '支持' : '不支持';
        }

        return $items;
    }

    /**
     * 创建 .env 配置文件
     */
    private function _createEnvFile($config, $site_name, $site_url)
    {
        $app_key = 'base64:' . base64_encode(random_bytes(32));
        $env_content = <<<EOT
APP_NAME="{$site_name}"
APP_ENV=local
APP_KEY={$app_key}
APP_DEBUG=true
APP_URL={$site_url}

DB_CONNECTION=mysql
DB_HOST={$config['db']['host']}
DB_PORT={$config['db']['port']}
DB_DATABASE={$config['db']['name']}
DB_USERNAME={$config['db']['user']}
DB_PASSWORD='{$config['db']['pass']}'
DB_PREFIX={$config['db']['prefix']}

REDIS_HOST={$config['redis']['host']}
REDIS_PASSWORD='{$config['redis']['pass']}'
REDIS_PORT={$config['redis']['port']}

ADMIN_PATH={$config['admin_path']}

RABBITMQ_ENABLED={$config['rabbitmq']['enabled']}
RABBITMQ_HOST={$config['rabbitmq']['host']}
RABBITMQ_PORT={$config['rabbitmq']['port']}
RABBITMQ_USER={$config['rabbitmq']['user']}
RABBITMQ_PASSWORD='{$config['rabbitmq']['pass']}'

STORAGE_TYPE={$config['storage']['storage_type']}
STORAGE_BASE_PATH={$config['storage']['base_path']}
STORAGE_URL_PREFIX={$config['storage']['url_prefix']}
STORAGE_COMPRESSION_ENABLED={$config['storage']['compression_enabled']}
STORAGE_MAX_COMPRESSION_LEVEL={$config['storage']['max_compression_level']}

EOT;
        file_put_contents(realpath(APP_PATH . '../') . '/.env', $env_content);
    }

    /**
     * 执行数据库迁移
     */
    private function _runMigrations(\PDO $pdo, $prefix, ?callable $logger = null)
    {
        $migrations_dir = realpath(APP_PATH . '../database/migrations');
        if (!$migrations_dir || !is_dir($migrations_dir)) {
            throw new \Exception('关键错误：数据库迁移目录 (database/migrations) 丢失或不可读。');
        }

        $sql_files = glob($migrations_dir . '/*.sql');
        if (empty($sql_files)) {
            throw new \Exception('关键错误：数据库迁移目录中没有找到SQL文件。');
        }

        natsort($sql_files);

        foreach ($sql_files as $schema_file) {
            $filename = basename($schema_file);
            if ($logger) {
                $logger("执行迁移文件: {$filename}");
            }
            if (!file_exists($schema_file) || !is_readable($schema_file)) {
                throw new \Exception("关键错误：数据库迁移文件 ({$filename}) 丢失或不可读。");
            }

            $sql = file_get_contents($schema_file);
            if ($sql === false) {
                throw new \Exception("关键错误：无法读取数据库迁移文件 ({$filename})。");
            }

            $sql = str_replace('__PREFIX__', $prefix, $sql);
            $statements = array_filter(array_map('trim', explode(';', $sql)));

            foreach ($statements as $statement) {
                if ($statement) {
                    try {
                        $pdo->exec($statement);
                    } catch (\PDOException $e) {
                        // 忽略常见的“已存在”/重复错误，方便重复执行迁移：
                        // 42S01: Table already exists; 42S21: Column already exists
                        // 42000/1091: Index/duplicate key already exists
                        // 23000: Integrity constraint violation (这里主要是 Duplicate entry)
                        $code = $e->getCode();
                        $msg = $e->getMessage();
                        $isDuplicate =
                            $code === '23000' &&
                            (stripos($msg, 'Duplicate entry') !== false || stripos($msg, 'duplicate key') !== false);

                        if (!in_array($code, ['42S01', '42S21', '42000', '1091'], true) && !$isDuplicate) {
                            throw $e;
                        }
                    }
                }
            }
        }
    }

    /**
     * 获取当前前缀下已存在的表
     */
    private function _getExistingProjectTables(\PDO $pdo, $db_name, $prefix)
    {
        $stmt = $pdo->prepare(
            'SELECT table_name
             FROM information_schema.tables
             WHERE table_schema = :schema
               AND table_name LIKE :prefix
             ORDER BY table_name ASC'
        );
        $stmt->execute([
            ':schema' => $db_name,
            ':prefix' => $prefix . '%',
        ]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];
    }

    /**
     * 删除当前前缀下的表（全新安装模式）
     */
    private function _dropProjectTables(\PDO $pdo, $db_name, $prefix, ?callable $logger = null)
    {
        $tables = $this->_getExistingProjectTables($pdo, $db_name, $prefix);
        if (empty($tables)) {
            if ($logger) {
                $logger('未检测到需要清理的旧表。');
            }
            return;
        }

        if ($logger) {
            $logger('检测到旧表 ' . count($tables) . ' 张，开始清理...');
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach ($tables as $table) {
            $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
            if ($logger) {
                $logger("已删除表: {$table}");
            }
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * 流式执行安装，便于前端实时展示日志
     */
    private function _executeInstallStream()
    {
        @set_time_limit(0);

        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('X-Accel-Buffering: no');

        $log = function ($message) {
            $line = '[' . date('H:i:s') . '] ' . $message . PHP_EOL;
            echo $line;
            flush();
        };

        $config = $_SESSION['install_config'] ?? [];
        $admin_info = $_SESSION['install_admin_info'] ?? [];
        $site_name = $_SESSION['install_site_name'] ?? '';
        $install_mode = $_POST['install_mode'] ?? 'patch';

        if (empty($config['db']) || empty($admin_info) || $site_name === '') {
            $log('__INSTALL_ERROR__ 安装会话已失效，请返回上一步重新填写。');
            return;
        }

        if (!in_array($install_mode, ['fresh', 'patch'], true)) {
            $log('__INSTALL_ERROR__ 无效的安装模式。');
            return;
        }

        // 自动获取当前站点 URL
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $site_url = $protocol . '://' . $host;

        try {
            $log('开始执行安装流程...');
            $log('连接数据库...');
            $dsn = "mysql:host={$config['db']['host']};port={$config['db']['port']};charset=utf8mb4";
            $pdo = new \PDO($dsn, $config['db']['user'], $config['db']['pass'], [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            ]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['db']['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$config['db']['name']}`");
            $log('数据库连接成功。');

            if ($install_mode === 'fresh') {
                $log('安装模式: 全新安装（清理旧表并重建）');
                $this->_dropProjectTables($pdo, $config['db']['name'], $config['db']['prefix'], $log);
            } else {
                $log('安装模式: 跳过已有数据，仅补齐缺失表');
            }

            $log('写入 .env 配置...');
            $this->_createEnvFile($config, $site_name, $site_url);
            $log('.env 配置写入完成。');

            $log('开始执行数据库迁移...');
            $this->_runMigrations($pdo, $config['db']['prefix'], $log);
            $log('数据库迁移完成。');

            $log('写入管理员账号...');
            $this->_createAdminUser($pdo, $config['db']['prefix'], $admin_info);
            $log('管理员账号处理完成。');

            file_put_contents(APP_PATH . 'install/install.lock', 'installed at ' . date('Y-m-d H:i:s'));
            $log('install.lock 创建成功。');

            $_SESSION['install_admin_path'] = $config['admin_path'];
            $_SESSION['install_final_site_url'] = $site_url;
            $_SESSION['install_admin_username'] = $admin_info['username'];
            unset(
                $_SESSION['install_config'],
                $_SESSION['install_admin_info'],
                $_SESSION['install_site_name'],
                $_SESSION['install_error'],
                $_SESSION['install_admin_draft'],
                $_SESSION['install_db_detect']
            );

            $log('__INSTALL_DONE__ 安装成功');
        } catch (\Exception $e) {
            $log('__INSTALL_ERROR__ ' . $e->getMessage());
        }
    }

    /**
     * 创建管理员用户
     */
    private function _createAdminUser(\PDO $pdo, $prefix, $admin_info)
    {
        $hashed_password = password_hash($admin_info['password'], PASSWORD_DEFAULT);
        $sql = "INSERT INTO `{$prefix}admin_admins` (username, password, email)
                VALUES (:username, :password, :email)
                ON DUPLICATE KEY UPDATE password = VALUES(password), email = VALUES(email)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':username' => $admin_info['username'],
            ':password' => $hashed_password,
            ':email' => $admin_info['email'],
        ]);
    }
}
