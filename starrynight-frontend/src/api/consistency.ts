import request from '@/utils/request'

export interface ConsistencyIssue {
  category: string
  severity: string
  message: string
  suggestion: string
  characterId?: number
  characterName?: string
}

export interface ConsistencyCheckResult {
  passed: boolean
  issues: ConsistencyIssue[]
}

export interface ForeshadowingItem {
  content: string
  type: string
  hintLevel: number
  hintKeyword: string
  position: number
}

export interface ForeshadowingWarning {
  foreshadowingId: number
  content: string
  warningType: string
  message: string
}

export interface ForeshadowingAnalysisResult {
  detectedForeshadowings: ForeshadowingItem[]
  pendingCount: number
  warnings: ForeshadowingWarning[]
}

export interface ForeshadowingRecord {
  id: number
  userId: number
  novelId: number
  chapterId: number
  foreshadowingType: string
  foreshadowingContent: string
  hintLevel: number
  resolutionStatus: string
  resolutionChapterId?: number
  createTime: string
}

export interface RhythmAnalysisResult {
  chapterNo: number
  wordCount: number
  anticipationScore: number
  tensionScore: number
  warmthScore: number
  sadnessScore: number
  conflictCount: number
  conflictDensity: number
  retentionScore: number
  averageTension: number
  averageWarmth: number
  tensionCurve: number[]
  warmthCurve: number[]
  suggestions: string[]
}

export interface RhythmAnalysis {
  id: number
  novelId: number
  chapterId: number
  chapterNo: number
  analysisType: string
  anticipationScore: number
  tensionScore: number
  warmthScore: number
  sadnessScore: number
  conflictCount: number
  conflictDensity: number
  retentionScore: number
  emotionCurve: string
  suggestions: string
  wordCount: number
  createTime: string
}

export interface CharacterConsistencyResult {
  issues: ConsistencyIssue[]
}

export function checkContent(
  userId: number,
  novelId: number,
  contentType: string,
  content: string,
  chapterId?: number
) {
  return request.post<ConsistencyCheckResult>('/api/consistency/check', null, {
    params: { userId, novelId, chapterId, contentType, content }
  })
}

export function analyzeForeshadowing(
  userId: number,
  novelId: number,
  content: string,
  chapterId?: number
) {
  return request.post<ForeshadowingAnalysisResult>('/api/consistency/foreshadowing/analyze', null, {
    params: { userId, novelId, chapterId, content }
  })
}

export function getPendingForeshadowings(novelId: number) {
  return request.get<ForeshadowingRecord[]>('/api/consistency/foreshadowing/pending', {
    params: { novelId }
  })
}

export function resolveForeshadowing(id: number, resolutionChapterId?: number, quality = 3) {
  return request.post('/api/consistency/foreshadowing/' + id + '/resolve', null, {
    params: { resolutionChapterId, quality }
  })
}

export function abandonForeshadowing(id: number) {
  return request.post('/api/consistency/foreshadowing/' + id + '/abandon')
}

export function analyzeRhythm(
  userId: number,
  novelId: number,
  content: string,
  chapterId?: number
) {
  return request.post<RhythmAnalysisResult>('/api/consistency/rhythm/analyze', null, {
    params: { userId, novelId, chapterId, content }
  })
}

export function getRhythmHistory(novelId: number) {
  return request.get<RhythmAnalysis[]>('/api/consistency/rhythm/history', {
    params: { novelId }
  })
}

export function checkCharacterConsistency(
  userId: number,
  novelId: number,
  content: string,
  chapterId?: number
) {
  return request.post<CharacterConsistencyResult>('/api/consistency/character/check', null, {
    params: { userId, novelId, chapterId, content }
  })
}

export interface RhythmDashboardData {
  emotionCurve: RhythmAnalysis[]
  conflictDensity: number[]
  chapterAttraction: Array<{
    chapterNo: number
    stars: number
    churnRate: string
    suggestion: string
  }>
  suggestions: Array<{
    text: string
    chapters?: number[]
    type: string
  }>
  emotionSuggestion: string
  conflictSuggestion: string
}

export function getRhythmDashboard(novelId: number) {
  return request.get<ResponseVO<RhythmDashboardData>>('/api/consistency/rhythm/dashboard', {
    params: { novelId }
  })
}

export function getChapterAttractionPrediction(novelId: number, chapterId: number) {
  return request.get<ResponseVO<{
    chapterNo: number
    stars: number
    churnRate: string
    retentionScore: number
    suggestions: string[]
  }>>('/api/consistency/rhythm/attraction', {
    params: { novelId, chapterId }
  })
}

export function getOverallRhythmScore(novelId: number) {
  return request.get<ResponseVO<{
    overallScore: number
    emotionBalance: number
    conflictBalance: number
    pacingQuality: string
    recommendations: string[]
  }>>('/api/consistency/rhythm/overall-score', {
    params: { novelId }
  })
}

export interface ForesightDetail extends ForeshadowingRecord {
  detail?: string
  quote?: string
  targetChapter?: number
  status: 'pending' | 'expiring' | 'recovered'
}

export interface ForesightCreateDTO {
  novelId: number
  chapterId: number
  sourceChapter: number
  foreshadowingType: string
  foreshadowingContent: string
  hintLevel?: number
  targetChapter?: number
}

export interface ForesightUpdateDTO {
  description?: string
  detail?: string
  targetChapter?: number
  status?: string
}

export function getForesightList(novelId: number) {
  return request.get<ResponseVO<ForesightDetail[]>>('/api/consistency/foresight/list', {
    params: { novelId }
  })
}

export function getForesightDetail(id: number) {
  return request.get<ResponseVO<ForesightDetail>>(`/api/consistency/foresight/${id}`)
}

export function createForesight(data: ForesightCreateDTO) {
  return request.post<ResponseVO<ForesightDetail>>('/api/consistency/foresight', data)
}

export function updateForesight(id: number, data: ForesightUpdateDTO) {
  return request.put<ResponseVO<ForesightDetail>>(`/api/consistency/foresight/${id}`, data)
}

export function deleteForesight(id: number) {
  return request.delete<ResponseVO<void>>(`/api/consistency/foresight/${id}`)
}

export function detectForeshadowing(novelId: number, chapterId: number, content: string) {
  return request.post<ResponseVO<ForeshadowingAnalysisResult>>('/api/consistency/foresight/detect', {
    novelId,
    chapterId,
    content
  })
}

export function generateForesightRecovery(id: number) {
  return request.post<ResponseVO<{
    sceneContent: string
    suggestedChapter: number
    confidence: number
  }>>(`/api/consistency/foresight/${id}/generate-recovery`, {})
}

export function getForesightSuggestions(novelId: number) {
  return request.get<ResponseVO<Array<{
    text: string
    chapters?: number[]
    type: 'recovery' | 'new'
  }>>>(`/api/consistency/foresight/suggestions`, {
    params: { novelId }
  })
}
