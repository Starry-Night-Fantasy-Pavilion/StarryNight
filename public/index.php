<?php

// 设定时区
date_default_timezone_set('Asia/Shanghai');

// 加载 .env 配置
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (preg_match('/^"(.*)"$/', $value, $matches)) {
            $value = $matches[1];
        } elseif (preg_match("/^'(.*)'$/", $value, $matches)) {
            $value = $matches[1];
        }
        $env[$name] = $value;
    }
    define('ENV_SETTINGS', $env);
}

// 引入 Composer 自动加载
require_once __DIR__ . '/../vendor/autoload.php';

// 加载核心辅助函数
require_once __DIR__ . '/../app/helpers.php';

// 初始化核心应用服务容器
use Core\Application;
use Core\Container\Container;

$appConfig = [
    'debug' => get_env('APP_DEBUG', 'false') === 'true',
    'env' => get_env('APP_ENV', 'production'),
    'timezone' => 'Asia/Shanghai',
    'charset' => 'UTF-8',
    'log' => [
        'path' => __DIR__ . '/../storage/logs',
        'level' => get_env('LOG_LEVEL', 'debug'),
    ],
    'cache' => [
        'driver' => 'file',
        'path' => __DIR__ . '/../storage/cache',
    ],
    'queue' => [
        'driver' => 'database',
        'table' => 'jobs',
        'connection' => 'default',
    ],
    'csrf' => [
        'token_length' => 32,
        'token_name' => 'csrf_token',
        'header_name' => 'X-CSRF-TOKEN',
    ],
    'xss' => [
        'allowed_tags' => [],
        'encoding' => 'UTF-8',
    ],
];

// 创建应用实例并注册核心服务（Application内部已注册异常处理器）
$app = new Application($appConfig);

// 启动应用（初始化Session、执行中间件等）
$app->boot();

// 注册应用层异常处理器（处理错误页面渲染）
use app\services\ErrorHandler;
ErrorHandler::register();

// 注册事件订阅者
use app\providers\EventServiceProvider;
EventServiceProvider::register();

use app\services\Router;

// 检查安装锁定文件
$lockFile = __DIR__ . '/../app/install/install.lock';
if (!file_exists($lockFile)) {
    // 未安装，跳转到安装向导
    header('Location: /install.php');
    exit;
}

// 初始化路由
$router = new Router();

// 获取后台路径前缀
$adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '/');

// 后台登录页面
$router->get('/' . $adminPrefix . '/login', ['app\admin\controller\AuthController', 'loginForm']);
$router->post('/' . $adminPrefix . '/login', ['app\admin\controller\AuthController', 'login']);
$router->get('/' . $adminPrefix . '/logout', ['app\admin\controller\AuthController', 'logout']);

// 后台管理首页
$router->get('/' . $adminPrefix, ['app\admin\controller\DashboardController', 'index']);

// 后台用户管理（CRM）
$router->get('/' . $adminPrefix . '/crm/users', ['app\admin\controller\CrmController', 'index']);
$router->post('/' . $adminPrefix . '/crm/users/batch', ['app\admin\controller\CrmController', 'batchAction']);
$router->get('/' . $adminPrefix . '/crm/user/add', ['app\admin\controller\CrmController', 'add']);
$router->post('/' . $adminPrefix . '/crm/user/add', ['app\admin\controller\CrmController', 'add']);
$router->get('/' . $adminPrefix . '/crm/user/{id}', ['app\admin\controller\CrmController', 'details']);
$router->get('/' . $adminPrefix . '/crm/user/{id}/edit', ['app\admin\controller\CrmController', 'edit']);
$router->post('/' . $adminPrefix . '/crm/user/{id}/edit', ['app\admin\controller\CrmController', 'edit']);
$router->get('/' . $adminPrefix . '/crm/user/{id}/balance', ['app\admin\controller\CrmController', 'adjustBalance']);
$router->post('/' . $adminPrefix . '/crm/user/{id}/balance', ['app\admin\controller\CrmController', 'adjustBalance']);
$router->get('/' . $adminPrefix . '/crm/user/{id}/toggle', ['app\admin\controller\CrmController', 'toggleStatus']);
$router->get('/' . $adminPrefix . '/crm/user/{id}/freeze', ['app\admin\controller\CrmController', 'freeze']);
$router->get('/' . $adminPrefix . '/crm/user/{id}/unfreeze', ['app\admin\controller\CrmController', 'unfreeze']);
$router->get('/' . $adminPrefix . '/crm/user/{id}/delete', ['app\admin\controller\CrmController', 'delete']);
$router->get('/' . $adminPrefix . '/crm/user/{id}/restore', ['app\admin\controller\CrmController', 'restore']);

