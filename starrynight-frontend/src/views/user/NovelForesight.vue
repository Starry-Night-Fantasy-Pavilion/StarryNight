<template>
  <div class="novel-foresight page-container">
    <div class="page-header">
      <h2>🎯 AI伏笔管理器</h2>
      <div class="header-actions">
        <el-button @click="handleScan">
          <el-icon><Search /></el-icon>
          扫描伏笔
        </el-button>
        <el-button type="primary" @click="handleAiGenerate">
          <el-icon><MagicStick /></el-icon>
          AI智能生成伏笔
        </el-button>
      </div>
    </div>

    <div class="page-content">
      <div class="foresight-stats">
        <el-row :gutter="16">
          <el-col :span="6">
            <div class="stat-card">
              <span class="stat-value">{{ stats.total }}</span>
              <span class="stat-label">伏笔总数</span>
            </div>
          </el-col>
          <el-col :span="6">
            <div class="stat-card warning">
              <span class="stat-value">{{ stats.unresolved }}</span>
              <span class="stat-label">待回收</span>
            </div>
          </el-col>
          <el-col :span="6">
            <div class="stat-card success">
              <span class="stat-value">{{ stats.resolved }}</span>
              <span class="stat-label">已回收</span>
            </div>
          </el-col>
          <el-col :span="6">
            <div class="stat-card danger">
              <span class="stat-value">{{ stats.expired }}</span>
              <span class="stat-label">过期未回收</span>
            </div>
          </el-col>
        </el-row>
      </div>

      <div class="foresight-filters">
        <el-radio-group v-model="filterStatus" @change="handleFilter">
          <el-radio-button label="all">全部</el-radio-button>
          <el-radio-button label="unresolved">待回收</el-radio-button>
          <el-radio-button label="resolved">已回收</el-radio-button>
          <el-radio-button label="expired">过期</el-radio-button>
        </el-radio-group>
        <el-input
          v-model="searchKeyword"
          placeholder="搜索伏笔..."
          style="width: 200px"
          clearable
        />
      </div>

      <div class="foresight-list">
        <el-table :data="filteredForesights" stripe>
          <el-table-column label="伏笔内容" min-width="200">
            <template #default="{ row }">
              <div class="foresight-content">
                <span class="foresight-text">{{ row.content }}</span>
                <el-tag v-if="row.isExpired" size="small" type="danger">已过期</el-tag>
                <el-tag v-if="row.isResolved" size="small" type="success">已回收</el-tag>
              </div>
            </template>
          </el-table-column>
          <el-table-column label="出现位置" width="150">
            <template #default="{ row }">
              <span>第{{ row.appearChapter }}章</span>
            </template>
          </el-table-column>
          <el-table-column label="预期回收" width="150">
            <template #default="{ row }">
              <span>第{{ row.expectedChapter }}章</span>
            </template>
          </el-table-column>
          <el-table-column label="关联元素" width="180">
            <template #default="{ row }">
              <div class="related-items">
                <el-tag
                  v-for="item in row.relatedItems"
                  :key="item"
                  size="small"
                  class="related-tag"
                >
                  {{ item }}
                </el-tag>
              </div>
            </template>
          </el-table-column>
          <el-table-column label="状态" width="100">
            <template #default="{ row }">
              <el-progress
                :percentage="row.resolvedPercentage"
                :status="getProgressStatus(row)"
                :stroke-width="6"
              />
            </template>
          </el-table-column>
          <el-table-column label="操作" width="180" fixed="right">
            <template #default="{ row }">
              <el-button size="small" type="primary" link @click="handleMarkResolved(row)">
                {{ row.isResolved ? '取消回收' : '标记回收' }}
              </el-button>
              <el-button size="small" link @click="handleEdit(row)">编辑</el-button>
              <el-popconfirm title="确定删除？" @confirm="handleDelete(row)">
                <template #reference>
                  <el-button size="small" link type="danger">删除</el-button>
                </template>
              </el-popconfirm>
            </template>
          </el-table-column>
        </el-table>
      </div>

      <div class="ai-analysis">
        <h3>🔮 AI伏笔分析</h3>
        <el-card shadow="hover">
          <div class="analysis-content">
            <p>根据当前作品分析：</p>
            <ul>
              <li>建议在第15-20章之间回收"神秘老者"相关伏笔</li>
              <li>发现3处可能遗漏的伏笔线索，建议补充说明</li>
              <li>"家族宝藏"伏笔回收点建议延后至第30章以增加悬念</li>
            </ul>
            <el-button size="small" type="primary" @click="handleApplyAnalysis">
              应用分析建议
            </el-button>
          </div>
        </el-card>
      </div>
    </div>

    <el-dialog v-model="showEditDialog" title="编辑伏笔" width="600px" destroy-on-close>
      <el-form :model="foresightForm" label-width="100px">
        <el-form-item label="伏笔内容" required>
          <el-input v-model="foresightForm.content" type="textarea" :rows="3" />
        </el-form-item>
        <el-form-item label="出现章节">
          <el-input-number v-model="foresightForm.appearChapter" :min="1" />
        </el-form-item>
        <el-form-item label="预期回收章节">
          <el-input-number v-model="foresightForm.expectedChapter" :min="1" />
        </el-form-item>
        <el-form-item label="关联元素">
          <el-select v-model="foresightForm.relatedItems" multiple placeholder="选择关联元素" style="width: 100%">
            <el-option label="角色" value="character" />
            <el-option label="物品" value="item" />
            <el-option label="事件" value="event" />
            <el-option label="地点" value="location" />
          </el-select>
        </el-form-item>
        <el-form-item label="备注">
          <el-input v-model="foresightForm.note" type="textarea" :rows="2" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showEditDialog = false">取消</el-button>
        <el-button type="primary" @click="handleSave">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import { Search, MagicStick } from '@element-plus/icons-vue'

