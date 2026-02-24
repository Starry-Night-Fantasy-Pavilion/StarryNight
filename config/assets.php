<?php

/**
 * 静态资源优化配置
 * 
 * 此配置用于定义需要合并和压缩的静态资源
 * 可以通过构建工具自动处理，也可以手动执行
 */

return [
    // CSS 资源合并配置
    'css' => [
        // 后台管理页面通用样式
        'admin-common' => [
            '/static/admin/css/style.css',
            '/static/admin/css/responsive-tables.css',
            '/static/admin/css/responsive-forms.css',
        ],
        
        // 仪表盘相关样式
        'admin-dashboard' => [
            '/static/admin/css/dashboard-base.css',
            '/static/admin/css/dashboard-cards.css',
            '/static/admin/css/dashboard-charts.css',
            '/static/admin/css/dashboard-icons.css',
            '/static/admin/css/dashboard-sections.css',
            '/static/admin/css/dashboard-forms.css',
            '/static/admin/css/dashboard-operations.css',
            '/static/admin/css/dashboard-v2-cards.css',
        ],
    ],
    
    // JavaScript 资源合并配置
    'js' => [
        // 后台管理页面通用脚本
        'admin-common' => [
            '/static/admin/js/admin-forms.js',
            '/static/admin/js/sidebar-toggle.js',
            '/static/admin/js/responsive-tables.js',
            '/static/admin/js/responsive-forms.js',
            '/static/admin/js/touch-interactions.js',
            '/static/admin/js/export-functionality.js',
        ],
        
        // 仪表盘相关脚本
        'admin-dashboard' => [
            '/static/admin/js/dashboard-charts.js',
            '/static/admin/js/dashboard-forms.js',
            '/static/admin/js/dashboard-interactions.js',
        ],
    ],
    
    // 资源压缩选项
    'minify' => [
        'enabled' => true,
        'remove_comments' => true,
        'remove_whitespace' => true,
    ],
    
    // 缓存配置
    'cache' => [
        'enabled' => true,
        'directory' => __DIR__ . '/../storage/framework/assets/',
        'busting' => 'filemtime', // 'filemtime' | 'md5' | 'manual'
    ],
];
