<?php

namespace app\models;

use app\services\Database;

class CrowdfundingReward
{
    public $id;
    public $project_id;
    public $title;
    public $description;
    public $pledge_amount;
    public $limit;
    public $delivery_date;
    public $created_at;
    public $updated_at;

    public static function findById($id)
    {
        $result = Database::queryOne("SELECT * FROM " . self::tableName() . " WHERE id = ?", [$id]);
        if ($result) {
            $reward = new self();
            foreach ($result as $key => $value) {
                if (property_exists($reward, $key)) {
                    $reward->$key = $value;
                }
            }
            return $reward;
        }
        return null;
    }

    public static function findByProject($projectId)
    {
        $rewards = [];
        $results = Database::queryAll("SELECT * FROM " . self::tableName() . " WHERE project_id = ?", [$projectId]);
        foreach ($results as $result) {
            $reward = new self();
            foreach ($result as $key => $value) {
                if (property_exists($reward, $key)) {
                    $reward->$key = $value;
                }
            }
            $rewards[] = $reward;
        }
        return $rewards;
    }

    public function save()
    {
        if ($this->id) {
            // Update
            $sql = "UPDATE " . self::tableName() . " SET project_id = ?, title = ?, description = ?, pledge_amount = ?, `limit` = ?, delivery_date = ? WHERE id = ?";
            Database::execute($sql, [
                $this->project_id,
                $this->title,
                $this->description,
                $this->pledge_amount,
                $this->limit,
                $this->delivery_date,
                $this->id
            ]);
        } else {
            // Create
            $sql = "INSERT INTO " . self::tableName() . " (project_id, title, description, pledge_amount, `limit`, delivery_date) VALUES (?, ?, ?, ?, ?, ?)";
            Database::execute($sql, [
                $this->project_id,
                $this->title,
                $this->description,
                $this->pledge_amount,
                $this->limit,
                $this->delivery_date
            ]);
            $this->id = Database::pdo()->lastInsertId();
        }
        return true;
    }

    public static function tableName()
    {
        return Database::prefix() . 'crowdfunding_rewards';
    }
}
