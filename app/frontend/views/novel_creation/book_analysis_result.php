<div class="page-header"><div class="container"><h1>拆书分析结果</h1><p>AI对原文的详细分析</p></div></div>

<div class="container">
    <div class="result-section">
        <?php if (isset($result) && $result['success']): ?>
            <div class="success-badge">分析完成</div>
            <div class="result-content">
                <?= nl2br(htmlspecialchars($result['content'])) ?>
            </div>
            
            <div class="next-step">
                <a href="/novel_creation/imitation_writing" class="btn btn-primary btn-lg">开始仿写</a>
            </div>
        <?php else: ?>
            <div class="error-badge">分析失败</div>
            <div class="error-message">
                <?= htmlspecialchars($result['error'] ?? '未知错误') ?>
            </div>
        <?php endif; ?>
        
        <div class="result-actions">
            <a href="/novel_creation/book_analysis" class="btn btn-outline">重新分析</a>
        </div>
    </div>
</div>

<style>
.page-header{background:linear-gradient(135deg,#667eea,#764ba2);color:white;padding:2rem 0;text-align:center;margin-bottom:2rem;}
.result-section{max-width:900px;margin:0 auto 2rem;background:white;border:1px solid #e9ecef;border-radius:12px;padding:2rem;}
.success-badge{background:#d4edda;color:#155724;padding:0.5rem 1rem;border-radius:4px;display:inline-block;margin-bottom:1rem;}
.error-badge{background:#f8d7da;color:#721c24;padding:0.5rem 1rem;border-radius:4px;display:inline-block;margin-bottom:1rem;}
.result-content{background:#f8f9fa;border:1px solid #e9ecef;border-radius:8px;padding:1.5rem;line-height:1.8;white-space:pre-wrap;margin-bottom:1.5rem;}
.error-message{background:#fff5f5;border:1px solid #feb2b2;border-radius:8px;padding:1.5rem;color:#c53030;margin-bottom:1.5rem;}
.next-step{text-align:center;margin:2rem 0;}
.result-actions{text-align:center;}
.btn{padding:0.75rem 1.5rem;border:none;border-radius:8px;text-decoration:none;display:inline-flex;align-items:center;gap:0.5rem;cursor:pointer;font-size:1rem;}
.btn-primary{background:linear-gradient(135deg,#667eea,#764ba2);color:white;}
.btn-outline{background:transparent;color:#667eea;border:2px solid #667eea;}
</style>
