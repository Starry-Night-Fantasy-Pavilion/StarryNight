import { del, get, post, put } from '@/utils/request'
import type { ResponseVO } from '@/types/api'

export interface Tenant {
  id: number
  name: string
  code: string
  type: 'personal' | 'team' | 'enterprise'
  status: 'active' | 'suspended' | 'trial'
  logo?: string
  description?: string
  contactEmail?: string
  createdAt: string
  expiredAt?: string
}

export interface TenantQuota {
  tenantId: number
  plan: 'free' | 'basic' | 'pro' | 'enterprise'
  novelsLimit: number
  chaptersLimit: number
  storageLimit: number
  apiCallsLimit: number
  teamMembersLimit: number
  currentNovels: number
  currentChapters: number
  currentStorage: number
  currentApiCalls: number
  currentTeamMembers: number
  resetAt: string
}

export interface TenantMember {
  id: number
  tenantId: number
  userId: number
  userName: string
  userEmail: string
  role: 'owner' | 'admin' | 'member' | 'viewer'
  joinedAt: string
  lastActiveAt?: string
}

export interface TenantInvitation {
  id: number
  tenantId: number
  email: string
  role: 'admin' | 'member' | 'viewer'
  invitedBy: number
  invitedAt: string
  expiresAt: string
  status: 'pending' | 'accepted' | 'expired'
}

export interface TenantUsageStats {
  tenantId: number
  period: 'daily' | 'weekly' | 'monthly'
  novelViews: number
  chapterReads: number
  apiCalls: number
  storageUsed: number
  activeUsers: number
  createdAt: string
}

export interface SharedProject {
  id: number
  novelId: number
  novelTitle: string
  sharedWith: number
  sharedWithName?: string
  permission: 'read' | 'write' | 'admin'
  sharedAt: string
  expiresAt?: string
}

export function getCurrentTenant(): Promise<ResponseVO<Tenant>> {
  return get('/tenant/current')
}

export function getTenantQuota(): Promise<ResponseVO<TenantQuota>> {
  return get('/tenant/quota')
}

export function updateTenant(id: number, data: Partial<Tenant>): Promise<ResponseVO<Tenant>> {
  return put(`/tenant/${id}`, data)
}

export function listTenantMembers(tenantId: number): Promise<ResponseVO<TenantMember[]>> {
  return get(`/tenant/${tenantId}/members`)
}

export function updateMemberRole(
  tenantId: number,
  memberId: number,
  role: TenantMember['role']
): Promise<ResponseVO<void>> {
  return put(`/tenant/${tenantId}/members/${memberId}/role`, { role })
}

export function removeMember(tenantId: number, memberId: number): Promise<ResponseVO<void>> {
  return del(`/tenant/${tenantId}/members/${memberId}`)
}

export function inviteMember(
  tenantId: number,
  data: { email: string; role: TenantInvitation['role'] }
): Promise<ResponseVO<TenantInvitation>> {
  return post(`/tenant/${tenantId}/invitations`, data)
}

export function listPendingInvitations(tenantId: number): Promise<ResponseVO<TenantInvitation[]>> {
  return get(`/tenant/${tenantId}/invitations`)
}

export function cancelInvitation(tenantId: number, invitationId: number): Promise<ResponseVO<void>> {
  return del(`/tenant/${tenantId}/invitations/${invitationId}`)
}

export function getTenantUsageStats(
  tenantId: number,
  period: 'daily' | 'weekly' | 'monthly'
): Promise<ResponseVO<TenantUsageStats[]>> {
  return get(`/tenant/${tenantId}/usage`, { params: { period } })
}

export function getSharedProjects(tenantId: number): Promise<ResponseVO<SharedProject[]>> {
  return get(`/tenant/${tenantId}/shared`)
}

export function shareProject(
  tenantId: number,
  data: { novelId: number; sharedWith: number; permission: SharedProject['permission'] }
): Promise<ResponseVO<SharedProject>> {
  return post(`/tenant/${tenantId}/shared`, data)
}

export function updateSharedPermission(
  tenantId: number,
  shareId: number,
  permission: SharedProject['permission']
): Promise<ResponseVO<void>> {
  return put(`/tenant/${tenantId}/shared/${shareId}`, { permission })
}

export function revokeSharedProject(tenantId: number, shareId: number): Promise<ResponseVO<void>> {
  return del(`/tenant/${tenantId}/shared/${shareId}`)
}

export function switchTenant(tenantId: number): Promise<ResponseVO<Tenant>> {
  return post(`/tenant/switch`, { tenantId })
}

export function listUserTenants(): Promise<ResponseVO<Tenant[]>> {
  return get('/tenant/list')
}

export function createPersonalTenant(data: { name: string }): Promise<ResponseVO<Tenant>> {
  return post('/tenant/personal', data)
}
