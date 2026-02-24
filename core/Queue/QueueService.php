<?php
/**
 * 消息队列服务
 * 支持异步任务处理、延迟任务、重试机制
 * 
 * @package Core\Queue
 * @version 1.0.0
 */

namespace Core\Queue;

use app\services\Database;
use PDO;

/**
 * 队列任务接口
 */
interface JobInterface
{
    /**
     * 处理任务
     */
    public function handle(): void;

    /**
     * 任务失败处理
     */
    public function failed(\Throwable $e): void;

    /**
     * 获取任务标识
     */
    public function getJobId(): string;

    /**
     * 获取最大重试次数
     */
    public function getMaxTries(): int;

    /**
     * 获取超时时间（秒）
     */
    public function getTimeout(): int;
}

/**
 * 队列服务类
 */
class QueueService
{
    /**
     * 默认队列名称
     */
    protected string $defaultQueue = 'default';

    /**
     * 最大重试次数
     */
    protected int $maxTries = 3;

    /**
     * 重试延迟（秒）
     */
    protected int $retryDelay = 60;

    /**
     * 任务超时时间（秒）
     */
    protected int $timeout = 300;

    /**
     * 失败任务保留时间（天）
     */
    protected int $failedJobRetention = 30;

    /**
     * 数据库表名
     */
    protected string $table = 'queue_jobs';

    /**
     * 失败任务表名
     */
    protected string $failedTable = 'queue_failed_jobs';

    /**
     * 构造函数
     */
    public function __construct(array $config = [])
    {
        if (isset($config['default_queue'])) {
            $this->defaultQueue = $config['default_queue'];
        }
        if (isset($config['max_tries'])) {
            $this->maxTries = $config['max_tries'];
        }
        if (isset($config['retry_delay'])) {
            $this->retryDelay = $config['retry_delay'];
        }
        if (isset($config['timeout'])) {
            $this->timeout = $config['timeout'];
        }
        if (isset($config['table'])) {
            $this->table = $config['table'];
        }
    }

    /**
     * 推送任务到队列
     */
    public function push($job, array $data = [], ?string $queue = null): string
    {
        $queue = $queue ?? $this->defaultQueue;
        $payload = $this->createPayload($job, $data);

        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}{$this->table}` 
                (queue, payload, attempts, reserved_at, available_at, created_at, status) 
                VALUES (:queue, :payload, 0, NULL, :available_at, :created_at, 'pending')";

        $stmt = $pdo->prepare($sql);
        $now = time();
        $stmt->execute([
            ':queue' => $queue,
            ':payload' => $payload,
            ':available_at' => $now,
            ':created_at' => $now,
        ]);

        return (string)$pdo->lastInsertId();
    }

    /**
     * 推送延迟任务
     */
    public function later(int $delay, $job, array $data = [], ?string $queue = null): string
    {
        $queue = $queue ?? $this->defaultQueue;
        $payload = $this->createPayload($job, $data);

        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}{$this->table}` 
                (queue, payload, attempts, reserved_at, available_at, created_at, status) 
                VALUES (:queue, :payload, 0, NULL, :available_at, :created_at, 'pending')";

        $stmt = $pdo->prepare($sql);
        $now = time();
        $stmt->execute([
            ':queue' => $queue,
            ':payload' => $payload,
            ':available_at' => $now + $delay,
            ':created_at' => $now,
        ]);

