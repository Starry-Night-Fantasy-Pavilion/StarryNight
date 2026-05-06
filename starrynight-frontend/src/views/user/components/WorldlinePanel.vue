<template>
  <div class="worldline-panel">
    <div class="panel-header">
      <el-button type="primary" @click="showCreateDialog = true">
        <el-icon><Plus /></el-icon>
        新建世界线
      </el-button>
      <el-button @click="importSettings">
        <el-icon><Upload /></el-icon>
        导入官方设定
      </el-button>
    </div>

    <el-row :gutter="16">
      <el-col :span="8">
        <el-card>
          <template #header>
            <span>世界线列表</span>
          </template>
          <div class="worldline-list">
            <div
              v-for="wl in worldlines"
              :key="wl.id"
              class="worldline-item"
              :class="{ active: selectedWorldline?.id === wl.id }"
              @click="selectWorldline(wl)"
            >
              <div class="worldline-icon">{{ getStatusIcon(wl.status) }}</div>
              <div class="worldline-info">
                <span class="name">{{ wl.name }}</span>
                <span class="source">{{ wl.source }}</span>
              </div>
              <el-tag size="small" :type="getStatusType(wl.status)">{{ wl.status }}</el-tag>
            </div>
            <div v-if="worldlines.length === 0" class="empty-list">
              <p>暂无世界线</p>
            </div>
          </div>
        </el-card>
      </el-col>

      <el-col :span="16">
        <el-card v-if="selectedWorldline">
          <template #header>
            <span>{{ selectedWorldline.name }}</span>
            <el-button size="small" type="primary" @click="editWorldline">编辑</el-button>
          </template>
          <el-descriptions :column="2" border>
            <el-descriptions-item label="名称">{{ selectedWorldline.name }}</el-descriptions-item>
            <el-descriptions-item label="来源">{{ selectedWorldline.source }}</el-descriptions-item>
            <el-descriptions-item label="状态">{{ selectedWorldline.status }}</el-descriptions-item>
            <el-descriptions-item label="描述" :span="2">{{ selectedWorldline.description || '无' }}</el-descriptions-item>
          </el-descriptions>

          <div class="cross-world-section">
            <h4>跨世界线规则</h4>
            <el-descriptions :column="1" border size="small">
              <el-descriptions-item label="可导入角色">
                <el-tag :type="selectedWorldline.crossWorldRules.canImportCharacters ? 'success' : 'danger'" size="small">
                  {{ selectedWorldline.crossWorldRules.canImportCharacters ? '允许' : '禁止' }}
                </el-tag>
              </el-descriptions-item>
              <el-descriptions-item label="可导入道具">
                <el-tag :type="selectedWorldline.crossWorldRules.canImportItems ? 'success' : 'danger'" size="small">
                  {{ selectedWorldline.crossWorldRules.canImportItems ? '允许' : '禁止' }}
                </el-tag>
              </el-descriptions-item>
              <el-descriptions-item label="冲突检测">
                <el-tag :type="selectedWorldline.crossWorldRules.conflictDetection ? 'success' : 'info'" size="small">
                  {{ selectedWorldline.crossWorldRules.conflictDetection ? '启用' : '禁用' }}
                </el-tag>
              </el-descriptions-item>
            </el-descriptions>
          </div>

          <div v-if="selectedWorldline.fusionRules" class="fusion-rules-section">
            <h4>融合规则</h4>
            <el-descriptions :column="1" border size="small">
              <el-descriptions-item label="允许融合的世界线">
                {{ selectedWorldline.fusionRules.allowedWorldlines.length }} 条
              </el-descriptions-item>
              <el-descriptions-item label="冲突解决">
                {{ selectedWorldline.fusionRules.conflictResolution }}
              </el-descriptions-item>
            </el-descriptions>
          </div>
        </el-card>
        <el-card v-else>
          <el-empty description="请选择要查看的世界线" />
        </el-card>
      </el-col>
    </el-row>

    <el-dialog v-model="showCreateDialog" title="创建世界线" width="500px">
      <el-form :model="worldlineForm" label-width="100px">
        <el-form-item label="名称" required>
          <el-input v-model="worldlineForm.name" placeholder="如: 主世界线" />
        </el-form-item>
        <el-form-item label="来源" required>
          <el-input v-model="worldlineForm.source" placeholder="如: 原著" />
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="worldlineForm.status" style="width: 100%">
            <el-option value="active" label="活动中" />
            <el-option value="archived" label="已归档" />
            <el-option value="if_branch" label="IF分支" />
          </el-select>
        </el-form-item>
        <el-form-item label="描述">
          <el-input v-model="worldlineForm.description" type="textarea" :rows="3" />
        </el-form-item>
        <el-form-item label="可导入角色">
          <el-switch v-model="worldlineForm.crossWorldRules.canImportCharacters" />
        </el-form-item>
        <el-form-item label="可导入道具">
          <el-switch v-model="worldlineForm.crossWorldRules.canImportItems" />
        </el-form-item>
        <el-form-item label="冲突检测">
          <el-switch v-model="worldlineForm.crossWorldRules.conflictDetection" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showCreateDialog = false">取消</el-button>
        <el-button type="primary" @click="submitWorldline">创建</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Plus, Upload } from '@element-plus/icons-vue'
