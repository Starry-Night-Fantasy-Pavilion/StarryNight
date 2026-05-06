<template>
  <el-config-provider :locale="zhCn">
  <div class="admin-console">
    <!-- 侧边栏 -->
    <aside class="admin-sidebar" aria-label="运营导航">
      <div class="sidebar-brand">
        <div class="sidebar-brand__logo" aria-hidden="true">
          <svg viewBox="0 0 40 40" fill="none">
            <rect width="40" height="40" rx="12" fill="url(#logo-grad)" />
            <path d="M20 10c-2 0-4 2-4 4v4l-4 2v4l4-2v4l-4 2v4l8-4v-4l4-2v-4l-4 2v-4c0-2-2-4-4-4z" fill="#fff" opacity="0.9"/>
            <defs>
              <linearGradient id="logo-grad" x1="0" y1="0" x2="40" y2="40">
                <stop stop-color="#6366f1"/>
                <stop offset="1" stop-color="#38bdf8"/>
              </linearGradient>
            </defs>
          </svg>
        </div>
        <div class="sidebar-brand__text">
          <span class="sidebar-brand__name">星夜阁</span>
          <span class="sidebar-brand__tag">运营中心</span>
        </div>
      </div>

      <el-scrollbar class="sidebar-scroll">
        <el-menu
          class="admin-nav-menu"
          :default-active="activeMenu"
          router
          :unique-opened="false"
        >
          <template v-for="group in groupedMenus" :key="group.title">
            <div class="nav-group">
              <div class="nav-group__title">{{ group.title }}</div>
              <el-menu-item
                v-for="menu in group.items"
                :key="menu.path"
                :index="`${adminBasePath}/${menu.path}`"
                class="nav-item"
              >
                <el-icon class="nav-item__icon">
                  <component :is="menu.icon" />
                </el-icon>
                <span class="nav-item__label">{{ menu.title }}</span>
                <span v-if="menu.badge" class="nav-item__badge">{{ menu.badge }}</span>
              </el-menu-item>
            </div>
          </template>
        </el-menu>
      </el-scrollbar>

      <div class="sidebar-foot">
        <div class="sidebar-foot__status">
          <span class="status-dot status-dot--online"></span>
          <span class="sidebar-foot__text">系统运行中</span>
        </div>
        <span class="sidebar-foot__version">v2.0</span>
      </div>
    </aside>

    <!-- 主内容区 -->
    <div class="admin-shell">
      <!-- 顶部栏 -->
      <header class="admin-topbar">
        <div class="topbar-left">
          <div class="topbar-breadcrumb">
            <el-icon class="topbar-breadcrumb__icon"><HomeFilled /></el-icon>
            <el-breadcrumb separator="">
              <el-breadcrumb-item :to="{ path: adminBasePath }">
                <span class="breadcrumb-root">工作台</span>
              </el-breadcrumb-item>
              <template v-if="currentRoute !== '工作台'">
                <span class="breadcrumb-sep">/</span>
                <el-breadcrumb-item>
                  <span class="breadcrumb-current">{{ currentRoute }}</span>
                </el-breadcrumb-item>
              </template>
            </el-breadcrumb>
          </div>
        </div>
        <div class="topbar-right">
          <button
            class="topbar-action-btn"
            title="返回前台"
            @click="goHome"
          >
            <el-icon :size="18"><House /></el-icon>
          </button>
          <button
            class="topbar-action-btn topbar-theme-btn"
            :title="theme === 'dark' ? '切换明亮模式' : '切换暗黑模式'"
            @click="toggleTheme"
          >
            <el-icon :size="18" class="theme-icon-switch">
              <Sunny v-if="theme === 'dark'" />
              <Moon v-else />
            </el-icon>
          </button>
          <el-dropdown trigger="click" @command="handleCommand">
            <button type="button" class="user-trigger">
              <el-avatar :size="34" :src="authStore.userInfo?.avatar" class="user-trigger__avatar">
                {{ opsAvatarLetter }}
              </el-avatar>
              <span class="user-trigger__info">
                <span class="user-trigger__name">{{ authStore.userInfo?.username || '运营账号' }}</span>
                <span class="user-trigger__role">{{ authStore.userInfo?.roleName || '管理员' }}</span>
              </span>
              <el-icon class="user-trigger__arrow"><ArrowDown /></el-icon>
            </button>
            <template #dropdown>
              <el-dropdown-menu class="admin-user-dropdown">
                <el-dropdown-item command="profile">
                  <el-icon><Postcard /></el-icon>
                  <span>个人中心</span>
                </el-dropdown-item>
                <el-dropdown-item command="home">
                  <el-icon><House /></el-icon>
                  <span>返回用户前台</span>
                </el-dropdown-item>
                <el-dropdown-item divided command="logout">
                  <el-icon><SwitchButton /></el-icon>
                  <span>退出登录</span>
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </div>
      </header>

      <!-- 页面内容 -->
      <main class="admin-stage">
        <router-view v-slot="{ Component }">
          <transition name="page-transition" mode="out-in">
            <component :is="Component" :key="route.fullPath" />
          </transition>
        </router-view>
      </main>
    </div>
  </div>
  </el-config-provider>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useOpsSessionStore } from '@/stores/auth'
