<?php

declare(strict_types=1);

namespace app\services;

/**
 * 模板解析器
 * 
 * 处理模板路径解析和渲染，支持主题继承机制
 * PHP 8.0+ 兼容版本
 */
class TemplateResolver
{
    /**
     * @var ThemeManager 主题管理器
     */
    private ThemeManager $themeManager;

    /**
     * @var array<string, string> 模板目录缓存
     */
    private array $templateDirs = [];

    /**
     * @var string 模板缓存目录
     */
    private string $cacheDir;

    /**
     * 构造函数
     *
     * @param ThemeManager $themeManager
     */
    public function __construct(ThemeManager $themeManager)
    {
        $this->themeManager = $themeManager;
        $this->cacheDir = __DIR__ . '/../../storage/framework/template_cache/';
        $this->ensureCacheDirectory();
    }

    /**
     * 确保缓存目录存在
     *
     * @return void
     */
    private function ensureCacheDirectory(): void
    {
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * 解析模板路径
     *
     * @param string $templateName 模板名称
     * @return string|null
     */
    public function resolve(string $templateName): ?string
    {
        // 当前仅前台 web 站点使用主题，因此主题类型固定为 web
        $themeType = 'web';
        $activeTheme = $this->themeManager->getActiveThemeId($themeType) ?? 'default';

        // 优先使用当前启用的 web 主题（ThemeManager 会返回绝对路径）
        $themeDir = $this->themeManager->getThemeDir($themeType, $activeTheme);
        $templatePath = rtrim($themeDir, '/\\') . '/templates/' . $templateName;

        if (file_exists($templatePath)) {
            return $templatePath;
        }

        // 在默认 web 主题中查找（public/web/default）
        $defaultThemeDir = $this->themeManager->getThemeDir($themeType, 'default');
        $defaultTemplatePath = rtrim($defaultThemeDir, '/\\') . '/templates/' . $templateName;

        if (file_exists($defaultTemplatePath)) {
            return $defaultTemplatePath;
        }

        return null;
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
        $templatePath = $this->resolve($templateName);

        if (!$templatePath) {
            throw new \RuntimeException("模板文件不存在: {$templateName}");
        }

        // 编译模板
        $compiledPath = $this->compile($templatePath);

        // 执行模板
        return $this->execute($compiledPath, $data);
    }

    /**
     * 编译模板
     *
     * @param string $templatePath 模板路径
     * @return string 编译后的路径
     */
    private function compile(string $templatePath): string
    {
        $cacheKey = md5($templatePath);
        $cacheFile = $this->cacheDir . $cacheKey . '.php';

        // 检查缓存是否有效
        if (file_exists($cacheFile)) {
            $templateMtime = filemtime($templatePath);
            $cacheMtime = filemtime($cacheFile);

            if ($cacheMtime >= $templateMtime) {
                return $cacheFile;
            }
        }

        // 读取并编译模板内容
        $content = file_get_contents($templatePath);
        if ($content === false) {
            throw new \RuntimeException("无法读取模板文件: {$templatePath}");
        }

        $compiled = $this->parse($content);

        // 保存编译后的内容
        file_put_contents($cacheFile, $compiled, LOCK_EX);

        return $cacheFile;
    }

    /**
     * 解析模板语法
     *
     * @param string $content 模板内容
     * @return string
     */
    private function parse(string $content): string
    {
        $patterns = [
            // 变量输出: {$variable}
            '/\{\s*\$([a-zA-Z_][a-zA-Z0-9_]*)\s*\}/' => '<?php echo htmlspecialchars($\1 ?? \'\', ENT_QUOTES, \'UTF-8\'); ?>',

            // if 语句: {if condition}
            '/\{\s*if\s+([^\}]+)\s*\}/' => '<?php if (\1): ?>',

            // else 语句: {else}
            '/\{\s*else\s*\}/' => '<?php else: ?>',

            // elseif 语句: {elseif condition}
            '/\{\s*elseif\s+([^\}]+)\s*\}/' => '<?php elseif (\1): ?>',

            // endif 语句: {/if}
            '/\{\s*\/if\s*\}/' => '<?php endif; ?>',

            // foreach 语句: {foreach $array as $item}
            '/\{\s*foreach\s+\$([a-zA-Z_][a-zA-Z0-9_]*)\s+as\s+\$([a-zA-Z_][a-zA-Z0-9_]*)\s*\}/' => '<?php foreach ($\1 as $\2): ?>',

            // endforeach 语句: {/foreach}
            '/\{\s*\/foreach\s*\}/' => '<?php endforeach; ?>',

            // include 语句: {include 'template'}
            '/\{\s*include\s+[\'"]([^\'"]+)[\'"]\s*\}/' => '<?php echo $this->render(\'\1\', get_defined_vars()); ?>',

            // 不转义的变量输出: {!$variable}
            '/\{\s*!\$([a-zA-Z_][a-zA-Z0-9_]*)\s*\}/' => '<?php echo $\1 ?? \'\'; ?>',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }

    /**
     * 执行编译后的模板
     *
     * @param string $compiledPath 编译后的模板路径
     * @param array $data 模板数据
     * @return string
     */
    private function execute(string $compiledPath, array $data): string
    {
        extract($data);
        // 调试：记录传递给模板的变量
        if (isset($data['third_party_login_buttons'])) {
            error_log('TemplateResolver: third_party_login_buttons 已传递，数量: ' . (is_array($data['third_party_login_buttons']) ? count($data['third_party_login_buttons']) : 'N/A'));
        } else {
            error_log('TemplateResolver: third_party_login_buttons 未传递');
            error_log('TemplateResolver: 传递的变量键: ' . implode(', ', array_keys($data)));
        }
        ob_start();
        include $compiledPath;
        $content = ob_get_clean();
        return $content;
    }

    /**
     * 清除模板缓存
     *
     * @return void
     */
    public function clearCache(): void
    {
        $files = glob($this->cacheDir . '*.php');
        if ($files === false) {
            return;
        }
        foreach ($files as $file) {
            @unlink($file);
        }
    }

    /**
     * 检查模板是否存在
     *
     * @param string $templateName 模板名称
     * @return bool
     */
    public function exists(string $templateName): bool
    {
        return $this->resolve($templateName) !== null;
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
