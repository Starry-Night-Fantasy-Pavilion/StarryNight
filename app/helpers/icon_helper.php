<?php
/**
 * SVG图标辅助函数
 * 从文件读取SVG图标并渲染
 * 
 * 图标路径优先级：
 * 1. 管理端页面：使用 /static/admin/icons/ 目录（与主题包完全隔离）
 * 2. 前台页面：使用当前主题包的 /assets/icons/ 目录
 */

/**
 * 判断当前是否为管理端请求
 * @return bool
 */
function is_admin_request(): bool {
    // 获取管理端路径前缀
    $adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '/');
    
    // 检查当前请求URI是否以管理端路径开头
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $requestPath = parse_url($requestUri, PHP_URL_PATH) ?: '';
    
    // 检查是否在管理端目录下
    return strpos($requestPath, '/' . $adminPrefix) === 0 
        || strpos($requestPath, '/' . $adminPrefix . '/') === 0;
}

/**
 * 渲染SVG图标
 * @param string $iconName 图标名称（不包含.svg扩展名）
 * @param array $attributes 额外的SVG属性（如width, height, class等）
 * @return string SVG HTML
 */
function render_icon(string $iconName, array $attributes = []): string {
    // 检查DOMDocument类是否可用
    if (!class_exists('DOMDocument')) {
        return '<!-- DOMDocument not available -->';
    }
    
    $iconPath = null;
    
    // 判断是否为管理端请求，管理端使用独立的图标目录
    if (is_admin_request()) {
        // 管理端：使用独立的图标目录，不依赖主题包
        $iconPath = __DIR__ . '/../../public/static/admin/icons/' . $iconName . '.svg';
    } else {
        // 前台：优先从主题包内的图标目录查找
        $themeIconPath = null;
        
        // 尝试获取当前活动的主题路径
        try {
            if (class_exists('\app\services\ThemeManager') && class_exists('\app\config\FrontendConfig')) {
                $themeManager = new \app\services\ThemeManager();
                $activeThemeId = $themeManager->getActiveThemeId(\app\config\FrontendConfig::THEME_TYPE_WEB) ?? \app\config\FrontendConfig::THEME_DEFAULT;
                $themeBasePath = \app\config\FrontendConfig::getThemePath($activeThemeId);
                $themeIconPath = __DIR__ . '/../../public' . $themeBasePath . '/assets/icons/' . $iconName . '.svg';
            }
        } catch (\Throwable $e) {
            // 忽略错误，继续尝试默认路径
        }
        
        // 如果主题包内找到，使用主题包内的图标
        if ($themeIconPath && file_exists($themeIconPath)) {
            $iconPath = $themeIconPath;
        } else {
            // 前台回退到默认主题图标目录
            $iconPath = __DIR__ . '/../../public/web/default/assets/icons/' . $iconName . '.svg';
        }
    }
    
    if (!$iconPath || !file_exists($iconPath)) {
        return '<!-- Icon not found: ' . htmlspecialchars($iconName) . ' -->';
    }
    
    $svgContent = file_get_contents($iconPath);
    if ($svgContent === false) {
        return '<!-- Failed to read icon file: ' . htmlspecialchars($iconName) . ' -->';
    }
    
    // 解析SVG内容
    try {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $loaded = @$dom->loadXML($svgContent);
        libxml_clear_errors();
        
        if (!$loaded) {
            return '<!-- Failed to parse SVG: ' . htmlspecialchars($iconName) . ' -->';
        }
        
        $svg = $dom->getElementsByTagName('svg')->item(0);
        
        if (!$svg) {
            return '<!-- Invalid SVG: ' . htmlspecialchars($iconName) . ' -->';
        }
    } catch (\Exception $e) {
        return '<!-- Error rendering icon: ' . htmlspecialchars($iconName) . ' -->';
    }
    
    // 设置默认属性
    $defaultAttributes = [
        'width' => '18',
        'height' => '18',
        'viewBox' => '0 0 24 24',
        'fill' => 'none',
        'stroke' => 'currentColor',
        'stroke-width' => '2',
        'stroke-linecap' => 'round',
        'stroke-linejoin' => 'round'
    ];
    
    // 合并属性
    $finalAttributes = array_merge($defaultAttributes, $attributes);
    
    // 应用属性
    foreach ($finalAttributes as $key => $value) {
        $svg->setAttribute($key, (string)$value);
    }
    
    // 返回SVG HTML
    return $dom->saveHTML($svg);
}

/**
 * 渲染图标（直接返回SVG，不包装在span中）
 * @param string $iconName 图标名称
 * @param array $attributes SVG属性
 * @return string 完整的图标HTML
 */
function icon(string $iconName, array $attributes = []): string {
    return render_icon($iconName, $attributes);
}
