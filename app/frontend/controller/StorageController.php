<?php

namespace app\frontend\controller;

use app\models\UserStorageQuota;
use app\models\StorageConfig;
use app\models\StorageCleanupLog;
use app\models\FileHash;
use Core\Controller;

class StorageController extends Controller
{
    /**
     * 存储管理页面
     */
    public function index()
    {
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        $userId = $_SESSION['user_id'];
        
        // 获取用户存储配额信息
        $quota = UserStorageQuota::findByUserId($userId);
        
        // 如果没有配额记录，创建默认配额
        if (!$quota) {
            // 获取用户会员等级
            $user = \app\models\User::find($userId);
            $membershipLevel = $user['membership_level'] ?? '普通会员';
            
            // 获取对应会员等级的配额
            $defaultQuota = UserStorageQuota::getQuotaByMembershipLevel($membershipLevel);
            if ($defaultQuota) {
                UserStorageQuota::createOrUpdate($userId, $membershipLevel, $defaultQuota['total_quota']);
                $quota = UserStorageQuota::findByUserId($userId);
            }
        }
        
        // 计算使用情况
        $usedSpace = $quota['used_space'] ?? 0;
        $totalQuota = $quota['total_quota'] ?? 0;
        $remainingSpace = $totalQuota - $usedSpace;
        $usagePercentage = $totalQuota > 0 ? round(($usedSpace / $totalQuota) * 100, 2) : 0;
        
        // 获取用户文件列表
        $userFiles = FileHash::getFilesByUser($userId);
        
        $this->view('storage/index', [
            'quota' => $quota,
            'usedSpace' => $usedSpace,
            'totalQuota' => $totalQuota,
            'remainingSpace' => $remainingSpace,
            'usagePercentage' => $usagePercentage,
            'userFiles' => $userFiles,
            'isOverQuota' => UserStorageQuota::isOverQuota($userId)
        ]);
    }
    
    /**
     * 获取存储使用情况 API
     */
    public function api()
    {
        if (!$this->isLoggedIn()) {
            $this->json(['success' => false, 'message' => '请先登录']);
            return;
        }

        $userId = $_SESSION['user_id'];
        
        // 获取用户存储配额信息
        $quota = UserStorageQuota::findByUserId($userId);
        
        if (!$quota) {
            $this->json(['success' => false, 'message' => '存储配额信息不存在']);
            return;
        }
        
        // 计算使用情况
        $usedSpace = $quota['used_space'] ?? 0;
        $totalQuota = $quota['total_quota'] ?? 0;
        $remainingSpace = $totalQuota - $usedSpace;
        $usagePercentage = $totalQuota > 0 ? round(($usedSpace / $totalQuota) * 100, 2) : 0;
        
        $this->json([
            'success' => true,
            'data' => [
                'used_space' => $usedSpace,
                'total_quota' => $totalQuota,
                'remaining_space' => $remainingSpace,
                'usage_percentage' => $usagePercentage,
                'is_over_quota' => UserStorageQuota::isOverQuota($userId),
                'formatted_used_space' => UserStorageQuota::formatBytes($usedSpace),
                'formatted_total_quota' => UserStorageQuota::formatBytes($totalQuota),
                'formatted_remaining_space' => UserStorageQuota::formatBytes($remainingSpace)
            ]
        ]);
    }
    
