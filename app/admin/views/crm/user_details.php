<?php
// File: app/admin/views/crm/user_details.php
// 用户详情页面 - 现代化卡片布局设计
?>

<?php $adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '/'); ?>

<div class="user-detail-page">
    <!-- 页面头部 -->
    <div class="user-detail-header">
        <div class="user-detail-nav">
            <a href="/<?= $adminPrefix ?>/crm/users" class="user-back-link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"/>
                </svg>
                返回用户列表
            </a>
        </div>
        <div class="user-detail-actions">
            <a href="/<?= $adminPrefix ?>/crm/user/<?php echo $user['id']; ?>/balance" class="crm-btn-v2 crm-btn-glass">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 6v12"/>
                    <path d="M8 10h8"/>
                    <path d="M8 14h8"/>
                </svg>
                调整余额
            </a>
            <a href="/<?= $adminPrefix ?>/crm/user/<?php echo $user['id']; ?>/edit" class="crm-btn-v2 crm-btn-primary-v2">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
                编辑用户
            </a>
        </div>
    </div>

    <!-- 用户概览卡片 -->
    <div class="user-overview-card">
        <div class="user-overview-main">
            <div class="user-avatar-large">
                <?php if (!empty($user['avatar'])): ?>
                    <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="">
                <?php else: ?>
                    <span class="user-avatar-letter"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></span>
                <?php endif; ?>
            </div>
            <div class="user-overview-info">
                <div class="user-name-row">
                    <h1 class="user-display-name"><?php echo htmlspecialchars($user['nickname'] ?? $user['username']); ?></h1>
                    <span class="user-username">@<?php echo htmlspecialchars($user['username']); ?></span>
                </div>
                <div class="user-badges">
                    <?php 
                    $status = $user['status'] ?? 'active';
                    $statusConfig = [
                        'active' => ['label' => '正常', 'class' => 'user-status-active'],
                        'disabled' => ['label' => '禁用', 'class' => 'user-status-disabled'],
                        'frozen' => ['label' => '冻结', 'class' => 'user-status-frozen'],
                        'deleted' => ['label' => '已删除', 'class' => 'user-status-deleted']
                    ];
                    $statusInfo = $statusConfig[$status] ?? ['label' => $status, 'class' => 'user-status-unknown'];
                    ?>
                    <span class="user-status-badge <?php echo $statusInfo['class']; ?>">
                        <?php if ($status === 'active'): ?>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        <?php endif; ?>
                        <?php echo $statusInfo['label']; ?>
                    </span>
                    
                    <?php if (!empty($user['membership_level_name'])): ?>
                    <span class="user-membership-badge">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                        </svg>
                        <?php echo htmlspecialchars($user['membership_level_name']); ?>
                    </span>
                    <?php else: ?>
                    <span class="user-membership-badge user-member-none">普通用户</span>
                    <?php endif; ?>
                </div>
                <div class="user-meta-row">
                    <div class="user-meta-item">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                        <?php echo htmlspecialchars($user['email']); ?>
                    </div>
                    <div class="user-meta-item">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        注册于 <?php echo date('Y年m月d日', strtotime($user['created_at'])); ?>
                    </div>
                    <div class="user-meta-item">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                        <?php echo $user['last_login_at'] ? '最后登录 ' . date('Y-m-d H:i', strtotime($user['last_login_at'])) : '从未登录'; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="user-overview-stats">
            <div class="user-stat-item">
                <div class="user-stat-icon user-stat-coin">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 6v12"/>
                        <path d="M8 10h8"/>
                        <path d="M8 14h8"/>
                    </svg>
                </div>
                <div class="user-stat-content">
                    <div class="user-stat-value"><?php echo number_format($user['coin_balance'] ?? 0); ?></div>
                    <div class="user-stat-label">星夜币</div>
                </div>
            </div>
            <div class="user-stat-item">
                <div class="user-stat-icon user-stat-id">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </div>
                <div class="user-stat-content">
                    <div class="user-stat-value">#<?php echo $user['id']; ?></div>
                    <div class="user-stat-label">用户ID</div>
                </div>
            </div>
        </div>
    </div>

    <!-- 详情内容区域 -->
    <div class="user-detail-grid">
        <!-- 左侧：基本信息 -->
        <div class="user-detail-left">
            <!-- 基本资料卡片 -->
            <div class="user-info-card">
                <div class="user-card-header">
                    <h3 class="user-card-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        基本资料
                    </h3>
                </div>
                <div class="user-card-body">
                    <div class="user-info-list">
                        <div class="user-info-row">
                            <span class="user-info-label">用户名</span>
                            <span class="user-info-value"><?php echo htmlspecialchars($user['username']); ?></span>
                        </div>
                        <div class="user-info-row">
                            <span class="user-info-label">电子邮箱</span>
                            <span class="user-info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <div class="user-info-row">
                            <span class="user-info-label">真实姓名</span>
                            <span class="user-info-value"><?php echo htmlspecialchars($user['real_name'] ?? '-'); ?></span>
                        </div>
                        <div class="user-info-row">
                            <span class="user-info-label">性别</span>
                            <span class="user-info-value">
                                <?php 
                                $genders = ['male' => '男', 'female' => '女', 'other' => '其他'];
                                echo $genders[$user['gender'] ?? ''] ?? '-'; 
                                ?>
                            </span>
                        </div>
                        <div class="user-info-row">
                            <span class="user-info-label">出生日期</span>
                            <span class="user-info-value"><?php echo $user['birthdate'] ?? '-'; ?></span>
                        </div>
                        <div class="user-info-row">
                            <span class="user-info-label">注册时间</span>
                            <span class="user-info-value"><?php echo $user['created_at']; ?></span>
                        </div>
                        <div class="user-info-row">
                            <span class="user-info-label">最后登录</span>
                            <span class="user-info-value"><?php echo $user['last_login_at'] ?? '从未登录'; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 个人简介卡片 -->
            <?php if (!empty($user['bio'])): ?>
            <div class="user-info-card">
                <div class="user-card-header">
                    <h3 class="user-card-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                            <polyline points="10 9 9 9 8 9"/>
                        </svg>
                        个人简介
                    </h3>
                </div>
                <div class="user-card-body">
                    <p class="user-bio-text"><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- 右侧：交易记录和会员历史 -->
        <div class="user-detail-right">
            <!-- 星夜币交易记录 -->
            <div class="user-info-card">
                <div class="user-card-header">
                    <h3 class="user-card-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                        星夜币交易记录
                    </h3>
                    <span class="user-card-badge">最近20条</span>
                </div>
                <div class="user-card-body">
                    <?php if (!empty($transactions)): ?>
                        <div class="user-transactions-list">
                            <?php foreach ($transactions as $tx): ?>
                            <div class="user-transaction-item">
                                <div class="user-tx-icon <?php echo ($tx['type'] ?? '') === 'recharge' ? 'user-tx-in' : (($tx['type'] ?? '') === 'spend' ? 'user-tx-out' : 'user-tx-adjust'); ?>">
                                    <?php if (($tx['type'] ?? '') === 'recharge'): ?>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="12" y1="19" x2="12" y2="5"/>
                                        <polyline points="5 12 12 5 19 12"/>
                                    </svg>
                                    <?php elseif (($tx['type'] ?? '') === 'spend'): ?>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="12" y1="5" x2="12" y2="19"/>
                                        <polyline points="19 12 12 19 5 12"/>
                                    </svg>
                                    <?php else: ?>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                        <line x1="8" y1="12" x2="16" y2="12"/>
                                    </svg>
                                    <?php endif; ?>
                                </div>
                                <div class="user-tx-info">
                                    <div class="user-tx-type">
                                        <?php
                                        $typeMap = [
                                            'recharge' => '充值',
                                            'spend' => '消耗',
                                            'adjust' => '调整',
                                            'system_adjust' => '系统调整'
                                        ];
                                        echo $typeMap[$tx['type']] ?? $tx['type'];
                                        ?>
                                    </div>
                                    <div class="user-tx-time"><?php echo date('Y-m-d H:i', strtotime($tx['created_at'])); ?></div>
                                </div>
                                <div class="user-tx-amount <?php echo ($tx['amount'] ?? 0) >= 0 ? 'user-tx-positive' : 'user-tx-negative'; ?>">
                                    <?php echo ($tx['amount'] ?? 0) >= 0 ? '+' : ''; ?><?php echo number_format($tx['amount'] ?? 0); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="user-empty-state">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M12 6v12"/>
                                <path d="M8 10h8"/>
                                <path d="M8 14h8"/>
                            </svg>
                            <p>暂无交易记录</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 会员历史 -->
            <?php if (!empty($membershipHistory)): ?>
            <div class="user-info-card">
                <div class="user-card-header">
                    <h3 class="user-card-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                        </svg>
                        会员历史
                    </h3>
                </div>
                <div class="user-card-body">
                    <div class="user-membership-list">
                        <?php foreach ($membershipHistory as $membership): ?>
                        <div class="user-membership-item">
                            <div class="user-mem-level"><?php echo htmlspecialchars($membership['level_name'] ?? '-'); ?></div>
                            <div class="user-mem-status">
                                <?php if (($membership['status'] ?? '') === 'active'): ?>
                                <span class="user-mem-active">有效</span>
                                <?php else: ?>
                                <span class="user-mem-expired"><?php echo htmlspecialchars($membership['status'] ?? '-'); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="user-mem-period">
                                <?php echo $membership['start_at'] ? date('Y-m-d', strtotime($membership['start_at'])) : '-'; ?> ~ 
                                <?php echo $membership['expire_at'] ? date('Y-m-d', strtotime($membership['expire_at'])) : '-'; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* 用户详情页面样式 */
