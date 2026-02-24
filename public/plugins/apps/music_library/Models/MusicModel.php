<?php

namespace plugins\apps\music_library\Models;

use PDO;
use PDOException;

/**
 * 音乐库的数据模型 (Music Model)
 *
 * 这是插件的数据访问层 (Data Access Layer)。它封装了所有与数据库相关的操作，
 * 为控制器提供了一个清晰、高级的接口来获取和操作数据。
 * 这种设计将业务逻辑（在控制器中）与数据访问逻辑（在模型中）分离开来，
 * 是 MVC 架构模式的核心实践。
 */
class MusicModel
{
    /**
     * @var PDO 数据库连接实例。
     */
    private $db;

    /**
     * @var string 数据库表前缀。
     */
    private $db_prefix;

    /**
     * @var bool 标记插件所需的数据表是否已经就绪。
     *           如果未安装 music_library 的表，则为 false，模型将进入“仅外部 API 模式”，避免抛出致命错误。
     */
    private $dbReady = false;

    /**
     * 构造函数 - 依赖注入
     *
     * @param PDO    $db        一个已实例化的 PDO 对象。模型不关心这个对象如何创建，只负责使用它。
     * @param string $db_prefix 数据库表前缀。
     */
    public function __construct(PDO $db, $db_prefix)
    {
        $this->db = $db;
        $this->db_prefix = $db_prefix;
        $this->initializeDatabaseState();
    }

    /**
     * 初始化数据库可用状态：
     * - 如果插件的数据表（例如 music_tracks）不存在，则捕获异常并将 $dbReady 设为 false，避免前台报致命错误。
     * - 如果存在，则标记为 true，正常使用本地曲库功能。
     */
    private function initializeDatabaseState(): void
    {
        try {
            // 仅做一次轻量检测：查询任意一行即可
            $sql = "SELECT 1 FROM " . $this->getTableName('music_tracks') . " LIMIT 1";
            $this->db->query($sql);
            $this->dbReady = true;
        } catch (PDOException $e) {
            // 42S02: Base table or view not found
            if ($e->getCode() === '42S02') {
                error_log('[music_library] 数据表 ' . $this->db_prefix . "music_tracks 不存在，插件将以“仅外部 API 模式”运行（不使用本地曲库表）。");
                $this->dbReady = false;
            } else {
                // 其他数据库错误仍然抛出，方便排查真实问题
                throw $e;
            }
        }
    }

    /**
     * 获取带前缀的完整表名。
     * @param string $name 表的基本名称。
     * @return string 完整的、带反引号的表名。
     */
    private function getTableName($name)
    {
        return '`' . $this->db_prefix . $name . '`';
    }
    
