<?php
// File: app/admin/views/crm/user_edit.php
// 用户编辑页面 - 现代化表单设计
?>

<?php 
$adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '/');
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
?>

<?php if (!$isAjax): ?>
<div class="user-form-page">
    <!-- 页面头部 -->
    <div class="user-form-header">
        <div class="user-form-nav">
            <a href="/<?= $adminPrefix ?>/crm/user/<?php echo $user['id']; ?>" class="user-back-link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"/>
                </svg>
                返回用户详情
            </a>
        </div>
        <div class="user-form-title-group">
            <div class="user-form-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
            </div>
            <div>
                <h1 class="user-form-title">编辑用户</h1>
                <p class="user-form-subtitle">修改用户 #<?php echo $user['id']; ?> 的信息</p>
            </div>
        </div>
    </div>
<?php endif; ?>

    <!-- 表单内容 -->
    <form method="POST" action="/<?= $adminPrefix ?>/crm/user/<?php echo $user['id']; ?>/edit" class="user-form-container">
        <div class="user-form-grid">
            <!-- 左侧：账户信息 -->
            <div class="user-form-section">
                <div class="user-section-card">
                    <div class="user-section-header">
                        <h3 class="user-section-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                            账户信息
                        </h3>
                    </div>
                    <div class="user-section-body">
                        <div class="user-form-group">
                            <label class="user-form-label">用户名</label>
                            <input type="text" class="user-form-input user-input-disabled" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                            <span class="user-form-hint">用户名不可修改</span>
                        </div>

                        <div class="user-form-group">
                            <label class="user-form-label">
                                电子邮箱
                                <span class="user-required">*</span>
                            </label>
                            <input type="email" class="user-form-input" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required placeholder="请输入电子邮箱">
                        </div>

                        <div class="user-form-group">
                            <label class="user-form-label">昵称</label>
                            <input type="text" class="user-form-input" name="nickname" value="<?php echo htmlspecialchars($user['nickname'] ?? ''); ?>" placeholder="请输入昵称（可选）">
                        </div>

                        <div class="user-form-group">
                            <label class="user-form-label">新密码</label>
                            <div class="user-password-input">
                                <input type="password" class="user-form-input" name="password" id="passwordInput" placeholder="留空则不修改密码">
                                <button type="button" class="user-password-toggle" onclick="togglePassword()">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="eye-icon">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="eye-off-icon" style="display:none;">
                                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                        <line x1="1" y1="1" x2="23" y2="23"/>
                                    </svg>
                                </button>
                            </div>
                            <span class="user-form-hint">密码长度至少6个字符</span>
                        </div>

                        <div class="user-form-group">
                            <label class="user-form-label">账户状态</label>
                            <div class="user-status-select">
                                <label class="user-status-option <?php echo $user['status'] === 'active' ? 'is-selected' : ''; ?>">
                                    <input type="radio" name="status" value="active" <?php echo $user['status'] === 'active' ? 'checked' : ''; ?>>
                                    <span class="user-status-radio"></span>
                                    <span class="user-status-label">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="20 6 9 17 4 12"/>
                                        </svg>
                                        正常
                                    </span>
                                </label>
                                <label class="user-status-option <?php echo $user['status'] === 'disabled' ? 'is-selected' : ''; ?>">
                                    <input type="radio" name="status" value="disabled" <?php echo $user['status'] === 'disabled' ? 'checked' : ''; ?>>
                                    <span class="user-status-radio"></span>
                                    <span class="user-status-label">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"/>
                                            <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
                                        </svg>
                                        禁用
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 右侧：个人资料 -->
            <div class="user-form-section">
                <div class="user-section-card">
                    <div class="user-section-header">
                        <h3 class="user-section-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                                <line x1="16" y1="13" x2="8" y2="13"/>
                                <line x1="16" y1="17" x2="8" y2="17"/>
                            </svg>
                            个人资料
                        </h3>
                    </div>
                    <div class="user-section-body">
                        <div class="user-form-group">
                            <label class="user-form-label">真实姓名</label>
                            <input type="text" class="user-form-input" name="real_name" value="<?php echo htmlspecialchars($user['real_name'] ?? ''); ?>" placeholder="请输入真实姓名（可选）">
                        </div>

                        <div class="user-form-group">
                            <label class="user-form-label">性别</label>
                            <div class="user-gender-select">
                                <label class="user-gender-option <?php echo empty($user['gender']) ? 'is-selected' : ''; ?>">
                                    <input type="radio" name="gender" value="" <?php echo empty($user['gender']) ? 'checked' : ''; ?>>
                                    <span class="user-gender-radio"></span>
                                    <span class="user-gender-label">未设置</span>
                                </label>
                                <label class="user-gender-option <?php echo ($user['gender'] ?? '') === 'male' ? 'is-selected' : ''; ?>">
                                    <input type="radio" name="gender" value="male" <?php echo ($user['gender'] ?? '') === 'male' ? 'checked' : ''; ?>>
                                    <span class="user-gender-radio"></span>
                                    <span class="user-gender-label">男</span>
                                </label>
                                <label class="user-gender-option <?php echo ($user['gender'] ?? '') === 'female' ? 'is-selected' : ''; ?>">
                                    <input type="radio" name="gender" value="female" <?php echo ($user['gender'] ?? '') === 'female' ? 'checked' : ''; ?>>
                                    <span class="user-gender-radio"></span>
                                    <span class="user-gender-label">女</span>
                                </label>
                                <label class="user-gender-option <?php echo ($user['gender'] ?? '') === 'other' ? 'is-selected' : ''; ?>">
                                    <input type="radio" name="gender" value="other" <?php echo ($user['gender'] ?? '') === 'other' ? 'checked' : ''; ?>>
                                    <span class="user-gender-radio"></span>
                                    <span class="user-gender-label">其他</span>
                                </label>
                            </div>
                        </div>

                        <div class="user-form-group">
                            <label class="user-form-label">出生日期</label>
                            <input type="date" class="user-form-input" name="birthdate" value="<?php echo $user['birthdate'] ?? ''; ?>">
                        </div>

                        <div class="user-form-group">
                            <label class="user-form-label">个人简介</label>
                            <textarea class="user-form-textarea" name="bio" rows="4" placeholder="请输入个人简介（可选）"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 表单操作栏 -->
        <div class="user-form-actions">
            <?php if ($isAjax): ?>
            <button type="button" onclick="if(window.Modal && window.ModalManager && window.ModalManager.activeModal) { window.ModalManager.activeModal.close(); }" class="crm-btn-v2 crm-btn-glass">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
                取消
            </button>
            <?php else: ?>
            <a href="/<?= $adminPrefix ?>/crm/user/<?php echo $user['id']; ?>" class="crm-btn-v2 crm-btn-glass">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
                取消
            </a>
            <?php endif; ?>
            <button type="submit" class="crm-btn-v2 crm-btn-primary-v2">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                    <polyline points="17 21 17 13 7 13 7 21"/>
                    <polyline points="7 3 7 8 15 8"/>
                </svg>
                保存更改
            </button>
        </div>
    </form>
