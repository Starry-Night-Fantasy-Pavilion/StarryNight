<?php

namespace BookSourceManager\Service;

use BookSourceManager\Service\HttpService;
use Exception;
use Symfony\Component\DomCrawler\Crawler;

class ContentService
{
    private HttpService $httpService;

    public function __construct()
    {
        $this->httpService = new HttpService();
    }

    /**
     * Get chapter content.
     * @param string $chapterUrl
     * @param array $source
     * @return string
     * @throws Exception
     */
    public function getContent(string $chapterUrl, array $source): string
    {
        $ruleContent = $source['ruleContent'] ?? null;
        if (!$ruleContent || empty($ruleContent['content'])) {
            throw new Exception("Content rule 'content' is not defined for this source.");
        }

        $headers = json_decode($source['header'] ?? '', true) ?? [];
        $htmlContent = $this->httpService->get($chapterUrl, $headers);

        $crawler = new Crawler($htmlContent, $chapterUrl);

        $contentSelector = $this->getSelectorFromRule($ruleContent['content']);
        
        $contentNode = $crawler->filter($contentSelector)->first();

        if ($contentNode->count() === 0) {
            return ''; // Or throw an exception if content is mandatory
        }

        // Get the inner HTML of the content node
        $content = $contentNode->html();

        // Basic content cleanup
        $content = $this->cleanupContent($content);

        return $content;
    }

    /**
     * Clean up the extracted content.
     * @param string $html
     * @return string
     */
    private function cleanupContent(string $html): string
    {
        // Remove script and style tags
        $html = preg_replace('/<script[^>]*>(.*?)</script>/is', '', $html);
        $html = preg_replace('/<style[^>]*>(.*?)</style>/is', '', $html);

        // Optional: Remove comments
        $html = preg_replace('/<!--(.|\s)*?-->/', '', $html);

        // Optional: Remove specific ad elements by class or id
        // $html = preg_replace('/<div class="ads">(.|\s)*?</div>/', '', $html);

        // Convert non-breaking spaces to regular spaces
        $html = str_replace('&nbsp;', ' ', $html);
        
        // Trim whitespace from the beginning and end
        $html = trim($html);

        return $html;
    }

    private function getSelectorFromRule(string $rule): string
    {
        $rule = str_starts_with($rule, '@CSS:') ? substr($rule, 5) : $rule;
        if (str_contains($rule, '@')) {
            return explode('@', $rule, 2)[0];
        }
        return $rule;
    }
}
