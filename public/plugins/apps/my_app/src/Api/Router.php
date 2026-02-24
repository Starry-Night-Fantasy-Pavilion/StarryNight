<?php

namespace BookSourceManager\Api;

use FastRoute\RouteCollector;

class Router {
    public static function getRoutes(): callable {
        return function (RouteCollector $r) {
            $base = '/api/book-source-manager'; // Prefixing to avoid conflicts

            // Book Source related APIs
            $r->addRoute('GET', $base . '/sources', ['BookSourceManager\Api\Controllers\BookSourceController', 'getAllSources']);
            $r->addRoute('GET', $base . '/sources/{id}', ['BookSourceManager\Api\Controllers\BookSourceController', 'getSourceById']);
            $r->addRoute('GET', $base . '/sources/search/{keyword}', ['BookSourceManager\Api\Controllers\BookSourceController', 'searchSources']);
            $r->addRoute('POST', $base . '/sources/check/{id}', ['BookSourceManager\Api\Controllers\BookSourceController', 'checkSource']);
            
            // Book search API
            $r->addRoute('GET', $base . '/search/{sourceId}/{keyword}', ['BookSourceManager\Api\Controllers\SearchController', 'searchBook']);
            
            // Book info API
            $r->addRoute('GET', $base . '/book/info/{sourceId}/{bookUrl}', ['BookSourceManager\Api\Controllers\BookInfoController', 'getBookInfo']);
            
            // Table of Contents API
            $r->addRoute('GET', $base . '/book/chapters/{sourceId}/{tocUrl}', ['BookSourceManager\Api\Controllers\ChapterController', 'getChapterList']);
            
            // Content API
            $r->addRoute('GET', $base . '/book/content/{sourceId}/{chapterUrl}', ['BookSourceManager\Api\Controllers\ContentController', 'getContent']);
        };
    }
}