// 后台运营数据（仪表盘下钻）
$router->get('/' . $adminPrefix . '/operations', ['app\admin\controller\OperationsController', 'index']);
$router->get('/' . $adminPrefix . '/operations/new-user', ['app\admin\controller\OperationsController', 'newUser']);
$router->get('/' . $adminPrefix . '/operations/dau', ['app\admin\controller\OperationsController', 'dau']);
$router->get('/' . $adminPrefix . '/operations/coin-spend', ['app\admin\controller\OperationsController', 'coinSpend']);
$router->get('/' . $adminPrefix . '/operations/revenue', ['app\admin\controller\OperationsController', 'revenue']);
$router->get('/' . $adminPrefix . '/operations/new-novel', ['app\admin\controller\OperationsController', 'newNovel']);
$router->get('/' . $adminPrefix . '/operations/new-music', ['app\admin\controller\OperationsController', 'newMusic']);
$router->get('/' . $adminPrefix . '/operations/new-anime', ['app\admin\controller\OperationsController', 'newAnime']);

// 后台通知栏 / 用户反馈 / 公告
$router->get('/' . $adminPrefix . '/notice/list', ['app\admin\controller\NoticeAnnouncementController', 'noticeList']);
$router->get('/' . $adminPrefix . '/notice/edit/{id}', ['app\admin\controller\NoticeAnnouncementController', 'noticeEdit']);
$router->get('/' . $adminPrefix . '/notice/delete/{id}', ['app\admin\controller\NoticeAnnouncementController', 'noticeDelete']);
$router->get('/' . $adminPrefix . '/notice/toggle/{id}', ['app\admin\controller\NoticeAnnouncementController', 'noticeToggle']);

$router->get('/' . $adminPrefix . '/feedback/list', ['app\admin\controller\NoticeAnnouncementController', 'feedbackList']);
$router->get('/' . $adminPrefix . '/feedback/detail/{id}', ['app\admin\controller\NoticeAnnouncementController', 'feedbackDetail']);
$router->get('/' . $adminPrefix . '/feedback/delete/{id}', ['app\admin\controller\NoticeAnnouncementController', 'feedbackDelete']);

$router->get('/' . $adminPrefix . '/announcement/list', ['app\admin\controller\NoticeAnnouncementController', 'announcementList']);
$router->get('/' . $adminPrefix . '/announcement/edit/{id}', ['app\admin\controller\NoticeAnnouncementController', 'announcementEdit']);
$router->get('/' . $adminPrefix . '/announcement/delete/{id}', ['app\admin\controller\NoticeAnnouncementController', 'announcementDelete']);
$router->get('/' . $adminPrefix . '/announcement/toggle/{id}', ['app\admin\controller\NoticeAnnouncementController', 'announcementToggle']);
$router->get('/' . $adminPrefix . '/announcement/categories', ['app\admin\controller\NoticeAnnouncementController', 'announcementCategories']);
$router->get('/' . $adminPrefix . '/announcement/category/{id}', ['app\admin\controller\NoticeAnnouncementController', 'announcementCategoryEdit']);
$router->get('/' . $adminPrefix . '/announcement/category-delete/{id}', ['app\admin\controller\NoticeAnnouncementController', 'announcementCategoryDelete']);