<?php if (!$isAjax): ?>
</div>
<?php endif; ?>

<script>
function togglePassword() {
    const input = document.getElementById('passwordInput');
    const eyeIcon = document.querySelector('.eye-icon');
    const eyeOffIcon = document.querySelector('.eye-off-icon');
    
    if (input.type === 'password') {
        input.type = 'text';
        eyeIcon.style.display = 'none';
        eyeOffIcon.style.display = 'block';
    } else {
        input.type = 'password';
        eyeIcon.style.display = 'block';
        eyeOffIcon.style.display = 'none';
    }
}
</script>

<style>
/* 用户表单页面样式 */
.user-form-page {
    padding: 24px;
    max-width: 100%;
}

/* 页面头部 */
.user-form-header {
    margin-bottom: 32px;
}

.user-form-nav {
    margin-bottom: 20px;
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

.user-form-title-group {
    display: flex;
    align-items: center;
    gap: 16px;
}

.user-form-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    border-radius: 14px;
    color: white;
    box-shadow: 0 4px 16px rgba(99, 102, 241, 0.3);
}

.user-form-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
}

.user-form-subtitle {
    font-size: 0.875rem;
    color: var(--text-muted);
    margin: 4px 0 0;
}

/* 表单容器 */
.user-form-container {
    max-width: 100%;
}

