<?php

declare(strict_types=1);

namespace Core;

/**
 * 主题基类
 * 
 * 所有主题必须继承此类，实现标准化的主题接口
 * 基于智简魔方财务系统主题架构规范
 */
abstract class Theme
{
    /**
     * 主题信息
     * @var array<string, mixed>
     */
    protected array $info = [];

    /**
     * 主题配置
     * @var array<string, mixed>
     */
    protected array $config = [];

    /**
     * 主题目录
     * @var string
     */
    protected string $themeDir = '';

    /**
     * 主题类型
     * @var string
     */
    protected string $type = '';

    /**
     * 父主题名称
     * @var string|null
     */
    protected ?string $parent = null;

    /**
     * 构造函数
     *
     * @param array $config 主题配置
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->themeDir = $this->getThemeDirectory();
    }

    /**
     * 获取主题目录
     *
     * @return string
     */
    protected function getThemeDirectory(): string
    {
        $reflection = new \ReflectionClass($this);
        return dirname($reflection->getFileName());
    }

    /**
     * 安装主题
     *
     * @return bool
     */
    public function install(): bool
    {
        // 创建主题配置目录
        $configDir = $this->themeDir . '/config';
        if (!is_dir($configDir)) {
            @mkdir($configDir, 0755, true);
        }

        return true;
    }

    /**
     * 卸载主题
     *
     * @return bool
     */
    public function uninstall(): bool
    {
        // 清理主题缓存
        $this->clearCache();
        return true;
    }

    /**
     * 激活主题
     *
     * @return bool
     */
    public function activate(): bool
    {
        // 子类可以重写此方法
        return true;
    }

    /**
     * 停用主题
     *
     * @return bool
     */
    public function deactivate(): bool
    {
        // 子类可以重写此方法
        return true;
    }

    /**
     * 获取主题信息
     *
     * @return array<string, mixed>
     */
    public function getInfo(): array
    {
        return array_merge($this->info, [
            'type' => $this->type,
            'parent' => $this->parent,
            'path' => $this->themeDir
        ]);
    }

    /**
     * 获取主题配置
     *
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * 保存主题配置
     *
     * @param array $config 配置数据
     * @return void
     */
    public function saveConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
        
        // 保存到配置文件
        $configFile = $this->themeDir . '/config/theme.json';
        $configDir = dirname($configFile);
        
        if (!is_dir($configDir)) {
            @mkdir($configDir, 0755, true);
        }
        
        file_put_contents($configFile, json_encode($this->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * 获取主题类型
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * 获取主题目录
     *
     * @return string
     */
    public function getThemeDir(): string
    {
        return $this->themeDir;
    }

    /**
     * 获取父主题名称
     *
     * @return string|null
     */
    public function getParent(): ?string
    {
        return $this->parent;
    }

    /**
     * 获取模板路径
     *
     * @param string $templateName 模板名称
     * @return string|null
     */
    public function getTemplatePath(string $templateName): ?string
    {
        // 如果模板名已经包含.php扩展名，直接使用；否则添加.php
        if (!str_ends_with($templateName, '.php')) {
            $templateName .= '.php';
        }
        
        // 优先在templates目录下查找
        $templatePath = $this->themeDir . '/templates/' . $templateName;
        if (file_exists($templatePath)) {
            return $templatePath;
        }
        
        // 如果templates目录下没有，尝试直接在主题目录下查找
        $templatePath = $this->themeDir . '/' . $templateName;
        if (file_exists($templatePath)) {
            return $templatePath;
        }
        
        return null;
    }

    /**
     * 获取资源路径
     *
     * @param string $assetName 资源名称
     * @return string|null
     */
    public function getAssetPath(string $assetName): ?string
    {
        $assetPath = $this->themeDir . '/assets/' . $assetName;
        
        if (file_exists($assetPath)) {
            return $assetPath;
        }
        
        return null;
    }

    /**
     * 获取所有CSS文件
     *
     * @return array<int, string>
     */
    public function getCssFiles(): array
    {
        $cssFiles = [];
        $cssDirs = [
            $this->themeDir . '/assets/css',
            $this->themeDir . '/static/css'
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
     * 获取所有JS文件
     *
     * @return array<int, string>
     */
    public function getJsFiles(): array
    {
        $jsFiles = [];
        $jsDirs = [
            $this->themeDir . '/assets/js',
            $this->themeDir . '/static/js'
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
     * 获取语言包
     *
     * @param string $language 语言代码
     * @return array<string, mixed>
     */
    public function getLanguage(string $language = 'zh-cn'): array
    {
        $langFile = $this->themeDir . '/language/' . $language . '.php';
        
        if (file_exists($langFile)) {
            return include $langFile;
        }
        
        return [];
    }

    /**
     * 渲染模板
     *
     * @param string $templateName 模板名称
     * @param array $data 模板数据
     * @return string
     */
    public function render(string $templateName, array $data = []): string
    {
        $templatePath = $this->getTemplatePath($templateName);
        
        if (!$templatePath) {
            throw new \RuntimeException("模板文件不存在: {$templateName}");
        }
        
        return $this->executeTemplate($templatePath, $data);
    }

    /**
     * 渲染模板（别名方法，兼容旧代码）
     *
     * @param string $templateName 模板名称
     * @param array $data 模板数据
     * @return string
     */
    public function renderTemplate(string $templateName, array $data = []): string
    {
        return $this->render($templateName, $data);
    }

    /**
     * 执行模板
     *
     * @param string $templatePath 模板路径
     * @param array $data 模板数据
     * @return string
     */
    protected function executeTemplate(string $templatePath, array $data): string
    {
        extract($data);
        ob_start();
        include $templatePath;
        return ob_get_clean();
    }

    /**
     * 清除缓存
     *
     * @return void
     */
    public function clearCache(): void
    {
        // 子类可以重写此方法
    }

    /**
     * 获取主题截图
     *
     * @return string|null
     */
    public function getScreenshot(): ?string
    {
        $screenshotFiles = ['theme.jpg', 'theme.png', 'screenshot.jpg', 'screenshot.png'];
        
        foreach ($screenshotFiles as $file) {
            $path = $this->themeDir . '/' . $file;
            if (file_exists($path)) {
                return $path;
            }
        }
        
        return null;
    }

    /**
     * 检查是否支持响应式
     *
     * @return bool
     */
    public function isResponsive(): bool
    {
        return $this->info['responsive'] ?? true;
    }

    /**
     * 检查是否支持RTL
     *
     * @return bool
     */
    public function isRtlSupport(): bool
    {
        return $this->info['rtl_support'] ?? false;
    }
}
