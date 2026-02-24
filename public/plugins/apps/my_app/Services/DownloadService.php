<?php

namespace Plugins\Apps\MyApp\Services;

use Plugins\Apps\MyApp\Plugin;
use Core\Services\DownloadService as BaseDownloadService;

class DownloadService extends BaseDownloadService
{
    private $plugin;

    public function __construct()
    {
        parent::__construct();
        $this->plugin = new Plugin('my_app');
    }

    /**
     * 检查书籍下载权限
     */
    public function checkBookDownloadPermissions(int $userId, int $bookId): array
    {
        // 获取书籍信息
        $book = $this->plugin->query('SELECT * FROM ' . $this->plugin->getTableName('books') . ' WHERE id = ?', [$bookId]);
        if (empty($book)) {
            return ['allowed' => false, 'message' => '书籍不存在'];
        }
        
        return $this->checkDownloadPermissions($userId, $bookId, 'book', $book[0]);
    }

    /**
     * 处理书籍下载
     */
    public function processBookDownload(int $userId, int $bookId, string $format): array
    {
        // 获取书籍信息
        $book = $this->plugin->query('SELECT * FROM ' . $this->plugin->getTableName('books') . ' WHERE id = ?', [$bookId]);
        if (empty($book)) {
            return ['success' => false, 'message' => '书籍不存在'];
        }
        
        $config = $this->plugin->getConfig();
        return $this->processDownload($userId, $bookId, 'book', $format, $book[0], $config);
    }

    /**
     * 为整本书生成TXT文件内容
     */
    public function generateTxtForBook(int $bookId): ?string
    {
        // 获取书籍信息
        $book = $this->plugin->query('SELECT title, author, description FROM ' . $this->plugin->getTableName('books') . ' WHERE id = ?', [$bookId]);
        if (empty($book)) {
            return null;
        }
        $book = $book[0];

        // 获取所有章节
        $chapters = $this->plugin->query('SELECT title, content FROM ' . $this->plugin->getTableName('chapters') . ' WHERE book_id = ? ORDER BY chapter_number ASC, id ASC', [$bookId]);
        if (empty($chapters)) {
            return null;
        }

        // 构建TXT内容
        $txtContent = "书名：" . $book['title'] . "\r\n";
        $txtContent .= "作者：" . $book['author'] . "\r\n";
        $txtContent .= "简介：" . $book['description'] . "\r\n";
        $txtContent .= "================================\r\n\r\n";

        foreach ($chapters as $chapter) {
            $txtContent .= "## " . $chapter['title'] . " ##\r\n\r\n";
            $txtContent .= $chapter['content'] . "\r\n\r\n";
        }

        return $txtContent;
    }

    /**
     * 重写数据库操作方法
     */
    private function query($sql, $params = [])
    {
        return $this->plugin->query($sql, $params);
    }

    private function insert($table, $data)
    {
        return $this->plugin->insert($this->plugin->getTableName($table), $data);
    }

    private function update($table, $data, $where)
    {
        return $this->plugin->update($this->plugin->getTableName($table), $data, $where);
    }
}