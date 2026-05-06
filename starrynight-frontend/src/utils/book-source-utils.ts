/**
 * 书库「书源解析」侧工具：对齐文档中的 book-source-utils / book.ts 职责（本站由后端解析，此处为缓存键、图片代理与目录会话）。
 */
import {
  getBookstoreLiveSources,
  getBookstoreLiveBookCached,
  getBookstoreLiveChapterCached,
  type BookstoreLiveBookApi,
  type BookstoreLiveChapterApi,
  type BookstoreLiveSourceItem
} from '@/api/bookstore'
import { BOOKSTORE_CACHE_TTL } from '@/utils/bookstoreCache'
import { getApiBaseUrl } from '@/config/apiBase'

/** 与文档分级 TTL 命名对齐（实际毫秒值见 {@link BOOKSTORE_CACHE_TTL}） */
export const CACHE_DURATIONS = {
  hot: 30 * 60 * 1000,
  categories: 60 * 60 * 1000,
  search: 15 * 60 * 1000,
  category: 20 * 60 * 1000,
  book: BOOKSTORE_CACHE_TTL.liveBook,
  chapter: BOOKSTORE_CACHE_TTL.liveChapter
} as const

export function liveTocStorageKey(bookId: number) {
  return `bookstore_live_toc:${bookId}`
}

export interface LiveTocChapterItem {
  chapterNo: number
  title: string
  url: string
}

export interface LiveTocPayload {
  sourceId: number
  chapters: LiveTocChapterItem[]
}

export function readLiveTocFromSession(bookId: number): LiveTocPayload | null {
  try {
    const raw = sessionStorage.getItem(liveTocStorageKey(bookId))
    if (!raw) return null
    const p = JSON.parse(raw) as LiveTocPayload
    if (!p?.chapters?.length || p.sourceId !== bookId) return null
    return p
  } catch {
    return null
  }
}

export function writeLiveTocToSession(bookId: number, payload: LiveTocPayload) {
  try {
    sessionStorage.setItem(liveTocStorageKey(bookId), JSON.stringify(payload))
  } catch {
    /* 隐私模式等 */
  }
}

export function clearLiveTocSession(bookId: number) {
  try {
    sessionStorage.removeItem(liveTocStorageKey(bookId))
  } catch {
    /* ignore */
  }
}

/** 文档 getBookSources */
export async function getBookSources() {
  return getBookstoreLiveSources()
}

/**
 * 文档步骤2：若 url 非 http(s) 开头，则相对书源项 baseUrl（书源基准）补全为绝对地址。
 */
export function resolveBookListingUrl(catalogBaseUrl: string | undefined | null, urlPart?: string | null): string {
  const part = (urlPart ?? '').trim()
  if (!part) {
    const b = (catalogBaseUrl ?? '').trim()
    if (!b) throw new Error('缺少 url')
    return b
  }
  if (/^https?:\/\//i.test(part)) return part
  const base = (catalogBaseUrl ?? '').trim()
  if (!base) throw new Error('相对路径需书源项 baseUrl')
  return new URL(part.startsWith('/') ? part : `/${part}`, base.endsWith('/') ? base : `${base}/`).href
}

/** 文档：getBookSources → 按 sourceId 取书源 → 补全 url → GET /bookstore/book（带缓存） */
export async function fetchBookstoreBookPerDoc(sourceId: number, urlPart?: string): Promise<BookstoreLiveBookApi> {
  const sources = await getBookSources()
  const source = sources.find((s: BookstoreLiveSourceItem) => s.id === sourceId)
  if (!source) throw new Error('书源不存在')
  const full = urlPart?.trim() ? resolveBookListingUrl(source.baseUrl, urlPart) : undefined
  return getBookstoreLiveBookCached(sourceId, full)
}

export async function fetchLiveBookCached(
  sourceId: number,
  url?: string
): Promise<BookstoreLiveBookApi> {
  return getBookstoreLiveBookCached(sourceId, url)
}

export async function fetchLiveChapterCached(
  sourceId: number,
  chapterUrl: string,
  onFresh?: (d: BookstoreLiveChapterApi) => void
): Promise<BookstoreLiveChapterApi> {
  return getBookstoreLiveChapterCached(sourceId, chapterUrl, onFresh)
}

/** 文档图片代理：img 指向本站 /bookstore/proxy/image */
export function bookstoreImageProxyUrl(remoteUrl: string): string {
  const base = getApiBaseUrl().replace(/\/+$/, '')
  return `${base}/bookstore/proxy/image?url=${encodeURIComponent(remoteUrl)}`
}

export function rewriteBookstoreImagesViaProxy(html: string): string {
  if (!html || !html.includes('src=')) return html
  return html.replace(/src="(https?:\/\/[^"]+)"/gi, (_m, u: string) => `src="${bookstoreImageProxyUrl(u)}"`)
}
