import { get, post, put, del } from '@/utils/request'

export interface NotificationMessage {
  id: number
  userId: number
  notificationType: string
  title: string
  content: string
  linkUrl?: string
  linkParams?: string
  isRead: number
  readTime?: string
  priority: string
  expireTime?: string
  createTime: string
}

export interface NotificationSetting {
  id: number
  userId: number
  notificationType: string
  pushEnabled: number
  emailEnabled: number
}

export type NotificationType = 'SYSTEM' | 'ACCOUNT' | 'ORDER' | 'ACTIVITY' | 'INTERACTION' | 'AI创作'

/** baseURL 已为 /api，路径使用 /notifications（不要再写 /api/notifications） */
export function getNotifications(userId: number, limit = 20) {
  return get<NotificationMessage[]>('/notifications', {
    params: { userId, limit },
    silentGlobalError: true
  })
}

export function getNotificationsByType(userId: number, type: string) {
  return get<NotificationMessage[]>(`/notifications/type/${type}`, { params: { userId } })
}

export function getUnreadCount(userId: number) {
  return get<number>('/notifications/unread-count', {
    params: { userId },
    silentGlobalError: true
  })
}

export function markAsRead(id: number) {
  return post<void>(`/notifications/${id}/read`)
}

export function markAllAsRead(userId: number) {
  return post<void>('/notifications/read-all', null, { params: { userId } })
}

export function deleteNotification(id: number) {
  return del<void>(`/notifications/${id}`)
}

export function getUserSettings(userId: number) {
  return get<Record<string, NotificationSetting>>('/notifications/settings', { params: { userId } })
}

export function updateSetting(userId: number, type: string, pushEnabled?: boolean, emailEnabled?: boolean) {
  return put<void>('/notifications/settings', null, {
    params: { userId, type, pushEnabled, emailEnabled }
  })
}

export const notificationTypeLabels: Record<string, string> = {
  SYSTEM: '系统通知',
  ACCOUNT: '账号通知',
  ORDER: '订单通知',
  ACTIVITY: '活动通知',
  INTERACTION: '互动通知',
  AI创作: 'AI创作通知'
}

export const notificationTypeIcons: Record<string, string> = {
  SYSTEM: '📢',
  ACCOUNT: '👤',
  ORDER: '💰',
  ACTIVITY: '🎁',
  INTERACTION: '💬',
  AI创作: '🤖'
}
