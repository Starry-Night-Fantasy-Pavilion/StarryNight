<?php
namespace app\admin\controller;

use app\services\Database;
use PDO;

class OperationsController extends BaseController
{
    public function index()
    {
        $title = '运营中心';
        $currentPage = 'operations';
        
        ob_start();
        require __DIR__ . '/../views/operations/index.php';
        $content = ob_get_clean();

        require __DIR__ . '/../views/layout.php';
    }

    public function newUser()
    {
        $title = '新增用户';
        $currentPage = 'operations';
        $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
        $group = $_GET['group'] ?? 'day';
        
        $data = $this->getNewUserData($days, $group);
        
        ob_start();
        require __DIR__ . '/../views/operations/new_user.php';
        $content = ob_get_clean();

        require __DIR__ . '/../views/layout.php';
    }

    public function dau()
    {
        $title = '日活用户';
        $currentPage = 'operations';
        $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
        $group = $_GET['group'] ?? 'day';
        
        $data = $this->getDauData($days, $group);
        
        ob_start();
        require __DIR__ . '/../views/operations/dau.php';
        $content = ob_get_clean();

        require __DIR__ . '/../views/layout.php';
    }

    public function coinSpend()
    {
        $title = '星夜币消耗';
        $currentPage = 'operations';
        $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
        $group = $_GET['group'] ?? 'day';
        
        $data = $this->getCoinSpendData($days, $group);
        
        ob_start();
        require __DIR__ . '/../views/operations/coin_spend.php';
        $content = ob_get_clean();

        require __DIR__ . '/../views/layout.php';
    }

    public function revenue()
    {
        $title = '收入';
        $currentPage = 'operations';
        $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
        $group = $_GET['group'] ?? 'day';
        
        $data = $this->getRevenueData($days, $group);
        
        ob_start();
        require __DIR__ . '/../views/operations/revenue.php';
        $content = ob_get_clean();

        require __DIR__ . '/../views/layout.php';
    }

    public function newNovel()
    {
        $title = '新增小说';
        $currentPage = 'operations';
        $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
        $group = $_GET['group'] ?? 'day';
        
        $data = $this->getNewNovelData($days, $group);
        
        ob_start();
        require __DIR__ . '/../views/operations/new_novel.php';
        $content = ob_get_clean();

        require __DIR__ . '/../views/layout.php';
    }

    public function newMusic()
    {
        $title = '新增音乐';
        $currentPage = 'operations';
        $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
        $group = $_GET['group'] ?? 'day';
        
        $data = $this->getNewMusicData($days, $group);
        
        ob_start();
        require __DIR__ . '/../views/operations/new_music.php';
        $content = ob_get_clean();

        require __DIR__ . '/../views/layout.php';
    }

    public function newAnime()
    {
        $title = '新增动漫';
        $currentPage = 'operations';
        $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
        $group = $_GET['group'] ?? 'day';
        
        $data = $this->getNewAnimeData($days, $group);
        
        ob_start();
        require __DIR__ . '/../views/operations/new_anime.php';
        $content = ob_get_clean();

        require __DIR__ . '/../views/layout.php';
    }

    private function getNewUserData(int $days, string $group): array
    {
        if ($days <= 0) $days = 30;
        if ($days > 365) $days = 365;
        if (!in_array($group, ['day', 'week', 'month'], true)) $group = 'day';

        $data = [
            'total' => 0,
            'today' => 0,
            'yesterday' => 0,
            'growth_rate' => 0,
            'chart_data' => []
        ];

        try {
            $pdo = Database::pdo();
            $prefix = Database::prefix();
            $usersTable = $prefix . 'users';
            
            if (!$this->tableExists($pdo, $usersTable)) {
                return $data;
            }

            $adminUserId = isset($_SESSION['admin_user_id']) ? (int)$_SESSION['admin_user_id'] : null;
            $excludeAdminCondition = $this->buildExcludeAdminCondition($pdo, $usersTable, $adminUserId);

            $data['total'] = (int)$this->scalar($pdo, "SELECT COUNT(*) FROM `{$usersTable}` WHERE 1=1 {$excludeAdminCondition}", [], 0);
            $data['today'] = (int)$this->scalar($pdo, "SELECT COUNT(*) FROM `{$usersTable}` WHERE created_at >= CURDATE() {$excludeAdminCondition}", [], 0);
            $data['yesterday'] = (int)$this->scalar($pdo, "SELECT COUNT(*) FROM `{$usersTable}` WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND created_at < CURDATE() {$excludeAdminCondition}", [], 0);
            
            if ($data['yesterday'] > 0) {
                $data['growth_rate'] = round((($data['today'] - $data['yesterday']) / $data['yesterday']) * 100, 2);
            }

            $groupExpr = match ($group) {
                'week' => "DATE_FORMAT(created_at, '%x-W%v')",
                'month' => "DATE_FORMAT(created_at, '%Y-%m')",
                default => "DATE(created_at)",
            };

            $data['chart_data'] = $this->rows(
                $pdo,
                "SELECT {$groupExpr} AS label, COUNT(*) AS value FROM `{$usersTable}` WHERE created_at >= NOW() - INTERVAL {$days} DAY {$excludeAdminCondition} GROUP BY label ORDER BY label ASC"
            );
        } catch (\Throwable $e) {
            error_log('OperationsController::getNewUserData error: ' . $e->getMessage());
        }

        return $data;
    }

