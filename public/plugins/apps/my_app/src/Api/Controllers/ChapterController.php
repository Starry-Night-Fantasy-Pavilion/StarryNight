<?php

namespace plugins\apps\my_app\src\Api\Controllers;

use plugins\apps\my_app\src\Service\BookInfoService;
use plugins\apps\my_app\src\Core\BookSourceManager;
use Exception;

class ChapterController {
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
     * Get chapter list.
     * @param array $params
     * @return array
     */
    public function getChapterList(array $params): array {
        $sourceId = $params['sourceId'] ?? null;
        $tocUrl = $params['tocUrl'] ?? null;

        if (!$sourceId || !$tocUrl) {
            return [
                'code' => 400,
                'message' => 'Missing required parameters: sourceId and tocUrl.',
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

            $chapters = $this->bookInfoService->getChapterList(urldecode($tocUrl), $source);
            
            return [
                'code' => 200,
                'message' => 'success',
                'data' => $chapters
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
