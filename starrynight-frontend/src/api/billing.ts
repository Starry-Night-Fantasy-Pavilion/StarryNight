import { get, post, put } from '@/utils/request'

export interface UserBalanceDTO {
  userId: number
  freeQuota: number
  freeQuotaDate: string
  platformCurrency: number
  platformCurrencyInPoints: number
  enableMixedPayment: boolean
  todayFreeUsed: number
  todayPaidUsed?: number
}

export interface EstimateResult {
  estimatedPoints: number
  message: string
  scenario: string
}

export interface RechargeRequest {
  userId: number
  amount: number
  payMethod: string
}

export interface RechargeResult {
  recordNo: string
  amount: number
  platformCurrency: number
  bonusCurrency: number
  payStatus: string
  payUrl?: string
}

export function getUserBalance(userId: number) {
  return get<UserBalanceDTO>('/user/balance', { params: { userId } })
}

export function getFreeQuota(userId: number) {
  return get<number>('/user/balance/free', { params: { userId } })
}

export function getPlatformCurrency(userId: number) {
  return get<number>('/user/balance/platform', { params: { userId } })
}

export function getMixedPayment(userId: number) {
  return get<boolean>('/user/mixed-payment', { params: { userId } })
}

export function setMixedPayment(userId: number, enabled: boolean) {
  return put<void>('/user/mixed-payment', undefined, { params: { userId, enabled } })
}

export function estimateCost(userId: number, contentType: string, inputTokens?: number, outputTokens?: number) {
  return get<EstimateResult>('/user/cost/estimate', {
    params: {
      userId,
      contentType,
      inputTokens: inputTokens ?? 0,
      outputTokens: outputTokens ?? 0
    }
  })
}

export function createRecharge(data: RechargeRequest) {
  return post<RechargeResult>('/user/recharge', data)
}