.user-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 24px;
}

.user-form-section {
    display: flex;
    flex-direction: column;
}

/* 区块卡片 */
.user-section-card {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.04));
    backdrop-filter: blur(20px) saturate(180%);
    -webkit-backdrop-filter: blur(20px) saturate(180%);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
}

.user-section-header {
    padding: 16px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.user-section-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
}

.user-section-title svg {
    opacity: 0.7;
}

.user-section-body {
    padding: 20px;
}

/* 表单组 */
.user-form-group {
    margin-bottom: 20px;
}

.user-form-group:last-child {
    margin-bottom: 0;
}

.user-form-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--text-primary);
    margin-bottom: 8px;
}

.user-required {
    color: #ef4444;
    margin-left: 2px;
}

.user-form-input,
.user-form-textarea {
    width: 100%;
    padding: 12px 16px;
    font-size: 0.9rem;
    color: var(--text-primary);
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 10px;
    transition: all 0.2s ease;
}

.user-form-input:focus,
.user-form-textarea:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
    background: rgba(255, 255, 255, 0.12);
}

.user-form-input::placeholder,
.user-form-textarea::placeholder {
    color: var(--text-muted);
}

.user-input-disabled {
    opacity: 0.6;
    cursor: not-allowed;
    background: rgba(255, 255, 255, 0.04);
}

.user-form-hint {
    display: block;
    font-size: 0.75rem;
    color: var(--text-muted);
    margin-top: 6px;
}

/* 密码输入框 */
.user-password-input {
    position: relative;
}

.user-password-input .user-form-input {
    padding-right: 48px;
}

.user-password-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    padding: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color 0.2s ease;
}

.user-password-toggle:hover {
    color: var(--text-primary);
}

/* 状态选择 */
.user-status-select,
.user-gender-select {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.user-status-option,
.user-gender-option {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.user-status-option:hover,
.user-gender-option:hover {
    background: rgba(255, 255, 255, 0.1);
}

.user-status-option input,
.user-gender-option input {
    display: none;
}

.user-status-radio,
.user-gender-radio {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.25);
    border-radius: 50%;
    position: relative;
    transition: all 0.2s ease;
}

.user-status-option.is-selected .user-status-radio,
.user-gender-option.is-selected .user-gender-radio {
    border-color: #6366f1;
    background: #6366f1;
}

.user-status-option.is-selected .user-status-radio::after,
.user-gender-option.is-selected .user-gender-radio::after {
    content: '';
    position: absolute;
    left: 4px;
    top: 4px;
    width: 4px;
    height: 4px;
    background: white;
    border-radius: 50%;
}

.user-status-label,
.user-gender-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.875rem;
    color: var(--text-primary);
}

.user-status-option.is-selected .user-status-label {
    color: #6366f1;
}

/* 文本域 */
.user-form-textarea {
    resize: vertical;
    min-height: 100px;
}

/* 表单操作栏 */
.user-form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding: 20px;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.04));
    backdrop-filter: blur(20px) saturate(180%);
    -webkit-backdrop-filter: blur(20px) saturate(180%);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 16px;
}

/* 响应式设计 */
@media (max-width: 1024px) {
    .user-form-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .user-form-page {
        padding: 16px;
    }
    
    .user-form-title-group {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .user-status-select,
    .user-gender-select {
        flex-direction: column;
    }
    
    .user-status-option,
    .user-gender-option {
        width: 100%;
    }
    
    .user-form-actions {
        flex-direction: column;
    }
    
    .user-form-actions .crm-btn-v2 {
        width: 100%;
        justify-content: center;
    }
}
</style>
