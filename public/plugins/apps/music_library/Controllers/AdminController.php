<?php

namespace plugins\apps\music_library\Controllers;

/**
 * 音乐库插件的后台管理控制器 (Admin Controller)
 *
 * 负责处理所有与音乐库插件后台管理界面相关的HTTP请求。
 * 它继承自 `BaseController`，从而获得了数据库访问和视图渲染等能力。
 *
 * 主要职责:
 * - 显示曲目、专辑、评论等列表。
 * - 提供创建、编辑、删除音乐库相关数据的功能。
 * - 处理后台设置表单的提交。
 */
class AdminController extends BaseController
{
    /**
     * 构造函数
     *
     * 调用父类的构造函数以建立数据库连接。
     * 这里是添加后台权限验证逻辑的理想位置。
     */
    public function __construct()
    {
        parent::__construct();
        // 示例: 检查用户是否登录以及是否为管理员
        // if (!is_admin()) {
        //     header('Location: /' . get_env('ADMIN_PATH', 'admin') . '/login');
        //     exit;
        // }
    }

    /**
     * 显示曲目列表页面
     *
     * 从数据库检索曲目，并实现分页功能。
     * 最后渲染 `admin/tracks` 视图。
     */
    public function tracks()
    {
        // --- 分页逻辑 ---
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 20; // 每页显示的条目数
        $offset = ($page - 1) * $limit;

        // 查询总曲目数以计算总页数
        $totalTracksResult = $this->query("SELECT COUNT(*) as total FROM " . $this->getTableName('music_tracks'));
        $totalTracks = $totalTracksResult[0]['total'] ?? 0;
        $totalPages = ceil($totalTracks / $limit);

        // 查询当前页的曲目数据
        $tracks = $this->query(
            "SELECT * FROM " . $this->getTableName('music_tracks') . " ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}"
        );

        // 渲染视图，并传递所需数据
        $this->render('admin/tracks', [
            'tracks' => $tracks,
            'currentPage' => $page,
            'totalPages' => $totalPages
        ]);
    }

    /**
     * 显示专辑列表页面
     *
     * 逻辑与 `tracks` 方法类似，包含分页。
     */
    public function albums()
    {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $totalAlbumsResult = $this->query("SELECT COUNT(*) as total FROM " . $this->getTableName('albums'));
        $totalAlbums = $totalAlbumsResult[0]['total'] ?? 0;
        $totalPages = ceil($totalAlbums / $limit);

        $albums = $this->query("SELECT * FROM " . $this->getTableName('albums') . " ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}");

        $this->render('admin/albums', [
            'albums' => $albums,
            'currentPage' => $page,
            'totalPages' => $totalPages
        ]);
    }

    /**
     * 显示音源列表页面
     */
    public function sources()
    {
        $sources = $this->query("SELECT * FROM " . $this->getTableName('music_sources') . " ORDER BY created_at DESC");
        $this->render('admin/sources', ['sources' => $sources]);
    }

    /**
     * 显示评论列表页面
     *
     * 包含分页，并使用 LEFT JOIN 关联查询获取评论对应的曲目标题。
     */
    public function comments()
    {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $totalCommentsResult = $this->query("SELECT COUNT(*) as total FROM " . $this->getTableName('music_comments'));
        $totalComments = $totalCommentsResult[0]['total'] ?? 0;
        $totalPages = ceil($totalComments / $limit);

        $sql = "SELECT c.*, t.title as track_title
                FROM " . $this->getTableName('music_comments') . " c
                LEFT JOIN " . $this->getTableName('music_tracks') . " t ON c.track_id = t.id
                ORDER BY c.created_at DESC
                LIMIT {$limit} OFFSET {$offset}";
        $comments = $this->query($sql);

        $this->render('admin/comments', [
            'comments' => $comments,
            'currentPage' => $page,
            'totalPages' => $totalPages
        ]);
    }

    /**
     * 显示排行榜管理页面
     */
    public function rankings()
    {
        $rankings = $this->query("SELECT * FROM " . $this->getTableName('music_rankings') . " ORDER BY updated_at DESC");
        $this->render('admin/rankings', ['rankings' => $rankings]);
    }

    /**
     * 显示插件设置页面
     */
    public function settings()
    {
        // 注意: 这里的设置是插件独立的。在更复杂的系统中，可能会有一个全局的设置服务。
        // 目前只是渲染一个简单的页面。
        $this->render('admin/settings', ['settings' => []]);
    }

    /**
     * 处理曲目的创建和编辑
     *
     * 这是一个典型的 "C-U" (Create/Update) 方法，通过检查请求方法和ID来区分操作。
     * - 如果是 POST 请求，则处理表单提交（创建或更新）。
     * - 如果是 GET 请求，则显示编辑/创建表单。
     *
     * @param int|null $id 要编辑的曲目ID。如果为 null，则表示是创建操作。
     */
    public function editTrack($id = null)
    {
        // --- 处理 POST 请求 (保存数据) ---
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST; // 注意: 在生产环境中，应对数据进行严格的清理和验证。
            
            if ($id) {
                // 更新现有曲目
                $set_parts = [];
                foreach (array_keys($data) as $key) {
                    $set_parts[] = "`{$key}` = :{$key}";
                }
                $sql = "UPDATE " . $this->getTableName('music_tracks') . " SET " . implode(', ', $set_parts) . " WHERE id = :id";
                $data['id'] = $id;
                $params = $data;
            } else {
                // 插入新曲目
                $columns = array_keys($data);
                $placeholders = array_map(fn($c) => ":{$c}", $columns);
                $sql = "INSERT INTO " . $this->getTableName('music_tracks') . " (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ")";
                $params = $data;
            }
            
            $this->execute($sql, $params);
            
            // 操作完成后，重定向回曲目列表页
            header('Location: /' . get_env('ADMIN_PATH', 'admin') . '/music_library/tracks');
            exit;
        }
        
        // --- 处理 GET 请求 (显示表单) ---
        $track = null;
        if ($id) {
            // 如果是编辑模式，根据ID获取曲目信息
            $trackResult = $this->query("SELECT * FROM " . $this->getTableName('music_tracks') . " WHERE id = :id", [':id' => $id]);
            $track = $trackResult[0] ?? null;
        }
        
        // 获取所有专辑，用于表单中的下拉选择框
        $albums = $this->query("SELECT id, name FROM " . $this->getTableName('albums') . " ORDER BY name");
        
        // 渲染编辑表单视图
        $this->render('admin/edit_track', [
            'track' => $track,
            'albums' => $albums,
            'isEdit' => (bool)$id // 向视图传递一个布尔值，方便判断是创建还是编辑模式
        ]);
    }
    
    // 其他方法如 editAlbum, editSource 等可以按照 `editTrack` 的模式进行重构和注释。
    // 为简洁起见，此处省略。
}
