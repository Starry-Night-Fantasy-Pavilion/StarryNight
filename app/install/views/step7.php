<!DOCTYPE html>
<html>
<head>
    <title>星夜阁 - 安装向导 - 执行安装</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="/static/install/css/style.css">
    <style>
        .install-modal {
            position: fixed;
            inset: 0;
            display: none;
            z-index: 9999;
        }
        .install-modal.open {
            display: block;
        }
        .install-modal-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(6px);
        }
        .install-modal-dialog {
            position: relative;
            max-width: 880px;
            margin: 60px auto;
            background: radial-gradient(circle at top, rgba(37,99,235,.35), rgba(15,23,42,1));
            border-radius: 16px;
            border: 1px solid rgba(148,163,184,.4);
            box-shadow: 0 25px 70px rgba(15,23,42,.8);
            padding: 18px 18px 16px;
            color: #e5e7eb;
        }
        .install-modal-dialog.error {
            border-color: #f87171;
            box-shadow: 0 25px 70px rgba(248,113,113,.55);
        }
        .install-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 8px;
        }
        .install-modal-title {
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .install-modal-title svg {
            flex-shrink: 0;
        }
        .install-modal-status {
            font-size: 13px;
            color: #9ca3af;
            margin-bottom: 6px;
        }
        .install-modal-status.error {
            color: #fecaca;
        }
        .install-modal-body {
            border-radius: 10px;
            background: #020617;
            border: 1px solid rgba(15,23,42,.9);
            padding: 8px;
        }
        #install-log {
            height: 320px;
            overflow: auto;
            margin: 0;
            background: transparent;
            color: #d1d5db;
            font-size: 12px;
        }
        .install-modal-footer {
            margin-top: 10px;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }
        .btn-ghost {
            border-radius: 999px;
            border: 1px solid rgba(148,163,184,.5);
            background: transparent;
            padding: 6px 14px;
            color: #e5e7eb;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
        }
        .btn-ghost:hover {
            background: rgba(15,23,42,.8);
        }
    </style>