interface Foresight {
  id: number
  content: string
  appearChapter: number
  expectedChapter: number
  relatedItems: string[]
  isResolved: boolean
  isExpired: boolean
  resolvedPercentage: number
  note?: string
}

const route = useRoute()
const novelId = computed(() => route.params.id as string)

const filterStatus = ref('all')
const searchKeyword = ref('')
const showEditDialog = ref(false)
const editingForesight = ref<Foresight | null>(null)

const stats = reactive({
  total: 8,
  unresolved: 5,
  resolved: 2,
  expired: 1
})

const foresights = ref<Foresight[]>([
  {
    id: 1,
    content: '神秘老者交给主角的玉佩',
    appearChapter: 3,
    expectedChapter: 20,
    relatedItems: ['角色:神秘老者', '物品:玉佩'],
    isResolved: false,
    isExpired: false,
    resolvedPercentage: 0
  },
  {
    id: 2,
    content: '主角家族被灭门的真相',
    appearChapter: 5,
    expectedChapter: 50,
    relatedItems: ['角色:主角家族', '事件:灭门'],
    isResolved: false,
    isExpired: false,
    resolvedPercentage: 30
  },
  {
    id: 3,
    content: '女主角的特殊体质',
    appearChapter: 8,
    expectedChapter: 15,
    relatedItems: ['角色:女主角', '物品:血脉'],
    isResolved: true,
    isExpired: false,
    resolvedPercentage: 100
  }
])

const filteredForesights = computed(() => {
  let result = foresights.value

  if (filterStatus.value === 'unresolved') {
    result = result.filter(f => !f.isResolved && !f.isExpired)
  } else if (filterStatus.value === 'resolved') {
    result = result.filter(f => f.isResolved)
  } else if (filterStatus.value === 'expired') {
    result = result.filter(f => f.isExpired)
  }

  if (searchKeyword.value) {
    const keyword = searchKeyword.value.toLowerCase()
    result = result.filter(f =>
      f.content.toLowerCase().includes(keyword) ||
      f.relatedItems.some(item => item.toLowerCase().includes(keyword))
    )
  }

  return result
})

const foresightForm = reactive({
  content: '',
  appearChapter: 1,
  expectedChapter: 10,
  relatedItems: [] as string[],
  note: ''
})

function getProgressStatus(foresight: Foresight): string | undefined {
  if (foresight.isResolved) return 'success'
  if (foresight.isExpired) return 'exception'
  return undefined
}

function handleFilter() {
  console.log('Filter:', filterStatus.value)
}

function handleScan() {
  ElMessage.info('正在扫描伏笔...')
}

function handleAiGenerate() {
  ElMessage.info('AI生成伏笔功能开发中')
}

function handleMarkResolved(foresight: Foresight) {
  foresight.isResolved = !foresight.isResolved
  foresight.resolvedPercentage = foresight.isResolved ? 100 : 0
  ElMessage.success(foresight.isResolved ? '已标记为回收' : '已取消回收')
}

function handleEdit(foresight: Foresight) {
  editingForesight.value = foresight
  Object.assign(foresightForm, {
    content: foresight.content,
    appearChapter: foresight.appearChapter,
    expectedChapter: foresight.expectedChapter,
    relatedItems: [...foresight.relatedItems],
    note: foresight.note
  })
  showEditDialog.value = true
}

function handleDelete(foresight: Foresight) {
  const index = foresights.value.findIndex(f => f.id === foresight.id)
  if (index !== -1) {
    foresights.value.splice(index, 1)
    ElMessage.success('删除成功')
  }
}

function handleSave() {
  if (!foresightForm.content) {
    ElMessage.warning('请输入伏笔内容')
    return
  }

  if (editingForesight.value) {
    const index = foresights.value.findIndex(f => f.id === editingForesight.value!.id)
    if (index !== -1) {
      foresights.value[index] = {
        ...editingForesight.value,
        ...foresightForm
      }
    }
  }

  showEditDialog.value = false
  ElMessage.success('保存成功')
}

function handleApplyAnalysis() {
  ElMessage.success('分析建议已应用')
}
</script>

<style lang="scss" scoped>
.novel-foresight {
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

  .foresight-stats {
    margin-bottom: 24px;
  }

  .stat-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 20px;
    background: var(--el-bg-color);
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);

    .stat-value {
      font-size: 28px;
      font-weight: 700;
      color: var(--el-text-color-primary);
    }

    .stat-label {
      font-size: 13px;
      color: var(--el-text-color-secondary);
      margin-top: 4px;
    }

    &.warning .stat-value { color: var(--el-color-warning); }
    &.success .stat-value { color: var(--el-color-success); }
    &.danger .stat-value { color: var(--el-color-danger); }
  }

  .foresight-filters {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
  }

  .foresight-list {
    background: var(--el-bg-color);
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 24px;

    .foresight-content {
      display: flex;
      align-items: center;
      gap: 8px;

      .foresight-text {
        flex: 1;
      }
    }

    .related-items {
      display: flex;
      flex-wrap: wrap;
      gap: 4px;

      .related-tag {
        margin-right: 4px;
      }
    }
  }

  .ai-analysis {
    background: var(--el-bg-color);
    border-radius: 12px;
    padding: 20px;

    h3 {
      margin: 0 0 16px;
      font-size: 16px;
    }

    .analysis-content {
      p {
        margin: 0 0 12px;
        font-weight: 500;
      }

      ul {
        margin: 0 0 16px;
        padding-left: 20px;

        li {
          margin-bottom: 8px;
          line-height: 1.6;
        }
      }
    }
  }
}
</style>
