<template>
  <div class="timeline-view page-container">
    <div class="page-header">
      <h1>⏱️ 时间线视图</h1>
      <div class="header-actions">
        <el-radio-group v-model="viewMode" size="small">
          <el-radio-button value="list">列表视图</el-radio-button>
          <el-radio-button value="timeline">时间线视图</el-radio-button>
        </el-radio-group>
        <el-select v-model="filterType" placeholder="视图筛选" clearable style="width: 160px">
          <el-option label="主线+支线" value="all" />
          <el-option label="仅主线" value="main" />
          <el-option label="按情节线分组" value="plot" />
        </el-select>
        <el-select v-model="selectedNovelId" placeholder="选择作品" clearable style="width: 200px" @change="loadData">
          <el-option v-for="novel in novels" :key="novel.id" :label="novel.title" :value="novel.id" />
        </el-select>
      </div>
    </div>

    <div v-if="!selectedNovelId" class="empty-state">
      <el-icon :size="64"><Timer /></el-icon>
      <p>请选择作品以查看时间线</p>
    </div>

    <div v-else class="timeline-content">
      <el-card v-if="viewMode === 'timeline'" class="timeline-card">
        <div class="timeline-wrapper">
          <div class="timeline-axis">
            <div
              v-for="(chapter, index) in timelineData"
              :key="chapter.id"
              class="timeline-node"
              :class="[`type-${chapter.plotType}`, { 'drag-over': dragOverIndex === index }]"
              draggable="true"
              @dragstart="handleDragStart($event, chapter, index)"
              @dragover.prevent="handleDragOver($event, index)"
              @dragleave="handleDragLeave"
              @drop="handleDrop($event, index)"
              @click="selectChapter(chapter)"
              @contextmenu.prevent="showContextMenu($event, chapter)"
            >
              <div class="node-card" :class="{ selected: selectedChapter?.id === chapter.id }">
                <div class="node-header">
                  <span class="chapter-no">第{{ chapter.sortOrder }}章</span>
                  <span class="plot-badge" :class="chapter.plotType">
                    {{ getPlotTypeLabel(chapter.plotType) }}
                  </span>
                </div>
                <div class="node-title">{{ chapter.title }}</div>
                <div class="node-meta">
                  <span v-if="chapter.wordCount">{{ chapter.wordCount }}字</span>
                  <span v-if="chapter.foreshadowing" class="foreshadow-tag">伏笔</span>
                </div>
              </div>
              <div class="node-connector" v-if="index < timelineData.length - 1"></div>
            </div>
          </div>

          <div class="timeline-legend">
            <span class="legend-item"><span class="dot main"></span> 主线</span>
            <span class="legend-item"><span class="dot sub"></span> 支线</span>
            <span class="legend-item"><span class="dot romance"></span> 感情线</span>
            <span class="legend-item"><span class="dot event"></span> 事件</span>
          </div>
        </div>
      </el-card>

      <el-card v-else class="list-card">
        <el-table :data="timelineData" @row-click="selectChapter" highlight-current-row>
          <el-table-column prop="sortOrder" label="章节" width="80">
            <template #default="{ row }">第{{ row.sortOrder }}章</template>
          </el-table-column>
          <el-table-column prop="title" label="标题" min-width="200" />
          <el-table-column prop="plotType" label="类型" width="100">
            <template #default="{ row }">
              <el-tag size="small" :type="getPlotTypeTag(row.plotType)">
                {{ getPlotTypeLabel(row.plotType) }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="keywords" label="关键词" min-width="200" />
          <el-table-column prop="characters" label="出场人物" min-width="150" />
          <el-table-column prop="wordCount" label="字数" width="100" />
          <el-table-column label="操作" width="120" fixed="right">
            <template #default="{ row }">
              <el-button link type="primary" size="small" @click.stop="editChapter(row)">编辑</el-button>
              <el-button link type="primary" size="small" @click.stop="addForeshadow(row)">伏笔</el-button>
            </template>
          </el-table-column>
        </el-table>
      </el-card>

      <el-card v-if="hoveredChapter" class="preview-card">
        <template #header>
          <span>悬浮预览 (第{{ hoveredChapter.sortOrder }}章)</span>
        </template>
        <el-descriptions :column="1" border size="small">
          <el-descriptions-item label="标题">{{ hoveredChapter.title }}</el-descriptions-item>
          <el-descriptions-item label="关键词">{{ hoveredChapter.keywords }}</el-descriptions-item>
          <el-descriptions-item label="出场人物">{{ hoveredChapter.characters }}</el-descriptions-item>
          <el-descriptions-item label="字数">{{ hoveredChapter.wordCount }}</el-descriptions-item>
          <el-descriptions-item label="伏笔">
            <el-tag v-if="hoveredChapter.foreshadowing" type="warning" size="small">
              {{ hoveredChapter.foreshadowing }}
            </el-tag>
            <span v-else>无</span>
          </el-descriptions-item>
          <el-descriptions-item label="冲突">
            <el-rate v-model="hoveredChapter.conflictLevel" disabled size="small" />
          </el-descriptions-item>
        </el-descriptions>
      </el-card>
    </div>

    <el-dialog v-model="foreshadowDialogVisible" title="设置伏笔" width="480px">
      <el-form :model="foreshadowForm" label-width="100px">
        <el-form-item label="伏笔内容">
          <el-input v-model="foreshadowForm.content" type="textarea" :rows="3" placeholder="描述伏笔内容..." />
        </el-form-item>
        <el-form-item label="伏笔类型">
          <el-select v-model="foreshadowForm.type" placeholder="选择类型">
            <el-option label="物品伏笔" value="item" />
            <el-option label="身份伏笔" value="identity" />
            <el-option label="关系伏笔" value="relationship" />
            <el-option label="能力伏笔" value="ability" />
          </el-select>
        </el-form-item>
        <el-form-item label="预期回收章节">
          <el-select v-model="foreshadowForm.targetChapterId" placeholder="选择章节" clearable>
            <el-option
              v-for="ch in timelineData"
              :key="ch.id"
              :label="`第${ch.sortOrder}章 ${ch.title}`"
              :value="ch.id"
            />
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="foreshadowDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="saveForeshadow">保存</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="contextMenuVisible" title="章节操作" width="400px">
      <div class="context-actions">
        <el-button text class="ctx-btn" @click="editChapter(selectedChapter!)">编辑章节</el-button>
        <el-button text class="ctx-btn" @click="addForeshadow(selectedChapter!)">添加伏笔</el-button>
        <el-button text class="ctx-btn" @click="setPlotType(selectedChapter!, 'main')">设为主线</el-button>
        <el-button text class="ctx-btn" @click="setPlotType(selectedChapter!, 'sub')">设为支线</el-button>
        <el-button text class="ctx-btn" type="danger" @click="deleteChapter(selectedChapter!)">删除章节</el-button>
      </div>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Timer } from '@element-plus/icons-vue'