import { ADMIN_CONSOLE_BASE_PATH, ADMIN_OPS_LOGIN_PATH } from '@/config/portal'
import { useTheme } from '@/composables/useTheme'
import { ElMessage } from 'element-plus'
import zhCn from 'element-plus/es/locale/lang/zh-cn'
import { getAdminCommunityReportStats, getCommunityWorkOrderStats } from '@/api/communityAdmin'
import {
  DataAnalysis,
  User,
  FolderOpened,
  Star,
  Document,
  Cpu,
  Money,
  Tickets,
  List,
  Setting,
  Bell,
  UserFilled,
  Connection,
  Box,
  Medal,
  Operation,
  Menu as MenuIcon,
  ArrowDown,
  House,
  SwitchButton,
  Postcard,
  HomeFilled,
  Sunny,
  Moon,
  Promotion,
  Key,
  Reading,
  Monitor,
  ChatDotRound
} from '@element-plus/icons-vue'

const route = useRoute()
const router = useRouter()
const authStore = useOpsSessionStore()
const { theme, toggleTheme } = useTheme()
const adminBasePath = ADMIN_CONSOLE_BASE_PATH

const opsAvatarLetter = computed(() => authStore.userInfo?.username?.charAt(0) || '运')

const pendingReportCount = ref(0)
const pendingWorkOrderCount = ref(0)

async function loadBadges() {
  try {
    const [rpt, wo] = await Promise.all([getAdminCommunityReportStats(), getCommunityWorkOrderStats()])
    pendingReportCount.value = Math.max(0, Number(rpt?.pendingCount ?? 0))
    pendingWorkOrderCount.value = Math.max(0, Number(wo?.pendingCount ?? 0))
  } catch {
    // ignore
  }
}

function dispatchBadgeRefresh() {
  window.dispatchEvent(new Event('admin-badges-refresh'))
}

const menuOrder: Record<string, number> = {
  dashboard: 1,
  users: 2,
  'vip-members': 2.5,
  categories: 3,
  bookstore: 3.5,
  recommendations: 4,
  novels: 5,
  community: 5.3,
  'risk-control': 5.35,
  announcements: 6,
  activities: 6.2,
  'redeem-codes': 6.25,
  'growth-tasks': 6.3,
  billing: 7,
  orders: 8,
  'ai-config': 9,
  'vector-db': 10,
  storage: 11,
  'system-config': 11.5,
  logs: 12.8,
  cache: 12.85,
  system: 14,
  'ops-accounts': 15
}

const menuIconMap: Record<string, typeof DataAnalysis> = {
  dashboard: DataAnalysis,
  users: User,
  'vip-members': Medal,
  categories: FolderOpened,
  bookstore: Reading,
  recommendations: Star,
  novels: Document,
  community: ChatDotRound,
  'risk-control': Operation,
  'ai-config': Cpu,
  billing: Money,
  orders: Tickets,
  logs: List,
  cache: Monitor,
  system: Setting,
  announcements: Bell,
  activities: Promotion,
  'redeem-codes': Key,
  'growth-tasks': List,
  'ops-accounts': UserFilled,
  'vector-db': Connection,
  storage: Box,
  'system-config': Operation
}