        return (string)$pdo->lastInsertId();
    }

    /**
     * 弹出任务
     */
    public function pop(?string $queue = null): ?array
    {
        $queue = $queue ?? $this->defaultQueue;
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        // 获取可用任务
        $sql = "SELECT * FROM `{$prefix}{$this->table}` 
                WHERE queue = :queue 
                AND status = 'pending' 
                AND available_at <= :now 
                ORDER BY created_at ASC 
                LIMIT 1 
                FOR UPDATE";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':queue' => $queue,
            ':now' => time(),
        ]);

        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$job) {
            return null;
        }

        // 标记为处理中
        $updateSql = "UPDATE `{$prefix}{$this->table}` 
                      SET status = 'processing', 
                          reserved_at = :reserved_at,
                          attempts = attempts + 1 
                      WHERE id = :id";

        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([
            ':reserved_at' => time(),
            ':id' => $job['id'],
        ]);

        return [
            'id' => $job['id'],
            'queue' => $job['queue'],
            'payload' => json_decode($job['payload'], true),
            'attempts' => $job['attempts'] + 1,
        ];
    }

    /**
     * 处理任务成功
     */
    public function ack(int $jobId): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "DELETE FROM `{$prefix}{$this->table}` WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id' => $jobId]);
    }

    /**
     * 处理任务失败
     */
    public function fail(int $jobId, string $error, array $payload = []): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        try {
            $pdo->beginTransaction();

            // 获取任务信息
            $sql = "SELECT * FROM `{$prefix}{$this->table}` WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $jobId]);
            $job = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$job) {
                $pdo->rollBack();
                return false;
            }

            // 检查是否需要重试
            $maxTries = $payload['maxTries'] ?? $this->maxTries;
            if ($job['attempts'] < $maxTries) {
                // 重新放入队列
                $retrySql = "UPDATE `{$prefix}{$this->table}` 
                            SET status = 'pending', 
                                reserved_at = NULL,
                                available_at = :available_at 
                            WHERE id = :id";
                $retryStmt = $pdo->prepare($retrySql);
                $retryStmt->execute([
                    ':available_at' => time() + $this->retryDelay,
                    ':id' => $jobId,
                ]);
            } else {
                // 移动到失败队列
                $this->moveToFailedQueue($job, $error);
            }

            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            return false;
        }
    }

    /**
     * 移动到失败队列
     */
    protected function moveToFailedQueue(array $job, string $error): void
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}{$this->failedTable}` 
                (queue, payload, exception, failed_at) 
                VALUES (:queue, :payload, :exception, :failed_at)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':queue' => $job['queue'],
            ':payload' => $job['payload'],
            ':exception' => $error,
            ':failed_at' => time(),
        ]);

        // 从主队列删除
        $deleteSql = "DELETE FROM `{$prefix}{$this->table}` WHERE id = :id";
        $deleteStmt = $pdo->prepare($deleteSql);
        $deleteStmt->execute([':id' => $job['id']]);
    }

    /**
     * 重试失败任务
     */
    public function retry(int $failedJobId): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}{$this->failedTable}` WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $failedJobId]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$job) {
            return false;
        }

        // 重新放入队列
        $this->push(
            json_decode($job['payload'], true)['job'],
            json_decode($job['payload'], true)['data'] ?? [],
            $job['queue']
        );

        // 删除失败记录
        $deleteSql = "DELETE FROM `{$prefix}{$this->failedTable}` WHERE id = :id";
        $deleteStmt = $pdo->prepare($deleteSql);
        $deleteStmt->execute([':id' => $failedJobId]);

        return true;
    }

    /**
     * 获取队列大小
     */
    public function size(?string $queue = null): int
    {
        $queue = $queue ?? $this->defaultQueue;
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT COUNT(*) FROM `{$prefix}{$this->table}` 
                WHERE queue = :queue AND status = 'pending'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':queue' => $queue]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * 获取失败任务数量
     */
    public function failedCount(?string $queue = null): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        if ($queue) {
            $sql = "SELECT COUNT(*) FROM `{$prefix}{$this->failedTable}` WHERE queue = :queue";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':queue' => $queue]);
        } else {
            $sql = "SELECT COUNT(*) FROM `{$prefix}{$this->failedTable}`";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
        }

        return (int)$stmt->fetchColumn();
    }

    /**
     * 清空队列
     */
    public function clear(?string $queue = null): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        if ($queue) {
            $sql = "DELETE FROM `{$prefix}{$this->table}` WHERE queue = :queue";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':queue' => $queue]);
        } else {
            $sql = "DELETE FROM `{$prefix}{$this->table}`";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
        }

        return $stmt->rowCount();
    }

    /**
     * 创建任务载荷
     */
    protected function createPayload($job, array $data): string
    {
        if (is_object($job)) {
            $jobClass = get_class($job);
        } elseif (is_string($job)) {
            $jobClass = $job;
        } else {
            throw new \InvalidArgumentException('Invalid job type');
        }

        return json_encode([
            'job' => $jobClass,
            'data' => $data,
            'maxTries' => $this->maxTries,
            'timeout' => $this->timeout,
            'createdAt' => time(),
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * 处理单个任务
     */
    public function processJob(array $job): bool
    {
        $jobClass = $job['payload']['job'];
        $jobData = $job['payload']['data'] ?? [];

        try {
            // 实例化任务
            if (class_exists($jobClass)) {
                $instance = new $jobClass($jobData);
            } else {
                throw new \RuntimeException("Job class {$jobClass} not found");
            }

            // 执行任务
            if (method_exists($instance, 'handle')) {
                $instance->handle();
            }

            // 标记成功
            $this->ack($job['id']);
            return true;
        } catch (\Throwable $e) {
            // 标记失败
            $this->fail($job['id'], $e->getMessage(), $job['payload']);

            // 调用失败回调
            if (isset($instance) && method_exists($instance, 'failed')) {
                $instance->failed($e);
            }

            return false;
        }
    }

    /**
     * 运行队列工作进程
     */
    public function work(?string $queue = null, int $sleep = 1, int $maxJobs = 0): void
    {
        $queue = $queue ?? $this->defaultQueue;
        $processed = 0;

        while (true) {
            $job = $this->pop($queue);

            if ($job) {
                $this->processJob($job);
                $processed++;

                if ($maxJobs > 0 && $processed >= $maxJobs) {
                    break;
                }
            } else {
                sleep($sleep);
            }

            // 检查内存
            if (memory_get_usage() > 512 * 1024 * 1024) {
                break;
            }
        }
    }
}

/**
 * 基础任务类
 */
abstract class Job implements JobInterface
{
    /**
     * 任务数据
     */
    protected array $data = [];

    /**
     * 任务ID
     */
    protected string $jobId;

    /**
     * 最大重试次数
     */
    protected int $maxTries = 3;

    /**
     * 超时时间
     */
    protected int $timeout = 300;

    /**
     * 构造函数
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
        $this->jobId = uniqid('job_', true);
    }

    /**
     * 任务失败处理
     */
    public function failed(\Throwable $e): void
    {
        // 记录日志
        error_log("Job {$this->jobId} failed: " . $e->getMessage());
    }

    /**
     * 获取任务标识
     */
    public function getJobId(): string
    {
        return $this->jobId;
    }

    /**
     * 获取最大重试次数
     */
    public function getMaxTries(): int
    {
        return $this->maxTries;
    }

    /**
     * 获取超时时间
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * 获取任务数据
     */
    public function getData(): array
    {
        return $this->data;
    }
}

/**
 * 可队列化的Trait
 */
trait Queueable
{
    /**
     * 队列名称
     */
    protected string $queue = 'default';

    /**
     * 延迟时间
     */
    protected int $delay = 0;

    /**
     * 设置队列
     */
    public function onQueue(string $queue): self
    {
        $this->queue = $queue;
        return $this;
    }

    /**
     * 设置延迟
     */
    public function delay(int $seconds): self
    {
        $this->delay = $seconds;
        return $this;
    }

    /**
     * 分发任务
     */
    public function dispatch(): string
    {
        $queue = app('queue');
        
        if ($this->delay > 0) {
            return $queue->later($this->delay, $this, [], $this->queue);
        }
        
        return $queue->push($this, [], $this->queue);
    }
}
