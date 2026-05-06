import request from '@/utils/request'

export interface CheckinResult {
  success: boolean
  date: string
  continuousDays: number
  baseReward: number
  bonusReward: number
  totalReward: number
  isFirst: boolean
  message: string
}

export interface CheckinStatus {
  checkedIn: boolean
  todayReward: number
  continuousDays: number
  checkedDates: string[]
  maxContinuousDays: number
  totalCheckins: number
}

export interface PointsSummary {
  currentBalance: number
  totalEarned: number
  totalUsed: number
  totalCheckins: number
  maxContinuousDays: number
}

export interface PointsTransaction {
  id: number
  userId: number
  transactionType: string
  pointsChange: number
  balanceBefore: number
  balanceAfter: number
  description: string
  createTime: string
}

export interface TaskStatus {
  taskCode: string
  taskName: string
  description: string
  rewardAmount: number
  completedCount: number
  maxTimes: number
  completed: boolean
  rewardClaimed: boolean
}

export interface TaskConfig {
  id: number
  taskCode: string
  taskName: string
  taskType: string
  description: string
  rewardAmount: number
}

export function doCheckin(userId: number) {
  return request.post<CheckinResult>('/api/growth/checkin', null, { params: { userId } })
}

export function getCheckinStatus(userId: number) {
  return request.get<CheckinStatus>('/api/growth/checkin/status', { params: { userId } })
}

export function getPointsSummary(userId: number) {
  return request.get<PointsSummary>('/api/growth/points/summary', { params: { userId } })
}

export function getPointsHistory(userId: number, limit = 20) {
  return request.get<PointsTransaction[]>('/api/growth/points/history', { params: { userId, limit } })
}

export function getDailyTasks(userId: number) {
  return request.get<TaskStatus[]>('/api/growth/tasks/daily', { params: { userId } })
}

export function getAchievementTasks() {
  return request.get<TaskConfig[]>('/api/growth/tasks/achievement')
}

export function recordTaskCompletion(userId: number, taskCode: string) {
  return request.post('/api/growth/tasks/complete', null, { params: { userId, taskCode } })
}

export interface RedeemResult {
  rewardType: string
  rewardPoints?: number
  rewardCurrency?: number
  message?: string
}

/** 用户端兑换码（需用户 JWT） */
export function redeemGrowthCode(code: string) {
  return request.post<RedeemResult>('/api/growth/redeem', { code })
}
