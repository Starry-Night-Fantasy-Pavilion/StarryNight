<?php

declare(strict_types=1);

namespace app\services;

use Core\Theme;

/**
 * 主题管理器
 *
 * 负责发现、加载和管理所有主题
 * 集成模板解析器和配置管理功能
 * 支持智简魔方财务系统主题架构规范
 */
class ThemeManager
{
    /**
     * @var string 主题根目录
     */
    private string $themesRoot;

    /**
     * @var string 状态文件路径
     */
    private string $stateFilePath;

    /**
     * @var TemplateResolver|null 模板解析器
     */
    private ?TemplateResolver $templateResolver = null;

    /**
     * @var ConfigManager 配置管理器
     */
    private ConfigManager $configManager;

    /**
     * @var FrontendMapper|null 前端映射器
     */
    private ?FrontendMapper $frontendMapper = null;

    /**
     * @var array<string, array{instance: Theme, config: array, path: string, type: string}> 主题实例缓存
     */
    private array $themeInstances = [];

    /**
     * @var array<string, string> 主题类型目录映射
     */
    private array $themeTypeDirs = [
        'cart' => 'public/cart/',
        'clientarea' => 'public/clientarea/',
        'web' => 'public/web/'
    ];

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->themesRoot = realpath(__DIR__ . '/../../public/web') ?: (__DIR__ . '/../../public/web');
        $this->stateFilePath = __DIR__ . '/../../storage/framework/theme_state.json';
        $this->configManager = new ConfigManager();
    }

    /**
     * 设置前端映射器
     *
     * @param FrontendMapper $frontendMapper
     * @return void
     */
    public function setFrontendMapper(FrontendMapper $frontendMapper): void
    {
        $this->frontendMapper = $frontendMapper;
    }

    /**
     * 获取前端映射器
     *
     * @return FrontendMapper|null
     */
    public function getFrontendMapper(): ?FrontendMapper
    {
        return $this->frontendMapper;
    }

    /**
     * 获取主题目录（公开方法）
     *
     * @param string $themeType 主题类型
     * @param string $themeId 主题ID
     * @return string
     */
    public function getThemeDir(string $themeType = 'web', string $themeId = ''): string
    {
        // 统一返回绝对路径，避免相对路径在不同工作目录下导致验证/加载失败
        $projectRoot = realpath(__DIR__ . '/../../') ?: (__DIR__ . '/../../');
        $baseDir = $this->themeTypeDirs[$themeType] ?? 'public/web/';

        // 主题类型根目录（绝对路径）
        $absoluteBaseDir = rtrim($projectRoot . DIRECTORY_SEPARATOR . trim($baseDir, '/\\'), '/\\');

        if ($themeId === '') {
            return $absoluteBaseDir;
        }

        return $absoluteBaseDir . DIRECTORY_SEPARATOR . trim($themeId, '/\\');
    }

    /**
     * 获取模板解析器
     *
     * @return TemplateResolver
     */
    public function getTemplateResolver(): TemplateResolver
    {
        if (!$this->templateResolver) {
            $this->templateResolver = new TemplateResolver($this);
        }
        return $this->templateResolver;
    }

    /**
     * 渲染模板
     *
     * @param string $templateName 模板名称
     * @param array $data 模板数据
     * @return string
     * @throws \RuntimeException
     */
    public function render(string $templateName, array $data = []): string
    {
        return $this->getTemplateResolver()->render($templateName, $data);
    }

    /**
     * 获取主题配置
     *
     * @param string $themeId 主题ID
     * @return array<string, mixed>
     */
    public function getThemeConfig(string $themeId): array
    {
        return $this->configManager->load('theme_' . $themeId);
    }

    /**
     * 保存主题配置
     *
     * @param string $themeId 主题ID
     * @param array<string, mixed> $config 配置数据
     * @return bool
     */
    public function saveThemeConfig(string $themeId, array $config): bool
    {
        return $this->configManager->save('theme_' . $themeId, $config);
    }

    /**
     * 列出所有主题
     *
     * @param string $themeType 主题类型
     * @return array<int, array<string, mixed>>
     */
    public function listThemes(string $themeType = 'web'): array
    {
        $themes = [];
        foreach ($this->getThemeIds($themeType) as $themeId) {
            $manifest = $this->getThemeManifest($themeType, $themeId);
            $validation = $this->validateTheme($themeType, $themeId);
            $themes[] = [
                'id' => $themeId,
                'name' => $manifest['name'] ?? $themeId,
                'version' => $manifest['version'] ?? null,
                'description' => $manifest['description'] ?? null,
                'author' => $manifest['author'] ?? null,
                'preview' => $manifest['preview'] ?? null,
                'parent' => $manifest['parent'] ?? null,
                'valid' => $validation['valid'],
                'missing' => $validation['missing'],
            ];
        }
        return $themes;
    }

    /**
     * 获取指定类型的主题数量
     *
     * @param string $themeType 主题类型
     * @return int
     */
    public function getThemeCount(string $themeType = 'web'): int
    {
        return count($this->getThemeIds($themeType));
    }

    /**
     * 当前是否只存在一个主题
     *
     * @param string $themeType 主题类型
     * @return bool
     */
    public function isSingleTheme(string $themeType = 'web'): bool
    {
        return $this->getThemeCount($themeType) <= 1;
    }

    /**
     * 是否允许对主题进行修改/删除等操作
     *
     * 约束：当系统中仅有一个该类型的主题时，不允许对该主题执行删除等破坏性操作
     *
     * @param string $themeType 主题类型
     * @param string $themeId 主题ID
     * @return bool
     */
    public function canModifyTheme(string $themeType, string $themeId): bool
    {
        // 只有一个主题时，禁止对该主题执行危险操作
        if ($this->isSingleTheme($themeType)) {
            return false;
        }

        return true;
    }

    /**
     * 获取活动主题ID
     *
     * @param string $themeType 主题类型
     * @return string|null
     */
    public function getActiveThemeId(string $themeType = 'web'): ?string
    {
        $themeId = null;
        $stateKey = 'active_theme_' . $themeType;
        
        if (is_readable($this->stateFilePath)) {
            $data = json_decode((string) file_get_contents($this->stateFilePath), true);
            if (is_array($data) && !empty($data[$stateKey])) {
                $themeId = (string) $data[$stateKey];
            }
        }

        if ($themeId && $this->validateTheme($themeType, $themeId)['valid']) {
            return $themeId;
        }

        foreach ($this->getThemeIds($themeType) as $candidate) {
            if ($this->validateTheme($themeType, $candidate)['valid']) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * 获取活动主题键
     *
     * @return string|null
     */
    public function getActiveThemeKey(): ?string
    {
        return $this->getActiveThemeId();
    }

    /**
     * 激活主题
     *
     * @param string $themeType 主题类型
     * @param string $themeId 主题ID
     * @return bool
     */
    public function activateTheme(string $themeType, string $themeId): bool
    {
        $validation = $this->validateTheme($themeType, $themeId);
        if (!$validation['valid']) {
            return false;
        }

        $dir = dirname($this->stateFilePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $stateKey = 'active_theme_' . $themeType;
        $payload = json_decode((string) file_get_contents($this->stateFilePath), true) ?: [];
        $payload[$stateKey] = $themeId;
        
        return file_put_contents($this->stateFilePath, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) !== false;
    }

    /**
     * 加载活动主题实例
     *
     * @param string $themeType 主题类型
     * @return Theme|null
     */
    public function loadActiveThemeInstance(string $themeType = 'web'): ?Theme
    {
        $themeId = $this->getActiveThemeId($themeType);
        if (!$themeId) {
            return null;
        }
        return $this->loadThemeInstance($themeType, $themeId);
    }

    /**
     * 加载主题实例
     *
     * @param string $themeType 主题类型
     * @param string $themeId 主题ID
     * @return Theme|null
     */
    public function loadThemeInstance(string $themeType, string $themeId): ?Theme
    {
        $cacheKey = $themeType . '_' . $themeId;
        
        // 检查缓存
        if (isset($this->themeInstances[$cacheKey])) {
            return $this->themeInstances[$cacheKey]['instance'];
        }

        $validation = $this->validateTheme($themeType, $themeId);
        if (!$validation['valid']) {
            return null;
        }

        $themeDir = $this->getThemeDir($themeType, $themeId);
        $themePhp = $themeDir . '/Theme.php';

        // 主题基类 Core\Theme 已通过 Composer 自动加载器加载，无需手动 require

        if (!is_readable($themePhp)) {
            return null;
        }
        require_once $themePhp;

        $class = $this->getThemeClassName($themePhp);
        if (!$class || !class_exists($class)) {
            return null;
        }

        $config = $this->getThemeManifest($themeType, $themeId);
        $instance = new $class($config);
        
        // 缓存实例
        $this->themeInstances[$cacheKey] = [
            'instance' => $instance,
            'config' => $config,
            'path' => $themeDir,
            'type' => $themeType
        ];
        
        return $instance;
    }

    /**
     * 获取主题继承链
     *
     * @param string $themeType 主题类型
     * @param string $themeId 主题ID
     * @return array<int, string>
     */
    public function getThemeInheritanceChain(string $themeType, string $themeId): array
    {
        $chain = [];
        $visited = [];

        while ($themeId && !in_array($themeId, $visited, true)) {
            $visited[] = $themeId;
            $chain[] = $themeId;

            $manifest = $this->getThemeManifest($themeType, $themeId);
            $parent = $manifest['parent'] ?? null;

            if ($parent && $this->validateTheme($themeType, $parent)['valid']) {
                $themeId = $parent;
            } else {
                break;
            }
        }

        return $chain;
    }

    /**
     * 验证主题
     *
     * @param string $themeType 主题类型
     * @param string $themeId 主题ID
     * @return array{valid: bool, missing: array<int, string>}
     */
    public function validateTheme(string $themeType, string $themeId): array
    {
        $missing = [];
        $themeDir = $this->getThemeDir($themeType, $themeId);
        
        foreach ($this->getRequiredThemeFiles() as $relative) {
            $path = $themeDir . '/' . $relative;
            if (!is_file($path)) {
                $missing[] = $relative;
            }
        }
        
        return ['valid' => count($missing) === 0, 'missing' => $missing];
    }

    /**
     * 获取主题ID列表
     *
     * @param string $themeType 主题类型
     * @return array<int, string>
     */
    private function getThemeIds(string $themeType = 'web'): array
    {
        $themesRoot = $this->getThemeDir($themeType);
        
        if (!is_dir($themesRoot)) {
            return [];
        }
        
        $dirs = array_filter(scandir($themesRoot), function ($name) use ($themesRoot) {
            if ($name === '.' || $name === '..') return false;
            return is_dir($themesRoot . '/' . $name);
        });
        
        $dirs = array_values($dirs);
        sort($dirs);
        return $dirs;
    }

    /**
     * 获取主题清单
     *
     * @param string $themeType 主题类型
     * @param string $themeId 主题ID
     * @return array<string, mixed>
     */
    private function getThemeManifest(string $themeType, string $themeId): array
    {
        $path = $this->getThemeDir($themeType, $themeId) . '/theme.json';
        if (!is_readable($path)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($path), true);
        return is_array($data) ? $data : [];
    }

    /**
     * 获取主题类名
     *
     * @param string $themePhpPath 主题PHP文件路径
     * @return string|null
     */
    private function getThemeClassName(string $themePhpPath): ?string
    {
        $src = @file_get_contents($themePhpPath);
        if (!is_string($src) || $src === '') {
            return null;
        }

        $ns = null;
        if (preg_match('/namespace\s+([^;]+);/i', $src, $m)) {
            $ns = trim($m[1]);
        }
        if (!$ns) {
            return null;
        }
        return $ns . '\\Theme';
    }

    /**
     * 获取必需的主题文件
     *
     * @return array<int, string>
     */
    private function getRequiredThemeFiles(): array
    {
        return [
            'Theme.php',
            'theme.json',
            'templates/layout.php',
            'templates/home.php',
            'assets/css/style.css',
            'assets/css/pages/home.css',
            'assets/css/pages/login.css',
            'assets/js/theme.js',
        ];
    }

    /**
     * 获取主题CSS文件
     *
     * @param string $themeType 主题类型
     * @param string $themeId 主题ID
     * @return array<int, string>
     */
    public function getThemeCssFiles(string $themeType, string $themeId): array
    {
        $themeDir = $this->getThemeDir($themeType, $themeId);
        $cssFiles = [];
        
        $cssDirs = [
            $themeDir . '/assets/css',
            $themeDir . '/static/css'
        ];
        
        foreach ($cssDirs as $dir) {
            if (is_dir($dir)) {
                $files = glob($dir . '/*.css');
                if ($files !== false) {
                    $cssFiles = array_merge($cssFiles, $files);
                }
            }
        }
        
        return $cssFiles;
    }

    /**
     * 获取主题JS文件
     *
     * @param string $themeType 主题类型
     * @param string $themeId 主题ID
     * @return array<int, string>
     */
    public function getThemeJsFiles(string $themeType, string $themeId): array
    {
        $themeDir = $this->getThemeDir($themeType, $themeId);
        $jsFiles = [];
        
        $jsDirs = [
            $themeDir . '/assets/js',
            $themeDir . '/static/js'
        ];
        
        foreach ($jsDirs as $dir) {
            if (is_dir($dir)) {
                $files = glob($dir . '/*.js');
                if ($files !== false) {
                    $jsFiles = array_merge($jsFiles, $files);
                }
            }
        }
        
        return $jsFiles;
    }

    /**
     * 清除主题缓存
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->themeInstances = [];
        
        if ($this->templateResolver) {
            $this->templateResolver->clearCache();
        }
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
