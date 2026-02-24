<?php
/**
 * 队列工作进程
 * 
 * 用于处理队列中的任务
 * 
 * 使用方法:
 *   php scripts/queue_worker.php [queue_name] [--sleep=1] [--max_jobs=100]
 * 
 * 示例:
 *   php scripts/queue_worker.php default --sleep=2 --max_jobs=50
 * 
 * @package Scripts
 */

// 设置不超时
set_time_limit(0);

// 加载环境配置
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $name = trim($parts[0]);
            $value = trim($parts[1]);
            if (preg_match('/^"(.*)"$/', $value, $matches)) {
                $value = $matches[1];
            } elseif (preg_match("/^'(.*)'$/", $value, $matches)) {
                $value = $matches[1];
            }
            putenv("{$name}={$value}");
        }
    }
}

// 加载 Composer 自动加载
require_once __DIR__ . '/../vendor/autoload.php';

// 加载辅助函数
require_once __DIR__ . '/../app/helpers.php';

// 解析命令行参数
$options = getopt('', ['sleep::', 'max_jobs::', 'memory::', 'verbose::', 'once::']);
$queueName = $argv[1] ?? 'default';
$sleep = (int)($options['sleep'] ?? 1);
$maxJobs = (int)($options['max_jobs'] ?? 0);
$memoryLimit = (int)($options['memory'] ?? 128); // MB
$verbose = isset($options['verbose']);
$once = isset($options['once']);

// 输出信息函数
function output(string $message, bool $verbose = false): void
{
    global $verbose;
    if ($verbose || !$verbose) {
        echo '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    }
}

// 检查内存使用
function checkMemory(int $limit): bool
{
    $usage = memory_get_usage(true) / 1024 / 1024;
    return $usage > $limit;
}

// 注册信号处理器
$running = true;
pcntl_async_signals(true);
pcntl_signal(SIGTERM, function () use (&$running) {
    output('收到终止信号，正在停止...');
    $running = false;
});
pcntl_signal(SIGINT, function () use (&$running) {
    output('收到中断信号，正在停止...');
    $running = false;
});

output("队列工作进程启动");
output("队列名称: {$queueName}");
output("休眠间隔: {$sleep}秒");
output("内存限制: {$memoryLimit}MB");
output("最大任务数: " . ($maxJobs > 0 ? $maxJobs : '无限制'));
output("单次模式: " . ($once ? '是' : '否'));

try {
    // 获取队列服务
    $queue = new \Core\Queue\QueueService([
        'default_queue' => $queueName,
    ]);
    
    $processed = 0;
    
    while ($running) {
        // 尝试获取任务
        $job = $queue->pop($queueName);
        
        if ($job) {
            output("处理任务 #{$job['id']}: " . ($job['payload']['job'] ?? 'unknown'));
            
            $startTime = microtime(true);
            $success = $queue->processJob($job);
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($success) {
                output("任务 #{$job['id']} 完成 (耗时: {$duration}ms)");
            } else {
                output("任务 #{$job['id']} 失败", true);
            }
            
            $processed++;
            
            // 检查是否达到最大任务数
            if ($maxJobs > 0 && $processed >= $maxJobs) {
                output("已达到最大任务数 ({$maxJobs})，停止工作进程");
                break;
            }
            
            // 单次模式
            if ($once) {
                output("单次模式，退出");
                break;
            }
        } else {
            // 没有任务，休眠
            sleep($sleep);
        }
        
        // 检查内存
        if (checkMemory($memoryLimit)) {
            $usage = round(memory_get_usage(true) / 1024 / 1024, 2);
            output("内存使用超过限制 ({$usage}MB > {$memoryLimit}MB)，停止工作进程");
            break;
        }
    }
    
    output("工作进程结束，共处理 {$processed} 个任务");
    exit(0);
    
} catch (\Throwable $e) {
    output("错误: " . $e->getMessage());
    output("文件: " . $e->getFile() . ":" . $e->getLine());
    exit(1);
}
