import type { ResponseVO } from '@/types/api'
import { getApiBaseUrl } from '@/config/apiBase'

function apiUrl(path: string): string {
  const p = path.startsWith('/') ? path : `/${path}`
  return `${getApiBaseUrl()}${p}`
}

async function parseJsonEnvelope<T>(res: Response): Promise<T> {
  let json: ResponseVO<T>
  try {
    json = (await res.json()) as ResponseVO<T>
  } catch {
    throw new Error(`请求失败（HTTP ${res.status}）`)
  }
  if (typeof json.code !== 'number' || json.code !== 200) {
    const msg = (json.message && String(json.message).trim()) || '请求失败'
    throw new Error(msg)
  }
  return json.data as T
}

/** 仅用于 Pinia 会话模块，避免与 axios 拦截器循环依赖 */
export async function authGatewayPost<T>(
  path: string,
  body?: unknown,
  headers?: Record<string, string>
): Promise<T> {
  const h: Record<string, string> = { ...(headers || {}) }
  if (body !== undefined && body !== null) {
    h['Content-Type'] = 'application/json'
  }
  const res = await fetch(apiUrl(path), {
    method: 'POST',
    headers: h,
    body: body === undefined || body === null ? undefined : JSON.stringify(body)
  })
  return parseJsonEnvelope<T>(res)
}

export async function authGatewayGet<T>(path: string, headers?: Record<string, string>): Promise<T> {
  const res = await fetch(apiUrl(path), {
    method: 'GET',
    headers: { ...(headers || {}) }
  })
  return parseJsonEnvelope<T>(res)
}
