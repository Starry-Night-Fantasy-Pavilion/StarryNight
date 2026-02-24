<?php

namespace Plugins\Apps\MyApp\Models;

use Plugins\Apps\MyApp\Plugin;

class BookModel
{
    private $plugin;
    private $booksTable;
    private $sourcesTable;
    private $chaptersTable;
    private $commentsTable;

    public function __construct()
    {
        $this->plugin = new Plugin('my_app');
        $this->booksTable = $this->plugin->getTableName('books');
        $this->sourcesTable = $this->plugin->getTableName('book_sources');
        $this->chaptersTable = $this->plugin->getTableName('chapters');
        $this->commentsTable = $this->plugin->getTableName('comments');
    }

    public function getBooks()
    {
        return $this->plugin->query("SELECT * FROM {$this->booksTable} ORDER BY id DESC");
    }

    public function getSources()
    {
        return $this->plugin->query("SELECT * FROM {$this->sourcesTable} ORDER BY id DESC");
    }

    public function getSourceById(int $id)
    {
        $sourceData = $this->plugin->query("SELECT * FROM {$this->sourcesTable} WHERE id = :id", [':id' => $id]);
        return $sourceData[0] ?? null;
    }

    public function updateSource(int $id, array $data)
    {
        return $this->plugin->update($this->sourcesTable, $data, ['id' => $id]);
    }

    public function createSource(array $data)
    {
        return $this->plugin->insert($this->sourcesTable, $data);
    }

    public function getChaptersByBookId(int $bookId)
    {
        return $this->plugin->query("SELECT id FROM {$this->chaptersTable} WHERE book_id = :book_id", [':book_id' => $bookId]);
    }

    public function getComments()
    {
        return $this->plugin->query("SELECT c.*, b.title as book_title, u.username FROM {$this->commentsTable} c LEFT JOIN {$this->booksTable} b ON c.book_id = b.id LEFT JOIN users u ON c.user_id = u.id ORDER BY c.id DESC");
    }

    public function getRankingByViews(int $limit = 20)
    {
        return $this->plugin->query("SELECT id, title, author, views FROM {$this->booksTable} ORDER BY views DESC LIMIT :limit", [':limit' => $limit]);
    }
}
