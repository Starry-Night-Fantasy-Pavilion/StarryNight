<?php

declare(strict_types=1);

namespace themes\web\default;

use Core\Theme as BaseTheme;

/**
 * 星夜主题
 *
 * 星夜阁前台默认主题实现
 */
class Theme extends BaseTheme
{
    /**
     * 主题信息
     * @var array<string, mixed>
     */
    protected array $info = [
        'name' => '星夜',
        'version' => '1.0.0',
        'description' => '星夜阁前台主题',
        'author' => '星夜',
    ];

    /**
     * 主题类型
     * @var string
     */
    protected string $type = 'web';

    /**
     * 父主题名称
     * @var string|null
     */
    protected ?string $parent = null;

    /**
     * 安装主题
     *
     * @return bool
     */
    public function install(): bool
    {
        // 创建必要的目录
        $directories = [
            'config',
            'templates',
            'assets/css',
            'assets/js',
            'assets/images',
            'language',
        ];

        foreach ($directories as $dir) {
            $path = $this->pluginDir . '/' . $dir;
            if (!is_dir($path)) {
                @mkdir($path, 0755, true);
            }
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
        // 清理缓存
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
        // 可以在这里注册钩子或执行其他激活逻辑
        return true;
    }

    /**
     * 停用主题
     *
     * @return bool
     */
    public function deactivate(): bool
    {
        // 清理资源
        return true;
    }

    /**
     * 获取菜单项
     *
     * @return array<int, array<string, mixed>>
     */
    public function getMenuItems(): array
    {
        return [
            [
                'name' => 'Home',
                'url' => '/',
                'icon' => 'home',
            ],
            [
                'name' => 'Products',
                'url' => '/products',
                'icon' => 'box',
            ],
            [
                'name' => 'About',
                'url' => '/about',
                'icon' => 'info',
            ],
        ];
    }

    /**
     * 获取主题配置表单
     *
     * @return array<string, array<string, mixed>>
     */
    public function getConfigForm(): array
    {
        return [
            'primary_color' => [
                'type' => 'color',
                'label' => 'Primary Color',
                'default' => '#00f2ff',
            ],
            'secondary_color' => [
                'type' => 'color',
                'label' => 'Secondary Color',
                'default' => '#7000ff',
            ],
            'background_color' => [
                'type' => 'color',
                'label' => 'Background Color',
                'default' => '#0a0a0a',
            ],
            'text_color' => [
                'type' => 'color',
                'label' => 'Text Color',
                'default' => '#ffffff',
            ],
        ];
    }
}
