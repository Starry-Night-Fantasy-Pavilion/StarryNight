import { get, put } from '@/utils/request'
import type { UserInfo } from '@/types/api'

export function getOpsSelfProfile() {
  return get<UserInfo>('/admin/self/profile')
}

export function updateOpsSelfProfile(data: { email: string; username?: string }) {
  return put<UserInfo>('/admin/self/profile', data)
}

export function updateOpsSelfPassword(data: { oldPassword: string; newPassword: string }) {
  return put<void>('/admin/self/password', data)
}