import { listNovels, listChapters } from '@/api/novel'

interface Novel {
  id: number
  title: string
}

interface Chapter {
  id: number
  sortOrder: number
  title: string
  plotType: 'main' | 'sub' | 'romance' | 'event'
  keywords?: string
  characters?: string
  wordCount?: number
  foreshadowing?: string
  conflictLevel?: number
}

const viewMode = ref<'list' | 'timeline'>('timeline')
const filterType = ref<'all' | 'main' | 'plot'>('all')
const selectedNovelId = ref<number>()
const novels = ref<Novel[]>([])
const timelineData = ref<Chapter[]>([])
const hoveredChapter = ref<Chapter | null>(null)
const selectedChapter = ref<Chapter | null>(null)
const dragOverIndex = ref<number>(-1)
const draggedChapter = ref<Chapter | null>(null)
const draggedIndex = ref<number>(-1)

const foreshadowDialogVisible = ref(false)
const contextMenuVisible = ref(false)
const foreshadowForm = reactive({
  content: '',
  type: 'item',
  targetChapterId: undefined as number | undefined
})

async function loadNovels() {
  try {
    const res = await listNovels({ page: 1, size: 100 })
    novels.value = res.data?.records || []
  } catch (e) {
    console.error('Failed to load novels', e)
  }
}

async function loadData() {
  if (!selectedNovelId.value) return

  try {
    const chapters = await listChapters(selectedNovelId.value)
    timelineData.value = (chapters as unknown as Chapter[]) || generateMockData()
  } catch (e) {
    timelineData.value = generateMockData()
  }
}

function generateMockData(): Chapter[] {
  const types: Chapter['plotType'][] = ['main', 'main', 'sub', 'main', 'romance', 'main', 'sub', 'main', 'main', 'event']
  return Array.from({ length: 20 }, (_, i) => ({
    id: i + 1,
    sortOrder: i + 1,
    title: `第${i + 1}章标题`,
    plotType: types[i % types.length],
    keywords: '比武、修炼、突破',
    characters: '林天、血冥、慕容雪',
    wordCount: 2500 + Math.floor(Math.random() * 1000),
    conflictLevel: Math.floor(Math.random() * 5) + 1
  }))
}

function getPlotTypeLabel(type: string): string {
  const map: Record<string, string> = {
    main: '主线',
    sub: '支线',
    romance: '感情',
    event: '事件'
  }
  return map[type] || type
}

function getPlotTypeTag(type: string): string {
  const map: Record<string, string> = {
    main: '',
    sub: 'info',
    romance: 'danger',
    event: 'warning'
  }
  return map[type] || ''
}

function selectChapter(chapter: Chapter) {
  selectedChapter.value = chapter
}

function handleDragStart(event: DragEvent, chapter: Chapter, index: number) {
  draggedChapter.value = chapter
  draggedIndex.value = index
  if (event.dataTransfer) {
    event.dataTransfer.effectAllowed = 'move'
  }
}

function handleDragOver(event: DragEvent, index: number) {
  dragOverIndex.value = index
}

