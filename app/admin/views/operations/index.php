<?php
$adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '/');
?>
<div class="dashboard-v2">
    <div class="dashboard-header-v2">
        <h1 class="dashboard-title-v2">运营功能中心</h1>
        <p class="dashboard-subtitle-v2">选择下方功能模块查看详细数据</p>
    </div>
    <div class="dashboard-grid-v2">
        <a href="/<?= $adminPrefix ?>/operations/new-user" class="dashboard-card-v2">
            <div class="card-icon-v2 bg-user">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </div>
            <div class="card-content-v2">
                <h3 class="card-title-v2">新增用户</h3>
                <p class="card-desc-v2">查看新增用户统计数据与趋势</p>
            </div>
            <div class="card-arrow-v2">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 12h14M12 5l7 7-7 7"></path>
                </svg>
            </div>
        </a>

        <a href="/<?= $adminPrefix ?>/operations/dau" class="dashboard-card-v2">
            <div class="card-icon-v2 bg-dau">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                </svg>
            </div>
            <div class="card-content-v2">
                <h3 class="card-title-v2">日活用户</h3>
                <p class="card-desc-v2">查看日活跃用户数据与活跃度</p>
            </div>
            <div class="card-arrow-v2">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 12h14M12 5l7 7-7 7"></path>
                </svg>
            </div>
        </a>

        <a href="/<?= $adminPrefix ?>/operations/coin-spend" class="dashboard-card-v2">
            <div class="card-icon-v2 bg-coin">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M12 6v12M15 9H9a3 3 0 0 0 0 6h6a3 3 0 0 0 0-6z"></path>
                </svg>
            </div>
            <div class="card-content-v2">
                <h3 class="card-title-v2">星夜币消耗</h3>
                <p class="card-desc-v2">查看星夜币消耗统计与分析</p>
            </div>
            <div class="card-arrow-v2">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 12h14M12 5l7 7-7 7"></path>
                </svg>
            </div>
        </a>

        <a href="/<?= $adminPrefix ?>/operations/revenue" class="dashboard-card-v2">
            <div class="card-icon-v2 bg-revenue">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
            </div>
            <div class="card-content-v2">
                <h3 class="card-title-v2">收入</h3>
                <p class="card-desc-v2">查看收入统计数据与趋势</p>
            </div>
            <div class="card-arrow-v2">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 12h14M12 5l7 7-7 7"></path>
                </svg>
            </div>
        </a>

        <a href="/<?= $adminPrefix ?>/operations/new-novel" class="dashboard-card-v2">
            <div class="card-icon-v2 bg-novel">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>
            </div>
            <div class="card-content-v2">
                <h3 class="card-title-v2">新增小说</h3>
                <p class="card-desc-v2">查看新增小说数据与统计</p>
            </div>
            <div class="card-arrow-v2">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 12h14M12 5l7 7-7 7"></path>
                </svg>
            </div>
        </a>

        <a href="/<?= $adminPrefix ?>/operations/new-music" class="dashboard-card-v2">
            <div class="card-icon-v2 bg-music">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 18V5l12-2v13"></path>
                    <circle cx="6" cy="18" r="3"></circle>
                    <circle cx="18" cy="16" r="3"></circle>
                </svg>
            </div>
            <div class="card-content-v2">
                <h3 class="card-title-v2">新增音乐</h3>
                <p class="card-desc-v2">查看新增音乐数据与统计</p>
            </div>
            <div class="card-arrow-v2">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 12h14M12 5l7 7-7 7"></path>
                </svg>
            </div>
        </a>

        <a href="/<?= $adminPrefix ?>/operations/new-anime" class="dashboard-card-v2">
            <div class="card-icon-v2 bg-anime">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                    <line x1="8" y1="21" x2="16" y2="21"></line>
                    <line x1="12" y1="17" x2="12" y2="21"></line>
                    <path d="M7 8h.01M17 8h.01M12 8h.01"></path>
                </svg>
            </div>
            <div class="card-content-v2">
                <h3 class="card-title-v2">新增动漫</h3>
                <p class="card-desc-v2">查看新增动漫数据与统计</p>
            </div>
            <div class="card-arrow-v2">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 12h14M12 5l7 7-7 7"></path>
                </svg>
            </div>
        </a>
    </div>
</div>

