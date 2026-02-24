<?php

declare(strict_types=1);

/**
 * 向后兼容层
 * 
 * 该文件提供向后兼容的类别名和函数，确保在过渡期内旧代码仍能正常工作
 * 所有兼容层方法都标记为 @deprecated，建议迁移到新实现
 * 
 * @package Core
 * @version 2.0.0
 */

// ==================== 命名空间别名 ====================

// 核心类别名（未来命名空间重构后启用）
// class_alias(\StarryNight\Core\Orm\Model::class, \Core\Orm\Model::class);
// class_alias(\StarryNight\Core\Services\DatabaseService::class, \Core\Services\DatabaseService::class);
// class_alias(\StarryNight\App\Models\User::class, \app\models\User::class);

// ==================== 辅助函数 ====================

if (!function_exists('db')) {
    /**
     * 获取数据库服务实例
     * 
     * @return \Core\Services\DatabaseService
     * @deprecated 使用依赖注入替代
     */
    function db(): \Core\Services\DatabaseService
    {
        static $instance = null;
        if ($instance === null) {
            $instance = \Core\Services\DatabaseService::fromEnvironment();
        }
        return $instance;
    }
}

if (!function_exists('pdo')) {
    /**
     * 获取PDO连接
     * 
     * @return \PDO
     * @deprecated 使用 db()->getPdo() 或依赖注入替代
     */
    function pdo(): \PDO
    {
        return db()->getPdo();
    }
}

if (!function_exists('db_prefix')) {
    /**
     * 获取数据库表前缀
     * 
     * @return string
     * @deprecated 使用 db()->getPrefix() 替代
     */
    function db_prefix(): string
    {
        return db()->getPrefix();
    }
}

if (!function_exists('db_table')) {
    /**
     * 获取带前缀的表名
     * 
     * @param string $table 表名
     * @return string
     * @deprecated 使用 db()->tableName($table) 替代
     */
    function db_table(string $table): string
    {
        return db()->tableName($table);
    }
}

// ==================== 模型兼容层 ====================

if (!class_exists('\Core\Database')) {
    /**
     * 数据库兼容类
     * 
     * 该类提供与旧 Database 类兼容的静态方法
     * 所有方法委托给新的 DatabaseService
     * 
     * @deprecated 使用 \Core\Services\DatabaseService 替代
     * @see \Core\Services\DatabaseService
     */
    class Database
    {
        /**
         * 获取PDO连接
         *
         * @return \PDO
         * @deprecated 使用 DatabaseService::fromEnvironment()->getPdo() 替代
         */
        public static function pdo(): \PDO
        {
            return \Core\Services\DatabaseService::fromEnvironment()->getPdo();
        }

        /**
         * 获取表前缀
         *
         * @return string
         * @deprecated 使用 DatabaseService::fromEnvironment()->getPrefix() 替代
         */
        public static function prefix(): string
        {
            return \Core\Services\DatabaseService::fromEnvironment()->getPrefix();
        }

        /**
         * 执行查询并返回所有结果
         *
         * @param string $sql SQL语句
         * @param array $params 参数
         * @return array
         * @deprecated 使用 DatabaseService::queryAll() 替代
         */
        public static function queryAll(string $sql, array $params = []): array
        {
            return \Core\Services\DatabaseService::fromEnvironment()->queryAll($sql, $params);
        }

        /**
         * 执行查询并返回单行结果
         *
         * @param string $sql SQL语句
         * @param array $params 参数
         * @return array|null
         * @deprecated 使用 DatabaseService::queryOne() 替代
         */
        public static function queryOne(string $sql, array $params = []): ?array
        {
            return \Core\Services\DatabaseService::fromEnvironment()->queryOne($sql, $params);
        }

        /**
         * 执行SQL语句
         *
         * @param string $sql SQL语句
         * @param array $params 参数
         * @return bool
         * @deprecated 使用 DatabaseService::execute() 替代
         */
        public static function execute(string $sql, array $params = []): bool
        {
            return \Core\Services\DatabaseService::fromEnvironment()->execute($sql, $params);
        }

        /**
         * 插入数据
         *
         * @param string $table 表名
         * @param array $data 数据
         * @return int|string
         * @deprecated 使用 DatabaseService::insert() 替代
         */
        public static function insert(string $table, array $data): int|string
        {
            return \Core\Services\DatabaseService::fromEnvironment()->insert($table, $data);
        }

        /**
         * 更新数据
         *
         * @param string $table 表名
         * @param array $data 数据
         * @param string $where 条件
         * @param array $whereParams 条件参数
         * @return int
         * @deprecated 使用 DatabaseService::update() 替代
         */
        public static function update(string $table, array $data, string $where, array $whereParams = []): int
        {
            return \Core\Services\DatabaseService::fromEnvironment()->update($table, $data, $where, $whereParams);
        }

        /**
         * 删除数据
         *
         * @param string $table 表名
         * @param string $where 条件
         * @param array $params 参数
         * @return int
         * @deprecated 使用 DatabaseService::delete() 替代
         */
        public static function delete(string $table, string $where, array $params = []): int
        {
            return \Core\Services\DatabaseService::fromEnvironment()->delete($table, $where, $params);
        }

        /**
         * 开始事务
         *
         * @return bool
         * @deprecated 使用 DatabaseService::beginTransaction() 替代
         */
        public static function beginTransaction(): bool
        {
            return \Core\Services\DatabaseService::fromEnvironment()->beginTransaction();
        }

        /**
         * 提交事务
         *
         * @return bool
         * @deprecated 使用 DatabaseService::commit() 替代
         */
        public static function commit(): bool
        {
            return \Core\Services\DatabaseService::fromEnvironment()->commit();
        }

        /**
         * 回滚事务
         *
         * @return bool
         * @deprecated 使用 DatabaseService::rollback() 替代
         */
        public static function rollback(): bool
        {
            return \Core\Services\DatabaseService::fromEnvironment()->rollback();
        }
    }
}