    private function getDauData(int $days, string $group): array
    {
        if ($days <= 0) $days = 30;
        if ($days > 365) $days = 365;
        if (!in_array($group, ['day', 'week', 'month'], true)) $group = 'day';

        $data = [
            'today' => 0,
            'yesterday' => 0,
            'growth_rate' => 0,
            'chart_data' => []
        ];

        try {
            $pdo = Database::pdo();
            $prefix = Database::prefix();
            $coinTable = $prefix . 'coin_transactions';
            $orderTable = $prefix . 'orders';
            $usersTable = $prefix . 'users';
            
            $adminUserId = isset($_SESSION['admin_user_id']) ? (int)$_SESSION['admin_user_id'] : null;
            $excludeAdminUsersU = '';
            if ($this->tableExists($pdo, $usersTable)) {
                $excludeAdminUsersU = $this->buildExcludeAdminCondition($pdo, $usersTable, $adminUserId, 'u');
            }

            $activeUnionSql = [];
            if ($this->tableExists($pdo, $coinTable)) {
                if ($this->tableExists($pdo, $usersTable)) {
                    $activeUnionSql[] = "SELECT t.user_id, DATE(t.created_at) AS date FROM `{$coinTable}` t LEFT JOIN `{$usersTable}` u ON u.id = t.user_id WHERE 1=1 {$excludeAdminUsersU}";
                } else {
                    $activeUnionSql[] = "SELECT user_id, DATE(created_at) AS date FROM `{$coinTable}`";
                }
            }
            if ($this->tableExists($pdo, $orderTable)) {
                if ($this->tableExists($pdo, $usersTable)) {
                    $activeUnionSql[] = "SELECT o.user_id, DATE(o.created_at) AS date FROM `{$orderTable}` o LEFT JOIN `{$usersTable}` u ON u.id = o.user_id WHERE 1=1 {$excludeAdminUsersU}";
                    $activeUnionSql[] = "SELECT o.user_id, DATE(o.paid_at) AS date FROM `{$orderTable}` o LEFT JOIN `{$usersTable}` u ON u.id = o.user_id WHERE o.paid_at IS NOT NULL {$excludeAdminUsersU}";
                } else {
                    $activeUnionSql[] = "SELECT user_id, DATE(created_at) AS date FROM `{$orderTable}`";
                    $activeUnionSql[] = "SELECT user_id, DATE(paid_at) AS date FROM `{$orderTable}` WHERE paid_at IS NOT NULL";
                }
            }

            if ($activeUnionSql) {
                $activeSql = implode(' UNION ALL ', $activeUnionSql);
                $data['today'] = (int)$this->scalar($pdo, "SELECT COUNT(DISTINCT user_id) FROM ({$activeSql}) a WHERE a.date = CURDATE()", [], 0);
                $data['yesterday'] = (int)$this->scalar($pdo, "SELECT COUNT(DISTINCT user_id) FROM ({$activeSql}) a WHERE a.date = DATE_SUB(CURDATE(), INTERVAL 1 DAY)", [], 0);
                
                if ($data['yesterday'] > 0) {
                    $data['growth_rate'] = round((($data['today'] - $data['yesterday']) / $data['yesterday']) * 100, 2);
                }

                $groupExpr = match ($group) {
                    'week' => "DATE_FORMAT(date, '%x-W%v')",
                    'month' => "DATE_FORMAT(date, '%Y-%m')",
                    default => "date",
                };

                $data['chart_data'] = $this->rows($pdo, "SELECT {$groupExpr} AS label, COUNT(DISTINCT user_id) AS value FROM ({$activeSql}) a WHERE date >= DATE_SUB(CURDATE(), INTERVAL {$days} DAY) GROUP BY label ORDER BY label ASC");
            }
        } catch (\Throwable $e) {
            error_log('OperationsController::getDauData error: ' . $e->getMessage());
        }

        return $data;
    }

