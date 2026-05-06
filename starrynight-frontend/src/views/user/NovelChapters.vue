<template>
  <div class="novel-chapters page-container">
    <div class="page-header">
      <div class="header-left">
        <h2>📚 章节管理</h2>
        <el-tag type="info">{{ chapters.length }} 章</el-tag>
      </div>
      <div class="header-actions">
        <el-radio-group v-model="viewMode" size="small">
          <el-radio-button label="table">
            <el-icon><Grid /></el-icon>
            列表
          </el-radio-button>
          <el-radio-button label="card">
            <el-icon><Menu /></el-icon>
            卡片
          </el-radio-button>
        </el-radio-group>
        <el-button @click="showStoryChart = true">
          <el-icon><TrendCharts /></el-icon>
          故事流程图
        </el-button>
        <el-button type="primary" @click="handleCreateChapter">
          <el-icon><Plus /></el-icon>
          新建章节
        </el-button>
      </div>
    </div>

    <div class="page-content">
      <div v-if="loading" class="skeleton-container">
        <el-skeleton :rows="8" animated />
      </div>

      <div v-else-if="chapters.length === 0" class="empty-state">
        <el-empty description="暂无章节">
          <template #image>
            <el-icon class="empty-icon" :size="80"><Document /></el-icon>
          </template>
          <el-button type="primary" @click="handleCreateChapter">
            创建第一章节
          </el-button>
        </el-empty>
      </div>

      <div v-else class="chapter-view">
        <el-table
          v-if="viewMode === 'table'"
          :data="chapters"
          stripe
          highlight-current-row
          @row-click="handleRowClick"
        >
          <el-table-column type="index" label="序号" width="70" align="center" />
          <el-table-column prop="title" label="标题" min-width="200">
            <template #default="{ row }">
              <span class="chapter-title" :class="{ 'is-draft': row.status === 0 }">
                {{ row.title || `第${row.chapterOrder}章` }}
              </span>
            </template>
          </el-table-column>
          <el-table-column label="字数" width="100" align="center">
            <template #default="{ row }">
              <span class="word-count">{{ formatWordCount(row.wordCount) }}</span>
            </template>
          </el-table-column>
          <el-table-column label="状态" width="90" align="center">
            <template #default="{ row }">
              <el-tag :type="getStatusType(row.status)" size="small">
                {{ getStatusText(row.status) }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column label="更新" width="120" align="center">
            <template #default="{ row }">
              <span class="update-time">{{ formatTime(row.updateTime) }}</span>
            </template>
          </el-table-column>
          <el-table-column label="操作" width="200" fixed="right" align="center">
            <template #default="{ row }">
              <el-button size="small" type="primary" link @click.stop="handleEdit(row)">
                编辑
              </el-button>
              <el-button size="small" link @click.stop="handleAiAssist(row)">
                <el-icon><MagicStick /></el-icon>
              </el-button>
              <el-dropdown trigger="click" @command="(cmd) => handleCommand(cmd, row)">
                <el-button size="small" link @click.stop="">
                  <el-icon><MoreFilled /></el-icon>
                </el-button>
                <template #dropdown>
                  <el-dropdown-menu>
                    <el-dropdown-item command="outline">查看细纲</el-dropdown-item>
                    <el-dropdown-item command="history">版本历史</el-dropdown-item>
                    <el-dropdown-item command="duplicate">复制</el-dropdown-item>
                    <el-dropdown-item command="move">移动</el-dropdown-item>
                    <el-dropdown-item command="delete" divided style="color: var(--el-color-danger)">
                      删除
                    </el-dropdown-item>
                  </el-dropdown-menu>
                </template>
              </el-dropdown>
            </template>
          </el-table-column>
        </el-table>

        <div v-else class="chapter-cards">
          <ChapterCard
            v-for="chapter in chapters"
            :key="chapter.id"
            :chapter="chapter"
            :expanded="expandedChapterId === chapter.id"
            @click="handleRowClick(chapter)"
            @edit="handleEdit(chapter)"
            @ai-assist="handleAiAssist(chapter)"
            @command="(cmd) => handleCommand(cmd, chapter)"
            @toggle="toggleChapterExpand(chapter)"
          />
        </div>
      </div>
    </div>

    <el-dialog
      v-model="showChapterDialog"
      :title="editingChapter ? '编辑章节' : '新建章节'"
      width="600px"
      destroy-on-close
    >
      <el-form ref="formRef" :model="chapterForm" :rules="rules" label-width="80px">
        <el-form-item label="所属卷">
          <el-select v-model="chapterForm.volumeId" placeholder="选择卷（可选）" clearable style="width: 100%">
            <el-option
              v-for="vol in volumes"
              :key="vol.id"
              :label="vol.title"
              :value="vol.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="标题" prop="title">
          <el-input v-model="chapterForm.title" placeholder="请输入章节标题" maxlength="100" show-word-limit />
        </el-form-item>
        <el-form-item label="章节序号">
          <el-input-number v-model="chapterForm.chapterOrder" :min="1" :max="10000" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showChapterDialog = false">取消</el-button>
        <el-button type="primary" @click="handleSave">保存</el-button>
      </template>
    </el-dialog>

    <el-dialog
      v-model="showStoryChart"
      title="故事流程图"
      width="90%"
      destroy-on-close
    >
      <StoryFlowChart
        v-if="showStoryChart"
        :chapters="storyChartChapters"
        @chapter-click="handleChartChapterClick"
      />
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import type { FormInstance, FormRules } from 'element-plus'
import { Grid, Menu, Plus, Document, MagicStick, MoreFilled, TrendCharts } from '@element-plus/icons-vue'
import { listChapters, createChapter, updateChapter, deleteChapter, listVolumes } from '@/api/novel'
import type { NovelChapter, NovelVolume } from '@/types/api'
import ChapterCard from './components/ChapterCard.vue'
import StoryFlowChart from './components/StoryFlowChart.vue'

const route = useRoute()
const router = useRouter()
const novelId = computed(() => route.params.id as string)

const loading = ref(false)
const saving = ref(false)
const chapters = ref<NovelChapter[]>([])
const volumes = ref<NovelVolume[]>([])
const viewMode = ref<'table' | 'card'>('table')
const showChapterDialog = ref(false)
const showStoryChart = ref(false)
const editingChapter = ref<NovelChapter | null>(null)
const expandedChapterId = ref<number | null>(null)

const formRef = ref<FormInstance>()
const chapterForm = ref({
  volumeId: undefined as number | undefined,
  title: '',
  chapterOrder: 1
})

const rules: FormRules = {
  title: [{ required: true, message: '请输入章节标题', trigger: 'blur' }]
}

const storyChartChapters = computed(() =>
  chapters.value.map(c => ({
    id: c.id!,
    chapterNo: c.chapterOrder,
    title: c.title || `第${c.chapterOrder}章`,
    hasForeshadowing: false,
    hasConflict: false,
    isClimax: false
  }))
)

function formatWordCount(count: number): string {
  if (!count) return '0'
  if (count >= 10000) return `${(count / 10000).toFixed(1)}万`
  return count.toString()
}

function getStatusType(status: number): string {
  const map: Record<number, string> = {
    0: 'info',
    1: 'success',
    2: 'success'
  }
  return map[status] || 'info'
}

function getStatusText(status: number): string {
  const map: Record<number, string> = {
    0: '草稿',
    1: '已发布',
    2: '已完结'
  }
  return map[status] || '未知'
}

function formatTime(time?: string): string {
  if (!time) return '-'
  const date = new Date(time)
  const now = new Date()
  const diff = now.getTime() - date.getTime()
  const days = Math.floor(diff / (1000 * 60 * 60 * 24))

  if (days === 0) return '今天'
  if (days === 1) return '昨天'
  if (days < 7) return `${days}天前`
  return date.toLocaleDateString()
}

async function loadData() {
  if (!novelId.value) return

  loading.value = true
  try {
    const [chaptersData, volumesData] = await Promise.all([
      listChapters(Number(novelId.value)),
      listVolumes(Number(novelId.value))
    ])

    chapters.value = chaptersData || []
    volumes.value = volumesData || []
  } catch (error) {
    console.error('Load chapters failed:', error)
    ElMessage.error('加载章节列表失败')
  } finally {
    loading.value = false
  }
}

function handleCreateChapter() {
  editingChapter.value = null
  chapterForm.value = {
    volumeId: undefined,
    title: '',
    chapterOrder: chapters.value.length + 1
  }
  showChapterDialog.value = true
}

function handleEdit(chapter: NovelChapter) {
  editingChapter.value = chapter
  chapterForm.value = {
    volumeId: chapter.volumeId,
    title: chapter.title,
    chapterOrder: chapter.chapterOrder
  }
  showChapterDialog.value = true
}

function handleRowClick(chapter: NovelChapter) {
  router.push(`/novel/${novelId.value}/editor/${chapter.id}`)
}

function toggleChapterExpand(chapter: NovelChapter) {
  expandedChapterId.value = expandedChapterId.value === chapter.id ? null : chapter.id!
}

function handleAiAssist(chapter: NovelChapter) {
  ElMessage.info('AI辅助功能开发中')
}

function handleCommand(command: string, chapter: NovelChapter) {
  switch (command) {
    case 'outline':
      router.push(`/novel/${novelId.value}/chapter-outline/${chapter.id}`)
      break
    case 'history':
      ElMessage.info('版本历史功能开发中')
      break
    case 'duplicate':
      handleDuplicateChapter(chapter)
      break
    case 'move':
      ElMessage.info('移动章节功能开发中')
      break
    case 'delete':
      handleDeleteChapter(chapter)
      break
  }
}

async function handleDuplicateChapter(chapter: NovelChapter) {
  try {
    await createChapter({
      novelId: Number(novelId.value),
      volumeId: chapter.volumeId,
      title: `${chapter.title} (副本)`,
      content: chapter.content,
      chapterOrder: chapters.value.length + 1
    })
    ElMessage.success('复制成功')
    loadData()
  } catch (error) {
    ElMessage.error('复制失败')
  }
}

async function handleDeleteChapter(chapter: NovelChapter) {
  try {
    await ElMessageBox.confirm('确定要删除这个章节吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })

    await deleteChapter(chapter.id!)
    ElMessage.success('删除成功')
    loadData()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('删除失败')
    }
  }
}

async function handleSave() {
  if (!formRef.value) return

  await formRef.value.validate(async (valid) => {
    if (!valid) return

    saving.value = true
    try {
      const data = {
        novelId: Number(novelId.value),
        volumeId: chapterForm.value.volumeId,
        title: chapterForm.value.title,
        chapterOrder: chapterForm.value.chapterOrder
      }

      if (editingChapter.value) {
        await updateChapter(editingChapter.value.id, data)
      } else {
        await createChapter(data)
      }

      ElMessage.success(editingChapter.value ? '更新成功' : '创建成功')
      showChapterDialog.value = false
      loadData()
    } catch (error) {
      ElMessage.error('保存失败')
    } finally {
      saving.value = false
    }
  })
}

function handleChartChapterClick(chapter: any) {
  showStoryChart.value = false
  router.push(`/novel/${novelId.value}/editor/${chapter.id}`)
}

onMounted(() => {
  loadData()
})
</script>

<style lang="scss" scoped>
.novel-chapters {
  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;

    .header-left {
      display: flex;
      align-items: center;
      gap: 12px;

      h2 {
        margin: 0;
        font-size: 20px;
      }
    }

    .header-actions {
      display: flex;
      gap: 12px;
    }
  }

  .chapter-title {
    font-weight: 500;

    &.is-draft {
      color: var(--el-text-color-secondary);
      font-style: italic;
    }
  }

  .word-count {
    color: var(--el-color-primary);
  }

  .update-time {
    font-size: 12px;
    color: var(--el-text-color-secondary);
  }

  .chapter-cards {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }
}
</style>
