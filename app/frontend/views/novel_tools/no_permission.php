<style>
    .no-permission-wrapper {
        max-width: 720px;
        margin: 60px auto;
        padding: 40px 24px;
    }
    .no-permission {
        text-align: center;
        padding: 40px 32px;
        background: rgba(255,255,255,0.05);
        border-radius: 12px;
        border: 1px solid rgba(255,255,255,0.1);
        margin-bottom: 32px;
    }
    .no-permission-icon {
        font-size: 56px;
        margin-bottom: 20px;
        opacity: 0.9;
    }
    .no-permission h1 {
        font-size: 22px;
        margin-bottom: 12px;
        color: #fff;
    }
    .no-permission p {
        color: rgba(255,255,255,0.7);
        margin-bottom: 24px;
        line-height: 1.6;
        font-size: 15px;
    }
    .no-permission-features {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
        margin-top: 24px;
    }
    .no-permission-feature-card {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px;
        background: rgba(255,255,255,0.04);
        border-radius: 8px;
        border: 1px solid rgba(255,255,255,0.08);
        color: rgba(255,255,255,0.6);
    }
    .no-permission-feature-card .feature-icon {
        font-size: 28px;
        opacity: 0.7;
    }
    .no-permission-feature-card .feature-name {
        font-size: 15px;
        font-weight: 500;
    }
    .no-permission-feature-card .feature-lock {
        margin-left: auto;
        font-size: 18px;
        opacity: 0.6;
    }
    .btn-group {
        display: flex;
        gap: 12px;
        justify-content: center;
        flex-wrap: wrap;
    }
    .btn {
        padding: 12px 24px;
        border-radius: 6px;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s;
        font-size: 14px;
    }
    .btn-primary {
        background: #667eea;
        color: #fff;
    }
    .btn-primary:hover {
        background: #5568d3;
    }
    .btn-secondary {
        background: rgba(255,255,255,0.1);
        color: #fff;
    }
    .btn-secondary:hover {
        background: rgba(255,255,255,0.2);
    }
    @media (max-width: 480px) {
        .no-permission-features {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="no-permission-wrapper">
    <div class="no-permission">
        <div class="no-permission-icon">🔒</div>
        <h1>无权限访问</h1>
        <p><?= htmlspecialchars($message ?? '您当前没有权限使用星夜创作引擎，请升级会员后使用。') ?></p>
        
        <div class="no-permission-features">
            <div class="no-permission-feature-card">
                <span class="feature-icon">✒</span>
                <span class="feature-name">小说创作</span>
                <span class="feature-lock">🔒</span>
            </div>
            <div class="no-permission-feature-card">
                <span class="feature-icon">♪</span>
                <span class="feature-name">AI音乐</span>
                <span class="feature-lock">🔒</span>
            </div>
            <div class="no-permission-feature-card">
                <span class="feature-icon">🎬</span>
                <span class="feature-name">短剧创作</span>
                <span class="feature-lock">🔒</span>
            </div>
            <div class="no-permission-feature-card">
                <span class="feature-icon">🖼</span>
                <span class="feature-name">图片生成</span>
                <span class="feature-lock">🔒</span>
            </div>
        </div>
        
        <div class="btn-group" style="margin-top: 28px;">
            <a href="/user_center/starry_night_config" class="btn btn-primary">查看权限配置</a>
            <a href="/membership" class="btn btn-primary">升级会员</a>
            <a href="/" class="btn btn-secondary">返回首页</a>
        </div>
    </div>
</div>
