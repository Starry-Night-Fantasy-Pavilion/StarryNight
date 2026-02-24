<?php

namespace app\models;

use app\services\Database;
use PDO;

class KnowledgeCategory
{
    /**
     * 获取所有分类
     *
     * @param bool $onlyActive
     * @return array
     */
    public static function getAll(bool $onlyActive = true): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}knowledge_categories`";
        
        if ($onlyActive) {
            $sql .= " WHERE is_active = 1";
        }
        
        $sql .= " ORDER BY parent_id ASC, sort_order ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取树形结构的分类
     *
     * @param bool $onlyActive
     * @return array
     */
    public static function getTree(bool $onlyActive = true): array
    {
        $categories = self::getAll($onlyActive);
        $tree = [];
        $indexed = [];

        // 先建立索引
        foreach ($categories as $category) {
            $category['children'] = [];
            $indexed[$category['id']] = $category;
        }

        // 构建树形结构
        foreach ($indexed as $id => $category) {
            if ($category['parent_id'] == 0) {
                $tree[$id] = $category;
            } else {
                if (isset($indexed[$category['parent_id']])) {
                    $indexed[$category['parent_id']]['children'][$id] = $category;
                }
            }
        }

        return array_values($tree);
    }

    /**
     * 根据ID获取分类
     *
     * @param int $id
     * @return array|null
     */
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}knowledge_categories` WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * 创建分类
     *
     * @param array $data
     * @return int|false
     */
    public static function create(array $data)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}knowledge_categories` 
                (name, description, icon, parent_id, sort_order, is_active) 
                VALUES (:name, :description, :icon, :parent_id, :sort_order, :is_active)";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':icon' => $data['icon'] ?? null,
            ':parent_id' => $data['parent_id'] ?? 0,
            ':sort_order' => $data['sort_order'] ?? 0,
            ':is_active' => $data['is_active'] ?? 1
        ]) ? $pdo->lastInsertId() : false;
    }

    /**
     * 更新分类
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $fields = ['name', 'description', 'icon', 'parent_id', 'sort_order', 'is_active'];
        $updates = [];
        $params = [':id' => $id];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $updates[] = "`$field` = :$field";
                $params[":$field"] = $data[$field];
            }
        }

        if (empty($updates)) {
            return false;
        }

        $sql = "UPDATE `{$prefix}knowledge_categories` SET " . implode(', ', $updates) . " WHERE `id` = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * 删除分类
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        try {
            $pdo->beginTransaction();

            // 检查是否有子分类
            $checkChildren = $pdo->prepare("SELECT COUNT(*) FROM `{$prefix}knowledge_categories` WHERE parent_id = ?");
            $checkChildren->execute([$id]);
            if ($checkChildren->fetchColumn() > 0) {
                $pdo->rollBack();
                return false; // 有子分类，不能删除
            }

            // 删除分类
            $deleteCategory = $pdo->prepare("DELETE FROM `{$prefix}knowledge_categories` WHERE id = ?");
            $deleteCategory->execute([$id]);

            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * 获取分类路径
     *
     * @param int $id
     * @return array
     */
    public static function getPath(int $id): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $path = [];
        $currentId = $id;

        while ($currentId > 0) {
            $sql = "SELECT * FROM `{$prefix}knowledge_categories` WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $currentId]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$category) {
                break;
            }

            array_unshift($path, $category);
            $currentId = $category['parent_id'];
        }

        return $path;
    }
}