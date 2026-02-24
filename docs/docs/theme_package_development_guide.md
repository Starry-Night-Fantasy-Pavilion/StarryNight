# 智简魔方财务系统 - 主题包开发文档

## 目录
1. [开发环境准备](#开发环境准备)
2. [创建新主题](#创建新主题)
3. [主题配置](#主题配置)
4. [模板开发](#模板开发)
5. [样式开发](#样式开发)
6. [JavaScript开发](#javascript开发)
7. [资源管理](#资源管理)
8. [测试与调试](#测试与调试)
9. [发布与分发](#发布与分发)

---

## 开发环境准备

### 系统要求

| 组件 | 最低要求 | 推荐配置 |
|------|---------|----------|
| PHP | 7.2+ | 7.4+ |
| 浏览器 | Chrome 90+, Firefox 88+, Safari 14+ | 最新版本 |
| 编辑器 | VS Code, Sublime Text | VS Code |
| Node.js | 12+ | 16+ (用于构建工具) |
| Git | 2.20+ | 最新版本 |

### 开发工具安装

```bash
# 1. 克隆项目
git clone https://github.com/your-repo/project.git
cd project

# 2. 安装依赖
composer install

# 3. 创建主题目录
mkdir -p public/themes/{cart,clientarea,web}

# 4. 设置权限
chmod -R 755 public/themes
```

### VS Code插件推荐

```json
{
  "recommendations": [
    "HookyQR.beautify",
    "esbenp.prettier-vscode",
    "dbaeumer.vscode-eslint",
    "stylelint.vscode-stylelint",
    "bradlc.vscode-tailwindcss",
    "formulahendry.auto-rename-tag",
    "christian-kohler.path-intellisense",
    "formulahendry.auto-close-tag"
  ]
}
```

### 开发环境配置

```json
// .vscode/settings.json
{
  "files.associations": {
    "*.tpl": "html"
  },
  "emmet.includeLanguages": {
    "tpl": "html"
  },
  "editor.formatOnSave": true,
  "editor.defaultFormatter": "esbenp.prettier-vscode",
  "css.validate": false,
  "stylelint.enable": true,
  "editor.codeActionsOnSave": {
    "source.fixAll.eslint": true
  }
}
```

---

## 创建新主题

### 主题类型选择

根据功能需求选择合适的主题类型：

```php
<?php
// 主题类型决策函数
function determineThemeType($requirements)
{
    if ($requirements['product_configuration']) {
        return 'cart';
    }
    
    if ($requirements['user_management']) {
        return 'clientarea';
    }
    
    if ($requirements['website_showcase']) {
        return 'web';
    }
    
    throw new \InvalidArgumentException('无法确定主题类型');
}
```

### 创建主题目录结构

```bash
# 创建购物车主题
mkdir -p public/themes/cart/my_cart/{assets/{css,js,images,fonts},config}

# 创建客户端主题
mkdir -p public/themes/clientarea/my_clientarea/{assets/{css,js,images,fonts},includes,servicedetail,language}

# 创建网站主题
mkdir -p public/themes/web/my_website/{assets/{css,js,images,fonts},language}
```

### 创建主题配置文件

```ini
# public/themes/cart/my_cart/theme.config
name=my_cart
title=我的购物车主题
description=自定义购物车主题，提供更好的产品配置体验
author=开发者
version=1.0.0
parent=default
type=cart
screenshot=theme.jpg
responsive=true
rtl_support=false
min_php_version=7.2
```

### 创建主题截图

```bash
# 主题截图要求
# 尺寸: 1200x900 像素
# 格式: JPG 或 PNG
# 大小: 不超过 500KB

# 使用截图工具或在线工具创建主题预览图
# 将截图保存为 theme.jpg 或 theme.png
```

---

## 主题配置

### 配置文件详解

```ini
# 主题基本配置
name=my_theme                    # 主题唯一标识（英文，小写，下划线分隔）
title=我的主题                   # 主题显示名称（中文）
description=主题描述信息         # 主题详细描述
author=开发者                   # 主题作者
version=1.0.0                 # 主题版本号（遵循语义化版本）
parent=default                 # 父主题名称（可选，用于继承）
type=clientarea               # 主题类型（cart|clientarea|web）
screenshot=theme.jpg          # 主题截图文件名
responsive=true              # 是否支持响应式设计
rtl_support=false            # 是否支持RTL布局
min_php_version=7.2          # 最低PHP版本要求
max_php_version=8.0          # 最高PHP版本要求
```

### 配置参数说明

#### 必填参数

| 参数 | 类型 | 说明 | 示例 |
|------|------|------|------|
| name | string | 主题唯一标识 | my_theme |
| title | string | 主题显示名称 | 我的主题 |
| version | string | 主题版本号 | 1.0.0 |
| type | string | 主题类型 | clientarea |

#### 可选参数

| 参数 | 类型 | 说明 | 默认值 | 示例 |
|------|------|------|--------|------|
| description | string | 主题描述 | 空 | 自定义主题描述 |
| author | string | 主题作者 | 空 | 开发者 |
| parent | string | 父主题名称 | 无 | default |
| screenshot | string | 主题截图 | theme.jpg | preview.jpg |
| responsive | boolean | 响应式支持 | true | true |
| rtl_support | boolean | RTL布局支持 | false | false |
| min_php_version | string | 最低PHP版本 | 7.0 | 7.2 |
| max_php_version | string | 最高PHP版本 | 无 | 8.0 |

### 版本号规范

遵循语义化版本（Semantic Versioning）规范：

```
主版本号.次版本号.修订号 (MAJOR.MINOR.PATCH)

示例:
1.0.0 - 初始版本
1.1.0 - 新增功能（向下兼容）
1.1.1 - 修复bug（向下兼容）
2.0.0 - 重大变更（可能不兼容）
```

---

## 模板开发

### 模板语法

#### 基础语法

```html
<!-- 输出变量 -->
{$variable}

<!-- 条件判断 -->
{if $condition}
  内容
{elseif $other_condition}
  其他内容
{else}
  默认内容
{/if}

<!-- 循环 -->
{foreach $array as $item}
  {$item}
{/foreach}

{foreach $array as $key => $value}
  {$key}: {$value}
{/foreach}

<!-- 包含模板 -->
{include 'header.tpl'}
{include 'sidebar.tpl'}

<!-- 定义区块 -->
{block name="content"}
  默认内容
{/block}

<!-- 继承模板 -->
{extends "base.tpl"}

<!-- 覆盖区块 -->
{block name="content"}
  自定义内容
{/block}
```

#### 高级语法

```html
<!-- 函数调用 -->
{$variable|htmlspecialchars}

<!-- 多个过滤器 -->
{$variable|strip_tags|trim}

<!-- 自定义函数 -->
{custom_function param1="value1" param2="value2"}

<!-- 循环索引 -->
{foreach $array as $index => $item}
  {$index}: {$item}
  {$item@iteration}  <!-- 当前迭代次数 -->
  {$item@first}      <!-- 是否第一个 -->
  {$item@last}       <!-- 是否最后一个 -->
  {$item@total}      <!-- 总数 -->
{/foreach}

<!-- 循环else -->
{foreach $array as $item}
  {$item}
{foreachelse}
  数组为空
{/foreach}

<!-- 静态资源路径 -->
{$__PUBLIC__}/themes/{$__THEME__}/assets/css/style.css
```

### 模板继承

#### 父模板 (base.tpl)

```html
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{block name="title"}默认标题{/block}</title>
    
    {block name="styles"}
    <link rel="stylesheet" href="{$__PUBLIC__}/themes/{$__THEME__}/assets/css/style.css">
    {/block}
</head>
<body>
    {block name="header"}
    <header>
        {include 'includes/header.tpl'}
    </header>
    {/block}
    
    {block name="content"}
    <main>
        默认内容
    </main>
    {/block}
    
    {block name="footer"}
    <footer>
        {include 'includes/footer.tpl'}
    </footer>
    {/block}
    
    {block name="scripts"}
    <script src="{$__PUBLIC__}/themes/{$__THEME__}/assets/js/main.js"></script>
    {/block}
</body>
</html>
```

#### 子模板 (page.tpl)

```html
{extends "base.tpl"}

{block name="title"}
页面标题 - {$site_name}
{/block}

{block name="styles"}
    {parent}
    <link rel="stylesheet" href="{$__PUBLIC__}/themes/{$__THEME__}/assets/css/page.css">
{/block}

{block name="content"}
<main class="page-content">
    <h1>{$page_title}</h1>
    <div class="content">
        {$page_content}
    </div>
</main>
{/block}

{block name="scripts"}
    {parent}
    <script src="{$__PUBLIC__}/themes/{$__THEME__}/assets/js/page.js"></script>
{/block}
```

### 常用模板组件

#### 导航组件

```html
<!-- includes/navigation.tpl -->
<nav class="navigation">
    <div class="container">
        <div class="nav-brand">
            <a href="{$site_url}">
                <img src="{$site_logo}" alt="{$site_name}">
            </a>
        </div>
        
        <button class="nav-toggle" id="navToggle">
            <span></span>
            <span></span>
            <span></span>
        </button>
        
        <ul class="nav-menu" id="navMenu">
            {foreach $menu_items as $item}
            <li class="nav-item {if $item.active}active{/if}">
                <a href="{$item.url}" class="nav-link">
                    {$item.title}
                </a>
                
                {if isset($item.children)}
                <ul class="nav-dropdown">
                    {foreach $item.children as $child}
                    <li>
                        <a href="{$child.url}">{$child.title}</a>
                    </li>
                    {/foreach}
                </ul>
                {/if}
            </li>
            {/foreach}
        </ul>
    </div>
</nav>
```

#### 分页组件

```html
<!-- includes/pagination.tpl -->
<div class="pagination">
    {if $pagination.total_pages > 1}
    <ul class="pagination-list">
        {if $pagination.current_page > 1}
        <li class="pagination-item">
            <a href="{$pagination.first_page_url}" class="pagination-link">
                首页
            </a>
        </li>
        <li class="pagination-item">
            <a href="{$pagination.prev_page_url}" class="pagination-link">
                上一页
            </a>
        </li>
        {/if}
        
        {foreach $pagination.pages as $page}
        <li class="pagination-item {if $page.active}active{/if}">
            {if $page.active}
            <span class="pagination-link">{$page.number}</span>
            {else}
            <a href="{$page.url}" class="pagination-link">{$page.number}</a>
            {/if}
        </li>
        {/foreach}
        
        {if $pagination.current_page < $pagination.total_pages}
        <li class="pagination-item">
            <a href="{$pagination.next_page_url}" class="pagination-link">
                下一页
            </a>
        </li>
        <li class="pagination-item">
            <a href="{$pagination.last_page_url}" class="pagination-link">
                末页
            </a>
        </li>
        {/if}
    </ul>
    
    <div class="pagination-info">
        共 {$pagination.total} 条记录，第 {$pagination.current_page} / {$pagination.total_pages} 页
    </div>
    {/if}
</div>
```

#### 面包屑组件

```html
<!-- includes/breadcrumb.tpl -->
<nav class="breadcrumb" aria-label="面包屑导航">
    <ol class="breadcrumb-list">
        <li class="breadcrumb-item">
            <a href="{$home_url}">
                <i class="icon-home"></i>
                首页
            </a>
        </li>
        
        {foreach $breadcrumbs as $index => $item}
        <li class="breadcrumb-item {if $item@last}active{/if}">
            {if !$item@last}
            <a href="{$item.url}">{$item.title}</a>
            {else}
            <span>{$item.title}</span>
            {/if}
        </li>
        {/foreach}
    </ol>
</nav>
```

---

## 样式开发

### CSS架构

#### BEM命名规范

```css
/* Block (块) */
.card {}

/* Element (元素) */
.card__header {}
.card__body {}
.card__footer {}

/* Modifier (修饰符) */
.card--primary {}
.card--large {}
.card--active {}
```

#### CSS变量

```css
:root {
  /* 颜色 */
  --color-primary: #4a90e2;
  --color-secondary: #50e3c2;
  --color-success: #2ecc71;
  --color-warning: #f39c12;
  --color-danger: #e74c3c;
  --color-text: #333333;
  --color-text-light: #666666;
  --color-border: #e0e0e0;
  --color-bg: #ffffff;
  --color-bg-light: #f5f5f5;
  
  /* 间距 */
  --spacing-xs: 4px;
  --spacing-sm: 8px;
  --spacing-md: 16px;
  --spacing-lg: 24px;
  --spacing-xl: 32px;
  --spacing-xxl: 48px;
  
  /* 字体 */
  --font-family-base: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  --font-size-xs: 12px;
  --font-size-sm: 14px;
  --font-size-base: 16px;
  --font-size-lg: 18px;
  --font-size-xl: 20px;
  --font-size-xxl: 24px;
  --font-weight-normal: 400;
  --font-weight-medium: 500;
  --font-weight-bold: 700;
  
  /* 圆角 */
  --border-radius-sm: 4px;
  --border-radius-md: 8px;
  --border-radius-lg: 12px;
  --border-radius-full: 9999px;
  
  /* 阴影 */
  --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
  --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
  --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
  --shadow-xl: 0 20px 25px rgba(0, 0, 0, 0.15);
  
  /* 过渡 */
  --transition-fast: 0.15s ease;
  --transition-base: 0.3s ease;
  --transition-slow: 0.5s ease;
  
  /* 断点 */
  --breakpoint-sm: 576px;
  --breakpoint-md: 768px;
  --breakpoint-lg: 992px;
  --breakpoint-xl: 1200px;
  --breakpoint-xxl: 1400px;
}
```

#### 响应式设计

```css
/* 移动优先 */
.container {
  width: 100%;
  padding: 0 var(--spacing-sm);
}

/* 平板 */
@media (min-width: var(--breakpoint-md)) {
  .container {
    max-width: 720px;
    margin: 0 auto;
    padding: 0 var(--spacing-md);
  }
}

/* 桌面 */
@media (min-width: var(--breakpoint-lg)) {
  .container {
    max-width: 960px;
  }
}

/* 大屏幕 */
@media (min-width: var(--breakpoint-xl)) {
  .container {
    max-width: 1140px;
  }
}

/* 超大屏幕 */
@media (min-width: var(--breakpoint-xxl)) {
  .container {
    max-width: 1320px;
  }
}
```

### 组件样式

#### 按钮组件

```css
.button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: var(--spacing-sm) var(--spacing-md);
  font-family: var(--font-family-base);
  font-size: var(--font-size-base);
  font-weight: var(--font-weight-medium);
  line-height: 1.5;
  color: var(--color-text);
  background-color: var(--color-bg);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius-md);
  cursor: pointer;
  transition: all var(--transition-base);
  user-select: none;
}

.button:hover {
  background-color: var(--color-bg-light);
  border-color: var(--color-primary);
  color: var(--color-primary);
}

.button:active {
  transform: translateY(1px);
}

.button--primary {
  background-color: var(--color-primary);
  border-color: var(--color-primary);
  color: white;
}

.button--primary:hover {
  background-color: #357abd;
  border-color: #357abd;
  color: white;
}

.button--large {
  padding: var(--spacing-md) var(--spacing-lg);
  font-size: var(--font-size-lg);
}

.button--small {
  padding: var(--spacing-xs) var(--spacing-sm);
  font-size: var(--font-size-sm);
}

.button--block {
  display: flex;
  width: 100%;
}

.button--disabled {
  opacity: 0.6;
  cursor: not-allowed;
  pointer-events: none;
}

@media (max-width: var(--breakpoint-md)) {
  .button {
    width: 100%;
  }
}
```

#### 卡片组件

```css
.card {
  background-color: var(--color-bg);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius-md);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
  transition: box-shadow var(--transition-base);
}

.card:hover {
  box-shadow: var(--shadow-md);
}

.card__header {
  padding: var(--spacing-md);
  border-bottom: 1px solid var(--color-border);
}

.card__title {
  margin: 0;
  font-size: var(--font-size-lg);
  font-weight: var(--font-weight-bold);
  color: var(--color-text);
}

.card__body {
  padding: var(--spacing-md);
}

.card__footer {
  padding: var(--spacing-md);
  border-top: 1px solid var(--color-border);
  background-color: var(--color-bg-light);
}

.card--primary {
  border-color: var(--color-primary);
}

.card--primary .card__header {
  background-color: var(--color-primary);
  color: white;
  border-bottom-color: var(--color-primary);
}

.card--large {
  border-radius: var(--border-radius-lg);
}

.card--large .card__header,
.card--large .card__body,
.card--large .card__footer {
  padding: var(--spacing-lg);
}
```

#### 表单组件

```css
.form-group {
  margin-bottom: var(--spacing-md);
}

.form-label {
  display: block;
  margin-bottom: var(--spacing-xs);
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
  color: var(--color-text);
}

.form-control {
  display: block;
  width: 100%;
  padding: var(--spacing-sm) var(--spacing-md);
  font-size: var(--font-size-base);
  line-height: 1.5;
  color: var(--color-text);
  background-color: var(--color-bg);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius-sm);
  transition: border-color var(--transition-fast);
}

.form-control:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
}

.form-control::placeholder {
  color: var(--color-text-light);
}

.form-control--error {
  border-color: var(--color-danger);
}

.form-control--error:focus {
  border-color: var(--color-danger);
  box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
}

.form-control--success {
  border-color: var(--color-success);
}

.form-control--success:focus {
  border-color: var(--color-success);
  box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.1);
}

.form-text {
  display: block;
  margin-top: var(--spacing-xs);
  font-size: var(--font-size-sm);
  color: var(--color-text-light);
}

.form-text--error {
  color: var(--color-danger);
}

.form-text--success {
  color: var(--color-success);
}
```

---

## JavaScript开发

### JavaScript架构

#### 模块化开发

```javascript
// assets/js/modules/module.js
class Module {
  constructor(options = {}) {
    this.options = {
      ...options
    };
    this.init();
  }
  
  init() {
    this.bindEvents();
    this.render();
  }
  
  bindEvents() {
    // 绑定事件
  }
  
  render() {
    // 渲染内容
  }
  
  destroy() {
    // 清理资源
  }
}

export default Module;
```

#### 事件委托

```javascript
// assets/js/utils/event-delegation.js
class EventDelegation {
  constructor() {
    this.delegatedEvents = new Map();
  }
  
  on(selector, eventType, handler) {
    if (!this.delegatedEvents.has(eventType)) {
      this.delegatedEvents.set(eventType, new Map());
    }
    
    this.delegatedEvents.get(eventType).set(selector, handler);
  }
  
  bind(container = document) {
    this.delegatedEvents.forEach((handlers, eventType) => {
      container.addEventListener(eventType, (e) => {
        handlers.forEach((handler, selector) => {
          const target = e.target.closest(selector);
          
          if (target && container.contains(target)) {
            handler.call(target, e, target);
          }
        });
      });
    });
  }
  
  unbind() {
    // 解绑所有事件
  }
}

export default EventDelegation;
```

### 常用功能模块

#### 表单验证

```javascript
// assets/js/modules/form-validator.js
class FormValidator {
  constructor(form, rules) {
    this.form = form;
    this.rules = rules;
    this.errors = {};
    this.init();
  }
  
  init() {
    this.form.addEventListener('submit', (e) => {
      e.preventDefault();
      this.validate();
    });
  }
  
  validate() {
    this.errors = {};
    const formData = new FormData(this.form);
    
    for (const [fieldName, fieldRules] of Object.entries(this.rules)) {
      const value = formData.get(fieldName);
      
      for (const rule of fieldRules) {
        const error = this.validateField(fieldName, value, rule);
        
        if (error) {
          this.errors[fieldName] = error;
          break;
        }
      }
    }
    
    this.displayErrors();
    return Object.keys(this.errors).length === 0;
  }
  
  validateField(fieldName, value, rule) {
    if (rule.required && !value) {
      return rule.message || `${fieldName} 是必填项`;
    }
    
    if (rule.pattern && !rule.pattern.test(value)) {
      return rule.message || `${fieldName} 格式不正确`;
    }
    
    if (rule.minLength && value.length < rule.minLength) {
      return rule.message || `${fieldName} 长度不能少于 ${rule.minLength}`;
    }
    
    if (rule.maxLength && value.length > rule.maxLength) {
      return rule.message || `${fieldName} 长度不能超过 ${rule.maxLength}`;
    }
    
    if (rule.custom && !rule.custom(value)) {
      return rule.message || `${fieldName} 验证失败`;
    }
    
    return null;
  }
  
  displayErrors() {
    this.form.querySelectorAll('.form-text--error').forEach(el => {
      el.remove();
    });
    
    this.form.querySelectorAll('.form-control--error').forEach(el => {
      el.classList.remove('form-control--error');
    });
    
    for (const [fieldName, error] of Object.entries(this.errors)) {
      const field = this.form.querySelector(`[name="${fieldName}"]`);
      
      if (field) {
        field.classList.add('form-control--error');
        
        const errorText = document.createElement('div');
        errorText.className = 'form-text form-text--error';
        errorText.textContent = error;
        field.parentNode.appendChild(errorText);
      }
    }
  }
}

export default FormValidator;
```

#### AJAX请求

```javascript
// assets/js/utils/ajax.js
class Ajax {
  static async request(url, options = {}) {
    const defaultOptions = {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json'
      },
      credentials: 'same-origin'
    };
    
    const config = { ...defaultOptions, ...options };
    
    try {
      const response = await fetch(url, config);
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      const data = await response.json();
      return data;
    } catch (error) {
      console.error('AJAX request failed:', error);
      throw error;
    }
  }
  
  static get(url, options = {}) {
    return this.request(url, { ...options, method: 'GET' });
  }
  
  static post(url, data, options = {}) {
    return this.request(url, {
      ...options,
      method: 'POST',
      body: JSON.stringify(data)
    });
  }
  
  static put(url, data, options = {}) {
    return this.request(url, {
      ...options,
      method: 'PUT',
      body: JSON.stringify(data)
    });
  }
  
  static delete(url, options = {}) {
    return this.request(url, { ...options, method: 'DELETE' });
  }
}

export default Ajax;
```

#### 通知提示

```javascript
// assets/js/modules/notification.js
class Notification {
  constructor() {
    this.container = this.createContainer();
  }
  
  createContainer() {
    let container = document.getElementById('notification-container');
    
    if (!container) {
      container = document.createElement('div');
      container.id = 'notification-container';
      container.className = 'notification-container';
      document.body.appendChild(container);
    }
    
    return container;
  }
  
  show(message, type = 'info', duration = 3000) {
    const notification = document.createElement('div');
    notification.className = `notification notification--${type}`;
    notification.innerHTML = `
      <div class="notification__content">
        <span class="notification__message">${message}</span>
        <button class="notification__close">&times;</button>
      </div>
    `;
    
    this.container.appendChild(notification);
    
    const closeBtn = notification.querySelector('.notification__close');
    closeBtn.addEventListener('click', () => this.hide(notification));
    
    if (duration > 0) {
      setTimeout(() => this.hide(notification), duration);
    }
    
    return notification;
  }
  
  hide(notification) {
    notification.classList.add('notification--hide');
    
    setTimeout(() => {
      if (notification.parentNode) {
        notification.parentNode.removeChild(notification);
      }
    }, 300);
  }
  
  success(message, duration) {
    return this.show(message, 'success', duration);
  }
  
  error(message, duration) {
    return this.show(message, 'error', duration);
  }
  
  warning(message, duration) {
    return this.show(message, 'warning', duration);
  }
  
  info(message, duration) {
    return this.show(message, 'info', duration);
  }
}

export default Notification;
```

---

## 资源管理

### 图片优化

```bash
# 使用ImageMagick优化图片
convert input.jpg -quality 85 -strip output.jpg

# 批量优化
for file in *.jpg; do
  convert "$file" -quality 85 -strip "optimized_$file"
done

# 生成不同尺寸
convert input.jpg -resize 320x output-small.jpg
convert input.jpg -resize 640x output-medium.jpg
convert input.jpg -resize 1280x output-large.jpg
```

### CSS压缩

```bash
# 使用cssnano压缩CSS
npx cssnano input.css output.css

# 压缩并自动添加浏览器前缀
npx postcss input.css --use autoprefixer --use cssnano --output output.css
```

### JavaScript压缩

```bash
# 使用Terser压缩JavaScript
npx terser input.js -o output.js -c -m

# 压缩并生成source map
npx terser input.js -o output.js -c -m --source-map
```

---

## 测试与调试

### 浏览器兼容性测试

```javascript
// assets/js/utils/browser-compatibility.js
class BrowserCompatibility {
  static check() {
    const checks = {
      es6: this.checkES6(),
      fetch: this.checkFetch(),
      localStorage: this.checkLocalStorage(),
      sessionStorage: this.checkSessionStorage()
    };
    
    return checks;
  }
  
  static checkES6() {
    try {
      eval('const test = () => {}');
      return true;
    } catch (e) {
      return false;
    }
  }
  
  static checkFetch() {
    return typeof fetch !== 'undefined';
  }
  
  static checkLocalStorage() {
    try {
      localStorage.setItem('test', 'test');
      localStorage.removeItem('test');
      return true;
    } catch (e) {
      return false;
    }
  }
  
  static checkSessionStorage() {
    try {
      sessionStorage.setItem('test', 'test');
      sessionStorage.removeItem('test');
      return true;
    } catch (e) {
      return false;
    }
  }
}

export default BrowserCompatibility;
```

### 性能测试

```javascript
// assets/js/utils/performance.js
class Performance {
  static measure(name, fn) {
    const start = performance.now();
    const result = fn();
    const end = performance.now();
    
    console.log(`${name} took ${(end - start).toFixed(2)}ms`);
    return result;
  }
  
  static async measureAsync(name, fn) {
    const start = performance.now();
    const result = await fn();
    const end = performance.now();
    
    console.log(`${name} took ${(end - start).toFixed(2)}ms`);
    return result;
  }
}

export default Performance;
```

---

## 发布与分发

### 主题打包

```bash
#!/bin/bash

# 主题打包脚本

THEME_NAME="my_theme"
THEME_VERSION="1.0.0"
PACKAGE_NAME="${THEME_NAME}-${THEME_VERSION}.zip"

echo "开始打包主题: ${THEME_NAME} v${THEME_VERSION}"

# 创建临时目录
TEMP_DIR="temp_package"
rm -rf "$TEMP_DIR"
mkdir -p "$TEMP_DIR"

# 复制主题文件
echo "复制主题文件..."
cp -r public/themes/clientarea/"${THEME_NAME}" "$TEMP_DIR/"

# 生成README
echo "生成README文件..."
cat > "$TEMP_DIR/README.md" << EOF
# ${THEME_NAME}

## 主题信息
- 名称: ${THEME_NAME}
- 版本: ${THEME_VERSION}
- 作者: 开发者
- 类型: 客户端主题

## 安装说明
1. 将主题文件上传到 \`public/themes/clientarea/\` 目录
2. 在后台主题管理中启用主题
3. 配置主题选项

## 更新日志
### ${THEME_VERSION}
- 初始版本发布
EOF

# 创建压缩包
echo "创建压缩包..."
cd "$TEMP_DIR"
zip -r "../${PACKAGE_NAME}" "${THEME_NAME}" README.md
cd ..

# 清理临时目录
echo "清理临时目录..."
rm -rf "$TEMP_DIR"

echo "打包完成: ${PACKAGE_NAME}"
```

### 版本管理

```bash
# Git版本标签
git tag -a v1.0.0 -m "Release version 1.0.0"
git push origin v1.0.0

# 查看版本标签
git tag -l

# 删除版本标签
git tag -d v1.0.0
git push origin :refs/tags/v1.0.0
```

---

## 总结

本开发指南提供了完整的主题包开发流程，从环境准备到发布分发的全过程。

### 关键要点

1. **遵循规范**: 严格按照主题开发规范进行开发
2. **模块化设计**: 使用模块化方式组织代码
3. **响应式设计**: 确保主题在各种设备上正常显示
4. **性能优化**: 优化资源加载和渲染性能
5. **兼容性**: 确保浏览器兼容性
6. **测试充分**: 进行充分的测试和调试

### 学习路径

1. **初级**: 学习基础模板语法和CSS
2. **中级**: 掌握组件开发和JavaScript交互
3. **高级**: 理解主题继承和性能优化
4. **专家**: 能够开发复杂主题和系统扩展

通过本指南的学习和实践，开发者可以快速掌握主题包开发技能，为系统贡献高质量的主题。
