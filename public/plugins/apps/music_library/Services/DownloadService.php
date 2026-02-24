<?php

namespace plugins\apps\music_library\Services;

use plugins\apps\music_library\Models\MusicModel;
use PDO;

/**
 * 音乐下载服务 (Download Service)
 *
 * 这是一个特定于音乐插件的业务服务。它负责处理与音乐下载相关的所有逻辑，
 * 包括权限检查和处理下载请求。
 *
 * ---
 * 架构说明:
 * 在一个更复杂的系统中，这个类可能会继承一个核心的 `BaseDownloadService`。
 * 这种设计允许将通用逻辑（如全局下载频率限制、用户积分扣除、下载日志记录）放在基类中，
 * 而插件特定的逻辑（如检查音乐是否需要VIP）则在当前这个子类中实现。
 *
 * 由于核心服务不存在，此类目前作为独立服务运行，并包含了所有必要的逻辑（以简化形式）。
 * ---
 */
class DownloadService
{
    /**
     * @var MusicModel 音乐数据模型实例。
     */
    private $musicModel;

    /**
     * @var array 插件的配置信息。
     */
    private $pluginConfig;

    /**
     * 构造函数 - 依赖注入
     *
     * @param MusicModel $musicModel 音乐数据模型。
     * @param array $pluginConfig 插件的配置数组。
     */
    public function __construct(MusicModel $musicModel, array $pluginConfig)
    {
        $this->musicModel = $musicModel;
        $this->pluginConfig = $pluginConfig;
    }

    /**
     * 检查下载特定音乐的权限。
     *
     * @param int $userId 用户ID。
     * @param int $trackId 曲目ID。
     * @return array 包含 'allowed' (bool) 和 'reason' (string) 的数组。
     */
    public function checkMusicDownloadPermission($userId, $trackId)
    {
        $track = $this->musicModel->getTrack($trackId);
        if (!$track) {
            return ['allowed' => false, 'reason' => '曲目未找到或未发布。'];
        }
        
        // 调用内部实现的通用权限检查方法。
        return $this->checkDownloadPermissions($userId, $trackId, 'music', $track);
    }

    /**
     * 处理音乐下载请求。
     *
     * @param int $userId 用户ID。
     * @param int $trackId 曲目ID。
     * @param string $format 请求的下载格式 (e.g., 'mp3', 'flac')。
     * @return array 包含 'success' (bool) 和 'message'/'url' (string) 的数组。
     */
    public function processMusicDownload($userId, $trackId, $format)
    {
        $track = $this->musicModel->getTrack($trackId);
        if (!$track) {
            return ['success' => false, 'message' => '曲目未找到或未发布。'];
        }
        
        // 调用内部实现的通用下载处理方法。
        return $this->processDownload($userId, $trackId, 'music', $format, $track, $this->pluginConfig);
    }

    /**
     * (占位符) 通用权限检查逻辑。
     *
     * @param int $userId 用户ID。
     * @param int $itemId 项目ID (这里是 trackId)。
     * @param string $itemType 项目类型 (e.g., 'music')。
     * @param array $itemData 项目的具体数据。
     * @return array 权限检查结果。
     */
    private function checkDownloadPermissions($userId, $itemId, $itemType, $itemData)
    {
        // 存根(STUB): 这里是通用权限检查逻辑的简化实现。
        // 真实场景下，这里会包含复杂的逻辑：
        // 1. 检查用户是否登录 ($userId)。
        // 2. 检查用户所属的用户组是否有下载权限。
        // 3. 检查下载此特定音乐是否需要VIP权限 (e.g., if ($itemData['requires_vip'] && !is_vip($userId)))。
        // 4. 检查用户的下载次数是否达到每日上限。
        // 5. 检查下载是否需要消耗积分，以及用户积分是否足够。

        // 简化逻辑：假设所有音乐都需要登录才能下载。
        if (!$userId) {
            return ['allowed' => false, 'reason' => '请登录后下载。'];
        }

        return ['allowed' => true, 'reason' => ''];
    }

    /**
     * (占位符) 通用下载处理逻辑。
     *
     * @param int $userId 用户ID。
     * @param int $itemId 项目ID。
     * @param string $itemType 项目类型。
     * @param string $format 请求的格式。
     * @param array $itemData 项目数据。
     * @param array $config 插件配置。
     * @return array 下载处理结果。
     */
    private function processDownload($userId, $itemId, $itemType, $format, $itemData, $config)
    {
        // 存根(STUB): 这里是通用下载处理逻辑的简化实现。
        // 真实场景下，这里会：
        // 1. 再次进行权限检查。
        // 2. 根据 $itemData['file_path'] 和 $format 确定最终的文件路径。
        // 3. 生成一个有时效性的、带签名的下载链接，以防止盗链。
        // 4. 记录下载日志到数据库。
        // 5. 更新用户的下载统计或扣除积分。

        // 简化逻辑：直接返回一个模拟的文件URL。
        $file_url = '/path/to/music/' . ($itemData['file_name'] ?? $itemId . '.mp3');
        
        return ['success' => true, 'url' => $file_url];
    }
}