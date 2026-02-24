<?php

namespace app\frontend\controller;

use Core\Controller;
use app\models\CrowdfundingProject;
use app\models\CrowdfundingPledge;
use app\models\CrowdfundingReward;
use app\models\CrowdfundingUpdate;

class CrowdfundingController extends Controller
{
    public function index()
    {
        try {
        $projects = CrowdfundingProject::findAll('status = ?', ['active']);
        $this->view('crowdfunding/index', ['projects' => $projects]);
    
        } catch (Exception $e) {
            // 记录错误日志
            error_log('Controller Error in CrowdfundingController::index: ' . $e->getMessage());
            
            // 返回友好的错误信息
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                return json_encode([
                    'success' => false,
                    'message' => '操作失败，请稍后重试',
                    'error' => DEBUG ? $e->getMessage() : '系统内部错误'
                ]);
            } else {
                // 对于普通请求，显示错误页面
                http_response_code(500);
                echo '<h1>系统错误</h1><p>抱歉，系统遇到了一些问题，请稍后重试。</p>';
                if (DEBUG) {
                    echo '<p>错误详情: ' . htmlspecialchars($e->getMessage()) . '</p>';
                }
                exit;
            }
        }
    }

    public function project($id)
    {
        $project = CrowdfundingProject::findById($id);
        if (!$project) {
            // Handle project not found
            $this->redirect('/crowdfunding');
            return;
        }
        $rewards = CrowdfundingReward::findByProject($id);
        $updates = CrowdfundingUpdate::findByProject($id);
        $pledges = CrowdfundingPledge::findByProject($id);

        $this->view('crowdfunding/project', [
            'project' => $project,
            'rewards' => $rewards,
            'updates' => $updates,
            'pledges' => $pledges
        ]);
    }

    public function create()
    {
        try {
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
        }

        if ($this->isPost()) {
            $project = new CrowdfundingProject();
            $project->user_id = $this->getUserId();
            $project->title = $_POST['title'];
            $project->description = $_POST['description'];
            $project->goal_amount = $_POST['goal_amount'];
            $project->start_date = $_POST['start_date'];
            $project->end_date = $_POST['end_date'];
            $project->status = 'pending'; // Or 'active' if approved automatically
            
            if ($project->save()) {
                // Also save rewards
                if (isset($_POST['rewards']) && is_array($_POST['rewards'])) {
                    foreach ($_POST['rewards'] as $rewardData) {
                        $reward = new CrowdfundingReward();
                        $reward->project_id = $project->id;
                        $reward->title = $rewardData['title'];
                        $reward->description = $rewardData['description'];
                        $reward->pledge_amount = $rewardData['pledge_amount'];
                        $reward->limit = $rewardData['limit'];
                        $reward->delivery_date = $rewardData['delivery_date'];
                        $reward->save();
                    }
                }
                $this->redirect('/crowdfunding/project/' . $project->id);
            } else {
                // Handle error
                $this->view('crowdfunding/create', ['error' => 'Failed to create project.']);
            }
        } else {
            $this->view('crowdfunding/create');
        }
    
        } catch (Exception $e) {
            // 记录错误日志
            error_log('Controller Error in CrowdfundingController::create: ' . $e->getMessage());
            
            // 返回友好的错误信息
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                return json_encode([
                    'success' => false,
                    'message' => '操作失败，请稍后重试',
                    'error' => DEBUG ? $e->getMessage() : '系统内部错误'
                ]);
            } else {
                // 对于普通请求，显示错误页面
                http_response_code(500);
                echo '<h1>系统错误</h1><p>抱歉，系统遇到了一些问题，请稍后重试。</p>';
                if (DEBUG) {
                    echo '<p>错误详情: ' . htmlspecialchars($e->getMessage()) . '</p>';
                }
                exit;
            }
        }
    }

    public function pledge($projectId)
    {
        if (!$this->isLoggedIn()) {
            $this->json(['success' => false, 'message' => 'Please login to pledge.']);
            return;
        }

        if ($this->isPost()) {
            $project = CrowdfundingProject::findById($projectId);
            if (!$project || $project->status !== 'active') {
                $this->json(['success' => false, 'message' => 'Project not available for pledging.']);
                return;
            }

            $amount = $_POST['amount'];
            $rewardId = $_POST['reward_id'] ?? null;

            // Basic validation
            if (!is_numeric($amount) || $amount <= 0) {
                $this->json(['success' => false, 'message' => 'Invalid pledge amount.']);
                return;
            }
            
            // More logic here: check against reward amount, user balance, etc.
            // For now, we assume payment is handled elsewhere and we just record the pledge.

            $pledge = new CrowdfundingPledge();
            $pledge->user_id = $this->getUserId();
            $pledge->project_id = $projectId;
            $pledge->reward_id = $rewardId;
            $pledge->amount = $amount;
            $pledge->status = 'successful'; // Assuming payment is instant

            if ($pledge->save()) {
                // Update project's current amount
                $project->current_amount += $amount;
                $project->save();
                $this->json(['success' => true, 'message' => 'Pledge successful!']);
            } else {
                $this->json(['success' => false, 'message' => 'Failed to record pledge.']);
            }
        }
    }
}