    private function getCoinSpendData(int $days, string $group): array
    {
        if ($days <= 0) $days = 30;
        if ($days > 365) $days = 365;
        if (!in_array($group, ['day', 'week', 'month'], true)) $group = 'day';

        $data = [
            'total' => 0,
            'today' => 0,
            'yesterday' => 0,
            'growth_rate' => 0,
            'chart_data' => []
        ];

        try {
            $pdo = Database::pdo();
            $prefix = Database::prefix();
            $coinTable = $prefix . 'coin_transactions';
            
            if (!$this->tableExists($pdo, $coinTable)) {
                return $data;
            }

            $usersTable = $prefix . 'users';
            $adminUserId = isset($_SESSION['admin_user_id']) ? (int)$_SESSION['admin_user_id'] : null;
            $excludeAdminUsersU = '';
            if ($this->tableExists($pdo, $usersTable)) {
                $excludeAdminUsersU = $this->buildExcludeAdminCondition($pdo, $usersTable, $adminUserId, 'u');
            }

            if ($this->tableExists($pdo, $usersTable)) {
                $data['total'] = (float)$this->scalar($pdo, "SELECT COALESCE(SUM(ABS(t.amount)), 0) FROM `{$coinTable}` t LEFT JOIN `{$usersTable}` u ON u.id = t.user_id WHERE t.type = 'spend' {$excludeAdminUsersU}", [], 0);
                $data['today'] = (float)$this->scalar($pdo, "SELECT COALESCE(SUM(ABS(t.amount)), 0) FROM `{$coinTable}` t LEFT JOIN `{$usersTable}` u ON u.id = t.user_id WHERE t.type = 'spend' AND DATE(t.created_at) = CURDATE() {$excludeAdminUsersU}", [], 0);
                $data['yesterday'] = (float)$this->scalar($pdo, "SELECT COALESCE(SUM(ABS(t.amount)), 0) FROM `{$coinTable}` t LEFT JOIN `{$usersTable}` u ON u.id = t.user_id WHERE t.type = 'spend' AND DATE(t.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) {$excludeAdminUsersU}", [], 0);
            } else {
                $data['total'] = (float)$this->scalar($pdo, "SELECT COALESCE(SUM(ABS(amount)), 0) FROM `{$coinTable}` WHERE type = 'spend'", [], 0);
                $data['today'] = (float)$this->scalar($pdo, "SELECT COALESCE(SUM(ABS(amount)), 0) FROM `{$coinTable}` WHERE type = 'spend' AND DATE(created_at) = CURDATE()", [], 0);
                $data['yesterday'] = (float)$this->scalar($pdo, "SELECT COALESCE(SUM(ABS(amount)), 0) FROM `{$coinTable}` WHERE type = 'spend' AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)", [], 0);
            }

            if ($data['yesterday'] > 0) {
                $data['growth_rate'] = round((($data['today'] - $data['yesterday']) / $data['yesterday']) * 100, 2);
            }

            $groupExpr = match ($group) {
                'week' => "DATE_FORMAT(created_at, '%x-W%v')",
                'month' => "DATE_FORMAT(created_at, '%Y-%m')",
                default => "DATE(created_at)",
            };

            if ($this->tableExists($pdo, $usersTable)) {
                $data['chart_data'] = $this->rows($pdo, "SELECT {$groupExpr} AS label, COALESCE(SUM(ABS(t.amount)), 0) AS value FROM `{$coinTable}` t LEFT JOIN `{$usersTable}` u ON u.id = t.user_id WHERE t.type = 'spend' AND t.created_at >= NOW() - INTERVAL {$days} DAY {$excludeAdminUsersU} GROUP BY label ORDER BY label ASC");
            } else {
                $data['chart_data'] = $this->rows($pdo, "SELECT {$groupExpr} AS label, COALESCE(SUM(ABS(amount)), 0) AS value FROM `{$coinTable}` WHERE type = 'spend' AND created_at >= NOW() - INTERVAL {$days} DAY GROUP BY label ORDER BY label ASC");
            }
        } catch (\Throwable $e) {
            error_log('OperationsController::getCoinSpendData error: ' . $e->getMessage());
        }

        return $data;
    }

