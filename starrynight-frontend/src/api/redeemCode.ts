import { get, post, put, del } from '@/utils/request'
import type { PageVO, RedeemCodeItem, RedeemGeneratePayload } from '@/types/api'

export function listRedeemCodes(params: { keyword?: string; page?: number; size?: number }) {
  return get<PageVO<RedeemCodeItem>>('/admin/redeem-codes/list', { params })
}

export function createRedeemCode(data: RedeemCodeItem) {
  return post<RedeemCodeItem>('/admin/redeem-codes', data)
}

export function updateRedeemCode(id: number, data: RedeemCodeItem) {
  return put<RedeemCodeItem>(`/admin/redeem-codes/${id}`, data)
}

export function deleteRedeemCode(id: number) {
  return del<void>(`/admin/redeem-codes/${id}`)
}

export function generateRedeemCodes(data: RedeemGeneratePayload) {
  return post<RedeemCodeItem[]>('/admin/redeem-codes/generate', data)
}
