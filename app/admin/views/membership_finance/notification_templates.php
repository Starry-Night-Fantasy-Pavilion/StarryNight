<?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
<link rel="stylesheet" href="/static/admin/css/notification-templates.css?v=<?= time() ?>">

<div class="nt-layout">
    <div class="nt-header">
        <div class="nt-header-content">
            <div class="nt-header-title-group">
                <div class="nt-header-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                </div>
                <div class="nt-header-text">
                    <h1 class="nt-title">通知模板管理</h1>
                    <p class="nt-subtitle">管理系统发送的邮件、短信等各类通知模板</p>
                </div>
            </div>
            <div class="nt-header-actions">
                <button type="button" class="nt-btn nt-btn-primary" id="btn-open-template-modal">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    新增模板
                </button>
            </div>
        </div>
    </div>

    <div class="nt-body">
        <?php if (!empty($error ?? '')): ?>
            <div class="nt-alert nt-alert-error">
                <div class="nt-alert-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                </div>
                <div class="nt-alert-content"><?= htmlspecialchars((string)$error) ?></div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($successMessage ?? '')): ?>
            <div class="nt-alert nt-alert-success">
                <div class="nt-alert-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <div class="nt-alert-content"><?= htmlspecialchars((string)$successMessage) ?></div>
            </div>
        <?php endif; ?>

        <div class="nt-card">
            <div class="nt-card-toolbar">
                <div class="nt-tabs">
                    <?php $currentChannel = $channelFilter ?? ''; ?>
                    <a href="/<?= $adminPrefix ?>/finance/notification-templates" class="nt-tab <?= $currentChannel === '' ? 'active' : '' ?>">
                        全部模板
                    </a>
                    <a href="/<?= $adminPrefix ?>/finance/notification-templates?channel=email" class="nt-tab <?= $currentChannel === 'email' ? 'active' : '' ?>">
                        <span class="nt-tab-icon">📧</span> 邮件模板
                    </a>
                    <a href="/<?= $adminPrefix ?>/finance/notification-templates?channel=sms" class="nt-tab <?= $currentChannel === 'sms' ? 'active' : '' ?>">
                        <span class="nt-tab-icon">💬</span> 短信模板
                    </a>
                </div>
                <div class="nt-search-box">
                    <form method="get" class="nt-search-form">
                        <?php if ($currentChannel): ?>
                            <input type="hidden" name="channel" value="<?= htmlspecialchars($currentChannel) ?>">
                        <?php endif; ?>
                        <div class="nt-input-group">
                            <span class="nt-input-prefix">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                            </span>
                            <input type="text" name="q" value="<?= htmlspecialchars((string)($keyword ?? '')) ?>" class="nt-input" placeholder="搜索模板编码或名称...">
                            <button type="submit" class="nt-btn nt-btn-secondary">搜索</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="nt-card-body">
                <?php if (empty($list)): ?>
                    <div class="nt-empty-state">
                        <div class="nt-empty-illustration">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                        </div>
                        <h3 class="nt-empty-title">暂无模板数据</h3>
                        <p class="nt-empty-desc">您还没有创建任何通知模板，点击下方按钮开始创建。</p>
                        <button type="button" class="nt-btn nt-btn-primary" id="btn-open-template-modal-empty">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            创建第一个模板
                        </button>
                    </div>
                <?php else: ?>
                    <div class="nt-table-container">
                        <table class="nt-table">
                            <thead>
                                <tr>
                                    <th width="80">ID</th>
                                    <th width="120">渠道</th>
                                    <th>模板名称</th>
                                    <th width="250">模板编码</th>
                                    <th width="180" class="nt-text-right">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($list as $t): ?>
                                    <tr>
                                        <td class="nt-text-muted">#<?= (int)$t['id'] ?></td>
                                        <td>
                                            <?php
                                            $channel = $t['channel'] ?? '';
                                            $channelConfig = [
                                                'email' => ['label' => '邮件', 'icon' => '📧', 'class' => 'nt-tag-email'],
                                                'sms' => ['label' => '短信', 'icon' => '💬', 'class' => 'nt-tag-sms'],
                                                'system' => ['label' => '站内信', 'icon' => '🔔', 'class' => 'nt-tag-system'],
                                            ];
                                            $config = $channelConfig[$channel] ?? ['label' => $channel, 'icon' => '📌', 'class' => 'nt-tag-default'];
                                            ?>
                                            <span class="nt-tag <?= $config['class'] ?>">
                                                <span class="nt-tag-icon"><?= $config['icon'] ?></span>
                                                <?= $config['label'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="nt-cell-title"><?= htmlspecialchars($t['title'] ?? '') ?></div>
                                        </td>
                                        <td>
                                            <code class="nt-code-snippet"><?= htmlspecialchars($t['code'] ?? '') ?></code>
                                        </td>
                                        <td class="nt-text-right">
                                            <div class="nt-action-group">
                                                <button type="button" class="nt-btn-icon btn-template-detail" title="查看详情"
                                                        data-id="<?= (int)$t['id'] ?>"
                                                        data-channel="<?= htmlspecialchars($t['channel'] ?? '') ?>"
                                                        data-code="<?= htmlspecialchars($t['code'] ?? '') ?>"
                                                        data-title="<?= htmlspecialchars($t['title'] ?? '') ?>"
                                                        data-file="<?= htmlspecialchars($t['content'] ?? '') ?>">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                        <circle cx="12" cy="12" r="3"></circle>
                                                    </svg>
                                                </button>
                                                <?php if (!empty($t['content'])): ?>
                                                    <?php $subDir = (($t['channel'] ?? 'email') === 'email') ? 'Email' : 'sms'; ?>
                                                    <a href="/static/errors/html/<?= htmlspecialchars($subDir, ENT_QUOTES, 'UTF-8') ?>/<?= rawurlencode($t['content']) ?>"
                                                       target="_blank" class="nt-btn-icon" title="预览模板">
                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                                                            <polyline points="15 3 21 3 21 9"></polyline>
                                                            <line x1="10" y1="14" x2="21" y2="3"></line>
                                                        </svg>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (($t['channel'] ?? '') === 'email' || ($t['channel'] ?? '') === 'sms'): ?>
                                                    <button type="button" class="nt-btn-icon nt-btn-icon-primary btn-template-test" title="发送测试"
                                                            data-id="<?= (int)$t['id'] ?>"
                                                            data-channel="<?= htmlspecialchars($t['channel'] ?? '') ?>"
                                                            data-code="<?= htmlspecialchars($t['code'] ?? '') ?>">
                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <line x1="22" y1="2" x2="11" y2="13"></line>
                                                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                                                        </svg>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="nt-guide-section">
            <div class="nt-guide-header">
                <div class="nt-guide-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                </div>
                <h3 class="nt-guide-title">模板开发指南</h3>
            </div>
            <div class="nt-guide-grid">
                <div class="nt-guide-card">
                    <div class="nt-guide-card-icon">📁</div>
                    <h4 class="nt-guide-card-title">文件存储路径</h4>
                    <ul class="nt-guide-list">
                        <li>邮件模板：<code class="nt-code-snippet">/static/errors/html/Email/</code></li>
                        <li>短信模板：<code class="nt-code-snippet">/static/errors/html/sms/</code></li>
                    </ul>
                </div>
                <div class="nt-guide-card">
                    <div class="nt-guide-card-icon">📝</div>
                    <h4 class="nt-guide-card-title">可用变量</h4>
                    <ul class="nt-guide-list">
                        <li>用户名：<code class="nt-code-snippet">{{username}}</code></li>
                        <li>验证码：<code class="nt-code-snippet">{{code}}</code></li>
                    </ul>
                </div>
                <div class="nt-guide-card">
                    <div class="nt-guide-card-icon">⚙️</div>
                    <h4 class="nt-guide-card-title">格式要求</h4>
                    <p class="nt-guide-text">请确保上传的 HTML 文件使用 <strong>UTF-8</strong> 编码，以避免乱码问题。建议使用标准的 HTML5 结构。</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 模板详情弹窗 -->
<div id="template-detail-modal" class="nt-modal">
    <div class="nt-modal-overlay" data-close="template-detail-modal"></div>
    <div class="nt-modal-container nt-modal-md">
        <div class="nt-modal-header">
            <h5 class="nt-modal-title">模板详情</h5>
            <button type="button" class="nt-modal-close" data-close="template-detail-modal">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <div class="nt-modal-body">
            <div class="nt-desc-list">
                <div class="nt-desc-item">
                    <div class="nt-desc-label">渠道</div>
                    <div class="nt-desc-value" id="detail_channel"></div>
                </div>
                <div class="nt-desc-item">
                    <div class="nt-desc-label">名称</div>
                    <div class="nt-desc-value" id="detail_title"></div>
                </div>
                <div class="nt-desc-item">
                    <div class="nt-desc-label">编码</div>
                    <div class="nt-desc-value"><code class="nt-code-snippet" id="detail_code"></code></div>
                </div>
                <div class="nt-desc-item">
                    <div class="nt-desc-label">文件名</div>
                    <div class="nt-desc-value"><span class="nt-text-mono" id="detail_file"></span></div>
                </div>
            </div>
        </div>
        <div class="nt-modal-footer">
            <button type="button" class="nt-btn nt-btn-secondary" data-close="template-detail-modal">关闭</button>
        </div>
    </div>
</div>

<!-- 测试发送弹窗 -->
<div id="template-test-modal" class="nt-modal">
    <div class="nt-modal-overlay" data-close="template-test-modal"></div>
    <div class="nt-modal-container nt-modal-sm">
        <div class="nt-modal-header">
            <h5 class="nt-modal-title">
                测试发送
                <span id="template-test-label" class="nt-modal-subtitle"></span>
            </h5>
            <button type="button" class="nt-modal-close" data-close="template-test-modal">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <form method="post" action="/<?= $adminPrefix ?>/finance/notification-templates">
            <div class="nt-modal-body">
                <input type="hidden" name="test_template_id" id="test_template_id" value="">
                <div class="nt-form-item">
                    <label class="nt-form-label">测试接收地址 <span class="nt-text-danger">*</span></label>
                    <input type="text" name="test_target" class="nt-input" placeholder="邮箱 或 手机号" required>
                    <div class="nt-form-help">邮件模板请输入邮箱地址，短信模板请输入手机号码</div>
                </div>
            </div>
            <div class="nt-modal-footer">
                <button type="button" class="nt-btn nt-btn-secondary" data-close="template-test-modal">取消</button>
                <button type="submit" class="nt-btn nt-btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                    发送测试
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 新增模板弹窗 -->
<div id="template-modal" class="nt-modal">
    <div class="nt-modal-overlay" data-close="template-modal"></div>
    <div class="nt-modal-container nt-modal-lg">
        <div class="nt-modal-header">
            <h5 class="nt-modal-title">新增通知模板</h5>
            <button type="button" class="nt-modal-close" data-close="template-modal">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <form method="post" enctype="multipart/form-data" action="/<?= $adminPrefix ?>/finance/notification-templates">
            <div class="nt-modal-body">
                <div class="nt-grid nt-grid-3">
                    <div class="nt-form-item">
                        <label class="nt-form-label">渠道 <span class="nt-text-danger">*</span></label>
                        <select name="channel" class="nt-select">
                            <option value="email">📧 邮件</option>
                            <option value="sms">💬 短信</option>
                            <option value="system">🔔 站内信</option>
                        </select>
                    </div>
                    <div class="nt-form-item">
                        <label class="nt-form-label">编码 <span class="nt-text-danger">*</span></label>
                        <input type="text" name="code" class="nt-input" placeholder="例如: register_welcome" required>
                        <div class="nt-form-help">在代码中用来定位模板</div>
                    </div>
                    <div class="nt-form-item">
                        <label class="nt-form-label">名称 <span class="nt-text-danger">*</span></label>
                        <input type="text" name="name" class="nt-input" placeholder="模板名称" required>
                    </div>
                </div>
                
                <div class="nt-grid nt-grid-2">
                    <div class="nt-form-item">
                        <label class="nt-form-label">主题（仅邮件）</label>
                        <input type="text" name="subject" class="nt-input" placeholder="邮件主题，可留空">
                    </div>
                    <div class="nt-form-item">
                        <label class="nt-form-label">HTML 模版文件 <span class="nt-text-danger">*</span></label>
                        <div class="nt-upload-area" id="nt-upload-area">
                            <div class="nt-upload-icon">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="17 8 12 3 7 8"></polyline>
                                    <line x1="12" y1="3" x2="12" y2="15"></line>
                                </svg>
                            </div>
                            <div class="nt-upload-text" id="template_file_name">点击或拖拽文件到此处上传</div>
                            <div class="nt-upload-hint">支持 .html, .htm 格式文件</div>
                            <input type="file" name="template_file" id="template_file_input" accept=".html,.htm" required class="nt-upload-input">
                        </div>
                    </div>
                </div>

                <div class="nt-alert nt-alert-info nt-mt-4">
                    <div class="nt-alert-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="16" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12.01" y2="8"></line>
                        </svg>
                    </div>
                    <div class="nt-alert-content">
                        建议使用 UTF-8 编码的 HTML 文件，内容中可使用 <code class="nt-code-snippet">{{code}}</code>、<code class="nt-code-snippet">{{username}}</code> 等占位符。
                    </div>
                </div>
            </div>
            <div class="nt-modal-footer">
                <button type="button" class="nt-btn nt-btn-secondary" data-close="template-modal">取消</button>
                <button type="submit" class="nt-btn nt-btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    保存模板
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal Logic
    const modals = {
        open: function(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        },
        close: function(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        }
    };

    // Open Modals
    document.getElementById('btn-open-template-modal')?.addEventListener('click', () => modals.open('template-modal'));
    document.getElementById('btn-open-template-modal-empty')?.addEventListener('click', () => modals.open('template-modal'));

    // Close Modals
    document.querySelectorAll('[data-close]').forEach(btn => {
        btn.addEventListener('click', function() {
            modals.close(this.getAttribute('data-close'));
        });
    });

    // Template Detail
    document.querySelectorAll('.btn-template-detail').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('detail_channel').textContent = this.dataset.channel;
            document.getElementById('detail_title').textContent = this.dataset.title;
            document.getElementById('detail_code').textContent = this.dataset.code;
            document.getElementById('detail_file').textContent = this.dataset.file;
            modals.open('template-detail-modal');
        });
    });

    // Template Test
    document.querySelectorAll('.btn-template-test').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('test_template_id').value = this.dataset.id;
            document.getElementById('template-test-label').textContent = `(${this.dataset.code})`;
            modals.open('template-test-modal');
        });
    });

    // File Upload UI
    const uploadArea = document.getElementById('nt-upload-area');
    const fileInput = document.getElementById('template_file_input');
    const fileNameDisplay = document.getElementById('template_file_name');

    if (uploadArea && fileInput) {
        uploadArea.addEventListener('click', () => fileInput.click());
        
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                updateFileName();
            }
        });

        fileInput.addEventListener('change', updateFileName);

        function updateFileName() {
            if (fileInput.files.length > 0) {
                fileNameDisplay.textContent = fileInput.files[0].name;
                fileNameDisplay.style.color = 'var(--nt-primary)';
                fileNameDisplay.style.fontWeight = '500';
                uploadArea.style.borderColor = 'var(--nt-primary)';
                uploadArea.style.backgroundColor = 'var(--nt-primary-50)';
            } else {
                fileNameDisplay.textContent = '点击或拖拽文件到此处上传';
                fileNameDisplay.style.color = '';
                fileNameDisplay.style.fontWeight = '';
                uploadArea.style.borderColor = '';
                uploadArea.style.backgroundColor = '';
            }
        }
    }
});
</script>
