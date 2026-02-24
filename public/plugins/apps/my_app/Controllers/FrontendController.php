<?php
namespace plugins\apps\my_app\Controllers;

use PDO;
use plugins\apps\my_app\Services\ReadingProgressService;
use plugins\apps\my_app\Services\BookmarkService;
use plugins\apps\my_app\Services\RecommendationService;

class FrontendController extends BaseController
{
    private $readingProgressService;
    private $bookmarkService;
    private $recommendationService;

    public function __construct()
    {
        parent::__construct();
        $this->readingProgressService = new ReadingProgressService();
        $this->bookmarkService = new BookmarkService();
        $this->recommendationService = new RecommendationService();
    }

    /**
     * 获取当前登录用户ID
     * @return int|null
     */
    private function getCurrentUserId(): ?int
    {
        // 这里应该从会话或认证系统中获取用户ID
        // 暂时返回null，表示未登录
        return $_SESSION['user_id'] ?? null;
    }
    public function index()
    {
        header('Content-Type: text/html; charset=utf-8');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 12;
        $offset = ($page - 1) * $limit;

        $countRow = $this->query('SELECT COUNT(*) as total FROM ' . $this->getTableName('books') . " WHERE status = 'published'");
        $total = (int)($countRow[0]['total'] ?? 0);
        $totalPages = max(1, (int)ceil($total / $limit));

        $stmt = $this->db->prepare('SELECT * FROM ' . $this->getTableName('books') . " WHERE status = 'published' ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $books = $stmt->fetchAll();

        // 获取热门书籍用于推荐
        $popularBooks = $this->recommendationService->getPopularBooks(6);

        // 获取用户推荐（如果用户已登录）
        $recommendations = [];
        $userId = $this->getCurrentUserId();
        if ($userId) {
            $recommendations = $this->recommendationService->generateRecommendations($userId, 6);
        }

        $this->render('frontend/index', [
            'books' => $books,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'popularBooks' => $popularBooks,
            'recommendations' => $recommendations,
        ]);
    }

    public function bookDetail($id)
    {
        header('Content-Type: text/html; charset=utf-8');

        $id = (int)$id;
        $bookRow = $this->query('SELECT * FROM ' . $this->getTableName('books') . ' WHERE id = :id', [':id' => $id]);
        $book = $bookRow[0] ?? null;
        if (!$book) {
            http_response_code(404);
            echo '书籍不存在。';
            return;
        }

        $this->execute('UPDATE ' . $this->getTableName('books') . ' SET views = views + 1 WHERE id = :id', [':id' => $id]);
        $book['views'] = ((int)($book['views'] ?? 0)) + 1;

        $chapters = $this->query('SELECT id, title FROM ' . $this->getTableName('chapters') . ' WHERE book_id = :book_id ORDER BY chapter_number ASC, id ASC', [':book_id' => $id]);

        // 获取用户相关数据
        $userId = $this->getCurrentUserId();
        $readingProgress = null;
        $bookmarks = [];
        $similarBooks = [];

        if ($userId) {
            // 记录用户查看行为
            $this->recommendationService->recordBehavior($userId, $id, 'view');
            
            // 获取阅读进度
            $readingProgress = $this->readingProgressService->getProgress($userId, $id);
            
            // 获取书签
            $bookmarks = $this->bookmarkService->getBookmarksByBook($userId, $id);
        }

        // 获取相似书籍
        $similarBooks = $this->recommendationService->getSimilarBooks($id, 4);

        $this->render('frontend/book_detail', [
            'book' => $book,
            'chapters' => $chapters,
            'comments' => [],
            'readingProgress' => $readingProgress,
            'bookmarks' => $bookmarks,
            'similarBooks' => $similarBooks,
            'userId' => $userId,
        ]);
    }

    public function readChapter($book_id, $chapter_id)
    {
        header('Content-Type: text/html; charset=utf-8');

        $bookId = (int)$book_id;
        $chapterId = (int)$chapter_id;

        $row = $this->query(
            'SELECT c.*, b.title as book_title FROM ' . $this->getTableName('chapters') . ' c LEFT JOIN ' . $this->getTableName('books') . ' b ON c.book_id = b.id WHERE c.id = :chapter_id AND c.book_id = :book_id',
            [':chapter_id' => $chapterId, ':book_id' => $bookId]
        );
        $chapter = $row[0] ?? null;
        if (!$chapter) {
            http_response_code(404);
            echo '章节不存在。';
            return;
        }

        $prev = $this->query(
            'SELECT id FROM ' . $this->getTableName('chapters') . ' WHERE book_id = :book_id AND id < :id ORDER BY id DESC LIMIT 1',
            [':book_id' => $bookId, ':id' => $chapterId]
        );
        $next = $this->query(
            'SELECT id FROM ' . $this->getTableName('chapters') . ' WHERE book_id = :book_id AND id > :id ORDER BY id ASC LIMIT 1',
            [':book_id' => $bookId, ':id' => $chapterId]
        );

        // 获取用户相关数据
        $userId = $this->getCurrentUserId();
        $readingProgress = null;
        $bookmarks = [];

        if ($userId) {
            // 更新阅读进度
            $totalChapters = $this->query('SELECT COUNT(*) as count FROM ' . $this->getTableName('chapters') . ' WHERE book_id = :book_id', [':book_id' => $bookId])[0]['count'];
            $currentChapterNumber = $this->query('SELECT chapter_number FROM ' . $this->getTableName('chapters') . ' WHERE id = :chapter_id', [':chapter_id' => $chapterId])[0]['chapter_number'] ?? 1;
            $progress = ($currentChapterNumber / $totalChapters) * 100;
            $this->readingProgressService->updateProgress($userId, $bookId, $chapterId, $progress);
            
            // 获取当前阅读进度
            $readingProgress = $this->readingProgressService->getProgress($userId, $bookId);
            
            // 获取本书的书签
            $bookmarks = $this->bookmarkService->getBookmarksByBook($userId, $bookId);
        }

        $this->render('frontend/read_chapter', [
            'chapter' => $chapter,
            'prevChapterId' => $prev[0]['id'] ?? null,
            'nextChapterId' => $next[0]['id'] ?? null,
            'readingProgress' => $readingProgress,
            'bookmarks' => $bookmarks,
            'userId' => $userId,
        ]);
    }

    /**
     * 添加书签API
     */
    public function addBookmark()
    {
        header('Content-Type: application/json');
        
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            echo json_encode(['success' => false, 'message' => '请先登录']);
            return;
        }

        $bookId = (int)($_POST['book_id'] ?? 0);
        $chapterId = (int)($_POST['chapter_id'] ?? 0);
        $position = (int)($_POST['position'] ?? 0);
        $note = $_POST['note'] ?? '';

        if (!$bookId || !$chapterId) {
            echo json_encode(['success' => false, 'message' => '参数错误']);
            return;
        }

        $bookmarkId = $this->bookmarkService->addBookmark($userId, $bookId, $chapterId, $position, $note);
        
        if ($bookmarkId) {
            echo json_encode(['success' => true, 'bookmark_id' => $bookmarkId]);
        } else {
            echo json_encode(['success' => false, 'message' => '添加书签失败']);
        }
    }

