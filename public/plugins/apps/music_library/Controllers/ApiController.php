<?php

namespace plugins\apps\music_library\Controllers;

use plugins\apps\music_library\Services\MusicApiService;
use Exception;

/**
 * 音乐库插件的API控制器 (API Controller)
 *
 * 负责处理所有与API相关的请求，通常返回JSON格式的数据。
 * 这些API可用于前端的AJAX调用（例如，动态加载数据、提交表单）或供第三方应用集成。
 *
 * 按 TuneHub / TuneFree 文档实现统一的音乐解析接口：
 * - Base URL: https://music-dl.sayqz.com
 * - Core Endpoint: /api/?source={source}&type={type}&...
 */
class ApiController extends BaseController
{
    /**
     * @var MusicApiService 音乐API服务
     */
    private $musicApiService;

    /**
     * 构造函数
     *
     * 调用父类构造函数以建立数据库连接。
     * API控制器通常需要实现某种形式的无状态认证，例如检查API密钥或JWT令牌。
     */
    public function __construct()
    {
        parent::__construct();
        $this->musicApiService = new MusicApiService($this->db, $this->db_prefix);
    }

    /**
     * API端点: 获取已发布的曲目列表
     *
     * 支持通过GET参数进行分页。
     * URL: /api/v1/music/tracks?limit=10&offset=20
     */
    public function getTracks()
    {
        // 从GET参数获取分页信息，并提供默认值
        $limit = (int)($_GET['limit'] ?? 20);
        $offset = (int)($_GET['offset'] ?? 0);
        
        $sql = "SELECT * FROM " . $this->getTableName('music_tracks') . " WHERE status = 'published' ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}";
        $tracks = $this->query($sql);
        
        // 使用基类提供的jsonResponse方法返回标准格式的JSON
        $this->jsonResponse(['success' => true, 'data' => $tracks]);
    }

    /**
     * API端点: 获取单个曲目的详细信息
     *
     * @param int $id 从路由传递过来的曲目ID。
     */
    public function getTrack($id)
    {
        $sql = "SELECT * FROM " . $this->getTableName('music_tracks') . " WHERE id = :id AND status = 'published'";
        $trackResult = $this->query($sql, [':id' => $id]);
        
        if ($trackResult) {
            $this->jsonResponse(['success' => true, 'data' => $trackResult[0]]);
        } else {
            // 如果找不到曲目，返回404状态码和错误信息
            $this->jsonResponse(['success' => false, 'message' => 'Track not found'], 404);
        }
    }
    
    /**
     * API端点: 添加一条新评论
     *
     * 这是一个处理POST请求的示例。
     * 它从请求体中读取JSON数据，并执行身份验证和输入验证。
     */
    public function addComment()
    {
        // --- 1. 身份验证 ---
        // 这是一个简化的、基于会话的身份验证示例。
        // 在生产级的无状态API中，通常会使用JWT (JSON Web Tokens) 或 API Key。
        session_start(); // 确保会话已启动
        $userId = $_SESSION['user_id'] ?? 0;
        if (!$userId) {
            // 如果用户未登录，返回401 Unauthorized错误
            $this->jsonResponse(['success' => false, 'message' => 'Authentication required'], 401);
            return;
        }

        // --- 2. 读取和解析输入 ---
        // `php://input` 是一个只读流，可以访问请求的原始正文。
        // 这对于接收非 application/x-www-form-urlencoded 类型的POST数据（如JSON）非常有用。
        $data = json_decode(file_get_contents('php://input'), true);
        
        // --- 3. 输入验证 ---
        $trackId = $data['track_id'] ?? 0;
        $content = $data['content'] ?? '';

        if (empty($trackId) || empty(trim($content))) {
            // 如果输入无效，返回400 Bad Request错误
            $this->jsonResponse(['success' => false, 'message' => 'Invalid input: track_id and content are required.'], 400);
            return;
        }

        // --- 4. 执行数据库操作 ---
        $sql = "INSERT INTO " . $this->getTableName('music_comments') . " (track_id, user_id, content, status) VALUES (:track_id, :user_id, :content, :status)";
        $this->execute($sql, [
            ':track_id' => $trackId,
            ':user_id' => $userId,
            // 对用户输入进行基础的HTML转义，防止XSS攻击
            ':content' => htmlspecialchars($content),
            // 新评论的状态可以设置为 'approved' (直接发布) 或 'pending' (需要审核)
            ':status' => 'approved'
        ]);

        // --- 5. 返回成功响应 ---
        $this->jsonResponse(['success' => true, 'message' => 'Comment added successfully']);
    }

