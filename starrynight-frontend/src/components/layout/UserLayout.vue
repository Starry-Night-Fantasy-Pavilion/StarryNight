<template>
  <div class="app-shell" :class="{ 'shell--bare': omitChrome }">
    <template v-if="!omitChrome">
      <aside class="sidebar" :class="{ collapsed: sidebarCollapsed }">
        <div class="sidebar-top">
          <router-link to="/home" class="sidebar-logo">
            <div class="logo-icon">
              <svg viewBox="0 0 32 32" width="32" height="32" fill="none">
                <circle cx="16" cy="16" r="14" stroke="url(#sLogoGrad)" stroke-width="1.5" opacity="0.5" />
                <circle cx="16" cy="16" r="4" fill="url(#sLogoGrad)" />
                <circle cx="6" cy="8" r="1.2" fill="url(#sLogoGrad)" opacity="0.6" />
                <circle cx="26" cy="10" r="0.8" fill="url(#sLogoGrad)" opacity="0.4" />
                <circle cx="10" cy="24" r="0.8" fill="url(#sLogoGrad)" opacity="0.5" />
                <circle cx="24" cy="22" r="1" fill="url(#sLogoGrad)" opacity="0.4" />
                <defs>
                  <linearGradient id="sLogoGrad" x1="0" y1="0" x2="32" y2="32">
                    <stop offset="0%" stop-color="#a78bfa" />
                    <stop offset="50%" stop-color="#818cf8" />
                    <stop offset="100%" stop-color="#6366f1" />
                  </linearGradient>
                </defs>
              </svg>
            </div>
            <span class="logo-text" v-show="!sidebarCollapsed">星夜</span>
          </router-link>

          <nav class="sidebar-nav">
            <router-link
              v-for="item in navMenuItems"
              :key="item.path"
              :to="item.path"
              class="nav-item"
              :class="{ active: isActive(item.path) }"
              :title="sidebarCollapsed ? item.label : ''"
            >
              <span class="nav-icon"><component :is="item.icon" :size="20" /></span>
              <span class="nav-label" v-show="!sidebarCollapsed">{{ item.label }}</span>
              <span class="nav-badge" v-if="item.badge && !sidebarCollapsed">{{ item.badge }}</span>
            </router-link>
          </nav>
        </div>

        <div class="sidebar-bottom">
          <div class="sidebar-toggle" @click="toggleSidebar">
            <el-icon :size="18"><Fold /></el-icon>
          </div>

          <div class="user-mini" @click="handleUserMenu">
            <el-avatar :size="36" :src="authStore.userInfo?.avatar" class="user-avatar">
              {{ authStore.userInfo?.username?.charAt(0)?.toUpperCase() }}
            </el-avatar>
            <div class="user-meta" v-show="!sidebarCollapsed">
              <span class="user-name">{{ authStore.userInfo?.nickname || authStore.userInfo?.username }}</span>
              <span class="user-role">{{ memberTypeLabel }}</span>
            </div>
            <el-icon v-show="!sidebarCollapsed" class="user-arrow"><ArrowRight /></el-icon>
          </div>
        </div>
      </aside>

      <div class="main-area">
        <header class="topbar">
          <div class="topbar-left">
            <el-breadcrumb separator="/">
              <el-breadcrumb-item :to="{ path: '/home' }">首页</el-breadcrumb-item>
              <el-breadcrumb-item v-if="currentPageTitle">{{ currentPageTitle }}</el-breadcrumb-item>
            </el-breadcrumb>
          </div>
          <div class="topbar-right">
            <NotificationBell class="topbar-action" />
            <div class="topbar-divider"></div>
            <el-dropdown trigger="click" @command="handleCommand" placement="bottom-end">
              <div class="topbar-user">
                <el-avatar :size="32" :src="authStore.userInfo?.avatar">
                  {{ authStore.userInfo?.username?.charAt(0)?.toUpperCase() }}
                </el-avatar>
                <el-icon class="topbar-arrow"><ArrowDown /></el-icon>
              </div>
              <template #dropdown>
                <el-dropdown-menu class="user-menu-dropdown">
                  <div class="menu-user-card">
                    <el-avatar :size="44" :src="authStore.userInfo?.avatar">
                      {{ authStore.userInfo?.username?.charAt(0)?.toUpperCase() }}
                    </el-avatar>
                    <div>
                      <div class="menu-user-name">{{ authStore.userInfo?.nickname || authStore.userInfo?.username }}</div>
                      <div class="menu-user-email">{{ authStore.userInfo?.email || '创作者' }}</div>
                    </div>
                  </div>
                  <el-dropdown-item command="profile" divided>
                    <el-icon><UserFilled /></el-icon>个人中心
                  </el-dropdown-item>
                  <el-dropdown-item command="orders">
                    <el-icon><Tickets /></el-icon>订单中心
                  </el-dropdown-item>
                  <el-dropdown-item command="vip">
                    <el-icon><Medal /></el-icon>会员中心
                  </el-dropdown-item>
                  <el-dropdown-item command="tickets">
                    <el-icon><Service /></el-icon>工单反馈
                  </el-dropdown-item>
                  <el-dropdown-item command="logout" divided>
                    <el-icon><SwitchButton /></el-icon>退出登录                  </el-dropdown-item>
                </el-dropdown-menu>
              </template>
            </el-dropdown>
          </div>
        </header>

        <main class="content-area">
          <router-view v-slot="{ Component }">
            <transition name="page-fade" mode="out-in">
              <component :is="Component" />
            </transition>
          </router-view>
        </main>
      </div>
    </template>

    <template v-else>
      <router-view v-slot="{ Component }">
        <transition name="page-fade" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useUserSessionStore } from '@/stores/auth'
