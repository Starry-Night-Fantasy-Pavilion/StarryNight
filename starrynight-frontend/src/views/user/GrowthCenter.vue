<template>
  <div class="growth-center page-container">
    <div class="page-header">
      <h1>📈 成长中心</h1>
    </div>

    <el-row :gutter="20">
      <el-col :span="16">
        <el-card class="checkin-section">
          <CheckinCard :user-id="userId" />
        </el-card>

        <el-card class="tasks-section" style="margin-top: 20px">
          <template #header>
            <span>📝 每日任务</span>
          </template>

          <div class="task-list" v-loading="loadingTasks">
            <div v-if="dailyTasks.length === 0" class="empty-state">
              <p>暂无每日任务</p>
            </div>

            <div v-for="task in dailyTasks" :key="task.taskCode" class="task-item">
              <div class="task-info">
                <span class="task-name">{{ task.taskName }}</span>
                <span class="task-desc">{{ task.description }}</span>
              </div>
              <div class="task-progress">
                <span class="progress-text">
                  {{ task.completedCount }}/{{ task.maxTimes || '∞' }}
                </span>
              </div>
              <div class="task-reward">
                <span class="reward-amount">+{{ task.rewardAmount }}</span>
                <span class="reward-unit">创作点</span>
              </div>
              <div class="task-action">
                <el-tag v-if="task.completed" type="success" size="small">已完成</el-tag>
                <el-tag v-else type="info" size="small">进行中</el-tag>
              </div>
            </div>
          </div>
        </el-card>
      </el-col>

      <el-col :span="8">
        <el-card class="stats-card">
          <template #header>
            <span>📊 成长数据</span>
          </template>

          <div class="stats-list" v-if="summary">
            <div class="stat-item">
              <div class="stat-value">{{ formatNumber(summary.currentBalance) }}</div>
              <div class="stat-label">当前创作点</div>
            </div>
            <div class="stat-item">
              <div class="stat-value">{{ formatNumber(summary.totalEarned) }}</div>
              <div class="stat-label">累计获得</div>
            </div>
            <div class="stat-item">
              <div class="stat-value">{{ formatNumber(summary.totalUsed) }}</div>
              <div class="stat-label">累计消耗</div>
            </div>
            <div class="stat-item">
              <div class="stat-value">{{ summary.totalCheckins }}</div>
              <div class="stat-label">累计签到</div>
            </div>
            <div class="stat-item">
              <div class="stat-value">{{ summary.maxContinuousDays }}</div>
              <div class="stat-label">最高连续</div>
            </div>
          </div>
        </el-card>

        <el-card class="history-card" style="margin-top: 20px">
          <template #header>
            <span>💰 最近记录</span>
          </template>

          <div class="history-list" v-loading="loadingHistory">
            <div v-if="history.length === 0" class="empty-state">
              <p>暂无记录</p>
            </div>

            <div v-for="record in history" :key="record.id" class="history-item">
              <div class="history-icon" :class="record.pointsChange > 0 ? 'income' : 'expense'">
                {{ record.pointsChange > 0 ? '+' : '' }}{{ record.pointsChange }}
              </div>
              <div class="history-info">
                <span class="history-desc">{{ record.description }}</span>
                <span class="history-time">{{ formatTime(record.createTime) }}</span>
              </div>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import CheckinCard from '@/components/user/CheckinCard.vue'
import {
  getDailyTasks,
  getPointsSummary,
  getPointsHistory,
  type TaskStatus,
  type PointsSummary,
  type PointsTransaction
} from '@/api/growth'

const props = defineProps<{
  userId: number
}>()

const dailyTasks = ref<TaskStatus[]>([])
const summary = ref<PointsSummary | null>(null)
const history = ref<PointsTransaction[]>([])
const loadingTasks = ref(false)
const loadingHistory = ref(false)

function formatNumber(num: number): string {
  return num?.toLocaleString() || '0'
}

function formatTime(timeStr: string): string {
  if (!timeStr) return ''
  const date = new Date(timeStr)
  const now = new Date()
  const diff = now.getTime() - date.getTime()

  if (diff < 60000) return '刚刚'
  if (diff < 3600000) return `${Math.floor(diff / 60000)}分钟前`
  if (diff < 86400000) return `${Math.floor(diff / 3600000)}小时前`

  return date.toLocaleDateString('zh-CN', { month: '2-digit', day: '2-digit' })
}

async function loadDailyTasks() {
  loadingTasks.value = true
  try {
    const res = await getDailyTasks(props.userId)
    dailyTasks.value = res.data
  } catch (error) {
    console.error('Failed to load daily tasks:', error)
  } finally {
    loadingTasks.value = false
  }
}

async function loadSummary() {
  try {
    const res = await getPointsSummary(props.userId)
    summary.value = res.data
  } catch (error) {
    console.error('Failed to load summary:', error)
  }
}

async function loadHistory() {
  loadingHistory.value = true
  try {
    const res = await getPointsHistory(props.userId, 10)
    history.value = res.data
  } catch (error) {
    console.error('Failed to load history:', error)
  } finally {
    loadingHistory.value = false
  }
}

onMounted(() => {
  loadDailyTasks()
  loadSummary()
  loadHistory()
})
</script>

<style lang="scss" scoped>
.empty-state {
  text-align: center;
  padding: 32px 0;
  color: #999;

  p {
    margin: 0;
  }
}

.stats-list {
  .stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 0;
    border-bottom: 1px solid #eee;

    &:last-child {
      border-bottom: none;
    }

    .stat-value {
      font-size: 20px;
      font-weight: 700;
      color: #667eea;
    }

    .stat-label {
      font-size: 14px;
      color: #666;
    }
  }
}

.history-list {
  max-height: 400px;
  overflow-y: auto;
}

.history-item {
  display: flex;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid #f0f0f0;

  &:last-child {
    border-bottom: none;
  }
}

.history-icon {
  min-width: 60px;
  padding: 4px 8px;
  border-radius: 4px;
  text-align: center;
  font-weight: 600;

  &.income {
    background: #e8f5e9;
    color: #4caf50;
  }

  &.expense {
    background: #ffebee;
    color: #f44336;
  }
}

.history-info {
  flex: 1;
  margin-left: 12px;
  display: flex;
  flex-direction: column;

  .history-desc {
    font-size: 14px;
    color: #333;
  }

  .history-time {
    font-size: 12px;
    color: #999;
    margin-top: 4px;
  }
}

.task-list {
  .task-item {
    display: flex;
    align-items: center;
    padding: 16px 0;
    border-bottom: 1px solid #eee;

    &:last-child {
      border-bottom: none;
    }
  }

  .task-info {
    flex: 1;
    display: flex;
    flex-direction: column;

    .task-name {
      font-size: 15px;
      font-weight: 600;
      color: #333;
    }

    .task-desc {
      font-size: 13px;
      color: #999;
      margin-top: 4px;
    }
  }

  .task-progress {
    min-width: 80px;
    text-align: center;

    .progress-text {
      font-size: 14px;
      color: #666;
    }
  }

  .task-reward {
    min-width: 100px;
    text-align: right;
    margin-right: 16px;

    .reward-amount {
      font-size: 16px;
      font-weight: 700;
      color: #667eea;
    }

    .reward-unit {
      font-size: 12px;
      color: #999;
      margin-left: 4px;
    }
  }
}
</style>
