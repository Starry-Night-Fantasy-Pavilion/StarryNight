<?php
namespace Plugins\Apps\My_app;

use app\services\Router;
use plugins\apps\my_app\Controllers\AdminController;
use plugins\apps\my_app\Controllers\FrontendController;
use plugins\apps\my_app\src\Api\Controllers\BookSourceController;
use plugins\apps\my_app\src\Api\Controllers\SearchController;
use plugins\apps\my_app\src\Api\Controllers\BookInfoController;
use plugins\apps\my_app\src\Api\Controllers\ChapterController;
use plugins\apps\my_app\src\Api\Controllers\ContentController;

// 防止类重复声明
if (class_exists(__NAMESPACE__ . '\Plugin')) {
    return;
}

class Plugin
{
    public function __construct()
    {
        // Register Composer autoloader
        if (file_exists(__DIR__ . '/vendor/autoload.php')) {
            require_once __DIR__ . '/vendor/autoload.php';
        }
    }
    
    public function registerRoutes(Router $router)
    {
        $adminPrefix = get_env('ADMIN_PATH', 'admin');

        $router->get('/bookstore', [FrontendController::class, 'index']);
        $router->get('/bookstore/book/{id}', [FrontendController::class, 'bookDetail']);
        $router->get('/bookstore/read/{book_id}/{chapter_id}', [FrontendController::class, 'readChapter']);
        $router->get('/bookstore/library', [FrontendController::class, 'userLibrary']);
        
        // API路由
        $router->post('/bookstore/api/add-bookmark', [FrontendController::class, 'addBookmark']);
        $router->post('/bookstore/api/delete-bookmark', [FrontendController::class, 'deleteBookmark']);
        $router->post('/bookstore/api/update-progress', [FrontendController::class, 'updateReadingProgress']);

        $router->group($adminPrefix, function (Router $router) {
            $router->get('/my_app/books', [AdminController::class, 'books']);
            $router->get('/my_app/sources', [AdminController::class, 'sources']);
            $router->get('/my_app/edit_source', [AdminController::class, 'edit_source']);
            $router->get('/my_app/edit_source/{id}', [AdminController::class, 'edit_source']);
            $router->post('/my_app/edit_source/{id}', [AdminController::class, 'edit_source']);
            $router->post('/my_app/edit_source', [AdminController::class, 'edit_source']);
            $router->get('/my_app/delete_source/{id}', [AdminController::class, 'delete_source']);
            $router->get('/my_app/comments', [AdminController::class, 'comments']);
            $router->get('/my_app/rankings', [AdminController::class, 'rankings']);
            $router->get('/my_app/settings', [AdminController::class, 'settings']);
            $router->post('/my_app/settings', [AdminController::class, 'settings']);
            $router->post('/my_app/sync-sources', [AdminController::class, 'sync_sources']);
        });

        // Book Source Manager API Routes
        $apiPrefix = '/api/book-source-manager';
        $router->group($apiPrefix, function (Router $router) {
            // Book Source related APIs
            $router->get('/sources', [BookSourceController::class, 'getAllSources']);
            $router->get('/sources/{id}', [BookSourceController::class, 'getSourceById']);
            $router->get('/sources/search/{keyword}', [BookSourceController::class, 'searchSources']);
            $router->post('/sources/check/{id}', [BookSourceController::class, 'checkSource']);
            
            // Book search API
            $router->get('/search/{sourceId}/{keyword}', [SearchController::class, 'searchBook']);
            
            // Book info API
            $router->get('/book/info/{sourceId}/{bookUrl}', [BookInfoController::class, 'getBookInfo']);
            
            // Table of Contents API
            $router->get('/book/chapters/{sourceId}/{tocUrl}', [ChapterController::class, 'getChapterList']);
            
            // Content API
            $router->get('/book/content/{sourceId}/{chapterUrl}', [ContentController::class, 'getContent']);
        });
    }
}

