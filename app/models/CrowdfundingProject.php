<?php

namespace app\models;

use app\services\Database;

class CrowdfundingProject
{
    public $id;
    public $user_id;
    public $title;
    public $description;
    public $goal_amount;
    public $current_amount;
    public $start_date;
    public $end_date;
    public $status;
    public $created_at;
    public $updated_at;

    public static function findById($id)
    {
        $result = Database::queryOne("SELECT * FROM " . self::tableName() . " WHERE id = ?", [$id]);
        if ($result) {
            $project = new self();
            foreach ($result as $key => $value) {
                if (property_exists($project, $key)) {
                    $project->$key = $value;
                }
            }
            return $project;
        }
        return null;
    }

    public static function findAll($conditions = '', $params = [])
    {
        $projects = [];
        $sql = "SELECT * FROM " . self::tableName();
        if ($conditions) {
            $sql .= " WHERE " . $conditions;
        }
        $results = Database::queryAll($sql, $params);
        foreach ($results as $result) {
            $project = new self();
            foreach ($result as $key => $value) {
                if (property_exists($project, $key)) {
                    $project->$key = $value;
                }
            }
            $projects[] = $project;
        }
        return $projects;
    }

    public function save()
    {
        if ($this->id) {
            // Update
            $sql = "UPDATE " . self::tableName() . " SET user_id = ?, title = ?, description = ?, goal_amount = ?, current_amount = ?, start_date = ?, end_date = ?, status = ? WHERE id = ?";
            Database::execute($sql, [
                $this->user_id,
                $this->title,
                $this->description,
                $this->goal_amount,
                $this->current_amount,
                $this->start_date,
                $this->end_date,
                $this->status,
                $this->id
            ]);
        } else {
            // Create
            $sql = "INSERT INTO " . self::tableName() . " (user_id, title, description, goal_amount, current_amount, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            Database::execute($sql, [
                $this->user_id,
                $this->title,
                $this->description,
                $this->goal_amount,
                $this->current_amount,
                $this->start_date,
                $this->end_date,
                $this->status
            ]);
            $this->id = Database::pdo()->lastInsertId();
        }
        return true;
    }

    public static function tableName()
    {
        return Database::prefix() . 'crowdfunding_projects';
    }
}
