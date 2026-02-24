<?php

namespace plugins\apps\music_library\Services;

use PDO;
use Exception;

/**
 * 音乐API服务类 (Music API Service)
 * 
 * 按 TuneFree / TuneHub 文档更新：
 * - Base URL: https://music-dl.sayqz.com
 * - Core Endpoint: GET /api/?source={source}&type={type}&...
 * - 支持 source: netease/qq/kuwo
 */
class MusicApiService
{
    /**
     * @var PDO 数据库连接
     */
    private $db;

    /**
     * @var string 数据库表前缀
     */
    private $db_prefix;

    /**
     * TuneHub / TuneFree API 基础 URL
     *
     * 说明：
     * - 官方文档域名为 https://api.tunefree.fun ，但当前环境下该域名请求返回 403
     * - 同一套接口在 https://music-dl.sayqz.com 提供了公开镜像，且无 403 限制
     * 为保证插件可正常获取真实歌曲，这里使用稳定可访问的镜像域名。
     */
    const API_BASE_URL = 'https://music-dl.sayqz.com';

    /**
     * 支持的平台（TuneHub 文档中的 source）
     */
    const SUPPORTED_SOURCES = ['netease', 'qq', 'kuwo'];

    /**
     * 构造函数
     *
     * @param PDO $db 数据库连接
     * @param string $db_prefix 数据库表前缀
     */
    public function __construct(PDO $db, string $db_prefix)
    {
        $this->db = $db;
        $this->db_prefix = $db_prefix;
    }

    /**
     * 是否启用 MOCK 模式（仅用于本地开发）
     */
    private function isMockMode(): bool
    {
        $v = get_env('MUSIC_LIBRARY_MOCK_MODE', '0');
        return $v === '1' || strtolower((string)$v) === 'true';
    }

    private function assertValidSource(string $source): void
    {
        if (!in_array($source, self::SUPPORTED_SOURCES, true)) {
            throw new Exception("不支持的平台 source: {$source}");
        }
    }

