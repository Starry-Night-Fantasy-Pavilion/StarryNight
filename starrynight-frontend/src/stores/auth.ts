/**
 * 会话隔离说明（务必保持）：
 * - useUserSessionStore：作者 / 读者端，持久化键 starrynight-user-session，仅 portal USER。
 * - useOpsSessionStore：运营管理端，持久化键 starrynight-ops-session，仅 portal OPS。
 * - axios 鉴权见 request.ts：admin/*、运营独占路径、且当前路由在运营控制台树下时用运营 JWT，其余用用户 JWT。
 * - 用户端登录/注册成功时清除运营本地会话；运营端登录成功时清除用户本地会话，避免双 Token 并存导致请求走错门户。
 * - 登录 / 刷新 / me / 登出走 authGateway（fetch），避免与 axios 拦截器循环依赖。
 */
import { defineStore } from 'pinia'
import { ref, computed, type Ref } from 'vue'
import { ElMessage } from 'element-plus'
import type { AuthPortalType, AuthVO, UserInfo } from '@/types/api'
import { authGatewayGet, authGatewayPost } from '@/utils/authGateway'

function applyAuthPayload(
  data: AuthVO,
  accessToken: Ref<string>,
  refreshToken: Ref<string>,
  userInfo: Ref<UserInfo | null>,
  profileLoaded: Ref<boolean>
) {
  accessToken.value = data.accessToken
  refreshToken.value = data.refreshToken
  userInfo.value = data.user
  profileLoaded.value = true
}

function bearer(accessToken: string): Record<string, string> {
  return accessToken ? { Authorization: `Bearer ${accessToken}` } : {}
}

/** 运营端会话：与用户端独立持久化与 Token（须先于 userSession 定义，供 OAuth 换票时清除运营会话） */
export const useOpsSessionStore = defineStore('opsSession', () => {
  const accessToken = ref<string>('')
  const refreshToken = ref<string>('')
  const userInfo = ref<UserInfo | null>(null)
  const profileLoaded = ref(false)
  const profileLoadingPromise = ref<Promise<UserInfo | null> | null>(null)

  const isLoggedIn = computed(() => !!accessToken.value)

  async function login(username: string, password: string) {
    try {
      const res = await authGatewayPost<AuthVO>('/auth/login', {
        username,
        password,
        portal: 'OPS' as AuthPortalType
      })
      applyAuthPayload(res, accessToken, refreshToken, userInfo, profileLoaded)
      return res
    } catch (e) {
      ElMessage.error(e instanceof Error ? e.message : '登录失败')
      throw e
    }
  }

  async function refreshAccessToken() {
    const res = await authGatewayPost<AuthVO>('/auth/refresh-token', null, {
      'Refresh-Token': refreshToken.value
    })
    applyAuthPayload(res, accessToken, refreshToken, userInfo, profileLoaded)
    return res
  }

  async function fetchProfile() {
    const res = await authGatewayGet<UserInfo>('/auth/me', bearer(accessToken.value))
    userInfo.value = res
    profileLoaded.value = true
    return res
  }

  async function initProfileIfNeeded() {
    if (!accessToken.value) {
      profileLoaded.value = false
      return null
    }
    if (userInfo.value && profileLoaded.value) {
      return userInfo.value
    }
    if (profileLoadingPromise.value) {
      return profileLoadingPromise.value
    }
    const loadingPromise = fetchProfile()
      .catch(() => {
        logout()
        return null
      })
      .finally(() => {
        profileLoadingPromise.value = null
      })
    profileLoadingPromise.value = loadingPromise
    return loadingPromise
  }

  function logout() {
    accessToken.value = ''
    refreshToken.value = ''
    userInfo.value = null
    profileLoaded.value = false
    profileLoadingPromise.value = null
  }

  async function logoutWithApi() {
    try {
      if (accessToken.value) {
        await authGatewayPost<void>('/auth/logout', {}, bearer(accessToken.value))
      }
    } finally {
      logout()
    }
  }

  async function sendResetCode(username: string) {
    await authGatewayPost<void>('/auth/send-code', { username })
  }

  async function resetPassword(username: string, code: string, newPassword: string) {
    await authGatewayPost<void>('/auth/reset-password', { username, code, newPassword })
  }

  return {
    accessToken,
    refreshToken,
    userInfo,
    profileLoaded,
    isLoggedIn,
    login,
    refreshAccessToken,
    fetchProfile,
    initProfileIfNeeded,
    logout,
    logoutWithApi,
    sendResetCode,
    resetPassword
  }
}, {
  persist: {
    key: 'starrynight-ops-session',
    pick: ['accessToken', 'refreshToken', 'userInfo']
  }
})