.user-detail-page {
    padding: 24px;
    max-width: 100%;
}

/* 页面头部 */
.user-detail-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}

.user-back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--text-secondary);
    text-decoration: none;
    font-size: 0.875rem;
    padding: 8px 16px;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 8px;
    transition: all 0.2s ease;
}

.user-back-link:hover {
    color: var(--text-primary);
    background: rgba(255, 255, 255, 0.12);
}

.user-detail-actions {
    display: flex;
    gap: 12px;
}

/* 用户概览卡片 */
.user-overview-card {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.12), rgba(255, 255, 255, 0.05));
    backdrop-filter: blur(20px) saturate(180%);
    -webkit-backdrop-filter: blur(20px) saturate(180%);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 20px;
    padding: 32px;
    margin-bottom: 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 32px;
    flex-wrap: wrap;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12), inset 0 1px 0 rgba(255, 255, 255, 0.2);
}

.user-overview-main {
    display: flex;
    align-items: center;
    gap: 24px;
    flex: 1;
    min-width: 0;
}

.user-avatar-large {
    width: 100px;
    height: 100px;
    border-radius: 20px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
    box-shadow: 0 8px 24px rgba(99, 102, 241, 0.3);
}

.user-avatar-large img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.user-avatar-letter {
    font-size: 2.5rem;
    font-weight: 700;
    color: white;
}

