<style>
    .no-permission {
        max-width: 600px;
        margin: 100px auto;
        padding: 40px;
        text-align: center;
        background: rgba(255,255,255,0.05);
        border-radius: 12px;
        border: 1px solid rgba(255,255,255,0.1);
    }
    .no-permission-icon {
        font-size: 64px;
        margin-bottom: 24px;
    }
    .no-permission h1 {
        font-size: 24px;
        margin-bottom: 16px;
        color: #fff;
    }
    .no-permission p {
        color: rgba(255,255,255,0.7);
        margin-bottom: 24px;
        line-height: 1.6;
    }
    .btn-group {
        display: flex;
        gap: 12px;
        justify-content: center;
    }
    .btn {
        padding: 12px 24px;
        border-radius: 6px;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s;
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
</style>

<div class="no-permission">
    <div class="no-permission-icon">🔒</div>
    <h1>无权限访问</h1>
    <p><?= htmlspecialchars($message ?? '您当前没有权限使用此功能，请升级会员后使用。') ?></p>
    <div class="btn-group">
        <a href="/user_center/starry_night_config" class="btn btn-primary">查看权限配置</a>
        <a href="/" class="btn btn-secondary">返回首页</a>
    </div>
</div>
