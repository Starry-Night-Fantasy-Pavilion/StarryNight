<?php

declare(strict_types=1);

namespace app\services;

/**
 * 前端映射机制
 * 
 * 处理URL路由映射、模板路径映射、静态资源映射、语言包映射
 * 基于智简魔方财务系统前端映射机制规范
 */
class FrontendMapper
{
    /**
     * @var ThemeManager 主题管理器
     */
    private ThemeManager $themeManager;

    /**
     * @var PluginManager 插件管理器
     */
    private PluginManager $pluginManager;

    /**
     * @var array<string, mixed> 路由映射缓存
     */
    private array $routeCache = [];

    /**
     * @var array<string, mixed> 模板映射缓存
     */
    private array $templateCache = [];

    /**
     * @var array<string, mixed> 资源映射缓存
     */
    private array $assetCache = [];

    /**
     * @var array<string, mixed> 语言包缓存
     */
    private array $languageCache = [];

    /**
     * 构造函数
     *
     * @param ThemeManager $themeManager
     * @param PluginManager $pluginManager
     */
    public function __construct(ThemeManager $themeManager, PluginManager $pluginManager)
    {
        $this->themeManager = $themeManager;
        $this->pluginManager = $pluginManager;
    }

    /**
     * 解析URL路由
     *
     * @param string $url URL路径
     * @return array<string, mixed>|null
     */
    public function resolveRoute(string $url): ?array
    {
        // 检查缓存
        if (isset($this->routeCache[$url])) {
            return $this->routeCache[$url];
        }

        // 解析URL
        $parts = explode('/', trim($url, '/'));
        
        // 基础路由映射
        $route = $this->parseBasicRoute($parts);
        
        if ($route) {
            $this->routeCache[$url] = $route;
            return $route;
        }

        // 插件路由映射
        $route = $this->parsePluginRoute($parts);
        
        if ($route) {
            $this->routeCache[$url] = $route;
            return $route;
        }

        return null;
    }

    /**
     * 解析基础路由
     *
     * @param array $parts URL部分
     * @return array<string, mixed>|null
     */
    private function parseBasicRoute(array $parts): ?array
    {
        if (empty($parts)) {
            return [
                'type' => 'web',
                'controller' => 'index',
                'action' => 'index'
            ];
        }

        $firstPart = $parts[0];

        // 购物车路由
        if ($firstPart === 'cart') {
            return [
                'type' => 'cart',
                'controller' => $parts[1] ?? 'index',
                'action' => $parts[2] ?? 'index',
                'params' => array_slice($parts, 3)
            ];
        }

        // 客户端路由
        if ($firstPart === 'clientarea') {
            return [
                'type' => 'clientarea',
                'controller' => $parts[1] ?? 'index',
                'action' => $parts[2] ?? 'index',
                'params' => array_slice($parts, 3)
            ];
        }

        // 网站路由
        return [
            'type' => 'web',
            'controller' => $firstPart,
            'action' => $parts[1] ?? 'index',
            'params' => array_slice($parts, 2)
        ];
    }

    /**
     * 解析插件路由
     *
     * @param array $parts URL部分
     * @return array<string, mixed>|null
     */
    private function parsePluginRoute(array $parts): ?array
    {
        if (empty($parts) || $parts[0] !== 'plugin') {
            return null;
        }

        $pluginName = $parts[1] ?? '';
        $controller = $parts[2] ?? 'index';
        $action = $parts[3] ?? 'index';

        return [
            'type' => 'plugin',
            'plugin' => $pluginName,
            'controller' => $controller,
            'action' => $action,
            'params' => array_slice($parts, 4)
        ];
    }

    /**
     * 解析模板路径
     *
     * @param string $themeType 主题类型
     * @param string $templateName 模板名称
     * @return string|null
     */
    public function resolveTemplate(string $themeType, string $templateName): ?string
    {
        $cacheKey = $themeType . '_' . $templateName;

        // 检查缓存
        if (isset($this->templateCache[$cacheKey])) {
            return $this->templateCache[$cacheKey];
        }

        // 获取活动主题
        $activeTheme = $this->themeManager->getActiveThemeId();
        
        if (!$activeTheme) {
            return null;
        }

        // 获取主题继承链
        $inheritanceChain = $this->themeManager->getThemeInheritanceChain($themeType, $activeTheme);

        // 从子主题到父主题依次查找
        foreach ($inheritanceChain as $themeName) {
            $templatePath = $this->themeManager->getThemeDir($themeType) . '/' . $themeName . '/' . $templateName;

            if (file_exists($templatePath)) {
                $this->templateCache[$cacheKey] = $templatePath;
                return $templatePath;
            }
        }

        // 在默认主题中查找
        $defaultTemplatePath = $this->themeManager->getThemeDir($themeType) . '/default/' . $templateName;

        if (file_exists($defaultTemplatePath)) {
            $this->templateCache[$cacheKey] = $defaultTemplatePath;
            return $defaultTemplatePath;
        }

        return null;
    }

