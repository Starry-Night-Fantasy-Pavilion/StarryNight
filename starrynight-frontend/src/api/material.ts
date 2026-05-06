import { del, get, post, put } from '@/utils/request'
import type { PageVO, ResponseVO } from '@/types/api'

export interface MaterialItem {
  id?: number
  title: string
  type: string
  subtype?: string
  description?: string
  content?: string
  tags?: string[]
  usageCount?: number
  favoriteCount?: number
  viewCount?: number
  source?: string
  sourceUrl?: string
  author?: string
  createTime?: string
  updateTime?: string
  userId?: number
}

export interface MaterialCreateDTO {
  title: string
  type: string
  subtype?: string
  description?: string
  content?: string
  tags?: string[]
  source?: string
  sourceUrl?: string
  author?: string
}

export interface MaterialUpdateDTO {
  title?: string
  type?: string
  subtype?: string
  description?: string
  content?: string
  tags?: string[]
  source?: string
  sourceUrl?: string
  author?: string
}

export interface MaterialCategory {
  id: string
  name: string
  type: string
  icon?: string
  count: number
  children?: MaterialCategory[]
}

export interface MaterialTag {
  id: number
  name: string
  count: number
  category?: string
}

export interface MaterialBatchOperation {
  ids: number[]
  operation: 'delete' | 'move' | 'tag' | 'merge'
  params?: {
    targetCategory?: string
    tags?: string[]
    mergeTargetId?: number
  }
}

export interface MaterialImportResult {
  success: number
  failed: number
  errors: Array<{
    row: number
    error: string
  }>
}

export function listMaterials(params: {
  keyword?: string
  type?: string
  subtype?: string
  tags?: string[]
  favorite?: boolean
  page?: number
  size?: number
}) {
  return get<ResponseVO<PageVO<MaterialItem>>>('/materials/list', { params })
}

export function getMaterial(id: number) {
  return get<ResponseVO<MaterialItem>>(`/materials/${id}`)
}

export function createMaterial(data: MaterialCreateDTO) {
  return post<ResponseVO<MaterialItem>>('/materials', data)
}

export function updateMaterial(id: number, data: MaterialUpdateDTO) {
  return put<ResponseVO<MaterialItem>>(`/materials/${id}`, data)
}

export function deleteMaterial(id: number) {
  return del<ResponseVO<void>>(`/materials/${id}`)
}

export function batchDeleteMaterials(ids: number[]) {
  return post<ResponseVO<{ deleted: number }>>('/materials/batch-delete', { ids })
}

export function batchUpdateMaterials(ids: number[], data: Partial<MaterialUpdateDTO>) {
  return put<ResponseVO<{ updated: number }>>('/materials/batch-update', { ids, data })
}

export function recordMaterialUsage(id: number) {
  return post<ResponseVO<void>>(`/materials/${id}/usage`, {})
}

export function recommendMaterials(params: {
  novelId?: number
  context?: string
  type?: string
  limit?: number
}) {
  return get<ResponseVO<MaterialItem[]>>('/materials/recommend', { params })
}

export function getMaterialCategories() {
  return get<ResponseVO<MaterialCategory[]>>('/materials/categories')
}

export function createMaterialCategory(data: Omit<MaterialCategory, 'id' | 'count' | 'children'>) {
  return post<ResponseVO<MaterialCategory>>('/materials/categories', data)
}

export function updateMaterialCategory(id: string, data: Partial<MaterialCategory>) {
  return put<ResponseVO<MaterialCategory>>(`/materials/categories/${id}`, data)
}

export function deleteMaterialCategory(id: string) {
  return del<ResponseVO<void>>(`/materials/categories/${id}`)
}

export function getMaterialTags(params?: { type?: string; category?: string }) {
  return get<ResponseVO<MaterialTag[]>>('/materials/tags', { params })
}

export function mergeMaterials(sourceId: number, targetId: number) {
  return post<ResponseVO<MaterialItem>>('/materials/merge', { sourceId, targetId })
}

export function importMaterials(data: {
  format: 'json' | 'csv' | 'markdown'
  content: string
  category?: string
}) {
  return post<ResponseVO<MaterialImportResult>>('/materials/import', data)
}

export function exportMaterials(params: {
  ids?: number[]
  type?: string
  format: 'json' | 'csv' | 'markdown'
}) {
  return get<ResponseVO<string>>('/materials/export', { params })
}

export function toggleFavorite(id: number) {
  return post<ResponseVO<{ favorite: boolean }>>(`/materials/${id}/favorite`, {})
}

export function getRelatedMaterials(id: number, limit?: number) {
  return get<ResponseVO<MaterialItem[]>>(`/materials/${id}/related`, { params: { limit } })
}

export function searchMaterials(params: {
  query: string
  type?: string
  tags?: string[]
  page?: number
  size?: number
}) {
  return get<ResponseVO<PageVO<MaterialItem>>>('/materials/search', { params })
}