// ==================== API版本兼容层 ====================

if (!function_exists('api_version')) {
    /**
     * 获取当前API版本
     * 
     * @return string
     */
    function api_version(): string
    {
        static $version = null;
        
        if ($version === null) {
            // 从URI解析
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            if (preg_match('#/api/(v\d+)/#i', $uri, $matches)) {
                $version = strtolower($matches[1]);
            } else {
                $version = 'v1'; // 默认版本
            }
        }
        
        return $version;
    }
}

if (!function_exists('api_url')) {
    /**
     * 生成API URL
     * 
     * @param string $path 路径
     * @param string|null $version 版本号
     * @return string
     */
    function api_url(string $path, ?string $version = null): string
    {
        $version = $version ?? api_version();
        $path = ltrim($path, '/');
        return "/api/{$version}/{$path}";
    }
}

// ==================== 模型工厂函数 ====================

if (!function_exists('model')) {
    /**
     * 创建模型实例
     * 
     * @param string $modelClass 模型类名
     * @return object
     */
    function model(string $modelClass): object
    {
        // 支持简写
        if (!class_exists($modelClass)) {
            // 尝试在 app\models 命名空间下查找
            $fullClass = "app\\models\\{$modelClass}";
            if (class_exists($fullClass)) {
                $modelClass = $fullClass;
            }
        }
        
        return new $modelClass();
    }
}

// ==================== 废弃警告辅助函数 ====================

if (!function_exists('trigger_deprecated')) {
    /**
     * 触发废弃警告
     * 
     * @param string $message 废弃信息
     * @param string $replacement 替代方案
     * @return void
     */
    function trigger_deprecated(string $message, string $replacement = ''): void
    {
        $fullMessage = "Deprecated: {$message}";
        if ($replacement) {
            $fullMessage .= " Use {$replacement} instead.";
        }
        
        trigger_error($fullMessage, E_USER_DEPRECATED);
    }
}

// ==================== 服务容器辅助 ====================

if (!function_exists('container')) {
    /**
     * 获取服务容器
     * 
     * @return \Core\Container\Container
     */
    function container(): \Core\Container\Container
    {
        static $container = null;
        
        if ($container === null) {
            $container = new \Core\Container\Container();
            
            // 注册默认服务
            $container->singleton('database', function () {
                return \Core\Services\DatabaseService::fromEnvironment();
            });
            
            $container->singleton('pdo', function ($c) {
                return $c->get('database')->getPdo();
            });
        }
        
        return $container;
    }
}

if (!function_exists('resolve')) {
    /**
     * 从容器解析服务
     * 
     * @param string $id 服务ID
     * @return mixed
     */
    function resolve(string $id): mixed
    {
        return container()->get($id);
    }
}

if (!function_exists('bind')) {
    /**
     * 绑定服务到容器
     *
     * @param string $id 服务ID
     * @param callable $factory 工厂函数
     * @return void
     */
    function bind(string $id, callable $factory): void
    {
        container()->bind($id, $factory);
    }
}

// ==================== 日志辅助函数 ====================

if (!function_exists('logger')) {
    /**
     * 获取日志服务
     * 
     * @return \Core\Services\LoggerService
     */
    function logger(): \Core\Services\LoggerService
    {
        static $logger = null;
        
        if ($logger === null) {
            $logger = new \Core\Services\LoggerService();
        }
        
        return $logger;
    }
}

if (!function_exists('log_info')) {
    /**
     * 记录信息日志
     * 
     * @param string $message 消息
     * @param array $context 上下文
     * @return void
     */
    function log_info(string $message, array $context = []): void
    {
        logger()->info($message, $context);
    }
}

if (!function_exists('log_error')) {
    /**
     * 记录错误日志
     * 
     * @param string $message 消息
     * @param array $context 上下文
     * @return void
     */
    function log_error(string $message, array $context = []): void
    {
        logger()->error($message, $context);
    }
}

if (!function_exists('log_warning')) {
    /**
     * 记录警告日志
     * 
     * @param string $message 消息
     * @param array $context 上下文
     * @return void
     */
    function log_warning(string $message, array $context = []): void
    {
        logger()->warning($message, $context);
    }
}
