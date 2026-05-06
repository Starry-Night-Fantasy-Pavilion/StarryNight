import type { AxiosInstance } from 'axios'

let serverApiPublicOrigin: string | null = null

function trimOrigin(raw: string): string {
  return raw.trim().replace(/\/+$/, '')
}

function envApiPublicOrigin(): string {
  return trimOrigin((import.meta.env.VITE_API_PUBLIC_ORIGIN as string | undefined) ?? '')
}

/** 首屏拉取公开配置后写入；与第三方登录运营页无关，对应库表 portal.frontend.api-public-origin。 */
export function applyRuntimeApiPublicOriginFromServer(origin: string | undefined | null): void {
  if (origin == null || !String(origin).trim()) {
    serverApiPublicOrigin = null
    return
  }
  const o = trimOrigin(String(origin))
  serverApiPublicOrigin = o || null
}

/** 接口前缀（含 /api）。优先环境变量 VITE_API_PUBLIC_ORIGIN；否则库表 portal 项；否则相对路径 /api。 */
export function getApiBaseUrl(): string {
  const e = envApiPublicOrigin()
  if (e) return `${e}/api`
  if (serverApiPublicOrigin) return `${serverApiPublicOrigin}/api`
  return '/api'
}

export function syncAxiosApiBase(client: AxiosInstance): void {
  client.defaults.baseURL = getApiBaseUrl()
}
