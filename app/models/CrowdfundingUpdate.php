<?php

namespace app\models;

use app\services\Database;

class CrowdfundingUpdate
{
    public $id;
    public $project_id;
    public $title;
    public $content;
    public $created_at;

    public static function findById($id)
    {
        $result = Database::queryOne("SELECT * FROM " . self::tableName() . " WHERE id = ?", [$id]);
        if ($result) {
            $update = new self();
            foreach ($result as $key => $value) {
                if (property_exists($update, $key)) {
                    $update->$key = $value;
                }
            }
            return $update;
        }
        return null;
    }

    public static function findByProject($projectId)
    {
        $updates = [];
        $results = Database::queryAll("SELECT * FROM " . self::tableName() . " WHERE project_id = ? ORDER BY created_at DESC", [$projectId]);
        foreach ($results as $result) {
            $update = new self();
            foreach ($result as $key => $value) {
                if (property_exists($update, $key)) {
                    $update->$key = $value;
                }
            }
            $updates[] = $update;
        }
        return $updates;
    }

    public function save()
    {
        if ($this->id) {
            // Update
            $sql = "UPDATE " . self::tableName() . " SET project_id = ?, title = ?, content = ? WHERE id = ?";
            Database::execute($sql, [
                $this->project_id,
                $this->title,
                $this->content,
                $this->id
            ]);
        } else {
            // Create
            $sql = "INSERT INTO " . self::tableName() . " (project_id, title, content) VALUES (?, ?, ?)";
            Database::execute($sql, [
                $this->project_id,
                $this->title,
                $this->content
            ]);
            $this->id = Database::pdo()->lastInsertId();
        }
        return true;
    }

    public static function tableName()
    {
        return Database::prefix() . 'crowdfunding_updates';
    }
}
