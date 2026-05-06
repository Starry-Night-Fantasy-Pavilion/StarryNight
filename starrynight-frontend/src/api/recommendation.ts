import { del, get, post, put } from '@/utils/request'
import type { PageVO, ResponseVO } from '@/types/api'

export interface RecommendationItem {
  id: number
  title: string
  type: string
  novelId: number
  novelTitle?: string
  cover?: string
  position: string
  sort: number
  startTime?: string
  endTime?: string
  status: number
  createTime?: string
  updateTime?: string
}

export interface RecommendationCreateDTO {
  title: string
  type: string
  novelId: number
  position: string
  sort?: number
  startTime?: string | null
  endTime?: string | null
  status?: number
}

export interface RecommendationUpdateDTO {
  title?: string
  type?: string
  novelId?: number
  position?: string
  sort?: number
  startTime?: string | null
  endTime?: string | null
  status?: number
}

export function listRecommendations(params: {
  page?: number
  size?: number
}): Promise<ResponseVO<PageVO<RecommendationItem>>> {
  return get<ResponseVO<PageVO<RecommendationItem>>>('/recommendations', { params })
}

export function getRecommendation(id: number): Promise<ResponseVO<RecommendationItem>> {
  return get<ResponseVO<RecommendationItem>>(`/recommendations/${id}`)
}

export function createRecommendation(data: RecommendationCreateDTO): Promise<ResponseVO<RecommendationItem>> {
  return post<ResponseVO<RecommendationItem>>('/recommendations', data)
}

export function updateRecommendation(id: number, data: RecommendationUpdateDTO): Promise<ResponseVO<RecommendationItem>> {
  return put<ResponseVO<RecommendationItem>>(`/recommendations/${id}`, data)
}

export function deleteRecommendation(id: number): Promise<ResponseVO<void>> {
  return del<ResponseVO<void>>(`/recommendations/${id}`)
}

export function searchNovels(keyword: string): Promise<{ data: { id: number; title: string; author?: string }[] }> {
  return get('/novels/search', { params: { keyword } })
}