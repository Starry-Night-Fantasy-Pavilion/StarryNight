<?php
$siteNameForAuth = (string)($site_name ?? '');
$siteLogoForAuth = (string)($site_logo ?? '');
?>

<div class="login-wrapper">
    <div class="login-card">
        <div class="login-inner">
            <div class="login-header">
                <div class="login-logo">
                    <?php if (!empty($siteLogoForAuth)): ?>
                        <img src="<?= htmlspecialchars($siteLogoForAuth) ?>" alt="<?= htmlspecialchars($siteNameForAuth ?: '星夜阁') ?>">
                    <?php endif; ?>
                </div>
                <h1 class="login-title">用户登录</h1>
                <p class="login-subtitle">欢迎来到 <?= htmlspecialchars($siteNameForAuth ?: '星夜阁') ?>，请先登录账号</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <?= htmlspecialchars((string) $error) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= htmlspecialchars((string) ($action ?? '/login')) ?>" class="login-form">
            <div class="form-group">
                <label for="username" class="form-label">账号 / 邮箱 / 手机号<span style="color:#f97373;"> *</span></label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    class="form-control"
                    autocomplete="username"
                    placeholder="请输入账号、邮箱或手机号"
                    required
                    autofocus
                >
            </div>
            <div class="form-group">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                    <label for="password" class="form-label">密码<span style="color:#f97373;"> *</span></label>
                    <a href="/reset-password" class="link-forgot">忘记密码？</a>
                </div>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control"
                    autocomplete="current-password"
                    placeholder="请输入密码"
                    required
                >
            </div>

            <?php if (!empty($captcha_html ?? '')): ?>
            <div class="form-group captcha-group">
                <label class="form-label">安全验证<span style="color:#f97373;"> *</span></label>
                <div class="captcha-inner">
                    <?= $captcha_html ?>
                </div>
            </div>
            <?php endif; ?>

            <button type="submit" class="btn-login">登录</button>
        </form>

        <?php if (!empty($third_party_login_buttons) && is_array($third_party_login_buttons) && count($third_party_login_buttons) > 0): ?>
        <div class="third-party-block">
            <div class="third-party-title">或使用以下方式登录</div>
            <div class="third-party-buttons">
                <?php foreach ($third_party_login_buttons as $button): ?>
                    <?= $button ?>
                <?php endforeach; ?>
            </div>
        </div>
        <script>
        (function () {
            function isDataUrl(url) {
                return typeof url === 'string' && url.indexOf('data:') === 0;
            }

            function getProviderLabel(btn) {
                if (!btn) return '';
                var t =
                    (btn.getAttribute('title') || '') ||
                    (btn.getAttribute('aria-label') || '');
                if (t) return t.trim();

                var textNode = btn.querySelector('.login-button-text');
                if (textNode && textNode.textContent) {
                    var txt = textNode.textContent.trim();
                    if (txt) return txt;
                }

                var img = btn.querySelector('img');
                if (img) {
                    var alt = (img.getAttribute('alt') || '').trim();
                    if (alt) return alt;
                }

                var raw = (btn.textContent || '').trim();
                return raw;
            }

            function normalizeButton(btn) {
                if (!btn) return;
                var label = getProviderLabel(btn);
                if (label) {
                    btn.setAttribute('title', label);
                    btn.setAttribute('aria-label', label);
                }

                var img = btn.querySelector('img');
                var svg = btn.querySelector('svg');

                if (img) {
                    var src = img.getAttribute('src') || '';
                    // 允许 data: 图片（插件可能用 data-url 作为 fallback logo）
                    // 只要有 src，就尽量保留 img 作为图标展示
                    if (src) {
                        img.removeAttribute('width');
                        img.removeAttribute('height');
                        btn.innerHTML = '';
                        btn.appendChild(img);
                        return;
                    }
                }
                if (svg) {
                    btn.innerHTML = '';
                    btn.appendChild(svg);
                    return;
                }

                // 如果没有 img/svg，就保留原始内容（例如 Font Awesome 的 <i> 图标）
                return;
            }

            var root = document.querySelector('.third-party-buttons');
            if (!root) return;
            var btns = root.querySelectorAll('a, button');
            btns.forEach(normalizeButton);
        })();
        </script>
        <?php endif; ?>

        <?php
        $uaPath = trim((string)($user_agreement_txt_path ?? ''));
        $ppPath = trim((string)($privacy_policy_txt_path ?? ''));
        ?>
        <?php if ($uaPath !== '' || $ppPath !== ''): ?>
        <div class="login-legal">
            <span>登录即表示同意</span>
            <?php if ($uaPath !== ''): ?>
                <a href="javascript:void(0)" data-legal="user">《用户协议》</a>
            <?php endif; ?>
            <?php if ($uaPath !== '' && $ppPath !== ''): ?>
                <span class="auth-legal-sep">和</span>
            <?php endif; ?>
            <?php if ($ppPath !== ''): ?>
                <a href="javascript:void(0)" data-legal="privacy">《隐私政策》</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="login-footer">
        </div>
        </div> <!-- /.login-inner -->
    </div>
