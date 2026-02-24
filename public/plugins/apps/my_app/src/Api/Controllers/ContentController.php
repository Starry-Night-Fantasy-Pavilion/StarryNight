<?php

namespace plugins\apps\my_app\src\Api\Controllers;

use plugins\apps\my_app\src\Service\ContentService;
use plugins\apps\my_app\src\Core\BookSourceManager;
use Exception;

class ContentController {
    private ContentService $contentService;
    private BookSourceManager $bookSourceManager;

    public function __construct() {
        $this->contentService = new ContentService();
        // This is not ideal, we should use a shared instance or DI container
        $this->bookSourceManager = new BookSourceManager(
            'https://xiu2.github.io/yuedu/shuyuan',
            './book_sources',
            './book_sources.json'
        );
    }

    /**
     * Get chapter content.
     * @param array $params
     * @return array
     */
    public function getContent(array $params): array {
        $sourceId = $params['sourceId'] ?? null;
        $chapterUrl = $params['chapterUrl'] ?? null;

        if (!$sourceId || !$chapterUrl) {
            return [
                'code' => 400,
                'message' => 'Missing required parameters: sourceId and chapterUrl.',
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

            $content = $this->contentService->getContent(urldecode($chapterUrl), $source);
            
            return [
                'code' => 200,
                'message' => 'success',
                'data' => ['content' => $content]
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