// 后台知识库管理
$router->get('/' . $adminPrefix . '/knowledge', ['app\admin\controller\KnowledgeController', 'index']);
$router->get('/' . $adminPrefix . '/knowledge/details/{id}', ['app\admin\controller\KnowledgeController', 'details']);
$router->get('/' . $adminPrefix . '/knowledge/create', ['app\admin\controller\KnowledgeController', 'create']);
$router->post('/' . $adminPrefix . '/knowledge/create', ['app\admin\controller\KnowledgeController', 'create']);
$router->get('/' . $adminPrefix . '/knowledge/edit/{id}', ['app\admin\controller\KnowledgeController', 'edit']);
$router->post('/' . $adminPrefix . '/knowledge/edit/{id}', ['app\admin\controller\KnowledgeController', 'edit']);
$router->post('/' . $adminPrefix . '/knowledge/delete/{id}', ['app\admin\controller\KnowledgeController', 'delete']);
$router->get('/' . $adminPrefix . '/knowledge/items/{id}', ['app\admin\controller\KnowledgeController', 'items']);
$router->get('/' . $adminPrefix . '/knowledge/create-item/{id}', ['app\admin\controller\KnowledgeController', 'createItem']);
$router->post('/' . $adminPrefix . '/knowledge/create-item/{id}', ['app\admin\controller\KnowledgeController', 'createItem']);
$router->get('/' . $adminPrefix . '/knowledge/edit-item/{id}', ['app\admin\controller\KnowledgeController', 'editItem']);
$router->post('/' . $adminPrefix . '/knowledge/edit-item/{id}', ['app\admin\controller\KnowledgeController', 'editItem']);
$router->post('/' . $adminPrefix . '/knowledge/delete-item/{id}', ['app\admin\controller\KnowledgeController', 'deleteItem']);
$router->get('/' . $adminPrefix . '/knowledge/categories', ['app\admin\controller\KnowledgeController', 'categories']);
$router->post('/' . $adminPrefix . '/knowledge/categories', ['app\admin\controller\KnowledgeController', 'categories']);
$router->get('/' . $adminPrefix . '/knowledge/templates', ['app\admin\controller\KnowledgeController', 'templates']);
$router->post('/' . $adminPrefix . '/knowledge/templates', ['app\admin\controller\KnowledgeController', 'templates']);
$router->get('/' . $adminPrefix . '/knowledge/statistics', ['app\admin\controller\KnowledgeController', 'statistics']);

// 后台会员、财务与营销管理
$router->get('/' . $adminPrefix . '/finance/membership-levels', ['app\admin\controller\MembershipFinanceController', 'membershipLevels']);
$router->get('/' . $adminPrefix . '/finance/membership-level/{id}', ['app\admin\controller\MembershipFinanceController', 'membershipLevelEdit']);
$router->get('/' . $adminPrefix . '/finance/coin-packages', ['app\admin\controller\MembershipFinanceController', 'coinPackages']);
$router->get('/' . $adminPrefix . '/finance/coin-package/{id}', ['app\admin\controller\MembershipFinanceController', 'coinPackageEdit']);
$router->get('/' . $adminPrefix . '/finance/coin-package-delete/{id}', ['app\admin\controller\MembershipFinanceController', 'coinPackageDelete']);
$router->get('/' . $adminPrefix . '/finance/orders', ['app\admin\controller\MembershipFinanceController', 'orders']);
$router->get('/' . $adminPrefix . '/finance/order-refund/{id}', ['app\admin\controller\MembershipFinanceController', 'orderRefund']);
$router->get('/' . $adminPrefix . '/finance/coin-spend-records', ['app\admin\controller\MembershipFinanceController', 'coinSpendRecords']);
$router->get('/' . $adminPrefix . '/finance/coupons', ['app\admin\controller\MembershipFinanceController', 'coupons']);
$router->get('/' . $adminPrefix . '/finance/activities', ['app\admin\controller\MembershipFinanceController', 'activities']);
$router->get('/' . $adminPrefix . '/finance/promotion-links', ['app\admin\controller\MembershipFinanceController', 'promotionLinks']);
$router->get('/' . $adminPrefix . '/finance/site-messages', ['app\admin\controller\MembershipFinanceController', 'siteMessages']);
$router->get('/' . $adminPrefix . '/finance/notification-templates', ['app\admin\controller\MembershipFinanceController', 'notificationTemplates']);
$router->post('/' . $adminPrefix . '/finance/notification-templates', ['app\admin\controller\MembershipFinanceController', 'notificationTemplates']);

// 后台未来功能管理
$router->get('/' . $adminPrefix . '/future-features', ['app\admin\controller\FutureFeaturesController', 'index']);
$router->post('/' . $adminPrefix . '/future-features/update', ['app\admin\controller\FutureFeaturesController', 'updateFeature']);
$router->post('/' . $adminPrefix . '/future-features/batch-update', ['app\admin\controller\FutureFeaturesController', 'batchUpdateFeatures']);
$router->get('/' . $adminPrefix . '/future-features/stats', ['app\admin\controller\FutureFeaturesController', 'getFeatureStats']);

