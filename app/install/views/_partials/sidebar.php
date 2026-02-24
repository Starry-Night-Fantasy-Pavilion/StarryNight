<?php
$current_step = isset($_GET['step']) ? (int)$_GET['step'] : 0;
?>
<div class="install-sidebar">
    <div>
        <div class="brand-section">
            <div class="brand-logo">
                <img src="/static/logo/logo.png" alt="Logo">
                <span>星夜阁</span>
            </div>
        </div>
        
        <ul class="nav-steps">
            <li class="nav-step <?php echo ($current_step == 0) ? 'active' : ''; ?>">
                欢迎
            </li>
            <li class="nav-step <?php echo ($current_step == 1) ? 'active' : ''; ?>">
                环境检查
            </li>
            <li class="nav-step <?php echo ($current_step == 2) ? 'active' : ''; ?>">
                管理员设置
            </li>
            <li class="nav-step <?php echo ($current_step == 3) ? 'active' : ''; ?>">
                RabbitMQ 配置
            </li>
            <li class="nav-step <?php echo ($current_step == 4) ? 'active' : ''; ?>">
                Redis 配置
            </li>
            <li class="nav-step <?php echo ($current_step == 5) ? 'active' : ''; ?>">
                存储配置
            </li>
            <li class="nav-step <?php echo ($current_step == 6) ? 'active' : ''; ?>">
                数据库配置
            </li>
            <li class="nav-step <?php echo ($current_step == 8) ? 'active' : ''; ?>">
                完成安装
            </li>
        </ul>
    </div>

    <div class="install-footer">
        <p>&copy; 2026 星夜阁. All rights reserved.</p>
    </div>
</div>
