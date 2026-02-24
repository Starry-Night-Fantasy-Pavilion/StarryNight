<?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
<?php require __DIR__ . '/_nav.php'; ?>
<div class="card">
    <div class="card-header"><h2 style="margin:0;"><?= $package ? '编辑套餐' : '新增套餐' ?></h2></div>
    <div class="card-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">套餐名称</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($package['name'] ?? '') ?>" required>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">金额（元）</label>
                    <input type="number" step="0.01" name="amount" class="form-control" value="<?= htmlspecialchars($package['amount'] ?? '') ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">赠送星夜币数量</label>
                    <input type="number" name="coin_amount" class="form-control" value="<?= (int)($package['coin_amount'] ?? 0) ?>" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">有效期（天）</label>
                <input type="number" name="valid_days" class="form-control" value="<?= (int)($package['valid_days'] ?? 0) ?>" placeholder="0=永久">
            </div>
            <div class="mb-3">
                <label class="form-label">销售状态</label>
                <select name="sale_status" class="form-control">
                    <option value="on_sale" <?= ($package['sale_status'] ?? '') === 'on_sale' ? 'selected' : '' ?>>在售</option>
                    <option value="off_sale" <?= ($package['sale_status'] ?? '') === 'off_sale' ? 'selected' : '' ?>>下架</option>
                </select>
            </div>
            <div class="mb-3">
                <label><input type="checkbox" name="is_limited_offer" value="1" <?= !empty($package['is_limited_offer']) ? 'checked' : '' ?>> 限时优惠</label>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">优惠开始时间</label>
                    <input type="datetime-local" name="offer_start_at" class="form-control" value="<?= !empty($package['offer_start_at']) ? date('Y-m-d\TH:i', strtotime($package['offer_start_at'])) : '' ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">优惠结束时间</label>
                    <input type="datetime-local" name="offer_end_at" class="form-control" value="<?= !empty($package['offer_end_at']) ? date('Y-m-d\TH:i', strtotime($package['offer_end_at'])) : '' ?>">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">排序</label>
                <input type="number" name="sort_order" class="form-control" value="<?= (int)($package['sort_order'] ?? 0) ?>">
            </div>
            <button type="submit" class="btn btn-primary">保存</button>
            <a href="/<?= $adminPrefix ?>/finance/coin-packages" class="btn btn-secondary">返回</a>
        </form>
    </div>
</div>
