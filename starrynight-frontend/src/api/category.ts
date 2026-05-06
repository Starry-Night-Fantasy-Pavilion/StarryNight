import { del, get, post, put } from '@/utils/request'

/** 运营端：作品/书城分类（频道 level1 + 可选题材 level2，接口字段名沿用 level1/2） */
export interface NovelCategoryRow {
  id: number
  parentId?: number | null
  level1Name: string
  level2Name: string
  sort: number
  status: number
  novelCount?: number
  bookCount?: number
}

export interface NovelCategoryMutate {
  level1Name: string
  /** 空字符串表示仅一级分类 */
  level2Name?: string
  sort?: number
  status?: number
}

export function listNovelCategories() {
  return get<NovelCategoryRow[]>('/admin/novel-categories/list')
}

export function createNovelCategory(data: NovelCategoryMutate) {
  return post<NovelCategoryRow>('/admin/novel-categories', data)
}

export function updateNovelCategory(id: number, data: NovelCategoryMutate) {
  return put<NovelCategoryRow>(`/admin/novel-categories/${id}`, data)
}

export function deleteNovelCategory(id: number) {
  return del<void>(`/admin/novel-categories/${id}`)
}

/** 前台：分类树（创作/筛选） */
export function getNovelCategoryTree() {
  return get<{ id: number; name: string; children: { id: number; name: string }[] }[]>('/novel-categories/tree')
}
