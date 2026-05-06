import { post } from '@/utils/request'
import type {
  ChapterDraftConnectionCheckPayload,
  ChapterDraftConnectionIssue,
  ChapterDraftGeneratePayload,
  ChapterDraftItem,
  ChapterWorkshopIntent,
  ChapterWorkshopResult,
  ContentExpandRequest,
  ContentExpandResult,
  ContentVersionItem,
  ContinueWritingRequest,
  ContinueWritingResult,
  ConsistencyReport,
  PlotSuggestionResult
} from '@/types/api'
import type { NovelVolume } from '@/types/api'
import { get } from '@/utils/request'

export function chapterWorkshopPreview(data: ChapterWorkshopIntent) {
  return post<ChapterWorkshopResult>('/novels/workshop/chapter-preview', data)
}

export function chapterWorkshopGenerate(data: ChapterWorkshopIntent) {
  return post<ChapterWorkshopResult>('/novels/workshop/chapter-generate', data)
}

export function generateChapterDrafts(data: ChapterDraftGeneratePayload) {
  return post<ChapterDraftItem[]>('/novels/chapter-drafts/generate', data)
}

// 文档标准路由（兼容入口）
export function generateChapterDraftsByAi(data: ChapterDraftGeneratePayload) {
  return post<ChapterDraftItem[]>('/ai/generate-chapter-draft', data)
}

export function checkChapterDraftConnections(data: ChapterDraftConnectionCheckPayload) {
  return post<ChapterDraftConnectionIssue[]>('/novels/chapter-drafts/check-connections', data)
}

export function previewContentExpand(data: ContentExpandRequest) {
  return post<ContentExpandResult>('/novels/content-expand/preview', data)
}

// 文档标准路由（兼容入口）
export function expandContentByAi(data: ContentExpandRequest) {
  return post<ContentExpandResult>('/ai/expand-content', data)
}

export function saveContentExpandVersion(data: { chapterOutlineId: number; content: string }) {
  return post<ContentVersionItem>('/novels/content-expand/versions/save', data)
}

export function saveContentDraftVersion(data: { chapterOutlineId: number; content: string }) {
  return post<ContentVersionItem>('/novels/content-expand/versions/save-draft', data)
}

export function listContentExpandVersions(chapterOutlineId: number) {
  return get<ContentVersionItem[]>('/novels/content-expand/versions', { params: { chapterOutlineId } })
}

export function listContentTimelineVersions(chapterOutlineId: number) {
  return get<ContentVersionItem[]>('/novels/content-expand/versions/timeline', { params: { chapterOutlineId } })
}

export function rollbackContentExpandVersion(versionId: number) {
  return post<ContentVersionItem>('/novels/content-expand/versions/rollback', { versionId })
}

// 章节版本标准接口（对齐 SPEC.md：章节版本历史/回滚）
export function listChapterVersionsTimeline(novelId: number, chapterId: number) {
  return get<ContentVersionItem[]>(`/novels/${novelId}/chapters/${chapterId}/version`)
}

export function rollbackChapterVersion(novelId: number, chapterId: number, versionId: number) {
  return post<ContentVersionItem>(`/novels/${novelId}/chapters/${chapterId}/rollback/${versionId}`)
}

// 智能续写（对齐 SPEC.md：/api/ai/continue-writing）
export function continueWriting(data: ContinueWritingRequest) {
  return post<ContinueWritingResult>('/ai/continue-writing', data)
}

export function checkConsistencyByAi(data: {
  novelId: number
  generatedText: string
  coreEvent?: string
  sceneLocation?: string
  atmosphere?: string
  emotionalTone?: string
  generationMode?: string
  presentCharacterIds?: string[]
  relatedOutlineNodes?: string[]
}) {
  return post<ConsistencyReport>('/ai/check-consistency', data)
}

export function getPlotSuggestionByAi(data: {
  novelId: number
  currentContent: string
  coreEvent?: string
  sceneLocation?: string
  emotionalTone?: string
}) {
  return post<PlotSuggestionResult>('/ai/plot-suggestion', data)
}

export function generateVolumesByAi(data: { novelId: number; volumeCount?: number }) {
  return post<NovelVolume[]>('/ai/generate-volumes', data)
}

export function generateOutlineByAi(data: {
  novelId: number
  coreIdea?: string
  genre?: string
  style?: string
}) {
  return post<{
    novelId: number
    type: string
    title: string
    content?: string
    sortOrder?: number
    version?: number
  }>('/ai/generate-outline', data)
}

