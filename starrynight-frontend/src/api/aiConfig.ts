import { del, get, post, put } from '@/utils/request'
import type { AiModelItem, AiSensitiveWordItem } from '@/types/api'

export function listAiModels(billingChannelId?: number) {
  return get<AiModelItem[]>('/admin/ai/models', {
    params: billingChannelId != null ? { billingChannelId } : undefined
  })
}

export function createAiModel(data: AiModelItem) {
  return post<AiModelItem>('/admin/ai/models', data)
}

export function updateAiModel(id: number, data: AiModelItem) {
  return put<AiModelItem>(`/admin/ai/models/${id}`, data)
}

export function deleteAiModel(id: number) {
  return del<void>(`/admin/ai/models/${id}`)
}

export function listAiSensitiveWords(level?: number) {
  return get<AiSensitiveWordItem[]>('/admin/ai/sensitive-words', {
    params: level === undefined ? undefined : { level }
  })
}

export function createAiSensitiveWord(data: AiSensitiveWordItem) {
  return post<AiSensitiveWordItem>('/admin/ai/sensitive-words', data)
}

export function updateAiSensitiveWord(id: number, data: AiSensitiveWordItem) {
  return put<AiSensitiveWordItem>(`/admin/ai/sensitive-words/${id}`, data)
}

export function deleteAiSensitiveWord(id: number) {
  return del<void>(`/admin/ai/sensitive-words/${id}`)
}

export interface AiTemplate {
  id?: number
  name: string
  type: string
  description?: string
  content: string
  enabled?: number
  usageCount?: number
}

export function listAiTemplates(type?: string) {
  return get<AiTemplate[]>('/admin/ai/templates', { params: type ? { type } : undefined })
}

export function createAiTemplate(data: AiTemplate) {
  return post<AiTemplate>('/admin/ai/templates', data)
}

export function updateAiTemplate(id: number, data: AiTemplate) {
  return put<AiTemplate>(`/admin/ai/templates/${id}`, data)
}

export function deleteAiTemplate(id: number) {
  return del<void>(`/admin/ai/templates/${id}`)
}

export interface GenerationParams {
  temperature: number
  maxTokens: number
  topP: number
  frequencyPenalty: number
  presencePenalty: number
  outlineTemperature?: number
  contentTemperature?: number
  chatTemperature?: number
  enableStreaming?: boolean
  streamInterval?: number
}

export function getGenerationParams() {
  return get<GenerationParams>('/admin/ai/config/params')
}

export function saveGenerationParams(params: GenerationParams) {
  return post<void>('/admin/ai/config/params', params)
}
