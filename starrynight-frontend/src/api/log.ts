import { del, get } from '@/utils/request'
import type { OperationLogItem, PageVO } from '@/types/api'

export function listOperationLogs(params: {
  page: number
  size: number
  userId?: number
  operation?: string
  module?: string
  startTime?: string
  endTime?: string
}) {
  return get<PageVO<OperationLogItem>>('/admin/logs/list', { params })
}

export function deleteOperationLog(id: number) {
  return del<void>(`/admin/logs/${id}`)
}