</head>
<body class="install-page step-7">
    <div class="install-wrapper">
        <?php include __DIR__ . '/_partials/sidebar.php'; ?>

        <div class="install-main">
            <div class="install-content">
                <h1>执行安装</h1>
                <p class="description">请选择安装方式并开始执行，安装过程中的详细日志会以弹窗形式实时显示。</p>

                <?php $existingCount = (int)($db_detect['existing_count'] ?? 0); ?>
                <div class="form-wrapper">
                    <fieldset>
                        <legend>数据库检测结果</legend>
                        <?php if ($existingCount > 0): ?>
                            <div style="background: rgba(245, 158, 11, 0.12); border: 1px solid #f59e0b; color: #ffd180; padding: 12px; border-radius: 10px; margin-bottom: 15px;">
                                检测到当前前缀下已有 <strong><?php echo $existingCount; ?></strong> 张项目表。
                            </div>
                        <?php else: ?>
                            <div style="background: rgba(34, 197, 94, 0.12); border: 1px solid #22c55e; color: #9cffc1; padding: 12px; border-radius: 10px; margin-bottom: 15px;">
                                未检测到当前前缀下已有项目表，可直接安装。
                            </div>
                        <?php endif; ?>

                        <div style="display: grid; gap: 12px;">
                            <label style="display: flex; gap: 10px; align-items: flex-start;">
                                <input type="radio" name="install_mode" value="patch" checked>
                                <span>跳过已有数据并补齐缺失表（推荐，保留现有数据）</span>
                            </label>
                            <label style="display: flex; gap: 10px; align-items: flex-start;">
                                <input type="radio" name="install_mode" value="fresh">
                                <span>全新安装（删除当前前缀下已有表后重建）</span>
                            </label>
                        </div>
                    </fieldset>
                </div>
            </div>

            <div class="actions">
                <a href="?step=6" class="btn btn-secondary" id="back-btn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
                    上一步
                </a>
                <button type="button" class="btn btn-primary" id="start-install-btn">
                    开始安装
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m5 3 14 9-14 9V3z"/></svg>
                </button>
            </div>
        </div>
    </div>

    <div id="install-log-modal" class="install-modal" aria-hidden="true">
        <div class="install-modal-backdrop"></div>
        <div class="install-modal-dialog" id="install-log-dialog">
            <div class="install-modal-header">
                <div class="install-modal-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 3h18v18H3z"></path>
                        <path d="M7 8h10"></path>
                        <path d="M7 12h6"></path>
                        <path d="M7 16h3"></path>
                    </svg>
                    <span>安装日志</span>
                </div>
                <button type="button" class="btn-ghost" id="install-log-close-btn">
                    关闭
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"></path>
                        <path d="m6 6 12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="install-log-status" class="install-modal-status">
                准备开始安装，请不要关闭此窗口。
            </div>
            <div class="install-modal-body">
                <pre id="install-log">等待开始安装...</pre>
            </div>
            <div class="install-modal-footer">
                <span style="font-size: 11px; color: #6b7280;">遇到错误时，请不要刷新页面，先完整复制日志信息再重试。</span>
            </div>
        </div>
    </div>
    <script>
        (function () {
            var startBtn = document.getElementById('start-install-btn');
            var backBtn = document.getElementById('back-btn');
            var modal = document.getElementById('install-log-modal');
            var dialog = document.getElementById('install-log-dialog');
            var closeBtn = document.getElementById('install-log-close-btn');
            var statusBar = document.getElementById('install-log-status');
            var logBox = document.getElementById('install-log');

            function openModal() {
                modal.classList.add('open');
                modal.setAttribute('aria-hidden', 'false');
            }

            function closeModal() {
                modal.classList.remove('open');
                modal.setAttribute('aria-hidden', 'true');
            }

            function appendLog(line) {
                if (!line) return;
                logBox.textContent += "\n" + line;
                logBox.scrollTop = logBox.scrollHeight;
            }

            async function startInstall() {
                var checked = document.querySelector('input[name="install_mode"]:checked');
                var mode = checked ? checked.value : 'patch';

                if (mode === 'fresh') {
                    var ok = confirm('全新安装会删除当前前缀下已存在的项目表，是否继续？');
                    if (!ok) return;
                }

                openModal();
                dialog.classList.remove('error');
                statusBar.classList.remove('error');
                statusBar.textContent = '安装进行中，请不要关闭此窗口或刷新页面。';

                startBtn.disabled = true;
                backBtn.style.pointerEvents = 'none';
                backBtn.style.opacity = '0.5';
                logBox.textContent = '[INFO] 正在发起安装请求...';

                try {
                    var body = new URLSearchParams();
                    body.set('action', 'execute');
                    body.set('install_mode', mode);

                    var response = await fetch('?step=7', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: body.toString()
                    });

                    if (!response.ok || !response.body) {
                        appendLog('[ERROR] 安装请求失败，请检查服务器状态。');
                        statusBar.textContent = '安装请求失败，请根据日志检查后重试。';
                        statusBar.classList.add('error');
                        dialog.classList.add('error');
                        startBtn.disabled = false;
                        backBtn.style.pointerEvents = '';
                        backBtn.style.opacity = '';
                        return;
                    }

                    var reader = response.body.getReader();
                    var decoder = new TextDecoder('utf-8');
                    var buffer = '';
                    var done = false;

                    while (!done) {
                        var result = await reader.read();
                        done = result.done;
                        buffer += decoder.decode(result.value || new Uint8Array(), { stream: !done });
                        var lines = buffer.split(/\r?\n/);
                        buffer = lines.pop() || '';

                        for (var i = 0; i < lines.length; i++) {
                            var line = lines[i].trim();
                            if (!line) continue;
                            appendLog(line);
                            if (line.indexOf('__INSTALL_DONE__') !== -1) {
                                appendLog('[INFO] 安装完成，正在跳转...');
                                statusBar.textContent = '安装完成，正在跳转到完成页面...';
                                setTimeout(function () { window.location.href = '?step=8'; }, 800);
                                return;
                            }
                            if (line.indexOf('__INSTALL_ERROR__') !== -1) {
                                appendLog('[ERROR] 安装失败，请根据日志检查后重试。');
                                statusBar.textContent = '安装失败，请仔细查看日志信息，处理问题后再重试。';
                                statusBar.classList.add('error');
                                dialog.classList.add('error');
                                startBtn.disabled = false;
                                backBtn.style.pointerEvents = '';
                                backBtn.style.opacity = '';
                                return;
                            }
                        }
                    }

                    if (buffer.trim() !== '') {
                        appendLog(buffer.trim());
                    }
                } catch (err) {
                    appendLog('[ERROR] ' + (err && err.message ? err.message : '安装过程异常'));
                    statusBar.textContent = '安装过程出现异常，请根据日志检查后重试。';
                    statusBar.classList.add('error');
                    dialog.classList.add('error');
                    startBtn.disabled = false;
                    backBtn.style.pointerEvents = '';
                    backBtn.style.opacity = '';
                }
            }

            startBtn.addEventListener('click', startInstall);
            closeBtn.addEventListener('click', function () {
                closeModal();
            });
        })();
    </script>
</body>
</html>
