<?php

namespace plugins\apps\my_app\src\Api\Controllers;

use BookSourceManager\Core\BookSourceManager;
use Exception;

class BookSourceController {
    private BookSourceManager $bookSourceManager;

    public function __construct() {
        // This should be improved with dependency injection and a config service.
        $this->bookSourceManager = new BookSourceManager(
            'https://xiu2.github.io/yuedu/shuyuan', // This URL is from the doc, might need to be configurable
            './book_sources',
            './book_sources.json'
        );
        $this->bookSourceManager->loadSources(); // Load sources on init
    }

    /**
     * Get all book sources.
     * @return array
     */
    public function getAllSources(): array {
        try {
            $sources = $this->bookSourceManager->loadSources();
            return [
                'code' => 200,
                'message' => 'success',
                'data' => $sources
            ];
        } catch (Exception $e) {
            return [
                'code' => 500,
                'message' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Get a single book source by its URL (acting as ID).
     * @param array $params
     * @return array
     */
    public function getSourceById(array $params): array {
        $sourceId = $params['id'] ?? null;
        if (!$sourceId) {
            return [
                'code' => 400,
                'message' => 'Missing required parameter: id.',
                'data' => []
            ];
        }

        try {
            // The router uses {id}, but our manager uses URL. We'll assume id is the URL for now.
            $source = $this->bookSourceManager->getSourceByUrl($sourceId);
            if ($source) {
                return [
                    'code' => 200,
                    'message' => 'success',
                    'data' => $source
                ];
            } else {
                return [
                    'code' => 404,
                    'message' => 'Book source not found.',
                    'data' => []
                ];
            }
        } catch (Exception $e) {
            return [
                'code' => 500,
                'message' => $e->getMessage(),
                'data' => []
            ];
        }
    }
    
    /**
     * Search for book sources by keyword.
     * @param array $params
     * @return array
     */
    public function searchSources(array $params): array {
        $keyword = $params['keyword'] ?? null;
        if (!$keyword) {
            return [
                'code' => 400,
                'message' => 'Missing required parameter: keyword.',
                'data' => []
            ];
        }

        try {
            $allSources = $this->bookSourceManager->loadSources();
            $filteredSources = array_filter($allSources, function($source) use ($keyword) {
                $nameMatch = isset($source['bookSourceName']) && stripos($source['bookSourceName'], $keyword) !== false;
                $groupMatch = isset($source['bookSourceGroup']) && stripos($source['bookSourceGroup'], $keyword) !== false;
                return $nameMatch || $groupMatch;
            });

            return [
                'code' => 200,
                'message' => 'success',
                'data' => array_values($filteredSources) // Re-index array
            ];
        } catch (Exception $e) {
            return [
                'code' => 500,
                'message' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Check the availability of a book source.
     * @param array $params
     * @return array
     */
    public function checkSource(array $params): array {
        $sourceId = $params['id'] ?? null;
        if (!$sourceId) {
            return [
                'code' => 400,
                'message' => 'Missing required parameter: id.',
                'data' => []
            ];
        }

        try {
            $sourceData = $this->bookSourceManager->getSourceByUrl($sourceId);
            if (!$sourceData) {
                return [
                    'code' => 404,
                    'message' => 'Book source not found.',
                    'data' => []
                ];
            }
            
            $bookSource = new \BookSourceManager\Core\BookSource($sourceData);
            $isAvailable = $this->bookSourceManager->checkSourceAvailability($bookSource);

            return [
                'code' => 200,
                'message' => 'success',
                'data' => ['available' => $isAvailable]
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