/** 用户端会话：与运营端独立持久化与 Token */
export const useUserSessionStore = defineStore('userSession', () => {
  const accessToken = ref<string>('')
  const refreshToken = ref<string>('')
  const userInfo = ref<UserInfo | null>(null)
  const profileLoaded = ref(false)
  const profileLoadingPromise = ref<Promise<UserInfo | null> | null>(null)

  const isLoggedIn = computed(() => !!accessToken.value)

  async function login(username: string, password: string) {
    try {
      const res = await authGatewayPost<AuthVO>('/auth/login', {
        username,
        password,
        portal: 'USER' as AuthPortalType
      })
      applyAuthPayload(res, accessToken, refreshToken, userInfo, profileLoaded)
      return res
    } catch (e) {
      ElMessage.error(e instanceof Error ? e.message : '登录失败')
      throw e
    }
  }

  async function register(data: {
    username: string
    password: string
    email?: string
    phone?: string
    realName?: string
    idCardNo?: string
  }) {
    try {
      const res = await authGatewayPost<AuthVO>('/auth/register', data)
      applyAuthPayload(res, accessToken, refreshToken, userInfo, profileLoaded)
      return res
    } catch (e) {
      ElMessage.error(e instanceof Error ? e.message : '注册失败')
      throw e
    }
  }

  async function refreshAccessToken() {
    const res = await authGatewayPost<AuthVO>('/auth/refresh-token', null, {
      'Refresh-Token': refreshToken.value
    })
    applyAuthPayload(res, accessToken, refreshToken, userInfo, profileLoaded)
    return res
  }

  async function fetchProfile() {
    const res = await authGatewayGet<UserInfo>('/auth/me', bearer(accessToken.value))
    userInfo.value = res
    profileLoaded.value = true
    return res
  }

  async function initProfileIfNeeded() {
    if (!accessToken.value) {
      profileLoaded.value = false
      return null
    }
    if (userInfo.value && profileLoaded.value) {
      return userInfo.value
    }
    if (profileLoadingPromise.value) {
      return profileLoadingPromise.value
    }
    const loadingPromise = fetchProfile()
      .catch(() => {
        logout()
        return null
      })
      .finally(() => {
        profileLoadingPromise.value = null
      })
    profileLoadingPromise.value = loadingPromise
    return loadingPromise
  }

  function logout() {
    accessToken.value = ''
    refreshToken.value = ''
    userInfo.value = null
    profileLoaded.value = false
    profileLoadingPromise.value = null
  }

  async function logoutWithApi() {
    try {
      if (accessToken.value) {
        await authGatewayPost<void>('/auth/logout', {}, bearer(accessToken.value))
      }
    } finally {
      logout()
    }
  }

  async function sendResetCode(username: string) {
    await authGatewayPost<void>('/auth/send-code', { username })
  }

  async function resetPassword(username: string, code: string, newPassword: string) {
    await authGatewayPost<void>('/auth/reset-password', { username, code, newPassword })
  }

  async function exchangeOauthSid(sid: string) {
    const res = await authGatewayPost<AuthVO>('/auth/oauth/exchange', { sid })
    const ops = useOpsSessionStore()
    ops.logout()
    applyAuthPayload(res, accessToken, refreshToken, userInfo, profileLoaded)
    return res
  }

  return {
    accessToken,
    refreshToken,
    userInfo,
    profileLoaded,
    isLoggedIn,
    login,
    register,
    refreshAccessToken,
    fetchProfile,
    initProfileIfNeeded,
    logout,
    logoutWithApi,
    sendResetCode,
    /** 与 sendResetCode 同义，供登录页等历史调用名 */
    forgotPassword: sendResetCode,
    resetPassword,
    exchangeOauthSid
  }
}, {
  persist: {
    key: 'starrynight-user-session',
    pick: ['accessToken', 'refreshToken', 'userInfo']
  }
})