    /**
     * 解析CSS路径
     *
     * @param string $themeType 主题类型
     * @param string $cssFile CSS文件名
     * @return string|null
     */
    public function resolveCss(string $themeType, string $cssFile): ?string
    {
        $cacheKey = 'css_' . $themeType . '_' . $cssFile;

        if (isset($this->assetCache[$cacheKey])) {
            return $this->assetCache[$cacheKey];
        }

        // 优先从静态资源目录查找（已分离的CSS文件）
        $projectRoot = realpath(__DIR__ . '/../../') ?: (__DIR__ . '/../../');
        $staticCssPath = $projectRoot . '/public/static/frontend/web/css/' . $cssFile;
        if (file_exists($staticCssPath)) {
            $url = '/static/frontend/web/css/' . $cssFile;
            $this->assetCache[$cacheKey] = $url;
            return $url;
        }

        // 回退到主题包内查找（向后兼容）
        $activeTheme = $this->themeManager->getActiveThemeId();
        $inheritanceChain = $this->themeManager->getThemeInheritanceChain($themeType, $activeTheme);

        foreach ($inheritanceChain as $themeName) {
            $cssPath = $this->themeManager->getThemeDir($themeType) . '/' . $themeName . '/assets/css/' . $cssFile;

            if (file_exists($cssPath)) {
                $url = '/public/' . $themeType . '/' . $themeName . '/assets/css/' . $cssFile;
                $this->assetCache[$cacheKey] = $url;
                return $url;
            }
        }

        return null;
    }

    /**
     * 解析JS路径
     *
     * @param string $themeType 主题类型
     * @param string $jsFile JS文件名
     * @return string|null
     */
    public function resolveJs(string $themeType, string $jsFile): ?string
    {
        $cacheKey = 'js_' . $themeType . '_' . $jsFile;

        if (isset($this->assetCache[$cacheKey])) {
            return $this->assetCache[$cacheKey];
        }

        // 优先从静态资源目录查找（已分离的JS文件）
        $projectRoot = realpath(__DIR__ . '/../../') ?: (__DIR__ . '/../../');
        $staticJsPath = $projectRoot . '/public/static/frontend/web/js/' . $jsFile;
        if (file_exists($staticJsPath)) {
            $url = '/static/frontend/web/js/' . $jsFile;
            $this->assetCache[$cacheKey] = $url;
            return $url;
        }

        // 回退到主题包内查找（向后兼容）
        $activeTheme = $this->themeManager->getActiveThemeId();
        $inheritanceChain = $this->themeManager->getThemeInheritanceChain($themeType, $activeTheme);

        foreach ($inheritanceChain as $themeName) {
            $jsPath = $this->themeManager->getThemeDir($themeType) . '/' . $themeName . '/assets/js/' . $jsFile;

            if (file_exists($jsPath)) {
                $url = '/public/' . $themeType . '/' . $themeName . '/assets/js/' . $jsFile;
                $this->assetCache[$cacheKey] = $url;
                return $url;
            }
        }

        return null;
    }

    /**
     * 解析图片路径
     *
     * @param string $themeType 主题类型
     * @param string $imageFile 图片文件名
     * @return string|null
     */
    public function resolveImage(string $themeType, string $imageFile): ?string
    {
        $cacheKey = 'img_' . $themeType . '_' . $imageFile;

        if (isset($this->assetCache[$cacheKey])) {
            return $this->assetCache[$cacheKey];
        }

        $activeTheme = $this->themeManager->getActiveThemeId();
        $inheritanceChain = $this->themeManager->getThemeInheritanceChain($themeType, $activeTheme);

        foreach ($inheritanceChain as $themeName) {
            $imagePath = $this->themeManager->getThemeDir($themeType) . '/' . $themeName . '/assets/images/' . $imageFile;

            if (file_exists($imagePath)) {
                $url = '/public/' . $themeType . '/' . $themeName . '/assets/images/' . $imageFile;
                $this->assetCache[$cacheKey] = $url;
                return $url;
            }
        }

        return null;
    }