    /**
     * 删除书签API
     */
    public function deleteBookmark()
    {
        header('Content-Type: application/json');
        
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            echo json_encode(['success' => false, 'message' => '请先登录']);
            return;
        }

        $bookmarkId = (int)($_POST['bookmark_id'] ?? 0);
        
        if (!$bookmarkId) {
            echo json_encode(['success' => false, 'message' => '参数错误']);
            return;
        }

        $result = $this->bookmarkService->deleteBookmark($userId, $bookmarkId);
        
        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => '删除书签失败']);
        }
    }

    /**
     * 更新阅读进度API
     */
    public function updateReadingProgress()
    {
        header('Content-Type: application/json');
        
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            echo json_encode(['success' => false, 'message' => '请先登录']);
            return;
        }

        $bookId = (int)($_POST['book_id'] ?? 0);
        $chapterId = (int)($_POST['chapter_id'] ?? 0);
        $progress = (float)($_POST['progress'] ?? 0);
        
        if (!$bookId || !$chapterId) {
            echo json_encode(['success' => false, 'message' => '参数错误']);
            return;
        }

        $result = $this->readingProgressService->updateProgress($userId, $bookId, $chapterId, $progress);
        
        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => '更新进度失败']);
        }
    }

    /**
     * 获取用户阅读进度和书签页面
     */
    public function userLibrary()
    {
        header('Content-Type: text/html; charset=utf-8');
        
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            echo '请先登录';
            return;
        }

        $readingProgress = $this->readingProgressService->getAllProgress($userId, 20);
        $bookmarks = $this->bookmarkService->getAllBookmarks($userId, 50);
        $recommendations = $this->recommendationService->generateRecommendations($userId, 10);

        $this->render('frontend/user_library', [
            'readingProgress' => $readingProgress,
            'bookmarks' => $bookmarks,
            'recommendations' => $recommendations,
        ]);
    }
}

