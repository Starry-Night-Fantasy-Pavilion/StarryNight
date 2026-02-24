<?php

namespace BookSourceManager\Core;

class BookSource {
    private string $bookSourceName;           // 名称
    private ?string $bookSourceGroup;         // 分组
    private string $bookSourceUrl;            // 地址，包括 http/https
    private int $bookSourceType = 0;          // 类型，0 文本，1 音频
    private ?string $bookUrlPattern;          // 详情页url正则
    private int $customOrder = 0;             // 手动排序编号
    private bool $enabled = true;             // 是否启用
    private bool $enabledExplore = true;      // 启用发现
    private ?string $header;                  // 请求头
    private ?string $loginUrl;                // 登录地址
    private int $lastUpdateTime = 0;          // 最后更新时间
    private int $weight = 0;                  // 权重
    private ?string $exploreUrl;              // 发现url
    private ?array $ruleExplore;              // 发现规则
    private ?string $searchUrl;               // 搜索url
    private ?array $ruleSearch;               // 搜索规则
    private ?array $ruleBookInfo;             // 书籍信息页规则
    private ?array $ruleToc;                  // 目录页规则
    private ?array $ruleContent;              // 正文页规则

    public function __construct(array $data = [])
    {
        $this->bookSourceName = $data['bookSourceName'] ?? '';
        $this->bookSourceGroup = $data['bookSourceGroup'] ?? null;
        $this->bookSourceUrl = $data['bookSourceUrl'] ?? '';
        $this->bookSourceType = $data['bookSourceType'] ?? 0;
        $this->bookUrlPattern = $data['bookUrlPattern'] ?? null;
        $this->customOrder = $data['customOrder'] ?? 0;
        $this->enabled = $data['enabled'] ?? true;
        $this->enabledExplore = $data['enabledExplore'] ?? true;
        $this->header = $data['header'] ?? null;
        $this->loginUrl = $data['loginUrl'] ?? null;
        $this->lastUpdateTime = $data['lastUpdateTime'] ?? 0;
        $this->weight = $data['weight'] ?? 0;
        $this->exploreUrl = $data['exploreUrl'] ?? null;
        $this->ruleExplore = $data['ruleExplore'] ?? null;
        $this->searchUrl = $data['searchUrl'] ?? null;
        $this->ruleSearch = $data['ruleSearch'] ?? null;
        $this->ruleBookInfo = $data['ruleBookInfo'] ?? null;
        $this->ruleToc = $data['ruleToc'] ?? null;
        $this->ruleContent = $data['ruleContent'] ?? null;
    }

    public function getBookSourceName(): string
    {
        return $this->bookSourceName;
    }

    public function getBookSourceUrl(): string
    {
        return $this->bookSourceUrl;
    }

    public function getSearchUrl(): ?string
    {
        return $this->searchUrl;
    }

    public function getRuleSearch(): ?array
    {
        return $this->ruleSearch;
    }
    
    public function getHeader(): ?string
    {
        return $this->header;
    }

    /**
     * 获取请求头映射
     * @return array
     */
    public function getHeaderMap(): array {
        if (empty($this->header)) {
            return [];
        }
        
        $headers = json_decode($this->header, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $headers;
        }
        
        $headerMap = [];
        $lines = explode("
", $this->header);
        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $headerMap[trim($key)] = trim($value);
            }
        }
        return $headerMap;
    }

    /**
     * 执行JavaScript代码
     * @param string $jsStr
     * @return mixed
     * @throws \Exception
     */
    private function evalJS(string $jsStr): mixed {
        throw new \Exception("JavaScript evaluation is not supported yet.");
    }
    
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
