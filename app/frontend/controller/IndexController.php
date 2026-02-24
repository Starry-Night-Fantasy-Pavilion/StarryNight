<?php
namespace app\frontend\controller;

use app\services\PluginManager;
use app\models\Setting;
use app\services\ThemeManager;
use app\services\FrontendMapper;

class IndexController
{
    public function index()
    {
        try {
        header('Content-Type: text/html; charset=utf-8');

        $pluginManager = new PluginManager();
        $plugins = $pluginManager->getPlugins();

        $adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '/');
        $siteName = Setting::get('site_name') ?: (string) get_env('APP_NAME', '星夜阁');
        $siteLogo = Setting::get('site_logo');

        // 初始化多语言支持
        $themeManager = new ThemeManager();
        $frontendMapper = new FrontendMapper($themeManager, $pluginManager);

        $navItems = [
            ['title' => $frontendMapper->translate('common.home'), 'url' => '/', 'active' => true],
            ['title' => $frontendMapper->translate('common.login'), 'url' => '/login', 'active' => false],
            ['title' => $frontendMapper->translate('common.register'), 'url' => '/register', 'active' => false],
        ];

        foreach ($plugins as $pluginKey => $plugin) {
            $config = $plugin['config'] ?? [];
            $frontendEntry = $config['frontend_entry'] ?? null;
            if (!is_string($frontendEntry) || trim($frontendEntry) === '') {
                continue;
            }

            $navItems[] = [
                'title' => (string) ($config['name'] ?? $pluginKey),
                'url' => $frontendEntry,
                'active' => false,
            ];
        }

        $theme = $themeManager->loadActiveThemeInstance();

        if (!$theme) {
            // 如果没有主题，使用默认视图
            // 原来指向 home.php，但实际存在的是 index.php，这里改为加载 index.php
            ob_start();
            $pluginsForView = $plugins;
            require __DIR__ . '/../views/index.php';
            $content = ob_get_clean();
            echo $content;
            return;
        }

        // 获取当前启用的主题ID，用于生成CSS/JS资源路径
        // Web服务器根目录是public，所以路径不需要/public前缀
        $activeThemeId = $themeManager->getActiveThemeId('web') ?? 'default';
        $themeBasePath = '/web/' . $activeThemeId;

        // 获取统计数据
        $stats = $this->getHomeStats();
        
        // 使用主题系统渲染首页模板（企业官网风格），并通过 ThemeManager 统一调度
        $homeContent = $theme->renderTemplate('home', [
            'site_name' => $siteName,
            'site_logo' => $siteLogo,
            'plugins' => $plugins,
            'nav_items' => $navItems,
            'stats' => $stats,
        ]);

        echo $theme->renderTemplate('layout', [
            'title'        => $siteName,
            'site_name'    => $siteName,
            'site_logo'    => $siteLogo,
            'page_class'   => 'page-home',
            'current_page' => 'home',
            'content'      => $homeContent,
            'nav_items'    => $navItems,
            'theme_base_path' => $themeBasePath, // 传递给layout用于生成CSS/JS路径
        ]);
        } catch (\Exception $e) {
            // 记录错误日志
            error_log('Controller Error in IndexController::index: ' . $e->getMessage());
            
            // 返回友好的错误信息
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                return json_encode([
                    'success' => false,
                    'message' => '操作失败，请稍后重试',
                    'error' => DEBUG ? $e->getMessage() : '系统内部错误'
                ]);
            } else {
                // 对于普通请求，显示错误页面
                http_response_code(500);
                echo '<h1>系统错误</h1><p>抱歉，系统遇到了一些问题，请稍后重试。</p>';
                if (DEBUG) {
                    echo '<p>错误详情: ' . htmlspecialchars($e->getMessage()) . '</p>';
                }
                exit;
            }
        }
    }
    
    /**
     * 获取首页统计数据
     */
    private function getHomeStats(): array
    {
        try {
            $pdo = \app\services\Database::pdo();
            $prefix = \app\services\Database::prefix();
            
            $stats = [
                'total_users' => 0,
                'total_novels' => 0,
                'total_words' => 0,
                'total_music' => 0,
                'total_anime' => 0,
            ];
            
            // 获取用户总数（排除管理员）
            $usersTable = $prefix . 'users';
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM `{$usersTable}` WHERE (role IS NULL OR role != 'admin') AND (is_admin IS NULL OR is_admin = 0)");
                $stats['total_users'] = (int)($stmt->fetchColumn() ?: 0);
            } catch (\Exception $e) {
                // 表不存在或查询失败，使用默认值
            }
            
            // 获取小说总数
            $booksTable = $prefix . 'books';
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM `{$booksTable}`");
                $stats['total_novels'] = (int)($stmt->fetchColumn() ?: 0);
                
                // 获取总字数
                $stmt = $pdo->query("SELECT SUM(word_count) FROM `{$booksTable}` WHERE word_count IS NOT NULL");
                $totalWords = $stmt->fetchColumn();
                $stats['total_words'] = (int)($totalWords ?: 0);
            } catch (\Exception $e) {
                // 表不存在或查询失败
            }
            
            // 获取音乐项目总数
            $musicTable = $prefix . 'music_tracks';
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM `{$musicTable}`");
                $stats['total_music'] = (int)($stmt->fetchColumn() ?: 0);
            } catch (\Exception $e) {
                // 表不存在或查询失败
            }
            
            // 获取动画项目总数
            $animeTable = $prefix . 'anime_projects';
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM `{$animeTable}`");
                $stats['total_anime'] = (int)($stmt->fetchColumn() ?: 0);
            } catch (\Exception $e) {
                // 表不存在或查询失败
            }
            
            return $stats;
        } catch (\Exception $e) {
            // 发生错误时返回默认值
            return [
                'total_users' => 0,
                'total_novels' => 0,
                'total_words' => 0,
                'total_music' => 0,
                'total_anime' => 0,
            ];
        }
    }
}
