import { del, get, post, put } from '@/utils/request'
import type { PageVO, ResponseVO } from '@/types/api'

export interface PromptTemplate {
  id?: number
  title: string
  content: string
  category: string
  variables?: PromptVariable[]
  description?: string
  tags?: string[]
  usageCount?: number
  favoriteCount?: number
  viewCount?: number
  isPublic?: boolean
  authorId?: number
  authorName?: string
  createTime?: string
  updateTime?: string
}

export interface PromptVariable {
  name: string
  description: string
  required: boolean
  defaultValue?: string
  type?: 'string' | 'number' | 'boolean' | 'select'
  options?: string[]
}

export interface PromptCreateDTO {
  title: string
  content: string
  category: string
  variables?: PromptVariable[]
  description?: string
  tags?: string[]
  isPublic?: boolean
}

export interface PromptUpdateDTO {
  title?: string
  content?: string
  category?: string
  variables?: PromptVariable[]
  description?: string
  tags?: string[]
  isPublic?: boolean
}

export interface PromptCategory {
  id: string
  name: string
  icon?: string
  count: number
  children?: PromptCategory[]
}

export interface PromptVersion {
  id: number
  promptId: number
  version: number
  content: string
  variables?: PromptVariable[]
  changeNote?: string
  createdAt: string
}

export interface PromptShare {
  id: number
  promptId: number
  sharedWithUserId?: number
  sharedWithTeamId?: number
  permission: 'read' | 'write' | 'admin'
  expiresAt?: string
  createTime: string
}

export interface PromptAnalytics {
  promptId: number
  totalUsage: number
  successRate: number
  avgLatency: number
  popularVariables: Array<{
    name: string
    usageCount: number
  }>
  usageTrend: Array<{
    date: string
    count: number
  }>
}

export function listPrompts(params: {
  keyword?: string
  category?: string
  tags?: string[]
  favorite?: boolean
  page?: number
  size?: number
}) {
  return get<ResponseVO<PageVO<PromptTemplate>>>('/prompts/list', { params })
}

export function getPrompt(id: number) {
  return get<ResponseVO<PromptTemplate>>(`/prompts/${id}`)
}

export function createPrompt(data: PromptCreateDTO) {
  return post<ResponseVO<PromptTemplate>>('/prompts', data)
}

export function updatePrompt(id: number, data: PromptUpdateDTO) {
  return put<ResponseVO<PromptTemplate>>(`/prompts/${id}`, data)
}

export function deletePrompt(id: number) {
  return del<ResponseVO<void>>(`/prompts/${id}`)
}

export function batchDeletePrompts(ids: number[]) {
  return post<ResponseVO<{ deleted: number }>>('/prompts/batch-delete', { ids })
}

export function batchUpdatePrompts(ids: number[], data: Partial<PromptUpdateDTO>) {
  return put<ResponseVO<{ updated: number }>>('/prompts/batch-update', { ids, data })
}

export function listPromptCategories() {
  return get<ResponseVO<PromptCategory[]>>('/prompts/categories')
}

export function createPromptCategory(data: Omit<PromptCategory, 'id' | 'count' | 'children'>) {
  return post<ResponseVO<PromptCategory>>('/prompts/categories', data)
}

export function updatePromptCategory(id: string, data: Partial<PromptCategory>) {
  return put<ResponseVO<PromptCategory>>(`/prompts/categories/${id}`, data)
}

export function deletePromptCategory(id: string) {
  return del<ResponseVO<void>>(`/prompts/categories/${id}`)
}

export function applyPrompt(id: number, variables: Record<string, string>) {
  return post<ResponseVO<string>>(`/prompts/${id}/apply`, variables)
}

export function togglePromptFavorite(id: number) {
  return post<ResponseVO<{ favorite: boolean }>>(`/prompts/${id}/favorite`, {})
}

export function duplicatePrompt(id: number, newTitle?: string) {
  return post<ResponseVO<PromptTemplate>>(`/prompts/${id}/duplicate`, { newTitle })
}

export function getPromptVersions(promptId: number) {
  return get<ResponseVO<PromptVersion[]>>(`/prompts/${promptId}/versions`)
}

export function createPromptVersion(promptId: number, data: {
  content: string
  variables?: PromptVariable[]
  changeNote?: string
}) {
  return post<ResponseVO<PromptVersion>>(`/prompts/${promptId}/versions`, data)
}

export function rollbackPromptVersion(promptId: number, versionId: number) {
  return post<ResponseVO<PromptTemplate>>(`/prompts/${promptId}/versions/${versionId}/rollback`, {})
}

export function getPromptAnalytics(promptId: number) {
  return get<ResponseVO<PromptAnalytics>>(`/prompts/${promptId}/analytics`)
}

export function sharePrompt(
  promptId: number,
  data: {
    sharedWithUserId?: number
    sharedWithTeamId?: number
    permission: 'read' | 'write' | 'admin'
    expiresAt?: string
  }
) {
  return post<ResponseVO<PromptShare>>(`/prompts/${promptId}/share`, data)
}

export function revokePromptShare(shareId: number) {
  return del<ResponseVO<void>>(`/prompts/share/${shareId}`)
}

export function getSharedWithMe() {
  return get<ResponseVO<PromptShare[]>>('/prompts/shared-with-me')
}

export function importPrompts(data: {
  format: 'json' | 'csv' | 'markdown'
  content: string
  category?: string
}) {
  return post<ResponseVO<{
    success: number
    failed: number
    errors: Array<{ row: number; error: string }>
  }>>('/prompts/import', data)
}

export function exportPrompts(params: {
  ids?: number[]
  category?: string
  format: 'json' | 'csv' | 'markdown'
}) {
  return get<ResponseVO<string>>('/prompts/export', { params })
}

export function getPromptTags(params?: { category?: string }) {
  return get<ResponseVO<Array<{ name: string; count: number }>>>('/prompts/tags', { params })
}

export function searchPrompts(params: {
  query: string
  category?: string
  tags?: string[]
  page?: number
  size?: number
}) {
  return get<ResponseVO<PageVO<PromptTemplate>>>('/prompts/search', { params })
}

export function getRelatedPrompts(promptId: number, limit?: number) {
  return get<ResponseVO<PromptTemplate[]>>(`/prompts/${promptId}/related`, { params: { limit } })
}

export function testPrompt(promptId: number, variables: Record<string, string>) {
  return post<ResponseVO<{
    success: boolean
    rendered: string
    output?: string
    error?: string
  }>>(`/prompts/${promptId}/test`, { variables })
}
