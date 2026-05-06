import { get, put } from '@/utils/request'
import type { AdminOrderItem, PageVO } from '@/types/api'

export function listAdminOrders(params: {
  page: number
  size: number
  keyword?: string
  status?: number
}) {
  return get<PageVO<AdminOrderItem>>('/admin/orders/list', { params })
}

export function updateAdminOrderStatus(id: number, status: number) {
  return put<void>(`/admin/orders/${id}/status`, { status })
}
