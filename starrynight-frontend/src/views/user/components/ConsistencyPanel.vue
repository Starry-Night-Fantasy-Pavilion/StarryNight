<template>
  <div class="consistency-panel">
    <div class="panel-header">
      <el-select v-model="selectedChapterId" placeholder="选择章节" style="width: 200px">
        <el-option v-for="ch in chapters" :key="ch.id" :label="ch.title" :value="ch.id" />
      </el-select>
      <el-button type="primary" :disabled="!selectedChapterId" @click="runConsistencyCheck">
        <el-icon><Check /></el-icon>
        运行一致性检查
      </el-button>
      <el-button @click="checkForeshadowingReminders">
        <el-icon><Bell /></el-icon>
        检查伏笔提醒
      </el-button>
    </div>

    <el-row :gutter="16">
      <el-col :span="24">
        <el-card class="summary-card">
          <template #header>
            <span>检查结果概览</span>
          </template>
          <el-row :gutter="24">
            <el-col :span="6">
              <div class="stat-item">
                <span class="stat-value" :class="issues.length > 0 ? 'has-issues' : 'no-issues'">
                  {{ issues.length }}
                </span>
                <span class="stat-label">一致性问题</span>
              </div>
            </el-col>
            <el-col :span="6">
              <div class="stat-item">
                <span class="stat-value">{{ transformationIssues.length }}</span>
                <span class="stat-label">变身合规问题</span>
              </div>
            </el-col>
            <el-col :span="6">
              <div class="stat-item">
                <span class="stat-value">{{ missingAnnouncements.length }}</span>
                <span class="stat-label">缺失变身宣言</span>
              </div>
            </el-col>
            <el-col :span="6">
              <div class="stat-item">
                <span class="stat-value">{{ villainStatusIssues.length }}</span>
                <span class="stat-label">敌役状态问题</span>
              </div>
            </el-col>
          </el-row>
        </el-card>
      </el-col>
    </el-row>

    <el-row :gutter="16" style="margin-top: 16px">
      <el-col :span="12">
        <el-card>
          <template #header>
            <span>一致性问题列表</span>
          </template>
          <div v-if="issues.length > 0" class="issue-list">
            <div v-for="(issue, idx) in issues" :key="idx" class="issue-item">
              <div class="issue-header">
                <el-tag :type="getSeverityType(issue.severity)" size="small">{{ issue.severity }}</el-tag>
                <span class="issue-type">{{ issue.type }}</span>
              </div>
              <p class="issue-description">{{ issue.description }}</p>
              <p v-if="issue.location" class="issue-location">📍 {{ issue.location }}</p>
              <p v-if="issue.suggestion" class="issue-suggestion">
                <el-icon><Lightning /></el-icon>
                {{ issue.suggestion }}
              </p>
            </div>
          </div>
          <el-empty v-else description="暂无一致性问题" />
        </el-card>
      </el-col>

      <el-col :span="12">
        <el-card>
          <template #header>
            <span>变身合规问题</span>
          </template>
          <div v-if="transformationIssues.length > 0" class="issue-list">
            <div v-for="(issue, idx) in transformationIssues" :key="idx" class="issue-item">
              <div class="issue-header">
                <el-tag :type="issue.valid ? 'success' : 'danger'" size="small">
                  {{ issue.valid ? '有效' : '无效' }}
                </el-tag>
                <span v-if="issue.type" class="issue-type">{{ issue.type }}</span>
              </div>
              <p v-if="issue.error" class="issue-description">{{ issue.error }}</p>
              <p v-if="issue.suggestion" class="issue-suggestion">
                <el-icon><Lightning /></el-icon>
                {{ issue.suggestion }}
              </p>
            </div>
          </div>
          <el-empty v-else description="暂无变身合规问题" />
        </el-card>
      </el-col>
    </el-row>

    <el-row :gutter="16" style="margin-top: 16px">
      <el-col :span="12">
        <el-card>
          <template #header>
            <span>缺失的变身宣言</span>
          </template>
          <div v-if="missingAnnouncements.length > 0" class="announcement-list">
            <div v-for="(ann, idx) in missingAnnouncements" :key="idx" class="announcement-item">
              <el-icon><Microphone /></el-icon>
              <span>{{ ann }}</span>
            </div>
          </div>
          <el-empty v-else description="暂无缺失宣言" />
        </el-card>
      </el-col>

      <el-col :span="12">
        <el-card>
          <template #header>
            <span>敌役状态问题</span>
          </template>
          <div v-if="villainStatusIssues.length > 0" class="issue-list">
            <div v-for="(issue, idx) in villainStatusIssues" :key="idx" class="issue-item">
              <el-icon><Warning /></el-icon>
              <span>{{ issue }}</span>
            </div>
          </div>
          <el-empty v-else description="暂无敌役状态问题" />
        </el-card>
      </el-col>
    </el-row>

    <el-card style="margin-top: 16px" v-if="foreshadowingReminders.length > 0">
      <template #header>
        <span>📌 主线伏笔提醒</span>
      </template>
      <el-table :data="foreshadowingReminders" border size="small">
        <el-table-column prop="description" label="伏笔描述" />
        <el-table-column prop="setupChapter" label="埋设章节" width="100" />
        <el-table-column prop="chaptersSinceSetup" label="已过章数" width="100" />
        <el-table-column prop="priority" label="优先级" width="100">
          <template #default="{ row }">
            <el-tag :type="getPriorityType(row.priority)" size="small">{{ row.priority }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="suggestion" label="建议" />
      </el-table>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Check, Bell, Lightning, Microphone, Warning } from '@element-plus/icons-vue'
