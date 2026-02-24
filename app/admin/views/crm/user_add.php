<?php
// File: app/admin/views/crm/user_add.php
// 添加新用户页面 - 现代化表单设计
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
            <a href="/<?= $adminPrefix ?>/crm/users" class="user-back-link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"/>
                </svg>
                返回用户列表
            </a>
        </div>
        <div class="user-form-title-group">
            <div class="user-form-icon user-form-icon-add">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="8.5" cy="7" r="4"/>
                    <line x1="20" y1="8" x2="20" y2="14"/>
                    <line x1="23" y1="11" x2="17" y2="11"/>
                </svg>
            </div>
            <div>
                <h1 class="user-form-title">添加新用户</h1>
                <p class="user-form-subtitle">创建新的系统用户账户</p>
            </div>
        </div>
    </div>

    <!-- 错误提示 -->
    <?php if (isset($error)): ?>
    <div class="user-alert user-alert-error">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <line x1="15" y1="9" x2="9" y2="15"/>
            <line x1="9" y1="9" x2="15" y2="15"/>
        </svg>
        <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>
<?php else: ?>
    <!-- AJAX 模式：只显示错误提示 -->
    <?php if (isset($error)): ?>
    <div class="user-alert user-alert-error" style="margin-bottom: 20px;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <line x1="15" y1="9" x2="9" y2="15"/>
            <line x1="9" y1="9" x2="15" y2="15"/>
        </svg>
        <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>
<?php endif; ?>

    <!-- 表单内容 -->
    <form method="POST" action="/<?= $adminPrefix ?>/crm/user/add" id="addUserForm" class="user-form-container">
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
                        <span class="user-section-badge">必填</span>
                    </div>
                    <div class="user-section-body">
                        <div class="user-form-group">
                            <label class="user-form-label">
                                用户名
                                <span class="user-required">*</span>
                            </label>
                            <input type="text" class="user-form-input" name="username" required minlength="3" maxlength="50" placeholder="请输入用户名">
                            <span class="user-form-hint">3-50个字符，只能包含字母、数字和下划线</span>
                        </div>

                        <div class="user-form-group">
                            <label class="user-form-label">
                                密码
                                <span class="user-required">*</span>
                            </label>
                            <div class="user-password-input">
                                <input type="password" class="user-form-input" name="password" id="passwordInput" required minlength="6" placeholder="请输入密码">
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
                            <label class="user-form-label">
                                电子邮箱
                                <span class="user-required">*</span>
                            </label>
                            <input type="email" class="user-form-input" name="email" placeholder="请输入电子邮箱">
                            <span class="user-form-hint">邮箱和手机号至少需要填写一项</span>
                        </div>

                        <div class="user-form-group">
                            <label class="user-form-label">手机号码</label>
                            <input type="tel" class="user-form-input" name="phone" placeholder="请输入手机号码">
                            <span class="user-form-hint">格式：11位手机号码</span>
                        </div>

                        <div class="user-form-group">
                            <label class="user-form-label">昵称</label>
                            <input type="text" class="user-form-input" name="nickname" placeholder="请输入昵称（可选）">
                        </div>

                        <div class="user-form-group">
                            <label class="user-form-label">账户状态</label>
                            <div class="user-status-select">
                                <label class="user-status-option is-selected">
                                    <input type="radio" name="status" value="active" checked>
                                    <span class="user-status-radio"></span>
                                    <span class="user-status-label">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="20 6 9 17 4 12"/>
                                        </svg>
                                        正常
                                    </span>
                                </label>
                                <label class="user-status-option">
                                    <input type="radio" name="status" value="disabled">
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
                        <span class="user-section-badge user-badge-optional">可选</span>
                    </div>
                    <div class="user-section-body">
                        <div class="user-form-group">
                            <label class="user-form-label">真实姓名</label>
                            <input type="text" class="user-form-input" name="real_name" placeholder="请输入真实姓名（可选）">
                        </div>

                        <div class="user-form-group">
                            <label class="user-form-label">性别</label>
                            <div class="user-gender-select">
                                <label class="user-gender-option is-selected">
                                    <input type="radio" name="gender" value="" checked>
                                    <span class="user-gender-radio"></span>
                                    <span class="user-gender-label">未设置</span>
                                </label>
                                <label class="user-gender-option">
                                    <input type="radio" name="gender" value="male">
                                    <span class="user-gender-radio"></span>
                                    <span class="user-gender-label">男</span>
                                </label>
                                <label class="user-gender-option">
                                    <input type="radio" name="gender" value="female">
                                    <span class="user-gender-radio"></span>
                                    <span class="user-gender-label">女</span>
                                </label>
                                <label class="user-gender-option">
                                    <input type="radio" name="gender" value="other">
                                    <span class="user-gender-radio"></span>
                                    <span class="user-gender-label">其他</span>
                                </label>
                            </div>
                        </div>

                        <div class="user-form-group">
                            <label class="user-form-label">出生日期</label>
                            <input type="date" class="user-form-input" name="birthdate">
                        </div>

                        <div class="user-form-group">
                            <label class="user-form-label">个人简介</label>
                            <textarea class="user-form-textarea" name="bio" rows="4" placeholder="请输入个人简介（可选）"></textarea>
                        </div>
                    </div>
                </div>

                <!-- 提示信息卡片 -->
                <div class="user-tips-card">
                    <div class="user-tips-header">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="16" x2="12" y2="12"/>
                            <line x1="12" y1="8" x2="12.01" y2="8"/>
                        </svg>
                        创建提示
                    </div>
                    <ul class="user-tips-list">
                        <li>用户名创建后不可修改，请谨慎填写</li>
                        <li>邮箱和手机号至少需要填写一项</li>
                        <li>初始密码建议通过安全渠道告知用户</li>
                        <li>用户创建后默认状态为"正常"</li>
                    </ul>
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
            <a href="/<?= $adminPrefix ?>/crm/users" class="crm-btn-v2 crm-btn-glass">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
                取消
            </a>
            <?php endif; ?>
            <button type="reset" class="crm-btn-v2 crm-btn-glass">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23 4 23 10 17 10"/>
                    <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
                </svg>
                重置
            </button>
            <button type="submit" class="crm-btn-v2 crm-btn-primary-v2">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="8.5" cy="7" r="4"/>
                    <line x1="20" y1="8" x2="20" y2="14"/>
                    <line x1="23" y1="11" x2="17" y2="11"/>
                </svg>
                创建用户
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