    /**
     * 文件上传处理
     */
    public function upload()
    {
        if (!$this->isLoggedIn()) {
            $this->json(['success' => false, 'message' => '请先登录']);
            return;
        }

        $userId = $_SESSION['user_id'];
        
        // 检查存储配额
        if (UserStorageQuota::isOverQuota($userId)) {
            $this->json(['success' => false, 'message' => '存储空间已超出配额限制']);
            return;
        }
        
        if (!isset($_FILES['file'])) {
            $this->json(['success' => false, 'message' => '没有上传文件']);
            return;
        }
        
        $file = $_FILES['file'];
        $uploadPath = $this->getUploadPath($userId);
        
        try {
            // 确保上传目录存在
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            
            // 生成唯一文件名
            $fileName = time() . '_' . $file['name'];
            $filePath = $uploadPath . '/' . $fileName;
            
            // 移动上传的文件
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                // 创建文件哈希记录
                $fileRecord = FileHash::getOrCreateFile($filePath, $userId);
                
                // 更新用户存储使用量
                UserStorageQuota::addUsedSpace($userId, $file['size']);
                
                $this->json([
                    'success' => true,
                    'message' => '文件上传成功',
                    'data' => [
                        'file_id' => $fileRecord['id'],
                        'file_name' => $fileName,
                        'file_size' => $file['size'],
                        'file_path' => $filePath
                    ]
                ]);
            } else {
                $this->json(['success' => false, 'message' => '文件上传失败']);
            }
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => '文件上传失败: ' . $e->getMessage()]);
        }
    }
    
    /**
     * 删除文件
     */
    public function deleteFile()
    {
        if (!$this->isLoggedIn()) {
            $this->json(['success' => false, 'message' => '请先登录']);
            return;
        }

        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => '请求方法错误']);
            return;
        }

        $userId = $_SESSION['user_id'];
        $fileId = intval($_POST['file_id'] ?? 0);
        
        if (!$fileId) {
            $this->json(['success' => false, 'message' => '无效的文件ID']);
            return;
        }
        
        $file = FileHash::findById($fileId);
        if (!$file) {
            $this->json(['success' => false, 'message' => '文件不存在']);
            return;
        }
        
        if ($file['upload_user_id'] != $userId) {
            $this->json(['success' => false, 'message' => '无权限删除此文件']);
            return;
        }
        
        try {
            // 删除物理文件
            if (file_exists($file['file_path'])) {
                unlink($file['file_path']);
            }
            
            // 减少引用计数
            FileHash::decrementReference($fileId);
            
            // 更新用户存储使用量
            UserStorageQuota::subtractUsedSpace($userId, $file['file_size']);
            
            $this->json(['success' => true, 'message' => '文件删除成功']);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => '文件删除失败: ' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取存储配置（管理员）
     */
    public function config()
    {
        if (!$this->isLoggedIn()) {
            $this->json(['success' => false, 'message' => '请先登录']);
            return;
        }
        
        if (!$this->isAdmin()) {
            $this->json(['success' => false, 'message' => '权限不足']);
            return;
        }
        
        $configs = StorageConfig::getAll();
        $activeConfig = StorageConfig::getActive();
        
        $this->json([
            'success' => true,
            'data' => [
                'configs' => $configs,
                'active_config' => $activeConfig
            ]
        ]);
    }
    
    /**
     * 更新存储配置（管理员）
     */
    public function updateConfig()
    {
        if (!$this->isLoggedIn()) {
            $this->json(['success' => false, 'message' => '请先登录']);
            return;
        }
        
        if (!$this->isAdmin()) {
            $this->json(['success' => false, 'message' => '权限不足']);
            return;
        }
        
        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => '请求方法错误']);
            return;
        }
        
        $storageType = $_POST['storage_type'] ?? 'local';
        $configName = $_POST['config_name'] ?? '';
        $configValue = $_POST['config_value'] ?? '{}';
        
        if (empty($configName)) {
            $this->json(['success' => false, 'message' => '配置名称不能为空']);
            return;
        }
        
        // 验证配置值是否为有效的JSON
        json_decode($configValue);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->json(['success' => false, 'message' => '配置值必须为有效的JSON格式']);
            return;
        }
        
        try {
            $data = [
                'storage_type' => $storageType,
                'config_name' => $configName,
                'config_value' => $configValue
            ];
            
            $config = StorageConfig::findByType($storageType);
            if ($config) {
                $success = StorageConfig::update($config['id'], $data);
            } else {
                $success = StorageConfig::create($data);
            }
            
            if ($success) {
                $this->json(['success' => true, 'message' => '配置更新成功']);
            } else {
                $this->json(['success' => false, 'message' => '配置更新失败']);
            }
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => '配置更新失败: ' . $e->getMessage()]);
        }
    }
    
    /**
     * 设置激活存储配置（管理员）
     */
    public function setActiveConfig()
    {
        if (!$this->isLoggedIn()) {
            $this->json(['success' => false, 'message' => '请先登录']);
            return;
        }
        
        if (!$this->isAdmin()) {
            $this->json(['success' => false, 'message' => '权限不足']);
            return;
        }
        
        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => '请求方法错误']);
            return;
        }
        
        $configId = intval($_POST['config_id'] ?? 0);
        if (!$configId) {
            $this->json(['success' => false, 'message' => '无效的配置ID']);
            return;
        }
        
        try {
            $success = StorageConfig::setActive($configId);
            if ($success) {
                $this->json(['success' => true, 'message' => '配置激活成功']);
            } else {
                $this->json(['success' => false, 'message' => '配置激活失败']);
            }
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => '配置激活失败: ' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取清理日志（管理员）
     */
    public function cleanupLogs()
    {
        if (!$this->isLoggedIn()) {
            $this->json(['success' => false, 'message' => '请先登录']);
            return;
        }
        
        if (!$this->isAdmin()) {
            $this->json(['success' => false, 'message' => '权限不足']);
            return;
        }
        
        $cleanupType = $_GET['cleanup_type'] ?? null;
        $limit = intval($_GET['limit'] ?? 50);
        
        $logs = StorageCleanupLog::getAll($cleanupType, $limit);
        $stats = StorageCleanupLog::getStats($cleanupType);
        $totalStats = StorageCleanupLog::getTotalStats();
        
        $this->json([
            'success' => true,
            'data' => [
                'logs' => $logs,
                'stats' => $stats,
                'total_stats' => $totalStats
            ]
        ]);
    }
    
    /**
     * 手动执行清理（管理员）
     */
    public function runCleanup()
    {
        if (!$this->isLoggedIn()) {
            $this->json(['success' => false, 'message' => '请先登录']);
            return;
        }
        
        if (!$this->isAdmin()) {
            $this->json(['success' => false, 'message' => '权限不足']);
            return;
        }
        
        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => '请求方法错误']);
            return;
        }
        
        $cleanupType = $_POST['cleanup_type'] ?? 'all';
        
        try {
            // 这里可以调用清理脚本
            $scriptPath = __DIR__ . '/../../scripts/storage_cleanup.php';
            $command = "php {$scriptPath} {$cleanupType} 2>&1";
            
            $output = shell_exec($command);
            $result = json_decode($output, true);
            
            if ($result) {
                $this->json([
                    'success' => true, 
                    'message' => '清理任务执行成功',
                    'data' => $result
                ]);
            } else {
                $this->json(['success' => false, 'message' => '清理任务执行失败']);
            }
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => '清理任务执行失败: ' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取上传路径
     */
    private function getUploadPath(int $userId): string
    {
        $config = StorageConfig::getActive();
        if ($config && $config['storage_type'] === 'local') {
            $configValue = json_decode($config['config_value'], true);
            $basePath = $configValue['base_path'] ?? '/data';
        } else {
            $basePath = '/data';
        }
        
        return $basePath . '/uploads/users/' . $userId;
    }
}