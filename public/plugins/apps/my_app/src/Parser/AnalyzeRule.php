<?php

namespace BookSourceManager\Parser;

use Symfony\Component\DomCrawler\Crawler;
use Exception;
use Softcreatr\JsonPath\JsonPath;

class AnalyzeRule {
    private mixed $content = null;
    private ?string $baseUrl = null;
    private bool $isJSON = false;
    private ?Crawler $crawler = null;

    /**
     * Set the content to be parsed.
     * @param mixed $content
     * @param string|null $baseUrl
     * @return $this
     * @throws Exception
     */
    public function setContent(mixed $content, ?string $baseUrl = null): self
    {
        $this->content = $content;
        $this->baseUrl = $baseUrl;
        
        if (is_string($content)) {
            $decodedJson = json_decode($content);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->isJSON = true;
                $this->content = $decodedJson; // Use the decoded object/array
            } else {
                $this->isJSON = false;
                $this->crawler = new Crawler($content, $baseUrl);
            }
        } elseif (is_array($content) || is_object($content)) {
            $this->isJSON = true;
        } else {
            throw new Exception("Unsupported content type for parsing.");
        }
        
        return $this;
    }

    /**
     * Get a list of strings based on a rule.
     * @param string $rule
     * @return ?array
     * @throws Exception
     */
    public function getStringList(string $rule): ?array
    {
        // This is a simplified implementation for now.
        // It will be expanded to handle different rule types (@CSS, @XPath, etc.)
        
        if ($this->isJSON) {
            try {
                $jsonPath = new JsonPath($this->content);
                // The rule for JSONPath is the path itself, e.g., '$.books[*].title'
                $result = $jsonPath->find($rule);
                return is_array($result) ? $result : null;
            } catch (Exception $e) {
                throw new Exception("JSONPath processing failed: " . $e->getMessage());
            }
        }

        if ($this->crawler) {
            // For now, we assume the rule is a CSS selector.
            // Example rule: ".book-name" or ".book-link@href"
            $attribute = null;
            if (str_contains($rule, '@')) {
                [$selector, $attribute] = explode('@', $rule, 2);
            } else {
                $selector = $rule;
            }

            $nodes = $this->crawler->filter($selector);
            $list = [];
            foreach ($nodes as $node) {
                $crawlerNode = new Crawler($node, $this->baseUrl);
                if ($attribute) {
                    if ($attribute === 'text') {
                        $value = $crawlerNode->text();
                    } elseif ($attribute === 'html') {
                        $value = $crawlerNode->html();
                    } else {
                        $value = $crawlerNode->attr($attribute);
                    }
                } else {
                    $value = $crawlerNode->text();
                }
                $list[] = $this->resolveUrl($value);
            }
            return $list;
        }

        return null;
    }

    /**
     * Get a single string based on a rule.
     * @param string $rule
     * @return string
     * @throws Exception
     */
    public function getString(string $rule): string
    {
        $list = $this->getStringList($rule);
        return (string)($list[0] ?? '');
    }

    /**
     * Resolves a URL against the base URL.
     * @param string $url
     * @return string
     */
    private function resolveUrl(string $url): string
    {
        if (empty($this->baseUrl) || empty($url) || preg_match('/^(https?|ftp):/i', $url)) {
            return $url;
        }
        
        $base = parse_url($this->baseUrl);
        if (!$base) {
            return $url;
        }

        $path = $base['path'] ?? '/';
        $path = preg_replace('//[^/]*$/', '/', $path);

        if (str_starts_with($url, '/')) {
            $path = '';
        }

        $resolved = $base['scheme'] . '://' . $base['host'];
        if (isset($base['port'])) {
            $resolved .= ':' . $base['port'];
        }
        $resolved .= $path . $url;

        // Basic normalization
        $resolved = str_replace('/./', '/', $resolved);
        // This doesn't handle /../ correctly, but it's a start.

        return $resolved;
    }

    /**
     * Execute JavaScript code.
     * @param string $jsStr
     * @param mixed $result
     * @return mixed
     * @throws Exception
     */
    private function evalJS(string $jsStr, mixed $result): mixed
    {
        throw new Exception("JavaScript evaluation is not supported yet.");
    }
}
