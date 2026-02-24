<?php
/**
 * 示例任务：发送邮件
 * 
 * @package App\Jobs
 */

namespace App\Jobs;

use Core\Queue\Job;
use Core\Queue\JobInterface;

/**
 * 发送邮件任务
 */
class SendEmailJob extends Job implements JobInterface
{
    /**
     * 最大重试次数
     */
    protected int $maxTries = 3;
    
    /**
     * 超时时间
     */
    protected int $timeout = 60;
    
    /**
     * 处理任务
     */
    public function handle(): void
    {
        $to = $this->data['to'] ?? '';
        $subject = $this->data['subject'] ?? '';
        $content = $this->data['content'] ?? '';
        
        if (empty($to) || empty($subject)) {
            throw new \InvalidArgumentException('缺少必要的邮件参数');
        }
        
        // 使用系统邮件发送函数
        $errorMsg = '';
        $result = send_system_mail($to, $subject, $content, $errorMsg);
        
        if (!$result) {
            throw new \RuntimeException('邮件发送失败: ' . $errorMsg);
        }
        
        // 记录日志
        error_log("邮件发送成功: {$to}");
    }
    
    /**
     * 任务失败处理
     */
    public function failed(\Throwable $e): void
    {
        $to = $this->data['to'] ?? 'unknown';
        error_log("邮件任务失败 [{$to}]: " . $e->getMessage());
        
        // 可以在这里添加失败通知逻辑
        // 例如：发送通知给管理员、记录到失败日志表等
    }
}
