<?php

namespace Plugins\Apps\MyApp\Services;

use Plugins\Apps\MyApp\Plugin;

class BookSourceService
{
    private $plugin;

    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * 同步指定书源
     * @param int $sourceId
     * @return array ['success' => bool, 'message' => string, 'added' => int, 'updated' => int]
     */
    public function sync(int $sourceId): array
    {
        $source = $this->plugin->query("SELECT * FROM " . $this->plugin->getTableName('book_sources') . " WHERE id = :id", [':id' => $sourceId]);
        if (empty($source)) {
            return ['success' => false, 'message' => '书源不存在。', 'added' => 0, 'updated' => 0];
        }
        $source = $source[0];

        $rules = json_decode($source['list_rule'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'message' => '书源列表规则解析失败，不是有效的JSON。', 'added' => 0, 'updated' => 0];
        }

        // 简单的原生实现，后续可以替换为 Guzzle 等库
        $context = stream_context_create(['http' => ['timeout' => 10]]); // 设置10秒超时
        $html = file_get_contents($source['base_url'], false, $context);
        if ($html === false) {
            return ['success' => false, 'message' => '无法获取书源内容，请检查URL或网络连接。', 'added' => 0, 'updated' => 0];
        }

        $parsedBooks = [];
        $addedCount = 0;
        $updatedCount = 0;

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true); // 抑制HTML5标签的警告
        if (!$dom->loadHTML($html)) {
            libxml_clear_errors();
            return ['success' => false, 'message' => '无法解析HTML内容。', 'added' => 0, 'updated' => 0];
        }
        libxml_clear_errors();
        
        $xpath = new \DOMXPath($dom);

        // 注意：DOMXPath 使用 XPath 语法，而不是 CSS 选择器。这里需要一个转换器或手动编写XPath。
        // 为简化起见，我们假设规则中直接提供了XPath。
        // 例如: {"item": "//div[@class='book-item']", "title": ".//h3/a", "author": ".//p[@class='author']", "url": ".//h3/a/@href"}
        
        $bookNodes = $xpath->query($rules['item']);

        if ($bookNodes === false || $bookNodes->length === 0) {
            return ['success' => false, 'message' => '根据规则未能找到任何书籍条目。请检查列表规则（item）。', 'added' => 0, 'updated' => 0];
        }

        foreach ($bookNodes as $node) {
            $titleNode = $xpath->query($rules['title'], $node)->item(0);
            $authorNode = $xpath->query($rules['author'], $node)->item(0);
            $urlNode = $xpath->query($rules['url'], $node)->item(0);

            if ($titleNode && $authorNode && $urlNode) {
                $url = $urlNode->nodeValue;
                // 处理相对URL
                if (!preg_match('/^https?:\/\//', $url)) {
                    $url = rtrim($source['base_url'], '/') . '/' . ltrim($url, '/');
                }

                $bookData = [
                    'title' => trim($titleNode->nodeValue),
                    'author' => trim($authorNode->nodeValue),
                    'source_url' => $url,
                    'source_id' => $sourceId
                ];

                // 检查书籍是否已存在
                $existingBook = $this->plugin->query("SELECT id FROM " . $this->plugin->getTableName('books') . " WHERE title = :title AND author = :author", [
                    ':title' => $bookData['title'],
                    ':author' => $bookData['author']
                ]);

                if (empty($existingBook)) {
                    // 添加新书
                    $bookId = $this->plugin->insert('books', [
                        'title' => $bookData['title'],
                        'author' => $bookData['author'],
                        'source_id' => $bookData['source_id'],
                        'source_url' => $bookData['source_url']
                    ]);
                    if ($bookId) {
                        // 插入成功后，立即同步详情
                        $this->syncBookDetails((int)$bookId);
                        $addedCount++;
                    }
                } else {
                    // 更新书籍信息（如果需要）
                    $updatedCount++;
                }
            }
        }

        // 更新同步时间
        $this->plugin->update('book_sources', ['last_sync_at' => date('Y-m-d H:i:s')], ['id' => $sourceId]);

