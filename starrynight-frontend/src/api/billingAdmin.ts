import { get, post, put, del } from '@/utils/request'
import type { ResponseVO } from '@/types/api'

export interface BillingConfigDTO {
  dailyFreeQuota: number
  defaultProfitMargin: number
  mixedPaymentDefault: boolean
  freeQuotaResetHour: number
  platformCurrencyRate?: number
  creationPointRate?: number
}

export interface ChannelDTO {
  id?: number
  channelCode: string
  channelName: string
  channelType: string
  costPer1kInput: number
  costPer1kOutput: number
  costPerCall?: number
  costPerSecond?: number
  baseCost?: number
  isFree: boolean
  enabled: boolean
  sortOrder: number
  status?: string
}

export interface DailyReportDTO {
  date: string
  freeCost: number
  paidCost: number
  revenue: number
  profit: number
}

export interface UsageRecordDTO {
  id: number
  userId: number
  channelCode: string
  inputTokens: number
  outputTokens: number
  duration: number
  cost: number
  createTime: string
}

export function getBillingConfig() {
  return get<BillingConfigDTO>('/admin/billing/config')
}

export function updateBillingConfig(data: Record<string, string>) {
  return put<ResponseVO<void>>('/admin/billing/config', data)
}

export function listChannels(type?: string, enabled?: boolean) {
  return get<ChannelDTO[]>('/admin/billing/channel', {
    params: { type, enabled }
  })
}

export function createChannel(data: ChannelDTO) {
  return post<ResponseVO<ChannelDTO>>('/admin/billing/channel', data)
}

export function updateChannel(id: number, data: ChannelDTO) {
  return put<ResponseVO<ChannelDTO>>(`/admin/billing/channel/${id}`, data)
}

export function deleteChannel(id: number) {
  return del<ResponseVO<void>>(`/admin/billing/channel/${id}`)
}

export function enableChannel(id: number) {
  return post<ResponseVO<void>>(`/admin/billing/channel/${id}/enable`, {})
}

export function disableChannel(id: number) {
  return post<ResponseVO<void>>(`/admin/billing/channel/${id}/disable`, {})
}

export function getDailyReport() {
  return get<DailyReportDTO>('/admin/billing/report/daily')
}

export function listUsageRecords(params: {
  userId?: number
  channelCode?: string
  startDate?: string
  endDate?: string
  page?: number
  size?: number
}) {
  return get<ResponseVO<any>>('/admin/billing/usage-records', { params })
}