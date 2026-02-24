#!/usr/bin/env php
<?php
/**
 * 星夜阁数据库补全脚本
 * 
 * 功能：
 * 1. 检查并创建缺失的数据库表
 * 2. 补充必要的初始数据
 * 3. 优化数据库结构和索引
 * 4. 验证数据完整性
 */

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 定义根目录
define('ROOT_PATH', dirname(__DIR__));

// 加载必要的文件
require_once ROOT_PATH . '/vendor/autoload.php';
require_once ROOT_PATH . '/app/helpers.php';

use app\services\Database;
use app\services\ErrorHandler;

// 注册异常处理器
ErrorHandler::register();

class DatabaseCompleter {
    private $pdo;
    private $prefix;
    private $logFile;
    
    public function __construct() {
        $this->pdo = Database::pdo();
        $this->prefix = Database::prefix();
        $this->logFile = ROOT_PATH . '/logs/database_completion_' . date('Y-m-d_H-i-s') . '.log';
        
        // 确保日志目录存在
        if (!is_dir(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0755, true);
        }
    }
    
    public function run() {
        $this->log("开始数据库补全流程...");
        
        try {
            // 1. 执行缺失表的迁移
            $this->runMissingMigrations();
            
            // 2. 插入初始数据
            $this->insertInitialData();
            
            // 3. 优化数据库结构
            $this->optimizeDatabase();
            
            // 4. 验证数据完整性
            $this->validateDataIntegrity();
            
            $this->log("数据库补全流程完成！");
            
        } catch (Exception $e) {
            $this->log("执行过程中发生错误: " . $e->getMessage());
            $this->log("堆栈跟踪: " . $e->getTraceAsString());
            throw $e;
        }
    }
    
    /**
     * 执行缺失表的迁移
     */
    private function runMissingMigrations() {
        $this->log("开始执行缺失表迁移...");
        
        $migrationFiles = [
            '014_missing_core_tables.sql'
        ];
        
        foreach ($migrationFiles as $file) {
            $filePath = ROOT_PATH . '/database/migrations/' . $file;
            if (file_exists($filePath)) {
                $this->executeMigrationFile($filePath);
            } else {
                $this->log("警告: 迁移文件不存在 - {$file}");
            }
        }
        
        $this->log("缺失表迁移执行完成");
    }
    
