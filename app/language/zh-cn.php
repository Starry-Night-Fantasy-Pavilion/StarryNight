<?php

/**
 * 系统级语言包（简版示例）
 *
 * 使用方式：
 * - FrontendMapper::translate('common.login')
 * - Theme::renderTemplate() 内可自行注入 mapper 并调用 translate
 */
return [
    'common' => [
        'welcome' => '欢迎',
        'home' => '首页',
        'login' => '登录',
        'register' => '注册',
        'logout' => '退出',
        'submit' => '提交',
        'cancel' => '取消',
        'save' => '保存',
        'delete' => '删除',
        'edit' => '编辑',
        'back' => '返回',
        'next' => '下一步',
        'previous' => '上一步',
        'search' => '搜索',
        'loading' => '加载中...',
    ],
    'user' => [
        'username' => '用户名',
        'password' => '密码',
        'email' => '邮箱',
        'phone' => '手机号',
        'profile' => '个人资料',
        'settings' => '设置',
        'security' => '安全设置',
    ],
    'errors' => [
        'unauthorized' => '未授权访问',
        'forbidden' => '无权限访问',
        'not_found' => '页面不存在',
        'server_error' => '服务器错误',
    ],
];

