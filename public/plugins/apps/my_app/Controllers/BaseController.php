<?php
namespace plugins\apps\my_app\Controllers;

use PDO;
use PDOException;

abstract class BaseController
{
    protected ?PDO $db = null;
    protected string $db_prefix = '';

    public function __construct()
    {
        $this->db_prefix = (string)get_env('DB_PREFIX', 'sn_');

        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                get_env('DB_HOST'),
                (int)get_env('DB_PORT', 3306),
                get_env('DB_DATABASE')
            );
            $this->db = new PDO(
                $dsn,
                get_env('DB_USERNAME'),
                get_env('DB_PASSWORD'),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            die('数据库连接失败。');
        }
    }

    protected function render(string $view, array $data = []): void
    {
        $viewPath = realpath(__DIR__ . '/../views/' . $view . '.php');
        if (!$viewPath || !is_readable($viewPath)) {
            http_response_code(500);
            echo '视图不存在。';
            return;
        }
        extract($data);
        ob_start();
        require $viewPath;
        echo ob_get_clean();
    }

    protected function getTableName(string $name): string
    {
        return '`' . $this->db_prefix . $name . '`';
    }

    protected function query(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    protected function execute(string $sql, array $params = []): int
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    protected function insert(string $table, array $data): ?int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO {$this->getTableName($table)} ($columns) VALUES ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));
        return $this->db->lastInsertId();
    }

    protected function update(string $table, array $data, array $where): int
    {
        $setParts = [];
        foreach ($data as $column => $value) {
            $setParts[] = "$column = ?";
        }
        $setClause = implode(', ', $setParts);
        
        $whereParts = [];
        foreach ($where as $column => $value) {
            $whereParts[] = "$column = ?";
        }
        $whereClause = implode(' AND ', $whereParts);
        
        $sql = "UPDATE {$this->getTableName($table)} SET $setClause WHERE $whereClause";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge(array_values($data), array_values($where)));
        return $stmt->rowCount();
    }

    protected function delete(string $table, array $where): int
    {
        $whereParts = [];
        foreach ($where as $column => $value) {
            $whereParts[] = "$column = ?";
        }
        $whereClause = implode(' AND ', $whereParts);
        
        $sql = "DELETE FROM {$this->getTableName($table)} WHERE $whereClause";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($where));
        return $stmt->rowCount();
    }

    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

