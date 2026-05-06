import { get, post, put } from '@/utils/request'
import type { AdminRoleItem, OpsAccountItem, PageVO } from '@/types/api'

export function listOpsAccounts(params: {
  page: number
  size: number
  keyword?: string
  status?: number
}) {
  return get<PageVO<OpsAccountItem>>('/admin/ops-accounts/list', { params })
}

export function createOpsAccount(data: {
  username: string
  email?: string
  password: string
  roleId: number
  status: number
}) {
  return post<OpsAccountItem>('/admin/ops-accounts', data)
}

export function updateOpsAccount(
  id: number,
  data: { email?: string | null; roleId: number; status: number }
) {
  return put<OpsAccountItem>(`/admin/ops-accounts/${id}`, data)
}

export function resetOpsAccountPassword(id: number, password: string) {
  return put<void>(`/admin/ops-accounts/${id}/password`, { password })
}

export function listEnabledAdminRoles() {
  return get<AdminRoleItem[]>('/admin/roles/list', { params: { status: 1 } })
}
