<?php

namespace app\models;

use app\services\Database;

class CrowdfundingPledge
{
    public $id;
    public $user_id;
    public $project_id;
    public $reward_id;
    public $amount;
    public $status;
    public $created_at;
    public $updated_at;

    public static function findById($id)
    {
        $result = Database::queryOne("SELECT * FROM " . self::tableName() . " WHERE id = ?", [$id]);
        if ($result) {
            $pledge = new self();
            foreach ($result as $key => $value) {
                if (property_exists($pledge, $key)) {
                    $pledge->$key = $value;
                }
            }
            return $pledge;
        }
        return null;
    }

    public static function findByProject($projectId)
    {
        $pledges = [];
        $results = Database::queryAll("SELECT * FROM " . self::tableName() . " WHERE project_id = ?", [$projectId]);
        foreach ($results as $result) {
            $pledge = new self();
            foreach ($result as $key => $value) {
                if (property_exists($pledge, $key)) {
                    $pledge->$key = $value;
                }
            }
            $pledges[] = $pledge;
        }
        return $pledges;
    }

    public function save()
    {
        if ($this->id) {
            // Update
            $sql = "UPDATE " . self::tableName() . " SET user_id = ?, project_id = ?, reward_id = ?, amount = ?, status = ? WHERE id = ?";
            Database::execute($sql, [
                $this->user_id,
                $this->project_id,
                $this->reward_id,
                $this->amount,
                $this->status,
                $this->id
            ]);
        } else {
            // Create
            $sql = "INSERT INTO " . self::tableName() . " (user_id, project_id, reward_id, amount, status) VALUES (?, ?, ?, ?, ?)";
            Database::execute($sql, [
                $this->user_id,
                $this->project_id,
                $this->reward_id,
                $this->amount,
                $this->status
            ]);
            $this->id = Database::pdo()->lastInsertId();
        }
        return true;
    }

    public static function tableName()
    {
        return Database::prefix() . 'crowdfunding_pledges';
    }
}
