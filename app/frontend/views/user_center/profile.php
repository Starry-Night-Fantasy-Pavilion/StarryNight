<?php
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<div class="dashboard-v2">
    <div class="dashboard-header-v2">
        <h1 class="dashboard-title-v2">个人中心</h1>
        <p class="dashboard-subtitle-v2">管理您的个人信息和账户设置</p>
    </div>

    <?php if (isset($success) && $success): ?>
    <div class="alert alert-success" style="margin: 20px 24px; padding: 12px 16px; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 8px; color: #10b981;">
        个人信息已成功更新
    </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
    <div class="alert alert-error" style="margin: 20px 24px; padding: 12px 16px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; color: #ef4444;">
        <?= $h($error) ?>
    </div>
    <?php endif; ?>

    <div style="max-width: 800px; margin: 0 auto; padding: 0 24px 24px;">
        <div class="card" style="background: linear-gradient(135deg, rgba(255, 255, 255, 0.15), rgba(255, 255, 255, 0.05)); backdrop-filter: blur(40px) saturate(200%); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 16px; padding: 32px; box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.15);">
            <form method="POST" action="/user_center/profile" enctype="multipart/form-data">
                <!-- 快速锚点导航：头像 / 密码 / 注销账号 -->
                <div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:20px;">
                    <a href="#avatar" style="padding:6px 12px; border-radius:999px; border:1px solid rgba(148,163,184,0.6); color:#e5e7eb; font-size:0.8rem; text-decoration:none; background:rgba(15,23,42,0.5);">个人中心</a>
                    <a href="#avatar" style="padding:6px 12px; border-radius:999px; border:1px solid rgba(148,163,184,0.6); color:#e5e7eb; font-size:0.8rem; text-decoration:none; background:rgba(15,23,42,0.5);">修改头像</a>
                    <a href="#password" style="padding:6px 12px; border-radius:999px; border:1px solid rgba(148,163,184,0.6); color:#e5e7eb; font-size:0.8rem; text-decoration:none; background:rgba(15,23,42,0.5);">修改密码</a>
                    <a href="#delete" style="padding:6px 12px; border-radius:999px; border:1px solid rgba(248,113,113,0.6); color:#fecaca; font-size:0.8rem; text-decoration:none; background:rgba(127,29,29,0.6);">注销账号</a>
                </div>

                <!-- 头像上传 -->
                <div class="form-group" id="avatar" style="margin-bottom: 32px; text-align: center;">
                    <label class="form-group-label" style="display: block; margin-bottom: 16px; font-weight: 500; color: rgba(255, 255, 255, 0.9);">头像</label>
                    <div style="position: relative; display: inline-block;">
                        <div id="avatar-preview" style="width: 120px; height: 120px; border-radius: 50%; overflow: hidden; border: 3px solid rgba(255, 255, 255, 0.3); background: rgba(255, 255, 255, 0.1); display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                            <?php if (!empty($user['avatar'])): ?>
                                <img src="<?= $h($user['avatar']) ?>" alt="头像" id="avatar-img" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <div id="avatar-placeholder" style="font-size: 48px; color: rgba(255, 255, 255, 0.5);">👤</div>
                            <?php endif; ?>
                        </div>
                        <label for="avatar" style="display: inline-block; margin-top: 12px; padding: 8px 16px; background: rgba(59, 130, 246, 0.2); border: 1px solid rgba(59, 130, 246, 0.4); border-radius: 8px; color: #60a5fa; cursor: pointer; transition: all 0.3s;">
                            更换头像
                            <input type="file" id="avatar" name="avatar" accept="image/*" style="display: none;" onchange="previewAvatar(this)">
                        </label>
                    </div>
                </div>

                <hr style="border: none; border-top: 1px solid rgba(255, 255, 255, 0.1); margin: 32px 0;">

                <h5 style="margin-bottom: 24px; color: rgba(255, 255, 255, 0.9); font-size: 1.1rem; font-weight: 600;">账户信息</h5>
                
                <div class="form-group">
                    <label class="form-group-label">用户名</label>
                    <input type="text" class="form-control" value="<?= $h($user['username']) ?>" disabled style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.15); color: rgba(255, 255, 255, 0.5);">
                    <div class="form-group-hint" style="font-size: 0.8rem; color: rgba(255, 255, 255, 0.4); margin-top: 6px;">用户名不可修改</div>
                </div>

                <div class="form-group" id="email">
                    <label class="form-group-label">电子邮箱</label>
                    <input type="email" name="email" class="form-control" value="<?= $h($user['email'] ?? '') ?>" required style="background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); color: rgba(255, 255, 255, 0.9); padding: 10px 14px; border-radius: 8px; width: 100%;">
                </div>

                <div class="form-group">
                    <label class="form-group-label">昵称</label>
                    <input type="text" name="nickname" class="form-control" value="<?= $h($user['nickname'] ?? '') ?>" style="background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); color: rgba(255, 255, 255, 0.9); padding: 10px 14px; border-radius: 8px; width: 100%;">
                </div>

                <div class="form-group" id="password">
                    <label class="form-group-label">新密码 (留空则不修改)</label>
                    <input type="password" name="password" class="form-control" placeholder="输入新密码" style="background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); color: rgba(255, 255, 255, 0.9); padding: 10px 14px; border-radius: 8px; width: 100%;">
                </div>

                <hr style="border: none; border-top: 1px solid rgba(255, 255, 255, 0.1); margin: 32px 0;">

                <h5 style="margin-bottom: 24px; color: rgba(255, 255, 255, 0.9); font-size: 1.1rem; font-weight: 600;">个人资料</h5>

                <div class="form-group">
                    <label class="form-group-label">真实姓名</label>
                    <input type="text" name="real_name" class="form-control" value="<?= $h($user['real_name'] ?? '') ?>" style="background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); color: rgba(255, 255, 255, 0.9); padding: 10px 14px; border-radius: 8px; width: 100%;">
                </div>

                <div class="form-group">
                    <label class="form-group-label">性别</label>
                    <select name="gender" class="form-control" style="background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); color: rgba(255, 255, 255, 0.9); padding: 10px 14px; border-radius: 8px; width: 100%;">
                        <option value="" <?= empty($user['gender']) ? 'selected' : '' ?>>未设置</option>
                        <option value="male" <?= ($user['gender'] ?? '') === 'male' ? 'selected' : '' ?>>男</option>
                        <option value="female" <?= ($user['gender'] ?? '') === 'female' ? 'selected' : '' ?>>女</option>
                        <option value="other" <?= ($user['gender'] ?? '') === 'other' ? 'selected' : '' ?>>其他</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-group-label">出生日期</label>
                    <input type="date" name="birthdate" class="form-control" value="<?= $h($user['birthdate'] ?? '') ?>" style="background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); color: rgba(255, 255, 255, 0.9); padding: 10px 14px; border-radius: 8px; width: 100%;">
                </div>

                <div class="form-group">
                    <label class="form-group-label">个人简介</label>
                    <textarea name="bio" class="form-control" rows="4" style="background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); color: rgba(255, 255, 255, 0.9); padding: 10px 14px; border-radius: 8px; width: 100%; resize: vertical;"><?= $h($user['bio'] ?? '') ?></textarea>
                </div>

                <!-- 注销账号（锚点） -->
                <div id="delete" style="margin-top: 32px; padding: 20px; border-radius: 12px; border: 1px solid rgba(248,113,113,0.6); background: rgba(127,29,29,0.4); color: #fee2e2;">
                    <h5 style="margin: 0 0 8px; font-size: 1rem;">注销账号</h5>
                    <p style="margin: 0 0 12px; font-size: 0.85rem; line-height: 1.5;">
                        注销账号为不可恢复操作，将清除与该账号相关的大部分数据。请谨慎操作。
                    </p>
                    <p style="margin: 0; font-size: 0.8rem; opacity: 0.9;">
                        如需正式注销账号，请先保存好重要数据，然后联系管理员或客服处理。
                    </p>
                </div>

                <div style="margin-top: 32px; display: flex; gap: 12px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1; padding: 12px 24px; background: linear-gradient(135deg, #3b82f6, #2563eb); border: none; border-radius: 8px; color: white; font-weight: 500; cursor: pointer; transition: all 0.3s;">
                        保存更改
                    </button>
                    <a href="/user_center" class="btn btn-secondary" style="padding: 12px 24px; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 8px; color: rgba(255, 255, 255, 0.9); text-decoration: none; display: inline-block; transition: all 0.3s;">
                        取消
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.form-group {
    margin-bottom: 20px;
}

.form-group-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.9);
    font-size: 0.9rem;
}

.form-control:focus {
    outline: none;
    border-color: rgba(59, 130, 246, 0.5);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.btn-secondary:hover {
    background: rgba(255, 255, 255, 0.15);
}
</style>

<script>
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('avatar-preview');
            const placeholder = document.getElementById('avatar-placeholder');
            let img = document.getElementById('avatar-img');
            
            if (!img) {
                img = document.createElement('img');
                img.id = 'avatar-img';
                img.style.cssText = 'width: 100%; height: 100%; object-fit: cover;';
                preview.appendChild(img);
            }
            
            if (placeholder) {
                placeholder.remove();
            }
            
            img.src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
