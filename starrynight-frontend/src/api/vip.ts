import request from '@/utils/request'

export interface VipPackage {
  id: number
  packageCode: string
  packageName: string
  description: string
  memberLevel: number
  durationDays: number
  price: number
  originalPrice: number
  dailyFreeQuota: number
  features: string
}

export interface MemberSubscription {
  id: number
  userId: number
  packageId: number
  memberLevel: number
  startTime: string
  expireTime: string
  status: string
  autoRenew: number
}

export interface MemberBenefits {
  memberLevel: number
  memberLevelName: string
  isActive: boolean
  expireTime?: string
  dailyFreeQuota: number
  [key: string]: any
}

export function getVipPackages(memberLevel?: number) {
  return request.get<VipPackage[]>('/api/vip/packages', {
    params: memberLevel ? { memberLevel } : {}
  })
}

export function getVipPackage(id: number) {
  return request.get<VipPackage>(`/api/vip/package/${id}`)
}

export function getMemberBenefits(userId: number) {
  return request.get<MemberBenefits>('/api/vip/benefits', { params: { userId } })
}

export function getMemberStatus(userId: number) {
  return request.get('/api/vip/status', { params: { userId } })
}

export function getSubscription(userId: number) {
  return request.get<MemberSubscription>('/api/vip/subscription', { params: { userId } })
}

export function getSubscriptionHistory(userId: number) {
  return request.get<MemberSubscription[]>('/api/vip/subscription/history', { params: { userId } })
}

export function checkBenefit(userId: number, benefitKey: string) {
  return request.get<boolean>('/api/vip/check-benefit', { params: { userId, benefitKey } })
}

export function activateMembership(userId: number, packageId: number) {
  return request.post<MemberSubscription>('/api/vip/activate', null, {
    params: { userId, packageId }
  })
}
