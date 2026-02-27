<?php
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<div class="page-brainstorm-generator">
    <div class="container">
        <h1 class="page-title">脑洞生成器</h1>
        <p class="page-subtitle">AI辅助生成创意脑洞和情节点子</p>

        <form id="brainstormForm" class="brainstorm-form">
            <div class="form-group">
                <label for="theme">主题 *</label>
                <input type="text" id="theme" name="theme" class="form-control" placeholder="例如：时间旅行、平行世界" required>
            </div>

            <div class="form-group">
                <label for="genre">类型 *</label>
                <select id="genre" name="genre" class="form-control" required>
                    <option value="奇幻">奇幻</option>
                    <option value="科幻">科幻</option>
                    <option value="悬疑">悬疑</option>
                    <option value="爱情">爱情</option>
                    <option value="冒险">冒险</option>
                    <option value="其他">其他</option>
                </select>
            </div>

            <div class="form-group">
                <label for="count">生成数量 *</label>
                <select id="count" name="count" class="form-control" required>
                    <option value="3">3个</option>
                    <option value="5" selected>5个</option>
                    <option value="10">10个</option>
                </select>
            </div>

            <div class="form-group">
                <label for="description">额外要求（可选）</label>
                <textarea id="description" name="description" class="form-control" rows="3" placeholder="例如：需要有反转情节、主角是女性等"></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">生成脑洞</button>
            </div>
        </form>

        <div id="resultContainer" class="result-container" style="display: none;">
            <h2>生成结果</h2>
            <div id="brainstormResult" class="brainstorm-result"></div>
            <div class="result-actions">
                <button class="btn btn-primary" onclick="copyResult()">复制结果</button>
                <button class="btn btn-outline" onclick="downloadResult()">下载</button>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('brainstormForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = {
        theme: formData.get('theme'),
        genre: formData.get('genre'),
        count: parseInt(formData.get('count')),
        description: formData.get('description')
    };

    fetch('/novel_creation/brainstorm_generator', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let html = '<ul>';
            data.ideas.forEach((idea, index) => {
                html += `<li><strong>脑洞 ${index + 1}:</strong> ${idea}</li>`;
            });
            html += '</ul>';
            document.getElementById('brainstormResult').innerHTML = html;
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
    const text = document.getElementById('brainstormResult').innerText;
    navigator.clipboard.writeText(text).then(() => {
        alert('已复制到剪贴板');
    });
}

function downloadResult() {
    const text = document.getElementById('brainstormResult').innerText;
    const blob = new Blob([text], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = '脑洞点子.txt';
    a.click();
    URL.revokeObjectURL(url);
}
</script>