    /**
     * 私有辅助方法：执行一个 SELECT 查询并返回所有结果。
     * @param string $sql SQL 语句。
     * @param array $params 绑定的参数。
     * @return array|false 查询结果或在失败时返回 false。
     */
    private function query($sql, $params = [])
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * 私有辅助方法：执行一个 INSERT, UPDATE, 或 DELETE 语句。
     * @param string $sql SQL 语句。
     * @param array $params 绑定的参数。
     * @return int 受影响的行数。
     */
    private function execute($sql, $params = [])
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * 获取曲目列表（分页）。
     * @param int $limit 每页数量。
     * @param int $offset 起始位置。
     * @return array 曲目列表。
     */
    public function getTracks($limit = 20, $offset = 0)
    {
        // 如果未安装插件数据表，直接返回空数组，避免致命 SQL 错误
        if (!$this->dbReady) {
            return [];
        }

        $sql = "SELECT * FROM " . $this->getTableName('music_tracks') . " WHERE status = 'published' ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        // 注意: PDO 对 LIMIT 和 OFFSET 的占位符绑定可能因驱动而异，直接拼接整数是安全的。
        // 但为了统一，这里仍然使用命名占位符，并确保传入的是整数。
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * 获取单个曲目的详细信息。
     * @param int $id 曲目ID。
     * @return array|null 曲目信息或在未找到时返回 null。
     */
    public function getTrack($id)
    {
        if (!$this->dbReady) {
            return null;
        }

        $sql = "SELECT * FROM " . $this->getTableName('music_tracks') . " WHERE id = :id AND status = 'published'";
        $result = $this->query($sql, [':id' => $id]);
        return $result[0] ?? null;
    }

    /**
     * 获取所有不重复的音乐流派。
     * @return array 流派名称列表。
     */
    public function getGenres()
    {
        if (!$this->dbReady) {
            return [];
        }

        $sql = "SELECT DISTINCT genre FROM " . $this->getTableName('music_tracks') . " WHERE genre IS NOT NULL AND genre != '' ORDER BY genre";
        $results = $this->query($sql);
        // 使用 array_map 将结果数组 [ ['genre' => 'Pop'], ['genre' => 'Rock'] ] 转换为 ['Pop', 'Rock']
        return array_map(fn($row) => $row['genre'], $results);
    }

    /**
     * 获取所有不重复的音乐情绪。
     * @return array 情绪名称列表。
     */
    public function getMoods()
    {
        if (!$this->dbReady) {
            return [];
        }

        $sql = "SELECT DISTINCT mood FROM " . $this->getTableName('music_tracks') . " WHERE mood IS NOT NULL AND mood != '' ORDER BY mood";
        $results = $this->query($sql);
        return array_map(fn($row) => $row['mood'], $results);
    }

    /**
     * 获取热门曲目。
     * @param int $limit 返回数量。
     * @return array 热门曲目列表。
     */
    public function getPopularTracks($limit = 10)
    {
        if (!$this->dbReady) {
            return [];
        }

        $sql = "SELECT * FROM " . $this->getTableName('music_tracks') . " WHERE status = 'published' ORDER BY plays DESC, likes DESC LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * 获取特色专辑。
     * @param int $limit 返回数量。
     * @return array 特色专辑列表。
     */
    public function getFeaturedAlbums($limit = 6)
    {
        if (!$this->dbReady) {
            return [];
        }

        try {
            $sql = "SELECT * FROM " . $this->getTableName('albums') . " WHERE status = 'published' ORDER BY likes DESC, plays DESC LIMIT :limit";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            if ($e->getCode() === '42S02') {
                error_log('[music_library] 数据表 ' . $this->db_prefix . "albums 不存在，跳过推荐专辑查询。");
                return [];
            }
            throw $e;
        }
    }

    /**
     * 获取相关曲目（基于相同艺术家或流派）。
     * @param array $track 当前曲目的信息数组。
     * @param int $limit 返回数量。
     * @return array 相关曲目列表。
     */
    public function getRelatedTracks($track, $limit = 8)
    {
        if (!$this->dbReady) {
            return [];
        }

        $sql = "SELECT * FROM " . $this->getTableName('music_tracks') . " WHERE id != :id AND status = 'published' AND (artist = :artist OR genre = :genre) ORDER BY plays DESC LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $track['id'], PDO::PARAM_INT);
        $stmt->bindValue(':artist', $track['artist']);
        $stmt->bindValue(':genre', $track['genre']);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * 获取指定曲目的评论。
     * @param int $trackId 曲目ID。
     * @param int $limit 每页数量。
     * @param int $offset 起始位置。
     * @return array 评论列表。
     */
    public function getComments($trackId, $limit = 20, $offset = 0)
    {
        if (!$this->dbReady) {
            return [];
        }

        $sql = "SELECT * FROM " . $this->getTableName('music_comments') . " WHERE track_id = :track_id AND status = 'approved' ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':track_id', $trackId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * 获取单个专辑的详细信息。
     * @param int $id 专辑ID。
     * @return array|null 专辑信息或 null。
     */
    public function getAlbum($id)
    {
        if (!$this->dbReady) {
            return null;
        }

        try {
            $sql = "SELECT * FROM " . $this->getTableName('albums') . " WHERE id = :id AND status = 'published'";
            $result = $this->query($sql, [':id' => $id]);
            return $result[0] ?? null;
        } catch (PDOException $e) {
            if ($e->getCode() === '42S02') {
                error_log('[music_library] 数据表 ' . $this->db_prefix . "albums 不存在，无法获取专辑详情。");
                return null;
            }
            throw $e;
        }
    }

    /**
     * 获取指定专辑下的所有曲目。
     * @param int $albumId 专辑ID。
     * @return array 曲目列表。
     */
    public function getAlbumTracks($albumId)
    {
        if (!$this->dbReady) {
            return [];
        }

        try {
            $sql = "SELECT * FROM " . $this->getTableName('music_tracks') . " WHERE album_id = :album_id AND status = 'published' ORDER BY created_at";
            return $this->query($sql, [':album_id' => $albumId]);
        } catch (PDOException $e) {
            if ($e->getCode() === '42S02') {
                error_log('[music_library] 数据表 ' . $this->db_prefix . "music_tracks 不存在，无法获取专辑曲目。");
                return [];
            }
            throw $e;
        }
    }

    /**
     * 获取相关专辑。
     * @param array $album 当前专辑信息。
     * @param int $limit 返回数量。
     * @return array 相关专辑列表。
     */
    public function getRelatedAlbums($album, $limit = 6)
    {
        if (!$this->dbReady) {
            return [];
        }

        try {
            $sql = "SELECT * FROM " . $this->getTableName('albums') . " WHERE id != :id AND status = 'published' AND (artist = :artist OR genre = :genre) ORDER BY likes DESC LIMIT :limit";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $album['id'], PDO::PARAM_INT);
            $stmt->bindValue(':artist', $album['artist']);
            $stmt->bindValue(':genre', $album['genre']);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            if ($e->getCode() === '42S02') {
                error_log('[music_library] 数据表 ' . $this->db_prefix . "albums 不存在，无法获取相关专辑。");
                return [];
            }
            throw $e;
        }
    }

    /**
     * 获取播放列表信息。
     * @param int $id 播放列表ID。
     * @return array|null 播放列表信息或 null。
     */
    public function getPlaylist($id)
    {
        if (!$this->dbReady) {
            return null;
        }

        $sql = "SELECT * FROM " . $this->getTableName('user_playlists') . " WHERE id = :id";
        $result = $this->query($sql, [':id' => $id]);
        return $result[0] ?? null;
    }

    /**
     * 获取播放列表中的所有曲目。
     * @param int $playlistId 播放列表ID。
     * @return array 曲目列表。
     */
    public function getPlaylistTracks($playlistId)
    {
        if (!$this->dbReady) {
            return [];
        }

        $sql = "SELECT t.*, pt.position FROM " . $this->getTableName('playlist_tracks') . " pt
                  JOIN " . $this->getTableName('music_tracks') . " t ON pt.track_id = t.id
                  WHERE pt.playlist_id = :playlist_id AND t.status = 'published'
                  ORDER BY pt.position";
        return $this->query($sql, [':playlist_id' => $playlistId]);
    }

    /**
     * 搜索曲目。
     * @param string $query 搜索关键词。
     * @param string $genre 按流派过滤。
     * @param string $mood 按情绪过滤。
     * @param int $limit 返回数量。
     * @param int $offset 起始位置。
     * @return array 搜索结果。
     */
    public function searchTracks($query, $genre = '', $mood = '', $limit = 20, $offset = 0)
    {
        if (!$this->dbReady) {
            return [];
        }

        $sql = "SELECT * FROM " . $this->getTableName('music_tracks') . " WHERE status = 'published' AND (title LIKE :query OR artist LIKE :query OR album LIKE :query)";
        $params = [':query' => "%{$query}%"];
        if ($genre) {
            $sql .= " AND genre = :genre";
            $params[':genre'] = $genre;
        }
        if ($mood) {
            $sql .= " AND mood = :mood";
            $params[':mood'] = $mood;
        }
        $sql .= " ORDER BY plays DESC";
        
        $stmt = $this->db->prepare($sql);
        // 必须在最后绑定 limit 和 offset
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        foreach ($params as $key => &$val) {
            $stmt->bindParam($key, $val);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * 统计搜索结果的总数。
     * @param string $query 搜索关键词。
     * @param string $genre 按流派过滤。
     * @param string $mood 按情绪过滤。
     * @return int 结果总数。
     */
    public function countSearchTracks($query, $genre = '', $mood = '')
    {
        if (!$this->dbReady) {
            return 0;
        }

        $sql = "SELECT COUNT(*) as count FROM " . $this->getTableName('music_tracks') . " WHERE status = 'published' AND (title LIKE :query OR artist LIKE :query OR album LIKE :query)";
        $params = [':query' => "%{$query}%"];
        if ($genre) {
            $sql .= " AND genre = :genre";
            $params[':genre'] = $genre;
        }
        if ($mood) {
            $sql .= " AND mood = :mood";
            $params[':mood'] = $mood;
        }
        $result = $this->query($sql, $params);
        return $result[0]['count'] ?? 0;
    }

    /**
     * 记录播放历史并增加播放次数。
     * @param int $userId 用户ID。
     * @param int $trackId 曲目ID。
     * @return void
     */
    public function recordPlayHistory($userId, $trackId)
    {
        if (!$this->dbReady) {
            return;
        }

        if ($userId) {
            $this->execute("INSERT INTO " . $this->getTableName('play_history') . " (user_id, track_id, played_at) VALUES (:user_id, :track_id, :played_at)", [
                ':user_id' => $userId,
                ':track_id' => $trackId,
                ':played_at' => date('Y-m-d H:i:s')
            ]);
            $this->execute("UPDATE " . $this->getTableName('music_tracks') . " SET plays = plays + 1 WHERE id = :track_id", [':track_id' => $trackId]);
        }
    }

    /**
     * 保存音乐拆解分析结果。
     * @param array $data 要插入的数据。
     * @return int 影响的行数。
     */
    public function addDeconstruction(array $data)
    {
        if (!$this->dbReady) {
            return 0;
        }

        // 确保只包含允许的字段，防止SQL注入之外的非法数据插入
        $allowed_keys = ['track_id', 'user_id', 'type', 'content', 'analysis_result'];
        $filtered_data = array_intersect_key($data, array_flip($allowed_keys));
        
        $columns = '`' . implode('`, `', array_keys($filtered_data)) . '`';
        $placeholders = ':' . implode(', :', array_keys($filtered_data));

        $sql = "INSERT INTO " . $this->getTableName('music_deconstructions') . " ($columns) VALUES ($placeholders)";
        return $this->execute($sql, $filtered_data);
    }

    /**
     * 保存仿写结果。
     * @param array $data 要插入的数据。
     * @return int 影响的行数。
     */
    public function addImitation(array $data)
    {
        if (!$this->dbReady) {
            return 0;
        }

        // 确保只包含允许的字段
        $allowed_keys = ['track_id', 'user_id', 'original_segment', 'imitation_result', 'analysis_data'];
        $filtered_data = array_intersect_key($data, array_flip($allowed_keys));

        $columns = '`' . implode('`, `', array_keys($filtered_data)) . '`';
        $placeholders = ':' . implode(', :', array_keys($filtered_data));

        $sql = "INSERT INTO " . $this->getTableName('imitations') . " ($columns) VALUES ($placeholders)";
        return $this->execute($sql, $filtered_data);
    }
}
