<?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
<?php require __DIR__ . '/_nav.php'; ?>
<div class="card">
    <div class="card-header"><h2 style="margin:0;"><?= $level ? '编辑等级' : '新增等级' ?></h2></div>
    <div class="card-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" class="form-horizontal">
            <div class="mb-3">
                <label class="form-label">等级名称</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($level['name'] ?? '') ?>" required placeholder="如：普通、VIP、SVIP">
            </div>
            <div class="mb-3">
                <label class="form-label">描述</label>
                <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($level['description'] ?? '') ?></textarea>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">月付价格</label>
                    <input type="number" step="0.01" name="price_monthly" class="form-control" value="<?= htmlspecialchars($level['price_monthly'] ?? '0') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">年付价格</label>
                    <input type="number" step="0.01" name="price_yearly" class="form-control" value="<?= htmlspecialchars($level['price_yearly'] ?? '0') ?>">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">星夜币购买折扣（%）</label>
                <input type="number" step="0.01" min="0" max="100" name="coin_discount_percent" class="form-control" value="<?= htmlspecialchars($level['coin_discount_percent'] ?? '100') ?>" placeholder="100=无折扣">
            </div>
            <div class="mb-3">
                <label class="form-label">功能权限（JSON 数组）</label>
                <textarea name="permissions_json" class="form-control" rows="2" placeholder='["ai_chat","download"]'><?= htmlspecialchars(is_string($level['permissions_json'] ?? '') ? $level['permissions_json'] : json_encode($level['permissions_json'] ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">使用配额（JSON 对象）</label>
                <textarea name="quota_json" class="form-control" rows="2" placeholder='{"ai_daily":100,"download_daily":10}'><?= htmlspecialchars(is_string($level['quota_json'] ?? '') ? $level['quota_json'] : json_encode($level['quota_json'] ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">权益说明（JSON）</label>
                <textarea name="benefits_json" class="form-control" rows="2"><?= htmlspecialchars(is_string($level['benefits_json'] ?? '') ? $level['benefits_json'] : json_encode($level['benefits_json'] ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) ?></textarea>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">排序</label>
                    <input type="number" name="sort_order" class="form-control" value="<?= (int)($level['sort_order'] ?? 0) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">状态</label>
                    <div>
                        <label><input type="checkbox" name="is_active" value="1" <?= !isset($level['is_active']) || !empty($level['is_active']) ? 'checked' : '' ?>> 启用</label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">保存</button>
            <a href="/<?= $adminPrefix ?>/finance/membership-levels" class="btn btn-secondary">返回</a>
        </form>
    </div>
</div>
