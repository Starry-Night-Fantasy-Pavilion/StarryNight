<?php

namespace Plugins\Apps\Music_library;

use app\services\Router;
use plugins\apps\music_library\Controllers\AdminController;
use plugins\apps\music_library\Controllers\ApiController;
use plugins\apps\music_library\Controllers\FrontendController;

// 防止类重复声明
if (class_exists(__NAMESPACE__ . '\Plugin')) {
    return;
}

/**
 * 音乐库插件 (Music Library Plugin)
 *
 * 这是音乐库插件的主类，是插件与主应用程序通信的唯一入口点。
 *
 * 工作流程:
 * 1. 在应用启动时，`PluginManager` 会扫描所有插件目录。
 * 2. 如果 `plugin.json` 中 `status` 为 `enabled`，`PluginManager` 会实例化这个 `Plugin` 类。
 * 3. 接着，`public/index.php` 中的引导程序会调用该实例的 `registerRoutes` 方法。
 */
class Plugin
{
    /**
     * 向主应用注册该插件的所有路由。
     *
     * 这个方法在应用程序启动时被调用，接收一个全局的路由器实例。
     * 插件的所有页面URL（前端、后台、API）都在这里定义。
     *
     * @param Router $router 从主应用传入的路由器实例。
     * @return void
     */
    public function registerRoutes(Router $router)
    {
        // 从 .env 配置中获取后台管理的路径前缀，默认为 'admin'。
        $admin_prefix = get_env('ADMIN_PATH', 'admin');

        // --- 1. 注册面向用户的前端路由 ---
        // 这些路由构成了插件在网站前台的页面。
        // 示例: http://your-site.com/music
        $router->get('/music', [FrontendController::class, 'index']);
        // TODO: 未来需要实现路由参数解析，例如 {id}
        $router->get('/music/track/{id}', [FrontendController::class, 'trackDetail']);
        $router->get('/music/album/{id}', [FrontendController::class, 'albumDetail']);
        $router->get('/music/player/{id}', [FrontendController::class, 'player']);

        // --- 2. 注册供外部或Ajax调用的API路由 ---
        // 这些路由通常返回 JSON 格式的数据。
        // 示例: http://your-site.com/api/v1/music/tracks
        
        // 原有的本地API
        $router->get('/api/v1/music/tracks', [ApiController::class, 'getTracks']);
        $router->get('/api/v1/music/track/{id}', [ApiController::class, 'getTrack']);
        $router->post('/api/v1/music/comment', [ApiController::class, 'addComment']);
        
        // 新增的音乐API（基于GitHub文档 https://github.com/sunzongzheng/musicApi）
        $router->get('/api/v1/music/search', [ApiController::class, 'searchSong']);
        $router->get('/api/v1/music/song_url', [ApiController::class, 'getSongUrl']);
        $router->get('/api/v1/music/lyric', [ApiController::class, 'getLyric']);
        $router->get('/api/v1/music/comment_external', [ApiController::class, 'getComment']);
        $router->get('/api/v1/music/song_detail', [ApiController::class, 'getSongDetail']);
        $router->get('/api/v1/music/artist_songs', [ApiController::class, 'getArtistSongs']);
        $router->get('/api/v1/music/playlist_detail', [ApiController::class, 'getPlaylistDetail']);
        $router->get('/api/v1/music/album_detail', [ApiController::class, 'getAlbumDetail']);
        $router->get('/api/v1/music/top_list', [ApiController::class, 'getTopList']);
        $router->get('/api/v1/music/artists', [ApiController::class, 'getArtists']);
        $router->get('/api/v1/music/search_history', [ApiController::class, 'getSearchHistory']);

        // --- 3. 注册后台管理路由 ---
        // 使用 `$router->group()` 可以为一组路由统一添加路径前缀。
        // 这样，所有后台管理的URL都会自动以 `/admin` (或自定义的前缀) 开头。
        // 示例: http://your-site.com/admin/music_library/tracks
        $router->group($admin_prefix, function (Router $router) {
            // 列表页面
            $router->get('/music_library/tracks', [AdminController::class, 'tracks']);
            $router->get('/music_library/albums', [AdminController::class, 'albums']);
            $router->get('/music_library/sources', [AdminController::class, 'sources']);
            $router->get('/music_library/comments', [AdminController::class, 'comments']);
            $router->get('/music_library/rankings', [AdminController::class, 'rankings']);
            $router->get('/music_library/settings', [AdminController::class, 'settings']);
            
            // 创建与编辑表单 (GET) 和处理 (POST)
            // 注意: 当前的轻量级路由器还不支持在回调中自动注入 {id} 参数，
            // 控制器需要自己从 URL 中解析。
            $router->get('/music_library/edit_track/{id}', [AdminController::class, 'editTrack']);
            $router->post('/music_library/edit_track/{id}', [AdminController::class, 'editTrack']);
            $router->get('/music_library/edit_album/{id}', [AdminController::class, 'editAlbum']);
            $router->post('/music_library/edit_album/{id}', [AdminController::class, 'editAlbum']);
        });
    }
}
