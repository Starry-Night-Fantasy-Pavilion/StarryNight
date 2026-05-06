/**
 * 书城前端分级缓存（对齐「缓存优先、后台刷新」的阅读体验；数据来自星夜自建 API，非书源爬取）。
 */
const PREFIX = 'starrynight_bs:'

export const BOOKSTORE_CACHE_TTL = {
  home: 60 * 60 * 1000,
  book: 30 * 60 * 1000,
  toc: 30 * 60 * 1000,
  chapter: 30 * 60 * 1000,
  /** 与文档一致：书源实时解析结果 */
  liveBook: 30 * 60 * 1000,
  liveChapter: 30 * 60 * 1000
} as const

type CacheEnvelope<T> = { v: T; exp: number }

function parse<T>(raw: string | null): CacheEnvelope<T> | null {
  if (!raw) return null
  try {
    return JSON.parse(raw) as CacheEnvelope<T>
  } catch {
    return null
  }
}

export function bookstoreCacheGet<T>(key: string): T | null {
  try {
    const env = parse<T>(localStorage.getItem(PREFIX + key))
    if (!env || typeof env.exp !== 'number') return null
    if (Date.now() > env.exp) {
      localStorage.removeItem(PREFIX + key)
      return null
    }
    return env.v
  } catch {
    return null
  }
}

export function bookstoreCacheSet<T>(key: string, value: T, ttlMs: number): void {
  try {
    const env: CacheEnvelope<T> = { v: value, exp: Date.now() + ttlMs }
    localStorage.setItem(PREFIX + key, JSON.stringify(env))
  } catch {
    /* 配额满等：忽略 */
  }
}

export function bookstoreCacheRemove(key: string): void {
  try {
    localStorage.removeItem(PREFIX + key)
  } catch {
    /* ignore */
  }
}

/** 先返回缓存并异步刷新写入缓存（可选回调拿到最新数据） */
export function bookstoreCacheStaleRefresh<T>(
  key: string,
  ttlMs: number,
  cached: T | null,
  fetcher: () => Promise<T>,
  onFresh?: (data: T) => void
): void {
  void fetcher()
    .then((fresh) => {
      bookstoreCacheSet(key, fresh, ttlMs)
      onFresh?.(fresh)
    })
    .catch(() => {})
}