.user-overview-info {
    flex: 1;
    min-width: 0;
}

.user-name-row {
    display: flex;
    align-items: baseline;
    gap: 12px;
    margin-bottom: 12px;
    flex-wrap: wrap;
}

.user-display-name {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
}

.user-username {
    font-size: 0.9rem;
    color: var(--text-muted);
}

.user-badges {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
    flex-wrap: wrap;
}

.user-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 12px;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 20px;
}

.user-status-active {
    background: rgba(16, 185, 129, 0.15);
    color: #10b981;
}

.user-status-disabled {
    background: rgba(245, 158, 11, 0.15);
    color: #f59e0b;
}

.user-status-frozen {
    background: rgba(59, 130, 246, 0.15);
    color: #3b82f6;
}

.user-status-deleted {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
}

.user-membership-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 12px;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 20px;
    background: linear-gradient(135deg, rgba(234, 179, 8, 0.2), rgba(245, 158, 11, 0.15));
    color: #fbbf24;
    border: 1px solid rgba(234, 179, 8, 0.3);
}

.user-member-none {
    background: rgba(148, 163, 184, 0.15);
    color: #94a3b8;
    border: none;
}

.user-meta-row {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.user-meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.user-meta-item svg {
    opacity: 0.6;
}

/* 统计区域 */
.user-overview-stats {
    display: flex;
    gap: 24px;
    flex-shrink: 0;
}

.user-stat-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 24px;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.user-stat-icon {
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
}

.user-stat-coin {
    background: linear-gradient(135deg, rgba(251, 191, 36, 0.2), rgba(245, 158, 11, 0.15));
    color: #fbbf24;
}

.user-stat-id {
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(139, 92, 246, 0.15));
    color: #818cf8;
}

.user-stat-content {
    display: flex;
    flex-direction: column;
}

.user-stat-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text-primary);
}

.user-stat-label {
    font-size: 0.75rem;
    color: var(--text-muted);
}

