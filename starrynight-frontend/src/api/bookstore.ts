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

/** 运营端 */
export interface BookstoreConfigPayload {
  enabled?: boolean
  siteTitle?: string
  bannersJson?: string
  sidebarReadersJson?: string
  latestUpdatesJson?: string
}

export function getAdminBookstoreConfig() {
  return get<{
    enabled: boolean
    siteTitle: string
    bannersJson: string
    sidebarReadersJson: string
    latestUpdatesJson: string
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
  /** 书源详情或目录页 URL，供外部解析引擎拉取 */
  sourceUrl?: string | null
  /** 书源规则 JSON（须为合法 JSON 字符串） */
  sourceJson?: string | null
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

export interface BookstoreChapterAdminRow {
  id: number
  chapterNo: number
  title: string
  wordCount: number
}

export interface BookstoreChapterAdminDetail {
  id: number
  bookId: number
  chapterNo: number
  title: string
  content: string
  wordCount: number
}

export interface BookstoreChapterMutatePayload {
  chapterNo: number
  title: string
  content?: string
}

export function listAdminBookstoreChapters(bookId: number) {
  return get<BookstoreChapterAdminRow[]>(`/admin/bookstore/books/${bookId}/chapters`)
}

export function getAdminBookstoreChapter(bookId: number, chapterId: number) {
  return get<BookstoreChapterAdminDetail>(`/admin/bookstore/books/${bookId}/chapters/${chapterId}`)
}

export function createAdminBookstoreChapter(bookId: number, data: BookstoreChapterMutatePayload) {
  return post<BookstoreChapterAdminRow>(`/admin/bookstore/books/${bookId}/chapters`, data)
}

export function updateAdminBookstoreChapter(
  bookId: number,
  chapterId: number,
  data: BookstoreChapterMutatePayload
) {
  return put<void>(`/admin/bookstore/books/${bookId}/chapters/${chapterId}`, data)
}

export function deleteAdminBookstoreChapter(bookId: number, chapterId: number) {
  return del<void>(`/admin/bookstore/books/${bookId}/chapters/${chapterId}`)
}
