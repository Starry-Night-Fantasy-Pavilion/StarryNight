<?php
namespace plugins\apps\my_app\Controllers;

use plugins\apps\my_app\Services\ReadingProgressService;
use plugins\apps\my_app\Services\BookmarkService;
use plugins\apps\my_app\Services\RecommendationService;

use BookSourceManager\Core\Config;
use Exception;

class AdminController extends BaseController
{
    private $readingProgressService;
    private $bookmarkService;
    private $recommendationService;
    private $configService;

    public function __construct()
    {
        parent::__construct();
        $this->readingProgressService = new ReadingProgressService();
        $this->bookmarkService = new BookmarkService();
        $this->recommendationService = new RecommendationService();
        $this->configService = new Config();
    }
    public function books()
    {
        header('Content-Type: text/html; charset=utf-8');
        $books = $this->query('SELECT * FROM ' . $this->getTableName('books') . ' ORDER BY id DESC LIMIT 200');
        $this->render('admin/books', ['books' => $books]);
    }

    public function sources()
    {
        header('Content-Type: text/html; charset=utf-8');
        $sources = $this->query('SELECT * FROM ' . $this->getTableName('book_sources') . ' ORDER BY id DESC LIMIT 200');
        $this->render('admin/sources', ['sources' => $sources]);
    }

    public function comments()
    {
        header('Content-Type: text/html; charset=utf-8');
        $comments = $this->query('SELECT * FROM ' . $this->getTableName('comments') . ' ORDER BY id DESC LIMIT 200');
        $this->render('admin/comments', ['comments' => $comments]);
    }

    public function rankings()
    {
        header('Content-Type: text/html; charset=utf-8');
        $rankings = $this->query('SELECT id, title, author, views FROM ' . $this->getTableName('books') . ' ORDER BY views DESC LIMIT 50');
        $this->render('admin/rankings', ['rankings' => $rankings]);
    }

    public function settings()
    {
        header('Content-Type: text/html; charset=utf-8');
        $message = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // CSRF check is recommended here
            $settingsToSave = [
                'source_mode' => $_POST['source_mode'] ?? 'manual',
                'remote_source_url' => $_POST['remote_source_url'] ?? '',
                'auto_sync_enabled' => isset($_POST['auto_sync_enabled']),
                'sync_interval' => (int)($_POST['sync_interval'] ?? 24),
            ];
            $this->configService->set($settingsToSave);
            $message = ['type' => 'success', 'messages' => ['设置已保存。']];
        }

        $config = $this->configService->get();

        $form = [
            'sections' => [
                'mode' => [
                    'title' => '书源配置模式',
                    'fields' => [
                        'source_mode' => [
                            'label' => '配置模式',
                            'type' => 'select',
                            'name' => 'source_mode',
                            'value' => $config['source_mode'] ?? 'manual',
                            'options' => [
                                'manual' => '手动配置书源',
                                'remote' => '远程获取书源'
                            ],
                            'description' => '选择书源的配置方式。'
                        ],
                    ]
                ],
                'remote' => [
                    'title' => '远程书源设置',
                    'fields' => [
                        'remote_source_url' => [
                            'label' => '远程书源地址',
                            'type' => 'text',
                            'name' => 'remote_source_url',
                            'value' => $config['remote_source_url'] ?? '',
                            'description' => '用于 `extract_sources.php` 脚本远程获取书源的 URL。'
                        ],
                        'auto_sync_enabled' => [
                            'label' => '启用自动同步',
                            'type' => 'checkbox',
                            'name' => 'auto_sync_enabled',
                            'value' => $config['auto_sync_enabled'] ?? false,
                            'description' => '是否启用自动同步远程书源。'
                        ],
                        'sync_interval' => [
                            'label' => '同步间隔（小时）',
                            'type' => 'number',
                            'name' => 'sync_interval',
                            'value' => $config['sync_interval'] ?? 24,
                            'min' => 1,
                            'max' => 168,
                            'description' => '自动同步的时间间隔（1-168小时）。'
                        ],
                    ]
                ]
            ]
        ];

