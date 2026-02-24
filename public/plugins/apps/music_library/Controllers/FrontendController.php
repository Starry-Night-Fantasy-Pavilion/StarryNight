<?php

namespace plugins\apps\music_library\Controllers;

use plugins\apps\music_library\Models\MusicModel;
use plugins\apps\music_library\Services\MusicApiService;

/**
 * 音乐库插件的前端控制器 (Frontend Controller)
 *
 * 负责处理所有面向网站访问者的页面请求，例如音乐库主页、曲目详情页等。
 * 它遵循了良好的 MVC (Model-View-Controller) 实践：
 * - **Controller**: 本身作为控制器，协调模型和视图。
 * - **Model**: 将所有数据相关的逻辑委托给 `MusicModel`。
 * - **View**: 使用 `render` 方法将数据传递给视图文件进行展示。
 */
class FrontendController extends BaseController
{
    /**
     * @var MusicModel 音乐库的模型实例，用于所有数据交互。
     */
    private $musicModel;

    /**
     * 构造函数
     *
     * 1. 调用父类构造函数以建立数据库连接。
     * 2. 实例化 `MusicModel`，并通过构造函数将数据库连接对象 (`$this->db`)
     *    注入到模型中。这是一种依赖注入的实践，增强了代码的可测试性和可维护性。
     */
    public function __construct()
    {
        parent::__construct();
        $this->musicModel = new MusicModel($this->db, $this->db_prefix);
    }

    /**
     * 显示音乐库主页
     *
     * 新设计：
     * - 首页主要是一个「搜索 + 播放器」的单页应用式 UI
     * - 页面加载时**不主动拉取外部 API 的歌曲列表**
     * - 用户输入关键词后，通过前端 JS 调用 `/api/v1/music/search`、`/api/v1/music/song_url` 等接口
     *   来动态加载歌曲并在页面播放器中播放
     */
    public function index()
    {
        // 这里只负责渲染壳页面，实际数据通过前端 JS 调用 API 获取
        $this->render('frontend/index');
    }

    /**
     * 显示单个曲目的详情页面
     *
     * @param int $id 从路由传递过来的曲目ID。
     */
    public function trackDetail($id)
    {
        $track = $this->musicModel->getTrack($id);
        
        // 如果根据ID找不到曲目，则显示404页面
        if (!$track) {
            http_response_code(404);
            $this->render('frontend/404'); // 假设存在一个404视图
            return;
        }
        
        // 获取相关曲目和评论
        $relatedTracks = $this->musicModel->getRelatedTracks($track);
        $comments = $this->musicModel->getComments($id);
        
        $this->render('frontend/track_detail', [
            'track' => $track,
            'relatedTracks' => $relatedTracks,
            'comments' => $comments,
        ]);
    }

    /**
     * 显示单个专辑的详情页面
     *
     * @param int $id 从路由传递过来的专辑ID。
     */
    public function albumDetail($id)
    {
        $album = $this->musicModel->getAlbum($id);

        if (!$album) {
            http_response_code(404);
            $this->render('frontend/404');
            return;
        }

        // 获取该专辑下的所有曲目
        $tracks = $this->musicModel->getAlbumTracks($id);

        $this->render('frontend/album_detail', [
            'album' => $album,
            'tracks' => $tracks,
        ]);
    }

    /**
     * 显示音乐播放器页面
     *
     * @param int $id 要播放的曲目ID。
     */
    public function player($id)
    {
        $track = $this->musicModel->getTrack($id);
        if (!$track) {
            http_response_code(404);
            $this->render('frontend/404');
            return;
        }
        
        // 在实际应用中，可以在这里记录播放事件，用于统计分析
        // $this->musicModel->logPlay($id);
        
        $this->render('frontend/player', [
            'track' => $track,
        ]);
    }

}