        return [
            'success' => true,
            'message' => "同步成功！新增 {$addedCount} 本，更新 {$updatedCount} 本。",
            'added' => $addedCount,
            'updated' => $updatedCount
        ];
    }

    /**
     * 同步单本书籍的详细信息
     * @param int $bookId
     * @return bool
     */
    public function syncBookDetails(int $bookId): bool
    {
        $book = $this->plugin->query("SELECT * FROM " . $this->plugin->getTableName('books') . " WHERE id = :id", [':id' => $bookId]);
        if (empty($book) || empty($book[0]['source_id']) || empty($book[0]['source_url'])) {
            return false;
        }
        $book = $book[0];

        $source = $this->plugin->query("SELECT * FROM " . $this->plugin->getTableName('book_sources') . " WHERE id = :id", [':id' => $book['source_id']]);
        if (empty($source) || empty($source[0]['detail_rule'])) {
            return false;
        }
        $source = $source[0];

        $rules = json_decode($source['detail_rule'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false; // 规则解析失败
        }

        $context = stream_context_create(['http' => ['timeout' => 10]]);
        $html = file_get_contents($book['source_url'], false, $context);
        if ($html === false) {
            return false; // 获取详情页失败
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        if (!$dom->loadHTML($html)) {
            libxml_clear_errors();
            return false;
        }
        libxml_clear_errors();
        
        $xpath = new \DOMXPath($dom);
        
        $updateData = [];

        // 根据规则解析各个字段
        foreach ($rules as $field => $rule) {
            $node = $xpath->query($rule)->item(0);
            if ($node instanceof \DOMElement) { // 检查节点类型
                // 特殊处理img的src和a的href
                if (($field === 'cover_image' || $field === 'source_url') && $node->hasAttribute('src')) {
                    $value = $node->getAttribute('src');
                } else {
                    $value = trim($node->nodeValue);
                }
                
                // 处理相对URL
                if (($field === 'cover_image') && !preg_match('/^https?:\/\//', $value)) {
                     $value = rtrim($source['base_url'], '/') . '/' . ltrim($value, '/');
                }

                if (!empty($value)) {
                    $updateData[$field] = $value;
                }
            }
        }

        if (!empty($updateData)) {
            $this->plugin->update('books', $updateData, ['id' => $bookId]);
        }

        return true;
    }

    /**
     * 同步书籍的章节列表
     * @param int $bookId
     * @return bool
     */
    public function syncChapterList(int $bookId): bool
    {
        $book = $this->plugin->query("SELECT * FROM " . $this->plugin->getTableName('books') . " WHERE id = :id", [':id' => $bookId]);
        if (empty($book) || empty($book[0]['source_id']) || empty($book[0]['source_url'])) {
            return false;
        }
        $book = $book[0];

        $source = $this->plugin->query("SELECT * FROM " . $this->plugin->getTableName('book_sources') . " WHERE id = :id", [':id' => $book['source_id']]);
        if (empty($source) || empty($source[0]['detail_rule'])) { // 复用detail_rule
            return false;
        }
        $source = $source[0];

        $rules = json_decode($source['detail_rule'], true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($rules['chapters_item']) || empty($rules['chapter_title']) || empty($rules['chapter_url'])) {
            return false; // 规则不完整
        }

        $context = stream_context_create(['http' => ['timeout' => 10]]);
        $html = file_get_contents($book['source_url'], false, $context);
        if ($html === false) {
            return false;
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);

        $chapterNodes = $xpath->query($rules['chapters_item']);
        $chapterNumber = 1;

        foreach ($chapterNodes as $node) {
            $titleNode = $xpath->query($rules['chapter_title'], $node)->item(0);
            $urlNode = $xpath->query($rules['chapter_url'], $node)->item(0);

            if ($titleNode && $urlNode) {
                $chapterUrl = $urlNode->nodeValue;
                if (!preg_match('/^https?:\/\//', $chapterUrl)) {
                    $chapterUrl = rtrim($source['base_url'], '/') . '/' . ltrim($chapterUrl, '/');
                }
                $chapterTitle = trim($titleNode->nodeValue);

                // 检查章节是否已存在
                // 使用 book_id 和 chapter_number 作为联合唯一键来检查，更可靠
                $existingChapter = $this->plugin->query("SELECT id FROM " . $this->plugin->getTableName('chapters') . " WHERE book_id = :book_id AND chapter_number = :chapter_number", [
                    ':book_id' => $bookId,
                    ':chapter_number' => $chapterNumber
                ]);

                if (empty($existingChapter)) {
                    $chapterId = $this->plugin->insert('chapters', [
                        'book_id' => $bookId,
                        'chapter_number' => $chapterNumber,
                        'title' => $chapterTitle,
                        'source_url' => $chapterUrl,
                    ]);
                    if ($chapterId) {
                        $this->syncChapterContent((int)$chapterId);
                    }
                }
                $chapterNumber++;
            }
        }
        return true;
    }

    /**
     * 同步单章节的内容
     * @param int $chapterId
     * @return bool
     */
    public function syncChapterContent(int $chapterId): bool
    {
        $chapter = $this->plugin->query("SELECT * FROM " . $this->plugin->getTableName('chapters') . " WHERE id = :id", [':id' => $chapterId]);
        if (empty($chapter) || empty($chapter[0]['source_url'])) {
            return false;
        }
        $chapter = $chapter[0];

        $book = $this->plugin->query("SELECT source_id FROM " . $this->plugin->getTableName('books') . " WHERE id = :id", [':id' => $chapter['book_id']]);
        if (empty($book)) {
            return false;
        }
        
        $source = $this->plugin->query("SELECT * FROM " . $this->plugin->getTableName('book_sources') . " WHERE id = :id", [':id' => $book[0]['source_id']]);
        if (empty($source) || empty($source[0]['content_rule'])) {
            return false;
        }
        $source = $source[0];

        $rules = json_decode($source['content_rule'], true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($rules['content'])) {
            return false; // 规则不完整
        }

        $context = stream_context_create(['http' => ['timeout' => 10]]);
        $html = file_get_contents($chapter['source_url'], false, $context);
        if ($html === false) {
            return false;
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);

        $contentNode = $xpath->query($rules['content'])->item(0);

        if ($contentNode) {
            // 提取纯文本，并进行清理
            $content = trim($contentNode->nodeValue);
            // 你可能需要更复杂的清理逻辑来移除广告、脚本等
            $content = preg_replace('/\s+/', ' ', $content); // 压缩空白

            $this->plugin->update('chapters', ['content' => $content], ['id' => $chapterId]);
        }

        return true;
    }
}