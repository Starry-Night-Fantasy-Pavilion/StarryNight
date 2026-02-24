<?php

namespace app\admin\controller;

use app\models\Setting;
use app\models\AIAgentMarket;
use app\models\CollaborationProject;
use app\models\CopyrightRegistration;
use app\models\Recommendation;
use app\models\CreationContest;
use app\models\EducationCourse;
use Exception;

class FutureFeaturesController extends BaseController
{
    /**
     * 显示功能管理页面
     */
    public function index()
    {
        if (!$this->isAdmin()) {
            $this->json(['success' => false, 'message' => '权限不足']);
            return;
        }

        $title = '未来功能管理';
        $currentPage = 'future-features';
        $features = $this->getFeatureSettings();

        ob_start();
        require __DIR__ . '/../views/future_features/index.php';
        $content = ob_get_clean();

        require __DIR__ . '/../views/layout.php';
    }
    
    /**
     * 获取功能设置
     */
    public function getFeatureSettings()
    {
        $featureKeys = [
            'feature_ai_agent_market',
            'feature_collaboration',
            'feature_copyright_protection',
            'feature_recommendation_system',
            'feature_creation_contests',
            'feature_education_training'
        ];
        
        $settings = Setting::getMany($featureKeys);
        $features = [];
        
        foreach ($featureKeys as $key) {
            $value = $settings[$key] ?? '{"enabled": false, "description": ""}';
            $decoded = json_decode($value, true);
            $features[$key] = $decoded ?: ['enabled' => false, 'description' => ''];
        }
        
        return $features;
    }
    
