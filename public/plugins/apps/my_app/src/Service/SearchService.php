<?php

namespace BookSourceManager\Service;

use BookSourceManager\Core\BookSourceManager;
use BookSourceManager\Parser\AnalyzeRule;
use BookSourceManager\Service\HttpService;
use Exception;
use Symfony\Component\DomCrawler\Crawler;

class SearchService {
    private BookSourceManager $bookSourceManager;
    private HttpService $httpService;
    
    public function __construct() {
        // This should be improved with dependency injection and a config service.
        $this->bookSourceManager = new BookSourceManager(
            'https://xiu2.github.io/yuedu/shuyuan', // This URL is from the doc, might need to be configurable
            './book_sources',
            './book_sources.json'
        );
        $this->httpService = new HttpService();
    }
    
    /**
     * Search for books.
     * @param string $sourceId The URL of the book source.
     * @param string $keyword
     * @param int $page
     * @return array
     * @throws Exception
     */
    public function search(string $sourceId, string $keyword, int $page = 1): array {
        // Get source
        $source = $this->bookSourceManager->getSourceByUrl($sourceId); // Assuming ID is URL
        if (!$source) {
            throw new Exception("Book source not found");
        }
        
        if (empty($source['searchUrl'])) {
            throw new Exception("This source does not support searching.");
        }

        // Build search URL
        $searchUrl = str_replace(['{key}', '{page}'], [urlencode($keyword), $page], $source['searchUrl']);
        
        // Get web page content
        $headers = [];
        if (!empty($source['header'])) {
            $headers = json_decode($source['header'], true) ?? [];
        }
        $body = $this->httpService->get($searchUrl, $headers);
        
        // Execute parsing
        $bookList = $this->analyzeBookList($body, $source, $searchUrl);
        
        return $bookList;
    }
    
    /**
     * Parse the book list from the search result page.
     * @param string $htmlContent
     * @param array $source
     * @param string $baseUrl
     * @return array
     * @throws Exception
     */
    private function analyzeBookList(string $htmlContent, array $source, string $baseUrl): array {
        $ruleSearch = $source['ruleSearch'] ?? null;
        if (!$ruleSearch || empty($ruleSearch['bookList'])) {
            throw new Exception("Search rule 'bookList' is not defined for this source.");
        }

        $crawler = new Crawler($htmlContent, $baseUrl);
        
        $listSelector = $this->getSelectorFromRule($ruleSearch['bookList']);

        $bookNodes = $crawler->filter($listSelector);

        $books = [];
        $bookNodes->each(function (Crawler $node, $i) use (&$books, $ruleSearch, $baseUrl) {
            $item = [];
            
            if (isset($ruleSearch['name'])) {
                $selector = $this->getSelectorFromRule($ruleSearch['name']);
                $item['name'] = $node->filter($selector)->text('');
            }

            if (isset($ruleSearch['author'])) {
                $selector = $this->getSelectorFromRule($ruleSearch['author']);
                $item['author'] = $node->filter($selector)->text('');
            }

            if (isset($ruleSearch['bookUrl'])) {
                $selector = $this->getSelectorFromRule($ruleSearch['bookUrl']);
                $attribute = $this->getAttributeFromRule($ruleSearch['bookUrl']) ?? 'href';
                $url = $node->filter($selector)->attr($attribute);
                $item['bookUrl'] = $this->resolveUrl($url, $baseUrl);
            }

            $books[] = $item;
        });

        return $books;
    }

    private function getSelectorFromRule(string $rule): string {
        $rule = str_starts_with($rule, '@CSS:') ? substr($rule, 5) : $rule;
        if (str_contains($rule, '@')) {
            return explode('@', $rule, 2)[0];
        }
        return $rule;
    }

    private function getAttributeFromRule(string $rule): ?string {
        if (str_contains($rule, '@')) {
            $parts = explode('@', $rule, 2);
            return $parts[1] === 'text' ? null : $parts[1];
        }
        return null;
    }

    private function resolveUrl(?string $url, string $baseUrl): ?string
    {
        if (empty($url) || empty($baseUrl) || preg_match('/^(https?|ftp):/i', $url)) {
            return $url;
        }
        
        $base = parse_url($baseUrl);
        if (!$base) return $url;
        
        $scheme = $base['scheme'] ?? 'http';
        $host = $base['host'] ?? '';
        $port = isset($base['port']) ? ':' . $base['port'] : '';
        
        if (str_starts_with($url, '//')) {
            return $scheme . ':' . $url;
        }
        if (str_starts_with($url, '/')) {
            return $scheme . '://' . $host . $port . $url;
        }
        
        $path = $base['path'] ?? '/';
        $path = dirname($path);

        // Using DIRECTORY_SEPARATOR is more robust and avoids escaping issues.
        if ($path === '.' || $path === DIRECTORY_SEPARATOR) {
            $path = '/';
        }

        if (!str_ends_with($path, '/')) {
            $path .= '/';
        }

        return $scheme . '://' . $host . $port . $path . $url;
    }
}
