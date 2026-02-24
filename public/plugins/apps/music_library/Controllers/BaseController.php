<?php

namespace plugins\apps\music_library\Controllers;

use PDO;
use PDOException;

/**
 * 音乐库插件的基础控制器 (Base Controller)
 *
 * 这是一个抽象类，作为插件内部所有其他控制器 (`AdminController`, `FrontendController`, `ApiController`) 的父类。
 * 它封装了所有控制器都需要共享的功能，以遵循 "Don't Repeat Yourself" (DRY) 原则。
 *
 * 核心功能:
 * - 数据库连接: 在控制器实例化时自动建立数据库连接。
 * - 视图渲染: 提供统一的 `render` 方法来加载插件专属的视图文件。
 * - 数据库操作封装: 提供 `query`, `execute` 等便捷方法来执行SQL。
 * - API响应格式化: 提供 `jsonResponse` 方法来标准化JSON输出。
 */
abstract class BaseController
{
    /**
     * @var PDO|null 数据库连接对象 (PDO instance)。
     */
    protected $db = null;

    /**
     * @var string 数据库表前缀，从 .env 文件读取。
     */
    protected $db_prefix = '';

    /**
     * 构造函数
     *
     * 当任何继承此基类的控制器被实例化时，此方法会自动执行。
     * 它负责从 .env 配置中读取数据库信息并建立一个PDO连接。
     */
    public function __construct()
    {
        // 从配置中获取数据表前缀
        $this->db_prefix = get_env('DB_PREFIX', 'sn_');
        
        try {
            // 使用 sprintf 构建 DSN (Data Source Name)
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                get_env('DB_HOST'),
                get_env('DB_PORT'),
                get_env('DB_DATABASE')
            );
            // 创建 PDO 实例
            $this->db = new PDO(
                $dsn,
                get_env('DB_USERNAME'),
                get_env('DB_PASSWORD'),
                [
                    // 设置错误模式为抛出异常，便于捕获和处理
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    // 设置默认的 fetch 模式为关联数组
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $e) {
            // 在生产环境中，应该记录错误日志并向用户显示一个友好的错误页面。
            // 为简单起见，这里直接终止程序并显示错误信息。
            error_log('Database connection failed: ' . $e->getMessage());
            die('数据库连接失败。请检查您的 .env 配置文件或联系管理员。');
        }
    }

    /**
     * 渲染一个视图文件。
     *
     * 此方法从插件自身的 `views` 目录中加载一个 PHP 文件，并向其传递数据。
     *
     * @param string $view 视图文件的相对路径 (不含 .php 后缀)。例如: 'admin/tracks'。
     * @param array  $data 一个关联数组，其键名将作为变量在视图文件中可用。
     * @return void
     */
    protected function render($view, $data = [])
    {
        // 构建视图文件的完整物理路径
        $view_path = realpath(__DIR__ . '/../views/' . $view . '.php');

        if ($view_path && is_readable($view_path)) {
            // 将 $data 数组的键值对提取为独立的变量 (e.g., $data['title'] -> $title)
            extract($data);
            // 开启输出缓冲
            ob_start();
            // 引入视图文件，此时视图文件中的代码会执行，其输出会被捕获到缓冲区
            require $view_path;
            // 获取缓冲区内容并清空缓冲区，然后输出内容
            echo ob_get_clean();
        } else {
            // 如果视图文件未找到，返回 500 错误
            http_response_code(500);
            error_log("Error: View file not found at {$view_path}");
            echo "错误: 视图文件未找到。";
        }
    }

    /**
     * 执行一个 SELECT 查询并返回所有结果。
     *
     * @param string $sql    要执行的SQL语句，可以使用 ? 作为占位符。
     * @param array  $params 用于绑定到占位符的参数数组。
     * @return array|false   成功时返回包含所有行的数组，失败时返回 false。
     */
    protected function query($sql, $params = [])
    {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Query failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 执行一个 INSERT, UPDATE, 或 DELETE 语句。
     *
     * @param string $sql    要执行的SQL语句，可以使用 ? 作为占位符。
     * @param array  $params 用于绑定到占位符的参数数组。
     * @return int           返回受影响的行数。
     */
    protected function execute($sql, $params = [])
    {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log('Execute failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * 获取带前缀的完整表名。
     *
     * @param string $name 表的基本名称 (例如: 'music_tracks')。
     * @return string      带前缀和反引号的完整表名 (例如: '`sn_music_tracks`')。
     */
    protected function getTableName($name)
    {
        return '`' . $this->db_prefix . $name . '`';
    }
    
    /**
     * 输出一个标准的 JSON 响应并终止程序。
     *
     * @param mixed $data       要编码为 JSON 的数据。
     * @param int   $statusCode HTTP 状态码，默认为 200。
     * @return void
     */
    protected function jsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