</div>



<script>
document.addEventListener('DOMContentLoaded', function () {
    // 安全地获取元素，避免控制台错误
    var modal = document.getElementById('auth-legal-modal');
    var dialog = modal ? modal.querySelector('.auth-legal-modal-dialog') : null;
    var titleEl = document.getElementById('auth-legal-modal-title');
    var contentEl = document.getElementById('auth-legal-modal-content');
    var closeBtn = modal ? modal.querySelector('.auth-legal-modal-close') : null;

    // 检查必要元素是否存在，避免报错
    if (!modal || !dialog) {
        return;
    }

    function fetchLegalText(kind, callback) {
        // 仅使用实际存在的路由，避免无意义的 404 日志
        var urls = kind === 'privacy'
            ? ['/privacy-policy']
            : ['/user-agreement'];

        var index = 0;

        function tryNext() {
            if (index >= urls.length) {
                callback(null);
                return;
            }

            var url = urls[index++];

            fetch(url, {
                headers: { 'Accept': 'text/plain' },
                cache: 'no-cache'
            })
                .then(function (res) {
                    return res.text();
                })
                .then(function (text) {
                    var safeText = (text || '').trim();

                    // 如果返回的是整页 HTML（如 Nginx 404），继续尝试下一条地址
                    if (/<!DOCTYPE html>/i.test(safeText) || /<html[\s>]/i.test(safeText)) {
                        tryNext();
                        return;
                    }

                    callback(safeText || '');
                })
                .catch(function () {
                    // 当前地址失败，继续下一个
                    tryNext();
                });
        }

        tryNext();
    }

    function openModal(kind) {
        var title = kind === 'privacy' ? '隐私政策' : '用户协议';
        var fallbackText = kind === 'privacy'
            ? '隐私政策内容暂时无法加载，请稍后重试或联系管理员。'
            : '用户协议内容暂时无法加载，请稍后重试或联系管理员。';
        
        titleEl.textContent = title;
        contentEl.textContent = '加载中...';
        modal.classList.add('visible');
        modal.setAttribute('aria-hidden', 'false');

        fetchLegalText(kind, function (text) {
            if (!contentEl) return;
            if (!text) {
                contentEl.textContent = fallbackText;
                return;
            }
            contentEl.textContent = text;
        });
    }

    function closeModal() {
        if (!modal) return;

        // 先把焦点移出弹窗，避免 aria-hidden 与聚焦元素冲突的无障碍警告
        try {
            if (document.activeElement && typeof document.activeElement.blur === 'function') {
                document.activeElement.blur();
            }
        } catch (e) {}

        modal.classList.remove('visible');
        modal.setAttribute('aria-hidden', 'true');
    }

    // 绑定点击事件
    document.addEventListener('click', function (e) {
        var link = e.target.closest('.login-legal a[data-legal]');
        if (link) {
            e.preventDefault();
            var kind = link.getAttribute('data-legal') || 'user';
            openModal(kind);
        }
    });

    // 绑定关闭按钮
    if (closeBtn) {
        closeBtn.addEventListener('click', function (e) {
            e.preventDefault();
            closeModal();
        });
    }

    // 点击背景关闭
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    }

    // ESC 键关闭
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal && modal.classList.contains('visible')) {
            closeModal();
        }
    });
});
</script>