/* 详情网格 */
.user-detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
}

.user-detail-left,
.user-detail-right {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

/* 信息卡片 */
.user-info-card {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.04));
    backdrop-filter: blur(20px) saturate(180%);
    -webkit-backdrop-filter: blur(20px) saturate(180%);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
}

.user-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.user-card-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
}

.user-card-title svg {
    opacity: 0.7;
}

.user-card-badge {
    font-size: 0.75rem;
    color: var(--text-muted);
    padding: 3px 10px;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 20px;
}

.user-card-body {
    padding: 20px;
}

/* 信息列表 */
.user-info-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.user-info-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 8px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.user-info-row:last-child {
    border-bottom: none;
}

.user-info-label {
    font-size: 0.875rem;
    color: var(--text-muted);
    flex-shrink: 0;
    width: 100px;
}

.user-info-value {
    font-size: 0.875rem;
    color: var(--text-primary);
    text-align: right;
    word-break: break-all;
}

/* 个人简介 */
.user-bio-text {
    font-size: 0.9rem;
    color: var(--text-secondary);
    line-height: 1.7;
    margin: 0;
}

/* 交易记录列表 */
.user-transactions-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.user-transaction-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: rgba(255, 255, 255, 0.04);
    border-radius: 10px;
    transition: background 0.2s ease;
}

.user-transaction-item:hover {
    background: rgba(255, 255, 255, 0.08);
}

.user-tx-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    flex-shrink: 0;
}

.user-tx-in {
    background: rgba(16, 185, 129, 0.15);
    color: #10b981;
}

.user-tx-out {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
}

.user-tx-adjust {
    background: rgba(245, 158, 11, 0.15);
    color: #f59e0b;
}

.user-tx-info {
    flex: 1;
    min-width: 0;
}

.user-tx-type {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--text-primary);
}

.user-tx-time {
    font-size: 0.75rem;
    color: var(--text-muted);
}

.user-tx-amount {
    font-family: 'SF Mono', 'Monaco', 'Cascadia Code', monospace;
    font-size: 0.9rem;
    font-weight: 600;
}

.user-tx-positive {
    color: #10b981;
}

.user-tx-negative {
    color: #ef4444;
}

/* 会员历史列表 */
.user-membership-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.user-membership-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: rgba(255, 255, 255, 0.04);
    border-radius: 10px;
}

.user-mem-level {
    font-weight: 600;
    color: var(--text-primary);
    flex: 1;
}

.user-mem-status {
    flex-shrink: 0;
}

.user-mem-active {
    font-size: 0.75rem;
    padding: 3px 10px;
    background: rgba(16, 185, 129, 0.15);
    color: #10b981;
    border-radius: 20px;
}

.user-mem-expired {
    font-size: 0.75rem;
    padding: 3px 10px;
    background: rgba(148, 163, 184, 0.15);
    color: #94a3b8;
    border-radius: 20px;
}

.user-mem-period {
    font-size: 0.75rem;
    color: var(--text-muted);
    flex-shrink: 0;
}

/* 空状态 */
.user-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    padding: 32px;
    color: var(--text-muted);
    text-align: center;
}

.user-empty-state svg {
    opacity: 0.4;
}

.user-empty-state p {
    margin: 0;
    font-size: 0.875rem;
}

/* 响应式设计 */
@media (max-width: 1024px) {
    .user-detail-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .user-detail-page {
        padding: 16px;
    }
    
    .user-detail-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .user-detail-actions {
        justify-content: stretch;
    }
    
    .user-detail-actions .crm-btn-v2 {
        flex: 1;
        justify-content: center;
    }
    
    .user-overview-card {
        flex-direction: column;
        text-align: center;
        padding: 24px;
    }
    
    .user-overview-main {
        flex-direction: column;
    }
    
    .user-name-row {
        flex-direction: column;
        align-items: center;
        gap: 4px;
    }
    
    .user-badges {
        justify-content: center;
    }
    
    .user-meta-row {
        flex-direction: column;
        align-items: center;
        gap: 8px;
    }
    
    .user-overview-stats {
        width: 100%;
        justify-content: center;
    }
    
    .user-stat-item {
        flex: 1;
        justify-content: center;
    }
    
    .user-info-row {
        flex-direction: column;
        gap: 4px;
    }
    
    .user-info-label {
        width: auto;
    }
    
    .user-info-value {
        text-align: left;
    }
}
</style>