const menuGroupsDef: { title: string; paths: string[] }[] = [
  { title: '概览', paths: ['dashboard'] },
  {
    title: '内容与用户',
    paths: [
      'users',
      'vip-members',
      'categories',
      'bookstore',
      'recommendations',
      'novels',
      'community',
      'risk-control',
      'announcements',
      'activities',
      'redeem-codes',
      'growth-tasks'
    ]
  },
  { title: '订单与计费', paths: ['billing', 'orders'] },
  { title: 'AI 与基础设施', paths: ['ai-config', 'vector-db', 'storage', 'system-config'] },
  { title: '账号权限', paths: ['ops-accounts'] },
  { title: '系统', paths: ['logs', 'cache', 'system'] }
]

const adminMenus = computed(() => {
  return router
    .getRoutes()
    .filter((item) => {
      if (!item.path.startsWith(`${adminBasePath}/`)) return false
      if (item.path.includes('/:')) return false
      const segment = item.path.replace(`${adminBasePath}/`, '')
      if (segment === 'profile') return false
      if (segment === 'roles') return false
      return Boolean(item.meta?.requiresAdmin && item.meta?.title)
    })
    .map((item) => {
      const segment = item.path.replace(`${adminBasePath}/`, '')
      const badge =
        segment === 'risk-control'
          ? pendingReportCount.value
          : segment === 'community'
            ? pendingWorkOrderCount.value
            : 0
      return {
        path: segment,
        title: item.meta.title as string,
        icon: menuIconMap[segment] || MenuIcon,
        badge: badge > 0 ? String(badge) : ''
      }
    })
    .sort((a, b) => (menuOrder[a.path] || 99) - (menuOrder[b.path] || 99))
})

const groupedMenus = computed(() => {
  const menus = adminMenus.value
  const byPath = new Map(menus.map((m) => [m.path, m]))
  const used = new Set<string>()
  const groups = menuGroupsDef
    .map((g) => {
      const items = g.paths.map((p) => byPath.get(p)).filter(Boolean) as typeof menus
      items.forEach((i) => used.add(i.path))
      return { title: g.title, items }
    })
    .filter((g) => g.items.length > 0)

  const orphans = menus.filter((m) => !used.has(m.path))
  if (orphans.length) {
    groups.push({ title: '其他', items: orphans })
  }
  return groups
})

const activeMenu = computed(() => route.path)
const currentRoute = computed(() => (route.meta.title as string) || '工作台')

function goHome() {
  router.push('/home')
}

onMounted(() => {
  loadBadges()
  window.addEventListener('admin-badges-refresh', loadBadges)
})

onBeforeUnmount(() => {
  window.removeEventListener('admin-badges-refresh', loadBadges)
})

async function handleCommand(command: string) {
  switch (command) {
    case 'profile':
      router.push(`${adminBasePath}/profile`)
      break
    case 'home':
      router.push('/home')
      break
    case 'logout':
      await authStore.logoutWithApi()
      ElMessage.success('已退出登录')
      router.push(ADMIN_OPS_LOGIN_PATH)
      break
  }
}
</script>

<style lang="scss" scoped>
.admin-console {
  --admin-sidebar-w: 256px;
  --admin-topbar-h: 60px;
  --admin-accent: #38bdf8;
  --admin-violet: #6366f1;

  display: flex;
  height: 100vh;
  max-height: 100vh;
  min-height: 0;
  overflow: hidden;
  color: $text-primary;
  background: $bg-canvas;
}

.admin-sidebar {
  width: var(--admin-sidebar-w);
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  position: fixed;
  inset: 0 auto 0 0;
  z-index: 200;
  background: $sidebar-gradient;
  border-right: 1px solid $border-subtle;
  box-shadow: $sidebar-shadow;
}

