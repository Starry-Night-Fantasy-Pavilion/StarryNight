import { del, get, post } from '@/utils/request'

export function listCacheNames() {
  return get<string[]>('/admin/cache/names')
}

export interface CacheKeyEntry {
  key: string
  ttlSeconds: number
  valuePreview: string
}

export function scanRedisKeys(params: { pattern?: string; limit?: number }) {
  return get<CacheKeyEntry[]>('/admin/cache/redis/scan', { params })
}

export function getRedisValuePreview(key: string) {
  return get<string>('/admin/cache/redis/value', { params: { key } })
}

export function deleteRedisKey(key: string) {
  return del<void>('/admin/cache/redis/key', { params: { key } })
}

export function clearSpringCache(cacheName: string) {
  return post<void>('/admin/cache/spring/clear', null, { params: { cacheName } })
}
