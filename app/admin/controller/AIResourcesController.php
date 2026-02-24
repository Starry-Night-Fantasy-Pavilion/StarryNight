<?php

namespace app\admin\controller;

use app\models\AIChannel;
use app\models\AIModelPrice;
use app\models\AIPresetModel;
use app\models\AIPromptTemplate;
use app\models\AIAgent;
use app\models\AIResourceAudit;
use app\models\AIEmbeddingModel;
use Exception;

class AIResourcesController extends BaseController
{
    public function index()
    {
        try {
        $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/');
        header("Location: /{$adminPrefix}/ai/channels");
        exit;
    
        } catch (Exception $e) {
            // 记录错误日志
            error_log('Controller Error in AIResourcesController::index: ' . $e->getMessage());
            
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

    public function channels()
    {
        $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'save') {
                $id = (int)($_POST['id'] ?? 0);
                $data = [
                    // 简化配置：URL / KEY / 模型名称（可多行/可分类）
                    'name' => trim((string)($_POST['name'] ?? '')) ?: '默认配置',
                    'type' => trim((string)($_POST['type'] ?? 'custom')),
                    'model_group' => trim((string)($_POST['model_group'] ?? '')),
                    'status' => ($_POST['status'] ?? 'enabled') === 'disabled' ? 'disabled' : 'enabled',
                    'base_url' => trim((string)($_POST['base_url'] ?? '')),
                    'api_key' => trim((string)($_POST['api_key'] ?? '')),
                    'models_text' => (string)($_POST['models_text'] ?? ''),

                    // 高级字段：没填就用默认
                    'priority' => (int)($_POST['priority'] ?? 0),
                    'weight' => (int)($_POST['weight'] ?? 100),
                    'config_json' => trim((string)($_POST['config_json'] ?? '')),
                    'concurrency_limit' => (int)($_POST['concurrency_limit'] ?? 0),
                    'is_free' => isset($_POST['is_free']) ? 1 : 0,
                    'is_user_custom' => isset($_POST['is_user_custom']) ? 1 : 0,
                ];
                AIChannel::save($id, $data);
            } elseif ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    AIChannel::delete($id);
                }
            }