// 表单验证
document.getElementById('addUserForm').addEventListener('submit', function(e) {
    const username = this.querySelector('[name="username"]').value.trim();
    const password = this.querySelector('[name="password"]').value;
    const email = this.querySelector('[name="email"]').value.trim();
    const phone = this.querySelector('[name="phone"]').value.trim();

    // 验证用户名格式
    const usernameRegex = /^[a-zA-Z0-9_]{3,50}$/;
    if (!usernameRegex.test(username)) {
        e.preventDefault();
        showError('用户名格式不正确，只能包含字母、数字和下划线，长度3-50个字符');
        return false;
    }

    // 验证密码长度
    if (password.length < 6) {
        e.preventDefault();
        showError('密码长度至少6个字符');
        return false;
    }

    // 验证邮箱和手机号至少填写一项
    if (!email && !phone) {
        e.preventDefault();
        showError('邮箱和手机号至少需要填写一项');
        return false;
    }

    // 验证邮箱格式
    if (email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            e.preventDefault();
            showError('邮箱格式不正确');
            return false;
        }
    }

    // 验证手机号格式
    if (phone) {
        const phoneRegex = /^1[3-9]\d{9}$/;
        if (!phoneRegex.test(phone)) {
            e.preventDefault();
            showError('手机号格式不正确，请输入11位手机号码');
            return false;
        }
    }
    
    return true;
});

function showError(message) {
    // 移除已有的错误提示
    const existingAlert = document.querySelector('.user-alert-error');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    // 创建新的错误提示
    const alertDiv = document.createElement('div');
    alertDiv.className = 'user-alert user-alert-error';
    alertDiv.innerHTML = `
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <line x1="15" y1="9" x2="9" y2="15"/>
            <line x1="9" y1="9" x2="15" y2="15"/>
        </svg>
        ${message}
    `;
    
    // 插入到表单头部后面
    const header = document.querySelector('.user-form-header');
    header.insertAdjacentElement('afterend', alertDiv);
    
    // 滚动到错误提示
    alertDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// 状态选择交互
document.querySelectorAll('.user-status-option input, .user-gender-option input').forEach(radio => {
    radio.addEventListener('change', function() {
        // 移除同组其他选项的选中状态
        const name = this.getAttribute('name');
        document.querySelectorAll(`input[name="${name}"]`).forEach(r => {
            r.closest('label').classList.remove('is-selected');
        });
        // 添加当前选项的选中状态
        this.closest('label').classList.add('is-selected');
    });
});
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

.user-form-icon-add {
    background: linear-gradient(135deg, #10b981, #34d399);
    box-shadow: 0 4px 16px rgba(16, 185, 129, 0.3);
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

/* 错误提示 */
.user-alert {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 24px;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.user-alert-error {
    background: rgba(239, 68, 68, 0.15);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #fca5a5;
}

.user-alert svg {
    flex-shrink: 0;
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
    gap: 24px;
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
    display: flex;
    justify-content: space-between;
    align-items: center;
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

.user-section-badge {
    font-size: 0.7rem;
    font-weight: 600;
    padding: 3px 10px;
    background: rgba(99, 102, 241, 0.2);
    color: #a5b4fc;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.user-badge-optional {
    background: rgba(148, 163, 184, 0.15);
    color: #94a3b8;
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

/* 提示卡片 */
.user-tips-card {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(99, 102, 241, 0.08));
    border: 1px solid rgba(99, 102, 241, 0.2);
    border-radius: 16px;
    padding: 20px;
}

.user-tips-header {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    color: #a5b4fc;
    margin-bottom: 12px;
}

.user-tips-list {
    margin: 0;
    padding: 0 0 0 20px;
    list-style: disc;
}

.user-tips-list li {
    font-size: 0.8125rem;
    color: var(--text-secondary);
    margin-bottom: 6px;
    line-height: 1.5;
}

.user-tips-list li:last-child {
    margin-bottom: 0;
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
