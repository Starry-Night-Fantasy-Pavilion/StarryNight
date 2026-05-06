import { del, get, post, put } from '@/utils/request'
import type { PageVO } from '@/types/api'
import {
  BOOKSTORE_CACHE_TTL,
  bookstoreCacheGet,
  bookstoreCacheSet,
  bookstoreCacheStaleRefresh
} from '@/utils/bookstoreCache'

export interface BookstoreHomeData {
  enabled: boolean
  siteTitle: string
  banners: Record<string, unknown>[]
  hotBooks: Record<string, unknown>[]
  newBooks: Record<string, unknown>[]
  rankingBooks: Record<string, unknown>[]
  categories: { id: number; name: string; icon: string; count: number }[]
  sidebarReaders: Record<string, unknown>[]
  latestUpdates: Record<string, unknown>[]
}

export function getBookstoreHome() {
  return get<BookstoreHomeData>('/bookstore/home')
}

/** 首页：有缓存则先展示，并在后台静默刷新 */
export async function getBookstoreHomeCached(): Promise<BookstoreHomeData> {
  const key = 'home'
  const hit = bookstoreCacheGet<BookstoreHomeData>(key)
  if (hit) {
    bookstoreCacheStaleRefresh(key, BOOKSTORE_CACHE_TTL.home, hit, getBookstoreHome)
    return hit
  }
  const data = await getBookstoreHome()
  bookstoreCacheSet(key, data, BOOKSTORE_CACHE_TTL.home)
  return data
}

export interface BookstoreBookPublic {
  id: number
  title: string
  author?: string
  cover?: string
  description?: string
  views?: number
  rating?: number
  isVip?: boolean
  wordCount?: number
  category?: string
  tags?: string[]
  /** 已配置书源时可走 /bookstore/book + /chapter 实时解析 */
  liveParseAvailable?: boolean
}

/** 书城前台搜索（上架书籍，分页） */
export interface BookstoreSearchBook {
  id: number
  title: string
  author?: string
  cover?: string
  description?: string
  category?: string
  wordCount?: number
  views?: number
  rating?: number
  chapterCount?: number
  isVip?: boolean
  tags?: string[]
  status?: string
}

export function searchBookstoreBooks(params: {
  keyword?: string
  categoryIds?: number[]
  sort?: string
  membership?: string
  wordCountRange?: string
  tags?: string[]
  /** 连载中：至少有一章；与业务「完结」字段无关时仅作弱筛选 */
  completionStatus?: string
  page?: number
  size?: number
}) {
  return get<PageVO<BookstoreSearchBook>>('/bookstore/books/search', {
    params: {
      keyword: params.keyword || undefined,
      categoryIds: params.categoryIds?.length ? params.categoryIds.join(',') : undefined,
      sort: params.sort || 'relevance',
      membership: params.membership || undefined,
      wordCountRange: params.wordCountRange || undefined,
      tags: params.tags?.length ? params.tags.join(',') : undefined,
      completionStatus: params.completionStatus || undefined,
      page: params.page ?? 1,
      size: params.size ?? 20
    }
  })
}

export function getBookstoreBook(id: number) {
  return get<BookstoreBookPublic>(`/bookstore/books/${id}`)
}

export async function getBookstoreBookCached(id: number): Promise<BookstoreBookPublic> {
  const key = `book:${id}`
  const hit = bookstoreCacheGet<BookstoreBookPublic>(key)
  if (hit) {
    bookstoreCacheStaleRefresh(key, BOOKSTORE_CACHE_TTL.book, hit, () => getBookstoreBook(id))
    return hit
  }
  const data = await getBookstoreBook(id)
  bookstoreCacheSet(key, data, BOOKSTORE_CACHE_TTL.book)
  return data
}

export interface BookstoreChapterTocItem {
  id: number
  chapterNo: number
  title: string
  wordCount: number
}

export function getBookstoreChapters(bookId: number) {
  return get<BookstoreChapterTocItem[]>(`/bookstore/books/${bookId}/chapters`)
}