    /**
     * 发起 HTTP GET 请求（支持读取 header、可选不跟随重定向）
     */
    private function httpGet(string $url, bool $followRedirect = true): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // 使用常见浏览器 UA，避免部分节点对自定义 UA 做限制导致 4xx/5xx
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $followRedirect);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("TuneHub API 调用失败: $error");
        }
        
        $rawHeader = substr((string)$response, 0, $headerSize);
        $body = substr((string)$response, $headerSize);

        $headers = [];
        foreach (preg_split("/\r\n|\n|\r/", trim($rawHeader)) as $line) {
            if (strpos($line, ':') !== false) {
                [$k, $v] = explode(':', $line, 2);
                $headers[strtolower(trim($k))] = trim($v);
            }
        }

        return [
            'http_code' => $httpCode,
            'headers' => $headers,
            'body' => $body,
            'raw_header' => $rawHeader,
        ];
    }

    /**
     * 调用 TuneHub Core API：/api/?source=...&type=...
     */
    private function callTuneHubApi(array $query, bool $followRedirect = true): array
    {
        $url = rtrim(self::API_BASE_URL, '/') . '/api/?' . http_build_query($query);
        $resp = $this->httpGet($url, $followRedirect);

        // 对 4xx/5xx 返回更友好的错误
        if ($resp['http_code'] >= 400) {
            throw new Exception("TuneHub API 返回错误状态码: {$resp['http_code']}");
        }

        // JSON or text
        $decoded = json_decode((string)$resp['body'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return [
                'mode' => 'json',
                'http_code' => $resp['http_code'],
                'headers' => $resp['headers'],
                'data' => $decoded,
            ];
        }

                return [
            'mode' => 'text',
            'http_code' => $resp['http_code'],
            'headers' => $resp['headers'],
            'data' => (string) $resp['body'],
        ];
    }

    /**
     * TuneHub: 获取歌曲基本信息 (type=info)
     */
    public function getInfo(string $source, $id): array
    {
        $this->assertValidSource($source);

        if ($this->isMockMode()) {
                return [
                'code' => 200,
                'message' => 'success',
                    'data' => [
                    'name' => '歌曲名称(模拟)',
                    'artist' => '歌手名称(模拟)',
                    'album' => '专辑名称(模拟)',
                    'url' => self::API_BASE_URL . '/api/?source=' . $source . '&id=' . $id . '&type=url',
                    'pic' => self::API_BASE_URL . '/api/?source=' . $source . '&id=' . $id . '&type=pic',
                    'lrc' => self::API_BASE_URL . '/api/?source=' . $source . '&id=' . $id . '&type=lrc',
                ],
                'timestamp' => date('c'),
            ];
        }

        $resp = $this->callTuneHubApi(['source' => $source, 'id' => $id, 'type' => 'info'], true);
        return $resp['data'];
    }

    /**
     * TuneHub: 获取音乐文件链接 (type=url) - 通常为 302 Redirect
     */
    public function getUrl(string $source, $id, string $br = '320k'): array
    {
        $this->assertValidSource($source);

        if ($this->isMockMode()) {
                return [
                'code' => 200,
                'message' => 'success',
                    'data' => [
                    'url' => 'https://example.com/mock/' . rawurlencode((string)$id) . '.mp3',
                    'source' => $source,
                    'br' => $br,
                ],
                'timestamp' => date('c'),
            ];
        }

        $url = rtrim(self::API_BASE_URL, '/') . '/api/?' . http_build_query([
            'source' => $source,
            'id' => $id,
            'type' => 'url',
            'br' => $br,
        ]);

        // 不跟随重定向，直接取 Location
        $resp = $this->httpGet($url, false);
        $location = $resp['headers']['location'] ?? '';

        if ($resp['http_code'] === 302 && $location) {
                return [
                'code' => 200,
                'message' => 'success',
                    'data' => [
                    'url' => $location,
                    'source' => $source,
                    'br' => $br,
                    'source_switch' => $resp['headers']['x-source-switch'] ?? null,
                ],
                'timestamp' => date('c'),
            ];
        }

        // 兼容：如果服务端返回 JSON 而非 302
        $decoded = json_decode((string)$resp['body'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        throw new Exception("获取音乐链接失败，HTTP {$resp['http_code']}");
    }

    /**
     * TuneHub: 获取封面图片 (type=pic) - 302 Redirect
     */
    public function getPic(string $source, $id): array
    {
        $this->assertValidSource($source);

        if ($this->isMockMode()) {
            return [
                'code' => 200,
                'message' => 'success',
                'data' => [
                    'url' => 'https://example.com/mock/cover.jpg',
                    'source' => $source,
                ],
                'timestamp' => date('c'),
            ];
        }

        $url = rtrim(self::API_BASE_URL, '/') . '/api/?' . http_build_query([
            'source' => $source,
            'id' => $id,
            'type' => 'pic',
        ]);
        $resp = $this->httpGet($url, false);
        $location = $resp['headers']['location'] ?? '';
        if ($resp['http_code'] === 302 && $location) {
            return [
                'code' => 200,
                'message' => 'success',
                'data' => [
                    'url' => $location,
                    'source' => $source,
                ],
                'timestamp' => date('c'),
            ];
        }

        throw new Exception("获取封面失败，HTTP {$resp['http_code']}");
    }

    /**
     * TuneHub: 获取歌词 (type=lrc) - text/plain
     */
    public function getLrc(string $source, $id): array
    {
        $this->assertValidSource($source);

        if ($this->isMockMode()) {
            return [
                'code' => 200,
                'message' => 'success',
                'data' => "[00:00.00] 这是模拟歌词\n[00:05.00] TuneHub LRC",
                'timestamp' => date('c'),
            ];
        }

        $resp = $this->callTuneHubApi(['source' => $source, 'id' => $id, 'type' => 'lrc'], true);
        // lrc 是 text/plain，不会是 JSON
        if (is_string($resp['data'])) {
            return [
                'code' => 200,
                'message' => 'success',
                'data' => $resp['data'],
                'timestamp' => date('c'),
            ];
        }

        return $resp['data'];
    }

    /**
     * TuneHub: 搜索 (type=search)
     */
    public function search(string $source, string $keyword, int $limit = 20, int $page = 1): array
    {
        $this->assertValidSource($source);

        $query = [
            'source' => $source,
            'type' => 'search',
            'keyword' => $keyword,
            'limit' => $limit,
            'page' => $page,
        ];
        $resp = $this->callTuneHubApi($query, true);
        return $resp['data'];
    }

    /**
     * TuneHub: 聚合搜索 (type=aggregateSearch)
     */
    public function aggregateSearch(string $keyword, int $page = 1, int $limit = 20): array
    {
        $query = [
            'type' => 'aggregateSearch',
            'keyword' => $keyword,
            'page' => $page,
            'limit' => $limit,
        ];
        $resp = $this->callTuneHubApi($query, true);
        return $resp['data'];
    }

    /**
     * TuneHub: 歌单详情 (type=playlist)
     */
    public function playlist(string $source, $id): array
    {
        $this->assertValidSource($source);
        $resp = $this->callTuneHubApi(['source' => $source, 'id' => $id, 'type' => 'playlist'], true);
        return $resp['data'];
    }

    /**
     * TuneHub: 排行榜列表 (type=toplists)
     */
    public function topLists(string $source): array
    {
        $this->assertValidSource($source);
        $resp = $this->callTuneHubApi(['source' => $source, 'type' => 'toplists'], true);
        return $resp['data'];
    }

    /**
     * TuneHub: 排行榜歌曲 (type=toplist)
     */
    public function topList(string $source, $id): array
    {
        $this->assertValidSource($source);
        $resp = $this->callTuneHubApi(['source' => $source, 'id' => $id, 'type' => 'toplist'], true);
        return $resp['data'];
    }

    /**
     * 保存搜索历史到本地数据库
     *
     * @param int $userId 用户ID
     * @param string $keyword 搜索关键词
     * @return bool 是否保存成功
     */
    public function saveSearchHistory(int $userId, string $keyword): bool
    {
        if (empty($userId) || empty(trim($keyword))) {
            return false;
        }

        $sql = "INSERT INTO " . $this->getTableName('search_history') . " (user_id, keyword, created_at) 
                VALUES (:user_id, :keyword, :created_at)
                ON DUPLICATE KEY UPDATE created_at = :created_at";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':keyword' => trim($keyword),
            ':created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * 获取用户搜索历史
     *
     * @param int $userId 用户ID
     * @param int $limit 返回数量
     * @return array 搜索历史列表
     */
    public function getSearchHistory(int $userId, int $limit = 10): array
    {
        $sql = "SELECT * FROM " . $this->getTableName('search_history') . " 
                WHERE user_id = :user_id 
                ORDER BY created_at DESC 
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * 获取带前缀的完整表名
     *
     * @param string $name 表的基本名称
     * @return string 完整的、带反引号的表名
     */
    private function getTableName(string $name): string
    {
        return '`' . $this->db_prefix . $name . '`';
    }
}