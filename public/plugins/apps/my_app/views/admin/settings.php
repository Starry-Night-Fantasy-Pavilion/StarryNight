<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>在线书城设置</title>
    <link rel="stylesheet" href="/static/admin/css/plugin-modal.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
             <h2 class="admin-title">在线书城设置</h2>
        </div>
        
        <form method="POST" class="settings-form">
            <?= csrf_field() ?>
            
            <?php foreach ($form['sections'] as $sectionId => $section): ?>
                <div class="form-section">
                    <h3 class="section-title"><?php echo htmlspecialchars($section['title']); ?></h3>
                    
                    <?php foreach ($section['fields'] as $fieldId => $field): ?>
                        <div class="form-group">
                            <label class="form-label" for="<?php echo $fieldId; ?>"><?php echo htmlspecialchars($field['label']); ?></label>
                            <?php
                                $currentValue = $config[$fieldId] ?? $field['default'] ?? '';
                                switch ($field['type']) {
                                    case 'select':
                                        echo '<select class="form-control" id="' . $fieldId . '" name="' . $fieldId . '">';
                                        foreach ($field['options'] as $optionValue => $optionLabel) {
                                            $selected = ($currentValue == $optionValue) ? 'selected' : '';
                                            echo '<option value="' . htmlspecialchars($optionValue) . '" ' . $selected . '>' . htmlspecialchars($optionLabel) . '</option>';
                                        }
                                        echo '</select>';
                                        break;
                                    case 'number':
                                        $min = $field['min'] ?? '';
                                        $max = $field['max'] ?? '';
                                        echo '<input type="number" class="form-control" id="' . $fieldId . '" name="' . $fieldId . '" value="' . htmlspecialchars($currentValue) . '" min="' . $min . '" max="' . $max . '">';
                                        break;
                                    case 'checkbox':
                                        $checked = $currentValue ? 'checked' : '';
                                        echo '<div class="form-check">';
                                        echo '<input type="checkbox" id="' . $fieldId . '" name="' . $fieldId . '" ' . $checked . '>';
                                        echo '<label for="' . $fieldId . '" style="display:inline;margin-left:5px;margin-bottom:0;cursor:pointer;">启用</label>';
                                        echo '</div>';
                                        break;
                                    case 'text':
                                    default:
                                        echo '<input type="text" class="form-control" id="' . $fieldId . '" name="' . $fieldId . '" value="' . htmlspecialchars($currentValue) . '">';
                                        break;
                                }
                            ?>
                            <?php if (!empty($field['description'])): ?>
                                <div class="form-description"><?php echo htmlspecialchars($field['description']); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">保存设置</button>
                <button type="button" id="sync-sources" class="btn btn-secondary" style="margin-left: 10px;">立即同步书源</button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sourceModeSelect = document.getElementById('source_mode');
            const remoteSection = document.querySelector('.form-section:nth-child(2)'); // remote section

            function toggleRemoteSettings() {
                if (sourceModeSelect.value === 'remote') {
                    remoteSection.style.display = 'block';
                } else {
                    remoteSection.style.display = 'none';
                }
            }

            // Initial toggle
            toggleRemoteSettings();

            // Listen for changes
            sourceModeSelect.addEventListener('change', toggleRemoteSettings);

            // Sync sources button
            document.getElementById('sync-sources').addEventListener('click', function() {
                if (confirm('确定要立即同步远程书源吗？这可能需要一些时间。')) {
                    this.disabled = true;
                    this.textContent = '同步中...';

                    fetch('/admin/my_app/sync-sources', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            _token: document.querySelector('input[name="_token"]').value
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        alert(data.message || '同步完成');
                    })
                    .catch(error => {
                        alert('同步失败：' + error.message);
                    })
                    .finally(() => {
                        this.disabled = false;
                        this.textContent = '立即同步书源';
                    });
                }
            });
        });
    </script>
</body>
</html>
