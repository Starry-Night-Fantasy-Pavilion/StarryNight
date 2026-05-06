import { get, post, put, del } from '@/utils/request'
import type { OpsCampaignItem } from '@/types/api'

export function listAdminCampaigns() {
  return get<OpsCampaignItem[]>('/admin/campaigns/list')
}

export function getAdminCampaign(id: number) {
  return get<OpsCampaignItem>(`/admin/campaigns/${id}`)
}

export function createCampaign(data: OpsCampaignItem) {
  return post<OpsCampaignItem>('/admin/campaigns', data)
}

export function updateCampaign(id: number, data: OpsCampaignItem) {
  return put<OpsCampaignItem>(`/admin/campaigns/${id}`, data)
}

export function deleteCampaign(id: number) {
  return del<void>(`/admin/campaigns/${id}`)
}

/** 前台可见活动（无需登录） */
export function listVisibleCampaigns() {
  return get<OpsCampaignItem[]>('/campaigns/visible')
}