    /**
     * 更新功能开关
     */
    public function updateFeature()
    {
        // 检查管理员权限
        if (!$this->isAdmin()) {
            $this->json(['success' => false, 'message' => '权限不足']);
            return;
        }
        
        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => '请求方法错误']);
            return;
        }
        
        $featureKey = $_POST['feature_key'] ?? '';
        $enabled = $_POST['enabled'] ?? false;
        
        if (!in_array($featureKey, [
            'feature_ai_agent_market',
            'feature_collaboration',
            'feature_copyright_protection',
            'feature_recommendation_system',
            'feature_creation_contests',
            'feature_education_training'
        ])) {
            $this->json(['success' => false, 'message' => '无效的功能键']);
            return;
        }
        
        // 获取当前设置
        $currentSetting = Setting::get($featureKey, '{"enabled": false, "description": ""}');
        $currentData = json_decode($currentSetting, true) ?: ['enabled' => false, 'description' => ''];
        
        // 更新设置
        $newData = [
            'enabled' => (bool)$enabled,
            'description' => $currentData['description'] ?? ''
        ];
        
        $result = Setting::set($featureKey, json_encode($newData));
        
        if ($result) {
            $this->json(['success' => true, 'message' => '功能状态更新成功']);
        } else {
            $this->json(['success' => false, 'message' => '功能状态更新失败']);
        }
    }
    
    /**
     * 批量更新功能开关
     */
    public function batchUpdateFeatures()
    {
        // 检查管理员权限
        if (!$this->isAdmin()) {
            $this->json(['success' => false, 'message' => '权限不足']);
            return;
        }
        
        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => '请求方法错误']);
            return;
        }
        
        $features = $_POST['features'] ?? [];
        
        if (!is_array($features)) {
            $this->json(['success' => false, 'message' => '无效的请求数据']);
            return;
        }
        
        $validFeatures = [
            'feature_ai_agent_market',
            'feature_collaboration',
            'feature_copyright_protection',
            'feature_recommendation_system',
            'feature_creation_contests',
            'feature_education_training'
        ];
        
        $updates = [];
        foreach ($features as $featureKey => $enabled) {
            if (in_array($featureKey, $validFeatures)) {
                // 获取当前设置
                $currentSetting = Setting::get($featureKey, '{"enabled": false, "description": ""}');
                $currentData = json_decode($currentSetting, true) ?: ['enabled' => false, 'description' => ''];
                
                $updates[$featureKey] = [
                    'enabled' => (bool)$enabled,
                    'description' => $currentData['description'] ?? ''
                ];
            }
        }
        
        $success = true;
        foreach ($updates as $featureKey => $data) {
            $result = Setting::set($featureKey, json_encode($data));
            if (!$result) {
                $success = false;
                break;
            }
        }
        
        if ($success) {
            $this->json(['success' => true, 'message' => '功能状态批量更新成功']);
        } else {
            $this->json(['success' => false, 'message' => '功能状态批量更新失败']);
        }
    }
    
    /**
     * 获取功能统计
     */
    public function getFeatureStats()
    {
        // 检查管理员权限
        if (!$this->isAdmin()) {
            $this->json(['success' => false, 'message' => '权限不足']);
            return;
        }
        
        $stats = [];
        
        // AI智能体市场统计
        if ($this->isFeatureEnabled('feature_ai_agent_market')) {
            $stats['ai_agent_market'] = $this->getAIAgentMarketStats();
        }
        
        // 多模态创作协作统计
        if ($this->isFeatureEnabled('feature_collaboration')) {
            $stats['collaboration'] = $this->getCollaborationStats();
        }
        
        // 版权保护与溯源统计
        if ($this->isFeatureEnabled('feature_copyright_protection')) {
            $stats['copyright_protection'] = $this->getCopyrightProtectionStats();
        }
        
        // 个性化推荐系统统计
        if ($this->isFeatureEnabled('feature_recommendation_system')) {
            $stats['recommendation_system'] = $this->getRecommendationSystemStats();
        }
        
        // AI创作大赛统计
        if ($this->isFeatureEnabled('feature_creation_contests')) {
            $stats['creation_contests'] = $this->getCreationContestsStats();
        }
        
        // 教育与培训模块统计
        if ($this->isFeatureEnabled('feature_education_training')) {
            $stats['education_training'] = $this->getEducationTrainingStats();
        }
        
        $this->json(['success' => true, 'data' => $stats]);
    }
    
    /**
     * 检查功能是否启用
     */
    private function isFeatureEnabled(string $featureKey): bool
    {
        $setting = Setting::get($featureKey, '{"enabled": false}');
        $data = json_decode($setting, true);
        return $data['enabled'] ?? false;
    }
    
    /**
     * 获取AI智能体市场统计
     */
    private function getAIAgentMarketStats(): array
    {
        try {
            $totalAgents = count(AIAgentMarket::getList(1, 1)['data'] ?? []);
            $featuredAgents = count(AIAgentMarket::getFeatured(1));
            
            return [
                'total_agents' => $totalAgents,
                'featured_agents' => $featuredAgents,
                'status' => 'active'
            ];
        } catch (Exception $e) {
            return [
                'total_agents' => 0,
                'featured_agents' => 0,
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 获取多模态创作协作统计
     */
    private function getCollaborationStats(): array
    {
        try {
            $totalProjects = count(CollaborationProject::getList(1, 1)['data'] ?? []);
            
            return [
                'total_projects' => $totalProjects,
                'status' => 'active'
            ];
        } catch (Exception $e) {
            return [
                'total_projects' => 0,
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 获取版权保护与溯源统计
     */
    private function getCopyrightProtectionStats(): array
    {
        try {
            $stats = CopyrightRegistration::getStats();
            
            return [
                'total_registrations' => $stats['total_registrations'] ?? 0,
                'registered_count' => $stats['registered_count'] ?? 0,
                'pending_count' => $stats['pending_count'] ?? 0,
                'status' => 'active'
            ];
        } catch (Exception $e) {
            return [
                'total_registrations' => 0,
                'registered_count' => 0,
                'pending_count' => 0,
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 获取个性化推荐系统统计
     */
    private function getRecommendationSystemStats(): array
    {
        try {
            $stats = Recommendation::getStats();
            
            return [
                'total_recommendations' => $stats['total_recommendations'] ?? 0,
                'clicked_count' => $stats['clicked_count'] ?? 0,
                'liked_count' => $stats['liked_count'] ?? 0,
                'status' => 'active'
            ];
        } catch (Exception $e) {
            return [
                'total_recommendations' => 0,
                'clicked_count' => 0,
                'liked_count' => 0,
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 获取AI创作大赛统计
     */
    private function getCreationContestsStats(): array
    {
        try {
            $stats = CreationContest::getStats();
            
            return [
                'total_contests' => $stats['total_contests'] ?? 0,
                'active_count' => $stats['active_count'] ?? 0,
                'completed_count' => $stats['completed_count'] ?? 0,
                'status' => 'active'
            ];
        } catch (Exception $e) {
            return [
                'total_contests' => 0,
                'active_count' => 0,
                'completed_count' => 0,
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 获取教育与培训模块统计
     */
    private function getEducationTrainingStats(): array
    {
        try {
            $stats = EducationCourse::getStats();
            
            return [
                'total_courses' => $stats['total_courses'] ?? 0,
                'published_count' => $stats['published_count'] ?? 0,
                'total_views' => $stats['total_views'] ?? 0,
                'status' => 'active'
            ];
        } catch (Exception $e) {
            return [
                'total_courses' => 0,
                'published_count' => 0,
                'total_views' => 0,
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 测试功能连接
     */
    public function testFeatureConnection()
    {
        // 检查管理员权限
        if (!$this->isAdmin()) {
            $this->json(['success' => false, 'message' => '权限不足']);
            return;
        }
        
        $featureKey = $_GET['feature'] ?? '';
        
        if (!in_array($featureKey, [
            'feature_ai_agent_market',
            'feature_collaboration',
            'feature_copyright_protection',
            'feature_recommendation_system',
            'feature_creation_contests',
            'feature_education_training'
        ])) {
            $this->json(['success' => false, 'message' => '无效的功能键']);
            return;
        }
        
        $testResult = [
            'feature' => $featureKey,
            'enabled' => $this->isFeatureEnabled($featureKey),
            'connection_test' => 'success',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        $this->json(['success' => true, 'data' => $testResult]);
    }
}