function handleDragLeave() {
  dragOverIndex.value = -1
}

function handleDrop(event: DragEvent, targetIndex: number) {
  event.preventDefault()
  if (draggedIndex.value === -1 || draggedIndex.value === targetIndex) return

  const data = [...timelineData.value]
  const [removed] = data.splice(draggedIndex.value, 1)
  data.splice(targetIndex, 0, removed)

  data.forEach((chapter, idx) => {
    chapter.sortOrder = idx + 1
  })

  timelineData.value = data
  dragOverIndex.value = -1
  draggedIndex.value = -1
  ElMessage.success('章节顺序已更新')
}

function editChapter(chapter: Chapter) {
  contextMenuVisible.value = false
  ElMessage.info(`编辑章节: ${chapter.title}`)
}

function addForeshadow(chapter: Chapter) {
  contextMenuVisible.value = false
  selectedChapter.value = chapter
  foreshadowForm.content = ''
  foreshadowForm.type = 'item'
  foreshadowForm.targetChapterId = undefined
  foreshadowDialogVisible.value = true
}

function saveForeshadow() {
  if (!foreshadowForm.content.trim()) {
    ElMessage.warning('请输入伏笔内容')
    return
  }
  if (selectedChapter.value) {
    selectedChapter.value.foreshadowing = foreshadowForm.content
  }
  foreshadowDialogVisible.value = false
  ElMessage.success('伏笔已设置')
}

function setPlotType(chapter: Chapter, type: Chapter['plotType']) {
  chapter.plotType = type
  contextMenuVisible.value = false
  ElMessage.success(`已设置为${getPlotTypeLabel(type)}`)
}

function deleteChapter(chapter: Chapter) {
  ElMessageBox.confirm(`确定删除第${chapter.sortOrder}章吗？`, '提示', {
    type: 'warning'
  }).then(() => {
    timelineData.value = timelineData.value.filter(c => c.id !== chapter.id)
    timelineData.value.forEach((c, idx) => {
      c.sortOrder = idx + 1
    })
    contextMenuVisible.value = false
    ElMessage.success('已删除')
  }).catch(() => {})
}

function showContextMenu(event: MouseEvent, chapter: Chapter) {
  selectedChapter.value = chapter
  contextMenuVisible.value = true
}

onMounted(() => {
  loadNovels()
})
</script>

<style lang="scss" scoped>
.timeline-view {
  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
  }

  .empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 400px;
    color: var(--el-text-color-secondary);
  }

  .timeline-content {
    .timeline-card {
      margin-bottom: 16px;

      .timeline-wrapper {
        padding: 20px 0;

        .timeline-axis {
          display: flex;
          overflow-x: auto;
          padding: 20px 0;
          gap: 0;

          .timeline-node {
            position: relative;
            flex-shrink: 0;
            cursor: pointer;

            .node-card {
              width: 120px;
              padding: 12px;
              background: var(--el-fill-color-light);
              border: 2px solid var(--el-border-color);
              border-radius: 8px;
              transition: all 0.3s;

              &:hover {
                border-color: var(--el-color-primary);
                transform: translateY(-4px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
              }

              &.selected {
                border-color: var(--el-color-primary);
                background: var(--el-color-primary-light-9);
              }

              .node-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 8px;

                .chapter-no {
                  font-size: 12px;
                  font-weight: 600;
                  color: var(--el-text-color);
                }

                .plot-badge {
                  font-size: 10px;
                  padding: 2px 6px;
                  border-radius: 4px;
                  color: white;

                  &.main { background: #409EFF; }
                  &.sub { background: #909399; }
                  &.romance { background: #F56C6C; }
                  &.event { background: #E6A23C; }
                }
              }

              .node-title {
                font-size: 12px;
                color: var(--el-text-color-secondary);
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
              }

              .node-meta {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-top: 8px;
                font-size: 10px;
                color: var(--el-text-color-muted);

                .foreshadow-tag {
                  color: var(--el-color-warning);
                }
              }
            }

            .node-connector {
              position: absolute;
              top: 30px;
              right: -30px;
              width: 30px;
              height: 2px;
              background: var(--el-border-color);
            }

            &.drag-over .node-card {
              border-color: var(--el-color-success);
              background: var(--el-color-success-light-9);
            }
          }
        }

        .timeline-legend {
          display: flex;
          justify-content: center;
          gap: 24px;
          margin-top: 16px;
          padding-top: 16px;
          border-top: 1px solid var(--el-border-color-lighter);

          .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: var(--el-text-color-secondary);

            .dot {
              width: 12px;
              height: 12px;
              border-radius: 50%;

              &.main { background: #409EFF; }
              &.sub { background: #909399; }
              &.romance { background: #F56C6C; }
              &.event { background: #E6A23C; }
            }
          }
        }
      }
    }

    .list-card {
      margin-bottom: 16px;
    }

    .preview-card {
      margin-top: 16px;
    }
  }
}
</style>
