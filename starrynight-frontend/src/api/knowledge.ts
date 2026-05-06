import { del, get, post, put } from '@/utils/request'
import type { PageVO, ResponseVO } from '@/types/api'

export interface KnowledgeChunk {
  id: number
  knowledgeId: number
  content: string
  metadata?: Record<string, any>
  createTime?: string
}

export interface KnowledgeItem {
  id?: number
  title: string
  type?: string
  description?: string
  status?: string
  fileUrl?: string
  fileName?: string
  chunkCount?: number
  createTime?: string
  updateTime?: string
}

export interface KnowledgeCapacity {
  totalCount: number
  usedCount: number
  chunkCount: number
}

export function listKnowledge(params: {
  keyword?: string
  type?: string
  status?: string
  page?: number
  size?: number
}) {
  return get<ResponseVO<PageVO<KnowledgeItem>>>('/knowledge/list', { params })
}

export function getKnowledge(id: number) {
  return get<ResponseVO<KnowledgeItem>>(`/knowledge/${id}`)
}

export function createKnowledge(data: Partial<KnowledgeItem>) {
  return post<ResponseVO<KnowledgeItem>>('/knowledge', data)
}

export function updateKnowledge(id: number, data: Partial<KnowledgeItem>) {
  return put<ResponseVO<KnowledgeItem>>(`/knowledge/${id}`, data)
}

export function deleteKnowledge(id: number) {
  return del<ResponseVO<void>>(`/knowledge/${id}`)
}

export function getKnowledgeCapacity() {
  return get<ResponseVO<KnowledgeCapacity>>('/knowledge/capacity')
}

export function uploadDocument(id: number, file: File) {
  const formData = new FormData()
  formData.append('file', file)
  return post<ResponseVO<KnowledgeItem>>(`/knowledge/${id}/upload`, formData)
}

export function listChunks(knowledgeId: number, params?: { page?: number; size?: number }) {
  return get<ResponseVO<PageVO<KnowledgeChunk>>>(`/knowledge/${knowledgeId}/chunks`, { params })
}

export function searchChunks(knowledgeId: number, keyword: string, params?: { page?: number; size?: number }) {
  return get<ResponseVO<PageVO<KnowledgeChunk>>>(`/knowledge/${knowledgeId}/search`, { params: { keyword, ...params } })
}

export function searchAllChunks(keyword: string, params?: { page?: number; size?: number }) {
  return get<ResponseVO<PageVO<KnowledgeChunk>>>('/knowledge/search', { params: { keyword, ...params } })
}

export interface KnowledgeSearchParams {
  libraryId?: number
  query: string
  mode?: 'keyword' | 'semantic' | 'hybrid'
  topK?: number
}

export function searchKnowledge(params: KnowledgeSearchParams): Promise<{ data: KnowledgeChunk[] }> {
  return get('/knowledge/search', { params })
}

export interface DocumentItem {
  id: number
  knowledgeId: number
  name: string
  type: string
  status: string
  chunkCount: number
  createTime: string
}

export function listDocuments(knowledgeId: number): Promise<{ data: DocumentItem[] }> {
  return get(`/knowledge/${knowledgeId}/documents`)
}

export function deleteDocument(knowledgeId: number, documentId: number): Promise<ResponseVO<void>> {
  return del(`/knowledge/${knowledgeId}/documents/${documentId}`)
}

export function reparseDocument(knowledgeId: number, documentId: number): Promise<ResponseVO<void>> {
  return post(`/knowledge/${knowledgeId}/documents/${documentId}/reparse`, {})
}

export function listDocumentChunks(knowledgeId: number, documentId: number, params?: { page?: number; size?: number }): Promise<{ data: KnowledgeChunk[] }> {
  return get(`/knowledge/${knowledgeId}/documents/${documentId}/chunks`, { params })
}

export function deleteChunk(knowledgeId: number, chunkId: number): Promise<ResponseVO<void>> {
  return del(`/knowledge/${knowledgeId}/chunks/${chunkId}`)
}

export function updateChunk(knowledgeId: number, chunkId: number, content: string): Promise<ResponseVO<KnowledgeChunk>> {
  return put(`/knowledge/${knowledgeId}/chunks/${chunkId}`, { content })
}

export interface KnowledgeGraph {
  nodes: Array<{
    id: string
    label: string
    type: string
    properties: Record<string, any>
  }>
  edges: Array<{
    source: string
    target: string
    label: string
    weight?: number
  }>
}

