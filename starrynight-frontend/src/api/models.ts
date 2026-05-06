import { get } from '@/utils/request'
import type { AiModelItem } from '@/types/api'

// 对齐开发文档：GET /api/models
export function listAvailableModels(billingChannelId?: number) {
  return get<AiModelItem[]>('/models', {
    params: billingChannelId != null ? { billingChannelId } : undefined
  })
}

