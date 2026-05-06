import type { LocationQueryValue } from 'vue-router'
import { ADMIN_CONSOLE_BASE_PATH } from '@/config/portal'

const adminSegments = new Set([
  'dashboard',
  'users',
  'categories',
  'bookstore',
  'community',
  'cache',
  'ai-config',
  'orders',
  'logs',
  'system',
  'announcements',
  'activities',
  'redeem-codes',
  'growth-tasks',
  'roles'
])

function adminBaseNormalized(adminBasePath = ADMIN_CONSOLE_BASE_PATH): string {
  return adminBasePath.replace(/\/$/, '') || '/admin'
}

/** 是否位于运营控制台路由树下（不含查询/hash）；不含独立登录页 /…/login */
export function isAdminConsolePath(
  path: string,
  adminBasePath: string = ADMIN_CONSOLE_BASE_PATH
): boolean {
  const base = adminBaseNormalized(adminBasePath)
  const p = (path.split(/[?#]/)[0] || '').replace(/\/$/, '') || path
  const loginPath = `${base}/login`
  if (p === loginPath) return false
  return p === base || p.startsWith(`${base}/`)
}

function firstQueryValue(v: LocationQueryValue | LocationQueryValue[] | undefined): string | undefined {
  if (v === undefined) return undefined
  if (Array.isArray(v)) {
    const x = v[0]
    return x == null ? undefined : x
  }
  return v ?? undefined
}

/** 登录成功后的跳转路径（处理 redirect 查询与运营子路径简写） */
export function normalizeRedirectTarget(raw: string | undefined | null): string {
  const adminBasePath = ADMIN_CONSOLE_BASE_PATH
  if (raw == null || !String(raw).trim()) {
    return '/home'
  }
  const t = String(raw).trim()
  if (t.startsWith('/')) {
    return t
  }
  if (adminSegments.has(t)) {
    return `${adminBasePath}/${t}`
  }
  return `/${t}`
}

/**
 * 运营端登录成功后的跳转：仅允许控制台路径，拒绝用户端路径、外链与空 redirect 污染。
 */
export function safeOpsPostLoginRedirect(
  raw: LocationQueryValue | LocationQueryValue[] | undefined,
  adminBasePath: string = ADMIN_CONSOLE_BASE_PATH
): string {
  const base = adminBaseNormalized(adminBasePath)
  const rawStr = firstQueryValue(raw)
  if (rawStr == null || !String(rawStr).trim()) {
    return base
  }
  const t = String(rawStr).trim()
  if (t.includes('://') || t.startsWith('//')) {
    return base
  }
  const candidate = normalizeRedirectTarget(t)
  return isAdminConsolePath(candidate, adminBasePath) ? candidate : base
}

/** 是否允许把该值写进运营登录页的 query.redirect（非空且为控制台路径） */
export function isAllowedOpsLoginRedirectQuery(
  raw: LocationQueryValue | LocationQueryValue[] | undefined,
  adminBasePath: string = ADMIN_CONSOLE_BASE_PATH
): boolean {
  const rawStr = firstQueryValue(raw)
  if (rawStr == null || !String(rawStr).trim()) {
    return false
  }
  const t = String(rawStr).trim()
  if (t.includes('://') || t.startsWith('//')) {
    return false
  }
  return isAdminConsolePath(normalizeRedirectTarget(t), adminBasePath)
}