// 后台 AI 资源与配置管理
$router->get('/' . $adminPrefix . '/ai/channels', ['app\admin\controller\AIResourcesController', 'channels']);
$router->post('/' . $adminPrefix . '/ai/channels', ['app\admin\controller\AIResourcesController', 'channels']);
$router->get('/' . $adminPrefix . '/ai/monitor', ['app\admin\controller\AIResourcesController', 'monitor']);
$router->get('/' . $adminPrefix . '/ai/model-prices', ['app\admin\controller\AIResourcesController', 'modelPrices']);
$router->post('/' . $adminPrefix . '/ai/model-prices', ['app\admin\controller\AIResourcesController', 'modelPrices']);
$router->get('/' . $adminPrefix . '/ai/preset-models', ['app\admin\controller\AIResourcesController', 'presetModels']);
$router->post('/' . $adminPrefix . '/ai/preset-models', ['app\admin\controller\AIResourcesController', 'presetModels']);
$router->get('/' . $adminPrefix . '/ai/templates', ['app\admin\controller\AIResourcesController', 'templates']);
$router->post('/' . $adminPrefix . '/ai/templates', ['app\admin\controller\AIResourcesController', 'templates']);
$router->get('/' . $adminPrefix . '/ai/agents', ['app\admin\controller\AIResourcesController', 'agents']);
$router->post('/' . $adminPrefix . '/ai/agents', ['app\admin\controller\AIResourcesController', 'agents']);
$router->get('/' . $adminPrefix . '/ai/audits', ['app\admin\controller\AIResourcesController', 'audits']);
$router->get('/' . $adminPrefix . '/ai/audits/details/{id}', ['app\admin\controller\AIResourcesController', 'auditDetails']);
$router->post('/' . $adminPrefix . '/ai/audits/details/{id}', ['app\admin\controller\AIResourcesController', 'auditDetails']);
$router->get('/' . $adminPrefix . '/ai/embeddings', ['app\admin\controller\AIResourcesController', 'embeddings']);
$router->post('/' . $adminPrefix . '/ai/embeddings', ['app\admin\controller\AIResourcesController', 'embeddings']);

// 后台插件管理
$router->get('/' . $adminPrefix . '/plugins', ['app\admin\controller\PluginController', 'index']);
$router->post('/' . $adminPrefix . '/plugins/install', ['app\admin\controller\PluginController', 'install']);
$router->post('/' . $adminPrefix . '/plugins/uninstall', ['app\admin\controller\PluginController', 'uninstall']);
$router->post('/' . $adminPrefix . '/plugins/toggle', ['app\admin\controller\PluginController', 'toggle']);
$router->get('/' . $adminPrefix . '/plugins/config', ['app\admin\controller\PluginController', 'config']);
$router->post('/' . $adminPrefix . '/plugins/config', ['app\admin\controller\PluginController', 'config']);

// 后台主题管理
$router->get('/' . $adminPrefix . '/themes', ['app\admin\controller\ThemeController', 'index']);

