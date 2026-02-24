<?php

use app\install\controller\IndexController;

// 定义应用根目录的绝对路径
if (!defined('APP_PATH')) {
    define('APP_PATH', __DIR__ . '/../app/');
}

// 引入 Composer 自动加载
require_once __DIR__ . '/../vendor/autoload.php';

// 引入核心辅助函数
require_once __DIR__ . '/../app/helpers.php';

// 初始化安装控制器
$installController = new IndexController();

// 获取步骤参数
$step = isset($_GET['step']) ? (int)$_GET['step'] : 0;

// 调用对应的安装步骤
$installController->index();
