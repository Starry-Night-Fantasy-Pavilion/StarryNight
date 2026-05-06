import { del, get, post, put } from '@/utils/request'
import type { ResponseVO } from '@/types/api'

export interface SynopsisGenerateRequest {
  bookName: string
  setting?: string
  style?: string
  count?: number
}

export interface SynopsisResult {
  content: string
  style?: string
}

export interface BookNameGenerateRequest {
  genre?: string
  style?: string
  count?: number
  theme?: string
  mood?: string
}

export interface BookNameResult {
  name: string
  description?: string
  genre?: string
  matchScore?: number
}

export interface WorldViewGenerateRequest {
  genre?: string
  type?: string
  count?: number
  theme?: string
}

export interface WorldViewResult {
  content: string
  type?: string
  elements?: string[]
}

export interface ConflictGenerateRequest {
  characters?: string[]
  type?: string
  count?: number
  genre?: string
}

export interface ConflictResult {
  content: string
  type?: string
  participants?: string[]
  intensity?: 'low' | 'medium' | 'high'
}

export interface CharacterQuickGenerateRequest {
  prompt: string
  genre?: string
}

export interface CharacterQuickResult {
  name: string
  gender?: string
  age?: string
  identity?: string
  personality?: string[]
  background?: string
}

export interface GoldenFingerGenerateRequest {
  genre?: string
  powerLevel?: string
  constraint?: string
}

export interface GoldenFingerResult {
  content: string
  name?: string
  effect?: string
  sideEffect?: string
}

export interface TitleGenerateRequest {
  content: string
  count?: number
  style?: 'dramatic' | 'humorous' | 'mysterious' | 'romantic'
}

export interface TitleResult {
  title: string
  subtitle?: string
  style?: string
}

export interface ChapterTitleRequest {
  chapterContent: string
  chapterNumber: number
  genre?: string
}

export interface ChapterTitleResult {
  title: string
  subtitle?: string
  keywords?: string[]
}

export interface DialogueGenerateRequest {
  characterName: string
  characterProfile?: string
  situation?: string
  emotion?: string
  count?: number
}

export interface DialogueResult {
  content: string
  emotion?: string
  gesture?: string
}

export interface DescriptionEnhanceRequest {
  content: string
  type: 'appearance' | 'environment' | 'action' | 'psychology'
  intensity?: 'subtle' | 'moderate' | 'vivid'
}

export interface DescriptionResult {
  original: string
  enhanced: string
  techniques?: string[]
}

export interface PlotHoleDetectRequest {
  content: string
  previousContent?: string
}

export interface PlotHoleResult {
  hasHole: boolean
  issues: Array<{
    type: string
    description: string
    location?: string
    severity: 'high' | 'medium' | 'low'
  }>
  suggestions?: string[]
}

export interface WritingInspirationRequest {
  genre?: string
  theme?: string
  mood?: string
  count?: number
}

export interface WritingInspirationResult {
  title: string
  description: string
  tags?: string[]
}

export interface StyleAnalyzeRequest {
  content: string
  referenceAuthors?: string[]
}

export interface StyleAnalyzeResult {
  tone?: string[]
  rhythm?: string
  vocabulary?: string[]
  techniques?: string[]
  suggestions?: string[]
}

export function generateSynopsis(data: SynopsisGenerateRequest): Promise<ResponseVO<SynopsisResult[]>> {
  return post('/ai/tools/synopsis', data)
}

export function generateBookNames(data: BookNameGenerateRequest): Promise<ResponseVO<BookNameResult[]>> {
  return post('/ai/tools/book-names', data)
}

export function generateWorldView(data: WorldViewGenerateRequest): Promise<ResponseVO<WorldViewResult[]>> {
  return post('/ai/tools/worldview', data)
}

export function generateConflicts(data: ConflictGenerateRequest): Promise<ResponseVO<ConflictResult[]>> {
  return post('/ai/tools/conflicts', data)
}

