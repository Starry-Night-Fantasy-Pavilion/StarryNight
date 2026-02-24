<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>充值中心 - 星夜阁</title>
    <?php use app\config\FrontendConfig; ?>
    <link rel="stylesheet" href="<?= htmlspecialchars(FrontendConfig::getThemeCssUrl('modules/membership.css')) ?>">
</head>
<body>
    <div class="membership-container">
        <!-- 页面头部 -->
        <header class="page-header">
            <div class="header-content">
                <h1>充值中心</h1>
                <p>选择充值套餐，获取更多星夜币</p>
                <div class="current-balance">
                    <span class="balance-label">当前余额：</span>
                    <span class="balance-amount">
                        <span class="currency">⭐</span>
                        <span class="amount"><?= number_format($tokenBalance['balance']) ?></span>
                    </span>
                </div>
            </div>
        </header>

        <!-- 热门套餐 -->
        <?php if ($hotPackages): ?>
            <section class="hot-packages">
                <h2>热门套餐</h2>
                <div class="hot-packages-grid">
                    <?php foreach ($hotPackages as $package): ?>
                        <div class="hot-package-card">
                            <div class="hot-badge">🔥 热门</div>
                            <h3><?= htmlspecialchars($package['name']) ?></h3>
                            <div class="package-tokens">
                                <span class="token-amount"><?= number_format($package['token_info']['total']) ?></span>
                                <span class="token-label">星夜币</span>
                            </div>
                            <div class="package-price">
                                <?php if ($package['discount']): ?>
                                    <div class="price-discount">
                                        <span class="original-price">¥<?= $package['price'] ?></span>
                                        <span class="discount-price">¥<?= $package['actual_price'] ?></span>
                                        <span class="discount-badge"><?= $package['discount'] ?></span>
                                    </div>
                                <?php else: ?>
                                    <div class="price-regular">
                                        <span class="price">¥<?= $package['actual_price'] ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if ($package['token_info']['bonus'] > 0 || $package['token_info']['vip_bonus'] > 0): ?>
                                <div class="package-bonus">
                                    <?php if ($package['token_info']['bonus'] > 0): ?>
                                        <span class="bonus-item">赠送 <?= $package['token_info']['bonus'] ?> 星夜币</span>
                                    <?php endif; ?>
                                    <?php if ($package['token_info']['vip_bonus'] > 0): ?>
                                        <span class="bonus-item">会员额外赠送 <?= $package['token_info']['vip_bonus'] ?> 星夜币</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <div class="package-actions">
                                <button class="btn btn-primary" onclick="purchaseRecharge(<?= $package['id'] ?>)">
                                    立即充值
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- 所有套餐 -->
        <main class="recharge-main">
            <h2>所有套餐</h2>
            <div class="packages-grid">
                <?php foreach ($packages as $package): ?>
                    <div class="recharge-card <?= $package['is_hot'] ? 'hot' : '' ?>">
                        <?php if ($package['badge']): ?>
                            <div class="recharge-badge"><?= htmlspecialchars($package['badge']) ?></div>
                        <?php endif; ?>
                        
                        <div class="card-header">
                            <h3><?= htmlspecialchars($package['name']) ?></h3>
                            <?php if ($package['is_hot']): ?>
                                <div class="hot-indicator">🔥 热门</div>
                            <?php endif; ?>
                        </div>

                        <div class="card-tokens">
                            <div class="token-display">
                                <span class="token-amount"><?= number_format($package['token_info']['total']) ?></span>
                                <span class="token-label">星夜币</span>
                            </div>
                            <div class="token-breakdown">
                                <div class="breakdown-item">
                                    <span class="label">基础：</span>
                                    <span class="value"><?= number_format($package['token_info']['base']) ?></span>
                                </div>
                                <?php if ($package['token_info']['bonus'] > 0): ?>
                                    <div class="breakdown-item bonus">
                                        <span class="label">赠送：</span>
                                        <span class="value">+<?= number_format($package['token_info']['bonus']) ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($package['token_info']['vip_bonus'] > 0): ?>
                                    <div class="breakdown-item vip-bonus">
                                        <span class="label">会员额外：</span>
                                        <span class="value">+<?= number_format($package['token_info']['vip_bonus']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card-price">
                            <?php if ($package['discount']): ?>
                                <div class="price-discount">
                                    <span class="original-price">¥<?= $package['price'] ?></span>
                                    <span class="discount-price">¥<?= $package['actual_price'] ?></span>
                                    <span class="discount-badge"><?= $package['discount'] ?></span>
                                </div>
                            <?php else: ?>
                                <div class="price-regular">
                                    <span class="price">¥<?= $package['actual_price'] ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="card-description">
                            <?php if ($package['description']): ?>
                                <p><?= htmlspecialchars($package['description']) ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="card-actions">
                            <button class="btn btn-primary" onclick="purchaseRecharge(<?= $package['id'] ?>)">
                                立即充值
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>

        <!-- 充值说明 -->
        <section class="recharge-info">
            <h2>充值说明</h2>
            <div class="info-grid">
                <div class="info-item">
                    <h3>到账时间</h3>
                    <p>充值成功后，星夜币将立即到账</p>
                </div>
                <div class="info-item">
                    <h3>会员优惠</h3>
                    <p>会员用户享受充值折扣和额外赠送星夜币</p>
                </div>
                <div class="info-item">
                    <h3>使用规则</h3>
                    <p>星夜币可用于AI生成、高级功能解锁等消费场景</p>
                </div>
                <div class="info-item">
                    <h3>退款政策</h3>
                    <p>充值成功后，除系统故障外不予退款</p>
                </div>
            </div>
        </section>
    </div>

    <!-- 购买确认模态框 -->
    <div id="rechargeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>确认充值</h3>
                <button class="modal-close" onclick="closeRechargeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="package-summary">
                    <h4 id="modalPackageName"></h4>
                    <div class="token-summary">
                        <div class="token-item">
                            <span class="label">基础星夜币：</span>
                            <span class="value" id="modalBaseTokens"></span>
                        </div>
                        <div class="token-item bonus">
                            <span class="label">赠送星夜币：</span>
                            <span class="value" id="modalBonusTokens"></span>
                        </div>
                        <div class="token-item vip-bonus">
                            <span class="label">会员额外：</span>
                            <span class="value" id="modalVipBonus"></span>
                        </div>
                        <div class="token-item total">
                            <span class="label">总计：</span>
                            <span class="value total" id="modalTotalTokens"></span>
                        </div>
                    </div>
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
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeRechargeModal()">取消</button>
                <button class="btn btn-primary" onclick="confirmRecharge()">确认充值</button>
            </div>
        </div>
    </div>

    <script src="<?= htmlspecialchars(FrontendConfig::getThemeJsUrl('modules/membership.js')) ?>"></script>
    <script>
        let selectedPackage = null;

        function purchaseRecharge(packageId) {
            selectedPackage = <?= json_encode($packages) ?>.find(p => p.id === packageId);
            if (selectedPackage) {
                document.getElementById('modalPackageName').textContent = selectedPackage.name;
                document.getElementById('modalBaseTokens').textContent = number_format(selectedPackage.token_info.base);
                document.getElementById('modalBonusTokens').textContent = numberFormat(selectedPackage.token_info.bonus);
                document.getElementById('modalVipBonus').textContent = numberFormat(selectedPackage.token_info.vip_bonus);
                document.getElementById('modalTotalTokens').textContent = numberFormat(selectedPackage.token_info.total);
                document.getElementById('modalOriginalPrice').textContent = '¥' + selectedPackage.price;
                document.getElementById('modalDiscountPrice').textContent = '¥' + selectedPackage.actual_price;
                document.getElementById('modalSaved').textContent = '¥' + selectedPackage.saved;
                document.getElementById('rechargeModal').style.display = 'block';
            }
        }

        function closeRechargeModal() {
            document.getElementById('rechargeModal').style.display = 'none';
        }

        function confirmRecharge() {
            if (!selectedPackage) return;

            const formData = new FormData();
            formData.append('package_id', selectedPackage.id);
            formData.append('payment_method', document.querySelector('input[name="payment_method"]:checked').value);

            fetch('/membership/rechargeTokens', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('订单创建成功！订单号：' + data.order_no + '\n获得星夜币：' + data.tokens);
                    closeRechargeModal();
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
            const modal = document.getElementById('rechargeModal');
            if (event.target === modal) {
                closeRechargeModal();
            }
        }

        // 数字格式化
        function numberFormat(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
    </script>
</body>
</html>