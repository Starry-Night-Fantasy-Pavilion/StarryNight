import { get, post, put, del } from '@/utils/request'
import type { PageVO, TaskConfigItem } from '@/types/api'

export function listTaskConfigs(params: { keyword?: string; page?: number; size?: number }) {
  return get<PageVO<TaskConfigItem>>('/admin/growth/task-configs/list', { params })
}

export function getTaskConfig(id: number) {
  return get<TaskConfigItem>(`/admin/growth/task-configs/${id}`)
}

export function createTaskConfig(data: TaskConfigItem) {
  return post<TaskConfigItem>('/admin/growth/task-configs', data)
}

export function updateTaskConfig(id: number, data: TaskConfigItem) {
  return put<TaskConfigItem>(`/admin/growth/task-configs/${id}`, data)
}

export function deleteTaskConfig(id: number) {
  return del<void>(`/admin/growth/task-configs/${id}`)
}
