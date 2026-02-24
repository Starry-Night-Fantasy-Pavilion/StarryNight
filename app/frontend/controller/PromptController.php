<?php

namespace app\frontend\controller;

use app\services\ThemeManager;
use app\models\Setting;

class PromptController
{
    /**
     * 渲染主题视图（layout + content）
     */
    private function render(string $template, array $viewVars = []): void
    {
        header('Content-Type: text/html; charset=utf-8');

        $siteName = Setting::get('site_name') ?: (string) get_env('APP_NAME', '星夜阁');
        $themeManager = new ThemeManager();
        $theme = $themeManager->loadActiveThemeInstance();

        if (!$theme) {
            // 没有主题时，直接输出简单内容
            echo $viewVars['content'] ?? ('模板渲染失败: ' . htmlspecialchars($template));
            return;
        }

        $navItems = [
            ['title' => '首页', 'url' => '/', 'active' => false],
            ['title' => '提示词工程', 'url' => '/prompts', 'active' => true],
        ];
        if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
            $navItems[] = ['title' => '登录', 'url' => '/login', 'active' => false];
            $navItems[] = ['title' => '注册', 'url' => '/register', 'active' => false];
        } else {
            $navItems[] = ['title' => '用户中心', 'url' => '/user_center', 'active' => false];
        }

        $content = $theme->renderTemplate($template, $viewVars);
        echo $theme->renderTemplate('layout', [
            'title' => (string)($viewVars['title'] ?? $siteName . ' - 提示词工程'),
            'site_name' => $siteName,
            'page_class' => (string)($viewVars['page_class'] ?? ''),
            'nav_items' => $navItems,
            'content' => $content,
        ]);
    }

    /**
     * 提示词工程首页
     */
    public function index()
    {
        $this->render('prompts', [
            'title' => '提示词工程 - 星夜阁',
            'page_class' => 'prompts-page',
        ]);
    }
}