.sidebar-brand {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 22px $space-lg 20px;
  border-bottom: 1px solid $border-subtle;
  background: linear-gradient(180deg, rgba(99, 102, 241, 0.04) 0%, transparent 100%);
}

.sidebar-brand__logo {
  flex-shrink: 0;
  width: 40px;
  height: 40px;

  svg {
    width: 100%;
    height: 100%;
  }
}

.sidebar-brand__text {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.sidebar-brand__name {
  font-size: $font-size-xl;
  font-weight: 700;
  letter-spacing: 0.02em;
  color: $text-primary;
  line-height: 1.2;
}

.sidebar-brand__tag {
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: $text-muted;
}

.sidebar-scroll {
  flex: 1;
  overflow: hidden;

  :deep(.el-scrollbar__wrap) {
    overflow-x: hidden;
  }
}

.admin-nav-menu {
  border-right: none !important;
  background: transparent !important;
  padding: $space-sm 0;

  :deep(.el-menu-item-group) {
    margin: 0;
  }
}

.nav-group {
  padding: $space-sm $space-md;

  & + & {
    margin-top: $space-xs;
  }
}

.nav-group__title {
  padding: 10px $space-sm 6px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: $text-muted;
  line-height: 1.3;
}

.nav-item {
  height: 42px !important;
  margin: 2px 0 !important;
  border-radius: $radius-md !important;
  color: $text-secondary !important;
  font-size: $font-size-md !important;
  font-weight: 500 !important;
  border-right: none !important;
  position: relative !important;
  overflow: hidden !important;
  padding: 0 $space-sm !important;

  .nav-item__icon {
    font-size: 18px;
    width: 20px;
    margin-right: $space-sm;
    opacity: 0.7;
  }

  .nav-item__label {
    flex: 1;
  }

  .nav-item__badge {
    font-size: 10px;
    font-weight: 600;
    padding: 2px 7px;
    border-radius: $radius-full;
    background: $danger-ghost;
    color: $danger-color;
    line-height: 1;
  }

  &:hover {
    color: $text-primary !important;
    background: rgba(148, 163, 184, 0.06) !important;

    .nav-item__icon {
      opacity: 1;
    }
  }
}

:deep(.el-menu-item.is-active) {
  color: $primary-color !important;
  font-weight: 600 !important;
  background: linear-gradient(90deg, rgba(99, 102, 241, 0.16), rgba(56, 189, 248, 0.08)) !important;
  box-shadow: inset 3px 0 0 $primary-color, 0 1px 2px rgba(99, 102, 241, 0.06);

  .nav-item__icon {
    opacity: 1;
    color: $primary-color;
  }

  .nav-item__label {
    letter-spacing: 0.01em;
  }
}

.sidebar-foot {
  padding: $space-md $space-lg;
  border-top: 1px solid $border-subtle;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.sidebar-foot__status {
  display: flex;
  align-items: center;
  gap: $space-sm;
}

.status-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  flex-shrink: 0;

  &--online {
    background: $success-color;
    box-shadow: 0 0 8px $success-glow;
  }
}

.sidebar-foot__text {
  font-size: $font-size-xs;
  color: $text-muted;
}

.sidebar-foot__version {
  font-size: 11px;
  font-weight: 600;
  color: $text-disabled;
  letter-spacing: 0.05em;
}

.admin-shell {
  flex: 1 1 auto;
  margin-left: var(--admin-sidebar-w);
  min-width: 0;
  min-height: 0;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  background: $bg-canvas;
}

.admin-topbar {
  position: sticky;
  top: 0;
  z-index: 100;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: $space-lg;
  height: var(--admin-topbar-h);
  padding: 0 $space-xl;
  background: $topbar-bg;
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border-bottom: 1px solid $topbar-border;
  box-shadow: $topbar-shadow;
}

.topbar-left {
  min-width: 0;
}

.topbar-breadcrumb {
  display: flex;
  align-items: center;
  gap: $space-sm;
}

.topbar-breadcrumb__icon {
  font-size: 16px;
  color: $text-muted;
}

.breadcrumb-root {
  color: $text-muted;
  font-size: $font-size-md;
  font-weight: 500;
  transition: color $transition-fast;

  &:hover {
    color: $text-primary;
  }
}

.breadcrumb-sep {
  color: $text-disabled;
  font-size: $font-size-sm;
  margin: 0 2px;
  user-select: none;
}

.breadcrumb-current {
  color: $text-primary;
  font-size: $font-size-md;
  font-weight: 700;
}

.topbar-right {
  display: flex;
  align-items: center;
  gap: $space-sm;
  padding-left: $space-lg;
  border-left: 1px solid $border-subtle;
}

.topbar-action-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 38px;
  height: 38px;
  border-radius: $radius-md;
  border: 1px solid $border-subtle;
  background: transparent;
  color: $text-muted;
  cursor: pointer;
  transition: all $transition-fast;
  font: inherit;

  &:hover {
    background: $primary-ghost;
    border-color: $border-accent;
    color: $primary-light;
  }

  &:focus-visible {
    outline: 2px solid $primary-light;
    outline-offset: 2px;
  }
}