// 后台系统设置与安全审计
$router->get('/' . $adminPrefix . '/system/basic-settings', ['app\admin\controller\SystemConfigController', 'basicSettings']);
$router->post('/' . $adminPrefix . '/system/basic-settings', ['app\admin\controller\SystemConfigController', 'basicSettings']);
$router->get('/' . $adminPrefix . '/system/register-settings', ['app\admin\controller\SystemConfigController', 'registerSettings']);
$router->post('/' . $adminPrefix . '/system/register-settings', ['app\admin\controller\SystemConfigController', 'registerSettings']);
$router->get('/' . $adminPrefix . '/system/legal-settings', ['app\admin\controller\SystemConfigController', 'legalSettings']);
$router->post('/' . $adminPrefix . '/system/legal-settings', ['app\admin\controller\SystemConfigController', 'legalSettings']);
$router->get('/' . $adminPrefix . '/system/storage-config', ['app\admin\controller\SystemConfigController', 'storageConfig']);
$router->post('/' . $adminPrefix . '/system/storage-config', ['app\admin\controller\SystemConfigController', 'storageConfig']);
$router->get('/' . $adminPrefix . '/system/roles', ['app\admin\controller\SystemConfigController', 'roles']);
$router->get('/' . $adminPrefix . '/system/role/{id}', ['app\admin\controller\SystemConfigController', 'roleEdit']);
$router->post('/' . $adminPrefix . '/system/role/{id}', ['app\admin\controller\SystemConfigController', 'roleEdit']);
$router->get('/' . $adminPrefix . '/system/role-delete/{id}', ['app\admin\controller\SystemConfigController', 'roleDelete']);
$router->get('/' . $adminPrefix . '/system/operation-logs', ['app\admin\controller\SystemConfigController', 'operationLogs']);
$router->get('/' . $adminPrefix . '/system/login-logs', ['app\admin\controller\SystemConfigController', 'loginLogs']);
$router->get('/' . $adminPrefix . '/system/exception-logs', ['app\admin\controller\SystemConfigController', 'exceptionLogs']);
$router->get('/' . $adminPrefix . '/system/security', ['app\admin\controller\SystemConfigController', 'securitySettings']);
$router->post('/' . $adminPrefix . '/system/security', ['app\admin\controller\SystemConfigController', 'securitySettings']);
$router->get('/' . $adminPrefix . '/system/starry-night-engine', ['app\admin\controller\SystemConfigController', 'starryNightEngineConfig']);
$router->post('/' . $adminPrefix . '/system/starry-night-engine', ['app\admin\controller\SystemConfigController', 'starryNightEngineConfig']);
$router->get('/' . $adminPrefix . '/system/starry-night-engine/{id}', ['app\admin\controller\SystemConfigController', 'starryNightEngineConfigEdit']);
$router->get('/' . $adminPrefix . '/system/home-settings', ['app\admin\controller\SystemConfigController', 'homeSettings']);
$router->post('/' . $adminPrefix . '/system/home-settings', ['app\admin\controller\SystemConfigController', 'homeSettings']);

// 后台内容审查
$router->get('/' . $adminPrefix . '/content-review', ['app\admin\controller\ContentReviewController', 'index']);
$router->get('/' . $adminPrefix . '/content-review/details/{id}', ['app\admin\controller\ContentReviewController', 'details']);
$router->post('/' . $adminPrefix . '/content-review/details/{id}', ['app\admin\controller\ContentReviewController', 'details']);
$router->get('/' . $adminPrefix . '/content-review/configs', ['app\admin\controller\ContentReviewController', 'configs']);
$router->post('/' . $adminPrefix . '/content-review/configs', ['app\admin\controller\ContentReviewController', 'configs']);

// 后台一致性检查
$router->get('/' . $adminPrefix . '/consistency/config', ['app\admin\controller\ConsistencyController', 'config']);
$router->post('/' . $adminPrefix . '/consistency/config', ['app\admin\controller\ConsistencyController', 'saveConfig']);
$router->get('/' . $adminPrefix . '/consistency/core-settings', ['app\admin\controller\ConsistencyController', 'coreSettings']);
$router->post('/' . $adminPrefix . '/consistency/core-settings', ['app\admin\controller\ConsistencyController', 'saveCoreSettings']);
$router->get('/' . $adminPrefix . '/consistency/check', ['app\admin\controller\ConsistencyController', 'check']);
$router->post('/' . $adminPrefix . '/consistency/check', ['app\admin\controller\ConsistencyController', 'manualCheck']);
$router->get('/' . $adminPrefix . '/consistency/reports', ['app\admin\controller\ConsistencyController', 'reports']);
$router->get('/' . $adminPrefix . '/consistency/analytics', ['app\admin\controller\ConsistencyController', 'analytics']);

// 后台社区内容管理
$router->get('/' . $adminPrefix . '/community', ['app\admin\controller\CommunityController', 'index']);
$router->post('/' . $adminPrefix . '/community/actions', ['app\admin\controller\CommunityController', 'actions']);
$router->get('/' . $adminPrefix . '/community/categories', ['app\admin\controller\CommunityController', 'categories']);
$router->post('/' . $adminPrefix . '/community/categories', ['app\admin\controller\CommunityController', 'categories']);
$router->get('/' . $adminPrefix . '/community/tags', ['app\admin\controller\CommunityController', 'tags']);
$router->post('/' . $adminPrefix . '/community/tags', ['app\admin\controller\CommunityController', 'tags']);
$router->get('/' . $adminPrefix . '/community/reports', ['app\admin\controller\CommunityController', 'reports']);
$router->post('/' . $adminPrefix . '/community/reports', ['app\admin\controller\CommunityController', 'reports']);
$router->get('/' . $adminPrefix . '/community/activities', ['app\admin\controller\CommunityController', 'activities']);
$router->post('/' . $adminPrefix . '/community/activities', ['app\admin\controller\CommunityController', 'activities']);

