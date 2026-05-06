import { del, get, post } from '@/utils/request'
import type {
  AdminCommunityCommentItem,
  AdminCommunityPostItem,
  AdminCommunityReportItem,
  CommunityWorkOrderItem,
  PageVO
} from '@/types/api'

export function listCommunityWorkOrders(params: { page?: number; size?: number }) {
  return get<PageVO<CommunityWorkOrderItem>>('/admin/community/work-orders/list', { params })
}

export function getCommunityWorkOrderStats() {
  return get<{ pendingCount: number }>('/admin/community/work-orders/stats')
}

export function listAdminCommunityPosts(params: {
  auditStatus?: number
  page?: number
  size?: number
}) {
  return get<PageVO<AdminCommunityPostItem>>('/admin/community/posts/list', { params })
}

export function approveCommunityPost(id: number) {
  return post<void>(`/admin/community/posts/${id}/approve`)
}

export function rejectCommunityPost(id: number, reason?: string) {
  return post<void>(`/admin/community/posts/${id}/reject`, reason ? { reason } : {})
}

export function takeDownCommunityPost(id: number) {
  return post<void>(`/admin/community/posts/${id}/take-down`)
}

export function listAdminCommunityComments(params: {
  postId?: number
  keyword?: string
  /** 0 待审 1 通过 2 驳回；不传为全部 */
  auditStatus?: number
  page?: number
  size?: number
}) {
  return get<PageVO<AdminCommunityCommentItem>>('/admin/community/comments/list', { params })
}

export function deleteAdminCommunityComment(id: number) {
  return del<void>(`/admin/community/comments/${id}`)
}

export function approveCommunityComment(id: number) {
  return post<void>(`/admin/community/comments/${id}/approve`)
}

export function rejectCommunityComment(id: number, reason?: string) {
  return post<void>(`/admin/community/comments/${id}/reject`, reason ? { reason } : {})
}

export function listAdminCommunityReports(params: {
  /** 0 待处理 1 已处理 2 已忽略；不传为全部 */
  status?: number
  page?: number
  size?: number
}) {
  return get<PageVO<AdminCommunityReportItem>>('/admin/community/reports/list', { params })
}

export function getAdminCommunityReportStats() {
  return get<{ pendingCount: number }>('/admin/community/reports/stats')
}

export function ignoreAdminCommunityReport(id: number, note?: string) {
  return post<void>(`/admin/community/reports/${id}/ignore`, note ? { note } : {})
}

export function resolveAdminCommunityReport(id: number, body?: { action?: string; note?: string }) {
  return post<void>(`/admin/community/reports/${id}/resolve`, body || {})
}
