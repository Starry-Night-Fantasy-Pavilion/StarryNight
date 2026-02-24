<?php
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<div class="page-consistency-config">
    <div class="container">
        <h1 class="page-title">一致性检查配置</h1>
        <p class="page-subtitle">配置角色、情节、世界观等一致性检查规则</p>

        <form id="consistencyConfigForm" class="consistency-config-form">
            <div class="form-section">
                <h2>角色一致性检查</h2>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="check_character_name" value="1" <?= ($config['check_character_name'] ?? false) ? 'checked' : '' ?>>
                        检查角色姓名一致性
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="check_character_appearance" value="1" <?= ($config['check_character_appearance'] ?? false) ? 'checked' : '' ?>>
                        检查角色外貌描述一致性
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="check_character_personality" value="1" <?= ($config['check_character_personality'] ?? false) ? 'checked' : '' ?>>
                        检查角色性格一致性
                    </label>
                </div>
            </div>

            <div class="form-section">
                <h2>情节一致性检查</h2>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="check_plot_timeline" value="1" <?= ($config['check_plot_timeline'] ?? false) ? 'checked' : '' ?>>
                        检查时间线一致性
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="check_plot_events" value="1" <?= ($config['check_plot_events'] ?? false) ? 'checked' : '' ?>>
                        检查事件逻辑一致性
                    </label>
                </div>
            </div>

            <div class="form-section">
                <h2>世界观一致性检查</h2>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="check_worldview_rules" value="1" <?= ($config['check_worldview_rules'] ?? false) ? 'checked' : '' ?>>
                        检查世界观规则一致性
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="check_worldview_setting" value="1" <?= ($config['check_worldview_setting'] ?? false) ? 'checked' : '' ?>>
                        检查世界观设定一致性
                    </label>
                </div>
            </div>

            <div class="form-section">
                <h2>检查敏感度</h2>
                <div class="form-group">
                    <label for="sensitivity">敏感度级别</label>
                    <select id="sensitivity" name="sensitivity" class="form-control">
                        <option value="low" <?= ($config['sensitivity'] ?? 'medium') === 'low' ? 'selected' : '' ?>>低（仅检查明显错误）</option>
                        <option value="medium" <?= ($config['sensitivity'] ?? 'medium') === 'medium' ? 'selected' : '' ?>>中（检查常见错误）</option>
                        <option value="high" <?= ($config['sensitivity'] ?? 'medium') === 'high' ? 'selected' : '' ?>>高（严格检查所有细节）</option>
                    </select>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">保存配置</button>
                <a href="/user_center" class="btn btn-outline">返回</a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('consistencyConfigForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = {};
    
    // 收集所有复选框的值
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    // 添加敏感度
    data.sensitivity = document.getElementById('sensitivity').value;

    fetch('/user_center/save_consistency_config', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('配置已保存');
        } else {
            alert('保存失败: ' + (data.message || '未知错误'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('保存失败，请重试');
    });
});
</script>
