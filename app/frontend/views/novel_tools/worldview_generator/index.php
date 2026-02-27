<?php
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<div class="page-worldview-generator">
    <div class="container">
        <h1 class="page-title">世界观生成器</h1>
        <p class="page-subtitle">AI辅助生成完整的世界观设定</p>

        <form id="worldviewForm" class="worldview-form">
            <div class="form-group">
                <label for="theme">主题 *</label>
                <input type="text" id="theme" name="theme" class="form-control" placeholder="例如：魔法与科技并存的世界" required>
            </div>

            <div class="form-group">
                <label for="genre">类型 *</label>
                <select id="genre" name="genre" class="form-control" required>
                    <option value="奇幻">奇幻</option>
                    <option value="科幻">科幻</option>
                    <option value="现代">现代</option>
                    <option value="古代">古代</option>
                    <option value="架空">架空</option>
                </select>
            </div>

            <div class="form-group">
                <label>需要包含的元素（可多选）</label>
                <div class="checkbox-group">
                    <label><input type="checkbox" name="elements[]" value="地理环境"> 地理环境</label>
                    <label><input type="checkbox" name="elements[]" value="社会结构"> 社会结构</label>
                    <label><input type="checkbox" name="elements[]" value="历史背景"> 历史背景</label>
                    <label><input type="checkbox" name="elements[]" value="魔法体系"> 魔法体系</label>
                    <label><input type="checkbox" name="elements[]" value="科技体系"> 科技体系</label>
                    <label><input type="checkbox" name="elements[]" value="种族设定"> 种族设定</label>
                    <label><input type="checkbox" name="elements[]" value="政治制度"> 政治制度</label>
                    <label><input type="checkbox" name="elements[]" value="经济体系"> 经济体系</label>
                </div>
            </div>

            <div class="form-group">
                <label for="complexity">复杂度 *</label>
                <select id="complexity" name="complexity" class="form-control" required>
                    <option value="simple">简单</option>
                    <option value="medium" selected>中等</option>
                    <option value="complex">复杂</option>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">生成世界观</button>
            </div>
        </form>

        <div id="resultContainer" class="result-container" style="display: none;">
            <h2>生成结果</h2>
            <div id="worldviewResult" class="worldview-result"></div>
            <div class="result-actions">
                <button class="btn btn-primary" onclick="copyResult()">复制结果</button>
                <button class="btn btn-outline" onclick="downloadResult()">下载</button>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('worldviewForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = {
        theme: formData.get('theme'),
        genre: formData.get('genre'),
        elements: formData.getAll('elements[]'),
        complexity: formData.get('complexity')
    };

    fetch('/novel_creation/worldview_generator', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('worldviewResult').innerHTML = '<pre>' + data.worldview + '</pre>';
            document.getElementById('resultContainer').style.display = 'block';
        } else {
            alert('生成失败: ' + (data.message || '未知错误'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('生成失败，请重试');
    });
});

function copyResult() {
    const text = document.getElementById('worldviewResult').innerText;
    navigator.clipboard.writeText(text).then(() => {
        alert('已复制到剪贴板');
    });
}

function downloadResult() {
    const text = document.getElementById('worldviewResult').innerText;
    const blob = new Blob([text], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = '世界观设定.txt';
    a.click();
    URL.revokeObjectURL(url);
}
</script>
