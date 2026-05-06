import { createRouter, createWebHistory } from 'vue-router'
import type { RouteRecordRaw } from 'vue-router'
import { useUserSessionStore, useOpsSessionStore } from '@/stores/auth'
import { ADMIN_CONSOLE_BASE_PATH, ADMIN_OPS_LOGIN_PATH } from '@/config/portal'
import { isAllowedOpsLoginRedirectQuery } from '@/utils/authRedirect'

const adminBasePath = ADMIN_CONSOLE_BASE_PATH

const routes: RouteRecordRaw[] = [
  {
    path: '/auth/ops-login',
    redirect: (to) => ({
      path: ADMIN_OPS_LOGIN_PATH,
      query: to.query,
      replace: true
    })
  },
  /** 运营登录与用户端 /auth/* 完全分路径，与控制台共用 ADMIN 前缀 */
  {
    path: ADMIN_OPS_LOGIN_PATH,
    name: 'OpsLogin',
    component: () => import('@/views/auth/OpsLogin.vue'),
    meta: { title: '运营端登录' }
  },
  {
    path: '/auth',
    component: () => import('@/components/layout/UserLayout.vue'),
    children: [
      {
        path: 'login',
        name: 'Login',
        component: () => import('@/views/auth/Login.vue'),
        meta: { title: '登录', omitChrome: true }
      },
      {
        path: 'register',
        name: 'Register',
        component: () => import('@/views/auth/Register.vue'),
        meta: { title: '注册', omitChrome: true }
      },
      {
        path: 'oauth-callback',
        name: 'OauthCallback',
        component: () => import('@/views/auth/OAuthCallback.vue'),
        meta: { title: '正在登录', omitChrome: true }
      },
      {
        path: 'realname-result',
        name: 'RealnameResult',
        component: () => import('@/views/auth/RealnameResult.vue'),
        meta: { title: '实名核验结果', omitChrome: true }
      }
    ]
  },
  {
    path: '/',
    component: () => import('@/components/layout/UserLayout.vue'),
    children: [
      {
        path: '',
        name: 'PortalLanding',
        component: () => import('@/views/user/PortalLanding.vue'),
        meta: { title: '星夜 · AI 小说创作', omitChrome: true }
      },
      {
        path: 'home',
        name: 'Home',
        component: () => import('@/views/user/Home.vue'),
        meta: { title: '首页', requiresAuth: true }
      },
      {
        path: 'author',
        name: 'AuthorCenter',
        component: () => import('@/views/user/AuthorCenter.vue'),
        meta: { title: '作者中心', requiresAuth: true }
      },
      {
        path: 'novel/:id',
        component: () => import('@/views/user/NovelDetail.vue'),
        meta: { title: '作品详情', requiresAuth: true },
        children: [
          {
            path: '',
            name: 'NovelDetail',
            redirect: { name: 'NovelOutline' }
          },
          {
            path: 'outline',
            name: 'NovelOutline',
            component: () => import('@/views/user/NovelOutline.vue'),
            meta: { title: '大纲管理' }
          },
          {
            path: 'chapters',
            name: 'NovelChapters',
            component: () => import('@/views/user/NovelChapters.vue'),
            meta: { title: '章节管理' }
          },
          {
            path: 'volumes',
            name: 'NovelVolumes',
            component: () => import('@/views/user/NovelVolumes.vue'),
            meta: { title: '卷管理' }
          },
          {
            path: 'character',
            name: 'NovelCharacter',
            component: () => import('@/views/user/NovelCharacter.vue'),
            meta: { title: '角色库' }
          },
          {
            path: 'timeline',
            name: 'NovelTimeline',
            component: () => import('@/views/user/NovelTimeline.vue'),
            meta: { title: '时间线' }
          },
          {
            path: 'rhythm',
            name: 'NovelRhythm',
            component: () => import('@/views/user/NovelRhythm.vue'),
            meta: { title: '节奏仪表盘' }
          },
          {
            path: 'foresight',
            name: 'NovelForesight',
            component: () => import('@/views/user/NovelForesight.vue'),
            meta: { title: '伏笔管理' }
          },
          {
            path: 'branch',
            name: 'NovelBranch',
            component: () => import('@/views/user/NovelBranch.vue'),
            meta: { title: '分支版本' }
          },
          {
            path: 'engine',
            name: 'NovelEngine',
            component: () => import('@/views/user/NovelEngine.vue'),
            meta: { title: '星夜引擎' }
          }
        ]
      },
      {
        path: 'novel/:id/edit',
        name: 'NovelEditor',
        component: () => import('@/views/user/NovelEditor.vue'),
        meta: { title: '作品编辑', requiresAuth: true }
      },
      {
        path: 'knowledge',
        name: 'KnowledgeLibrary',
        component: () => import('@/views/user/KnowledgeLibrary.vue'),
        meta: { title: '知识库', requiresAuth: true }
      },
      {
        path: 'knowledge/:id',
        name: 'KnowledgeDetail',
        component: () => import('@/views/user/KnowledgeDetail.vue'),
        meta: { title: '知识库详情', requiresAuth: true }
      },
      {
        path: 'characters/:novelId',
        name: 'CharacterLibrary',
        component: () => import('@/views/user/CharacterLibrary.vue'),
        meta: { title: '角色库', requiresAuth: true }
      },
      {
        path: 'materials',
        name: 'MaterialLibrary',
        component: () => import('@/views/user/MaterialLibrary.vue'),
        meta: { title: '素材库', requiresAuth: true }
      },
      {
        path: 'prompts',
        name: 'PromptLibrary',
        component: () => import('@/views/user/PromptLibrary.vue'),
        meta: { title: '提示词库', requiresAuth: true }
      },
      {
        path: 'style-expand',
        name: 'StyleExpand',
        component: () => import('@/views/user/StyleExpand.vue'),
        meta: { title: '风格扩写', requiresAuth: true }
      },
      {
        path: 'tools',
        name: 'ToolBox',
        component: () => import('@/views/user/ToolBox.vue'),
        meta: { title: '小工具箱', requiresAuth: true }
      },
      {
        path: 'profile',
        name: 'Profile',
        component: () => import('@/views/user/Profile.vue'),
        meta: { title: '个人中心', requiresAuth: true }
      },
      {
        path: 'orders',
        name: 'OrderCenter',
        component: () => import('@/views/user/OrderCenter.vue'),
        meta: { title: '订单中心', requiresAuth: true }
      },
      {
        path: 'vip',
        name: 'VipCenter',
        component: () => import('@/views/user/VipCenter.vue'),
        meta: { title: '会员中心', requiresAuth: true }
      },
      {
        path: 'notifications',
        name: 'NotificationCenter',
        component: () => import('@/views/user/NotificationCenter.vue'),
        meta: { title: '通知中心', requiresAuth: true }
      },
      {
        path: 'growth',
        name: 'GrowthCenter',
        component: () => import('@/views/user/GrowthCenter.vue'),
        meta: { title: '成长中心', requiresAuth: true }
      },
      {
        path: 'rhythm',
        name: 'RhythmDashboard',
        component: () => import('@/views/user/RhythmDashboard.vue'),
        meta: { title: '叙事节奏', requiresAuth: true }
      },
      {
        path: 'timeline',
        name: 'TimelineView',
        component: () => import('@/views/user/TimelineView.vue'),
        meta: { title: '时间线视图', requiresAuth: true }
      },
      {
        path: 'foresight',
        name: 'ForesightManager',
        component: () => import('@/views/user/ForesightManager.vue'),
        meta: { title: '伏笔管理', requiresAuth: true }
      },
      {
        path: 'tokusatsu',
        name: 'TokusatsuManager',
        component: () => import('@/views/user/TokusatsuManager.vue'),
        meta: { title: '特摄增强', requiresAuth: true }
      },
      {
        path: 'branch-version',
        name: 'BranchVersion',
        component: () => import('@/views/user/BranchVersion.vue'),
        meta: { title: '版本分支', requiresAuth: true }
      },
      {
        path: 'bookstore',
        name: 'BookStore',
        component: () => import('@/views/user/bookstore/BookStore.vue'),
        meta: { title: '在线书城' }
      },
      {
        path: 'bookstore/detail/:id',
        name: 'BookDetail',
        component: () => import('@/views/user/bookstore/BookDetail.vue'),
        meta: { title: '书籍详情' }
      },
      {
        path: 'bookstore/reader/:id/:chapter',
        name: 'Reader',
        component: () => import('@/views/user/bookstore/Reader.vue'),
        meta: { title: '阅读' }
      },
      {
        path: 'bookshelf',
        name: 'Bookshelf',
        component: () => import('@/views/user/bookstore/Bookshelf.vue'),
        meta: { title: '我的书架', requiresAuth: true }
      },
      {
        path: 'bookstore/search',
        name: 'BookSearch',
        component: () => import('@/views/user/bookstore/Search.vue'),
        meta: { title: '书籍搜索' }
      },
      {
        path: 'community',
        name: 'CommunityList',
        component: () => import('@/views/user/community/CommunityList.vue'),
        meta: { title: '星夜社区' }
      },
      {
        path: 'community/post/:id',
        name: 'CommunityPostDetail',
        component: () => import('@/views/user/community/CommunityPostDetail.vue'),
        meta: { title: '帖子详情' }
      },
      {
        path: 'community/new',
        name: 'CommunityPostNew',
        component: () => import('@/views/user/community/CommunityPostEditor.vue'),
        meta: { title: '发布帖子', requiresAuth: true }
      },
      {
        path: 'community/edit/:id',
        name: 'CommunityPostEdit',
        component: () => import('@/views/user/community/CommunityPostEditor.vue'),
        meta: { title: '编辑帖子', requiresAuth: true }
      }
    ]
  },
  {
    path: adminBasePath,
    component: () => import('@/components/layout/AdminLayout.vue'),
    children: [
      {
        path: '',
        redirect: { name: 'Dashboard' }
      },
      {
        path: 'dashboard',
        name: 'Dashboard',
        component: () => import('@/views/admin/Dashboard.vue'),
        meta: { title: '仪表盘', requiresAuth: true, requiresAdmin: true }
      },
      {
        path: 'users',
        name: 'AdminUsers',
        component: () => import('@/views/admin/Users.vue'),
        meta: { title: '用户管理', requiresAuth: true, requiresAdmin: true }
      },
      {
        path: 'vip-members',
        name: 'AdminVipMembers',
        component: () => import('@/views/admin/VipMembers.vue'),
        meta: { title: 'VIP 管理', requiresAuth: true, requiresAdmin: true }
      },
      {
        path: 'categories',
        name: 'AdminCategories',
        component: () => import('@/views/admin/Categories.vue'),
        meta: { title: '分类管理', requiresAuth: true, requiresAdmin: true }
      },
      {
        path: 'bookstore',
        name: 'AdminBookstore',
        component: () => import('@/views/admin/BookstoreAdmin.vue'),
        meta: { title: '书城管理', requiresAuth: true, requiresAdmin: true }
      },
      {
        path: 'recommendations',
        name: 'AdminRecommendations',
        component: () => import('@/views/admin/Recommendations.vue'),
        meta: { title: '推荐管理', requiresAuth: true, requiresAdmin: true }
      },
      {
        path: 'novels',
        name: 'AdminNovels',
        component: () => import('@/views/admin/Novels.vue'),
        meta: { title: '作品管理', requiresAuth: true, requiresAdmin: true }
      },
      {
        path: 'community',
        name: 'AdminCommunity',
        component: () => import('@/views/admin/CommunityAdmin.vue'),
        meta: { title: '社区管理', requiresAuth: true, requiresAdmin: true }
      },
      {
        path: 'risk-control',
        name: 'AdminRiskControl',
        component: () => import('@/views/admin/CommunityAdmin.vue'),
        meta: { title: '举报与风控', requiresAuth: true, requiresAdmin: true }
      },
      {
        path: 'ai-config',
        name: 'AdminAIConfig',
        component: () => import('@/views/admin/AIConfig.vue'),
        meta: { title: 'AI配置', requiresAuth: true, requiresAdmin: true }
      },
      {
        path: 'billing',
        name: 'AdminBilling',
        component: () => import('@/views/admin/BillingConfig.vue'),
        meta: { title: '计费配置', requiresAuth: true, requiresAdmin: true }
      },
      {
        path: 'orders',
        name: 'AdminOrders',
        component: () => import('@/views/admin/Orders.vue'),
        meta: { title: '订单管理', requiresAuth: true, requiresAdmin: true }
      },
      {
        path: 'logs',
        name: 'AdminLogs',
        component: () => import('@/views/admin/Logs.vue'),
        meta: { title: '操作日志', requiresAuth: true, requiresAdmin: true }
      },
      {
        path: 'cache',
        name: 'AdminCache',
        component: () => import('@/views/admin/CacheView.vue'),
        meta: { title: '缓存查看', requiresAuth: true, requiresAdmin: true }
      },
      {
        path: 'system',
        name: 'AdminSystemConfig',
        component: () => import('@/views/admin/SystemConfig.vue'),
        meta: { title: '系统设置', requiresAuth: true, requiresAdmin: true }
      },
      {
        path: 'announcements',
        name: 'AdminAnnouncements',
        component: () => import('@/views/admin/Announcements.vue'),
        meta: { title: '公告管理', requiresAuth: true, requiresAdmin: true }
      },
      {
        path: 'activities',
        name: 'AdminActivities',
        component: () => import('@/views/admin/Activities.vue'),
        meta: { title: '活动管理', requiresAuth: true, requiresAdmin: true }
      },
      {
        path: 'redeem-codes',
        name: 'AdminRedeemCodes',
        component: () => import('@/views/admin/RedeemCodes.vue'),
        meta: { title: '兑换码', requiresAuth: true, requiresAdmin: true }
      },
      {
        path: 'growth-tasks',
        name: 'AdminGrowthTasks',
        component: () => import('@/views/admin/GrowthTasks.vue'),
        meta: { title: '任务管理', requiresAuth: true, requiresAdmin: true }
      },
      {
        path: 'roles',
        name: 'AdminRoles',
        redirect: { name: 'AdminOpsAccounts', query: { tab: 'roles' } },
        meta: { title: '角色与权限', requiresAuth: true, requiresAdmin: true }
      },
      {
        path: 'ops-accounts',
        name: 'AdminOpsAccounts',
        component: () => import('@/views/admin/OpsAccounts.vue'),
        meta: { title: '账号与权限', requiresAuth: true, requiresAdmin: true }
      },
      {
        path: 'profile',
        name: 'AdminOpsProfile',
        component: () => import('@/views/admin/OpsProfile.vue'),
        meta: { title: '个人中心', requiresAuth: true, requiresAdmin: true }
      },
      {
        path: 'vector-db',
        name: 'AdminVectorDb',
        component: () => import('@/views/admin/VectorDbManager.vue'),
        meta: { title: '向量数据库', requiresAuth: true, requiresAdmin: true }
      },
      {
        path: 'storage',
        name: 'AdminStorage',
        component: () => import('@/views/admin/StorageConfig.vue'),
        meta: { title: '存储管理', requiresAuth: true, requiresAdmin: true }
      },
      {
        path: 'payment-config',
        redirect: { name: 'AdminSystemConfigEmpty', query: { tab: 'payment' } },
        meta: { requiresAuth: true, requiresAdmin: true }
      },
      {
        path: 'mail-template',
        redirect: { name: 'AdminSystemConfigEmpty', query: { tab: 'mailTemplate' } },
        meta: { requiresAuth: true, requiresAdmin: true }
      },
      {
        path: 'system-config',
        name: 'AdminSystemConfigEmpty',
        component: () => import('@/views/admin/SystemConfigEmpty.vue'),
        meta: { title: '系统配置', requiresAuth: true, requiresAdmin: true }
      },
      {
        path: 'queue',
        redirect: { name: 'AdminSystemConfigEmpty' },
        meta: { requiresAuth: true, requiresAdmin: true }
      }
    ]
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach(async (to, _from, next) => {
  document.title = `${to.meta.title || 'StarryNight'} - 星夜阁`

  if (to.name === 'OpsLogin' && 'redirect' in to.query && !isAllowedOpsLoginRedirectQuery(to.query.redirect, adminBasePath)) {
    const q = { ...to.query } as Record<string, string | string[]>
    delete q.redirect
    const keys = Object.keys(q)
    next(keys.length ? { name: 'OpsLogin', replace: true, query: q } : { name: 'OpsLogin', replace: true })
    return
  }

  const userS = useUserSessionStore()
  const opsS = useOpsSessionStore()

  if (to.meta.requiresAdmin) {
    if (opsS.isLoggedIn) {
      await opsS.initProfileIfNeeded()
    }
  } else if (to.meta.requiresAuth) {
    if (userS.isLoggedIn) {
      await userS.initProfileIfNeeded()
    }
  }

  if (to.name === 'Login' && userS.isLoggedIn) {
    next({ name: 'Home' })
    return
  }
  if (to.name === 'OpsLogin' && opsS.isLoggedIn) {
    next({ path: adminBasePath })
    return
  }
  if (to.name === 'Register' && userS.isLoggedIn) {
    next({ name: 'Home' })
    return
  }

  if (to.name === 'PortalLanding' && userS.isLoggedIn) {
    next({ name: 'Home', replace: true })
    return
  }

  if (to.meta.requiresAuth && !to.meta.requiresAdmin && !userS.isLoggedIn) {
    next({ name: 'Login', query: { redirect: to.fullPath } })
    return
  }

  if (to.meta.requiresAdmin && !opsS.isLoggedIn) {
    next({ name: 'OpsLogin', query: { redirect: to.fullPath } })
    return
  }

  if (to.meta.requiresAdmin && opsS.userInfo?.isAdmin !== 1) {
    next({ name: 'OpsLogin', query: { redirect: to.fullPath } })
    return
  }

  next()
})

export default router
