# 智简魔方财务系统 - 主题架构实现文档

## 目录
1. [系统架构概述](#系统架构概述)
2. [核心架构设计](#核心架构设计)
3. [主题包结构](#主题包结构)
4. [继承机制](#继承机制)
5. [切换功能](#切换功能)
6. [响应式设计](#响应式设计)
7. [迁移指南](#迁移指南)

---

## 系统架构概述

### 主题系统设计理念

智简魔方财务系统的主题架构基于**分层、模块化、可继承**的设计理念：

- **分层架构**: 前端展示层与业务逻辑层分离
- **模块化设计**: 主题由多个模块组成，可独立开发和维护
- **继承机制**: 子主题可以继承父主题，实现代码复用
- **响应式设计**: 支持多设备适配，提供一致的用户体验

### 主题类型分类

系统支持多种主题类型，每种类型服务于不同的业务场景：

| 主题类型 | 目录位置 | 主要用途 | 特点 |
|---------|----------|---------|------|
| 购物车主题 | `public/themes/cart/` | 产品选择和配置 | 产品展示、价格计算、配置选项 |
| 客户端主题 | `public/themes/clientarea/` | 用户中心和管理 | 订单管理、服务管理、账户设置 |
| 网站主题 | `public/themes/web/` | 官方网站展示 | 公司介绍、产品展示、新闻资讯 |

### 架构层次

```
┌─────────────────────────────────────────────────────────────┐
│                    表现层 (Presentation Layer)             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │  模板引擎    │  │  样式系统    │  │  资源管理    │ │
│  │  (Template)  │  │  (Styles)    │  │  (Assets)    │ │
│  └──────────────┘  └──────────────┘  └──────────────┘ │
├─────────────────────────────────────────────────────────────┤
│                  主题管理层 (Theme Layer)                 │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  主题管理器 (Theme Manager)                      │  │
│  │  - 主题发现与加载                                 │  │
│  │  - 主题继承解析                                   │  │
│  │  - 主题切换管理                                   │  │
│  │  - 模板路径解析                                   │  │
│  └──────────────────────────────────────────────────────┘  │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │  购物车主题  │  │  客户端主题  │  │  网站主题    │ │
│  │  (Cart)      │  │  (Clientarea)│  │  (Web)       │ │
│  └──────────────┘  └──────────────┘  └──────────────┘ │
├─────────────────────────────────────────────────────────────┤
│                  核心服务层 (Core Services)             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │  配置管理    │  │  缓存服务    │  │  国际化服务  │ │
│  └──────────────┘  └──────────────┘  └──────────────┘ │
├─────────────────────────────────────────────────────────────┤
│                  数据层 (Data Layer)                     │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │  主题配置    │  │  用户偏好    │  │  系统设置    │ │
│  └──────────────┘  └──────────────┘  └──────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

---

## 核心架构设计

### 1. 主题管理器 (Theme Manager)

主题管理器是主题系统的核心，负责主题的发现、加载、切换和继承解析。

```php
<?php
namespace app\common\lib;

class ThemeManager
{
    private $themes = [];
    private $activeThemes = [];
    private $themeDirs = [
        'cart' => 'public/themes/cart/',
        'clientarea' => 'public/themes/clientarea/',
        'web' => 'public/themes/web/'
    ];
    private $inheritanceMap = [];
    
    public function __construct()
    {
        $this->discoverThemes();
        $this->loadActiveThemes();
    }
    
    /**
     * 发现所有主题
     */
    public function discoverThemes()
    {
        foreach ($this->themeDirs as $type => $dir) {
            $this->themes[$type] = $this->discoverTypeThemes($type, $dir);
        }
        
        $this->resolveInheritance();
    }
    
    /**
     * 发现特定类型的主题
     */
    public function discoverTypeThemes($type, $dir)
    {
        $themes = [];
        
        if (!is_dir($dir)) {
            return $themes;
        }
        
        $items = scandir($dir);
        
        foreach ($items as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            
            $themePath = $dir . $item;
            
            if (is_dir($themePath)) {
                $themeInfo = $this->getThemeInfo($type, $item);
                
                if ($themeInfo) {
                    $themes[$item] = $themeInfo;
                }
            }
        }
        
        return $themes;
    }
    
    /**
     * 获取主题信息
     */
    public function getThemeInfo($type, $name)
    {
        $configFile = $this->themeDirs[$type] . $name . '/theme.config';
        
        if (!file_exists($configFile)) {
            return null;
        }
        
        $config = $this->parseThemeConfig($configFile);
        
        if (!$config) {
            return null;
        }
        
        $config['type'] = $type;
        $config['name'] = $name;
        $config['path'] = $this->themeDirs[$type] . $name . '/';
        $config['screenshot'] = $this->getThemeScreenshot($type, $name);
        
        return $config;
    }
    
    /**
     * 解析主题配置文件
     */
    public function parseThemeConfig($configFile)
    {
        $lines = file($configFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $config = [];
        
        foreach ($lines as $line) {
            if (strpos($line, '=') === false) {
                continue;
            }
            
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            if (!empty($key)) {
                $config[$key] = $value;
            }
        }
        
        return $config;
    }
    
    /**
     * 获取主题截图
     */
    public function getThemeScreenshot($type, $name)
    {
        $screenshotFiles = [
            'theme.jpg',
            'theme.png',
            'theme.svg',
            'screenshot.jpg',
            'screenshot.png'
        ];
        
        $themePath = $this->themeDirs[$type] . $name . '/';
        
        foreach ($screenshotFiles as $file) {
            $filePath = $themePath . $file;
            
            if (file_exists($filePath)) {
                return '/' . str_replace('\\', '/', $filePath);
            }
        }
        
        return '/public/themes/default/theme.jpg';
    }
    
    /**
     * 解析主题继承关系
     */
    public function resolveInheritance()
    {
        $this->inheritanceMap = [];
        
        foreach ($this->themes as $type => $themes) {
            foreach ($themes as $name => $theme) {
                if (isset($theme['parent'])) {
                    $parentTheme = $theme['parent'];
                    $this->inheritanceMap[$type][$name] = $parentTheme;
                }
            }
        }
        
        // 检查循环继承
        foreach ($this->inheritanceMap as $type => $inheritance) {
            foreach ($inheritance as $child => $parent) {
                if ($this->hasCircularInheritance($type, $child, [])) {
                    unset($this->inheritanceMap[$type][$child]);
                }
            }
        }
    }
    
    /**
     * 检查循环继承
     */
    public function hasCircularInheritance($type, $themeName, $visited = [])
    {
        if (in_array($themeName, $visited)) {
            return true;
        }
        
        $visited[] = $themeName;
        
        if (!isset($this->inheritanceMap[$type][$themeName])) {
            return false;
        }
        
        $parentTheme = $this->inheritanceMap[$type][$themeName];
        return $this->hasCircularInheritance($type, $parentTheme, $visited);
    }
    
    /**
     * 获取主题继承链
     */
    public function getThemeInheritanceChain($type, $themeName)
    {
        $chain = [$themeName];
        $currentTheme = $themeName;
        
        while (isset($this->inheritanceMap[$type][$currentTheme])) {
            $parentTheme = $this->inheritanceMap[$type][$currentTheme];
            
            if (in_array($parentTheme, $chain)) {
                break;
            }
            
            $chain[] = $parentTheme;
            $currentTheme = $parentTheme;
        }
        
        return $chain;
    }
    
    /**
     * 设置活动主题
     */
    public function setActiveTheme($type, $themeName)
    {
        if (!isset($this->themes[$type][$themeName])) {
            throw new \InvalidArgumentException("主题不存在: {$themeName}");
        }
        
        $this->activeThemes[$type] = $themeName;
        
        // 保存到配置
        $config = new \app\common\lib\ConfigManager();
        $config->set('theme.' . $type, $themeName);
        
        // 清除缓存
        $this->clearThemeCache($type);
        
        return true;
    }
    
    /**
     * 获取活动主题
     */
    public function getActiveTheme($type)
    {
        if (isset($this->activeThemes[$type])) {
            return $this->activeThemes[$type];
        }
        
        // 从配置加载
        $config = new \app\common\lib\ConfigManager();
        $themeName = $config->get('theme.' . $type);
        
        if ($themeName && isset($this->themes[$type][$themeName])) {
            $this->activeThemes[$type] = $themeName;
            return $themeName;
        }
        
        // 返回默认主题
        $this->activeThemes[$type] = 'default';
        return 'default';
    }
    
    /**
     * 获取主题模板路径
     */
    public function getTemplatePath($type, $templateName)
    {
        $activeTheme = $this->getActiveTheme($type);
        $inheritanceChain = $this->getThemeInheritanceChain($type, $activeTheme);
        
        // 从子主题到父主题依次查找模板
        foreach ($inheritanceChain as $themeName) {
            $templatePath = $this->themeDirs[$type] . $themeName . '/' . $templateName;
            
            if (file_exists($templatePath)) {
                return $templatePath;
            }
        }
        
        // 在默认主题中查找
        $defaultTemplatePath = $this->themeDirs[$type] . 'default/' . $templateName;
        
        if (file_exists($defaultTemplatePath)) {
            return $defaultTemplatePath;
        }
        
        throw new \RuntimeException("模板文件不存在: {$templateName}");
    }
    
    /**
     * 获取主题资源路径
     */
    public function getAssetPath($type, $assetName)
    {
        $activeTheme = $this->getActiveTheme($type);
        $inheritanceChain = $this->getThemeInheritanceChain($type, $activeTheme);
        
        foreach ($inheritanceChain as $themeName) {
            $assetPath = $this->themeDirs[$type] . $themeName . '/assets/' . $assetName;
            
            if (file_exists($assetPath)) {
                return '/' . str_replace('\\', '/', $assetPath);
            }
        }
        
        $defaultAssetPath = $this->themeDirs[$type] . 'default/assets/' . $assetName;
        
        if (file_exists($defaultAssetPath)) {
            return '/' . str_replace('\\', '/', $defaultAssetPath);
        }
        
        return null;
    }
    
    /**
     * 清除主题缓存
     */
    public function clearThemeCache($type = null)
    {
        $cacheDir = 'runtime/theme_cache/';
        
        if ($type) {
            $cacheFile = $cacheDir . $type . '.cache';
            
            if (file_exists($cacheFile)) {
                unlink($cacheFile);
            }
        } else {
            if (is_dir($cacheDir)) {
                $files = glob($cacheDir . '*.cache');
                
                foreach ($files as $file) {
                    unlink($file);
                }
            }
        }
    }
    
    /**
     * 获取所有主题
     */
    public function getAllThemes()
    {
        return $this->themes;
    }
    
    /**
     * 获取特定类型的主题
     */
    public function getThemesByType($type)
    {
        return $this->themes[$type] ?? [];
    }
}
```

### 2. 模板引擎集成

```php
<?php
namespace app\common\lib;

class TemplateEngine
{
    private $themeManager;
    private $templateDirs = [];
    private $compileDir = 'runtime/templates/';
    private $cacheDir = 'runtime/template_cache/';
    
    public function __construct(ThemeManager $themeManager)
    {
        $this->themeManager = $themeManager;
        $this->initializeTemplateDirs();
    }
    
    /**
     * 初始化模板目录
     */
    private function initializeTemplateDirs()
    {
        $types = ['cart', 'clientarea', 'web'];
        
        foreach ($types as $type) {
            $activeTheme = $this->themeManager->getActiveTheme($type);
            $themePath = $this->themeManager->themeDirs[$type] . $activeTheme . '/';
            
            $this->templateDirs[$type] = [
                'theme' => $themePath,
                'inheritance' => $this->themeManager->getThemeInheritanceChain($type, $activeTheme)
            ];
        }
    }
    
    /**
     * 渲染模板
     */
    public function render($type, $templateName, $data = [])
    {
        $templatePath = $this->findTemplate($type, $templateName);
        
        if (!$templatePath) {
            throw new \RuntimeException("模板文件不存在: {$templateName}");
        }
        
        $compiled = $this->compileTemplate($templatePath);
        
        if ($compiled === false) {
            return $this->renderTemplate($templatePath, $data);
        }
        
        return $this->executeTemplate($compiled, $data);
    }
    
    /**
     * 查找模板文件
     */
    private function findTemplate($type, $templateName)
    {
        $templateDirs = $this->templateDirs[$type];
        
        foreach ($templateDirs['inheritance'] as $themeName) {
            $templatePath = $this->themeManager->themeDirs[$type] . $themeName . '/' . $templateName;
            
            if (file_exists($templatePath)) {
                return $templatePath;
            }
        }
        
        return null;
    }
    
    /**
     * 编译模板
     */
    private function compileTemplate($templatePath)
    {
        $cacheKey = md5($templatePath);
        $cacheFile = $this->cacheDir . $cacheKey . '.php';
        
        if (file_exists($cacheFile)) {
            $templateMtime = filemtime($templatePath);
            $cacheMtime = filemtime($cacheFile);
            
            if ($cacheMtime >= $templateMtime) {
                return $cacheFile;
            }
        }
        
        $templateContent = file_get_contents($templatePath);
        $compiledContent = $this->parseTemplate($templateContent);
        
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
        
        file_put_contents($cacheFile, $compiledContent);
        
        return $cacheFile;
    }
    
    /**
     * 解析模板
     */
    private function parseTemplate($content)
    {
        $patterns = [
            '/\{\s*\$([a-zA-Z_][a-zA-Z0-9_]*)\s*\}/' => '<?php echo $\1; ?>',
            '/\{\s*if\s+([^\}]+)\s*\}/' => '<?php if (\1): ?>',
            '/\{\s*else\s*\}/' => '<?php else: ?>',
            '/\{\s*elseif\s+([^\}]+)\s*\}/' => '<?php elseif (\1): ?>',
            '/\{\s*\/if\s*\}/' => '<?php endif; ?>',
            '/\{\s*foreach\s+\$([a-zA-Z_][a-zA-Z0-9_]*)\s+as\s+\$([a-zA-Z_][a-zA-Z0-9_]*)\s*\}/' => '<?php foreach ($\1 as $\2): ?>',
            '/\{\s*\/foreach\s*\}/' => '<?php endforeach; ?>',
            '/\{\s*include\s+([^\}]+)\s*\}/' => '<?php echo $this->render(\1); ?>',
            '/\{\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\}/' => '<?php $\1; ?>'
        ];
        
        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }
        
        return $content;
    }
    
    /**
     * 执行模板
     */
    private function executeTemplate($compiledFile, $data)
    {
        extract($data);
        ob_start();
        include $compiledFile;
        $content = ob_get_clean();
        
        return $content;
    }
    
    /**
     * 渲染模板（不编译）
     */
    private function renderTemplate($templatePath, $data)
    {
        $content = file_get_contents($templatePath);
        
        foreach ($data as $key => $value) {
            $content = str_replace('{$' . $key . '}', $value, $content);
        }
        
        return $content;
    }
}
```

### 3. 样式管理系统

```php
<?php
namespace app\common\lib;

class StyleManager
{
    private $themeManager;
    private $styleCache = [];
    private $cacheTime = 3600;
    
    public function __construct(ThemeManager $themeManager)
    {
        $this->themeManager = $themeManager;
    }
    
    /**
     * 获取主题样式
     */
    public function getThemeStyles($type)
    {
        $cacheKey = 'theme_styles_' . $type;
        
        if (isset($this->styleCache[$cacheKey])) {
            $cached = $this->styleCache[$cacheKey];
            
            if ((time() - $cached['time']) < $this->cacheTime) {
                return $cached['styles'];
            }
        }
        
        $styles = $this->collectThemeStyles($type);
        
        $this->styleCache[$cacheKey] = [
            'styles' => $styles,
            'time' => time()
        ];
        
        return $styles;
    }
    
    /**
     * 收集主题样式
     */
    private function collectThemeStyles($type)
    {
        $activeTheme = $this->themeManager->getActiveTheme($type);
        $inheritanceChain = $this->themeManager->getThemeInheritanceChain($type, $activeTheme);
        
        $styles = [];
        
        foreach ($inheritanceChain as $themeName) {
            $themeStyles = $this->getThemeStyleFiles($type, $themeName);
            
            foreach ($themeStyles as $styleFile) {
                $styles[] = $this->themeManager->themeDirs[$type] . $themeName . '/' . $styleFile;
            }
        }
        
        return $styles;
    }
    
    /**
     * 获取主题样式文件
     */
    private function getThemeStyleFiles($type, $themeName)
    {
        $styleFiles = [];
        $themePath = $this->themeManager->themeDirs[$type] . $themeName . '/';
        
        $directories = [
            'assets/css/',
            'assets/less/',
            'assets/sass/',
            'static/css/'
        ];
        
        foreach ($directories as $dir) {
            $fullPath = $themePath . $dir;
            
            if (is_dir($fullPath)) {
                $files = glob($fullPath . '*.css');
                
                foreach ($files as $file) {
                    $relativePath = str_replace($themePath, '', $file);
                    $styleFiles[] = $dir . $relativePath;
                }
            }
        }
        
        return $styleFiles;
    }
    
    /**
     * 编译LESS样式
     */
    public function compileLess($type, $lessFile)
    {
        $lessPath = $this->themeManager->themeDirs[$type] . $lessFile;
        
        if (!file_exists($lessPath)) {
            throw new \RuntimeException("LESS文件不存在: {$lessFile}");
        }
        
        $lessContent = file_get_contents($lessPath);
        $cssContent = $this->parseLess($lessContent);
        
        $cssFile = str_replace('.less', '.css', $lessPath);
        file_put_contents($cssFile, $cssContent);
        
        return $cssFile;
    }
    
    /**
     * 解析LESS
     */
    private function parseLess($content)
    {
        $variables = [];
        
        preg_match_all('/@([a-zA-Z_][a-zA-Z0-9_-]*)\s*:\s*([^;]+);/', $content, $matches);
        
        foreach ($matches[1] as $index => $varName) {
            $variables[$varName] = trim($matches[2][$index]);
        }
        
        $css = $content;
        
        foreach ($variables as $varName => $varValue) {
            $css = str_replace('@' . $varName, $varValue, $css);
        }
        
        return $css;
    }
    
    /**
     * 清除样式缓存
     */
    public function clearStyleCache($type = null)
    {
        if ($type) {
            unset($this->styleCache['theme_styles_' . $type]);
        } else {
            $this->styleCache = [];
        }
    }
}
```

---

## 主题包结构

### 购物车主题结构

```
public/themes/cart/
└── my_cart/
    ├── assets/                  # 静态资源
    │   ├── css/                # 样式文件
    │   │   ├── product.css
    │   │   ├── topbar.css
    │   │   └── style.css
    │   ├── js/                 # JavaScript文件
    │   │   ├── configureproduct.js
    │   │   ├── viewcart.js
    │   │   └── common.js
    │   ├── images/             # 图片资源
    │   │   ├── slider.png
    │   │   └── logo.png
    │   └── fonts/             # 字体文件
    ├── config.tpl               # 配置模板
    ├── product.tpl             # 产品模板
    ├── ordersummary.tpl         # 订单汇总模板
    ├── viewcart.tpl            # 购物车模板
    ├── topbar-categories.tpl    # 顶部栏分类模板
    ├── sidebar-categories.tpl   # 侧边栏分类模板
    ├── theme.config             # 主题配置文件
    ├── theme.jpg               # 主题截图
    └── README.md               # 说明文档
```

### 客户端主题结构

```
public/themes/clientarea/
└── my_clientarea/
    ├── assets/                  # 静态资源
    │   ├── css/                # 样式文件
    │   │   ├── app.css
    │   │   ├── icons.min.css
    │   │   └── custom.css
    │   ├── js/                 # JavaScript文件
    │   │   ├── app.js
    │   │   ├── public.js
    │   │   └── billing.js
    │   ├── images/             # 图片资源
    │   │   ├── api0.png
    │   │   └── gold.svg
    │   └── fonts/             # 字体文件
    │       └── boxicons/
    ├── includes/                # 包含模板
    │   ├── breadcrumb.tpl
    │   ├── head.tpl
    │   ├── menu.tpl
    │   ├── modal.tpl
    │   └── pageheader.tpl
    ├── servicedetail/           # 服务详情模板
    │   ├── cloud.tpl
    │   ├── general.tpl
    │   └── ssl.tpl
    ├── language/                # 语言包
    │   ├── chinese.php
    │   └── english.php
    ├── affiliates.tpl          # 代理模板
    ├── billing.tpl             # 账单模板
    ├── clientarea.tpl          # 客户中心模板
    ├── details.tpl             # 详情模板
    ├── downloads.tpl           # 下载模板
    ├── login.tpl              # 登录模板
    ├── register.tpl           # 注册模板
    ├── security.tpl           # 安全设置模板
    ├── service.tpl            # 服务模板
    ├── theme.config            # 主题配置文件
    ├── theme.jpg              # 主题截图
    └── README.md              # 说明文档
```

### 网站主题结构

```
public/themes/web/
└── my_website/
    ├── assets/                  # 静态资源
    │   ├── css/                # 样式文件
    │   │   ├── all.min.css
    │   │   ├── article.css
    │   │   ├── base.css
    │   │   └── zjmf.css
    │   ├── js/                 # JavaScript文件
    │   │   ├── main.js
    │   │   └── jquery.min.js
    │   ├── images/             # 图片资源
    │   │   ├── banner/
    │   │   ├── logo/
    │   │   └── feature/
    │   └── fonts/             # 字体文件
    │       └── iconfont/
    ├── language/                # 语言包
    │   ├── chinese.php
    │   └── english.php
    ├── about.html              # 关于页面
    ├── cloud.html              # 云服务器页面
    ├── domain.html             # 域名页面
    ├── help.html               # 帮助页面
    ├── index.html              # 首页
    ├── map.html                # 地图页面
    ├── news.html               # 新闻页面
    ├── ssl.html                # SSL证书页面
    ├── theme.config            # 主题配置文件
    ├── theme.jpg              # 主题截图
    └── README.md              # 说明文档
```

### 主题配置文件格式

```ini
# theme.config
name=My Theme
title=我的主题
description=自定义主题描述
author=开发者
version=1.0.0
parent=default
type=clientarea
screenshot=theme.jpg
responsive=true
rtl_support=false
min_php_version=7.2
max_php_version=8.0
```

### 主题配置参数说明

| 参数 | 说明 | 必填 | 示例 |
|------|------|------|------|
| name | 主题标识名（英文） | 是 | my_theme |
| title | 主题显示名称（中文） | 是 | 我的主题 |
| description | 主题描述 | 否 | 自定义主题描述 |
| author | 主题作者 | 否 | 开发者 |
| version | 主题版本号 | 是 | 1.0.0 |
| parent | 父主题名称 | 否 | default |
| type | 主题类型 | 是 | clientarea |
| screenshot | 主题截图文件名 | 否 | theme.jpg |
| responsive | 是否支持响应式 | 否 | true |
| rtl_support | 是否支持RTL布局 | 否 | false |
| min_php_version | 最低PHP版本 | 否 | 7.2 |
| max_php_version | 最高PHP版本 | 否 | 8.0 |

---

## 继承机制

### 继承原理

主题继承机制允许子主题继承父主题的模板和资源，实现代码复用和定制化开发。

```
┌─────────────────────────────────────────────────────────────┐
│                    子主题 (Child Theme)                  │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │  自定义模板   │  │  自定义样式   │  │  自定义资源   │ │
│  │  (Override)   │  │  (Override)   │  │  (Override)   │ │
│  └──────────────┘  └──────────────┘  └──────────────┘ │
├─────────────────────────────────────────────────────────────┤
│                    父主题 (Parent Theme)                 │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │  基础模板     │  │  基础样式     │  │  基础资源     │ │
│  │  (Base)       │  │  (Base)       │  │  (Base)       │ │
│  └──────────────┘  └──────────────┘  └──────────────┘ │
├─────────────────────────────────────────────────────────────┤
│                    系统主题 (System Theme)                │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │  默认模板     │  │  默认样式     │  │  默认资源     │ │
│  │  (Default)    │  │  (Default)    │  │  (Default)    │ │
│  └──────────────┘  └──────────────┘  └──────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### 继承实现

```php
<?php
namespace app\common\lib;

class ThemeInheritance
{
    private $themeManager;
    private $inheritanceCache = [];
    
    public function __construct(ThemeManager $themeManager)
    {
        $this->themeManager = $themeManager;
    }
    
    /**
     * 获取继承的模板路径
     */
    public function getInheritedTemplate($type, $templateName)
    {
        $cacheKey = $type . '_' . $templateName;
        
        if (isset($this->inheritanceCache[$cacheKey])) {
            return $this->inheritanceCache[$cacheKey];
        }
        
        $activeTheme = $this->themeManager->getActiveTheme($type);
        $inheritanceChain = $this->themeManager->getThemeInheritanceChain($type, $activeTheme);
        
        $templatePath = null;
        
        foreach ($inheritanceChain as $themeName) {
            $path = $this->themeManager->themeDirs[$type] . $themeName . '/' . $templateName;
            
            if (file_exists($path)) {
                $templatePath = $path;
                break;
            }
        }
        
        if (!$templatePath) {
            $defaultPath = $this->themeManager->themeDirs[$type] . 'default/' . $templateName;
            
            if (file_exists($defaultPath)) {
                $templatePath = $defaultPath;
            }
        }
        
        $this->inheritanceCache[$cacheKey] = $templatePath;
        
        return $templatePath;
    }
    
    /**
     * 获取继承的样式文件列表
     */
    public function getInheritedStyles($type)
    {
        $activeTheme = $this->themeManager->getActiveTheme($type);
        $inheritanceChain = $this->themeManager->getThemeInheritanceChain($type, $activeTheme);
        
        $styles = [];
        
        foreach ($inheritanceChain as $themeName) {
            $themeStyles = $this->getThemeStyles($type, $themeName);
            $styles = array_merge($styles, $themeStyles);
        }
        
        return $styles;
    }
    
    /**
     * 获取继承的资源路径
     */
    public function getInheritedAsset($type, $assetName)
    {
        $activeTheme = $this->themeManager->getActiveTheme($type);
        $inheritanceChain = $this->themeManager->getThemeInheritanceChain($type, $activeTheme);
        
        foreach ($inheritanceChain as $themeName) {
            $path = $this->themeManager->themeDirs[$type] . $themeName . '/assets/' . $assetName;
            
            if (file_exists($path)) {
                return '/' . str_replace('\\', '/', $path);
            }
        }
        
        $defaultPath = $this->themeManager->themeDirs[$type] . 'default/assets/' . $assetName;
        
        if (file_exists($defaultPath)) {
            return '/' . str_replace('\\', '/', $defaultPath);
        }
        
        return null;
    }
    
    /**
     * 获取主题样式
     */
    private function getThemeStyles($type, $themeName)
    {
        $styles = [];
        $themePath = $this->themeManager->themeDirs[$type] . $themeName . '/';
        
        $styleDirs = [
            'assets/css/',
            'static/css/',
            'assets/less/',
            'assets/sass/'
        ];
        
        foreach ($styleDirs as $dir) {
            $fullPath = $themePath . $dir;
            
            if (is_dir($fullPath)) {
                $files = glob($fullPath . '*.{css,less,sass}', GLOB_BRACE);
                
                foreach ($files as $file) {
                    $relativePath = str_replace($themePath, '', $file);
                    $styles[] = '/' . str_replace('\\', '/', $themePath . $relativePath);
                }
            }
        }
        
        return $styles;
    }
    
    /**
     * 清除继承缓存
     */
    public function clearCache($type = null)
    {
        if ($type) {
            $pattern = $type . '_';
            
            foreach (array_keys($this->inheritanceCache) as $key) {
                if (strpos($key, $pattern) === 0) {
                    unset($this->inheritanceCache[$key]);
                }
            }
        } else {
            $this->inheritanceCache = [];
        }
    }
}
```

### 继承示例

#### 父主题配置

```ini
# public/themes/clientarea/default/theme.config
name=default
title=默认主题
description=系统默认主题
author=智简魔方
version=1.0.0
type=clientarea
responsive=true
```

#### 子主题配置

```ini
# public/themes/clientarea/my_theme/theme.config
name=my_theme
title=我的主题
description=基于默认主题的定制主题
author=开发者
version=1.0.0
parent=default
type=clientarea
responsive=true
```

#### 子主题模板覆盖

```php
<?php
// public/themes/clientarea/my_theme/login.tpl

// 只覆盖需要修改的部分
<div class="login-container custom-login">
    <h1>欢迎登录</h1>
    
    <!-- 继承父主题的登录表单 -->
    {include parent/login_form.tpl}
    
    <div class="custom-footer">
        <p>我的主题 © 2024</p>
    </div>
</div>
```

#### 子主题样式覆盖

```css
/* public/themes/clientarea/my_theme/assets/css/custom.css */

/* 覆盖父主题样式 */
.login-container {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px;
    padding: 40px;
}

.custom-login h1 {
    color: white;
    text-align: center;
    margin-bottom: 30px;
}

.custom-footer {
    text-align: center;
    margin-top: 30px;
    color: rgba(255, 255, 255, 0.8);
}
```

---

## 切换功能

### 主题切换实现

```php
<?php
namespace app\common\controller;

use think\Controller;
use app\common\lib\ThemeManager;

class ThemeController extends Controller
{
    private $themeManager;
    
    public function __construct()
    {
        $this->themeManager = new ThemeManager();
    }
    
    /**
     * 切换主题
     */
    public function switchTheme()
    {
        $type = input('type', 'clientarea');
        $themeName = input('theme');
        
        if (!$themeName) {
            return json(['status' => false, 'message' => '主题名称不能为空']);
        }
        
        try {
            $result = $this->themeManager->setActiveTheme($type, $themeName);
            
            if ($result) {
                // 记录主题切换日志
                $this->logThemeSwitch($type, $themeName);
                
                return json([
                    'status' => true,
                    'message' => '主题切换成功',
                    'theme' => $themeName
                ]);
            } else {
                return json(['status' => false, 'message' => '主题切换失败']);
            }
        } catch (\Exception $e) {
            return json(['status' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * 预览主题
     */
    public function previewTheme()
    {
        $type = input('type', 'clientarea');
        $themeName = input('theme');
        
        try {
            $themeInfo = $this->themeManager->getThemeInfo($type, $themeName);
            
            if (!$themeInfo) {
                return json(['status' => false, 'message' => '主题不存在']);
            }
            
            // 设置临时主题
            session('preview_theme_' . $type, $themeName);
            
            return json([
                'status' => true,
                'message' => '主题预览已启用',
                'theme' => $themeInfo
            ]);
        } catch (\Exception $e) {
            return json(['status' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * 取消主题预览
     */
    public function cancelPreview()
    {
        $type = input('type', 'clientarea');
        
        session('preview_theme_' . $type, null);
        
        return json([
            'status' => true,
            'message' => '主题预览已取消'
        ]);
    }
    
    /**
     * 获取可用主题列表
     */
    public function getThemes()
    {
        $type = input('type', 'clientarea');
        
        $themes = $this->themeManager->getThemesByType($type);
        
        return json([
            'status' => true,
            'themes' => $themes
        ]);
    }
    
    /**
     * 获取主题详情
     */
    public function getThemeDetail()
    {
        $type = input('type', 'clientarea');
        $themeName = input('theme');
        
        try {
            $themeInfo = $this->themeManager->getThemeInfo($type, $themeName);
            
            if (!$themeInfo) {
                return json(['status' => false, 'message' => '主题不存在']);
            }
            
            return json([
                'status' => true,
                'theme' => $themeInfo
            ]);
        } catch (\Exception $e) {
            return json(['status' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * 记录主题切换日志
     */
    private function logThemeSwitch($type, $themeName)
    {
        $logData = [
            'type' => $type,
            'theme_name' => $themeName,
            'user_id' => session('user_id'),
            'ip' => request()->ip(),
            'time' => time()
        ];
        
        \think\Db::name('theme_switch_log')->insert($logData);
    }
}
```

### 前端主题切换

```javascript
// 主题切换JavaScript
class ThemeSwitcher {
    constructor() {
        this.currentTheme = null;
        this.previewTheme = null;
        this.init();
    }
    
    init() {
        this.loadCurrentTheme();
        this.bindEvents();
    }
    
    loadCurrentTheme() {
        fetch('/api/theme/current')
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    this.currentTheme = data.theme;
                    this.applyTheme(data.theme);
                }
            });
    }
    
    bindEvents() {
        // 主题选择器
        const themeSelector = document.getElementById('theme-selector');
        if (themeSelector) {
            themeSelector.addEventListener('change', (e) => {
                this.switchTheme(e.target.value);
            });
        }
        
        // 预览按钮
        const previewButtons = document.querySelectorAll('.preview-theme-btn');
        previewButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const themeName = e.target.dataset.theme;
                this.previewTheme(themeName);
            });
        });
        
        // 取消预览按钮
        const cancelPreviewBtn = document.getElementById('cancel-preview');
        if (cancelPreviewBtn) {
            cancelPreviewBtn.addEventListener('click', () => {
                this.cancelPreview();
            });
        }
    }
    
    switchTheme(themeName) {
        fetch('/api/theme/switch', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                theme: themeName
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                this.currentTheme = themeName;
                this.applyTheme(themeName);
                this.showNotification('主题切换成功');
            } else {
                this.showNotification('主题切换失败: ' + data.message, 'error');
            }
        })
        .catch(error => {
            this.showNotification('网络错误', 'error');
        });
    }
    
    previewTheme(themeName) {
        fetch('/api/theme/preview', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                theme: themeName
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                this.previewTheme = themeName;
                this.applyTheme(themeName);
                this.showNotification('主题预览已启用');
            } else {
                this.showNotification('主题预览失败: ' + data.message, 'error');
            }
        })
        .catch(error => {
            this.showNotification('网络错误', 'error');
        });
    }
    
    cancelPreview() {
        fetch('/api/theme/cancel-preview', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                this.previewTheme = null;
                this.applyTheme(this.currentTheme);
                this.showNotification('主题预览已取消');
            }
        })
        .catch(error => {
            this.showNotification('网络错误', 'error');
        });
    }
    
    applyTheme(themeName) {
        // 更新CSS变量
        this.updateCSSVariables(themeName);
        
        // 更新主题类
        document.body.className = document.body.className.replace(/theme-\w+/g, '');
        document.body.classList.add('theme-' + themeName);
        
        // 重新加载资源
        this.reloadAssets(themeName);
    }
    
    updateCSSVariables(themeName) {
        const themeColors = this.getThemeColors(themeName);
        
        const root = document.documentElement;
        
        Object.keys(themeColors).forEach(key => {
            root.style.setProperty('--' + key, themeColors[key]);
        });
    }
    
    getThemeColors(themeName) {
        const themes = {
            'default': {
                'primary-color': '#4a90e2',
                'secondary-color': '#50e3c2',
                'text-color': '#333333',
                'bg-color': '#ffffff',
                'border-color': '#e0e0e0'
            },
            'dark': {
                'primary-color': '#5c6bc0',
                'secondary-color': '#667eea',
                'text-color': '#ffffff',
                'bg-color': '#1a1a2e',
                'border-color': '#2d2d44'
            }
        };
        
        return themes[themeName] || themes['default'];
    }
    
    reloadAssets(themeName) {
        const stylesheets = document.querySelectorAll('link[rel="stylesheet"]');
        
        stylesheets.forEach(link => {
            if (link.href.includes('/themes/')) {
                const newHref = link.href.replace(/themes\/[^\/]+\//, 'themes/' + themeName + '/');
                link.href = newHref;
            }
        });
    }
    
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = 'notification notification-' + type;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }
}

// 初始化主题切换器
document.addEventListener('DOMContentLoaded', () => {
    new ThemeSwitcher();
});
```

---

## 响应式设计

### 响应式设计原则

```css
/* 基础响应式CSS */

/* 1. 流式布局 */
.container {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    box-sizing: border-box;
}

/* 2. 弹性网格 */
.grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

/* 3. 媒体查询 */
/* 移动设备 */
@media screen and (max-width: 768px) {
    .container {
        padding: 0 15px;
    }
    
    .grid {
        grid-template-columns: 1fr;
    }
}

/* 平板设备 */
@media screen and (min-width: 769px) and (max-width: 1024px) {
    .grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* 桌面设备 */
@media screen and (min-width: 1025px) {
    .grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

/* 4. 响应式图片 */
img {
    max-width: 100%;
    height: auto;
}

/* 5. 响应式字体 */
html {
    font-size: 16px;
}

@media screen and (max-width: 768px) {
    html {
        font-size: 14px;
    }
}

/* 6. 触摸优化 */
@media (hover: none) and (pointer: coarse) {
    .button {
        min-width: 44px;
        min-height: 44px;
        padding: 12px 20px;
    }
}
```

### 移动优先设计

```css
/* 移动优先CSS */

/* 基础样式（移动设备） */
body {
    font-size: 14px;
    line-height: 1.5;
}

.container {
    width: 100%;
    padding: 0 15px;
}

.navigation {
    display: block;
}

.navigation ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.navigation li {
    display: block;
    border-bottom: 1px solid #eee;
}

/* 平板设备 */
@media screen and (min-width: 769px) {
    body {
        font-size: 15px;
    }
    
    .container {
        max-width: 960px;
        margin: 0 auto;
    }
    
    .navigation {
        display: flex;
    }
    
    .navigation ul {
        display: flex;
        gap: 20px;
    }
    
    .navigation li {
        border-bottom: none;
    }
}

/* 桌面设备 */
@media screen and (min-width: 1025px) {
    body {
        font-size: 16px;
    }
    
    .container {
        max-width: 1200px;
    }
    
    .navigation {
        display: flex;
        justify-content: space-between;
    }
}
```

### 响应式组件

```css
/* 响应式按钮组件 */
.button {
    display: inline-block;
    padding: 12px 24px;
    font-size: 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

@media screen and (max-width: 768px) {
    .button {
        width: 100%;
        padding: 14px 20px;
        font-size: 14px;
    }
}

/* 响应式表单组件 */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
}

@media screen and (max-width: 768px) {
    .form-group input,
    .form-group select,
    .form-group textarea {
        font-size: 16px; /* 防止iOS缩放 */
    }
}

/* 响应式卡片组件 */
.card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    padding: 20px;
    margin-bottom: 20px;
}

@media screen and (max-width: 768px) {
    .card {
        padding: 15px;
        margin-bottom: 15px;
    }
}

/* 响应式导航组件 */
.navigation {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    background: white;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    z-index: 1000;
}

.navigation .logo {
    padding: 15px 20px;
}

.navigation .menu {
    display: none;
}

.navigation .toggle {
    display: block;
    padding: 15px 20px;
    cursor: pointer;
}

@media screen and (min-width: 769px) {
    .navigation .menu {
        display: flex;
        justify-content: flex-end;
        align-items: center;
    }
    
    .navigation .toggle {
        display: none;
    }
}

/* 移动菜单激活状态 */
.navigation.mobile-active .menu {
    display: block;
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.navigation.mobile-active .menu ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.navigation.mobile-active .menu li {
    border-bottom: 1px solid #eee;
}

.navigation.mobile-active .menu a {
    display: block;
    padding: 15px 20px;
    text-decoration: none;
    color: #333;
}
```

---

## 迁移指南

### 迁移到新项目的步骤

#### 1. 主题目录迁移

```bash
#!/bin/bash

# 主题迁移脚本

SOURCE_DIR="/path/to/old-project/public/themes"
TARGET_DIR="/path/to/new-project/public/themes"

# 创建目标目录
mkdir -p "$TARGET_DIR"

# 迁移所有主题
for theme_type in cart clientarea web; do
    if [ -d "$SOURCE_DIR/$theme_type" ]; then
        echo "迁移 $theme_type 主题..."
        cp -r "$SOURCE_DIR/$theme_type" "$TARGET_DIR/"
    fi
done

# 迁移主题配置
if [ -d "data/theme_configs" ]; then
    echo "迁移主题配置..."
    cp -r data/theme_configs "$TARGET_DIR/../data/"
fi

echo "主题迁移完成!"
```

#### 2. 数据库迁移

```php
<?php
// migration/002_create_theme_tables.php

class CreateThemeTables
{
    public function up()
    {
        $sql = [
            "CREATE TABLE IF NOT EXISTS `theme` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `type` varchar(50) NOT NULL COMMENT '主题类型',
                `name` varchar(100) NOT NULL COMMENT '主题名称',
                `title` varchar(100) NOT NULL COMMENT '主题标题',
                `description` text COMMENT '主题描述',
                `author` varchar(100) DEFAULT NULL COMMENT '作者',
                `version` varchar(20) NOT NULL COMMENT '版本号',
                `parent` varchar(100) DEFAULT NULL COMMENT '父主题',
                `screenshot` varchar(255) DEFAULT NULL COMMENT '截图',
                `status` tinyint(1) DEFAULT '0' COMMENT '状态 0未启用 1已启用',
                `config` text COMMENT '配置信息',
                `install_time` int(11) DEFAULT NULL COMMENT '安装时间',
                `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
                PRIMARY KEY (`id`),
                UNIQUE KEY `theme_type_name` (`type`, `name`),
                KEY `type` (`type`),
                KEY `status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='主题表';",
            
            "CREATE TABLE IF NOT EXISTS `theme_config` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `theme_name` varchar(100) NOT NULL COMMENT '主题名称',
                `config_key` varchar(255) NOT NULL COMMENT '配置键',
                `config_value` text COMMENT '配置值',
                `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `theme_config` (`theme_name`, `config_key`),
                KEY `theme_name` (`theme_name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='主题配置表';",
            
            "CREATE TABLE IF NOT EXISTS `theme_switch_log` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `type` varchar(50) NOT NULL COMMENT '主题类型',
                `theme_name` varchar(100) NOT NULL COMMENT '主题名称',
                `user_id` int(11) DEFAULT NULL COMMENT '用户ID',
                `ip` varchar(50) DEFAULT NULL COMMENT 'IP地址',
                `time` int(11) NOT NULL COMMENT '切换时间',
                PRIMARY KEY (`id`),
                KEY `type` (`type`),
                KEY `theme_name` (`theme_name`),
                KEY `user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='主题切换日志表';"
        ];
        
        foreach ($sql as $statement) {
            \think\Db::execute($statement);
        }
    }
    
    public function down()
    {
        $tables = ['theme_switch_log', 'theme_config', 'theme'];
        
        foreach ($tables as $table) {
            \think\Db::execute("DROP TABLE IF EXISTS `{$table}`");
        }
    }
}
```

#### 3. 配置文件迁移

```php
<?php
// config/theme.php

return [
    'theme_dir' => [
        'cart' => 'public/themes/cart/',
        'clientarea' => 'public/themes/clientarea/',
        'web' => 'public/themes/web/'
    ],
    
    'theme_config_dir' => 'data/theme_configs/',
    
    'theme_cache_dir' => 'runtime/theme_cache/',
    
    'template_cache_dir' => 'runtime/template_cache/',
    
    'default_themes' => [
        'cart' => 'default',
        'clientarea' => 'default',
        'web' => 'default'
    ],
    
    'inheritance' => [
        'enabled' => true,
        'max_depth' => 5
    ],
    
    'responsive' => [
        'enabled' => true,
        'breakpoints' => [
            'mobile' => 768,
            'tablet' => 1024,
            'desktop' => 1200
        ]
    ],
    
    'performance' => [
        'cache_enabled' => true,
        'compile_templates' => true,
        'minify_assets' => true
    ]
];
```

#### 4. 主题适配器

```php
<?php
// app/common/lib/ThemeAdapter.php

namespace app\common\lib;

class ThemeAdapter
{
    private $oldThemeManager;
    private $newThemeManager;
    
    public function __construct()
    {
        $this->oldThemeManager = new \OldThemeManager();
        $this->newThemeManager = new ThemeManager();
    }
    
    public function migrateTheme($themeType, $themeName)
    {
        $oldTheme = $this->oldThemeManager->getTheme($themeType, $themeName);
        $newTheme = $this->newThemeManager->getThemeInfo($themeType, $themeName);
        
        $this->migrateThemeConfig($themeType, $themeName, $oldTheme, $newTheme);
        $this->migrateThemeAssets($themeType, $themeName, $oldTheme, $newTheme);
        $this->migrateThemeTemplates($themeType, $themeName, $oldTheme, $newTheme);
    }
    
    private function migrateThemeConfig($themeType, $themeName, $oldTheme, $newTheme)
    {
        $oldConfig = $this->oldThemeManager->getThemeConfig($themeName);
        $newConfig = $this->transformThemeConfig($oldConfig);
        
        $this->newThemeManager->saveThemeConfig($themeName, $newConfig);
    }
    
    private function transformThemeConfig($oldConfig)
    {
        $newConfig = [];
        
        foreach ($oldConfig as $key => $value) {
            $newKey = $this->transformConfigKey($key);
            $newValue = $this->transformConfigValue($value);
            
            $newConfig[$newKey] = $newValue;
        }
        
        return $newConfig;
    }
    
    private function transformConfigKey($key)
    {
        $keyMap = [
            'theme_name' => 'name',
            'theme_title' => 'title',
            'theme_desc' => 'description',
            'theme_author' => 'author',
            'theme_version' => 'version',
            'parent_theme' => 'parent'
        ];
        
        return $keyMap[$key] ?? $key;
    }
    
    private function transformConfigValue($value)
    {
        if (is_array($value)) {
            return array_map([$this, 'transformConfigValue'], $value);
        }
        
        if (is_string($value)) {
            return trim($value);
        }
        
        return $value;
    }
    
    private function migrateThemeAssets($themeType, $themeName, $oldTheme, $newTheme)
    {
        $oldAssets = $this->oldThemeManager->getThemeAssets($themeName);
        
        foreach ($oldAssets as $asset) {
            $newAssetPath = $this->transformAssetPath($themeType, $themeName, $asset);
            
            $this->copyAsset($asset, $newAssetPath);
        }
    }
    
    private function transformAssetPath($themeType, $themeName, $assetPath)
    {
        $oldPath = str_replace('\\', '/', $assetPath);
        
        if (strpos($oldPath, '/static/') !== false) {
            $newPath = str_replace('/static/', '/assets/', $oldPath);
        } elseif (strpos($oldPath, '/css/') !== false) {
            $newPath = str_replace('/css/', '/assets/css/', $oldPath);
        } elseif (strpos($oldPath, '/js/') !== false) {
            $newPath = str_replace('/js/', '/assets/js/', $oldPath);
        } else {
            $newPath = $oldPath;
        }
        
        return 'public/themes/' . $themeType . '/' . $themeName . $newPath;
    }
    
    private function copyAsset($source, $destination)
    {
        $destDir = dirname($destination);
        
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }
        
        if (file_exists($source)) {
            copy($source, $destination);
        }
    }
    
    private function migrateThemeTemplates($themeType, $themeName, $oldTheme, $newTheme)
    {
        $oldTemplates = $this->oldThemeManager->getThemeTemplates($themeName);
        
        foreach ($oldTemplates as $template) {
            $newTemplatePath = $this->transformTemplatePath($themeType, $themeName, $template);
            
            $this->copyTemplate($template, $newTemplatePath);
        }
    }
    
    private function transformTemplatePath($themeType, $themeName, $templatePath)
    {
        $oldPath = str_replace('\\', '/', $templatePath);
        
        if (strpos($oldPath, '.html') !== false) {
            $newPath = str_replace('.html', '.tpl', $oldPath);
        } elseif (strpos($oldPath, '.htm') !== false) {
            $newPath = str_replace('.htm', '.tpl', $oldPath);
        } else {
            $newPath = $oldPath;
        }
        
        return 'public/themes/' . $themeType . '/' . $themeName . $newPath;
    }
    
    private function copyTemplate($source, $destination)
    {
        $destDir = dirname($destination);
        
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }
        
        if (file_exists($source)) {
            copy($source, $destination);
        }
    }
}
```

### 迁移验证清单

#### 功能验证

- [ ] 主题发现机制正常工作
- [ ] 主题切换功能正常
- [ ] 主题继承机制正常
- [ ] 模板渲染正常
- [ ] 样式加载正常
- [ ] 资源加载正常
- [ ] 响应式设计正常
- [ ] 主题配置保存/读取正常

#### 性能验证

- [ ] 主题加载时间在可接受范围内
- [ ] 模板编译缓存有效
- [ ] 样式文件压缩有效
- [ ] 资源文件合并有效
- [ ] 内存使用在合理范围内

#### 兼容性验证

- [ ] 与现有插件兼容
- [ ] 与现有主题兼容
- [ ] 与浏览器兼容
- [ ] 与移动设备兼容
- [ ] 与PHP版本兼容

### 常见问题及解决方案

#### 问题1: 主题继承不生效

**症状**: 子主题无法继承父主题的模板

**原因**: 继承配置错误或循环继承

**解决方案**:
```php
// 检查继承配置
$themeConfig = parseThemeConfig($themePath);

if (isset($themeConfig['parent'])) {
    $parentTheme = $themeConfig['parent'];
    
    // 检查循环继承
    if ($themeManager->hasCircularInheritance($type, $themeName)) {
        throw new Exception('存在循环继承');
    }
}
```

#### 问题2: 样式文件加载失败

**症状**: 主题样式无法正常加载

**原因**: 样式文件路径错误或权限问题

**解决方案**:
```bash
# 检查样式文件权限
chmod 644 public/themes/clientarea/my_theme/assets/css/*.css

# 检查样式文件是否存在
ls -la public/themes/clientarea/my_theme/assets/css/
```

#### 问题3: 响应式设计失效

**症状**: 移动设备显示异常

**原因**: 媒体查询配置错误或CSS问题

**解决方案**:
```css
/* 确保viewport meta标签正确 */
<meta name="viewport" content="width=device-width, initial-scale=1.0">

/* 使用正确的媒体查询语法 */
@media screen and (max-width: 768px) {
    /* 移动设备样式 */
}

/* 使用相对单位 */
.container {
    width: 100%;
    max-width: 1200px;
    padding: 0 5%;
}
```

---

## 总结

智简魔方财务系统的主题架构提供了强大而灵活的前端定制能力，通过分层设计、继承机制和响应式支持，确保了系统的可扩展性和用户体验。

### 核心优势

1. **分层架构**: 前端与后端分离，便于独立开发和维护
2. **模块化设计**: 主题由多个模块组成，便于复用和定制
3. **继承机制**: 子主题可以继承父主题，实现代码复用
4. **响应式设计**: 支持多设备适配，提供一致的用户体验
5. **性能优化**: 模板编译、样式压缩、资源合并

### 最佳实践

1. 遵循主题开发规范
2. 使用继承机制减少重复代码
3. 实现响应式设计
4. 优化主题性能
5. 编写清晰的文档
6. 进行充分的测试

### 未来扩展方向

1. 主题市场集成
2. 在线主题编辑器
3. 主题自动更新
4. 主题性能监控
5. 主题安全审计

通过本文档的指导，开发者可以快速掌握主题架构的实现方法，并将其成功迁移到新的项目环境中。