.topbar-theme-btn {
  .theme-icon-switch {
    transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s ease;
  }

  &:hover .theme-icon-switch {
    transform: rotate(30deg);
  }
}

.user-trigger {
  display: inline-flex;
  align-items: center;
  gap: $space-sm;
  padding: 5px 14px 5px 5px;
  border: 1px solid $border-subtle;
  border-radius: $radius-full;
  background: $bg-surface;
  box-shadow: $shadow-sm;
  cursor: pointer;
  transition: all $transition-fast;
  font: inherit;
  color: inherit;

  &:hover {
    border-color: $border-accent;
    background: $bg-elevated;
    box-shadow: $shadow-md;
  }

  &:focus-visible {
    outline: 2px solid $primary-light;
    outline-offset: 2px;
  }
}

.user-trigger__avatar {
  flex-shrink: 0;
  background: $gradient-primary-btn;
  color: #fff;
  font-weight: 700;
  font-size: $font-size-sm;
}

.user-trigger__info {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  min-width: 0;
  text-align: left;
  line-height: 1.25;
}

.user-trigger__name {
  font-size: $font-size-sm;
  font-weight: 600;
  color: $text-primary;
  max-width: 120px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.user-trigger__role {
  font-size: 11px;
  color: $text-muted;
}

.user-trigger__arrow {
  font-size: 12px;
  color: $text-muted;
  margin-left: 2px;
  transition: transform $transition-fast;
}

.admin-stage {
  flex: 1 1 auto;
  min-height: 0;
  overflow-x: hidden;
  overflow-y: auto;
  overscroll-behavior: contain;
  padding: clamp($space-lg, 2.5vw, $space-2xl);
  padding-bottom: $space-2xl;
  scrollbar-gutter: stable;
  scrollbar-color: #{$scrollbar-thumb} transparent;
  animation: fadeIn 0.35s ease;

  &::-webkit-scrollbar {
    width: 8px;
  }

  &::-webkit-scrollbar-thumb {
    background: $scrollbar-thumb;
    border-radius: 4px;
    border: 2px solid transparent;
    background-clip: padding-box;
  }

  &::-webkit-scrollbar-thumb:hover {
    background: $scrollbar-thumb-hover;
    background-clip: padding-box;
  }

  :deep(.page-container) {
    overflow: visible;
  }

  :deep(.page-header) {
    position: relative;
    padding-bottom: $space-md;
    margin-bottom: $space-xl;

    &::after {
      content: '';
      position: absolute;
      left: 0;
      bottom: 0;
      width: min(120px, 28%);
      height: 3px;
      border-radius: 2px;
      background: linear-gradient(90deg, $primary-color, rgba(56, 189, 248, 0.65), transparent);
      opacity: 0.9;
    }

    h1 {
      font-size: $font-size-3xl;
      font-weight: 700;
      letter-spacing: -0.02em;
      color: $text-primary;
      line-height: 1.2;
    }
  }

  :deep(.page-header__lead) {
    margin-top: $space-sm;
    max-width: 60ch;
    font-size: $font-size-md;
    font-weight: 400;
    line-height: 1.6;
    color: $text-muted;
  }

  :deep(.page-content) {
    display: flex;
    flex-direction: column;
    gap: $space-lg;
  }

  :deep(.el-card) {
    border-color: $border-default;
    box-shadow: $shadow-card;

    &:hover {
      border-color: $border-emphasis;
      box-shadow: $shadow-elevated;
    }
  }

  :deep(.el-card__header) {
    padding: calc($space-md) calc($space-lg);
    border-bottom: 1px solid $border-subtle;
    font-size: $font-size-lg;
    font-weight: 600;
    color: $text-primary;
    background: rgba(255, 255, 255, 0.02);
  }

  :deep(.el-card__body) {
    padding: $space-lg;
  }

  :deep(.card-header) {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: $space-md;
  }

  :deep(.el-table) {
    --el-table-border-color: #{$border-subtle};
    --el-table-header-bg-color: #{$table-header-bg};
    --el-table-tr-bg-color: #{$bg-surface};
    --el-table-row-hover-bg-color: #{$table-hover-bg};
    border-radius: $radius-sm;
    overflow: hidden;
    font-size: $font-size-md;
  }

  :deep(.el-table__header-wrapper) {
    border-radius: $radius-sm $radius-sm 0 0;
  }

  :deep(.el-table th.el-table__cell) {
    background: $table-header-bg !important;
    color: $text-muted;
    font-weight: 600;
    font-size: $font-size-sm;
    border-bottom: 1px solid $border-subtle;
    padding: 14px 0;
  }

  :deep(.el-table td.el-table__cell) {
    padding: 12px 0;
    border-bottom: 1px solid $border-subtle;
  }

  :deep(.el-table .el-table__cell) {
    color: $text-primary;
  }

  :deep(.el-table tr:last-child td) {
    border-bottom: none;
  }

  :deep(.el-table--striped .el-table__body tr.el-table__row--striped td.el-table__cell) {
    background: $table-stripe-bg;
  }

  :deep(.pagination) {
    margin-top: $space-lg;
    display: flex;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: $space-sm;
  }

  :deep(.el-input-number) {
    width: 100%;

    .el-input-number__decrease,
    .el-input-number__increase {
      background: $bg-surface;
      border-color: $border-subtle;
      color: $text-secondary;
      border-radius: 0;

      &:hover {
        color: $primary-light;
      }
    }
  }

  :deep(.el-switch) {
    --el-switch-on-color: #{$primary-color};
    --el-switch-off-color: #{rgba(148, 163, 184, 0.3)};
  }

  :deep(.el-form-item__label) {
    color: $text-secondary;
    font-weight: 500;
  }
}

// --- 页面切换动画 ---
.page-transition-enter-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.page-transition-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}

.page-transition-enter-from {
  opacity: 0;
  transform: translateY(8px);
}

.page-transition-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>

<style lang="scss">
.admin-user-dropdown {
  border-radius: $radius-md !important;
  padding: $space-xs 0 !important;
  box-shadow: $shadow-lg !important;
  border: 1px solid $border-subtle !important;

  .el-dropdown-menu__item {
    display: flex;
    align-items: center;
    gap: $space-sm;
    padding: $space-sm $space-md;
    color: $text-secondary;
    border-radius: $radius-sm;
    margin: 0 $space-xs;

    &:hover {
      color: $text-primary;
      background: $bg-elevated;
    }
  }
}

html[data-theme='dark'] .admin-console .el-menu-item.is-active.el-menu-item {
  color: #f1f5f9 !important;
  background: linear-gradient(90deg, rgba(99, 102, 241, 0.26), rgba(56, 189, 248, 0.1)) !important;
  box-shadow: inset 3px 0 0 #a5b4fc, 0 1px 0 rgba(255, 255, 255, 0.05);

  .nav-item__icon {
    color: #c7d2fe !important;
  }
}
</style>
