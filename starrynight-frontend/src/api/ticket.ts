import { get, post, put } from '@/utils/request'
import type {
  AdminTicketUpdateBody,
  PageVO,
  TicketCreateBody,
  TicketItem,
  TicketReplyBody,
  TicketStatus
} from '@/types/api'

// ─── 用户端 ─────────────────────────────────────────────────

export function createTicket(body: TicketCreateBody) {
  return post<TicketItem>('/tickets', body)
}

export function listMyTickets(params: { status?: TicketStatus; page?: number; size?: number }) {
  return get<PageVO<TicketItem>>('/tickets', { params })
}

export function getTicket(id: number) {
  return get<TicketItem>(`/tickets/${id}`)
}

export function replyTicket(id: number, body: TicketReplyBody) {
  return post<void>(`/tickets/${id}/reply`, body)
}

// ─── 管理端 ─────────────────────────────────────────────────

export function adminListTickets(params: {
  status?: TicketStatus
  category?: string
  keyword?: string
  page?: number
  size?: number
}) {
  return get<PageVO<TicketItem>>('/admin/tickets', { params })
}

export function adminGetTicketStats() {
  return get<{ openCount: number }>('/admin/tickets/stats')
}

export function adminGetTicket(id: number) {
  return get<TicketItem>(`/admin/tickets/${id}`)
}

export function adminUpdateTicket(id: number, body: AdminTicketUpdateBody) {
  return put<void>(`/admin/tickets/${id}`, body)
}

export function adminReplyTicket(id: number, body: TicketReplyBody) {
  return post<void>(`/admin/tickets/${id}/reply`, body)
}

export function adminCloseTicket(id: number, reason?: string) {
  return post<void>(`/admin/tickets/${id}/close`, reason ? { reason } : {})
}