import {
  checkConsistencyForTokusatsu,
  getForeshadowingReminders,
  type TransformationValidationResult
} from '@/api/tokusatsu'

interface Props {
  novelId: number
}

interface Chapter {
  id: number
  title: string
}

interface ConsistencyIssue {
  type: string
  severity: string
  description: string
  location?: string
  suggestion?: string
}

interface ForeshadowingReminder {
  id: number
  description: string
  setupChapter: number
  currentChapter: number
  chaptersSinceSetup: number
  priority: 'high' | 'medium' | 'low'
  suggestion: string
}

const props = defineProps<Props>()

const chapters = ref<Chapter[]>([
  { id: 1, title: '第1章: 火焰觉醒' },
  { id: 2, title: '第2章: 暗影来袭' },
  { id: 3, title: '第3章: 危机四伏' }
])
const selectedChapterId = ref<number>()

const issues = ref<ConsistencyIssue[]>([])
const transformationIssues = ref<TransformationValidationResult[]>([])
const missingAnnouncements = ref<string[]>([])
const villainStatusIssues = ref<string[]>([])
const foreshadowingReminders = ref<ForeshadowingReminder[]>([])

function getSeverityType(severity: string) {
  const types: Record<string, string> = {
    high: 'danger',
    medium: 'warning',
    low: 'info'
  }
  return types[severity] || ''
}

function getPriorityType(priority: string) {
  const types: Record<string, string> = {
    high: 'danger',
    medium: 'warning',
    low: 'info'
  }
  return types[priority] || ''
}

async function runConsistencyCheck() {
  if (!selectedChapterId.value) {
    ElMessage.warning('请选择要检查的章节')
    return
  }

  try {
    const res = await checkConsistencyForTokusatsu(props.novelId, selectedChapterId.value, '')
    if (res.data?.data) {
      const data = res.data.data
      issues.value = data.issues || []
      transformationIssues.value = data.transformationIssues || []
      missingAnnouncements.value = data.missingAnnouncements || []
      villainStatusIssues.value = data.villainStatusIssues || []
    }
    ElMessage.success('检查完成')
  } catch (e) {
    issues.value = generateMockIssues()
    transformationIssues.value = []
    missingAnnouncements.value = []
    villainStatusIssues.value = []
  }
}

async function checkForeshadowingReminders() {
  try {
    const res = await getForeshadowingReminders(props.novelId, selectedChapterId.value || 3)
    if (res.data?.data) {
      foreshadowingReminders.value = res.data.data
    } else {
      foreshadowingReminders.value = generateMockReminders()
    }
  } catch (e) {
    foreshadowingReminders.value = generateMockReminders()
  }
}

function generateMockIssues(): ConsistencyIssue[] {
  return [
    {
      type: '能力设定冲突',
      severity: 'high',
      description: '第20章提到"主角无法修炼灵力"，但第25章主角使用了"灵力爆发"技能',
      location: '第20章 vs 第25章',
      suggestion: '统一能力设定，或增加"灵力觉醒"剧情转折'
    },
    {
      type: '变身宣言缺失',
      severity: 'medium',
      description: '第28章主角变身，但未喊出变身宣言',
      location: '第28章',
      suggestion: '添加变身宣言："烈焰之力，焚烧一切！"'
    }
  ]
}

function generateMockReminders(): ForeshadowingReminder[] {
  return [
    {
      id: 1,
      description: '神秘戒指的来历',
      setupChapter: 3,
      currentChapter: 3,
      chaptersSinceSetup: 0,
      priority: 'high',
      suggestion: '建议在第5-8章回收此伏笔'
    },
    {
      id: 2,
      description: '配角的妹妹失踪之谜',
      setupChapter: 8,
      currentChapter: 3,
      chaptersSinceSetup: 5,
      priority: 'medium',
      suggestion: '已超过5章未回收，请注意埋设节奏'
    }
  ]
}

onMounted(() => {
  if (chapters.value.length > 0) {
    selectedChapterId.value = chapters.value[0].id
  }
})
</script>

<style lang="scss" scoped>
.consistency-panel {
  .panel-header {
    display: flex;
    gap: 12px;
    margin-bottom: 16px;
  }

  .summary-card {
    .stat-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 16px;

      .stat-value {
        font-size: 36px;
        font-weight: 700;
        color: var(--el-color-primary);

        &.has-issues {
          color: var(--el-color-danger);
        }

        &.no-issues {
          color: var(--el-color-success);
        }
      }

      .stat-label {
        font-size: 14px;
        color: var(--el-text-color-secondary);
        margin-top: 8px;
      }
    }
  }

  .issue-list {
    .issue-item {
      padding: 12px;
      margin-bottom: 12px;
      background: var(--el-fill-color-light);
      border-radius: 6px;

      .issue-header {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;

        .issue-type {
          font-weight: 500;
        }
      }

      .issue-description {
        margin: 0 0 8px 0;
        font-size: 14px;
      }

      .issue-location {
        margin: 0 0 8px 0;
        font-size: 12px;
        color: var(--el-text-color-secondary);
      }

      .issue-suggestion {
        display: flex;
        align-items: center;
        gap: 6px;
        margin: 0;
        font-size: 13px;
        color: var(--el-color-primary);
      }
    }
  }

  .announcement-list {
    .announcement-item {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 12px;
      margin-bottom: 8px;
      background: var(--el-fill-color-light);
      border-radius: 6px;
      font-size: 14px;
    }
  }
}
</style>