export function generateCharacterQuick(data: CharacterQuickGenerateRequest): Promise<ResponseVO<CharacterQuickResult>> {
  return post('/ai/tools/character-quick', data)
}

export function generateGoldenFinger(data: GoldenFingerGenerateRequest): Promise<ResponseVO<GoldenFingerResult[]>> {
  return post('/ai/tools/golden-finger', data)
}

export function generateTitles(data: TitleGenerateRequest): Promise<ResponseVO<TitleResult[]>> {
  return post('/ai/tools/titles', data)
}

export function generateChapterTitles(data: ChapterTitleRequest): Promise<ResponseVO<ChapterTitleResult[]>> {
  return post('/ai/tools/chapter-titles', data)
}

export function generateDialogues(data: DialogueGenerateRequest): Promise<ResponseVO<DialogueResult[]>> {
  return post('/ai/tools/dialogues', data)
}

export function enhanceDescription(data: DescriptionEnhanceRequest): Promise<ResponseVO<DescriptionResult>> {
  return post('/ai/tools/description-enhance', data)
}

export function detectPlotHoles(data: PlotHoleDetectRequest): Promise<ResponseVO<PlotHoleResult>> {
  return post('/ai/tools/plot-hole-detect', data)
}

export function getWritingInspiration(data: WritingInspirationRequest): Promise<ResponseVO<WritingInspirationResult[]>> {
  return post('/ai/tools/inspiration', data)
}

export function analyzeStyle(data: StyleAnalyzeRequest): Promise<ResponseVO<StyleAnalyzeResult>> {
  return post('/ai/tools/style-analyze', data)
}

export function expandContent(content: string, targetLength: number): Promise<ResponseVO<{ expanded: string }>> {
  return post('/ai/tools/expand-content', { content, targetLength })
}

export function condenseContent(content: string, targetLength: number): Promise<ResponseVO<{ condensed: string }>> {
  return post('/ai/tools/condense-content', { content, targetLength })
}

export function rewriteContent(content: string, style?: string): Promise<ResponseVO<{ rewritten: string }>> {
  return post('/ai/tools/rewrite', { content, style })
}

export interface PromptTemplate {
  id: number
  name: string
  category: string
  content: string
  variables?: string[]
  description?: string
  useCount?: number
  createdAt?: string
  updatedAt?: string
}

export function listPromptTemplates(params?: { category?: string; keyword?: string }) {
  return get<ResponseVO<PromptTemplate[]>>('/ai/tools/prompt-templates', { params })
}

export function getPromptTemplate(id: number) {
  return get<ResponseVO<PromptTemplate>>(`/ai/tools/prompt-templates/${id}`)
}

export function createPromptTemplate(data: Omit<PromptTemplate, 'id' | 'useCount' | 'createdAt' | 'updatedAt'>) {
  return post<ResponseVO<PromptTemplate>>('/ai/tools/prompt-templates', data)
}

export function updatePromptTemplate(id: number, data: Partial<PromptTemplate>) {
  return put<ResponseVO<PromptTemplate>>(`/ai/tools/prompt-templates/${id}`, data)
}

export function deletePromptTemplate(id: number) {
  return del<ResponseVO<void>>(`/ai/tools/prompt-templates/${id}`)
}

export function renderPromptTemplate(id: number, variables: Record<string, string>) {
  return post<ResponseVO<{ rendered: string }>>(`/ai/tools/prompt-templates/${id}/render`, { variables })
}

export interface AIGenerateConfig {
  model?: string
  temperature?: number
  maxTokens?: number
  topP?: number
}

export interface AIGenerateRequest {
  prompt: string
  config?: AIGenerateConfig
}

export function generateWithAI(data: AIGenerateRequest): Promise<ResponseVO<{ content: string; usage?: any }>> {
  return post('/ai/tools/generate', data)
}
