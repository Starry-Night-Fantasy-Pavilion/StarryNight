<?php

namespace app\services;

use app\models\UserConsistencyConfig;
use app\models\ConsistencyReport;
use app\models\ConsistencyConflict;
use app\models\CoreSetting;
use app\models\VectorDbConfig;
use app\models\EmbeddingModelConfig;
use app\models\VectorDbUsageLog;
use app\services\Database;
use StarryNightEngine\StarryNightEngine;
use StarryNightEngine\Contracts\EngineRequest;
use StarryNightEngine\Contracts\EngineResponse;
use StarryNightEngine\Contracts\ConsistencyReport as EngineConsistencyReport;
use StarryNightEngine\Contracts\UserTier;
use StarryNightEngine\Services\EnhancedRAGService;
use StarryNightEngine\Services\VectorEmbeddingService;

/**
 * 一致性检查集成服务
 * 
 * 将数据库中的一致性检查配置与星夜创作引擎进行集成
 */
class ConsistencyCheckIntegrationService
{
    private EnhancedRAGService $ragService;
    private VectorEmbeddingService $vectorService;
    private array $config;
    
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->getDefaultConfig(), $config);
        $this->ragService = new EnhancedRAGService($config);
        $this->vectorService = $this->ragService->getVectorService();
    }
    
    /**
     * 执行一致性检查
     * 
     * @param int $userId 用户ID
     * @param string $content 要检查的内容
     * @param int $projectId 项目ID
     * @param string $projectType 项目类型 (novel/anime/music)
     * @param int|null $contentId 内容ID
     * @param string|null $contentType 内容类型
     * @return array 检查结果
     */
    public function performConsistencyCheck(
        int $userId, 
        string $content, 
        int $projectId, 
        string $projectType, 
        ?int $contentId = null, 
        ?string $contentType = null
    ): array {
        try {
            // 1. 获取用户一致性配置
            $userConfig = UserConsistencyConfig::getOrCreateByUserId($userId);
            
            // 2. 检查是否启用一致性检查
            if (!$userConfig['is_enabled']) {
                return [
                    'success' => true,
                    'enabled' => false,
                    'message' => '用户未启用一致性检查',
                    'report_id' => null
                ];
            }
            
            // 3. 创建检查报告记录
            $reportId = ConsistencyReport::create([
                'user_id' => $userId,
                'project_id' => $projectId,
                'project_type' => $projectType,
                'content_id' => $contentId,
                'content_type' => $contentType,
                'status' => 'pending'
            ]);
            
            if (!$reportId) {
                throw new \Exception('创建一致性检查报告失败');
            }
            
            // 4. 记录向量数据库使用日志开始
            $this->logVectorDbUsage($userId, 'consistency_check', $userConfig, 'start');
            
            $startTime = microtime(true);
            
            try {
                // 5. 获取项目的核心设定
                $coreSettings = CoreSetting::getByProject($projectId, $projectType, null, true);
                
                // 6. 构建引擎请求
                $engineRequest = $this->buildEngineRequest($content, $projectType, $userConfig);
                
                // 7. 构建用户等级
                $userTier = new UserTier('regular'); // 可以根据用户会员等级动态设置
                
                // 8. 执行一致性检查
                $engineResponse = $this->ragService->generate($engineRequest, $userTier);
                
                // 9. 解析引擎响应
                $consistencyReport = $this->parseEngineResponse($engineResponse);
                
                // 10. 保存冲突记录
                $this->saveConflictRecords($reportId, $userId, $projectId, $projectType, $contentId, $contentType, $consistencyReport);
                
                // 11. 更新报告状态
                $executionTime = round((microtime(true) - $startTime) * 1000);
                $tokensUsed = $this->extractTokensUsed($engineResponse);
                
                ConsistencyReport::markCompleted($reportId, $consistencyReport, $executionTime, $tokensUsed);
                
                // 12. 记录向量数据库使用日志结束
                $this->logVectorDbUsage($userId, 'consistency_check', $userConfig, 'success', $tokensUsed, $executionTime);
                
                return [
                    'success' => true,
                    'enabled' => true,
                    'report_id' => $reportId,
                    'pass' => $consistencyReport['pass'],
                    'score' => $consistencyReport['score'] ?? 0,
                    'conflicts_count' => count($consistencyReport['violations'] ?? []),
                    'execution_time' => $executionTime,
                    'tokens_used' => $tokensUsed
                ];
                
            } catch (\Exception $e) {
                // 记录错误
                ConsistencyReport::markFailed($reportId, $e->getMessage());
                $this->logVectorDbUsage($userId, 'consistency_check', $userConfig, 'failed', 0, 0, $e->getMessage());
                
                return [
                    'success' => false,
                    'enabled' => true,
                    'report_id' => $reportId,
                    'error' => $e->getMessage()
                ];
            }
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'enabled' => false,
                'error' => '系统错误: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 更新核心设定
     * 
     * @param int $userId 用户ID
     * @param int $projectId 项目ID
     * @param string $projectType 项目类型
     * @param array $settings 设定数据
     * @return bool
     */
    public function updateCoreSettings(int $userId, int $projectId, string $projectType, array $settings): bool
    {
        try {
            $userConfig = UserConsistencyConfig::getOrCreateByUserId($userId);
            
            foreach ($settings as $setting) {
                // 生成向量并存储
                $vectorId = $this->generateAndStoreVector($userId, $setting, $userConfig);
                
                // 保存核心设定
                CoreSetting::create([
                    'user_id' => $userId,
                    'project_id' => $projectId,
                    'project_type' => $projectType,
                    'setting_type' => $setting['type'],
                    'setting_key' => $setting['key'],
                    'setting_value' => $setting['value'],
                    'vector_id' => $vectorId,
                    'embedding_model' => $userConfig['embedding_model'],
                    'metadata' => json_encode($setting['metadata'] ?? [])
                ]);
            }
            
            return true;
            
        } catch (\Exception $e) {
            error_log("更新核心设定失败: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 获取一致性检查统计
     * 
     * @param int|null $userId 用户ID
     * @param string|null $dateRange 日期范围
     * @return array
     */
    public function getConsistencyStats(?int $userId = null, ?string $dateRange = null): array
    {
        $reportStats = ConsistencyReport::getReportStats($userId, null, $dateRange);
        $conflictStats = ConsistencyConflict::getConflictStats($userId, null, $dateRange);
        $vectorDbStats = VectorDbUsageLog::getUsageStats($userId, $dateRange);
        
        return [
            'reports' => $reportStats,
            'conflicts' => $conflictStats,
            'vector_db_usage' => $vectorDbStats,
            'summary' => $this->calculateSummaryStats($reportStats, $conflictStats, $vectorDbStats)
        ];
    }
    
    /**
     * 获取用户的一致性检查配置
     * 
     * @param int $userId 用户ID
     * @return array
     */
    public function getUserConsistencyConfig(int $userId): array
    {
        $config = UserConsistencyConfig::getOrCreateByUserId($userId);
        
        // 获取可用的选项
        $vectorDbOptions = VectorDbConfig::getEnabled();
        $embeddingModelOptions = EmbeddingModelConfig::getEnabled();
        
        return [
            'config' => $config,
            'vector_db_options' => $vectorDbOptions,
            'embedding_model_options' => $embeddingModelOptions,
            'db_mode_options' => UserConsistencyConfig::getDbModeOptions(),
            'check_frequency_options' => UserConsistencyConfig::getCheckFrequencyOptions(),
            'check_scope_options' => UserConsistencyConfig::getCheckScopeOptions()
        ];
    }
    
    /**
     * 更新用户的一致性检查配置
     * 
     * @param int $userId 用户ID
     * @param array $configData 配置数据
     * @return array
     */
    public function updateUserConsistencyConfig(int $userId, array $configData): array
    {
        // 验证配置数据
        $validation = UserConsistencyConfig::validateConfig($configData);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'errors' => $validation['errors']
            ];
        }
        
        // 更新配置
        $success = UserConsistencyConfig::updateByUserId($userId, $configData);
        
        return [
            'success' => $success,
            'config' => UserConsistencyConfig::getOrCreateByUserId($userId)
        ];
    }
    
    /**
     * 构建引擎请求
     */
    private function buildEngineRequest(string $content, string $projectType, array $userConfig): EngineRequest
    {
        // 根据项目类型设置不同的上下文
        $context = $this->buildProjectContext($projectType);
        
        // 设置一致性检查选项
        $options = [
            'consistency_check' => true,
            'check_level' => $userConfig['sensitivity'] > 0.8 ? 'high' : 'low',
            'strictness' => $userConfig['sensitivity'],
            'project_type' => $projectType
        ];
        
        return new EngineRequest(
            userQuery: "请对以下内容进行一致性检查：{$content}",
            context: $context,
            options: $options
        );
    }
    
    /**
     * 构建项目上下文
     */
    private function buildProjectContext(string $projectType): array
    {
        $context = [
            'project_type' => $projectType,
            'check_timestamp' => date('Y-m-d H:i:s')
        ];
        
        // 根据项目类型添加特定上下文
        switch ($projectType) {
            case 'novel':
                $context['content_type'] = '小说章节';
                $context['check_focus'] = ['character_consistency', 'plot_coherence', 'worldview_consistency'];
                break;
            case 'anime':
                $context['content_type'] = '动漫场景';
                $context['check_focus'] = ['character_design', 'scene_coherence', 'style_consistency'];
                break;
            case 'music':
                $context['content_type'] = '音乐片段';
                $context['check_focus'] = ['melody_consistency', 'style_consistency', 'theme_consistency'];
                break;
        }
        
        return $context;
    }
    
    /**
     * 解析引擎响应
     */
    private function parseEngineResponse(EngineResponse $response): array
    {
        $debug = $response->debug;
        
        // 从调试信息中提取一致性检查结果
        $consistencyResult = $debug['consistency_report'] ?? [];
        
        return [
            'pass' => $consistencyResult['pass'] ?? true,
            'score' => $consistencyResult['score'] ?? 100,
            'violations' => $consistencyResult['violations'] ?? [],
            'warnings' => $consistencyResult['warnings'] ?? [],
            'details' => $consistencyResult['details'] ?? []
        ];
    }
    
    /**
     * 保存冲突记录
     */
    private function saveConflictRecords(
        int $reportId, 
        int $userId, 
        int $projectId, 
        string $projectType, 
        ?int $contentId, 
        ?string $contentType, 
        array $consistencyReport
    ): void {
        $violations = $consistencyReport['violations'] ?? [];
        
        foreach ($violations as $violation) {
            $conflictData = [
                'report_id' => $reportId,
                'user_id' => $userId,
                'project_id' => $projectId,
                'project_type' => $projectType,
                'content_id' => $contentId,
                'content_type' => $contentType,
                'conflict_type' => $this->mapViolationType($violation['type'] ?? 'general'),
                'severity' => $this->mapSeverity($violation['severity'] ?? 'medium'),
                'description' => $violation['description'] ?? '',
                'conflicting_content' => $violation['content'] ?? '',
                'reference_setting' => $violation['reference'] ?? '',
                'suggestion' => $violation['suggestion'] ?? '',
                'similarity_score' => $violation['similarity_score'] ?? null
            ];
            
            ConsistencyConflict::create($conflictData);
        }
    }
    
    /**
     * 映射违规类型
     */
    private function mapViolationType(string $type): string
    {
        $typeMap = [
            'character' => 'character',
            'plot' => 'plot',
            'setting' => 'setting',
            'logic' => 'logic',
            'style' => 'style',
            'continuity' => 'continuity'
        ];
        
        return $typeMap[$type] ?? 'logic';
    }
    
    /**
     * 映射严重程度
     */
    private function mapSeverity(string $severity): string
    {
        $severityMap = [
            'low' => 'low',
            'medium' => 'medium',
            'high' => 'high',
            'critical' => 'critical'
        ];
        
        return $severityMap[$severity] ?? 'medium';
    }
    
    /**
     * 生成并存储向量
     */
    private function generateAndStoreVector(int $userId, array $setting, array $userConfig): ?string
    {
        try {
            // 构建要向量化的文本
            $text = "{$setting['key']}: {$setting['value']}";
            
            // 创建Document对象
            $document = new \LLPhant\Embeddings\Document();
            $document->content = $text;
            $document->metadata = [
                'user_id' => $userId,
                'setting_key' => $setting['key'],
                'setting_type' => $setting['type'] ?? 'general',
                'project_type' => $setting['project_type'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // 使用VectorEmbeddingService生成嵌入向量
            // 通过embeddingGenerator生成嵌入
            $embeddingGenerator = $this->getEmbeddingGenerator();
            if (!$embeddingGenerator) {
                throw new \Exception("无法获取嵌入生成器");
            }
            
            $documentWithEmbedding = $embeddingGenerator->embedDocument($document);
            
            if (!$documentWithEmbedding || empty($documentWithEmbedding->embedding)) {
                error_log("向量生成返回空结果");
                return null;
            }
            
            // 将向量存储到向量数据库
            $vectorStore = $this->getVectorStore();
            if (!$vectorStore) {
                throw new \Exception("无法获取向量存储");
            }
            
            $vectorStore->addDocument($documentWithEmbedding);
            
            // 生成向量ID（使用文档ID或基于内容的哈希）
            $vectorId = $documentWithEmbedding->id ?? 'vector_' . md5($text . $userId . time());
            
            return $vectorId;
            
        } catch (\Exception $e) {
            error_log("生成向量失败: " . $e->getMessage());
            // 降级：返回基于哈希的ID（用于兼容性）
            $text = "{$setting['key']}: {$setting['value']}";
            return 'vector_' . md5($text . $userId . time());
        }
    }
    
    /**
     * 获取嵌入生成器（通过反射或公共方法）
     */
    private function getEmbeddingGenerator()
    {
        // 尝试通过反射访问私有属性
        try {
            $reflection = new \ReflectionClass($this->vectorService);
            $property = $reflection->getProperty('embeddingGenerator');
            $property->setAccessible(true);
            return $property->getValue($this->vectorService);
        } catch (\Exception $e) {
            error_log("无法访问embeddingGenerator: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 获取向量存储（通过反射或公共方法）
     */
    private function getVectorStore()
    {
        // 尝试通过反射访问私有属性
        try {
            $reflection = new \ReflectionClass($this->vectorService);
            $property = $reflection->getProperty('vectorStore');
            $property->setAccessible(true);
            return $property->getValue($this->vectorService);
        } catch (\Exception $e) {
            error_log("无法访问vectorStore: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 提取使用的Token数量
     */
    private function extractTokensUsed(EngineResponse $response): int
    {
        $debug = $response->debug;
        return $debug['tokens_used'] ?? 0;
    }
    
    /**
     * 记录向量数据库使用日志
     */
    private function logVectorDbUsage(
        int $userId, 
        string $operationType, 
        array $userConfig, 
        string $status, 
        int $tokensUsed = 0, 
        int $executionTime = 0, 
        ?string $errorMessage = null
    ): void {
        VectorDbUsageLog::create([
            'user_id' => $userId,
            'operation_type' => $operationType,
            'vector_db_type' => $userConfig['db_mode'] ?? 'single',
            'embedding_model' => $userConfig['embedding_model'] ?? 'openai',
            'tokens_used' => $tokensUsed,
            'execution_time' => $executionTime,
            'status' => $status,
            'error_message' => $errorMessage
        ]);
    }
    
    /**
     * 计算汇总统计
     */
    private function calculateSummaryStats(array $reportStats, array $conflictStats, array $vectorDbStats): array
    {
        $totalReports = array_sum(array_column($reportStats, 'total_reports'));
        $completedReports = array_sum(array_column($reportStats, 'completed_reports'));
        $totalConflicts = array_sum(array_map(function($stats) {
            return array_sum(array_column($stats, 'total_conflicts'));
        }, $conflictStats));
        
        return [
            'total_reports' => $totalReports,
            'completed_reports' => $completedReports,
            'success_rate' => $totalReports > 0 ? round(($completedReports / $totalReports) * 100, 2) : 0,
            'total_conflicts' => $totalConflicts,
            'total_tokens_used' => array_sum(array_column($vectorDbStats, 'total_tokens_used')),
            'avg_execution_time' => array_sum(array_column($vectorDbStats, 'avg_execution_time')) / max(1, count($vectorDbStats))
        ];
    }
    
    /**
     * 获取默认配置
     */
    private function getDefaultConfig(): array
    {
        return [
            'enable_vector_db' => true,
            'default_vector_db' => 'single',
            'default_embedding_model' => 'openai',
            'max_content_length' => 10000,
            'batch_size' => 100
        ];
    }
    
    /**
     * 测试向量数据库连接
     */
    public function testVectorDbConnection(int $configId): array
    {
        return VectorDbConfig::testConnection($configId);
    }
    
    /**
     * 测试嵌入式模型
     */
    public function testEmbeddingModel(int $configId, string $testText = "测试文本"): array
    {
        return EmbeddingModelConfig::testModel($configId, $testText);
    }
    
    /**
     * 获取系统健康状态
     */
    public function getSystemHealthStatus(): array
    {
        $ragHealth = $this->ragService->getHealthStatus();
        $vectorDbHealth = $this->vectorService->healthCheck();
        
        // 获取最近的错误统计
        $recentErrors = ConsistencyReport::getAll(1, 10, null, null, 'failed')['reports'] ?? [];
        
        return [
            'overall' => ($ragHealth['overall'] === 'healthy' && $vectorDbHealth['status'] === 'healthy') ? 'healthy' : 'unhealthy',
            'rag_service' => $ragHealth,
            'vector_service' => $vectorDbHealth,
            'recent_errors' => $recentErrors,
            'last_check' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * 统一错误处理
     */
    protected function handleError(\Exception $e, $operation = '') {
        $errorMessage = $operation ? $operation . '失败: ' . $e->getMessage() : $e->getMessage();
        
        // 记录错误日志
        error_log('Service Error: ' . $errorMessage);
        
        // 抛出自定义异常
        throw new \Exception($errorMessage, $e->getCode(), $e);
    }
}