import {
  listWorldlines,
  createWorldline,
  updateWorldline,
  deleteWorldline,
  type Worldline
} from '@/api/tokusatsu'

interface Props {
  novelId: number
}

const props = defineProps<Props>()

const worldlines = ref<Worldline[]>([])
const selectedWorldline = ref<Worldline | null>(null)
const showCreateDialog = ref(false)

const worldlineForm = reactive<{
  name: string
  source: string
  status: 'active' | 'archived' | 'if_branch'
  description: string
  crossWorldRules: {
    canImportCharacters: boolean
    canImportItems: boolean
    conflictDetection: boolean
  }
}>({
  name: '',
  source: '',
  status: 'active',
  description: '',
  crossWorldRules: {
    canImportCharacters: false,
    canImportItems: false,
    conflictDetection: true
  }
})

function getStatusIcon(status: string) {
  const icons: Record<string, string> = {
    active: '🌍',
    archived: '📁',
    if_branch: '🔀'
  }
  return icons[status] || '🌍'
}

function getStatusType(status: string) {
  const types: Record<string, string> = {
    active: 'success',
    archived: 'info',
    if_branch: 'warning'
  }
  return types[status] || ''
}

async function loadWorldlines() {
  try {
    const res = await listWorldlines(props.novelId)
    worldlines.value = res.data || []
  } catch (e) {
    worldlines.value = []
  }
}

function selectWorldline(wl: Worldline) {
  selectedWorldline.value = wl
}

function editWorldline() {
  ElMessage.info('编辑功能开发中')
}

async function submitWorldline() {
  if (!worldlineForm.name || !worldlineForm.source) {
    ElMessage.warning('请填写必填项')
    return
  }
  try {
    await createWorldline(props.novelId, {
      ...worldlineForm,
      createdAt: new Date().toISOString()
    } as any)
    ElMessage.success('创建成功')
    showCreateDialog.value = false
    await loadWorldlines()
  } catch (e) {
    ElMessage.error('创建失败')
  }
}

function importSettings() {
  ElMessage.info('导入官方设定功能开发中')
}

onMounted(() => {
  loadWorldlines()
})
</script>

<style lang="scss" scoped>
.worldline-panel {
  .panel-header {
    display: flex;
    gap: 12px;
    margin-bottom: 16px;
  }

  .worldline-list {
    .worldline-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px;
      cursor: pointer;
      border-radius: 6px;
      transition: all 0.2s;

      &:hover {
        background: var(--el-fill-color-light);
      }

      &.active {
        background: var(--el-color-primary-light-9);
        border-left: 3px solid var(--el-color-primary);
      }

      .worldline-icon {
        font-size: 24px;
      }

      .worldline-info {
        flex: 1;
        display: flex;
        flex-direction: column;

        .name {
          font-weight: 500;
        }

        .source {
          font-size: 12px;
          color: var(--el-text-color-secondary);
        }
      }
    }

    .empty-list {
      text-align: center;
      padding: 20px;
      color: var(--el-text-color-secondary);
    }
  }

  .cross-world-section,
  .fusion-rules-section {
    margin-top: 16px;

    h4 {
      margin: 0 0 8px 0;
      font-size: 14px;
      color: var(--el-text-color-primary);
    }
  }
}
</style>