    /**
     * 执行单个迁移文件
     */
    private function executeMigrationFile(string $filePath) {
        $this->log("执行迁移文件: " . basename($filePath));
        
        $sql = file_get_contents($filePath);
        if (!$sql) {
            $this->log("错误: 无法读取迁移文件 {$filePath}");
            return;
        }
        
        // 替换表前缀
        $sql = str_replace('__PREFIX__', $this->prefix, $sql);
        
        // 分割SQL语句
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) {
                return !empty($stmt) && !preg_match('/^\s*--/', $stmt);
            }
        );
        
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($statements as $statement) {
            try {
                $this->pdo->exec($statement);
                $successCount++;
            } catch (PDOException $e) {
                // 如果是表已存在的错误，则忽略
                if (strpos($e->getMessage(), 'already exists') !== false) {
                    $this->log("表已存在，跳过: " . $this->extractTableName($statement));
                } else {
                    $this->log("SQL执行错误: " . $e->getMessage());
                    $errorCount++;
                }
            }
        }
        
        $this->log("迁移文件执行结果 - 成功: {$successCount}, 错误: {$errorCount}");
    }
    
    /**
     * 提取SQL语句中的表名
     */
    private function extractTableName(string $sql): string {
        if (preg_match('/`([^`]+)`/', $sql, $matches)) {
            return $matches[1];
        }
        return 'unknown_table';
    }
    
    /**
     * 插入初始数据
     */
    private function insertInitialData() {
        $this->log("开始插入初始数据...");
        
        // 1. 插入默认会员等级
        $this->insertDefaultMembershipLevels();
        
        // 2. 插入默认VIP权益
        $this->insertDefaultVipBenefits();
        
        // 3. 插入默认公告分类
        $this->insertDefaultAnnouncementCategories();
        
        // 4. 插入默认AI模型配置
        $this->insertDefaultAiModels();
        
        // 5. 插入默认创作工具
        $this->insertDefaultCreationTools();
        
        $this->log("初始数据插入完成");
    }
    
    /**
     * 插入默认会员等级
     */
    private function insertDefaultMembershipLevels() {
        $levels = [
            [
                'name' => '普通用户',
                'level' => 0,
                'description' => '基础用户权限',
                'price_monthly' => 0.00,
                'price_yearly' => 0.00
            ],
            [
                'name' => 'VIP会员',
                'level' => 1,
                'description' => '尊享VIP特权',
                'price_monthly' => 29.90,
                'price_yearly' => 299.00
            ],
            [
                'name' => 'SVIP会员',
                'level' => 2,
                'description' => '超级VIP特权',
                'price_monthly' => 59.90,
                'price_yearly' => 599.00
            ]
        ];
        
        foreach ($levels as $level) {
            $sql = "INSERT IGNORE INTO `{$this->prefix}membership_levels` 
                    (name, level, description, price_monthly, price_yearly, is_active, sort_order) 
                    VALUES (:name, :level, :description, :price_monthly, :price_yearly, 1, :sort_order)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':name' => $level['name'],
                ':level' => $level['level'],
                ':description' => $level['description'],
                ':price_monthly' => $level['price_monthly'],
                ':price_yearly' => $level['price_yearly'],
                ':sort_order' => $level['level']
            ]);
        }
        
        $this->log("默认会员等级插入完成");
    }
    
    /**
     * 插入默认VIP权益
     */
    private function insertDefaultVipBenefits() {
        $benefits = [
            [
                'benefit_key' => 'higher_ai_quota',
                'benefit_name' => '更高AI配额',
                'benefit_type' => 'quota',
                'description' => '享受更高的AI服务使用配额'
            ],
            [
                'benefit_key' => 'exclusive_models',
                'benefit_name' => '专享模型',
                'benefit_type' => 'feature',
                'description' => '可使用VIP专享的高级AI模型'
            ],
            [
                'benefit_key' => 'priority_support',
                'benefit_name' => '优先客服',
                'benefit_type' => 'feature',
                'description' => '享受7×24小时优先客服支持'
            ],
            [
                'benefit_key' => 'early_access',
                'benefit_name' => '新功能抢先体验',
                'benefit_type' => 'feature',
                'description' => '优先体验平台最新功能'
            ]
        ];
        
        foreach ($benefits as $benefit) {
            $sql = "INSERT IGNORE INTO `{$this->prefix}vip_benefits` 
                    (benefit_key, benefit_name, benefit_type, description, is_enabled) 
                    VALUES (:key, :name, :type, :description, 1)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':key' => $benefit['benefit_key'],
                ':name' => $benefit['benefit_name'],
                ':type' => $benefit['benefit_type'],
                ':description' => $benefit['description']
            ]);
        }
        
        // 关联会员等级和权益
        $this->associateMembershipBenefits();
        
        $this->log("默认VIP权益插入完成");
    }
    
    /**
     * 关联会员等级和权益
     */
    private function associateMembershipBenefits() {
        // 获取会员等级和权益ID
        $levels = $this->pdo->query("SELECT id, level FROM `{$this->prefix}membership_levels` ORDER BY level")->fetchAll();
        $benefits = $this->pdo->query("SELECT id, benefit_key FROM `{$this->prefix}vip_benefits`")->fetchAll();
        
        foreach ($levels as $level) {
            $levelId = $level['id'];
            $levelNum = $level['level'];
            
            foreach ($benefits as $benefit) {
                $benefitId = $benefit['id'];
                $benefitKey = $benefit['benefit_key'];
                
                // 根据会员等级分配不同权益
                $shouldAssign = false;
                switch ($levelNum) {
                    case 0: // 普通用户
                        $shouldAssign = false;
                        break;
                    case 1: // VIP
                        $shouldAssign = in_array($benefitKey, ['higher_ai_quota', 'priority_support']);
                        break;
                    case 2: // SVIP
                        $shouldAssign = true; // 所有权益
                        break;
                }
                
                if ($shouldAssign) {
                    $sql = "INSERT IGNORE INTO `{$this->prefix}membership_level_benefits` 
                            (membership_level_id, benefit_id) VALUES (:level_id, :benefit_id)";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([
                        ':level_id' => $levelId,
                        ':benefit_id' => $benefitId
                    ]);
                }
            }
        }
    }
    
    /**
     * 插入默认公告分类
     */
    private function insertDefaultAnnouncementCategories() {
        $categories = [
            ['name' => '系统通知', 'description' => '系统重要通知和更新', 'sort_order' => 1],
            ['name' => '活动公告', 'description' => '平台活动和促销信息', 'sort_order' => 2],
            ['name' => '维护公告', 'description' => '系统维护和升级通知', 'sort_order' => 3],
            ['name' => '其他', 'description' => '其他类型公告', 'sort_order' => 4]
        ];
        
        foreach ($categories as $category) {
            $sql = "INSERT IGNORE INTO `{$this->prefix}announcement_categories` 
                    (name, description, sort_order, is_active) 
                    VALUES (:name, :description, :sort_order, 1)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':name' => $category['name'],
                ':description' => $category['description'],
                ':sort_order' => $category['sort_order']
            ]);
        }
        
        $this->log("默认公告分类插入完成");
    }
    
    /**
     * 插入默认AI模型配置
     */
    private function insertDefaultAiModels() {
        $models = [
            [
                'name' => 'gpt-3.5-turbo',
                'description' => 'OpenAI GPT-3.5 Turbo模型',
                'type' => 'openai',
                'is_enabled' => 1,
                'sort_order' => 1
            ],
            [
                'name' => 'gpt-4',
                'description' => 'OpenAI GPT-4模型',
                'type' => 'openai',
                'is_enabled' => 1,
                'sort_order' => 2
            ],
            [
                'name' => 'claude-3-haiku',
                'description' => 'Anthropic Claude 3 Haiku模型',
                'type' => 'anthropic',
                'is_enabled' => 1,
                'sort_order' => 3
            ]
        ];
        
        foreach ($models as $model) {
            $sql = "INSERT IGNORE INTO `{$this->prefix}ai_preset_models` 
                    (name, description, is_enabled, sort_order) 
                    VALUES (:name, :description, :is_enabled, :sort_order)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':name' => $model['name'],
                ':description' => $model['description'],
                ':is_enabled' => $model['is_enabled'],
                ':sort_order' => $model['sort_order']
            ]);
        }
        
        $this->log("默认AI模型配置插入完成");
    }
    
    /**
     * 插入默认创作工具
     */
    private function insertDefaultCreationTools() {
        $tools = [
            [
                'name' => '世界观生成器',
                'code' => 'world_builder',
                'category' => 'worldview',
                'description' => '根据关键词生成详细的世界观设定',
                'prompt_template' => '请根据以下关键词生成一个详细的世界观设定：{keywords}'
            ],
            [
                'name' => '角色生成器',
                'code' => 'character_creator',
                'category' => 'character',
                'description' => '生成详细的角色设定',
                'prompt_template' => '请创建一个名为{name}的角色，要求：{requirements}'
            ],
            [
                'name' => '情节生成器',
                'code' => 'plot_generator',
                'category' => 'plot',
                'description' => '生成故事情节和发展',
                'prompt_template' => '请为以下设定生成一个精彩的情节：{setup}'
            ]
        ];
        
        foreach ($tools as $tool) {
            $sql = "INSERT IGNORE INTO `{$this->prefix}creation_tools` 
                    (name, code, category, description, prompt_template, is_active, sort_order) 
                    VALUES (:name, :code, :category, :description, :prompt, 1, :sort_order)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':name' => $tool['name'],
                ':code' => $tool['code'],
                ':category' => $tool['category'],
                ':description' => $tool['description'],
                ':prompt' => $tool['prompt_template'],
                ':sort_order' => array_search($tool, $tools) + 1
            ]);
        }
        
        $this->log("默认创作工具插入完成");
    }
    
    /**
     * 优化数据库结构
     */
    private function optimizeDatabase() {
        $this->log("开始数据库优化...");
        
        // 1. 优化表结构
        $this->optimizeTableStructure();
        
        // 2. 创建必要的索引
        $this->createAdditionalIndexes();
        
        // 3. 更新统计信息
        $this->updateStatistics();
        
        $this->log("数据库优化完成");
    }
    
    /**
     * 优化表结构
     */
    private function optimizeTableStructure() {
        $optimizations = [
            "ALTER TABLE `{$this->prefix}users` ENGINE=InnoDB",
            "ALTER TABLE `{$this->prefix}ai_agents` ENGINE=InnoDB",
            "ALTER TABLE `{$this->prefix}user_feedback` ENGINE=InnoDB"
        ];
        
        foreach ($optimizations as $sql) {
            try {
                $this->pdo->exec($sql);
            } catch (PDOException $e) {
                // 忽略错误
            }
        }
    }
    
    /**
     * 创建额外索引
     */
    private function createAdditionalIndexes() {
        $indexes = [
            "CREATE INDEX IF NOT EXISTS idx_users_email_status ON `{$this->prefix}users` (email, status)",
            "CREATE INDEX IF NOT EXISTS idx_ai_agents_user_category ON `{$this->prefix}ai_agents` (user_id, category)",
            "CREATE INDEX IF NOT EXISTS idx_feedback_status_type ON `{$this->prefix}user_feedback` (status, type)",
            "CREATE INDEX IF NOT EXISTS idx_announcements_status_published ON `{$this->prefix}announcements` (status, published_at)"
        ];
        
        foreach ($indexes as $sql) {
            try {
                $this->pdo->exec($sql);
            } catch (PDOException $e) {
                // 忽略索引已存在的错误
            }
        }
    }
    
    /**
     * 更新统计信息
     */
    private function updateStatistics() {
        try {
            $this->pdo->exec("ANALYZE TABLE `{$this->prefix}users`");
            $this->pdo->exec("ANALYZE TABLE `{$this->prefix}ai_agents`");
        } catch (PDOException $e) {
            // 忽略错误
        }
    }
    
    /**
     * 验证数据完整性
     */
    private function validateDataIntegrity() {
        $this->log("开始数据完整性验证...");
        
        $checks = [
            '用户表' => "SELECT COUNT(*) FROM `{$this->prefix}users`",
            'AI智能体表' => "SELECT COUNT(*) FROM `{$this->prefix}ai_agents`",
            '会员等级表' => "SELECT COUNT(*) FROM `{$this->prefix}membership_levels`",
            'VIP权益表' => "SELECT COUNT(*) FROM `{$this->prefix}vip_benefits`",
            '公告分类表' => "SELECT COUNT(*) FROM `{$this->prefix}announcement_categories`",
            '用户反馈表' => "SELECT COUNT(*) FROM `{$this->prefix}user_feedback`"
        ];
        
        foreach ($checks as $name => $sql) {
            try {
                $count = $this->pdo->query($sql)->fetchColumn();
                $this->log("✓ {$name}: {$count} 条记录");
            } catch (PDOException $e) {
                $this->log("✗ {$name}: 查询失败 - " . $e->getMessage());
            }
        }
        
        $this->log("数据完整性验证完成");
    }
    
    /**
     * 记录日志
     */
    private function log(string $message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}\n";
        
        echo $logMessage;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }
}

// 执行数据库补全流程
try {
    $completer = new DatabaseCompleter();
    $completer->run();
    echo "\n✅ 数据库补全流程执行成功！\n";
    echo "详细日志请查看: " . ROOT_PATH . '/logs/database_completion_' . date('Y-m-d_H-i-s') . '.log' . "\n";
} catch (Exception $e) {
    echo "\n❌ 数据库补全流程执行失败: " . $e->getMessage() . "\n";
    exit(1);
}