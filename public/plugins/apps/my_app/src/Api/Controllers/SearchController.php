<?php

namespace plugins\apps\my_app\src\Api\Controllers;

use BookSourceManager\Service\SearchService;
use Exception;

class SearchController {
    private SearchService $searchService;
    
    public function __construct() {
        $this->searchService = new SearchService();
    }
    
    /**
     * Search for a book.
     * @param array $params Route parameters, e.g., ['sourceId' => '...', 'keyword' => '...']
     * @return array
     */
    public function searchBook(array $params): array {
        $sourceId = $params['sourceId'] ?? null;
        $keyword = $params['keyword'] ?? null;
        $page = $_GET['page'] ?? 1; // Allow pagination via query string
        
        if (!$sourceId || !$keyword) {
            return [
                'code' => 400,
                'message' => 'Missing required parameters: sourceId and keyword.',
                'data' => []
            ];
        }

        try {
            $results = $this->searchService->search($sourceId, $keyword, (int)$page);
            return [
                'code' => 200,
                'message' => 'success',
                'data' => $results
            ];
        } catch (Exception $e) {
            // In a production environment, you might want to log the error message
            // and return a more generic error to the user.
            return [
                'code' => 500,
                'message' => $e->getMessage(),
                'data' => []
            ];
        }
    }
}
