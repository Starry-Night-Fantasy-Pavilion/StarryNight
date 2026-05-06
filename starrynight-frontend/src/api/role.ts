import { del, get, post, put } from '@/utils/request'
import type { AdminRoleItem } from '@/types/api'

export function listAdminRoles(status?: number) {
  return get<AdminRoleItem[]>('/admin/roles/list', {
    params: status === undefined ? undefined : { status }
  })
}

export function createAdminRole(data: AdminRoleItem) {
  return post<AdminRoleItem>('/admin/roles', data)
}

export function updateAdminRole(id: number, data: AdminRoleItem) {
  return put<AdminRoleItem>(`/admin/roles/${id}`, data)
}

export function deleteAdminRole(id: number) {
  return del<void>(`/admin/roles/${id}`)
}
