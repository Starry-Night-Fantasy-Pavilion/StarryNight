<div class="page-header"><div class="container"><h1><?= htmlspecialchars($tool_name) ?>结果</h1><p>AI生成的内容，仅供参考</p></div></div>

<div class="container">
    <div class="result-section">
        <?php if (isset($result) && $result['success']): ?>
            <div class="success-badge">生成成功</div>
            <div class="result-content">
                <?= nl2br(htmlspecialchars($result['content'])) ?>
            </div>
        <?php else: ?>
            <div class="error-badge">生成失败</div>
            <div class="error-message">
                <?= htmlspecialchars($result['error'] ?? '未知错误') ?>
            </div>
        <?php endif; ?>
        
        <div class="result-actions">
            <?php if (isset($result) && $result['success']): ?>
                <button class="btn btn-outline" onclick="copyResult()">复制内容</button>
            <?php endif; ?>
            <a href="<?= $back_url ?>" class="btn btn-primary">重新创作</a>
        </div>
    </div>
</div>

<script>
function copyResult() {
    const content = document.querySelector('.result-content').innerText;
    navigator.clipboard.writeText(content).then(() => {
        alert('内容已复制到剪贴板');
    });
}
</script>

<style>
.page-header{background:linear-gradient(135deg,#667eea,#764ba2);color:white;padding:2rem 0;text-align:center;margin-bottom:2rem;}
.page-header h1{font-size:2rem;margin-bottom:0.5rem;}
.result-section{max-width:900px;margin:0 auto 2rem;background:white;border:1px solid #e9ecef;border-radius:12px;padding:2rem;box-shadow:0 2px 8px rgba(0,0,0,0.08);}
.success-badge{background:#d4edda;color:#155724;padding:0.5rem 1rem;border-radius:4px;display:inline-block;margin-bottom:1rem;}
.error-badge{background:#f8d7da;color:#721c24;padding:0.5rem 1rem;border-radius:4px;display:inline-block;margin-bottom:1rem;}
.result-content{background:#f8f9fa;border:1px solid #e9ecef;border-radius:8px;padding:1.5rem;line-height:1.8;white-space:pre-wrap;font-family:inherit;margin-bottom:1.5rem;}
.error-message{background:#fff5f5;border:1px solid #feb2b2;border-radius:8px;padding:1.5rem;color:#c53030;margin-bottom:1.5rem;}
.result-actions{display:flex;gap:1rem;justify-content:center;}
.btn{padding:0.75rem 1.5rem;border:none;border-radius:8px;text-decoration:none;display:inline-flex;align-items:center;gap:0.5rem;cursor:pointer;font-size:1rem;}
.btn-primary{background:linear-gradient(135deg,#667eea,#764ba2);color:white;}
.btn-outline{background:transparent;color:#667eea;border:2px solid #667eea;}
</style>