// 前端首页
$router->get('/', ['app\frontend\controller\IndexController', 'index']);

// 前端登录/注册
$router->get('/login', ['app\frontend\controller\AuthController', 'loginForm']);
$router->post('/login', ['app\frontend\controller\AuthController', 'login']);
$router->get('/register', ['app\frontend\controller\AuthController', 'registerForm']);
$router->post('/register', ['app\frontend\controller\AuthController', 'register']);
$router->post('/register/send-code', ['app\frontend\controller\AuthController', 'sendRegisterCode']);
$router->get('/logout', ['app\frontend\controller\AuthController', 'logout']);
$router->get('/api/captcha/refresh', ['app\frontend\controller\AuthController', 'refreshCaptcha']);

// 前端重置密码（整合找回密码和重置密码功能）
$router->get('/reset-password', ['app\frontend\controller\AuthController', 'resetPasswordForm']);
$router->post('/reset-password', ['app\frontend\controller\AuthController', 'resetPassword']);
$router->post('/reset-password/send-code', ['app\frontend\controller\AuthController', 'sendResetCode']);

// 协议（.txt，后台可配置）
$router->get('/user-agreement', ['app\frontend\controller\LegalController', 'userAgreement']);
$router->get('/privacy-policy', ['app\frontend\controller\LegalController', 'privacyPolicy']);

// 前端小说
$router->get('/novel', ['app\frontend\controller\NovelController', 'index']);
$router->get('/novel/create', ['app\frontend\controller\NovelController', 'create']);
$router->get('/novel/{id}/editor', ['app\frontend\controller\NovelController', 'editor']);
$router->post('/novel/ai-continue', ['app\frontend\controller\NovelController', 'aiContinue']);
$router->post('/novel/ai-rewrite', ['app\frontend\controller\NovelController', 'aiRewrite']);
$router->post('/novel/ai-expand', ['app\frontend\controller\NovelController', 'aiExpand']);
$router->post('/novel/ai-polish', ['app\frontend\controller\NovelController', 'aiPolish']);
$router->post('/novel/generate-outline', ['app\frontend\controller\NovelController', 'generateOutline']);
$router->post('/novel/generate-character', ['app\frontend\controller\NovelController', 'generateCharacter']);
$router->get('/novel/chapter/{id}', ['app\frontend\controller\NovelController', 'getChapter']);
$router->post('/novel/save-chapter', ['app\frontend\controller\NovelController', 'saveChapter']);

