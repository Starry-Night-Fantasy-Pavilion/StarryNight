<?php

declare(strict_types=1);

namespace api\controllers;

use app\models\UserStorageQuota;
use app\models\FileHash;

/**
 * 存储相关API控制器
 */
class StorageApiController extends BaseApiController
{
    /**
     * 获取存储使用情况
     */
    public function getUsage(): void
    {
        $userId = $this->requireAuth();
        $quota = UserStorageQuota::findByUserId($userId);

        if (!$quota) {
            $this->error('存储配额信息不存在');
        }

        $usedSpace = (int)($quota['used_space'] ?? 0);
        $totalQuota = (int)($quota['total_quota'] ?? 0);

        $this->success([
            'used_space' => $usedSpace,
            'total_quota' => $totalQuota,
            'remaining_space' => $totalQuota - $usedSpace,
            'usage_percentage' => $totalQuota > 0 ? round(($usedSpace / $totalQuota) * 100, 2) : 0,
            'is_over_quota' => UserStorageQuota::isOverQuota($userId),
            'formatted_used_space' => UserStorageQuota::formatBytes($usedSpace),
            'formatted_total_quota' => UserStorageQuota::formatBytes($totalQuota),
            'formatted_remaining_space' => UserStorageQuota::formatBytes($totalQuota - $usedSpace)
        ]);
    }

    /**
     * 文件上传处理
     */
    public function upload(): void
    {
        $userId = $this->requireAuth();

        if (UserStorageQuota::isOverQuota($userId)) {
            $this->error('存储空间已超出配额限制');
        }

        $file = $this->validateUpload();
        $filePath = $this->processFileUpload($file, $userId);

        try {
            $fileRecord = FileHash::getOrCreateFile($filePath, $userId);
            UserStorageQuota::addUsedSpace($userId, $file['size']);

            $this->success([
                'file_id' => $fileRecord['id'],
                'file_name' => basename($filePath),
                'file_size' => $file['size'],
                'file_path' => $filePath
            ], '文件上传成功');
        } catch (\Exception $e) {
            $this->cleanupFailedUpload($filePath);
            $this->error('文件处理失败: ' . $e->getMessage());
        }
    }

    /**
     * 删除文件
     */
    public function deleteFile(): void
    {
        $userId = $this->requireAuth();
        $fileId = $this->postInt('file_id');

        if (!$fileId) {
            $this->error('无效的文件ID');
        }

        $file = FileHash::findById($fileId);
        if (!$file || $file['upload_user_id'] != $userId) {
            $this->error($file ? '无权限删除此文件' : '文件不存在');
        }

        try {
            $this->deletePhysicalFile($file['file_path']);
            FileHash::decrementReference($fileId);
            UserStorageQuota::subtractUsedSpace($userId, (int)$file['file_size']);

            $this->success(null, '文件删除成功');
        } catch (\Exception $e) {
            $this->error('文件删除失败: ' . $e->getMessage());
        }
    }

    /**
     * 处理文件上传
     */
    private function processFileUpload(array $file, int $userId): string
    {
        $uploadPath = $this->getUploadPath($userId);

        if (!is_dir($uploadPath) && !mkdir($uploadPath, 0755, true)) {
            $this->error('创建上传目录失败');
        }

        $fileName = time() . '_' . $file['name'];
        $filePath = $uploadPath . '/' . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            $this->error('文件上传失败');
        }

        return $filePath;
    }

    /**
     * 删除物理文件
     */
    private function deletePhysicalFile(string $filePath): void
    {
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * 清理失败的上传
     */
    private function cleanupFailedUpload(string $filePath): void
    {
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }
}
