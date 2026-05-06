import { del, get, post, put } from '@/utils/request'
import request from '@/utils/request'
import type { Novel, NovelVolume, NovelChapter, PageVO } from '@/types/api'

export interface NovelCreateDTO {
  title: string
  subtitle?: string
  cover?: string
  categoryId?: number
  genre?: string
  style?: string
  synopsis?: string
}

export interface NovelUpdateDTO {
  title?: string
  subtitle?: string
  cover?: string
  categoryId?: number
  genre?: string
  style?: string
  synopsis?: string
  status?: number
}

export interface VolumeCreateDTO {
  novelId: number
  title: string
  description?: string
  volumeOrder: number
}

export interface VolumeUpdateDTO {
  title?: string
  description?: string
  volumeOrder?: number
  status?: number
}

export interface ChapterCreateDTO {
  novelId: number
  volumeId?: number
  title: string
  content?: string
  outline?: string
  chapterOrder: number
}

export interface ChapterUpdateDTO {
  volumeId?: number
  title?: string
  content?: string
  outline?: string
  chapterOrder?: number
  status?: number
}

export function listNovels(params: { page?: number; size?: number }) {
  return get<ResponseVO<PageVO<Novel>>>('/novels', { params })
}

export function getNovel(id: number) {
  return get<ResponseVO<Novel>>(`/novels/${id}`)
}

export function createNovel(data: NovelCreateDTO) {
  return post<ResponseVO<Novel>>('/novels', data)
}

export function updateNovel(id: number, data: NovelUpdateDTO) {
  return put<ResponseVO<Novel>>(`/novels/${id}`, data)
}

export function deleteNovel(id: number) {
  return del<ResponseVO<void>>(`/novels/${id}`)
}

export function publishNovel(id: number) {
  return post<ResponseVO<void>>(`/novels/${id}/publish`, {})
}

export function exportNovel(id: number, format: 'txt' | 'html' = 'txt'): Promise<string> {
  return get<string>(`/novels/${id}/export`, { params: { format } })
}

export function listVolumes(novelId: number): Promise<NovelVolume[]> {
  return get<NovelVolume[]>(`/novels/${novelId}/volumes`)
}

export function createVolume(data: VolumeCreateDTO): Promise<NovelVolume> {
  return post<NovelVolume>('/novels/volumes', data)
}

export function updateVolume(id: number, data: VolumeUpdateDTO): Promise<NovelVolume> {
  return put<NovelVolume>(`/novels/volumes/${id}`, data)
}

export function deleteVolume(id: number): Promise<void> {
  return del<void>(`/novels/volumes/${id}`)
}

export function generateVolumes(novelId: number, volumeCount?: number): Promise<NovelVolume[]> {
  return post<NovelVolume[]>(`/novels/${novelId}/generate-volumes`, null, {
    params: volumeCount ? { volumeCount } : {}
  })
}

export function generateOutline(novelId: number, data?: {
  coreIdea?: string
  genre?: string
  style?: string
  template?: string
}): Promise<any> {
  return post<any>(`/novels/${novelId}/generate-outline`, data || {})
}

export function listChapters(novelId: number, volumeId?: number): Promise<NovelChapter[]> {
  return get<NovelChapter[]>(`/novels/${novelId}/chapters`, {
    params: volumeId ? { volumeId } : {}
  })
}

export function getChapter(id: number): Promise<NovelChapter> {
  return get<NovelChapter>(`/novels/chapters/${id}`)
}

export function createChapter(data: ChapterCreateDTO): Promise<NovelChapter> {
  return post<NovelChapter>('/novels/chapters', data)
}

export function updateChapter(id: number, data: ChapterUpdateDTO): Promise<NovelChapter> {
  return put<NovelChapter>(`/novels/chapters/${id}`, data)
}

export function deleteChapter(id: number): Promise<void> {
  return del<void>(`/novels/chapters/${id}`)
}

export function exportNovelToWord(id: number): Promise<Blob> {
  return request.get(`/novels/${id}/export/word`, {
    responseType: 'blob'
  }) as Promise<Blob>
}

export function downloadWordFile(id: number, filename?: string) {
  return exportNovelToWord(id).then(blob => {
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = filename || `novel_${id}.docx`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  })
}

export function approveNovel(id: number): Promise<void> {
  return put(`/novels/${id}/audit`, { auditStatus: 1 })
}

export function rejectNovel(id: number, reason?: string): Promise<void> {
  return put(`/novels/${id}/audit`, { auditStatus: 2, rejectReason: reason })
}
