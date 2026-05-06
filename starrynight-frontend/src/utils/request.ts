import axios from 'axios'
import type {
  AxiosInstance,
  AxiosRequestConfig,
  AxiosResponse,
  InternalAxiosRequestConfig,
  AxiosError
} from 'axios'
import { ElMessage } from 'element-plus'
import { useUserSessionStore, useOpsSessionStore } from '@/stores/auth'
import { ADMIN_CONSOLE_BASE_PATH, ADMIN_OPS_LOGIN_PATH } from '@/config/portal'
import router from '@/router'
import { getApiBaseUrl } from '@/config/apiBase'
import type { ResponseVO } from '@/types/api'

declare module 'axios' {
  interface AxiosRequestConfig {
    /** 为 true 时响应错误不弹全局 ElMessage（用于轮询、后台刷新） */
    silentGlobalError?: boolean
  }
}

interface RetryableRequestConfig extends InternalAxiosRequestConfig {
  _retry?: boolean
}

type SessionPortal = 'USER' | 'OPS'

function normalizedApiPath(config: Pick<InternalAxiosRequestConfig, 'url'>): string {
  return (config.url || '').replace(/^\//, '').split('?')[0]
}

/** 后端路径显式归属运营 JWT（含 admin 前缀及少量非 admin 路径） */
function pathRequiresOpsToken(rel: string): boolean {
  if (rel.startsWith('admin/')) return true
  return false
}

/** 当前 SPA 是否处在运营控制台路由树下（用于与用户端共路径的接口，如 GET /novels） */
function isUnderAdminConsoleRoute(): boolean {
  try {
    const path = (router.currentRoute.value?.path || '').split('?')[0].replace(/\/$/, '') || ''
    const base = ADMIN_CONSOLE_BASE_PATH.replace(/\/$/, '')
    if (path === ADMIN_OPS_LOGIN_PATH.replace(/\/$/, '')) return false
    return path === base || path.startsWith(`${base}/`)
  } catch {
    return false
  }
}

function resolveRequestPortal(config: InternalAxiosRequestConfig): SessionPortal {
  const p = config.authPortal
  if (p === 'OPS' || p === 'USER') return p
  const rel = normalizedApiPath(config)
  if (pathRequiresOpsToken(rel)) return 'OPS'
  if (isUnderAdminConsoleRoute()) return 'OPS'
  return 'USER'
}

/** 从 Axios / 业务 reject 中取出可读文案（兼容 ResponseVO、Spring Boot 默认 JSON、HTML 错误页） */
export function extractApiErrorMessage(error: unknown): string {
  if (axios.isAxiosError(error)) {
    const ax = error as AxiosError
    const { response, message } = ax
    const data: unknown = response?.data

    if (data != null && typeof data === 'object' && !Array.isArray(data)) {
      const body = data as Record<string, unknown>
      /** 后端 ResponseVO（message）、Spring ProblemDetail（detail/title）、网关等 */
      const textKeys = ['message', 'msg', 'detail', 'errorMessage', 'error_description'] as const
      for (const key of textKeys) {
        const v = body[key]
        if (typeof v === 'string' && v.trim()) return v.trim()
      }
      const nested = body.data
      if (nested != null && typeof nested === 'object' && !Array.isArray(nested)) {
        const inner = nested as Record<string, unknown>
        for (const key of textKeys) {
          const v = inner[key]
          if (typeof v === 'string' && v.trim()) return v.trim()
        }
      }
      const err = body.error
      if (typeof err === 'string' && err.trim()) return err.trim()
      const title = body.title
      if (typeof title === 'string' && title.trim()) return title.trim()
      const path = body.path
      if (typeof path === 'string' && response?.status != null) {
        return `请求失败（${response.status}）${path}`
      }
    }

    if (typeof data === 'string') {
      const s = data.trim()
      if (s.startsWith('{')) {
        try {
          const parsed = JSON.parse(s) as { message?: string; error?: string }
          if (typeof parsed.message === 'string' && parsed.message.trim()) return parsed.message.trim()
          if (typeof parsed.error === 'string' && parsed.error.trim()) return parsed.error.trim()
        } catch {
          /* ignore */
        }
      }
      const stripped = s.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim()
      if (stripped.length > 0) return stripped.slice(0, 300)
    }

    const status = response?.status
    if (status === 403) return '禁止访问（403）：请检查账号权限、登录入口或使用正确的账号体系'
    if (status === 401) return '未授权（401），请重新登录'
    if (status === 502 || status === 503 || status === 504) {
      return `服务暂不可用（HTTP ${status}），请确认后端已启动且与前端代理端口一致`
    }
    if (status === 404) return '接口不存在（404），请检查请求路径与网关/代理配置'
    if (status === 400)
      return '请求无法处理（HTTP 400）：多为参数校验失败、账号或密码错误、或请求体不是合法 JSON'
    if (status === 500) return '服务器内部错误（500），请查看后端日志'

    if (message === 'Network Error') return '网络异常：无法连接服务器，请检查网络或后端是否已启动'

    return message || '请求失败，请稍后重试'
  }

  if (error instanceof Error && error.message) return error.message
  if (typeof error === 'object' && error !== null && 'message' in error) {
    const m = (error as { message?: unknown }).message
    if (typeof m === 'string' && m.trim()) return m.trim()
  }

  return '请求失败，请稍后重试'
}

const service: AxiosInstance = axios.create({
  baseURL: getApiBaseUrl(),
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json'
  }
})

const refreshQueues: Record<SessionPortal, { busy: boolean; pending: Array<() => void> }> = {
  USER: { busy: false, pending: [] },
  OPS: { busy: false, pending: [] }
}

