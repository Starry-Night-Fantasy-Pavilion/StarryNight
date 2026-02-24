<?php

namespace plugins\apps\music_library\Services;

use plugins\apps\music_library\Models\MusicModel;
use PDO;

/**
 * 音乐服务层 (Music Service)
 *
 * 服务层是业务逻辑的核心。它协调模型（数据访问）和控制器（用户输入）之间的操作。
 * 将业务逻辑（如计算分页、组合来自不同模型的数据等）放在服务层，
 * 可以使控制器更轻量，逻辑更清晰，也更容易进行单元测试。
 */
class MusicService
{
    /**
     * @var MusicModel
     */
    private $musicModel;

    /**
     * MusicService constructor.
     *
     * @param PDO $db The database connection.
     * @param string $db_prefix The database table prefix.
     */
    public function __construct(PDO $db, string $db_prefix)
    {
        $this->musicModel = new MusicModel($db, $db_prefix);
    }

    /**
     * 获取格式化的曲目列表，包含分页信息。
     *
     * @param array $params Request parameters, expecting 'limit' and 'page'.
     * @return array An array containing the list of tracks and pagination details.
     */
    public function getTracks(array $params): array
    {
        $limit = (int)($params['limit'] ?? 10);
        $page = (int)($params['page'] ?? 1);
        $offset = ($page - 1) * $limit;
        
        // 从模型获取数据
        $tracks = $this->musicModel->getTracks($limit, $offset);
        // 注意：为了实现准确的分页，模型层最好能提供一个 count 方法。
        // 这里我们暂时用一个简化的方式，但在真实应用中应调用如 $this->musicModel->countTracks() 的方法。
        $total = $this->musicModel->countSearchTracks(''); // 假设一个方法来获取总数

        return [
            'musics' => $tracks,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'totalPages' => ceil($total / $limit)
            ]
        ];
    }

    /**
     * 获取单曲详情。
     *
     * @param int $musicId The ID of the track.
     * @return array|null The track details or null if not found.
     */
    public function getTrackDetails(int $musicId): ?array
    {
        return $this->musicModel->getTrack($musicId);
    }


    /**
     * 提交评论 (存根方法).
     *
     * @param int $musicId The ID of the track.
     * @param int $userId The ID of the user submitting the comment.
     * @param string $content The comment content.
     * @return int The new comment's ID.
     */
    public function submitComment(int $musicId, int $userId, string $content): int
    {
        // 存根(STUB): 这是一个简化的示例。
        // 真实实现会调用模型层的方法，如 `addComment`，并进行内容验证。
        // 例如: return $this->musicModel->addComment($musicId, $userId, $content);
        return 123; // 返回一个模拟的评论ID
    }

    /**
     * 获取排行榜数据 (存根方法).
     *
     * @param string $type The type of ranking (e.g., 'plays', 'likes').
     * @param string $period The time period (e.g., 'weekly', 'monthly').
     * @return array A list of ranked tracks.
     */
    public function getRankings(string $type, string $period): array
    {
        // 存根(STUB): MusicModel 目前没有复杂的排行榜查询方法。
        // 我们使用已有的 `getPopularTracks` 作为替代。
        // 真实实现可能需要根据类型和周期构建更复杂的SQL查询。
        return $this->musicModel->getPopularTracks(10);
    }
}