            header("Location: /{$adminPrefix}/ai/channels");
            exit;
        }

        $channels = AIChannel::all();
        $group = trim((string)($_GET['group'] ?? ''));
        $q = trim((string)($_GET['q'] ?? ''));
        if ($group !== '') {
            $channels = array_values(array_filter($channels, function ($c) use ($group) {
                return (string)($c['model_group'] ?? '') === $group;
            }));
        }
        if ($q !== '') {
            $qLower = mb_strtolower($q, 'UTF-8');
            $channels = array_values(array_filter($channels, function ($c) use ($qLower) {
                $hay = implode("\n", [
                    (string)($c['name'] ?? ''),
                    (string)($c['base_url'] ?? ''),
                    (string)($c['model_group'] ?? ''),
                    (string)($c['models_text'] ?? ''),
                ]);
                return mb_strpos(mb_strtolower($hay, 'UTF-8'), $qLower) !== false;
            }));
        }
        $editId = (int)($_GET['edit'] ?? 0);
        $edit = $editId > 0 ? AIChannel::find($editId) : null;
        $title = 'AI 渠道管理';
        $currentPage = 'ai-channels';

        ob_start();
        require __DIR__ . '/../views/ai_resources/channels.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layout.php';
    }

    public function monitor()
    {
        $title = '渠道监控';
        $currentPage = 'ai-monitor';
        $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/');

        $channelId = (int)($_GET['channel_id'] ?? 0);
        $hours = (int)($_GET['hours'] ?? 24);
        $hours = max(1, min(168, $hours));

        $channels = AIChannel::all();
        $stats = AIChannel::stats($channelId, $hours);
        $recentErrors = AIChannel::recentErrors($channelId, 50);

        ob_start();
        require __DIR__ . '/../views/ai_resources/monitor.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layout.php';
    }

    public function modelPrices()
    {
        $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'save') {
                $id = (int)($_POST['id'] ?? 0);
                $data = [
                    'channel_id' => (int)($_POST['channel_id'] ?? 0),
                    'model_name' => trim((string)($_POST['model_name'] ?? '')),
                    'input_coin_per_1k' => (string)($_POST['input_coin_per_1k'] ?? '0'),
                    'output_coin_per_1k' => (string)($_POST['output_coin_per_1k'] ?? '0'),
                    'profit_percent' => (string)($_POST['profit_percent'] ?? '0'),
                    'is_active' => isset($_POST['is_active']) ? 1 : 0,
                ];
                AIModelPrice::save($id, $data);
            } elseif ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    AIModelPrice::delete($id);
                }
            }

            header("Location: /{$adminPrefix}/ai/model-prices");
            exit;
        }

        $channels = AIChannel::all();
        $items = AIModelPrice::allWithChannel();
        $editId = (int)($_GET['edit'] ?? 0);
        $edit = $editId > 0 ? AIModelPrice::find($editId) : null;
        $title = '模型价格管理';
        $currentPage = 'ai-model-prices';

        ob_start();
        require __DIR__ . '/../views/ai_resources/model_prices.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layout.php';
    }

    public function presetModels()
    {
        $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'save') {
                $id = (int)($_POST['id'] ?? 0);
                $data = [
                    'name' => trim((string)($_POST['name'] ?? '')),
                    'description' => trim((string)($_POST['description'] ?? '')),
                    'default_channel_id' => (int)($_POST['default_channel_id'] ?? 0),
                    'is_enabled' => isset($_POST['is_enabled']) ? 1 : 0,
                ];
                AIPresetModel::save($id, $data);
            } elseif ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    AIPresetModel::delete($id);
                }
            }

            header("Location: /{$adminPrefix}/ai/preset-models");
            exit;
        }

        $channels = AIChannel::all();
        $items = AIPresetModel::allWithChannel();
        $editId = (int)($_GET['edit'] ?? 0);
        $edit = $editId > 0 ? AIPresetModel::find($editId) : null;
        $title = '预设模型名称';
        $currentPage = 'ai-preset-models';

        ob_start();
        require __DIR__ . '/../views/ai_resources/preset_models.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layout.php';
    }

    public function templates()
    {
        $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'save') {
                $id = (int)($_POST['id'] ?? 0);
                $data = [
                    'name' => trim((string)($_POST['name'] ?? '')),
                    'description' => trim((string)($_POST['description'] ?? '')),
                    'feature' => trim((string)($_POST['feature'] ?? '')),
                    'category' => trim((string)($_POST['category'] ?? '')),
                    'is_public' => isset($_POST['is_public']) ? 1 : 0,
                    'price_coin' => (string)($_POST['price_coin'] ?? '0'),
                    'status' => $_POST['status'] ?? 'draft',
                    'content' => (string)($_POST['content'] ?? ''),
                ];
                AIPromptTemplate::saveWithNewVersion($id, $data);
            } elseif ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    AIPromptTemplate::delete($id);
                }
            }

            header("Location: /{$adminPrefix}/ai/templates");
            exit;
        }

        $editId = (int)($_GET['edit'] ?? 0);
        $edit = $editId > 0 ? AIPromptTemplate::findWithCurrentVersion($editId) : null;
        $items = AIPromptTemplate::allWithCurrentVersion();
        $title = '通用提示词模板';
        $currentPage = 'ai-templates';

        ob_start();
        require __DIR__ . '/../views/ai_resources/templates.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layout.php';
    }

    public function agents()
    {
        $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'save') {
                $id = (int)($_POST['id'] ?? 0);
                $data = [
                    'name' => trim((string)($_POST['name'] ?? '')),
                    'role' => trim((string)($_POST['role'] ?? '')),
                    'abilities_json' => (string)($_POST['abilities_json'] ?? ''),
                    'prompt' => (string)($_POST['prompt'] ?? ''),
                    'available_models_json' => (string)($_POST['available_models_json'] ?? ''),
                    'status' => $_POST['status'] ?? 'draft',
                    'is_public' => isset($_POST['is_public']) ? 1 : 0,
                    'price_coin' => (string)($_POST['price_coin'] ?? '0'),
                ];
                AIAgent::save($id, $data);
            } elseif ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    AIAgent::delete($id);
                }
            }

            header("Location: /{$adminPrefix}/ai/agents");
            exit;
        }

        $items = AIAgent::all();
        $editId = (int)($_GET['edit'] ?? 0);
        $edit = $editId > 0 ? AIAgent::find($editId) : null;
        $title = '通用智能体管理';
        $currentPage = 'ai-agents';

        ob_start();
        require __DIR__ . '/../views/ai_resources/agents.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layout.php';
    }

    public function audits()
    {
        $page = (int)($_GET['page'] ?? 1);
        $status = (string)($_GET['status'] ?? '');
        $type = (string)($_GET['resource_type'] ?? '');

        $filters = array_filter([
            'status' => $status,
            'resource_type' => $type,
        ]);

        $data = AIResourceAudit::getQueue($page, 15, $filters);

        $title = '资源审核';
        $currentPage = 'ai-audits';
        $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/');

        ob_start();
        require __DIR__ . '/../views/ai_resources/audits.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layout.php';
    }

    public function auditDetails($id)
    {
        $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/');
        $item = AIResourceAudit::find((int)$id);
        if (!$item) {
            header("Location: /{$adminPrefix}/ai/audits");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $comment = $_POST['comment'] ?? '';
            $reviewerId = $_SESSION['admin_user']['id'] ?? 0;
            AIResourceAudit::review((int)$id, (int)$reviewerId, (string)$action, (string)$comment);
            header("Location: /{$adminPrefix}/ai/audits/details/{$id}");
            exit;
        }

        $title = '资源审核详情';
        $currentPage = 'ai-audits';

        ob_start();
        require __DIR__ . '/../views/ai_resources/audit_details.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layout.php';
    }

    public function embeddings()
    {
        $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'save') {
                $id = (int)($_POST['id'] ?? 0);
                $data = [
                    'name' => trim((string)($_POST['name'] ?? '')),
                    'description' => trim((string)($_POST['description'] ?? '')),
                    'type' => trim((string)($_POST['type'] ?? 'openai')),
                    'base_url' => trim((string)($_POST['base_url'] ?? '')),
                    'api_key' => trim((string)($_POST['api_key'] ?? '')),
                    'config_json' => (string)($_POST['config_json'] ?? ''),
                    'is_enabled' => isset($_POST['is_enabled']) ? 1 : 0,
                    'is_user_customizable' => isset($_POST['is_user_customizable']) ? 1 : 0,
                ];
                AIEmbeddingModel::save($id, $data);
            } elseif ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    AIEmbeddingModel::delete($id);
                }
            }

            header("Location: /{$adminPrefix}/ai/embeddings");
            exit;
        }

        $items = AIEmbeddingModel::all();
        $editId = (int)($_GET['edit'] ?? 0);
        $edit = $editId > 0 ? AIEmbeddingModel::find($editId) : null;
        $title = '嵌入式模型管理';
        $currentPage = 'ai-embeddings';

        ob_start();
        require __DIR__ . '/../views/ai_resources/embeddings.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layout.php';
    }
}

