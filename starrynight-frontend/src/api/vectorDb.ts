import { get, post, put, del } from '@/utils/request'

export interface VectorNode {
  id?: number
  name: string
  host: string
  port?: number
  apiKey?: string
  maxVectors?: number
  maxStorage?: number
  status?: string
  enabled?: number
  vectorCount?: number
  load?: number
  storageUsed?: string
  address?: string
}

export interface VectorCollection {
  id?: number
  name: string
  type?: string
  vectorCount?: number
  dimension?: number
  embeddingModel?: string
  distance?: string
  maxVectors?: number
  status?: string
}

export interface VectorPoolConfig {
  maxConnections: number
  minIdle: number
  connectionTimeout: number
  maxVectors: number
  maxStorage: number
}

export interface VectorStats {
  totalNodes: number
  totalVectors: string
  storageUsed: string
  clusterStatus: string
}

export interface Alert {
  time: string
  level: string
  message: string
}

export function getVectorStats() {
  return get<VectorStats>('/admin/vector/stats')
}

export function listVectorNodes() {
  return get<VectorNode[]>('/admin/vector/nodes')
}

export function createVectorNode(data: VectorNode) {
  return post<VectorNode>('/admin/vector/nodes', data)
}

export function updateVectorNode(id: number, data: VectorNode) {
  return put<VectorNode>(`/admin/vector/nodes/${id}`, data)
}

export function deleteVectorNode(id: number) {
  return del<void>(`/admin/vector/nodes/${id}`)
}

export function restartVectorNode(id: number) {
  return post<void>(`/admin/vector/nodes/${id}/restart`, {})
}

export function listVectorCollections() {
  return get<VectorCollection[]>('/admin/vector/collections')
}

export function createVectorCollection(data: VectorCollection) {
  return post<VectorCollection>('/admin/vector/collections', data)
}

export function deleteVectorCollection(id: number) {
  return del<void>(`/admin/vector/collections/${id}`)
}

export function createVectorSnapshot(id: number) {
  return post<void>(`/admin/vector/collections/${id}/snapshot`, {})
}

export function getVectorPoolConfig() {
  return get<VectorPoolConfig>('/admin/vector/pool-config')
}

export function saveVectorPoolConfig(data: VectorPoolConfig) {
  return put<void>('/admin/vector/pool-config', data)
}