<?php

namespace plugins\apps\my_app\src\Api\Controllers;

use plugins\apps\my_app\src\Service\BookInfoService;
use plugins\apps\my_app\src\Core\BookSourceManager;
use Exception;

class BookInfoController {
    private BookInfoService $bookInfoService;
    private BookSourceManager $bookSourceManager;

    public function __construct() {
        $this->bookInfoService = new BookInfoService();
        // This is not ideal, we should use a shared instance or DI container
        $this->bookSourceManager = new BookSourceManager(
            'https://xiu2.github.io/yuedu/shuyuan',
            './book_sources',
            './book_sources.json'
        );
    }

    /**
     * Get book information.
     * @param array $params
     * @return array
     */
    public function getBookInfo(array $params): array {
        $sourceId = $params['sourceId'] ?? null;
        $bookUrl = $params['bookUrl'] ?? null;

        if (!$sourceId || !$bookUrl) {
            return [
                'code' => 400,
                'message' => 'Missing required parameters: sourceId and bookUrl.',
                'data' => []
            ];
        }

        try {
            $source = $this->bookSourceManager->getSourceByUrl($sourceId);
            if (!$source) {
                return [
                    'code' => 404,
                    'message' => 'Book source not found.',
                    'data' => []
                ];
            }

            $bookInfo = $this->bookInfoService->getBookInfo(urldecode($bookUrl), $source);
            
            return [
                'code' => 200,
                'message' => 'success',
                'data' => $bookInfo
            ];
        } catch (Exception $e) {
            return [
                'code' => 500,
                'message' => $e->getMessage(),
                'data' => []
            ];
        }
    }
}
