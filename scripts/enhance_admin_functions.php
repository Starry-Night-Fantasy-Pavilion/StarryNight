<?php
/**
 * 后台管理功能完善脚本
 * 
 * 针对检查发现的问题进行功能补全和优化
 */

echo "=== 后台管理功能完善 ===\n\n";

// 1. 批量删除功能增强
function enhanceBatchDelete()
{
    echo "1. 增强批量删除功能...\n";
    
    $batchDeleteCode = '
    /**
     * 批量删除功能
     */
    public function batchDelete()
    {
        if ($_SERVER[\'REQUEST_METHOD\'] !== \'POST\') {
            $this->error(\'无效请求\', 400);
            return;
        }
        
        $ids = $_POST[\'ids\'] ?? [];
        if (empty($ids) || !is_array($ids)) {
            $this->error(\'请选择要删除的项目\', 400);
            return;
        }
        
        $successCount = 0;
        $failCount = 0;
        $errors = [];
        
        foreach ($ids as $id) {
            $id = (int)$id;
            if ($id <= 0) continue;
            
            try {
                if ($this->deleteItem($id)) {
                    $successCount++;
                } else {
                    $failCount++;
                    $errors[] = "ID {$id} 删除失败";
                }
            } catch (Exception $e) {
                $failCount++;
                $errors[] = "ID {$id}: " . $e->getMessage();
            }
        }
        
        $message = "批量删除完成：成功 {$successCount} 项，失败 {$failCount} 项";
        if (!empty($errors)) {
            $message .= "\\n错误详情：" . implode("; ", array_slice($errors, 0, 5));
            if (count($errors) > 5) {
                $message .= "; 等 " . (count($errors) - 5) . " 个错误";
            }
        }
        
        $this->success($message);
    }
    
    /**
     * 统一删除确认页面
     */
    public function deleteConfirm($id = null)
    {
        $ids = $_GET[\'ids\'] ?? ($id ? [$id] : []);
        if (empty($ids)) {
            $this->error(\'无效的删除请求\', 400);
            return;
        }
        
        $items = $this->getItemsByIds($ids);
        
        $this->render(\'delete_confirm\', [
            \'title\' => \'删除确认\',
            \'items\' => $items,
            \'ids\' => $ids,
            \'backUrl\' => $_SERVER[\'HTTP_REFERER\'] ?? \'/admin/\'
        ]);
    }';
    
    // 为常用控制器添加批量删除功能
    $controllers = [
        'NoticeAnnouncementController',
        'CommunityController', 
        'MembershipFinanceController',
        'AIResourcesController'
    ];
    
    foreach ($controllers as $controller) {
        $controllerFile = __DIR__ . "/../app/admin/controller/{$controller}.php";
        if (file_exists($controllerFile)) {
            $content = file_get_contents($controllerFile);
            
            // 检查是否已有批量删除功能
            if (strpos($content, 'batchDelete') === false) {
                // 在类的末尾添加批量删除方法
                $content = str_replace('}', $batchDeleteCode . "\n}", $content);
                file_put_contents($controllerFile, $content);
                echo "   ✅ 为 {$controller} 添加批量删除功能\n";
            }
        }
    }
}

// 2. 导入导出功能完善
function enhanceImportExport()
{
    echo "\n2. 完善导入导出功能...\n";
    
    $importExportCode = '
    /**
     * 数据导出功能
     */
    public function export()
    {
        $format = $_GET[\'format\'] ?? \'csv\';
        $filters = $_GET[\'filters\'] ?? [];
        
        $data = $this->getListForExport($filters);
        
        switch ($format) {
            case \'excel\':
                $this->exportToExcel($data);
                break;
            case \'json\':
                $this->exportToJson($data);
                break;
            case \'csv\':
            default:
                $this->exportToCsv($data);
                break;
        }
    }
    
    /**
     * 数据导入功能
     */
    public function import()
    {
        if ($_SERVER[\'REQUEST_METHOD\'] !== \'POST\') {
            $this->render(\'import_form\', [
                \'title\' => \'数据导入\',
                \'sampleData\' => $this->getSampleImportData()
            ]);
            return;
        }
        
        $file = $_FILES[\'import_file\'] ?? null;
        if (!$file || $file[\'error\'] !== UPLOAD_ERR_OK) {
            $this->error(\'文件上传失败\', 400);
            return;
        }
        
        $format = pathinfo($file[\'name\'], PATHINFO_EXTENSION);
        $tempFile = $file[\'tmp_name\'];
        
        try {
            $importedData = $this->parseImportFile($tempFile, $format);
            $result = $this->processImportData($importedData);
            
            $this->success("导入完成：成功 {$result[\'success\']} 条，失败 {$result[\'failed\']} 条");
        } catch (Exception $e) {
            $this->error(\'导入失败: \' . $e->getMessage(), 500);
        }
    }
    
    /**
     * 获取示例导入数据
     */
    protected function getSampleImportData()
    {
        return [
            [\'name\' => \'示例名称\', \'description\' => \'示例描述\', \'status\' => \'active\']
        ];
    }';
    
    // 为主要控制器添加导入导出功能
    $controllers = [
        'CommunityController',
        'MembershipFinanceController'
    ];
    
    foreach ($controllers as $controller) {
        $controllerFile = __DIR__ . "/../app/admin/controller/{$controller}.php";
        if (file_exists($controllerFile)) {
            $content = file_get_contents($controllerFile);
            
            if (strpos($content, 'export') === false) {
                $content = str_replace('}', $importExportCode . "\n}", $content);
                file_put_contents($controllerFile, $content);
                echo "   ✅ 为 {$controller} 添加导入导出功能\n";
            }
        }
    }
}

// 3. 统一删除确认机制
function createDeleteConfirmationView()
{
    echo "\n3. 创建统一删除确认视图...\n";
    
    $deleteConfirmView = '<?php require __DIR__ . \'/layout/header.php\'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">删除确认</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <h4>⚠️ 警告：此操作不可撤销</h4>
                        <p>您即将删除以下 <?= count($items) ?> 个项目：</p>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>名称</th>
                                    <th>创建时间</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item[\'id\']) ?></td>
                                    <td><?= htmlspecialchars($item[\'name\'] ?? $item[\'title\'] ?? \'未知\') ?></td>
                                    <td><?= htmlspecialchars($item[\'created_at\'] ?? \'\') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <form method="POST" action="<?= $_SERVER[\'REQUEST_URI\'] ?>" class="mt-4">
                        <input type="hidden" name="action" value="batchDelete">
                        <?php foreach ($ids as $id): ?>
                        <input type="hidden" name="ids[]" value="<?= (int)$id ?>">
                        <?php endforeach; ?>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-danger" 
                                    onclick="return confirm(\'确定要删除这 <?= count($ids) ?> 个项目吗？此操作不可撤销！\')">
                                确认删除
                            </button>
                            <a href="<?= htmlspecialchars($backUrl) ?>" class="btn btn-secondary">取消</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . \'/layout/footer.php\'; ?>';
    
    $viewFile = __DIR__ . '/../app/admin/views/shared/delete_confirm.php';
    if (!file_exists(dirname($viewFile))) {
        mkdir(dirname($viewFile), 0755, true);
    }
    
    file_put_contents($viewFile, $deleteConfirmView);
    echo "   ✅ 创建删除确认视图: " . basename($viewFile) . "\n";
}

// 4. 批量操作前端组件
function createBatchOperationsComponent()
{
    echo "\n4. 创建批量操作前端组件...\n";
    
    $batchComponent = '(function() {
    \'use strict\';
    
    // 批量操作组件
    class BatchOperations {
        constructor(options = {}) {
            this.options = {
                selectAllSelector: \'#select-all\',
                itemSelector: \'.item-checkbox\',
                batchActionsSelector: \'.batch-actions\',
                deleteUrl: options.deleteUrl || \'/admin/batch-delete\'
            };
            
            this.init();
        }
        
        init() {
            this.bindEvents();
            this.updateBatchActions();
        }
        
        bindEvents() {
            // 全选/取消全选
            const selectAll = document.querySelector(this.options.selectAllSelector);
            if (selectAll) {
                selectAll.addEventListener(\'change\', (e) => {
                    this.toggleSelectAll(e.target.checked);
                });
            }
            
            // 单个选择框变化
            const items = document.querySelectorAll(this.options.itemSelector);
            items.forEach(item => {
                item.addEventListener(\'change\', () => {
                    this.updateSelectAllState();
                    this.updateBatchActions();
                });
            });
            
            // 批量删除
            const deleteBtn = document.querySelector(\'[data-action="batch-delete"]\');
            if (deleteBtn) {
                deleteBtn.addEventListener(\'click\', (e) => {
                    e.preventDefault();
                    this.batchDelete();
                });
            }
        }
        
        toggleSelectAll(checked) {
            const items = document.querySelectorAll(this.options.itemSelector);
            items.forEach(item => {
                item.checked = checked;
            });
            this.updateBatchActions();
        }
        
        updateSelectAllState() {
            const selectAll = document.querySelector(this.options.selectAllSelector);
            const items = document.querySelectorAll(this.options.itemSelector);
            const checkedItems = document.querySelectorAll(this.options.itemSelector + \':checked\');
            
            if (selectAll) {
                selectAll.checked = checkedItems.length === items.length && items.length > 0;
                selectAll.indeterminate = checkedItems.length > 0 && checkedItems.length < items.length;
            }
        }
        
        updateBatchActions() {
            const checkedItems = document.querySelectorAll(this.options.itemSelector + \':checked\');
            const batchActions = document.querySelector(this.options.batchActionsSelector);
            
            if (batchActions) {
                batchActions.style.display = checkedItems.length > 0 ? \'flex\' : \'none\';
            }
        }
        
        getSelectedIds() {
            const checkedItems = document.querySelectorAll(this.options.itemSelector + \':checked\');
            return Array.from(checkedItems).map(item => item.value);
        }
        
        async batchDelete() {
            const ids = this.getSelectedIds();
            if (ids.length === 0) {
                alert(\'请至少选择一项\');
                return;
            }
            
            if (!confirm(`确定要删除这 ${ids.length} 个项目吗？此操作不可撤销！`)) {
                return;
            }
            
            try {
                const response = await fetch(this.options.deleteUrl, {
                    method: \'POST\',
                    headers: {
                        \'Content-Type\': \'application/json\',
                        \'X-Requested-With\': \'XMLHttpRequest\'
                    },
                    body: JSON.stringify({ ids: ids })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert(\'删除失败: \' + result.message);
                }
            } catch (error) {
                alert(\'操作失败: \' + error.message);
            }
        }
    }
    
    // 自动初始化
    document.addEventListener(\'DOMContentLoaded\', function() {
        // 为所有包含批量操作的表格初始化组件
        const tablesWithBatch = document.querySelectorAll(\'table[data-batch-enabled="true"]\');
        tablesWithBatch.forEach(table => {
            new BatchOperations({
                selectAllSelector: \'#\' + table.id + \' #select-all\',
                itemSelector: \'#\' + table.id + \' .item-checkbox\',
                batchActionsSelector: \'#\' + table.id + \' .batch-actions\'
            });
        });
    });
    
    // 全局暴露以便手动调用
    window.BatchOperations = BatchOperations;
})();';
    
    $jsFile = __DIR__ . '/../public/static/admin/js/batch-operations.js';
    if (!file_exists(dirname($jsFile))) {
        mkdir(dirname($jsFile), 0755, true);
    }
    
    file_put_contents($jsFile, $batchComponent);
    echo "   ✅ 创建批量操作组件: " . basename($jsFile) . "\n";
}

// 执行所有完善操作
enhanceBatchDelete();
enhanceImportExport();
createDeleteConfirmationView();
createBatchOperationsComponent();

echo "\n=== 功能完善完成 ===\n";
echo "✅ 批量删除功能已增强\n";
echo "✅ 导入导出功能已完善\n";
echo "✅ 统一删除确认机制已建立\n";
echo "✅ 批量操作前端组件已创建\n";

echo "\n使用说明：\n";
echo "1. 在列表页面添加全选复选框\n";
echo "2. 引入 batch-operations.js 脚本\n";
echo "3. 为表格添加 data-batch-enabled=\"true\" 属性\n";
echo "4. 使用统一的删除确认流程\n";