export async function getBookstoreChaptersCached(bookId: number): Promise<BookstoreChapterTocItem[]> {
  const key = `toc:${bookId}`
  const hit = bookstoreCacheGet<BookstoreChapterTocItem[]>(key)
  if (hit) {
    bookstoreCacheStaleRefresh(key, BOOKSTORE_CACHE_TTL.toc, hit, () => getBookstoreChapters(bookId))
    return hit
  }
  const data = await getBookstoreChapters(bookId)
  bookstoreCacheSet(key, data, BOOKSTORE_CACHE_TTL.toc)
  return data
}

export interface BookstoreChapterRead {
  bookId: number
  chapterNo: number
  title: string
  contentHtml: string
  prevChapterNo: number | null
  nextChapterNo: number | null
  totalChapters: number
}

export function getBookstoreChapterRead(bookId: number, chapterNo: number) {
  return get<BookstoreChapterRead>(`/bookstore/books/${bookId}/read/${chapterNo}`)
}

export async function getBookstoreChapterReadCached(
  bookId: number,
  chapterNo: number,
  onFresh?: (d: BookstoreChapterRead) => void
): Promise<BookstoreChapterRead> {
  const key = `read:${bookId}:${chapterNo}`
  const hit = bookstoreCacheGet<BookstoreChapterRead>(key)
  if (hit) {
    bookstoreCacheStaleRefresh(key, BOOKSTORE_CACHE_TTL.chapter, hit, () => getBookstoreChapterRead(bookId, chapterNo), onFresh)
    return hit
  }
  const data = await getBookstoreChapterRead(bookId, chapterNo)
  bookstoreCacheSet(key, data, BOOKSTORE_CACHE_TTL.chapter)
  return data
}

/** 文档 getBookSources：已配置书源的书目列表，id 即 sourceId */
export interface BookstoreLiveSourceItem {
  id: number
  bookSourceName?: string
  name?: string
  /** 书源基准 URL，用于补全相对 url（后端字段 baseUrl） */
  baseUrl?: string
}

export function getBookstoreLiveSources() {
  return get<BookstoreLiveSourceItem[]>('/bookstore/sources')
}

export interface BookstoreLiveChapterLink {
  title: string
  url: string
  intro?: string
}

export interface BookstoreLiveBookPayload {
  id?: number
  title?: string
  author?: string
  cover?: string
  description?: string
  category?: string
  rating?: number
  wordCount?: number
  extraInfo?: Record<string, unknown>
}

export interface BookstoreLiveBookApi {
  book: BookstoreLiveBookPayload
  chapters: BookstoreLiveChapterLink[]
}

export function getBookstoreLiveBook(sourceId: number, url?: string) {
  return get<BookstoreLiveBookApi>('/bookstore/book', {
    params: { sourceId, url: url || undefined }
  })
}

export interface BookstoreLiveChapterNavPointer {
  url?: string
}

export interface BookstoreLiveChapterApi {
  title: string
  contentHtml: string
  navigation: {
    prevChapter?: BookstoreLiveChapterNavPointer | null
    nextChapter?: BookstoreLiveChapterNavPointer | null
  }
}

export function getBookstoreLiveChapter(sourceId: number, chapterUrl: string) {
  return get<BookstoreLiveChapterApi>('/bookstore/chapter', {
    params: { sourceId, url: chapterUrl }
  })
}

export async function getBookstoreLiveBookCached(sourceId: number, url?: string): Promise<BookstoreLiveBookApi> {
  const key = `livebook:${sourceId}:${url ?? ''}`
  const hit = bookstoreCacheGet<BookstoreLiveBookApi>(key)
  if (hit) {
    bookstoreCacheStaleRefresh(key, BOOKSTORE_CACHE_TTL.liveBook, hit, () => getBookstoreLiveBook(sourceId, url))
    return hit
  }
  const data = await getBookstoreLiveBook(sourceId, url)
  bookstoreCacheSet(key, data, BOOKSTORE_CACHE_TTL.liveBook)
  return data
}