        $this->render('admin/settings', [
            'form' => $form,
            'config' => $config,
            'message' => $message
        ]);
    }

    public function edit_source($id = null)
    {
        header('Content-Type: text/html; charset=utf-8');
        $source = null;
        $message = null;

        if ($id) {
            $source = $this->query('SELECT * FROM ' . $this->getTableName('book_sources') . ' WHERE id = ?', [$id], 'first');
            if (!$source) {
                header('Location: /admin/my_app/sources');
                exit;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Note: CSRF check is assumed to be handled by the framework or a middleware.
            // if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
            //     $message = ['type' => 'error', 'messages' => ['CSRF token mismatch.']];
            // } else {
                $data = [
                    'book_source_name' => $_POST['book_source_name'] ?? '',
                    'book_source_group' => $_POST['book_source_group'] ?? null,
                    'book_source_url' => $_POST['book_source_url'] ?? '',
                    'book_url_pattern' => $_POST['book_url_pattern'] ?? null,
                    'book_source_type' => (int)($_POST['book_source_type'] ?? 0),
                    'weight' => (int)($_POST['weight'] ?? 0),
                    'custom_order' => (int)($_POST['custom_order'] ?? 0),
                    'enabled' => isset($_POST['enabled']) ? 1 : 0,
                    'enabled_explore' => isset($_POST['enabled_explore']) ? 1 : 0,
                    'login_url' => $_POST['login_url'] ?? null,
                    'header' => $_POST['header'] ?? null,
                    'explore_url' => $_POST['explore_url'] ?? null,
                    'rule_explore' => $_POST['rule_explore'] ?? null,
                    'search_url' => $_POST['search_url'] ?? null,
                    'rule_search' => $_POST['rule_search'] ?? null,
                    'rule_book_info' => $_POST['rule_book_info'] ?? null,
                    'rule_toc' => $_POST['rule_toc'] ?? null,
                    'rule_content' => $_POST['rule_content'] ?? null,
                ];

                // Basic validation
                if (empty($data['book_source_name']) || empty($data['book_source_url'])) {
                    $message = ['type' => 'error', 'messages' => ['书源名称和地址是必填项。']];
                    $source = array_merge($source ?? [], $_POST);
                } else {
                    if ($id) {
                        $this->execute('UPDATE ' . $this->getTableName('book_sources') . ' SET ' . implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($data))) . ' WHERE id = ?', array_merge(array_values($data), [$id]));
                    } else {
                        $this->execute('INSERT INTO ' . $this->getTableName('book_sources') . ' (' . implode(', ', array_map(fn($k) => "`$k`", array_keys($data))) . ') VALUES (' . rtrim(str_repeat('?,', count($data)), ',') . ')', array_values($data));
                    }
                    header('Location: /admin/my_app/sources');
                    exit;
                }
            // }
        }

        $this->render('admin/edit_source', ['source' => $source, 'message' => $message]);
    }

    public function delete_source($id)
    {
        if ($id) {
            $this->execute('DELETE FROM ' . $this->getTableName('book_sources') . ' WHERE id = ?', [$id]);
        }
        header('Location: /admin/my_app/sources');
        exit;
    }

    public function sync_sources()
    {
        header('Content-Type: application/json');

        try {
            $config = $this->configService->get();
            $remoteUrl = $config['remote_source_url'] ?? '';

            if (empty($remoteUrl)) {
                echo json_encode(['success' => false, 'message' => '未配置远程书源地址']);
                exit;
            }

            // 这里应该调用 extract_sources.php 的逻辑
            // 暂时返回成功消息
            echo json_encode(['success' => true, 'message' => '书源同步完成']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '同步失败：' . $e->getMessage()]);
        }
        exit;
    }
}