// 前端小说创作工具
$router->get('/novel_creation', ['app\frontend\controller\NovelCreationController', 'index']);
$router->get('/novel_creation/chapter_analysis', ['app\frontend\controller\NovelCreationController', 'chapterAnalysis']);
$router->post('/novel_creation/chapter_analysis', ['app\frontend\controller\NovelCreationController', 'doChapterAnalysis']);
$router->get('/novel_creation/book_analysis', ['app\frontend\controller\NovelCreationController', 'bookAnalysis']);
$router->post('/novel_creation/book_analysis', ['app\frontend\controller\NovelCreationController', 'doBookAnalysis']);
$router->get('/novel_creation/imitation_writing', ['app\frontend\controller\NovelCreationController', 'imitationWriting']);
$router->post('/novel_creation/imitation_writing', ['app\frontend\controller\NovelCreationController', 'doImitationWriting']);
$router->get('/novel_creation/opening_generator', ['app\frontend\controller\NovelCreationController', 'openingGenerator']);
$router->post('/novel_creation/opening_generator', ['app\frontend\controller\NovelCreationController', 'doOpeningGenerator']);
$router->get('/novel_creation/title_generator', ['app\frontend\controller\NovelCreationController', 'titleGenerator']);
$router->post('/novel_creation/title_generator', ['app\frontend\controller\NovelCreationController', 'doTitleGenerator']);
$router->get('/novel_creation/description_generator', ['app\frontend\controller\NovelCreationController', 'descriptionGenerator']);
$router->post('/novel_creation/description_generator', ['app\frontend\controller\NovelCreationController', 'doDescriptionGenerator']);
$router->get('/novel_creation/cheat_generator', ['app\frontend\controller\NovelCreationController', 'cheatGenerator']);
$router->post('/novel_creation/cheat_generator', ['app\frontend\controller\NovelCreationController', 'doCheatGenerator']);
$router->get('/novel_creation/name_generator', ['app\frontend\controller\NovelCreationController', 'nameGenerator']);
$router->post('/novel_creation/name_generator', ['app\frontend\controller\NovelCreationController', 'doNameGenerator']);
$router->get('/novel_creation/cover_generator', ['app\frontend\controller\NovelCreationController', 'coverGenerator']);
$router->post('/novel_creation/cover_generator', ['app\frontend\controller\NovelCreationController', 'doCoverGenerator']);
$router->get('/novel_creation/short_story', ['app\frontend\controller\NovelCreationController', 'shortStory']);
$router->post('/novel_creation/short_story', ['app\frontend\controller\NovelCreationController', 'doShortStory']);
$router->get('/novel_creation/short_drama', ['app\frontend\controller\NovelCreationController', 'shortDrama']);
$router->post('/novel_creation/short_drama', ['app\frontend\controller\NovelCreationController', 'doShortDrama']);
$router->get('/novel_creation/editor', ['app\frontend\controller\NovelCreationController', 'editor']);
$router->get('/novel_creation/outline_generator', ['app\frontend\controller\NovelCreationController', 'outlineGenerator']);
$router->post('/novel_creation/outline_generator', ['app\frontend\controller\NovelCreationController', 'doOutlineGenerator']);
$router->post('/novel_creation/save-outline', ['app\frontend\controller\NovelCreationController', 'saveOutline']);
$router->post('/novel_creation/delete-outline', ['app\frontend\controller\NovelCreationController', 'deleteOutline']);
$router->get('/novel_creation/character_manager', ['app\frontend\controller\NovelCreationController', 'characterManager']);
$router->get('/novel_creation/character_generator', ['app\frontend\controller\NovelCreationController', 'characterGenerator']);
$router->post('/novel_creation/character_generator', ['app\frontend\controller\NovelCreationController', 'doCharacterGenerator']);
$router->post('/novel_creation/save-character', ['app\frontend\controller\NovelCreationController', 'saveCharacter']);
$router->post('/novel_creation/delete-character', ['app\frontend\controller\NovelCreationController', 'deleteCharacter']);
$router->get('/novel_creation/character_consistency', ['app\frontend\controller\NovelCreationController', 'characterConsistencyCheck']);
$router->post('/novel_creation/character_consistency', ['app\frontend\controller\NovelCreationController', 'doCharacterConsistencyCheck']);
$router->post('/novel_creation/create-novel', ['app\frontend\controller\NovelCreationController', 'createNovel']);
$router->post('/novel_creation/save-chapter', ['app\frontend\controller\NovelCreationController', 'saveChapter']);
$router->post('/novel_creation/delete-chapter', ['app\frontend\controller\NovelCreationController', 'deleteChapter']);
$router->post('/novel_creation/reorder-chapters', ['app\frontend\controller\NovelCreationController', 'reorderChapters']);
$router->get('/novel_creation/get-versions', ['app\frontend\controller\NovelCreationController', 'getChapterVersions']);
$router->post('/novel_creation/restore-version', ['app\frontend\controller\NovelCreationController', 'restoreVersion']);
$router->post('/novel_creation/ai-continue', ['app\frontend\controller\NovelCreationController', 'aiContinue']);
$router->post('/novel_creation/ai-rewrite', ['app\frontend\controller\NovelCreationController', 'aiRewrite']);
$router->post('/novel_creation/ai-expand', ['app\frontend\controller\NovelCreationController', 'aiExpand']);
$router->post('/novel_creation/ai-polish', ['app\frontend\controller\NovelCreationController', 'aiPolish']);
$router->get('/novel_creation/worldview_generator', ['app\frontend\controller\NovelCreationController', 'worldviewGenerator']);
$router->post('/novel_creation/worldview_generator', ['app\frontend\controller\NovelCreationController', 'doWorldviewGenerator']);
$router->get('/novel_creation/brainstorm_generator', ['app\frontend\controller\NovelCreationController', 'brainstormGenerator']);
$router->post('/novel_creation/brainstorm_generator', ['app\frontend\controller\NovelCreationController', 'doBrainstormGenerator']);