    /**
     * 加载语言包
     *
     * @param string $themeType 主题类型
     * @param string $language 语言代码
     * @return array<string, mixed>
     */
    public function loadLanguage(string $themeType, string $language = 'zh-cn'): array
    {
        $cacheKey = $themeType . '_' . $language;

        if (isset($this->languageCache[$cacheKey])) {
            return $this->languageCache[$cacheKey];
        }

        $languageData = [];

        // 从主题语言包加载
        $activeTheme = $this->themeManager->getActiveThemeId();
        $inheritanceChain = $this->themeManager->getThemeInheritanceChain($themeType, $activeTheme);

        // 从父主题到子主题加载，子主题覆盖父主题
        foreach (array_reverse($inheritanceChain) as $themeName) {
            $langFile = $this->themeManager->getThemeDir($themeType) . '/' . $themeName . '/language/' . $language . '.php';

            if (file_exists($langFile)) {
                $themeLanguage = include $langFile;
                if (is_array($themeLanguage)) {
                    $languageData = array_merge($languageData, $themeLanguage);
                }
            }
        }

        // 从系统语言包加载
        $systemLangFile = __DIR__ . '/../../app/language/' . $language . '.php';
        if (file_exists($systemLangFile)) {
            $systemLanguage = include $systemLangFile;
            if (is_array($systemLanguage)) {
                $languageData = array_merge($systemLanguage, $languageData);
            }
        }

        $this->languageCache[$cacheKey] = $languageData;

        return $languageData;
    }

    /**
     * 翻译
     *
     * @param string $key 翻译键
     * @param array $params 参数
     * @param string|null $themeType 主题类型
     * @return string
     */
    public function translate(string $key, array $params = [], ?string $themeType = null): string
    {
        $themeType = $themeType ?? $this->detectThemeType();
        $language = $this->getCurrentLanguage();
        $languageData = $this->loadLanguage($themeType, $language);

        $translation = $languageData[$key] ?? null;

        // 支持点号路径：common.welcome -> ['common']['welcome']
        if ($translation === null && str_contains($key, '.')) {
            $translation = $this->getArrayValueByDotPath($languageData, $key);
        }

        if (!is_string($translation) || $translation === '') {
            $translation = $key;
        }

        if (!empty($params)) {
            foreach ($params as $param => $value) {
                $translation = str_replace(':' . $param, (string)$value, $translation);
            }
        }

        return $translation;
    }

    /**
     * 通过点号路径从数组中读取值
     *
     * @param array<string, mixed> $data
     * @param string $path
     * @return mixed|null
     */
    private function getArrayValueByDotPath(array $data, string $path)
    {
        $parts = array_filter(explode('.', $path), fn ($p) => $p !== '');
        $current = $data;
        foreach ($parts as $part) {
            if (!is_array($current) || !array_key_exists($part, $current)) {
                return null;
            }
            $current = $current[$part];
        }
        return $current;
    }

    /**
     * 检测当前主题类型
     *
     * @return string
     */
    private function detectThemeType(): string
    {
        $path = $_SERVER['REQUEST_URI'] ?? '/';

        if (strpos($path, '/cart') === 0) {
            return 'cart';
        } elseif (strpos($path, '/clientarea') === 0) {
            return 'clientarea';
        } else {
            return 'web';
        }
    }

    /**
     * 获取当前语言
     *
     * @return string
     */
    private function getCurrentLanguage(): string
    {
        // 从session获取
        if (isset($_SESSION['language'])) {
            return $_SESSION['language'];
        }

        // 从cookie获取
        if (isset($_COOKIE['language'])) {
            return $_COOKIE['language'];
        }

        // 从浏览器语言获取
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
            return $lang === 'zh' ? 'zh-cn' : 'en';
        }

