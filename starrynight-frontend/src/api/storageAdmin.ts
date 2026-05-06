import { get, post, put, del } from '@/utils/request'
import type { ResponseVO } from '@/types/api'

export interface StorageConfig {
  id?: number
  name: string
  type: string
  endpoint: string
  accessKey?: string
  secretKey?: string
  bucket: string
  domain?: string
  enabled?: boolean
  isDefault?: boolean
  totalStorage?: number
  usedStorage?: number
  status?: string
}

export function listStorageConfigs() {
  return get<ResponseVO<StorageConfig[]>>('/admin/storage/configs')
}

export function getStorageConfig(id: number) {
  return get<ResponseVO<StorageConfig>>(`/admin/storage/configs/${id}`)
}

export function getDefaultStorageConfig() {
  return get<ResponseVO<StorageConfig>>('/admin/storage/configs/default')
}

export function createStorageConfig(data: StorageConfig) {
  return post<ResponseVO<StorageConfig>>('/admin/storage/configs', data)
}

export function updateStorageConfig(id: number, data: StorageConfig) {
  return put<ResponseVO<StorageConfig>>(`/admin/storage/configs/${id}`, data)
}

export function deleteStorageConfig(id: number) {
  return del<ResponseVO<void>>(`/admin/storage/configs/${id}`)
}

export function testStorageConnection(id: number) {
  return post<ResponseVO<void>>(`/admin/storage/configs/${id}/test`, {})
}

export function getStorageStats() {
  return get<ResponseVO<StorageConfig>>('/admin/storage/stats')
}