import { ElMessage } from 'element-plus'
import NotificationBell from '@/components/user/NotificationBell.vue'
import {
  HomeFilled, EditPen, Collection, UserFilled, Box, MagicStick,
  SwitchButton, ArrowDown, ArrowRight, Ticket, Medal, Fold, Tickets,
  Reading, Notebook, DataBoard, ChatDotRound, Service
} from '@element-plus/icons-vue'

const router = useRouter()
const route = useRoute()
const authStore = useUserSessionStore()
const sidebarCollapsed = ref(false)

const omitChrome = computed(() =>
  route.matched.some((r) => Boolean(r.meta.omitChrome))
)

interface NavMenuItem {
  path: string
  label: string
  icon: any
  badge?: string
}

const navMenuItems = computed<NavMenuItem[]>(() => [
  { path: '/home', label: '首页', icon: HomeFilled },
  { path: '/author', label: '创作中心', icon: EditPen },
  { path: '/community', label: '社区', icon: ChatDotRound },
  { path: '/knowledge', label: '知识库', icon: Collection },
  { path: '/characters', label: '角色库', icon: UserFilled },
  { path: '/materials', label: '素材库', icon: Box },
  { path: '/prompts', label: '提示词库', icon: MagicStick },
  { path: '/style-expand', label: '风格拓展', icon: Reading },
  { path: '/tools', label: '工具箱', icon: Notebook },
])

const currentPageTitle = computed(() => {
  const matched = route.matched
  if (matched.length > 1) {
    const current = matched[matched.length - 1]
    return (current.meta?.title as string) || ''
  }
  const item = navMenuItems.value.find(m => isActive(m.path))
  return item?.label || ''
})

const memberTypeLabel = computed(() => {
  const level = authStore.userInfo?.memberLevel || 0
  const map: Record<number, string> = { 0: '普通用户', 1: '青铜', 2: 'VIP', 3: 'SVIP', 4: '钻石' }
  return map[level] || '普通用户'
})

function isActive(path: string) {
  if (path === '/home') return route.path === '/home'
  return route.path.startsWith(path)
}

function toggleSidebar() {
  sidebarCollapsed.value = !sidebarCollapsed.value
}

function handleUserMenu() {
  router.push('/profile')
}

async function handleCommand(cmd: string) {
  switch (cmd) {
    case 'profile':
      router.push('/profile'); break
    case 'orders':
      router.push('/orders'); break
    case 'vip':
      router.push('/vip'); break
    case 'tickets':
      router.push('/tickets'); break
    case 'logout':
      await authStore.logoutWithApi()
      ElMessage.success('已退出登录')
      router.push('/auth/login')
      break
  }
}
</script>

<style lang="scss" scoped>
.app-shell {
  display: flex;
  height: 100vh;
  width: 100%;
  overflow: hidden;

  /**
   * 无侧栏页（官网、登录等）：body 为 overflow:hidden，须在本层占满视口并自行纵向滚动，
   * 否则长页面会被裁切，且背景无法感知为「铺满」。
   */
  &.shell--bare {
    width: 100%;
    min-height: 100vh;
    height: 100vh;
    max-height: 100vh;
    overflow-x: hidden;
    overflow-y: auto;
    overscroll-behavior: contain;
    background: $bg-canvas;
  }
}

.sidebar {
  width: $sidebar-width;
  min-width: $sidebar-width;
  height: 100%;
  background: $sidebar-gradient;
  border-right: 1px solid $border-subtle;
  box-shadow: $sidebar-shadow;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  transition: width $transition-normal, min-width $transition-normal;
  position: relative;
  z-index: 10;

  &.collapsed {
    width: $sidebar-collapsed;
    min-width: $sidebar-collapsed;
  }
}

.sidebar-top {
  padding: $space-md;
  display: flex;
  flex-direction: column;
  gap: $space-sm;
}

