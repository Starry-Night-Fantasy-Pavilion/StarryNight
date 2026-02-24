<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($source['id']) ? '编辑' : '添加'; ?>书源</title>
    <link rel="stylesheet" href="/static/admin/css/plugin-modal.css">
</head>
<body>
<div class="container-fluid">
    <h2><?php echo isset($source['id']) ? '编辑' : '添加'; ?>书源</h2>

    <?php if (isset($message) && is_array($message) && $message['type'] === 'error'): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($message['messages'] as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST">
        <?= csrf_field() ?>
        
        <ul class="nav nav-tabs" id="sourceTab">
            <li class="nav-item">
                <button class="nav-link active" data-target="#basic-info" type="button">基本信息</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-target="#explore-rule" type="button">发现规则</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-target="#search-rule" type="button">搜索规则</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-target="#book-info-rule" type="button">书籍信息规则</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-target="#toc-rule" type="button">目录规则</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-target="#content-rule" type="button">正文规则</button>
            </li>
        </ul>

        <div class="tab-content border border-top-0 p-3 mb-3">
            <div class="tab-pane active" id="basic-info">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="book_source_name">书源名称</label>
                            <input type="text" class="form-control" id="book_source_name" name="book_source_name" value="<?php echo htmlspecialchars($source['book_source_name'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="book_source_group">书源分组</label>
                            <input type="text" class="form-control" id="book_source_group" name="book_source_group" value="<?php echo htmlspecialchars($source['book_source_group'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                <div class="form-group mb-3">
                    <label for="book_source_url">书源地址 (URL)</label>
                    <input type="url" class="form-control" id="book_source_url" name="book_source_url" value="<?php echo htmlspecialchars($source['book_source_url'] ?? ''); ?>" required>
                </div>
                <div class="form-group mb-3">
                    <label for="book_url_pattern">书籍详情页URL正则</label>
                    <input type="text" class="form-control" id="book_url_pattern" name="book_url_pattern" value="<?php echo htmlspecialchars($source['book_url_pattern'] ?? ''); ?>">
                    <small class="form-text text-muted">用于匹配书籍详情页的URL，可选。</small>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group mb-3">
                            <label for="book_source_type">书源类型</label>
                            <select class="form-control" id="book_source_type" name="book_source_type">
                                <option value="0" <?php echo (($source['book_source_type'] ?? 0) == 0) ? 'selected' : ''; ?>>文本</option>
                                <option value="1" <?php echo (($source['book_source_type'] ?? 0) == 1) ? 'selected' : ''; ?>>音频</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-3">
                            <label for="weight">权重</label>
                            <input type="number" class="form-control" id="weight" name="weight" value="<?php echo htmlspecialchars($source['weight'] ?? 0); ?>">
                            <small class="form-text text-muted">数字越大，优先级越高。</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-3">
                            <label for="custom_order">排序</label>
                            <input type="number" class="form-control" id="custom_order" name="custom_order" value="<?php echo htmlspecialchars($source['custom_order'] ?? 0); ?>">
                            <small class="form-text text-muted">手动排序编号。</small>
                        </div>
                    </div>
                </div>
                 <div class="row">
                    <div class="col-md-3">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="enabled" name="enabled" value="1" <?php echo ($source['enabled'] ?? true) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="enabled">启用书源</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="enabled_explore" name="enabled_explore" value="1" <?php echo ($source['enabled_explore'] ?? true) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="enabled_explore">启用发现</label>
                        </div>
                    </div>
                </div>
                <div class="form-group mb-3">
                    <label for="login_url">登录地址</label>
                    <input type="url" class="form-control" id="login_url" name="login_url" value="<?php echo htmlspecialchars($source['login_url'] ?? ''); ?>">
                </div>
                <div class="form-group mb-3">
                    <label for="header">请求头 (Header)</label>
                    <textarea class="form-control" id="header" name="header" rows="4"><?php echo htmlspecialchars($source['header'] ?? ''); ?></textarea>
                    <small class="form-text text-muted">可以是JSON格式或每行一个 "Key: Value"。</small>
                </div>
            </div>

            <div class="tab-pane" id="explore-rule">
                <div class="form-group mb-3">
                    <label for="explore_url">发现URL</label>
                    <input type="text" class="form-control" id="explore_url" name="explore_url" value="<?php echo htmlspecialchars($source['explore_url'] ?? ''); ?>">
                    <small class="form-text text-muted">支持变量 {page}。例如：https://example.com/explore?page={page}</small>
                </div>
                <div class="form-group mb-3">
                    <label for="rule_explore">发现规则</label>
                    <textarea class="form-control" id="rule_explore" name="rule_explore" rows="10"><?php echo htmlspecialchars($source['rule_explore'] ?? ''); ?></textarea>
                    <small class="form-text text-muted">JSON格式，使用CSS选择器。例如：{"bookList": ".list > .item", "name": ".title", "author": ".author", "bookUrl": "a@href"}</small>
                </div>
            </div>

            <div class="tab-pane" id="search-rule">
                <div class="form-group mb-3">
                    <label for="search_url">搜索URL</label>
                    <input type="text" class="form-control" id="search_url" name="search_url" value="<?php echo htmlspecialchars($source['search_url'] ?? ''); ?>">
                    <small class="form-text text-muted">必须包含 {key} 变量，可选 {page} 变量。例如：https://example.com/search?q={key}&page={page}</small>
                </div>
                <div class="form-group mb-3">
                    <label for="rule_search">搜索规则</label>
                    <textarea class="form-control" id="rule_search" name="rule_search" rows="10"><?php echo htmlspecialchars($source['rule_search'] ?? ''); ?></textarea>
                    <small class="form-text text-muted">JSON格式，使用CSS选择器。例如：{"bookList": ".search-results > li", "name": ".book-name", "author": ".book-author", "bookUrl": ".book-link@href"}</small>
                </div>
            </div>

            <div class="tab-pane" id="book-info-rule">
                <div class="form-group mb-3">
                    <label for="rule_book_info">书籍信息页规则</label>
                    <textarea class="form-control" id="rule_book_info" name="rule_book_info" rows="10"><?php echo htmlspecialchars($source['rule_book_info'] ?? ''); ?></textarea>
                    <small class="form-text text-muted">JSON格式，使用CSS选择器。例如：{"name": ".book-title", "author": ".book-author", "intro": ".book-intro", "tocUrl": ".toc-link@href"}</small>
                </div>
            </div>

            <div class="tab-pane" id="toc-rule">
                <div class="form-group mb-3">
                    <label for="rule_toc">目录页规则</label>
                    <textarea class="form-control" id="rule_toc" name="rule_toc" rows="10"><?php echo htmlspecialchars($source['rule_toc'] ?? ''); ?></textarea>
                    <small class="form-text text-muted">JSON格式，使用CSS选择器。例如：{"chapterList": ".chapter-list > li", "chapterName": ".chapter-name", "chapterUrl": ".chapter-link@href"}</small>
                </div>
            </div>

            <div class="tab-pane" id="content-rule">
                <div class="form-group mb-3">
                    <label for="rule_content">正文页规则</label>
                    <textarea class="form-control" id="rule_content" name="rule_content" rows="10"><?php echo htmlspecialchars($source['rule_content'] ?? ''); ?></textarea>
                    <small class="form-text text-muted">JSON格式，使用CSS选择器。例如：{"content": ".chapter-content"}</small>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">保存</button>
            <a href="/<?php echo get_env('ADMIN_PATH', 'admin'); ?>/my_app/sources" class="btn btn-secondary">取消</a>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tabs = document.querySelectorAll('.nav-link');
        tabs.forEach(tab => {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.nav-link').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
                const targetId = this.getAttribute('data-target');
                document.querySelector(targetId).classList.add('active');
            });
        });
    });
</script>
</body>
</html>