        return 'zh-cn';
    }

    /**
     * 获取主题继承链
     *
     * @param string $themeType 主题类型
     * @param string $themeName 主题名称
     * @return array<int, string>
     */
    public function getThemeInheritanceChain(string $themeType, string $themeName): array
    {
        return $this->themeManager->getThemeInheritanceChain($themeType, $themeName);
    }

    /**
     * 清除缓存
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->routeCache = [];
        $this->templateCache = [];
        $this->assetCache = [];
        $this->languageCache = [];
    }

    /**
     * 获取所有CSS文件
     *
     * @param string $themeType 主题类型
     * @return array<int, string>
     */
    public function getAllCss(string $themeType): array
    {
        $cssFiles = [];

        // 优先从静态资源目录查找（已分离的CSS文件）
        $projectRoot = realpath(__DIR__ . '/../../') ?: (__DIR__ . '/../../');
        $staticCssDir = $projectRoot . '/public/static/frontend/web/css';
        if (is_dir($staticCssDir)) {
            $files = glob($staticCssDir . '/**/*.css');
            if ($files !== false) {
                foreach ($files as $file) {
                    $relativePath = str_replace($staticCssDir, '', $file);
                    $url = '/static/frontend/web/css' . str_replace('\\', '/', $relativePath);
                    $cssFiles[] = $url;
                }
            }
        }

        // 回退到主题包内查找（向后兼容）
        $activeTheme = $this->themeManager->getActiveThemeId();
        $inheritanceChain = $this->themeManager->getThemeInheritanceChain($themeType, $activeTheme);

        foreach ($inheritanceChain as $themeName) {
            $themeDir = $this->themeManager->getThemeDir($themeType) . '/' . $themeName;
            $cssDirs = [
                $themeDir . '/assets/css',
                $themeDir . '/static/css'
            ];

            foreach ($cssDirs as $dir) {
                if (is_dir($dir)) {
                    $files = glob($dir . '/**/*.css');
                    if ($files !== false) {
                        foreach ($files as $file) {
                            $relativePath = str_replace($themeDir, '', $file);
                            $url = '/public/' . $themeType . '/' . $themeName . str_replace('\\', '/', $relativePath);
                            // 避免重复添加已在静态资源目录中的文件
                            if (!in_array($url, $cssFiles)) {
                            $cssFiles[] = $url;
                            }
                        }
                    }
                }
            }
        }

        return $cssFiles;
    }

    /**
     * 获取所有JS文件
     *
     * @param string $themeType 主题类型
     * @return array<int, string>
     */
    public function getAllJs(string $themeType): array
    {
        $jsFiles = [];

        // 优先从静态资源目录查找（已分离的JS文件）
        $projectRoot = realpath(__DIR__ . '/../../') ?: (__DIR__ . '/../../');
        $staticJsDir = $projectRoot . '/public/static/frontend/web/js';
        if (is_dir($staticJsDir)) {
            $files = glob($staticJsDir . '/**/*.js');
            if ($files !== false) {
                foreach ($files as $file) {
                    $relativePath = str_replace($staticJsDir, '', $file);
                    $url = '/static/frontend/web/js' . str_replace('\\', '/', $relativePath);
                    $jsFiles[] = $url;
                }
            }
        }

        // 回退到主题包内查找（向后兼容）
        $activeTheme = $this->themeManager->getActiveThemeId();
        $inheritanceChain = $this->themeManager->getThemeInheritanceChain($themeType, $activeTheme);

        foreach ($inheritanceChain as $themeName) {
            $themeDir = $this->themeManager->getThemeDir($themeType) . '/' . $themeName;
            $jsDirs = [
                $themeDir . '/assets/js',
                $themeDir . '/static/js'
            ];

            foreach ($jsDirs as $dir) {
                if (is_dir($dir)) {
                    $files = glob($dir . '/**/*.js');
                    if ($files !== false) {
                        foreach ($files as $file) {
                            $relativePath = str_replace($themeDir, '', $file);
                            $url = '/public/' . $themeType . '/' . $themeName . str_replace('\\', '/', $relativePath);
                            // 避免重复添加已在静态资源目录中的文件
                            if (!in_array($url, $jsFiles)) {
                            $jsFiles[] = $url;
                            }
                        }
                    }
                }
            }
        }

        return $jsFiles;
    }

    /**
     * 统一错误处理
     */
    protected function handleError(\Exception $e, $operation = '') {
        $errorMessage = $operation ? $operation . '失败: ' . $e->getMessage() : $e->getMessage();
        
        // 记录错误日志
        error_log('Service Error: ' . $errorMessage);
        
        // 抛出自定义异常
        throw new \Exception($errorMessage, $e->getCode(), $e);
    }
}