// 前端AI音乐
$router->get('/ai_music', ['app\frontend\controller\AiMusicController', 'index']);

// 前端动画制作
$router->get('/anime_production', ['app\frontend\controller\AnimeProductionController', 'index']);

// 前端知识库
$router->get('/knowledge', ['app\frontend\controller\KnowledgeController', 'index']);

// 前端提示词工程
$router->get('/prompts', ['app\frontend\controller\PromptController', 'index']);

// 前端模板库
$router->get('/templates', ['app\frontend\controller\TemplateController', 'index']);
$router->get('/templates/create', ['app\frontend\controller\TemplateController', 'create']);
$router->post('/templates', ['app\frontend\controller\TemplateController', 'store']);
$router->get('/templates/{id}', ['app\frontend\controller\TemplateController', 'show']);
$router->post('/templates/apply', ['app\frontend\controller\TemplateController', 'apply']);

// 前端智能体
$router->get('/agents', ['app\frontend\controller\AgentController', 'index']);
$router->get('/agents/create', ['app\frontend\controller\AgentController', 'create']);
$router->post('/agents', ['app\frontend\controller\AgentController', 'store']);
$router->get('/agents/{id}', ['app\frontend\controller\AgentController', 'show']);
$router->post('/agents/use', ['app\frontend\controller\AgentController', 'use']);

// 前端资源分享平台
$router->get('/share', ['app\frontend\controller\ShareController', 'index']);
$router->get('/share/knowledge', ['app\frontend\controller\ShareController', 'knowledge']);
$router->get('/share/prompts', ['app\frontend\controller\ShareController', 'prompts']);
$router->get('/share/templates', ['app\frontend\controller\ShareController', 'templates']);
$router->get('/share/agents', ['app\frontend\controller\ShareController', 'agents']);
$router->get('/share/{id}', ['app\frontend\controller\ShareController', 'show']);

// 前端会员
$router->get('/membership', ['app\frontend\controller\MembershipController', 'index']);
$router->get('/membership/packages', ['app\frontend\controller\MembershipController', 'packages']);
$router->get('/membership/orders', ['app\frontend\controller\MembershipController', 'orders']);
$router->get('/membership/recharge', ['app\frontend\controller\MembershipController', 'recharge']);
$router->get('/membership/token-records', ['app\frontend\controller\MembershipController', 'tokenRecords']);

// 前端排行榜
$router->get('/ranking', ['app\frontend\controller\RankingController', 'index']);

// 前端用户中心
$router->get('/user_center', ['app\frontend\controller\UserCenterController', 'index']);
$router->get('/user_center/profile', ['app\frontend\controller\UserCenterController', 'profile']);
$router->post('/user_center/profile', ['app\frontend\controller\UserCenterController', 'profile']);
$router->get('/user_center/starry_night_config', ['app\frontend\controller\UserCenterController', 'starryNightConfig']);
$router->post('/user_center/save_starry_night_config', ['app\frontend\controller\UserCenterController', 'saveStarryNightConfig']);
$router->get('/user_center/consistency_config', ['app\frontend\controller\UserCenterController', 'consistencyConfig']);
$router->post('/user_center/save_consistency_config', ['app\frontend\controller\UserCenterController', 'saveConsistencyConfig']);

// 前端存储
$router->get('/storage', ['app\frontend\controller\StorageController', 'index']);

// 前端公告
$router->get('/announcement', ['app\frontend\controller\AnnouncementController', 'index']);

// 前端通知栏
$router->get('/notice_bar', ['app\frontend\controller\NoticeBarController', 'index']);

// 前端众筹
$router->get('/crowdfunding', ['app\frontend\controller\CrowdfundingController', 'index']);
$router->get('/crowdfunding/create', ['app\frontend\controller\CrowdfundingController', 'create']);
$router->get('/crowdfunding/{id}', ['app\frontend\controller\CrowdfundingController', 'project']);

// 前端反馈
$router->get('/feedback', ['app\frontend\controller\FeedbackController', 'index']);

// 语言切换
$router->get('/language/switch', ['app\frontend\controller\LanguageController', 'switchLanguage']);
$router->get('/language/current', ['app\frontend\controller\LanguageController', 'getCurrentLanguage']);

// API 路由
$router->get('/api/v1/status', function() {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok', 'message' => '服务正常运行']);
});

// 分发路由
$router->dispatch();
