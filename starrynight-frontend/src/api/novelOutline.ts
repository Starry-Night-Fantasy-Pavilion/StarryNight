import { del, get, post } from '@/utils/request'
import type { NovelOutlineItem, NovelVolume } from '@/types/api'

export function listNovelOutlines(params: {
  novelId: number
  type: string
  volumeId?: number
  chapterId?: number
}) {
  return get<NovelOutlineItem[]>('/novels/outlines', { params })
}

export function upsertNovelOutline(data: NovelOutlineItem) {
  return post<NovelOutlineItem>('/novels/outlines', data)
}

export function deleteNovelOutline(id: number) {
  return del<void>(`/novels/outlines/${id}`)
}

export function listNovelVolumes(novelId: number) {
  return get<NovelVolume[]>(`/novels/${novelId}/volumes`)
}

export function createNovelVolume(data: {
  novelId: number
  title: string
  description?: string
  volumeOrder: number
}) {
  return post<NovelVolume>('/novels/volumes', data)
}

export interface OutlineVersion {
  id: number
  novelId: number
  outlineId: number
  versionNumber: number
  content: string
  createTime: string
  changeDescription?: string
}

export function listOutlineVersions(outlineId: number): Promise<{ data: OutlineVersion[] }> {
  return get(`/novels/outlines/${outlineId}/versions`)
}

export function getOutlineVersion(outlineId: number, versionId: number): Promise<{ data: OutlineVersion }> {
  return get(`/novels/outlines/${outlineId}/versions/${versionId}`)
}

export function rollbackOutlineVersion(outlineId: number, versionId: number): Promise<{ data: OutlineVersion }> {
  return post(`/novels/outlines/${outlineId}/versions/${versionId}/rollback`, {})
}

export interface Branch {
  id: number
  novelId: number
  name: string
  description?: string
  baseVersionId?: string
  rootCommitId?: string
  headCommitId?: string
  parentBranchId?: number
  status: 'active' | 'merged' | 'archived'
  createdAt: string
  mergedAt?: string
}

export interface Commit {
  id: string
  branchId: number
  parentIds: string[]
  nodeType: string
  nodeId: string
  changeType: 'create' | 'update' | 'delete'
  contentBefore?: string
  contentAfter?: string
  message: string
  author: 'user' | 'ai'
  aiConversationId?: string
  createdAt: string
}

export interface VersionSnapshot {
  id: string
  nodeType: string
  nodeId: string
  content: string
  commitId: string
  createdAt: string
}

export interface Diff {
  nodeId: string
  nodeType: string
  changeType: 'added' | 'modified' | 'deleted'
  contentBefore?: string
  contentAfter: string
}

export interface Conflict {
  nodeId: string
  nodeType: string
  sourceValue: string
  targetValue: string
  resolution?: 'use_source' | 'use_target' | 'manual'
  resolvedValue?: string
}

export interface MergeResult {
  hasConflicts: boolean
  conflicts?: Conflict[]
  requiresManualResolution: boolean
  mergeCommitId?: string
}

export interface BranchCreateDTO {
  novelId: number
  name: string
  description?: string
  baseVersionId?: string
  branchType?: '虐文' | '爽文' | '自定义'
}

export function listBranches(novelId: number) {
  return get<Branch[]>(`/novels/${novelId}/branches`)
}

export function getBranch(branchId: number) {
  return get<Branch>(`/novels/branches/${branchId}`)
}

export function createBranch(data: BranchCreateDTO) {
  return post<Branch>('/novels/branches', data)
}

export function updateBranch(branchId: number, data: Partial<Branch>) {
  return post<Branch>(`/novels/branches/${branchId}`, data)
}

export function deleteBranch(branchId: number) {
  return del<void>(`/novels/branches/${branchId}`)
}

export function archiveBranch(branchId: number) {
  return post<void>(`/novels/branches/${branchId}/archive`, {})
}

export function getBranchCommits(branchId: number) {
  return get<Commit[]>(`/novels/branches/${branchId}/commits`)
}

export function getBranchDiff(branchId: number, baseCommitId?: string) {
  return get<Diff[]>(`/novels/branches/${branchId}/diff`, { params: { baseCommitId } })
}

export function previewMerge(sourceBranchId: number, targetBranchId: number) {
  return get<MergeResult>(`/novels/branches/${sourceBranchId}/merge/preview`, {
    params: { targetBranchId }
  })
}

export function executeMerge(sourceBranchId: number, targetBranchId: number, resolution?: Record<string, string>) {
  return post<MergeResult>(`/novels/branches/${sourceBranchId}/merge`, {
    targetBranchId,
    resolution
  })
}

export function getCommonAncestor(branchAId: number, branchBId: number) {
  return get<Commit>(`/novels/branches/common-ancestor`, {
    params: { branchAId, branchBId }
  })
}