    private function getRevenueData(int $days, string $group): array
    {
        if ($days <= 0) $days = 30;
        if ($days > 365) $days = 365;
        if (!in_array($group, ['day', 'week', 'month'], true)) $group = 'day';

        $data = [
            'total' => 0,
            'today' => 0,
            'yesterday' => 0,
            'growth_rate' => 0,
            'chart_data' => []
        ];

        try {
            $pdo = Database::pdo();
            $prefix = Database::prefix();
            $orderTable = $prefix . 'orders';
            
            if (!$this->tableExists($pdo, $orderTable)) {
                return $data;
            }

            $data['total'] = (float)$this->scalar($pdo, "SELECT COALESCE(SUM(amount), 0) FROM `{$orderTable}` WHERE status = 'completed'", [], 0);
            $data['today'] = (float)$this->scalar($pdo, "SELECT COALESCE(SUM(amount), 0) FROM `{$orderTable}` WHERE status = 'completed' AND DATE(paid_at) = CURDATE()", [], 0);
            $data['yesterday'] = (float)$this->scalar($pdo, "SELECT COALESCE(SUM(amount), 0) FROM `{$orderTable}` WHERE status = 'completed' AND DATE(paid_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)", [], 0);
            
            if ($data['yesterday'] > 0) {
                $data['growth_rate'] = round((($data['today'] - $data['yesterday']) / $data['yesterday']) * 100, 2);
            }

            $groupExpr = match ($group) {
                'week' => "DATE_FORMAT(paid_at, '%x-W%v')",
                'month' => "DATE_FORMAT(paid_at, '%Y-%m')",
                default => "DATE(paid_at)",
            };

            $data['chart_data'] = $this->rows($pdo, "SELECT {$groupExpr} AS label, COALESCE(SUM(amount), 0) AS value FROM `{$orderTable}` WHERE status = 'completed' AND paid_at IS NOT NULL AND paid_at >= NOW() - INTERVAL {$days} DAY GROUP BY label ORDER BY label ASC");
        } catch (\Throwable $e) {
            error_log('OperationsController::getRevenueData error: ' . $e->getMessage());
        }

        return $data;
    }

    private function getNewNovelData(int $days, string $group): array
    {
        return $this->getCreationData('books', $days, $group);
    }

    private function getNewMusicData(int $days, string $group): array
    {
        return $this->getCreationData('music_tracks', $days, $group);
    }

    private function getNewAnimeData(int $days, string $group): array
    {
        $data = $this->getCreationData('anime', $days, $group);
        if (empty($data['chart_data'])) {
            $data = $this->getCreationData('animes', $days, $group);
        }
        return $data;
    }

    private function getCreationData(string $tableName, int $days, string $group): array
    {
        if ($days <= 0) $days = 30;
        if ($days > 365) $days = 365;
        if (!in_array($group, ['day', 'week', 'month'], true)) $group = 'day';

        $data = [
            'total' => 0,
            'today' => 0,
            'yesterday' => 0,
            'growth_rate' => 0,
            'chart_data' => []
        ];

        try {
            $pdo = Database::pdo();
            $prefix = Database::prefix();
            $table = $prefix . $tableName;
            
            if (!$this->tableExists($pdo, $table)) {
                return $data;
            }

            $data['total'] = (int)$this->scalar($pdo, "SELECT COUNT(*) FROM `{$table}`", [], 0);
            $data['today'] = (int)$this->scalar($pdo, "SELECT COUNT(*) FROM `{$table}` WHERE created_at >= CURDATE()", [], 0);
            $data['yesterday'] = (int)$this->scalar($pdo, "SELECT COUNT(*) FROM `{$table}` WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND created_at < CURDATE()", [], 0);
            
            if ($data['yesterday'] > 0) {
                $data['growth_rate'] = round((($data['today'] - $data['yesterday']) / $data['yesterday']) * 100, 2);
            }

            $groupExpr = match ($group) {
                'week' => "DATE_FORMAT(created_at, '%x-W%v')",
                'month' => "DATE_FORMAT(created_at, '%Y-%m')",
                default => "DATE(created_at)",
            };

            $data['chart_data'] = $this->rows($pdo, "SELECT {$groupExpr} AS label, COUNT(*) AS value FROM `{$table}` WHERE created_at >= NOW() - INTERVAL {$days} DAY GROUP BY label ORDER BY label ASC");
        } catch (\Throwable $e) {
            error_log('OperationsController::getCreationData error: ' . $e->getMessage());
        }

        return $data;
    }

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
}