.sidebar-logo {
  display: flex;
  align-items: center;
  gap: $space-sm;
  padding: $space-sm $space-md;
  margin-bottom: $space-sm;
  border-radius: $border-radius;
  transition: all $transition-fast;

  &:hover {
    background: $primary-ghost;
  }
}

.logo-icon {
  flex-shrink: 0;
  display: flex;
  animation: float 4s ease-in-out infinite;
}

.logo-text {
  font-size: 18px;
  font-weight: 700;
  background: linear-gradient(135deg, #a78bfa, #818cf8);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  white-space: nowrap;
  letter-spacing: 2px;
}

.sidebar-nav {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: $space-sm;
  padding: 10px 14px;
  border-radius: $border-radius;
  font-size: $font-size-sm;
  font-weight: 500;
  color: $text-secondary;
  transition: all $transition-fast;
  position: relative;
  white-space: nowrap;
  overflow: hidden;

  &:hover {
    color: $text-primary;
    background: $primary-ghost;
  }

  &.active {
    color: #fff;
    background: linear-gradient(135deg, rgba(79, 70, 229, 0.92), rgba(99, 102, 241, 0.85));
    box-shadow:
      inset 0 1px 0 rgba(255, 255, 255, 0.2),
      0 4px 14px rgba(79, 70, 229, 0.28);

    .nav-icon {
      color: #e0e7ff;
    }
  }
}

.nav-icon {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  color: $text-muted;
  transition: color $transition-fast;
}

.nav-badge {
  margin-left: auto;
  font-size: 10px;
  font-weight: 600;
  padding: 1px 7px;
  border-radius: 10px;
  background: $primary-color;
  color: #fff;
}

.sidebar-bottom {
  padding: $space-md;
  border-top: 1px solid $border-subtle;
  display: flex;
  flex-direction: column;
  gap: $space-sm;
}

.sidebar-toggle {
  display: flex;
  justify-content: center;
  padding: 6px;
  border-radius: $border-radius-sm;
  color: $text-muted;
  cursor: pointer;
  transition: all $transition-fast;

  &:hover {
    background: $primary-ghost;
    color: $text-primary;
  }
}

.user-mini {
  display: flex;
  align-items: center;
  gap: $space-sm;
  padding: $space-sm $space-md;
  border-radius: $border-radius;
  cursor: pointer;
  transition: all $transition-fast;

  &:hover {
    background: $primary-ghost;
  }
}

.user-avatar {
  flex-shrink: 0;
}

.user-meta {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.user-name {
  font-size: $font-size-sm;
  font-weight: 600;
  color: $text-primary;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.user-role {
  font-size: $font-size-xs;
  color: $text-muted;
}

.user-arrow {
  color: $text-muted;
  font-size: 12px;
}

.main-area {
  flex: 1 1 auto;
  display: flex;
  flex-direction: column;
  width: 100%;
  min-width: 0;
  min-height: 0;
  height: 100%;
  overflow: hidden;
  background: $bg-canvas;
}

.topbar {
  height: 56px;
  min-height: 56px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 $space-xl;
  background: $topbar-bg;
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  border-bottom: 1px solid $topbar-border;
  box-shadow: $topbar-shadow;
  z-index: 5;
}

.topbar-left {
  :deep(.el-breadcrumb__inner) {
    color: $text-secondary;
    font-size: $font-size-sm;
    font-weight: 500;
  }
  :deep(.el-breadcrumb__item:last-child .el-breadcrumb__inner) {
    color: $text-primary;
  }
}

.topbar-right {
  display: flex;
  align-items: center;
  gap: $space-sm;
}

.topbar-action {
  display: flex;
  align-items: center;
}

.topbar-divider {
  width: 1px;
  height: 24px;
  background: $border-subtle;
}

.topbar-user {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 4px 8px;
  border-radius: $border-radius-sm;
  cursor: pointer;
  transition: all $transition-fast;

  &:hover {
    background: $primary-ghost;
  }
}

.topbar-arrow {
  font-size: 12px;
  color: $text-muted;
}

.user-menu-dropdown {
  min-width: 210px;
}

.menu-user-card {
  display: flex;
  align-items: center;
  gap: $space-sm;
  padding: $space-sm $space-md;

  .menu-user-name {
    font-size: $font-size-sm;
    font-weight: 600;
    color: $text-primary;
  }
  .menu-user-email {
    font-size: $font-size-xs;
    color: $text-muted;
  }
}

.content-area {
  flex: 1 1 auto;
  width: 100%;
  min-width: 0;
  min-height: 0;
  overflow-y: auto;
  overflow-x: hidden;
  overscroll-behavior: contain;
  scrollbar-gutter: stable;
  background: $bg-canvas;
}

.page-fade-enter-active,
.page-fade-leave-active {
  transition: opacity 0.2s ease;
}

.page-fade-enter-from,
.page-fade-leave-to {
  opacity: 0;
}
</style>
