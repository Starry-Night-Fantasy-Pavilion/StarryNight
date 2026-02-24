<!DOCTYPE html>
<html>
<head>
    <title>星夜阁 - 安装向导 - 环境检查</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="/static/install/css/style.css">
</head>
<body class="install-page step-1">
    <div class="install-wrapper">
        <?php include __DIR__ . '/_partials/sidebar.php'; ?>

        <div class="install-main">
            <div class="install-content">
                <h1>环境检查</h1>
                <p class="description">系统将检查以下项目，以确保服务器环境满足最低要求。</p>

                <div class="form-wrapper">
                    <fieldset>
                        <legend>服务器环境</legend>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>项目</th>
                                    <th>要求</th>
                                    <th>当前</th>
                                    <th>状态</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['env'] as $item): ?>
                                <tr>
                                    <td><?php echo $item['name']; ?></td>
                                    <td><?php echo $item['required']; ?></td>
                                    <td><?php echo $item['current']; ?></td>
                                    <td>
                                        <?php if ($item['pass']): ?>
                                            <span class="status-success">通过</span>
                                        <?php else: ?>
                                            <span class="status-fail">失败</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </fieldset>

                    <fieldset>
                        <legend>目录权限</legend>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>目录</th>
                                    <th>要求</th>
                                    <th>当前</th>
                                    <th>状态</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['dir'] as $item): ?>
                                <tr>
                                    <td><?php echo $item['name']; ?></td>
                                    <td><?php echo $item['required']; ?></td>
                                    <td><?php echo $item['current']; ?></td>
                                    <td>
                                        <?php if ($item['pass']): ?>
                                            <span class="status-success">通过</span>
                                        <?php else: ?>
                                            <span class="status-fail">失败</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </fieldset>

                    <fieldset>
                        <legend>PHP扩展与函数</legend>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>扩展/函数</th>
                                    <th>要求</th>
                                    <th>当前</th>
                                    <th>状态</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['func'] as $item): ?>
                                <tr>
                                    <td><?php echo $item['name']; ?></td>
                                    <td><?php echo $item['required']; ?></td>
                                    <td><?php echo $item['current']; ?></td>
                                    <td>
                                        <?php if ($item['pass']): ?>
                                            <span class="status-success">通过</span>
                                        <?php else: ?>
                                            <span class="status-fail">失败</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </fieldset>
                </div>
            </div>

            <div class="actions">
                <a href="?step=0" class="btn btn-secondary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
                    上一步
                </a>
                <?php if ($data['all_passed']): ?>
                    <a href="?step=2" class="btn btn-primary">
                        下一步
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </a>
                <?php else: ?>
                    <a href="?step=1" class="btn btn-secondary">重新检查</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
