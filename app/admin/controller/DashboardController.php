<?php
namespace app\admin\controller;

use app\services\Database;
use PDO;

class DashboardController extends BaseController
{
    public function index()
    {
        try {
            $title = '运营仪表盘';
            $currentPage = 'dashboard';
            $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
            $reportMetric = $_GET['report_metric'] ?? 'new_users';
            $reportGroup = $_GET['report_group'] ?? 'day';
            $stats = $this->buildOperationsStats($days, (string)$reportMetric, (string)$reportGroup);

            ob_start();
            require __DIR__ . '/../views/dashboard.php';
            $content = ob_get_clean();

            require __DIR__ . '/../views/layout.php';
        } catch (\Throwable $e) {
            error_log('DashboardController::index() error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            http_response_code(500);
            echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>错误</title></head><body><h1>页面加载失败</h1><p>请稍后重试或联系管理员。</p></body></html>';
        }
    }

    private function buildOperationsStats(int $days, string $reportMetric, string $reportGroup): array
    {
        if ($days <= 0) $days = 30;
        if ($days > 365) $days = 365;
        if (!in_array($reportGroup, ['day', 'week', 'month'], true)) $reportGroup = 'day';

        $stats = [
            'meta' => [
                'days' => $days,
                'report_metric' => $reportMetric,
                'report_group' => $reportGroup,
            ],
            'system' => [
                'php_version' => phpversion(),
                'db_status' => '未知',
                'db_driver' => 'N/A',
                'db_version' => 'N/A',
                'db_ping_ms' => null,
                'server_load' => null,
                'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'disk_free_gb' => null,
                'ai_channel_status' => [],
                'queue_backlog' => null,
                'api_success_rate' => null,
                'api_avg_latency_ms' => null,
            ],
            'users' => [
                'total_users' => 0,
                'new_users_today' => 0,
                'dau' => 0,
                'mau' => 0,
                'active_memberships' => 0,
                'retention_7d' => null,
                'retention_30d' => null,
                'growth_trend' => [],
            ],
            'creation' => [
                'total_books' => 0,
                'total_music' => 0,
                'total_anime' => 0,
                'trend' => [
                    'books' => [],
                    'music' => [],
                    'anime' => [],
                ],
                'top_books' => [],
                'top_music' => [],
                'top_anime' => [],
            ],
            'coins' => [
                'total_spent' => 0,
                'spend_trend' => [],
                'spend_by_feature' => [],
                'top_spenders' => [],
            ],
            'revenue' => [
                'total_revenue' => 0,
                'trend' => [],
                'by_gateway' => [],
                'membership_by_level' => [],
            ],
            'pending' => [
                'feedback' => 0,
                'audit' => 0,
                'alerts' => 0,
                'marketing' => 0,
                'details' => [],
                'feedback_details' => [],
                'alert_details' => [],
                'marketing_details' => [],
            ],
            'report' => [
                'series' => [],
            ],
        ];

        try {
            $pdo = Database::pdo();
            $prefix = Database::prefix();

            $this->fillSystem($pdo, $stats);
            $this->fillUsers($pdo, $prefix, $days, $stats);
            $this->fillCreation($pdo, $prefix, $days, $stats);
            $this->fillCoins($pdo, $prefix, $days, $stats);
            $this->fillRevenue($pdo, $prefix, $days, $stats);
            $this->fillPending($pdo, $prefix, $stats);
            $this->fillSystemHealth($pdo, $prefix, $stats);
            $stats['report']['series'] = $this->buildReport($pdo, $prefix, $reportMetric, $reportGroup, $days);
        } catch (\Throwable $e) {
                    error_log('Dashboard Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
                }

        return $stats;
    }

    private function fillSystem(PDO $pdo, array &$stats): void
    {
        try {
            $stats['system']['db_status'] = '连接成功';
            $stats['system']['db_driver'] = (string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            $stats['system']['db_version'] = (string)$pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
            $t0 = microtime(true);
            $pdo->query('SELECT 1')->fetchColumn();
            $stats['system']['db_ping_ms'] = (int)round((microtime(true) - $t0) * 1000);
        } catch (\Throwable $e) {
            $stats['system']['db_status'] = '连接失败';
        }

        $load = function_exists('sys_getloadavg') ? sys_getloadavg() : null;
        if (is_array($load) && isset($load[0])) {
            $stats['system']['server_load'] = round($load[0], 2);
        }

        $diskFree = @disk_free_space(__DIR__);
        if (is_numeric($diskFree) && $diskFree > 0) {
            $stats['system']['disk_free_gb'] = round($diskFree / 1024 / 1024 / 1024, 2);
        }
    }

    private function fillSystemHealth(PDO $pdo, string $prefix, array &$stats): void
    {
        // AI渠道状态 - 从设置表或专门的AI渠道状态表读取
        $settingsTable = $prefix . 'settings';
        if ($this->tableExists($pdo, $settingsTable)) {
            $aiChannels = $this->rows($pdo, "SELECT `key`, `value` FROM `{$settingsTable}` WHERE `key` LIKE 'ai_channel_%'");
            foreach ($aiChannels as $channel) {
                $channelName = str_replace('ai_channel_', '', $channel['key']);
                $stats['system']['ai_channel_status'][$channelName] = [
                    'status' => $channel['value'] === 'enabled' ? '正常' : '异常',
                    'enabled' => $channel['value'] === 'enabled',
                ];
            }
        }

        // 消息队列积压 - 假设有队列任务表
        $queueTable = $prefix . 'queue_jobs';
        if ($this->tableExists($pdo, $queueTable)) {
            $stats['system']['queue_backlog'] = (int)$this->scalar(
                $pdo,
                "SELECT COUNT(*) FROM `{$queueTable}` WHERE status = 'pending'",
                [],
                0
            );
        } else {
            $stats['system']['queue_backlog'] = null;
        }

        // API调用成功率与延迟 - 假设有API日志表
        $apiLogTable = $prefix . 'api_logs';
        if ($this->tableExists($pdo, $apiLogTable)) {
            $totalCalls = (int)$this->scalar($pdo, "SELECT COUNT(*) FROM `{$apiLogTable}` WHERE created_at >= NOW() - INTERVAL 24 HOUR", [], 0);
            $successCalls = (int)$this->scalar($pdo, "SELECT COUNT(*) FROM `{$apiLogTable}` WHERE created_at >= NOW() - INTERVAL 24 HOUR AND status = 'success'", [], 0);
            if ($totalCalls > 0) {
                $stats['system']['api_success_rate'] = round($successCalls / $totalCalls * 100, 2);
            }

            $avgLatency = $this->scalar($pdo, "SELECT AVG(latency_ms) FROM `{$apiLogTable}` WHERE created_at >= NOW() - INTERVAL 24 HOUR AND latency_ms IS NOT NULL", [], null);
            if ($avgLatency !== null) {
                $stats['system']['api_avg_latency_ms'] = round((float)$avgLatency, 2);
            }
        }
    }

    private function fillUsers(PDO $pdo, string $prefix, int $days, array &$stats): void
    {
        $usersTable = $prefix . 'users';
        if (!$this->tableExists($pdo, $usersTable)) return;

        // 排除所有后台管理员账号（通过role字段或is_admin字段） + 当前登录后台账号
        $adminUserId = isset($_SESSION['admin_user_id']) ? (int)$_SESSION['admin_user_id'] : null;
        $excludeAdminCondition = $this->buildExcludeAdminCondition($pdo, $usersTable, $adminUserId);
        $excludeAdminUserJoinCondition = $this->buildExcludeAdminCondition($pdo, $usersTable, $adminUserId, 'u');

        $userStatsResult = $this->rows($pdo, "SELECT COUNT(*) AS total_users, SUM(CASE WHEN created_at >= CURDATE() THEN 1 ELSE 0 END) AS new_users_today FROM `{$usersTable}` WHERE 1=1 {$excludeAdminCondition}");
        if (!empty($userStatsResult[0])) {
            $stats['users']['total_users'] = (int)($userStatsResult[0]['total_users'] ?? 0);
            $stats['users']['new_users_today'] = (int)($userStatsResult[0]['new_users_today'] ?? 0);
        }
        $stats['users']['growth_trend'] = $this->rows(
            $pdo,
            "SELECT DATE(created_at) AS date, COUNT(*) AS count FROM `{$usersTable}` WHERE created_at >= NOW() - INTERVAL {$days} DAY {$excludeAdminCondition} GROUP BY DATE(created_at) ORDER BY date ASC"
        );

        $coinTable = $prefix . 'coin_transactions';
        $orderTable = $prefix . 'orders';
        
        $activeUnionSql = [];
        if ($this->tableExists($pdo, $coinTable)) {
            $activeUnionSql[] = "SELECT t.user_id, t.created_at AS t FROM `{$coinTable}` t LEFT JOIN `{$usersTable}` u ON u.id = t.user_id WHERE 1=1 {$excludeAdminUserJoinCondition}";
        }
        if ($this->tableExists($pdo, $orderTable)) {
            $activeUnionSql[] = "SELECT o.user_id, o.created_at AS t FROM `{$orderTable}` o LEFT JOIN `{$usersTable}` u ON u.id = o.user_id WHERE 1=1 {$excludeAdminUserJoinCondition}";
            $activeUnionSql[] = "SELECT o.user_id, o.paid_at AS t FROM `{$orderTable}` o LEFT JOIN `{$usersTable}` u ON u.id = o.user_id WHERE o.paid_at IS NOT NULL {$excludeAdminUserJoinCondition}";
        }
        if ($activeUnionSql) {
            $activeSql = implode(' UNION ALL ', $activeUnionSql);
            $stats['users']['dau'] = (int)$this->scalar($pdo, "SELECT COUNT(DISTINCT user_id) FROM ({$activeSql}) a WHERE a.t >= NOW() - INTERVAL 1 DAY", [], 0);
            $stats['users']['mau'] = (int)$this->scalar($pdo, "SELECT COUNT(DISTINCT user_id) FROM ({$activeSql}) a WHERE a.t >= NOW() - INTERVAL 30 DAY", [], 0);
        }

        $membershipTable = $prefix . 'user_memberships';
        if ($this->tableExists($pdo, $membershipTable)) {
            $stats['users']['active_memberships'] = (int)$this->scalar($pdo, "SELECT COUNT(*) FROM `{$membershipTable}` m LEFT JOIN `{$usersTable}` u ON u.id = m.user_id WHERE m.status = 'active' AND m.end_date > NOW() {$excludeAdminUserJoinCondition}", [], 0);
        }

        if ($this->tableExists($pdo, $coinTable)) {
            $stats['users']['retention_7d'] = $this->retentionWithinDays($pdo, $usersTable, $coinTable, 7, $excludeAdminCondition);
            $stats['users']['retention_30d'] = $this->retentionWithinDays($pdo, $usersTable, $coinTable, 30, $excludeAdminCondition);
        }
    }

    private function fillCreation(PDO $pdo, string $prefix, int $days, array &$stats): void
    {
        $countQueries = [];
        
        $booksTable = $prefix . 'books';
        if ($this->tableExists($pdo, $booksTable)) {
            $countQueries[] = "SELECT 'books' as type, COUNT(*) as count FROM `{$booksTable}`";
            $stats['creation']['trend']['books'] = $this->rows(
                $pdo,
                "SELECT DATE(created_at) AS date, COUNT(*) AS count FROM `{$booksTable}` WHERE created_at >= NOW() - INTERVAL {$days} DAY GROUP BY DATE(created_at) ORDER BY date ASC"
            );
            $stats['creation']['top_books'] = $this->rows(
                $pdo,
                "SELECT id, title, views, likes FROM `{$booksTable}` ORDER BY views DESC, likes DESC, id DESC LIMIT 10"
            );
        }

        $musicTable = $prefix . 'music_tracks';
        if ($this->tableExists($pdo, $musicTable)) {
            $countQueries[] = "SELECT 'music' as type, COUNT(*) as count FROM `{$musicTable}`";
            $stats['creation']['trend']['music'] = $this->rows(
                $pdo,
                "SELECT DATE(created_at) AS date, COUNT(*) AS count FROM `{$musicTable}` WHERE created_at >= NOW() - INTERVAL {$days} DAY GROUP BY DATE(created_at) ORDER BY date ASC"
            );
            $stats['creation']['top_music'] = $this->rows(
                $pdo,
                "SELECT id, title, artist, plays, likes FROM `{$musicTable}` ORDER BY plays DESC, likes DESC, id DESC LIMIT 10"
            );
        }

        // 动漫内容统计
        $animeTable = $prefix . 'anime';
        $animeTableAlt = $prefix . 'animes';
        $actualAnimeTable = null;
        if ($this->tableExists($pdo, $animeTable)) {
            $actualAnimeTable = $animeTable;
        } elseif ($this->tableExists($pdo, $animeTableAlt)) {
            $actualAnimeTable = $animeTableAlt;
        }

        if ($actualAnimeTable) {
            $countQueries[] = "SELECT 'anime' as type, COUNT(*) as count FROM `{$actualAnimeTable}`";
            $stats['creation']['trend']['anime'] = $this->rows(
                $pdo,
                "SELECT DATE(created_at) AS date, COUNT(*) AS count FROM `{$actualAnimeTable}` WHERE created_at >= NOW() - INTERVAL {$days} DAY GROUP BY DATE(created_at) ORDER BY date ASC"
            );
            $stats['creation']['top_anime'] = $this->rows(
                $pdo,
                "SELECT id, title, views, likes FROM `{$actualAnimeTable}` ORDER BY views DESC, likes DESC, id DESC LIMIT 10"
            );
        }

        if (!empty($countQueries)) {
            $sql = implode(' UNION ALL ', $countQueries);
            $totalCounts = $this->rows($pdo, $sql);
            foreach ($totalCounts as $row) {
                if ($row['type'] === 'books') {
                    $stats['creation']['total_books'] = (int)$row['count'];
                } elseif ($row['type'] === 'music') {
                    $stats['creation']['total_music'] = (int)$row['count'];
                } elseif ($row['type'] === 'anime') {
                    $stats['creation']['total_anime'] = (int)$row['count'];
                }
            }
        }
    }

    private function fillCoins(PDO $pdo, string $prefix, int $days, array &$stats): void
    {
        $coinTable = $prefix . 'coin_transactions';
        if (!$this->tableExists($pdo, $coinTable)) return;

        $usersTable = $prefix . 'users';
        $adminUserId = isset($_SESSION['admin_user_id']) ? (int)$_SESSION['admin_user_id'] : null;
        $excludeAdminUsersU = '';
        $excludeAdminUsersU2 = '';
        if ($this->tableExists($pdo, $usersTable)) {
            // 星夜币消耗统计也排除后台账号
            $excludeAdminUsersU = $this->buildExcludeAdminCondition($pdo, $usersTable, $adminUserId, 'u');
            $excludeAdminUsersU2 = $this->buildExcludeAdminCondition($pdo, $usersTable, $adminUserId, 'u2');

            $stats['coins']['total_spent'] = (float)$this->scalar(
                $pdo,
                "SELECT COALESCE(SUM(ABS(t.amount)), 0) FROM `{$coinTable}` t LEFT JOIN `{$usersTable}` u ON u.id = t.user_id WHERE t.type = 'spend' {$excludeAdminUsersU}",
                [],
                0
            );
            $stats['coins']['spend_trend'] = $this->rows(
                $pdo,
                "SELECT DATE(t.created_at) AS date, COALESCE(SUM(ABS(t.amount)), 0) AS total FROM `{$coinTable}` t LEFT JOIN `{$usersTable}` u ON u.id = t.user_id WHERE t.type = 'spend' AND t.created_at >= NOW() - INTERVAL {$days} DAY {$excludeAdminUsersU} GROUP BY DATE(t.created_at) ORDER BY date ASC"
            );
            $stats['coins']['spend_by_feature'] = $this->rows(
                $pdo,
                "SELECT COALESCE(NULLIF(TRIM(t.related_type), ''), 'unknown') AS feature, COALESCE(SUM(ABS(t.amount)), 0) AS total FROM `{$coinTable}` t LEFT JOIN `{$usersTable}` u ON u.id = t.user_id WHERE t.type = 'spend' AND t.created_at >= NOW() - INTERVAL {$days} DAY {$excludeAdminUsersU} GROUP BY feature ORDER BY total DESC LIMIT 10"
            );

            $stats['coins']['top_spenders'] = $this->rows(
                $pdo,
                "SELECT x.user_id, u.username, u.email, x.total FROM (
                    SELECT t.user_id, COALESCE(SUM(ABS(t.amount)), 0) AS total
                    FROM `{$coinTable}` t
                    LEFT JOIN `{$usersTable}` u2 ON u2.id = t.user_id
                    WHERE t.type = 'spend' AND t.created_at >= NOW() - INTERVAL {$days} DAY {$excludeAdminUsersU2}
                    GROUP BY t.user_id
                    ORDER BY total DESC
                    LIMIT 20
                ) x
                LEFT JOIN `{$usersTable}` u ON u.id = x.user_id
                ORDER BY x.total DESC"
            );
        } else {
            $stats['coins']['total_spent'] = (float)$this->scalar($pdo, "SELECT COALESCE(SUM(ABS(amount)), 0) FROM `{$coinTable}` WHERE type = 'spend'", [], 0);
            $stats['coins']['spend_trend'] = $this->rows(
                $pdo,
                "SELECT DATE(created_at) AS date, COALESCE(SUM(ABS(amount)), 0) AS total FROM `{$coinTable}` WHERE type = 'spend' AND created_at >= NOW() - INTERVAL {$days} DAY GROUP BY DATE(created_at) ORDER BY date ASC"
            );
            $stats['coins']['spend_by_feature'] = $this->rows(
                $pdo,
                "SELECT COALESCE(NULLIF(TRIM(related_type), ''), 'unknown') AS feature, COALESCE(SUM(ABS(amount)), 0) AS total FROM `{$coinTable}` WHERE type = 'spend' AND created_at >= NOW() - INTERVAL {$days} DAY GROUP BY feature ORDER BY total DESC LIMIT 10"
            );
            $stats['coins']['top_spenders'] = $this->rows(
                $pdo,
                "SELECT user_id, COALESCE(SUM(ABS(amount)), 0) AS total FROM `{$coinTable}` WHERE type = 'spend' AND created_at >= NOW() - INTERVAL {$days} DAY GROUP BY user_id ORDER BY total DESC LIMIT 20"
            );
        }
    }

    private function fillRevenue(PDO $pdo, string $prefix, int $days, array &$stats): void
    {
        $orderTable = $prefix . 'orders';
        if (!$this->tableExists($pdo, $orderTable)) return;

        $stats['revenue']['total_revenue'] = (float)$this->scalar($pdo, "SELECT COALESCE(SUM(amount), 0) FROM `{$orderTable}` WHERE status = 'completed'", [], 0);
        $stats['revenue']['trend'] = $this->rows(
            $pdo,
            "SELECT DATE(paid_at) AS date, COALESCE(SUM(amount), 0) AS total FROM `{$orderTable}` WHERE status = 'completed' AND paid_at IS NOT NULL AND paid_at >= NOW() - INTERVAL {$days} DAY GROUP BY DATE(paid_at) ORDER BY date ASC"
        );
        $stats['revenue']['by_gateway'] = $this->rows(
            $pdo,
            "SELECT COALESCE(NULLIF(TRIM(payment_gateway), ''), 'unknown') AS gateway, COALESCE(SUM(amount), 0) AS total FROM `{$orderTable}` WHERE status = 'completed' GROUP BY gateway ORDER BY total DESC"
        );
        $stats['revenue']['membership_by_level'] = $this->rows(
            $pdo,
            "SELECT COALESCE(NULLIF(TRIM(product_id), ''), 'membership_unknown') AS level, COALESCE(SUM(amount), 0) AS total FROM `{$orderTable}` WHERE status = 'completed' AND product_type = 'membership' GROUP BY level ORDER BY total DESC"
        );
    }

    private function fillPending(PDO $pdo, string $prefix, array &$stats): void
    {
        $details = [];
        $audit = 0;
        $feedback = 0;
        $alerts = 0;
        $marketing = 0;

        // 审核任务
        $commentsTable = $prefix . 'comments';
        if ($this->tableExists($pdo, $commentsTable)) {
            $c = (int)$this->scalar($pdo, "SELECT COUNT(*) FROM `{$commentsTable}` WHERE status <> 'approved'", [], 0);
            $audit += $c;
            $details[] = ['type' => '小说评论待审', 'count' => $c];
        }

        $musicCommentsTable = $prefix . 'music_comments';
        if ($this->tableExists($pdo, $musicCommentsTable)) {
            $c = (int)$this->scalar($pdo, "SELECT COUNT(*) FROM `{$musicCommentsTable}` WHERE status <> 'approved'", [], 0);
            $audit += $c;
            $details[] = ['type' => '音乐评论待审', 'count' => $c];
        }

        // 用户反馈
        $feedbackTable = $prefix . 'user_feedback';
        if ($this->tableExists($pdo, $feedbackTable)) {
            $feedback = (int)$this->scalar($pdo, "SELECT COUNT(*) FROM `{$feedbackTable}` WHERE status = 'pending'", [], 0);
            $stats['pending']['feedback_details'] = $this->rows(
                $pdo,
                "SELECT id, user_id, title, content, created_at FROM `{$feedbackTable}` WHERE status = 'pending' ORDER BY created_at DESC LIMIT 10"
            );
        } else {
            // 如果没有专门的反馈表，尝试从其他表查找
            $reportsTable = $prefix . 'reports';
            if ($this->tableExists($pdo, $reportsTable)) {
                $feedback = (int)$this->scalar($pdo, "SELECT COUNT(*) FROM `{$reportsTable}` WHERE status = 'pending'", [], 0);
                $stats['pending']['feedback_details'] = $this->rows(
                    $pdo,
                    "SELECT id, user_id, title, content, created_at FROM `{$reportsTable}` WHERE status = 'pending' ORDER BY created_at DESC LIMIT 10"
                );
            }
        }

        // 异常告警
        $alertsTable = $prefix . 'system_alerts';
        if ($this->tableExists($pdo, $alertsTable)) {
            $alerts = (int)$this->scalar($pdo, "SELECT COUNT(*) FROM `{$alertsTable}` WHERE status = 'active'", [], 0);
            $stats['pending']['alert_details'] = $this->rows(
                $pdo,
                "SELECT id, type, message, severity, created_at FROM `{$alertsTable}` WHERE status = 'active' ORDER BY severity DESC, created_at DESC LIMIT 10"
            );
        }

        // 营销活动提醒
        $campaignsTable = $prefix . 'marketing_campaigns';
        if ($this->tableExists($pdo, $campaignsTable)) {
            $marketing = (int)$this->scalar($pdo, "SELECT COUNT(*) FROM `{$campaignsTable}` WHERE status = 'scheduled' AND start_date <= DATE_ADD(NOW(), INTERVAL 7 DAY)", [], 0);
            $stats['pending']['marketing_details'] = $this->rows(
                $pdo,
                "SELECT id, name, start_date, end_date, status FROM `{$campaignsTable}` WHERE status = 'scheduled' AND start_date <= DATE_ADD(NOW(), INTERVAL 7 DAY) ORDER BY start_date ASC LIMIT 10"
            );
        }

        $stats['pending']['audit'] = $audit;
        $stats['pending']['feedback'] = $feedback;
        $stats['pending']['alerts'] = $alerts;
        $stats['pending']['marketing'] = $marketing;
        $stats['pending']['details'] = $details;
    }

    private function buildReport(PDO $pdo, string $prefix, string $metric, string $group, int $days): array
    {
        $groupExprCreated = match ($group) {
            'week' => "DATE_FORMAT(created_at, '%x-W%v')",
            'month' => "DATE_FORMAT(created_at, '%Y-%m')",
            default => "DATE(created_at)",
        };
        $groupExprPaid = match ($group) {
            'week' => "DATE_FORMAT(paid_at, '%x-W%v')",
            'month' => "DATE_FORMAT(paid_at, '%Y-%m')",
            default => "DATE(paid_at)",
        };

        $adminUserId = isset($_SESSION['admin_user_id']) ? (int)$_SESSION['admin_user_id'] : null;
        $usersTable = $prefix . 'users';
        $excludeUsersNoAlias = '';
        $excludeUsersAliasU = '';
        $usersTableExists = $this->tableExists($pdo, $usersTable);
        if ($usersTableExists) {
            $excludeUsersNoAlias = $this->buildExcludeAdminCondition($pdo, $usersTable, $adminUserId);
            $excludeUsersAliasU = $this->buildExcludeAdminCondition($pdo, $usersTable, $adminUserId, 'u');
        }

        if ($metric === 'coin_spend') {
            $coinTable = $prefix . 'coin_transactions';
            if (!$this->tableExists($pdo, $coinTable)) return [];
            return $this->rows($pdo, "SELECT {$groupExprCreated} AS label, COALESCE(SUM(ABS(amount)), 0) AS value FROM `{$coinTable}` WHERE type='spend' AND created_at >= NOW() - INTERVAL {$days} DAY GROUP BY label ORDER BY label ASC");
        }

        if ($metric === 'revenue') {
            $orderTable = $prefix . 'orders';
            if (!$this->tableExists($pdo, $orderTable)) return [];
            return $this->rows($pdo, "SELECT {$groupExprPaid} AS label, COALESCE(SUM(amount), 0) AS value FROM `{$orderTable}` WHERE status='completed' AND paid_at IS NOT NULL AND paid_at >= NOW() - INTERVAL {$days} DAY GROUP BY label ORDER BY label ASC");
        }

        if ($metric === 'new_books') {
            $booksTable = $prefix . 'books';
            if (!$this->tableExists($pdo, $booksTable)) return [];
            return $this->rows($pdo, "SELECT {$groupExprCreated} AS label, COUNT(*) AS value FROM `{$booksTable}` WHERE created_at >= NOW() - INTERVAL {$days} DAY GROUP BY label ORDER BY label ASC");
        }

        if ($metric === 'new_music') {
            $musicTable = $prefix . 'music_tracks';
            if (!$this->tableExists($pdo, $musicTable)) return [];
            return $this->rows($pdo, "SELECT {$groupExprCreated} AS label, COUNT(*) AS value FROM `{$musicTable}` WHERE created_at >= NOW() - INTERVAL {$days} DAY GROUP BY label ORDER BY label ASC");
        }

        if ($metric === 'new_anime') {
            $animeTable = $prefix . 'anime';
            if (!$this->tableExists($pdo, $animeTable)) {
                $animeTable = $prefix . 'animes';
                if (!$this->tableExists($pdo, $animeTable)) return [];
            }
            return $this->rows($pdo, "SELECT {$groupExprCreated} AS label, COUNT(*) AS value FROM `{$animeTable}` WHERE created_at >= NOW() - INTERVAL {$days} DAY GROUP BY label ORDER BY label ASC");
        }

        if ($metric === 'dau') {
            $coinTable = $prefix . 'coin_transactions';
            $orderTable = $prefix . 'orders';
            $activeUnionSql = [];
            if ($this->tableExists($pdo, $coinTable)) {
                if ($usersTableExists) {
                    $activeUnionSql[] = "SELECT t.user_id, DATE(t.created_at) AS date FROM `{$coinTable}` t LEFT JOIN `{$usersTable}` u ON u.id = t.user_id WHERE 1=1 {$excludeUsersAliasU}";
                } else {
                    $activeUnionSql[] = "SELECT user_id, DATE(created_at) AS date FROM `{$coinTable}`";
                }
            }
            if ($this->tableExists($pdo, $orderTable)) {
                if ($usersTableExists) {
                    $activeUnionSql[] = "SELECT o.user_id, DATE(o.created_at) AS date FROM `{$orderTable}` o LEFT JOIN `{$usersTable}` u ON u.id = o.user_id WHERE 1=1 {$excludeUsersAliasU}";
                    $activeUnionSql[] = "SELECT o.user_id, DATE(o.paid_at) AS date FROM `{$orderTable}` o LEFT JOIN `{$usersTable}` u ON u.id = o.user_id WHERE o.paid_at IS NOT NULL {$excludeUsersAliasU}";
                } else {
                    $activeUnionSql[] = "SELECT user_id, DATE(created_at) AS date FROM `{$orderTable}`";
                    $activeUnionSql[] = "SELECT user_id, DATE(paid_at) AS date FROM `{$orderTable}` WHERE paid_at IS NOT NULL";
                }
            }
            if ($activeUnionSql) {
                $activeSql = implode(' UNION ALL ', $activeUnionSql);
                $groupExpr = match ($group) {
                    'week' => "DATE_FORMAT(date, '%x-W%v')",
                    'month' => "DATE_FORMAT(date, '%Y-%m')",
                    default => "date",
                };
                return $this->rows($pdo, "SELECT {$groupExpr} AS label, COUNT(DISTINCT user_id) AS value FROM ({$activeSql}) a WHERE date >= DATE_SUB(CURDATE(), INTERVAL {$days} DAY) GROUP BY label ORDER BY label ASC");
            }
            return [];
        }

        if (!$usersTableExists) return [];
        return $this->rows($pdo, "SELECT {$groupExprCreated} AS label, COUNT(*) AS value FROM `{$usersTable}` WHERE created_at >= NOW() - INTERVAL {$days} DAY {$excludeUsersNoAlias} GROUP BY label ORDER BY label ASC");
    }

    /**
     * 生成“排除后台账号”的WHERE条件片段（以 AND 开头或空字符串）。
     * - 优先按 role='admin' 排除，其次按 is_admin=1 排除
     * - 同时排除当前登录后台账号ID（如果提供）
     */
    private function buildExcludeAdminCondition(PDO $pdo, string $usersTable, ?int $adminUserId = null, string $userAlias = ''): string
    {
        $alias = $userAlias !== '' ? ($userAlias . '.') : '';
        $cond = '';

        $hasRoleField = $this->columnExists($pdo, $usersTable, 'role');
        $hasIsAdminField = $this->columnExists($pdo, $usersTable, 'is_admin');

        if ($hasRoleField) {
            $cond .= " AND ({$alias}role IS NULL OR {$alias}role <> 'admin')";
        } elseif ($hasIsAdminField) {
            $cond .= " AND ({$alias}is_admin IS NULL OR {$alias}is_admin <> 1)";
        }

        if ($adminUserId) {
            $cond .= " AND {$alias}id <> " . (int)$adminUserId;
        }

        return $cond;
    }

    private function retentionWithinDays(PDO $pdo, string $usersTable, string $coinTable, int $days, string $excludeAdminCondition = ""): ?float
    {
        $cohortSize = (int)$this->scalar(
            $pdo,
            "SELECT COUNT(*) FROM `{$usersTable}` WHERE created_at >= CURDATE() - INTERVAL {$days} DAY AND created_at < CURDATE() {$excludeAdminCondition}",
            [],
            0
        );
        if ($cohortSize <= 0) return null;

        $retained = (int)$this->scalar(
            $pdo,
            "SELECT COUNT(DISTINCT u.id) FROM `{$usersTable}` u JOIN `{$coinTable}` t ON t.user_id = u.id WHERE u.created_at >= CURDATE() - INTERVAL {$days} DAY AND u.created_at < CURDATE() AND t.created_at < u.created_at + INTERVAL {$days} DAY {$excludeAdminCondition}",
            [],
            0
        );
        return round($retained / $cohortSize * 100, 2);
    }

    private function tableExists(PDO $pdo, string $tableName): bool
    {
        try {
            $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
            $stmt->execute([$tableName]);
            return (bool)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function columnExists(PDO $pdo, string $tableName, string $columnName): bool
    {
        try {
            $stmt = $pdo->prepare("SHOW COLUMNS FROM `{$tableName}` LIKE ?");
            $stmt->execute([$columnName]);
            return (bool)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function scalar(PDO $pdo, string $sql, array $params = [], mixed $default = 0): mixed
    {
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $value = $stmt->fetchColumn();
            if ($value === false || $value === null) return $default;
            return $value;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    private function rows(PDO $pdo, string $sql, array $params = []): array
    {
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * 导出统计数据
     */
    public function export()
    {
        $format = $_GET['format'] ?? 'csv'; // csv, excel, json
        $type = $_GET['type'] ?? 'users'; // users, creation, revenue, coins
        $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
        
        if ($days <= 0) $days = 30;
        if ($days > 365) $days = 365;

        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $data = [];
        $filename = '';
        
        switch ($type) {
            case 'users':
                $data = $this->exportUsers($pdo, $prefix, $days);
                $filename = 'users_' . date('YmdHis') . '.csv';
                break;
            case 'creation':
                $data = $this->exportCreation($pdo, $prefix, $days);
                $filename = 'creation_' . date('YmdHis') . '.csv';
                break;
            case 'revenue':
                $data = $this->exportRevenue($pdo, $prefix, $days);
                $filename = 'revenue_' . date('YmdHis') . '.csv';
                break;
            case 'coins':
                $data = $this->exportCoins($pdo, $prefix, $days);
                $filename = 'coins_' . date('YmdHis') . '.csv';
                break;
            default:
                $this->error('无效的导出类型', 400);
                return;
        }

        if ($format === 'json') {
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . str_replace('.csv', '.json', $filename) . '"');
            echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;
        }

        // CSV格式
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        
        // 添加BOM以支持Excel正确显示中文
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        if (!empty($data)) {
            // 输出表头
            fputcsv($output, array_keys($data[0]));
            
            // 输出数据
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }
        
        fclose($output);
        exit;
    }

    private function exportUsers(PDO $pdo, string $prefix, int $days): array
    {
        $usersTable = $prefix . 'users';
        if (!$this->tableExists($pdo, $usersTable)) {
            return [];
        }

        $adminUserId = isset($_SESSION['admin_user_id']) ? (int)$_SESSION['admin_user_id'] : null;
        $excludeAdminCondition = $this->buildExcludeAdminCondition($pdo, $usersTable, $adminUserId);

        $sql = "SELECT 
                    id,
                    username,
                    email,
                    created_at,
                    last_login_at,
                    status
                FROM `{$usersTable}` 
                WHERE created_at >= NOW() - INTERVAL {$days} DAY {$excludeAdminCondition}
                ORDER BY created_at DESC";
        
        return $this->rows($pdo, $sql);
    }

    private function exportCreation(PDO $pdo, string $prefix, int $days): array
    {
        $result = [];
        
        $booksTable = $prefix . 'books';
        if ($this->tableExists($pdo, $booksTable)) {
            $books = $this->rows($pdo, "SELECT id, title, views, likes, created_at FROM `{$booksTable}` WHERE created_at >= NOW() - INTERVAL {$days} DAY ORDER BY created_at DESC");
            foreach ($books as $book) {
                $result[] = array_merge($book, ['type' => 'book']);
            }
        }

        $musicTable = $prefix . 'music_tracks';
        if ($this->tableExists($pdo, $musicTable)) {
            $music = $this->rows($pdo, "SELECT id, title, artist, plays as views, likes, created_at FROM `{$musicTable}` WHERE created_at >= NOW() - INTERVAL {$days} DAY ORDER BY created_at DESC");
            foreach ($music as $m) {
                $result[] = array_merge($m, ['type' => 'music']);
            }
        }

        return $result;
    }

    private function exportRevenue(PDO $pdo, string $prefix, int $days): array
    {
        $orderTable = $prefix . 'orders';
        if (!$this->tableExists($pdo, $orderTable)) {
            return [];
        }

        $sql = "SELECT 
                    id,
                    order_no,
                    user_id,
                    amount,
                    status,
                    gateway,
                    paid_at,
                    created_at
                FROM `{$orderTable}` 
                WHERE created_at >= NOW() - INTERVAL {$days} DAY
                ORDER BY created_at DESC";
        
        return $this->rows($pdo, $sql);
    }

    private function exportCoins(PDO $pdo, string $prefix, int $days): array
    {
        $coinTable = $prefix . 'coin_transactions';
        if (!$this->tableExists($pdo, $coinTable)) {
            return [];
        }

        $sql = "SELECT 
                    id,
                    user_id,
                    type,
                    amount,
                    balance_after,
                    description,
                    created_at
                FROM `{$coinTable}` 
                WHERE created_at >= NOW() - INTERVAL {$days} DAY
                ORDER BY created_at DESC";
        
        return $this->rows($pdo, $sql);
    }
}
