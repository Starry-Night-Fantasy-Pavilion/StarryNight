<?php

declare(strict_types=1);

/**
 * 路由配置文件
 * 
 * 定义应用的所有路由规则
 * 支持GET、POST、PUT、DELETE、PATCH等方法
 * 
 * 注意：此文件中的控制器类名应与实际存在的控制器匹配
 */

use Core\Routing\Router;

return [
    // API路由组
    'api' => [
        'prefix' => '/api',
        'middleware' => ['api'],
        'routes' => [
            // 认证相关
            ['GET', '/user', [\app\frontend\controller\AuthController::class, 'getCurrentUser']],
            ['POST', '/login', [\app\frontend\controller\AuthController::class, 'login']],
            ['POST', '/logout', [\app\frontend\controller\AuthController::class, 'logout']],
            ['POST', '/register', [\app\frontend\controller\AuthController::class, 'register']],
            
            // 用户中心
            ['GET', '/user/profile', [\app\frontend\controller\UserCenterController::class, 'profile']],
            ['POST', '/user/profile', [\app\frontend\controller\UserCenterController::class, 'updateProfile']],
            ['GET', '/user/settings', [\app\frontend\controller\UserCenterController::class, 'settings']],
            ['POST', '/user/settings', [\app\frontend\controller\UserCenterController::class, 'updateSettings']],
            
            // 小说相关
            ['GET', '/novels', [\app\frontend\controller\NovelController::class, 'index']],
            ['GET', '/novels/{id}', [\app\frontend\controller\NovelController::class, 'show']],
            ['POST', '/novels', [\app\frontend\controller\NovelController::class, 'store']],
            ['PUT', '/novels/{id}', [\app\frontend\controller\NovelController::class, 'update']],
            ['DELETE', '/novels/{id}', [\app\frontend\controller\NovelController::class, 'destroy']],
            
            // 小说章节
            ['GET', '/novels/{novel_id}/chapters', [\app\frontend\controller\NovelController::class, 'chapters']],
            ['GET', '/novels/{novel_id}/chapters/{chapter_id}', [\app\frontend\controller\NovelController::class, 'chapter']],
            ['POST', '/novels/{novel_id}/chapters', [\app\frontend\controller\NovelController::class, 'storeChapter']],
            ['PUT', '/novels/{novel_id}/chapters/{chapter_id}', [\app\frontend\controller\NovelController::class, 'updateChapter']],
            ['DELETE', '/novels/{novel_id}/chapters/{chapter_id}', [\app\frontend\controller\NovelController::class, 'destroyChapter']],
            
            // AI创作
            ['POST', '/ai/generate', [\app\frontend\controller\NovelCreationController::class, 'generate']],
            ['POST', '/ai/continue', [\app\frontend\controller\NovelCreationController::class, 'continue']],
            ['POST', '/ai/rewrite', [\app\frontend\controller\NovelCreationController::class, 'rewrite']],
            
            // 存储相关
            ['POST', '/storage/upload', [\app\frontend\controller\StorageController::class, 'upload']],
            ['GET', '/storage/files', [\app\frontend\controller\StorageController::class, 'files']],
            ['DELETE', '/storage/files/{id}', [\app\frontend\controller\StorageController::class, 'delete']],
            
            // 会员相关
            ['GET', '/membership/packages', [\app\frontend\controller\MembershipController::class, 'packages']],
            ['POST', '/membership/purchase', [\app\frontend\controller\MembershipController::class, 'purchase']],
            ['GET', '/membership/status', [\app\frontend\controller\MembershipController::class, 'status']],
            
            // 排行榜
            ['GET', '/ranking/novels', [\app\frontend\controller\RankingController::class, 'novels']],
            ['GET', '/ranking/users', [\app\frontend\controller\RankingController::class, 'users']],
            
            // 公告
            ['GET', '/announcements', [\app\frontend\controller\AnnouncementController::class, 'index']],
            ['GET', '/announcements/{id}', [\app\frontend\controller\AnnouncementController::class, 'show']],
            
            // 反馈
            ['POST', '/feedback', [\app\frontend\controller\FeedbackController::class, 'store']],
            
            // 知识库
            ['GET', '/knowledge', [\app\frontend\controller\KnowledgeController::class, 'index']],
            ['GET', '/knowledge/{id}', [\app\frontend\controller\KnowledgeController::class, 'show']],
            
            // AI Agent
            ['GET', '/agents', [\app\frontend\controller\AgentController::class, 'index']],
            ['GET', '/agents/{id}', [\app\frontend\controller\AgentController::class, 'show']],
            ['POST', '/agents/{id}/chat', [\app\frontend\controller\AgentController::class, 'chat']],
        ],
    ],
    
    // 后台管理路由组
    'admin' => [
        'prefix' => '/admin',
        'middleware' => ['admin', 'auth'],
        'routes' => [
            // 仪表盘
            ['GET', '/dashboard', [\app\admin\controller\DashboardController::class, 'index']],
            ['GET', '/stats', [\app\admin\controller\DashboardController::class, 'stats']],
            
            // 用户管理 (使用CrmController)
            ['GET', '/users', [\app\admin\controller\CrmController::class, 'users']],
            ['GET', '/users/{id}', [\app\admin\controller\CrmController::class, 'userDetails']],
            ['POST', '/users/{id}/edit', [\app\admin\controller\CrmController::class, 'userEdit']],
            ['POST', '/users/{id}/toggle-status', [\app\admin\controller\CrmController::class, 'toggleStatus']],
            
            // 小说管理 (使用ContentReviewController)
            ['GET', '/novels', [\app\admin\controller\ContentReviewController::class, 'novels']],
            ['GET', '/novels/{id}', [\app\admin\controller\ContentReviewController::class, 'novelDetails']],
            ['POST', '/novels/{id}/approve', [\app\admin\controller\ContentReviewController::class, 'approveNovel']],
            
            // 系统设置 (使用SystemConfigController)
            ['GET', '/settings', [\app\admin\controller\SystemConfigController::class, 'basicSettings']],
            ['POST', '/settings', [\app\admin\controller\SystemConfigController::class, 'saveBasicSettings']],
            ['GET', '/settings/security', [\app\admin\controller\SystemConfigController::class, 'securitySettings']],
            ['POST', '/settings/security', [\app\admin\controller\SystemConfigController::class, 'saveSecuritySettings']],
            ['GET', '/settings/legal', [\app\admin\controller\SystemConfigController::class, 'legalSettings']],
            ['POST', '/settings/legal', [\app\admin\controller\SystemConfigController::class, 'saveLegalSettings']],
            ['GET', '/settings/home', [\app\admin\controller\SystemConfigController::class, 'homeSettings']],
            ['POST', '/settings/home', [\app\admin\controller\SystemConfigController::class, 'homeSettings']],
            
            // AI渠道管理 (使用AIResourcesController)
            ['GET', '/ai-channels', [\app\admin\controller\AIResourcesController::class, 'channels']],
            ['POST', '/ai-channels', [\app\admin\controller\AIResourcesController::class, 'saveChannel']],
            ['PUT', '/ai-channels/{id}', [\app\admin\controller\AIResourcesController::class, 'updateChannel']],
            ['DELETE', '/ai-channels/{id}', [\app\admin\controller\AIResourcesController::class, 'deleteChannel']],
            
            // 插件管理
            ['GET', '/plugins', [\app\admin\controller\PluginController::class, 'index']],
            ['POST', '/plugins/{name}/enable', [\app\admin\controller\PluginController::class, 'enable']],
            ['POST', '/plugins/{name}/disable', [\app\admin\controller\PluginController::class, 'disable']],
            ['POST', '/plugins/{name}/configure', [\app\admin\controller\PluginController::class, 'configure']],
            
            // 会员管理
            ['GET', '/memberships', [\app\admin\controller\MembershipController::class, 'index']],
            ['GET', '/memberships/packages', [\app\admin\controller\MembershipController::class, 'packages']],
            ['POST', '/memberships/packages', [\app\admin\controller\MembershipController::class, 'savePackage']],
            ['PUT', '/memberships/packages/{id}', [\app\admin\controller\MembershipController::class, 'updatePackage']],
            ['DELETE', '/memberships/packages/{id}', [\app\admin\controller\MembershipController::class, 'deletePackage']],
            
            // 公告管理 (使用NoticeAnnouncementController)
            ['GET', '/announcements', [\app\admin\controller\NoticeAnnouncementController::class, 'announcements']],
            ['POST', '/announcements', [\app\admin\controller\NoticeAnnouncementController::class, 'saveAnnouncement']],
            ['PUT', '/announcements/{id}', [\app\admin\controller\NoticeAnnouncementController::class, 'updateAnnouncement']],
            ['DELETE', '/announcements/{id}', [\app\admin\controller\NoticeAnnouncementController::class, 'deleteAnnouncement']],
            
            // 日志管理 (使用SystemConfigController)
            ['GET', '/logs', [\app\admin\controller\SystemConfigController::class, 'operationLogs']],
            ['GET', '/logs/admin', [\app\admin\controller\SystemConfigController::class, 'adminLogs']],
            ['GET', '/logs/operation', [\app\admin\controller\SystemConfigController::class, 'operationLogs']],
            ['GET', '/logs/exception', [\app\admin\controller\SystemConfigController::class, 'exceptionLogs']],
            
            // 知识库管理
            ['GET', '/knowledge', [\app\admin\controller\KnowledgeController::class, 'index']],
            ['POST', '/knowledge', [\app\admin\controller\KnowledgeController::class, 'store']],
            ['PUT', '/knowledge/{id}', [\app\admin\controller\KnowledgeController::class, 'update']],
            ['DELETE', '/knowledge/{id}', [\app\admin\controller\KnowledgeController::class, 'destroy']],
            
            // 社区管理
            ['GET', '/community', [\app\admin\controller\CommunityController::class, 'index']],
            ['GET', '/community/reports', [\app\admin\controller\CommunityController::class, 'reports']],
            ['POST', '/community/reports/{id}/handle', [\app\admin\controller\CommunityController::class, 'handleReport']],
            
            // 一致性检查
            ['GET', '/consistency', [\app\admin\controller\ConsistencyController::class, 'index']],
            ['GET', '/consistency/check', [\app\admin\controller\ConsistencyController::class, 'check']],
            ['GET', '/consistency/reports', [\app\admin\controller\ConsistencyController::class, 'reports']],
            
            // 运营管理
            ['GET', '/operations', [\app\admin\controller\OperationsController::class, 'index']],
            ['GET', '/operations/dau', [\app\admin\controller\OperationsController::class, 'dau']],
            ['GET', '/operations/revenue', [\app\admin\controller\OperationsController::class, 'revenue']],
            
            // 主题管理
            ['GET', '/themes', [\app\admin\controller\ThemeController::class, 'index']],
            ['POST', '/themes/{name}/activate', [\app\admin\controller\ThemeController::class, 'activate']],
            ['POST', '/themes/{name}/configure', [\app\admin\controller\ThemeController::class, 'configure']],
        ],
    ],
    
    // 前台页面路由
    'web' => [
        'prefix' => '',
        'middleware' => ['web'],
        'routes' => [
            // 首页
            ['GET', '/', [\app\frontend\controller\IndexController::class, 'index']],
            
            // 认证页面
            ['GET', '/login', [\app\frontend\controller\AuthController::class, 'loginPage']],
            ['GET', '/register', [\app\frontend\controller\AuthController::class, 'registerPage']],
            ['GET', '/forgot-password', [\app\frontend\controller\AuthController::class, 'forgotPasswordPage']],
            
            // 用户中心
            ['GET', '/user-center', [\app\frontend\controller\UserCenterController::class, 'index']],
            ['GET', '/user-center/profile', [\app\frontend\controller\UserCenterController::class, 'profilePage']],
            ['GET', '/user-center/settings', [\app\frontend\controller\UserCenterController::class, 'settingsPage']],
            
            // 小说页面
            ['GET', '/novels', [\app\frontend\controller\NovelController::class, 'indexPage']],
            ['GET', '/novels/{id}', [\app\frontend\controller\NovelController::class, 'showPage']],
            ['GET', '/novels/{novel_id}/chapters/{chapter_id}', [\app\frontend\controller\NovelController::class, 'chapterPage']],
            
            // 创作页面
            ['GET', '/create', [\app\frontend\controller\NovelCreationController::class, 'createPage']],
            ['GET', '/create/{id}', [\app\frontend\controller\NovelCreationController::class, 'editPage']],
            
            // 排行榜
            ['GET', '/ranking', [\app\frontend\controller\RankingController::class, 'indexPage']],
            
            // 法律页面
            ['GET', '/legal/user-agreement', [\app\frontend\controller\LegalController::class, 'userAgreement']],
            ['GET', '/legal/privacy-policy', [\app\frontend\controller\LegalController::class, 'privacyPolicy']],
            
            // 语言切换
            ['GET', '/language/{lang}', [\app\frontend\controller\LanguageController::class, 'switch']],
            
            // 分享页面
            ['GET', '/share', [\app\frontend\controller\ShareController::class, 'index']],
            ['GET', '/share/{id}', [\app\frontend\controller\ShareController::class, 'show']],
            
            // 模板页面
            ['GET', '/templates', [\app\frontend\controller\TemplateController::class, 'index']],
            
            // 提示词页面
            ['GET', '/prompts', [\app\frontend\controller\PromptController::class, 'index']],
        ],
    ],
];