    /**
     * API端点: 搜索歌曲
     *
     * 使用 TuneHub 搜索接口：
     * - 单平台: /api/?source={source}&type=search&keyword={keyword}&limit={limit}
     * - 聚合搜索: /api/?type=aggregateSearch&keyword={keyword}
     *
     * URL: /api/v1/music/search?keyword=周杰伦&vendor=netease&offset=1&limit=20
     */
    public function searchSong()
    {
        $keyword = trim((string)($_GET['keyword'] ?? ''));
        $vendor = trim((string)($_GET['vendor'] ?? ''));
        $offset = (int)($_GET['offset'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 20);
        $page = $offset > 0 ? $offset : 1;

        if ($keyword === '') {
            $this->jsonResponse(['success' => false, 'message' => '搜索关键词不能为空'], 400);
            return;
        }

        try {
            // TuneFree 文档没有 aggregateSearch，我们统一走单平台搜索：
            // 如果前端传 all 或为空，则默认用 netease 平台。
            if ($vendor === '' || strtolower($vendor) === 'all') {
                $vendor = 'netease';
            }
            $result = $this->musicApiService->search($vendor, $keyword, $limit, $page);
            
            // 保存搜索历史（如果用户已登录）
            session_start();
            $userId = $_SESSION['user_id'] ?? 0;
            if ($userId) {
                $this->musicApiService->saveSearchHistory($userId, $keyword);
            }

            $this->jsonResponse(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => '搜索失败: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API端点: 获取歌曲URL
     *
     * URL: /api/v1/music/song_url?vendor=netease&id=123456
     */
    public function getSongUrl()
    {
        $vendor = trim((string)($_GET['vendor'] ?? ''));
        $id = $_GET['id'] ?? '';
        $br = (string)($_GET['br'] ?? '320k');

        if ($vendor === '' || $id === '') {
            $this->jsonResponse(['success' => false, 'message' => 'vendor 和 id 参数不能为空'], 400);
            return;
        }

        try {
            $result = $this->musicApiService->getUrl($vendor, $id, $br);
            $this->jsonResponse(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => '获取歌曲URL失败: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API端点: 获取歌曲歌词
     *
     * URL: /api/v1/music/lyric?vendor=netease&id=123456
     */
    public function getLyric()
    {
        $vendor = trim((string)($_GET['vendor'] ?? ''));
        $id = $_GET['id'] ?? '';

        if ($vendor === '' || $id === '') {
            $this->jsonResponse(['success' => false, 'message' => 'vendor 和 id 参数不能为空'], 400);
            return;
        }

        try {
            $result = $this->musicApiService->getLrc($vendor, $id);
            $this->jsonResponse(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => '获取歌词失败: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API端点: 获取歌曲评论
     *
     * URL: /api/v1/music/comment?vendor=netease&id=123456&page=1&limit=20
     */
    public function getComment()
    {
        // TuneHub / TuneFree 当前未提供评论接口，这里返回显式提示
        $this->jsonResponse([
            'success' => false,
            'message' => '当前音乐接口不再提供远程评论功能（TuneHub 未实现）。',
        ], 501);
    }

    /**
     * API端点: 获取歌曲详情
     *
     * URL: /api/v1/music/song_detail?vendor=netease&id=123456
     */
    public function getSongDetail()
    {
        $vendor = trim((string)($_GET['vendor'] ?? ''));
        $id = $_GET['id'] ?? '';

        if ($vendor === '' || $id === '') {
            $this->jsonResponse(['success' => false, 'message' => 'vendor 和 id 参数不能为空'], 400);
            return;
        }

        try {
            $result = $this->musicApiService->getInfo($vendor, $id);
            $this->jsonResponse(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => '获取歌曲详情失败: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API端点: 获取歌手单曲
     *
     * URL: /api/v1/music/artist_songs?vendor=netease&id=12345&offset=0&limit=50
     */
    public function getArtistSongs()
    {
        // TuneHub 当前没有公开独立的「歌手单曲」API，这里直接提示不支持
        $this->jsonResponse([
            'success' => false,
            'message' => '当前音乐接口不再提供歌手单曲列表功能（TuneHub 未实现）。',
        ], 501);
    }

    /**
     * API端点: 获取歌单信息
     *
     * URL: /api/v1/music/playlist_detail?vendor=netease&id=12345&offset=0&limit=50
     */
    public function getPlaylistDetail()
    {
        $vendor = trim((string)($_GET['vendor'] ?? ''));
        $id = $_GET['id'] ?? '';

        if ($vendor === '' || $id === '') {
            $this->jsonResponse(['success' => false, 'message' => 'vendor 和 id 参数不能为空'], 400);
            return;
        }

        try {
            $result = $this->musicApiService->playlist($vendor, $id);
            $this->jsonResponse(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => '获取歌单信息失败: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API端点: 获取专辑信息
     *
     * URL: /api/v1/music/album_detail?vendor=netease&id=12345
     */
    public function getAlbumDetail()
    {
        // TuneHub 当前没有公开独立专辑详情接口，这里直接提示不支持
        $this->jsonResponse([
            'success' => false,
            'message' => '当前音乐接口不再提供专辑详情功能（TuneHub 未实现）。',
        ], 501);
    }

    /**
     * API端点: 获取网易云音乐排行榜
     *
     * URL: /api/v1/music/top_list?vendor=netease&id=排行榜ID
     */
    public function getTopList()
    {
        $vendor = trim((string)($_GET['vendor'] ?? $_GET['source'] ?? 'netease'));
        $id = $_GET['id'] ?? '';

        if ($id === '') {
            $this->jsonResponse(['success' => false, 'message' => 'id 参数不能为空（排行榜ID）'], 400);
            return;
        }

        try {
            $result = $this->musicApiService->topList($vendor, $id);
            $this->jsonResponse(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => '获取排行榜失败: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API端点: 获取QQ音乐歌手列表
     *
     * URL: /api/v1/music/artists?offset=0&area=-100&sex=-100&genre=-100&index=-100
     */
    public function getArtists()
    {
        // TuneHub 当前没有公开歌手列表接口，这里直接提示不支持
        $this->jsonResponse([
            'success' => false,
            'message' => '当前音乐接口不再提供歌手列表功能（TuneHub 未实现）。',
        ], 501);
    }

    /**
     * API端点: 获取用户搜索历史
     *
     * URL: /api/v1/music/search_history?limit=10
     */
    public function getSearchHistory()
    {
        session_start();
        $userId = $_SESSION['user_id'] ?? 0;
        $limit = (int)($_GET['limit'] ?? 10);

        if (!$userId) {
            $this->jsonResponse(['success' => false, 'message' => '用户未登录'], 401);
            return;
        }

        try {
            $history = $this->musicApiService->getSearchHistory($userId, $limit);
            $this->jsonResponse(['success' => true, 'data' => $history]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => '获取搜索历史失败: ' . $e->getMessage()], 500);
        }
    }

    // 其他API方法（如点赞、收藏等）可以按照类似模式添加。
}
