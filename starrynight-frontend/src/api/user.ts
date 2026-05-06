import { get, post, put } from '@/utils/request'
import type {
  AdminUserCreatePayload,
  AdminUserDetail,
  AdminUserItem,
  PageVO,
  UserProfile,
  UserProfileUpdatePayload
} from '@/types/api'

export function listAdminUsers(params: {
  page: number
  size: number
  keyword?: string
  status?: number
  /** 与 user_profile.member_level 精确匹配 */
  memberLevel?: number
  /** 与 user_profile.member_level 下限（含），如 2 表示 VIP 与高级 VIP */
  memberLevelMin?: number
}) {
  return get<PageVO<AdminUserItem>>('/admin/users/list', { params })
}

export function createAdminUser(data: AdminUserCreatePayload) {
  return post<AdminUserItem>('/admin/users', data)
}

export function updateUserStatus(id: number, status: number) {
  return put<void>(`/admin/users/${id}/status`, { status })
}

export function getAdminUserDetail(id: number) {
  return get<AdminUserDetail>(`/admin/users/${id}/detail`)
}

export function updateAdminUserBalance(id: number, payload: { freeQuota?: number; platformCurrency?: number }) {
  return put<void>(`/admin/users/${id}/balance`, payload)
}

export function updateAdminUserMembership(
  id: number,
  payload: { memberLevel: number; memberExpireTime?: string | null }
) {
  return put<void>(`/admin/users/${id}/membership`, payload)
}

/** 运营修正人脸/三方实名核验状态：0 未通过，1 已通过（通过时须已登记证件信息） */
export function updateAdminUserRealnameVerified(id: number, realNameVerified: 0 | 1) {
  return put<void>(`/admin/users/${id}/realname-verified`, { realNameVerified })
}

export function updateUserPoints(id: number, points: number) {
  return put<void>(`/admin/users/${id}/points`, { points })
}

export function getCurrentUserProfile() {
  return get<UserProfile>('/user/profile')
}

export function updateCurrentUserProfile(payload: UserProfileUpdatePayload) {
  return put<UserProfile>('/user/profile', payload)
}
