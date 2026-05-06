import { del, get, post, put } from '@/utils/request'
import type { AnnouncementItem } from '@/types/api'

export function listAnnouncements(status?: number) {
  return get<AnnouncementItem[]>('/admin/announcements/list', {
    params: status === undefined ? undefined : { status }
  })
}

export function createAnnouncement(data: AnnouncementItem) {
  return post<AnnouncementItem>('/admin/announcements', data)
}

export function updateAnnouncement(id: number, data: AnnouncementItem) {
  return put<AnnouncementItem>(`/admin/announcements/${id}`, data)
}

export function deleteAnnouncement(id: number) {
  return del<void>(`/admin/announcements/${id}`)
}
