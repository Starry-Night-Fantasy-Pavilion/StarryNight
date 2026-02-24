<?php

namespace plugins\apps\my_app\src\Service;

use plugins\apps\my_app\src\Service\HttpService;
use Exception;
use Symfony\Component\DomCrawler\Crawler;

class BookInfoService
{
    private HttpService $httpService;

    public function __construct()
    {
        $this->httpService = new HttpService();
    }

    /**
     * Get book details.
     * @param string $bookUrl
     * @param array $source
     * @return array
     * @throws Exception
     */
    public function getBookInfo(string $bookUrl, array $source): array
    {
        $ruleBookInfo = $source['ruleBookInfo'] ?? null;
        if (!$ruleBookInfo) {
            throw new Exception("Book info rule 'ruleBookInfo' is not defined for this source.");
        }

        $headers = json_decode($source['header'] ?? '', true) ?? [];
        $htmlContent = $this->httpService->get($bookUrl, $headers);

        $crawler = new Crawler($htmlContent, $bookUrl);

        $info = [];
        foreach ($ruleBookInfo as $key => $rule) {
            $selector = $this->getSelectorFromRule($rule);
            $attribute = $this->getAttributeFromRule($rule);
            
            $node = $crawler->filter($selector)->first();
            if ($node->count() > 0) {
                if ($attribute) {
                    $value = $node->attr($attribute);
                } else {
                    // Special handling for 'intro' to get full HTML content
                    if ($key === 'intro') {
                        $value = $node->html();
                    } else {
                        $value = $node->text();
                    }
                }
                $info[$key] = trim($value);
            }
        }
        
        // Resolve relative URLs for cover image and tocUrl
        if (!empty($info['cover_image'])) {
            $info['cover_image'] = $this->resolveUrl($info['cover_image'], $bookUrl);
        }
        if (!empty($info['tocUrl'])) {
            $info['tocUrl'] = $this->resolveUrl($info['tocUrl'], $bookUrl);
        }

        return $info;
    }

    /**
     * Get chapter list.
     * @param string $tocUrl
     * @param array $source
     * @return array
     * @throws Exception
     */
    public function getChapterList(string $tocUrl, array $source): array
    {
        $ruleToc = $source['ruleToc'] ?? null;
        if (!$ruleToc || empty($ruleToc['chapterList'])) {
            throw new Exception("Table of Contents rule 'chapterList' is not defined for this source.");
        }

        $headers = json_decode($source['header'] ?? '', true) ?? [];
        $htmlContent = $this->httpService->get($tocUrl, $headers);

        $crawler = new Crawler($htmlContent, $tocUrl);

        $listSelector = $this->getSelectorFromRule($ruleToc['chapterList']);
        $chapterNodes = $crawler->filter($listSelector);

        $chapters = [];
        $chapterNodes->each(function (Crawler $node, $i) use (&$chapters, $ruleToc, $tocUrl) {
            $item = [];

            if (isset($ruleToc['chapterName'])) {
                $selector = $this->getSelectorFromRule($ruleToc['chapterName']);
                $item['name'] = $node->filter($selector)->text('');
            }

            if (isset($ruleToc['chapterUrl'])) {
                $selector = $this->getSelectorFromRule($ruleToc['chapterUrl']);
                $attribute = $this->getAttributeFromRule($ruleToc['chapterUrl']) ?? 'href';
                $url = $node->filter($selector)->attr($attribute);
                $item['url'] = $this->resolveUrl($url, $tocUrl);
            }
            
            $item['number'] = $i + 1;
            $chapters[] = $item;
        });

        return $chapters;
    }

    private function getSelectorFromRule(string $rule): string
    {
        $rule = str_starts_with($rule, '@CSS:') ? substr($rule, 5) : $rule;
        if (str_contains($rule, '@')) {
            return explode('@', $rule, 2)[0];
        }
        return $rule;
    }

    private function getAttributeFromRule(string $rule): ?string
    {
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
        if ($path === '.' || $path === DIRECTORY_SEPARATOR) {
            $path = '/';
        }
        if (!str_ends_with($path, '/')) {
            $path .= '/';
        }

        return $scheme . '://' . $host . $port . $path . $url;
    }
}