function flushPortalQueue(portal: SessionPortal) {
  const q = refreshQueues[portal].pending.splice(0, refreshQueues[portal].pending.length)
  q.forEach((cb) => cb())
}

const AUTH_PATHS_WITHOUT_BEARER = [
  'auth/login',
  'auth/register',
  'auth/send-code',
  'auth/reset-password',
  'auth/refresh',
  'auth/refresh-token'
]

service.interceptors.request.use(
  (config) => {
    const userS = useUserSessionStore()
    const opsS = useOpsSessionStore()
    const rel = normalizedApiPath(config)
    const isAuthPublic = AUTH_PATHS_WITHOUT_BEARER.some((p) => rel === p || rel.startsWith(`${p}/`))
    const portal = resolveRequestPortal(config)
    const token = portal === 'OPS' ? opsS.accessToken : userS.accessToken
    if (token && !isAuthPublic) {
      config.headers.Authorization = `Bearer ${token}`
    } else if (isAuthPublic) {
      delete config.headers.Authorization
    }
    return config
  },
  (error) => {
    console.error('Request error:', error)
    return Promise.reject(error)
  }
)

function isEnvelopeCode(value: unknown): value is number {
  return typeof value === 'number' && Number.isFinite(value)
}

service.interceptors.response.use(
  (response: AxiosResponse) => {
    const res = response.data as ResponseVO | undefined
    const code = res && typeof res === 'object' && 'code' in res ? (res as { code: unknown }).code : undefined
    const numericCode = isEnvelopeCode(code) ? code : typeof code === 'string' && code.trim() !== '' ? Number(code) : NaN
    if (!res || typeof res !== 'object' || !Number.isFinite(numericCode)) {
      ElMessage.error('接口返回格式异常，无法解析业务状态码')
      return Promise.reject(new Error('Invalid response envelope'))
    }
    if (numericCode !== 200) {
      if (numericCode === 401) {
        return Promise.reject({
          isBusiness401: true,
          message: res.message || 'Unauthorized',
          response
        })
      }
      ElMessage.error(res.message || 'Request failed')
      return Promise.reject(new Error(res.message || 'Request failed'))
    }
    return res as unknown as AxiosResponse<ResponseVO>
  },
  async (error) => {
    const originalRequest = error.config as RetryableRequestConfig | undefined
    const isUnauthorized =
      error?.isBusiness401 === true || error?.response?.status === 401

    if (
      isUnauthorized &&
      originalRequest &&
      !originalRequest._retry &&
      !originalRequest.url?.includes('/auth/refresh') &&
      !originalRequest.url?.includes('/auth/refresh-token')
    ) {
      const portal = resolveRequestPortal(originalRequest)
      const userS = useUserSessionStore()
      const opsS = useOpsSessionStore()
      const refreshToken = portal === 'OPS' ? opsS.refreshToken : userS.refreshToken

      if (refreshToken) {
        originalRequest._retry = true
        const slot = refreshQueues[portal]

        if (!slot.busy) {
          slot.busy = true
          try {
            if (portal === 'OPS') {
              await opsS.refreshAccessToken()
            } else {
              await userS.refreshAccessToken()
            }
            slot.busy = false
            flushPortalQueue(portal)
            return service(originalRequest)
          } catch (refreshError) {
            slot.busy = false
            slot.pending = []
            if (portal === 'OPS') {
              opsS.logout()
            } else {
              userS.logout()
            }
            ElMessage.error('登录已过期，请重新登录')
            return Promise.reject(refreshError)
          }
        }

        return new Promise((resolve, reject) => {
          slot.pending.push(() => {
            service(originalRequest).then(resolve).catch(reject)
          })
        })
      }
    }

    console.error('Response error:', error)
    const message = extractApiErrorMessage(error)
    const rawUrl = originalRequest?.url || ''
    const rel = rawUrl.replace(/^\//, '').split('?')[0]
    const authFormUrls =
      rel === 'auth/login' ||
      rel.startsWith('auth/login/') ||
      rel === 'auth/register' ||
      rel.startsWith('auth/register/') ||
      rawUrl.includes('/auth/login') ||
      rawUrl.includes('/auth/register')
    const silentGlobal = originalRequest?.silentGlobalError === true
    if (!authFormUrls && !silentGlobal) {
      ElMessage.error(message)
    }
    return Promise.reject(error)
  }
)

export default service

/**
 * 成功响应拦截器返回的是业务信封 `{ code, message, data }`（即原 response.data），
 * 不是 AxiosResponse。此处兼容「信封」与「完整 AxiosResponse」两种形态，只返回内层 data。
 */
function unwrapResponseData<T>(response: unknown): T {
  if (response == null || typeof response !== 'object') {
    return response as T
  }
  const r = response as Record<string, unknown>
  const looksAxios = 'config' in r && 'headers' in r && 'status' in r
  if (looksAxios) {
    const envelope = (r as unknown as AxiosResponse<ResponseVO<T>>).data as ResponseVO<T>
    return envelope.data as T
  }
  if ('code' in r && 'data' in r) {
    return r.data as T
  }
  return response as T
}

export function get<T>(url: string, config?: AxiosRequestConfig): Promise<T> {
  return service.get<T>(url, config).then((response) => unwrapResponseData<T>(response))
}

export function post<T>(url: string, data?: any, config?: AxiosRequestConfig): Promise<T> {
  return service.post<T>(url, data, config).then((response) => unwrapResponseData<T>(response))
}

export function put<T>(url: string, data?: any, config?: AxiosRequestConfig): Promise<T> {
  return service.put<T>(url, data, config).then((response) => unwrapResponseData<T>(response))
}

export function del<T>(url: string, config?: AxiosRequestConfig): Promise<T> {
  return service.delete<T>(url, config).then((response) => unwrapResponseData<T>(response))
}
