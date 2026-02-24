<?php
$adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? '插件配置') ?></title>
    <link rel="stylesheet" href="/static/admin/css/plugin-modal.css">
</head>
<body>
    <link rel="stylesheet" href="/static/admin/css/plugin-config-inline.css?v=<?= time() ?>">
    <script src="/static/admin/js/plugin-config-form.js?v=<?= time() ?>"></script>

    <div class="admin-container">
        <div class="admin-header">
            <h2 class="admin-title"><?= htmlspecialchars($pluginConfig['name'] ?? $pluginId) ?> - 配置</h2>
        </div>
        
        <form method="POST" id="plugin-config-form" class="settings-form">
            <?php if (empty($configForm)): ?>
                <div class="form-message">
                    <p>该插件暂无配置项</p>
                </div>
            <?php else: ?>
                <?php foreach ($configForm as $field): ?>
                    <div class="form-group">
                        <label class="form-label" for="<?= htmlspecialchars($field['name'] ?? '') ?>">
                            <?= htmlspecialchars($field['title'] ?? $field['name'] ?? '') ?>
                            <?php if (!empty($field['required'])): ?>
                                <span class="required">*</span>
                            <?php endif; ?>
                        </label>
                        
                        <?php
                        $fieldName = $field['name'] ?? '';
                        $fieldType = $field['type'] ?? 'text';
                        $fieldValueRaw = $currentConfig[$fieldName] ?? $field['default'] ?? '';
                        
                        // 检查fieldValueRaw是否是字段定义本身（错误存储的情况）
                        // 如果包含type、label等字段定义的键，说明这是字段定义而不是值
                        if (is_array($fieldValueRaw) && (isset($fieldValueRaw['type']) || isset($fieldValueRaw['label']) || isset($fieldValueRaw['title']) || isset($fieldValueRaw['name']))) {
                            // 这是字段定义，不是配置值，使用默认值
                            $fieldValueRaw = $field['default'] ?? '';
                        } elseif (is_string($fieldValueRaw) && !empty($fieldValueRaw)) {
                            // 检查是否是JSON字符串且包含字段定义的特征
                            $decoded = json_decode($fieldValueRaw, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && (isset($decoded['type']) || isset($decoded['label']) || isset($decoded['title']) || isset($decoded['name']))) {
                                // 这是字段定义的JSON字符串，使用默认值
                                $fieldValueRaw = $field['default'] ?? '';
                            }
                        }
                        
                        // 确保fieldValue是字符串，如果是数组则转换为JSON
                        // 处理编码问题：确保UTF-8编码
                        if (is_array($fieldValueRaw)) {
                            // 再次检查，确保不是字段定义
                            if (isset($fieldValueRaw['type']) || isset($fieldValueRaw['label']) || isset($fieldValueRaw['title']) || isset($fieldValueRaw['name'])) {
                                $fieldValue = '';
                            } elseif (empty($fieldValueRaw) || (count($fieldValueRaw) === 1 && isset($fieldValueRaw[0]) && $fieldValueRaw[0] === '')) {
                                // 空数组或只包含空字符串的数组，使用空字符串
                                $fieldValue = '';
                            } else {
                                $fieldValue = json_encode($fieldValueRaw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                            }
                        } else {
                            $fieldValue = (string)$fieldValueRaw;
                            // 检查是否是JSON字符串且是空数组
                            if ($fieldValue === '[""]' || $fieldValue === '[]' || $fieldValue === 'null') {
                                $fieldValue = '';
                            } elseif (!empty($fieldValue)) {
                                // 尝试解析JSON，如果是空数组则使用空字符串
                                $decoded = json_decode($fieldValue, true);
                                if (json_last_error() === JSON_ERROR_NONE && (empty($decoded) || (is_array($decoded) && count($decoded) === 1 && isset($decoded[0]) && $decoded[0] === ''))) {
                                    $fieldValue = '';
                                }
                            }
                            // 确保字符串是UTF-8编码
                            if (!mb_check_encoding($fieldValue, 'UTF-8')) {
                                $fieldValue = mb_convert_encoding($fieldValue, 'UTF-8', 'auto');
                            }
                        }
                        $fieldOptions = $field['options'] ?? [];
                        ?>
                        
                        <?php if ($fieldType === 'select'): ?>
                            <select class="form-control" id="<?= htmlspecialchars($fieldName) ?>" name="config[<?= htmlspecialchars($fieldName) ?>]">
                                <?php foreach ($fieldOptions as $optValue => $optLabel): ?>
                                    <option value="<?= htmlspecialchars($optValue) ?>" <?= $fieldValue == $optValue ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($optLabel) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ($fieldType === 'textarea'): ?>
                            <textarea class="form-control" id="<?= htmlspecialchars($fieldName) ?>" name="config[<?= htmlspecialchars($fieldName) ?>]" rows="5"><?= htmlspecialchars($fieldValue) ?></textarea>
                        <?php elseif ($fieldType === 'checkbox'): ?>
                            <div class="form-check">
                                <?php
                                $checked = false;
                                $fieldValueArray = is_array($fieldValueRaw) ? $fieldValueRaw : [];
                                if (is_array($fieldValueRaw)) {
                                    $checked = !empty($fieldValueRaw);
                                } elseif (is_bool($fieldValueRaw)) {
                                    $checked = $fieldValueRaw;
                                } else {
                                    $checked = !empty($fieldValueRaw);
                                }
                                ?>
                                <?php if (is_array($fieldOptions) && count($fieldOptions) > 0): ?>
                                    <?php foreach ($fieldOptions as $optValue => $optLabel): ?>
                                        <label style="display: block; margin-bottom: 8px;">
                                            <input type="checkbox" name="config[<?= htmlspecialchars($fieldName) ?>][]" value="<?= htmlspecialchars($optValue) ?>" 
                                                <?= (is_array($fieldValueArray) && in_array($optValue, $fieldValueArray)) ? 'checked' : '' ?>>
                                            <?= htmlspecialchars($optLabel) ?>
                                        </label>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <input type="checkbox" id="<?= htmlspecialchars($fieldName) ?>" name="config[<?= htmlspecialchars($fieldName) ?>]" value="1" <?= $checked ? 'checked' : '' ?>>
                                    <label for="<?= htmlspecialchars($fieldName) ?>" style="display:inline;margin-left:5px;margin-bottom:0;cursor:pointer;">启用</label>
                                <?php endif; ?>
                            </div>
                        <?php elseif ($fieldType === 'number'): ?>
                            <input type="number" class="form-control" id="<?= htmlspecialchars($fieldName) ?>" name="config[<?= htmlspecialchars($fieldName) ?>]" 
                                value="<?= htmlspecialchars($fieldValue) ?>" 
                                min="<?= htmlspecialchars($field['min'] ?? '') ?>" 
                                max="<?= htmlspecialchars($field['max'] ?? '') ?>">
                        <?php elseif ($fieldType === 'password'): ?>
                            <div style="position: relative;">
                                <input type="password" class="form-control password-input" id="<?= htmlspecialchars($fieldName) ?>" name="config[<?= htmlspecialchars($fieldName) ?>]" 
                                    value="<?= htmlspecialchars($fieldValue) ?>" autocomplete="off" 
                                    data-original-value="<?= htmlspecialchars($fieldValue) ?>">
                                <button type="button" class="password-toggle-btn" onclick="togglePassword('<?= htmlspecialchars($fieldName) ?>')" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #666; font-size: 14px;">
                                    <span class="password-toggle-icon">👁️</span>
                                </button>
                            </div>
                        <?php else: ?>
                            <input type="text" class="form-control" id="<?= htmlspecialchars($fieldName) ?>" name="config[<?= htmlspecialchars($fieldName) ?>]" 
                                value="<?= htmlspecialchars($fieldValue) ?>">
                        <?php endif; ?>
                        
                        <?php if (!empty($field['description'])): ?>
                            <div class="form-description"><?= htmlspecialchars($field['description']) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">保存配置</button>
                <button type="button" class="btn btn-secondary" onclick="window.parent.postMessage({type: 'close-plugin-modal'}, '*')">取消</button>
            </div>
        </form>
    </div>
</body>
</html>
