<?php

namespace plugins\apps\my_app\src\Core;

use plugins\apps\my_app\src\Core\BookSource;
use plugins\apps\my_app\src\Service\HttpService;
use Exception;

class BookSourceManager {
    private string $sourceUrl;
    private string $saveDir;
    private string $outputFile;
    private array $sources = [];
    private HttpService $httpService;
    
    public function __construct(string $sourceUrl, string $saveDir, string $outputFile) {
        $this->sourceUrl = $sourceUrl;
        $this->saveDir = $saveDir;
        $this->outputFile = $outputFile;
        $this->httpService = new HttpService();
        
        if (!is_dir($this->saveDir)) {
            mkdir($this->saveDir, 0755, true);
        }
    }
    
    /**
     * 获取书源列表
     * @return array
     */
    public function fetchSourceList(): array {
        try {
            $content = $this->httpService->get($this->sourceUrl);
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Failed to decode source list JSON: ' . json_last_error_msg());
            }

            return $data ?? [];
        } catch (Exception $e) {
            // In a real app, you'd log this error.
            return [];
        }
    }
    
    /**
     * 处理单个书源
     * @param array|string $sourceData
     * @return ?array
     */
    public function processSource(array|string $sourceData): ?array {
        if (is_string($sourceData)) {
            // If it's a string, it might be a URL to a single source file.
            // This logic can be expanded later.
            $data = json_decode($sourceData, true);
        } else {
            $data = $sourceData;
        }

        if (is_array($data)) {
            $bookSource = new BookSource($data);
            return $bookSource->toArray();
        }

        return null;
    }
    
    /**
     * 保存书源数据
     * @param array $structuredData
     */
    public function saveStructuredData(array $structuredData): void {
        $jsonContent = json_encode($structuredData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($this->outputFile, $jsonContent);
    }
    
    /**
     * 检测书源可用性
     * @param BookSource $bookSource
     * @return bool
     */
    public function checkSourceAvailability(BookSource $bookSource): bool {
        try {
            $this->httpService->get($bookSource->getBookSourceUrl(), $bookSource->getHeaderMap());
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 从文件加载所有书源
     * @return array
     */
    public function loadSources(): array
    {
        if (file_exists($this->outputFile)) {
            $jsonContent = file_get_contents($this->outputFile);
            $this->sources = json_decode($jsonContent, true) ?? [];
        }
        return $this->sources;
    }

    /**
     * 根据URL获取单个书源
     * @param string $url
     * @return ?array
     */
    public function getSourceByUrl(string $url): ?array
    {
        if (empty($this->sources)) {
            $this->loadSources();
        }
        foreach ($this->sources as $source) {
            if (isset($source['bookSourceUrl']) && $source['bookSourceUrl'] === $url) {
                return $source;
            }
        }
        return null;
    }
}
