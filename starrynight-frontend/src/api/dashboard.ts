import { get } from '@/utils/request'
import type { AdminDashboardStats } from '@/types/api'

export function getAdminDashboardStats() {
  return get<AdminDashboardStats>('/admin/dashboard/stats')
}
