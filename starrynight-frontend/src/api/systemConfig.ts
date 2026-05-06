import { del, get, post, put } from '@/utils/request'
import type { SystemConfigItem } from '@/types/api'

export function listSystemConfigs(group?: string) {
  return get<SystemConfigItem[]>('/admin/config/list', {
    params: group ? { group } : undefined
  })
}

export function createSystemConfig(data: SystemConfigItem) {
  return post<SystemConfigItem>('/admin/config', data)
}

export function updateSystemConfig(data: SystemConfigItem) {
  return put<SystemConfigItem>('/admin/config', data)
}

export function deleteSystemConfig(configKey: string) {
  return del<void>(`/admin/config/${configKey}`)
}

/** 从库重载 system_config 到进程内存并触发 Redis/Rabbit 等热切换（直改库、跑脚本后用） */
export function reloadRuntimeSystemConfig() {
  return post<void>('/admin/config/reload-runtime')
}
