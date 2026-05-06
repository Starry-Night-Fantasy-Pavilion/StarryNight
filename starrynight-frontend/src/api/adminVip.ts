import { get, post, put } from '@/utils/request'

/** 与后端 vip_package 一致 */
export interface AdminVipPackage {
  id: number
  packageCode: string
  packageName: string
  description?: string
  memberLevel: number
  durationDays: number
  price: number
  originalPrice?: number
  dailyFreeQuota: number
  features?: string
  sortOrder: number
  status: number
  createTime?: string
  updateTime?: string
}

export interface AdminVipPackagePayload {
  packageCode?: string
  packageName: string
  description?: string
  memberLevel: number
  durationDays: number
  price: number
  originalPrice?: number
  dailyFreeQuota: number
  features?: string
  sortOrder: number
  status: number
}

/** 与后端 member_benefit_config 一致 */
export interface AdminMemberBenefitConfig {
  id: number
  memberLevel: number
  benefitKey: string
  benefitName: string
  benefitValue?: string | null
  description?: string
  enabled: number
  createTime?: string
  updateTime?: string
}

export interface AdminBenefitConfigPayload {
  benefitName: string
  benefitValue?: string | null
  description?: string
  enabled: number
}

export function adminListVipPackages() {
  return get<AdminVipPackage[]>('/admin/vip/packages')
}

export function adminCreateVipPackage(data: AdminVipPackagePayload) {
  return post<AdminVipPackage>('/admin/vip/packages', data)
}

export function adminUpdateVipPackage(id: number, data: AdminVipPackagePayload) {
  return put<AdminVipPackage>(`/admin/vip/packages/${id}`, data)
}

export function adminListBenefitConfigs(memberLevel?: number) {
  return get<AdminMemberBenefitConfig[]>('/admin/vip/benefit-configs', {
    params: memberLevel != null ? { memberLevel } : {}
  })
}

export function adminUpdateBenefitConfig(id: number, data: AdminBenefitConfigPayload) {
  return put<AdminMemberBenefitConfig>(`/admin/vip/benefit-configs/${id}`, data)
}
