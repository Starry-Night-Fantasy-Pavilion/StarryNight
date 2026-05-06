<template>
  <div class="novel-engine page-container">
    <div class="page-header">
      <h2>⚡ 星夜引擎</h2>
      <div class="header-actions">
        <el-button @click="handleRefresh">
          <el-icon><Refresh /></el-icon>
          刷新状态
        </el-button>
        <el-button type="primary" @click="showSettingsDialog = true">
          <el-icon><Setting /></el-icon>
          引擎设置
        </el-button>
      </div>
    </div>

    <div class="page-content">
      <div class="engine-status">
        <el-row :gutter="16">
          <el-col :span="6">
            <div class="status-card">
              <div class="status-icon" :class="engineStatus.status">
                <el-icon :size="32"><Cpu /></el-icon>
              </div>
              <div class="status-info">
                <span class="status-label">引擎状态</span>
                <span class="status-value" :class="engineStatus.status">
                  {{ getStatusText(engineStatus.status) }}
                </span>
              </div>
            </div>
          </el-col>
          <el-col :span="6">
            <div class="status-card">
              <div class="status-info">
                <span class="status-label">处理队列</span>
                <span class="status-value">{{ engineStatus.queueSize }}</span>
              </div>
            </div>
          </el-col>
          <el-col :span="6">
            <div class="status-card">
              <div class="status-info">
                <span class="status-label">今日处理</span>
                <span class="status-value">{{ engineStatus.todayProcessed }}</span>
              </div>
            </div>
          </el-col>
          <el-col :span="6">
            <div class="status-card">
              <div class="status-info">
                <span class="status-label">API额度</span>
                <span class="status-value">{{ getQuotaDisplay() }}</span>
                <el-progress :percentage="engineStatus.quotaUsed" :stroke-width="4" class="quota-progress" />
              </div>
            </div>
          </el-col>
        </el-row>
      </div>

      <div class="engine-modules">
        <h3>🚀 引擎模块</h3>
        <el-row :gutter="16">
          <el-col :span="8">
            <div class="module-card" :class="{ active: modules.plotGenerator.active }">
              <div class="module-header">
                <el-icon :size="24"><Document /></el-icon>
                <span class="module-name">剧情生成器</span>
                <el-switch v-model="modules.plotGenerator.enabled" @change="toggleModule('plotGenerator')" />
              </div>
              <p class="module-desc">根据大纲自动生成详细剧情</p>
              <div class="module-stats">
                <span>已生成：{{ modules.plotGenerator.generated }}</span>
              </div>
            </div>
          </el-col>
          <el-col :span="8">
            <div class="module-card" :class="{ active: modules.worldBuilder.active }">
              <div class="module-header">
                <el-icon :size="24"><Location /></el-icon>
                <span class="module-name">世界构建器</span>
                <el-switch v-model="modules.worldBuilder.enabled" @change="toggleModule('worldBuilder')" />
              </div>
              <p class="module-desc">自动构建和完善世界观设定</p>
              <div class="module-stats">
                <span>已构建：{{ modules.worldBuilder.generated }}项</span>
              </div>
            </div>
          </el-col>
          <el-col :span="8">
            <div class="module-card" :class="{ active: modules.characterAI.active }">
              <div class="module-header">
                <el-icon :size="24"><User /></el-icon>
                <span class="module-name">角色AI</span>
                <el-switch v-model="modules.characterAI.enabled" @change="toggleModule('characterAI')" />
              </div>
              <p class="module-desc">智能分析和完善角色设定</p>
              <div class="module-stats">
                <span>已分析：{{ modules.characterAI.generated }}个</span>
              </div>
            </div>
          </el-col>
          <el-col :span="8">
            <div class="module-card" :class="{ active: modules.consistencyCheck.active }">
              <div class="module-header">
                <el-icon :size="24"><Connection /></el-icon>
                <span class="module-name">一致性检查</span>
                <el-switch v-model="modules.consistencyCheck.enabled" @change="toggleModule('consistencyCheck')" />
              </div>
              <p class="module-desc">自动检测剧情和角色的一致性问题</p>
              <div class="module-stats">
                <span>检测到：{{ modules.consistencyCheck.issues }}个问题</span>
              </div>
            </div>
          </el-col>
          <el-col :span="8">
            <div class="module-card" :class="{ active: modules.pacingOptimizer.active }">
              <div class="module-header">
                <el-icon :size="24"><TrendCharts /></el-icon>
                <span class="module-name">节奏优化</span>
                <el-switch v-model="modules.pacingOptimizer.enabled" @change="toggleModule('pacingOptimizer')" />
              </div>
              <p class="module-desc">分析并优化叙事节奏</p>
              <div class="module-stats">
                <span>已优化：{{ modules.pacingOptimizer.generated }}处</span>
              </div>
            </div>
          </el-col>
          <el-col :span="8">
            <div class="module-card" :class="{ active: modules.autoForesight.active }">
              <div class="module-header">
                <el-icon :size="24"><Warning /></el-icon>
                <span class="module-name">伏笔管理</span>
                <el-switch v-model="modules.autoForesight.enabled" @change="toggleModule('autoForesight')" />
              </div>
              <p class="module-desc">智能伏笔设置和回收提醒</p>
              <div class="module-stats">
                <span>伏笔：{{ modules.autoForesight.foresights }}个</span>
              </div>
            </div>
          </el-col>
        </el-row>
      </div>

      <div class="engine-tasks">
        <h3>📋 当前任务</h3>
        <el-table :data="tasks" stripe>
          <el-table-column label="任务名称" min-width="150">
            <template #default="{ row }">
              <span>{{ row.name }}</span>
            </template>
          </el-table-column>
          <el-table-column label="模块" width="120">
            <template #default="{ row }">
              <el-tag size="small">{{ row.module }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column label="状态" width="100">
            <template #default="{ row }">
              <el-tag :type="getTaskType(row.status)" size="small">
                {{ getTaskStatus(row.status) }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column label="进度" width="150">
            <template #default="{ row }">
              <el-progress :percentage="row.progress" :stroke-width="6" />
            </template>
          </el-table-column>
          <el-table-column label="创建时间" width="180">
            <template #default="{ row }">
              {{ formatTime(row.createTime) }}
            </template>
          </el-table-column>
          <el-table-column label="操作" width="120">
            <template #default="{ row }">
              <el-button
                v-if="row.status === 'running'"
                size="small"
                link
                type="danger"
                @click="handleCancelTask(row)"
              >
                取消
              </el-button>
              <el-button
                v-if="row.status === 'completed'"
                size="small"
                link
                type="primary"
                @click="handleViewResult(row)"
              >
                查看
              </el-button>
            </template>
          </el-table-column>
        </el-table>
      </div>

      <div class="engine-log">
        <h3>📜 引擎日志</h3>
        <div class="log-container">
          <div v-for="(log, index) in logs" :key="index" class="log-item" :class="log.level">
            <span class="log-time">{{ log.time }}</span>
            <span class="log-level">[{{ log.level.toUpperCase() }}]</span>
            <span class="log-message">{{ log.message }}</span>
          </div>
        </div>
      </div>
    </div>

    <el-dialog v-model="showSettingsDialog" title="引擎设置" width="600px" destroy-on-close>
      <el-form :model="settingsForm" label-width="120px">
        <el-form-item label="并发任务数">
          <el-input-number v-model="settingsForm.maxConcurrent" :min="1" :max="5" />
        </el-form-item>
        <el-form-item label="生成质量">
          <el-radio-group v-model="settingsForm.quality">
            <el-radio label="fast">快速（低延迟）</el-radio>
            <el-radio label="balanced">平衡</el-radio>
            <el-radio label="quality">高质量（高延迟）</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="自动保存">
          <el-switch v-model="settingsForm.autoSave" />
        </el-form-item>
        <el-form-item label="保存间隔">
          <el-input-number v-model="settingsForm.saveInterval" :min="30" :max="300" :step="30" />
          <span class="interval-hint">秒</span>
        </el-form-item>
        <el-form-item label="API Key">
          <el-input v-model="settingsForm.apiKey" type="password" show-password />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showSettingsDialog = false">取消</el-button>
        <el-button type="primary" @click="handleSaveSettings">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import { Refresh, Setting, Cpu, Document, Location, User, Connection, TrendCharts, Warning } from '@element-plus/icons-vue'
import { getTenantQuota, type TenantQuota } from '@/api/tenant'

const route = useRoute()
const novelId = ref(route.params.id as string)

const tenantQuota = ref<TenantQuota | null>(null)

const engineStatus = reactive({
  status: 'running',
  queueSize: 3,
  todayProcessed: 156,
  quotaUsed: 0
})

const modules = reactive({
  plotGenerator: { enabled: true, active: true, generated: 45 },
  worldBuilder: { enabled: true, active: true, generated: 128 },
  characterAI: { enabled: true, active: true, generated: 23 },
  consistencyCheck: { enabled: true, active: false, issues: 5 },
  pacingOptimizer: { enabled: false, active: false, generated: 12 },
  autoForesight: { enabled: true, active: true, foresights: 18 }
})

const tasks = ref([
  {
    id: 1,
    name: '生成第15-20章详细剧情',
    module: '剧情生成器',
    status: 'running',
    progress: 65,
    createTime: '2026-05-02 10:30:00'
  },
  {
    id: 2,
    name: '完善世界观设定',
    module: '世界构建器',
    status: 'completed',
    progress: 100,
    createTime: '2026-05-02 09:15:00'
  },
  {
    id: 3,
    name: '角色一致性检查',
    module: '一致性检查',
    status: 'pending',
    progress: 0,
    createTime: '2026-05-02 11:00:00'
  }
])

const logs = ref([
  { time: '11:23:45', level: 'info', message: '引擎状态检测正常' },
  { time: '11:23:30', level: 'info', message: '任务#1 进度更新: 65%' },
  { time: '11:22:15', level: 'success', message: '世界构建器完成: 新增3项设定' },
  { time: '11:20:00', level: 'warning', message: '一致性检查发现5个潜在问题' },
  { time: '11:15:30', level: 'info', message: '剧情生成器开始生成第15章' }
])

const showSettingsDialog = ref(false)
const settingsForm = reactive({
  maxConcurrent: 3,
  quality: 'balanced',
  autoSave: true,
  saveInterval: 60,
  apiKey: ''
})

async function loadTenantQuota() {
  try {
    const res = await getTenantQuota()
    if (res.data?.data) {
      tenantQuota.value = res.data.data
      engineStatus.quotaUsed = Math.round(
        (tenantQuota.value.currentApiCalls / tenantQuota.value.apiCallsLimit) * 100
      )
    }
  } catch (e) {
    console.error('Failed to load tenant quota', e)
  }
}

function getQuotaDisplay() {
  if (!tenantQuota.value) return `${engineStatus.quotaUsed}%`
  return `${tenantQuota.value.currentApiCalls} / ${tenantQuota.value.apiCallsLimit}`
}

function getStatusText(status: string): string {
  const map: Record<string, string> = {
    running: '运行中',
    idle: '空闲',
    error: '错误'
  }
  return map[status] || status
}

function getTaskType(status: string): string {
  const map: Record<string, string> = {
    pending: 'info',
    running: 'primary',
    completed: 'success',
    failed: 'danger'
  }
  return map[status] || 'info'
}

function getTaskStatus(status: string): string {
  const map: Record<string, string> = {
    pending: '等待中',
    running: '进行中',
    completed: '已完成',
    failed: '失败'
  }
  return map[status] || status
}

function formatTime(time: string): string {
  return new Date(time).toLocaleString()
}

function toggleModule(moduleName: string) {
  console.log('Toggle module:', moduleName)
}

async function handleRefresh() {
  await loadTenantQuota()
  ElMessage.success('状态已刷新')
}

function handleCancelTask(task: any) {
  task.status = 'failed'
  ElMessage.info('任务已取消')
}

function handleViewResult(task: any) {
  console.log('View result:', task)
}

function handleSaveSettings() {
  showSettingsDialog.value = false
  ElMessage.success('设置已保存')
}

onMounted(() => {
  loadTenantQuota()
})
</script>

<style lang="scss" scoped>
.novel-engine {
  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;

    h2 {
      margin: 0;
      font-size: 20px;
    }

    .header-actions {
      display: flex;
      gap: 12px;
    }
  }

  .engine-status {
    margin-bottom: 24px;
  }

  .status-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px;
    background: var(--el-bg-color);
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);

    .status-icon {
      width: 56px;
      height: 56px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--el-color-primary-light-9);
      color: var(--el-color-primary);

      &.running {
        background: var(--el-color-success-light-9);
        color: var(--el-color-success);
      }

      &.error {
        background: var(--el-color-danger-light-9);
        color: var(--el-color-danger);
      }
    }

    .status-info {
      display: flex;
      flex-direction: column;
      gap: 4px;

      .status-label {
        font-size: 13px;
        color: var(--el-text-color-secondary);
      }

      .status-value {
        font-size: 20px;
        font-weight: 700;
        color: var(--el-text-color-primary);

        &.running { color: var(--el-color-success); }
        &.error { color: var(--el-color-danger); }
      }

      .quota-progress {
        margin-top: 4px;
        width: 100px;
      }
    }
  }

  .engine-modules {
    background: var(--el-bg-color);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 24px;

    h3 {
      margin: 0 0 16px;
      font-size: 16px;
    }
  }

  .module-card {
    padding: 16px;
    border: 1px solid var(--el-border-color-light);
    border-radius: 8px;
    margin-bottom: 16px;
    transition: all 0.2s;

    &:hover {
      border-color: var(--el-color-primary-light-5);
    }

    &.active {
      border-color: var(--el-color-primary);
      background: var(--el-color-primary-light-9);
    }

    .module-header {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 8px;

      .module-name {
        flex: 1;
        font-weight: 600;
        font-size: 15px;
      }
    }

    .module-desc {
      margin: 0 0 12px;
      font-size: 13px;
      color: var(--el-text-color-secondary);
    }

    .module-stats {
      font-size: 12px;
      color: var(--el-text-color-secondary);
    }
  }

  .engine-tasks,
  .engine-log {
    background: var(--el-bg-color);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 24px;

    h3 {
      margin: 0 0 16px;
      font-size: 16px;
    }
  }

  .log-container {
    max-height: 200px;
    overflow-y: auto;
    font-family: 'Monaco', 'Menlo', monospace;
    font-size: 12px;
    background: var(--el-fill-color-dark);
    border-radius: 4px;
    padding: 12px;
  }

  .log-item {
    display: flex;
    gap: 12px;
    margin-bottom: 8px;
    color: var(--el-text-color-regular);

    &.warning { color: var(--el-color-warning); }
    &.error { color: var(--el-color-danger); }
    &.success { color: var(--el-color-success); }

    .log-time {
      color: var(--el-text-color-secondary);
      flex-shrink: 0;
    }

    .log-level {
      flex-shrink: 0;
    }

    .log-message {
      flex: 1;
    }
  }

  .interval-hint {
    margin-left: 8px;
    color: var(--el-text-color-secondary);
  }
}
</style>
