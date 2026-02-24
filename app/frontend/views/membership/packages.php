<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>会员套餐 - 星夜阁</title>
    <?php use app\config\FrontendConfig; ?>
    <link rel="stylesheet" href="<?= htmlspecialchars(FrontendConfig::getThemeCssUrl('modules/membership.css')) ?>">
</head>
<body>
    <div class="membership-container">
        <!-- 页面头部 -->
        <header class="page-header">
            <div class="header-content">
                <h1>会员套餐</h1>
                <p>选择适合您的会员套餐，享受更多权益</p>
                <?php if ($currentMembership): ?>
                    <div class="current-membership">
                        <span class="current-label">当前会员：</span>
                        <span class="current-type"><?= $currentMembership['type_name'] ?></span>
                        <?php if (!$currentMembership['is_lifetime']): ?>
                            <span class="current-expire">
                                到期时间：<?= date('Y-m-d', strtotime($currentMembership['end_time'])) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </header>

        <!-- 套餐列表 -->
        <main class="packages-main">
            <div class="packages-grid">
                <?php foreach ($packages as $package): ?>
                    <div class="package-card <?= $package['is_recommended'] ? 'recommended' : '' ?>">
                        <?php if ($package['badge']): ?>
                            <div class="package-badge"><?= htmlspecialchars($package['badge']) ?></div>
                        <?php endif; ?>
                        
                        <div class="package-header">
                            <h3><?= htmlspecialchars($package['name']) ?></h3>
                            <div class="package-type">
                                <?= $this->getMembershipTypeName($package['type']) ?>
                            </div>
                        </div>

                        <div class="package-price">
                            <?php if ($package['discount']): ?>
                                <div class="price-discount">
                                    <span class="original-price">¥<?= $package['original_price'] ?></span>
                                    <span class="discount-price">¥<?= $package['actual_price'] ?></span>
                                    <span class="discount-badge"><?= $package['discount'] ?></span>
                                </div>
                            <?php else: ?>
                                <div class="price-regular">
                                    <span class="price">¥<?= $package['actual_price'] ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="package-duration">
                            <?php if ($package['type'] == 3): ?>
                                <span class="duration-lifetime">终身有效</span>
                            <?php else: ?>
                                <span class="duration-days"><?= $package['duration_days'] ?>天</span>
                            <?php endif; ?>
                        </div>

                        <div class="package-features">
                            <h4>套餐权益</h4>
                            <?php if ($package['features_array']): ?>
                                <ul>
                                    <?php foreach ($package['features_array'] as $feature): ?>
                                        <li>
                                            <i class="icon-check"></i>
                                            <span><?= htmlspecialchars($feature) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="no-features">暂无详细权益说明</p>
                            <?php endif; ?>
                        </div>

                        <div class="package-actions">
                            <?php if ($currentMembership): ?>
                                <?php if ($currentMembership['type'] < $package['type']): ?>
                                    <button class="btn btn-primary" onclick="purchaseMembership(<?= $package['id'] ?>)">
                                        立即升级
                                    </button>
                                <?php elseif ($currentMembership['type'] == $package['type']): ?>
                                    <button class="btn btn-secondary" disabled>
                                        当前套餐
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-outline" onclick="purchaseMembership(<?= $package['id'] ?>)">
                                        选择此套餐
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <button class="btn btn-primary" onclick="purchaseMembership(<?= $package['id'] ?>)">
                                    立即开通
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>

        <!-- 套餐对比 -->
        <section class="package-comparison">
            <h2>套餐对比</h2>
            <div class="comparison-table">
                <table>
                    <thead>
                        <tr>
                            <th>功能权益</th>
                            <?php foreach ($packages as $package): ?>
                                <th class="<?= $package['is_recommended'] ? 'recommended' : '' ?>">
                                    <?= htmlspecialchars($package['name']) ?>
                                    <?php if ($package['badge']): ?>
                                        <div class="table-badge"><?= htmlspecialchars($package['badge']) ?></div>
                                    <?php endif; ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>价格</td>
                            <?php foreach ($packages as $package): ?>
                                <td class="price-cell">
                                    <?php if ($package['discount']): ?>
                                        <div>
                                            <span class="original-price">¥<?= $package['original_price'] ?></span>
                                            <span class="discount-price">¥<?= $package['actual_price'] ?></span>
                                        </div>
                                    <?php else: ?>
                                        <span>¥<?= $package['actual_price'] ?></span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td>有效期</td>
                            <?php foreach ($packages as $package): ?>
                                <td>
                                    <?php if ($package['type'] == 3): ?>
                                        终身
                                    <?php else: ?>
                                        <?= $package['duration_days'] ?>天
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td>AI音乐创作</td>
                            <?php foreach ($packages as $package): ?>
                                <td>
                                    <i class="<?= $package['type'] >= 1 ? 'icon-check' : 'icon-close' ?>"></i>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td>AI动漫制作</td>
                            <?php foreach ($packages as $package): ?>
                                <td>
                                    <i class="<?= $package['type'] >= 1 ? 'icon-check' : 'icon-close' ?>"></i>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td>自定义AI模型</td>
                            <?php foreach ($packages as $package): ?>
                                <td>
                                    <i class="<?= $package['type'] >= 1 ? 'icon-check' : 'icon-close' ?>"></i>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td>批量生成</td>
                            <?php foreach ($packages as $package): ?>
                                <td>
                                    <i class="<?= $package['type'] >= 1 ? 'icon-check' : 'icon-close' ?>"></i>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td>高级导出</td>
                            <?php foreach ($packages as $package): ?>
                                <td>
                                    <i class="<?= $package['type'] >= 1 ? 'icon-check' : 'icon-close' ?>"></i>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td>无限存储</td>
                            <?php foreach ($packages as $package): ?>
                                <td>
                                    <i class="<?= $package['type'] >= 1 ? 'icon-check' : 'icon-close' ?>"></i>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td>云端同步</td>
                            <?php foreach ($packages as $package): ?>
                                <td>
                                    <i class="<?= $package['type'] >= 1 ? 'icon-check' : 'icon-close' ?>"></i>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td>版本历史</td>
                            <?php foreach ($packages as $package): ?>
                                <td>
                                    <i class="<?= $package['type'] >= 1 ? 'icon-check' : 'icon-close' ?>"></i>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td>协作功能</td>
                            <?php foreach ($packages as $package): ?>
                                <td>
                                    <i class="<?= $package['type'] >= 1 ? 'icon-check' : 'icon-close' ?>"></i>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td>操作</td>
                            <?php foreach ($packages as $package): ?>
                                <td>
                                    <?php if ($currentMembership): ?>
                                        <?php if ($currentMembership['type'] < $package['type']): ?>
                                            <button class="btn btn-small btn-primary" onclick="purchaseMembership(<?= $package['id'] ?>)">
                                                升级
                                            </button>
                                        <?php elseif ($currentMembership['type'] == $package['type']): ?>
                                            <button class="btn btn-small btn-secondary" disabled>
                                                当前
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-small btn-outline" onclick="purchaseMembership(<?= $package['id'] ?>)">
                                                选择
                                            </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <button class="btn btn-small btn-primary" onclick="purchaseMembership(<?= $package['id'] ?>)">
                                            开通
                                        </button>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <!-- 购买确认模态框 -->
    <div id="purchaseModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>确认购买</h3>
                <button class="modal-close" onclick="closePurchaseModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="package-summary">
                    <h4 id="modalPackageName"></h4>
                    <div class="price-summary">
                        <div class="price-item">
                            <span class="label">原价：</span>
                            <span class="value" id="modalOriginalPrice"></span>
                        </div>
                        <div class="price-item">
                            <span class="label">优惠价：</span>
                            <span class="value discount" id="modalDiscountPrice"></span>
                        </div>
                        <div class="price-item">
                            <span class="label">节省：</span>
                            <span class="value saved" id="modalSaved"></span>
                        </div>
                    </div>
                </div>
                <div class="payment-options">
                    <h4>支付方式</h4>
                    <div class="payment-methods">
                        <label class="payment-method">
                            <input type="radio" name="payment_method" value="alipay" checked>
                            <span class="method-icon alipay"></span>
                            <span class="method-name">支付宝</span>
                        </label>
                        <label class="payment-method">
                            <input type="radio" name="payment_method" value="wechat">
                            <span class="method-icon wechat"></span>
                            <span class="method-name">微信支付</span>
                        </label>
                    </div>
                </div>
                <div class="auto-renew">
                    <label>
                        <input type="checkbox" id="autoRenew">
                        <span class="checkbox-label">自动续费</span>
                    </label>
                    <p class="auto-renew-desc">到期前自动续费，可随时取消</p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closePurchaseModal()">取消</button>
                <button class="btn btn-primary" onclick="confirmPurchase()">确认购买</button>
            </div>
        </div>
    </div>

    <script src="<?= htmlspecialchars(FrontendConfig::getThemeJsUrl('modules/membership.js')) ?>"></script>
    <script>
        let selectedPackage = null;

        function purchaseMembership(packageId) {
            selectedPackage = <?= json_encode($packages) ?>.find(p => p.id === packageId);
            if (selectedPackage) {
                document.getElementById('modalPackageName').textContent = selectedPackage.name;
                document.getElementById('modalOriginalPrice').textContent = '¥' + selectedPackage.original_price;
                document.getElementById('modalDiscountPrice').textContent = '¥' + selectedPackage.actual_price;
                document.getElementById('modalSaved').textContent = '¥' + selectedPackage.saved;
                document.getElementById('purchaseModal').style.display = 'block';
            }
        }

        function closePurchaseModal() {
            document.getElementById('purchaseModal').style.display = 'none';
        }

        function confirmPurchase() {
            if (!selectedPackage) return;

            const formData = new FormData();
            formData.append('package_id', selectedPackage.id);
            formData.append('payment_method', document.querySelector('input[name="payment_method"]:checked').value);
            formData.append('auto_renew', document.getElementById('autoRenew').checked ? 1 : 0);

            fetch('/membership/purchaseMembership', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('订单创建成功！订单号：' + data.order_no);
                    closePurchaseModal();
                    // 这里可以跳转到支付页面
                } else {
                    alert('订单创建失败：' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('网络错误，请重试');
            });
        }

        // 点击模态框外部关闭
        window.onclick = function(event) {
            const modal = document.getElementById('purchaseModal');
            if (event.target === modal) {
                closePurchaseModal();
            }
        }
    </script>
</body>
</html>

<?php
/**
 * 获取会员类型名称
 */
function getMembershipTypeName($type) {
    $names = [
        1 => '月度会员',
        2 => '年度会员',
        3 => '终身会员'
    ];
    
    return $names[$type] ?? '未知类型';
}
?>