export interface KnowledgeLink {
  id: number
  sourceId: number
  targetId: number
  linkType: string
  description?: string
  strength: number
  createTime: string
}

export interface KnowledgeShare {
  id: number
  knowledgeId: number
  sharedWithUserId?: number
  sharedWithTeamId?: number
  permission: 'read' | 'write' | 'admin'
  expiresAt?: string
  createTime: string
}

export interface KnowledgeTag {
  id: number
  name: string
  color?: string
  count: number
}

export function getKnowledgeGraph(knowledgeId: number): Promise<ResponseVO<KnowledgeGraph>> {
  return get(`/knowledge/${knowledgeId}/graph`)
}

export function getRelatedKnowledge(knowledgeId: number, limit?: number): Promise<ResponseVO<KnowledgeItem[]>> {
  return get(`/knowledge/${knowledgeId}/related`, { params: { limit } })
}

export function createKnowledgeLink(
  sourceId: number,
  targetId: number,
  data: { linkType: string; description?: string; strength?: number }
): Promise<ResponseVO<KnowledgeLink>> {
  return post(`/knowledge/${sourceId}/links`, { targetId, ...data })
}

export function deleteKnowledgeLink(linkId: number): Promise<ResponseVO<void>> {
  return del(`/knowledge/links/${linkId}`)
}

export function getKnowledgeLinks(knowledgeId: number): Promise<ResponseVO<KnowledgeLink[]>> {
  return get(`/knowledge/${knowledgeId}/links`)
}

export function shareKnowledge(
  knowledgeId: number,
  data: { sharedWithUserId?: number; sharedWithTeamId?: number; permission: 'read' | 'write' | 'admin'; expiresAt?: string }
): Promise<ResponseVO<KnowledgeShare>> {
  return post(`/knowledge/${knowledgeId}/share`, data)
}

export function revokeKnowledgeShare(shareId: number): Promise<ResponseVO<void>> {
  return del(`/knowledge/share/${shareId}`)
}

export function getSharedWithMe(): Promise<ResponseVO<KnowledgeShare[]>> {
  return get('/knowledge/shared-with-me')
}

export function getKnowledgeTags(knowledgeId?: number): Promise<ResponseVO<KnowledgeTag[]>> {
  return get('/knowledge/tags', { params: { knowledgeId } })
}

export function addKnowledgeTags(knowledgeId: number, tags: string[]): Promise<ResponseVO<void>> {
  return post(`/knowledge/${knowledgeId}/tags`, { tags })
}

export function removeKnowledgeTag(knowledgeId: number, tagId: number): Promise<ResponseVO<void>> {
  return del(`/knowledge/${knowledgeId}/tags/${tagId}`)
}

export interface HybridSearchParams {
  query: string
  knowledgeIds?: number[]
  topK?: number
  rerank?: boolean
  filters?: Record<string, any>
}

export interface HybridSearchResult {
  chunk: KnowledgeChunk
  score: number
  source: 'vector' | 'keyword' | 'rerank'
  highlights?: string[]
}

export function hybridSearchKnowledge(params: HybridSearchParams): Promise<ResponseVO<HybridSearchResult[]>> {
  return post('/knowledge/hybrid-search', params)
}

export function semanticSearchKnowledge(
  query: string,
  knowledgeId?: number,
  topK?: number
): Promise<ResponseVO<KnowledgeChunk[]>> {
  return post('/knowledge/semantic-search', { query, knowledgeId, topK })
}

export function batchGetKnowledge(ids: number[]): Promise<ResponseVO<KnowledgeItem[]>> {
  return post('/knowledge/batch-get', { ids })
}

export function duplicateKnowledge(knowledgeId: number, newTitle?: string): Promise<ResponseVO<KnowledgeItem>> {
  return post(`/knowledge/${knowledgeId}/duplicate`, { newTitle })
}

export function getKnowledgeStats(knowledgeId: number): Promise<ResponseVO<{
  viewCount: number
  searchCount: number
 引用Count: number
  lastAccessedAt: string
}>> {
  return get(`/knowledge/${knowledgeId}/stats`)
}

export function updateChunkMetadata(knowledgeId: number, chunkId: number, metadata: Record<string, any>): Promise<ResponseVO<KnowledgeChunk>> {
  return put(`/knowledge/${knowledgeId}/chunks/${chunkId}/metadata`, { metadata })
}