export async function getBookstoreLiveChapterCached(
  sourceId: number,
  chapterUrl: string,
  onFresh?: (d: BookstoreLiveChapterApi) => void
): Promise<BookstoreLiveChapterApi> {
  const key = `livech:${sourceId}:${chapterUrl}`
  const hit = bookstoreCacheGet<BookstoreLiveChapterApi>(key)
  if (hit) {
    bookstoreCacheStaleRefresh(key, BOOKSTORE_CACHE_TTL.liveChapter, hit, () => getBookstoreLiveChapter(sourceId, chapterUrl), onFresh)
    return hit
  }
  const data = await getBookstoreLiveChapter(sourceId, chapterUrl)
  bookstoreCacheSet(key, data, BOOKSTORE_CACHE_TTL.liveChapter)
  return data
}

/** 运营端 */
export interface BookstoreConfigPayload {
  enabled?: boolean
  siteTitle?: string
}

export function getAdminBookstoreConfig() {
  return get<{
    enabled: boolean
    siteTitle: string
  }>('/admin/bookstore/config')
}

export function saveAdminBookstoreConfig(data: BookstoreConfigPayload) {
  return put<void>('/admin/bookstore/config', data)
}

export interface BookstoreBookAdmin {
  id?: number
  title: string
  author?: string
  coverUrl?: string
  intro?: string
  categoryId?: number | null
  isVip: number
  rating: number
  wordCount: number
  readCount: number
  sortOrder: number
  status: number
  tags?: string
  /** 书源 URL（对应 /api/bookstore/book?url=） */
  sourceUrl?: string | null
  /** 已同步章节数（只读） */
  chapterCount?: number
}

export function listAdminBookstoreBooks(params: { keyword?: string; page?: number; size?: number }) {
  return get<PageVO<BookstoreBookAdmin>>('/admin/bookstore/books/list', { params })
}

export function createAdminBookstoreBook(data: BookstoreBookAdmin) {
  return post<BookstoreBookAdmin>('/admin/bookstore/books', data)
}

export function updateAdminBookstoreBook(id: number, data: BookstoreBookAdmin) {
  return put<BookstoreBookAdmin>(`/admin/bookstore/books/${id}`, data)
}

export function deleteAdminBookstoreBook(id: number) {
  return del<void>(`/admin/bookstore/books/${id}`)
}

/** Legado 书源集合（导入到 bookstore_book_source，公开 /bookstore/sources 优先展示） */
export interface BookstoreLegadoImportResult {
  inserted: number
  updated: number
  skipped: number
  errors?: string[]
}

export interface BookstoreLegadoSourceAdmin {
  id: number
  bookSourceName?: string
  bookSourceUrl?: string
  bookSourceGroup?: string
  enabled?: number
  hasRuleSearch?: boolean
  hasRuleToc?: boolean
  hasRuleContent?: boolean
  commentSnippet?: string
}

export function importAdminLegadoSourcesFromUrl(url: string) {
  return post<BookstoreLegadoImportResult>('/admin/bookstore/legado-sources/import-url', { url })
}

export function importAdminLegadoSourcesFromJson(rawJson: string) {
  return post<BookstoreLegadoImportResult>('/admin/bookstore/legado-sources/import-json', rawJson, {
    headers: { 'Content-Type': 'application/json' }
  })
}

export function listAdminLegadoSources(params: { keyword?: string; page?: number; size?: number }) {
  return get<PageVO<BookstoreLegadoSourceAdmin>>('/admin/bookstore/legado-sources/list', { params })
}

export function deleteAdminLegadoSource(id: number) {
  return del<void>(`/admin/bookstore/legado-sources/${id}`)
}

export function setAdminLegadoSourceEnabled(id: number, enabled: boolean) {
  return put<void>(`/admin/bookstore/legado-sources/${id}/enabled`, undefined, {
    params: { enabled }
  })
}

