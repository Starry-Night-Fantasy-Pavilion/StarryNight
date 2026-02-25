<?php

declare(strict_types=1);

namespace app\config;

class FrontendConfig
{
    public const THEME_DEFAULT = 'default';
    public const THEME_TYPE_WEB = 'web';
    
    public const PATH_STATIC_ROOT = '/static';
    public const PATH_STATIC_ADMIN = '/static/admin';
    public const PATH_STATIC_FRONTEND = '/static/frontend';
    public const PATH_STATIC_FRONTEND_WEB = '/static/frontend/web';
    public const PATH_STATIC_FRONTEND_WEB_JS = '/static/frontend/web/js';
    public const PATH_STATIC_FRONTEND_WEB_CSS = '/static/frontend/web/css';
    
    public const PATH_THEME_WEB = '/web';
    public const PATH_THEME_ASSETS = 'assets';
    public const PATH_THEME_CSS = 'assets/css';
    public const PATH_THEME_JS = 'assets/js';
    public const PATH_THEME_IMAGES = 'assets/images';
    public const PATH_THEME_TEMPLATES = 'templates';
    public const PATH_THEME_LANGUAGE = 'language';
    
    public const CACHE_VERSION = '20260226';
    public const CACHE_VERSION_PARAM = 'v';
    
    public const LANG_ZH_CN = 'zh-cn';
    public const LANG_EN = 'en';
    public const LANG_DEFAULT = self::LANG_ZH_CN;
    
    public const SIDEBAR_WIDTH = 260;
    public const SIDEBAR_MOBILE_BREAKPOINT = 1024;
    
    public const FESTIVE_MONTHS = ['01', '02'];
    
    public static function getStaticPath(string $subPath = ''): string
    {
        return self::PATH_STATIC_ROOT . ($subPath ? '/' . ltrim($subPath, '/') : '');
    }
    
    public static function getThemePath(string $themeId, string $subPath = ''): string
    {
        return self::PATH_THEME_WEB . '/' . $themeId . ($subPath ? '/' . ltrim($subPath, '/') : '');
    }
    
    public static function getAssetUrl(string $path, ?string $version = null): string
    {
        $url = $path;
        $ver = $version ?? self::CACHE_VERSION;
        if ($ver) {
            $separator = str_contains($url, '?') ? '&' : '?';
            $url .= $separator . self::CACHE_VERSION_PARAM . '=' . $ver;
        }
        return $url;
    }
    
    public static function isFestiveSeason(): bool
    {
        $currentMonth = date('m');
        return in_array($currentMonth, self::FESTIVE_MONTHS);
    }
    
    /**
     * 获取主题资源URL
     *
     * @param string $assetPath 资源路径 (如 'css/base.css', 'js/theme.js')
     * @param string|null $themeId 主题ID，默认为 'default'
     * @param string|null $version 版本号
     * @return string
     */
    public static function getThemeAssetUrl(string $assetPath, ?string $themeId = null, ?string $version = null): string
    {
        $theme = $themeId ?? self::THEME_DEFAULT;

        // 根据当前脚本所在目录自动推断前缀，兼容以下两种部署方式：
        // 1. 网站根目录指向项目根目录（/public 在 URL 中可见，如 /public/web/...）
        // 2. 网站根目录直接指向 public 目录（URL 中没有 /public，直接 /web/...）
        $baseDir = '';
        if (!empty($_SERVER['SCRIPT_NAME'])) {
            $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
            if ($scriptDir !== '/' && $scriptDir !== '.' && $scriptDir !== '') {
                $baseDir = rtrim($scriptDir, '/');
            }
        }

        // 这里不再额外拼接 PATH_THEME_ASSETS，
        // 由调用方传入的 $assetPath 决定是否包含 "assets/..."
        $path = $baseDir . self::PATH_THEME_WEB . '/' . $theme . '/' . ltrim($assetPath, '/');
        return self::getAssetUrl($path, $version);
    }
    
    /**
     * 获取主题CSS URL
     *
     * @param string $cssFile CSS文件名 (如 'base.css', 'pages/home.css')
     * @param string|null $themeId 主题ID
     * @param string|null $version 版本号
     * @return string
     */
    public static function getThemeCssUrl(string $cssFile, ?string $themeId = null, ?string $version = null): string
    {
        return self::getThemeAssetUrl(self::PATH_THEME_CSS . '/' . ltrim($cssFile, '/'), $themeId, $version);
    }
    
    /**
     * 获取主题JS URL
     *
     * @param string $jsFile JS文件名 (如 'theme.js', 'components/sidebar.js')
     * @param string|null $themeId 主题ID
     * @param string|null $version 版本号
     * @return string
     */
    public static function getThemeJsUrl(string $jsFile, ?string $themeId = null, ?string $version = null): string
    {
        return self::getThemeAssetUrl(self::PATH_THEME_JS . '/' . ltrim($jsFile, '/'), $themeId, $version);
    }
    
    /**
     * 获取主题图片URL
     *
     * @param string $imageFile 图片文件名
     * @param string|null $themeId 主题ID
     * @return string
     */
    public static function getThemeImageUrl(string $imageFile, ?string $themeId = null): string
    {
        return self::getThemeAssetUrl(self::PATH_THEME_IMAGES . '/' . ltrim($imageFile, '/'), $themeId);
    }
}
