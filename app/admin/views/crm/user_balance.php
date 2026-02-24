<?php
// File: app/admin/views/crm/user_balance.php
?>

<div class="dashboard-v2">
    <div class="dashboard-header-v2">
        <h1 class="dashboard-title-v2">调整余额</h1>
        <p class="dashboard-subtitle-v2">调整用户 #<?php echo $user['id']; ?> 的星夜币余额</p>
        <div class="dashboard-actions-v2">
            <a href="/admin/crm/user/<?php echo $user['id']; ?>" class="btn btn-secondary">返回详情</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mx-auto">
            <div class="card">
                <div class="card-body">
                    <div class="alert alert-info mb-4">
                        当前余额: <strong><?php echo number_format($user['coin_balance'] ?? 0, 2); ?></strong>
                    </div>

                    <form method="POST" action="/admin/crm/user/<?php echo $user['id']; ?>/balance">
                        <div class="mb-3">
                            <label class="form-label">调整金额 (正数增加，负数减少)</label>
                            <input type="number" step="0.01" class="form-control" name="amount" required placeholder="0.00">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">备注说明</label>
                            <textarea class="form-control" name="description" rows="3" required placeholder="请输入调整原因..."></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">确认调整</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
