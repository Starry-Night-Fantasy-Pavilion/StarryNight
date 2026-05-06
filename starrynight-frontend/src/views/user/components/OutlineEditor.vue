<template>
  <div class="outline-editor">
    <div class="outline-header">
      <div class="header-left">
        <h3>📖 大纲编辑</h3>
        <el-select v-model="outlineTemplate" size="small" class="template-select">
          <el-option label="黄金三幕" value="three-act" />
          <el-option label="英雄之旅" value="hero-journey" />
          <el-option label="五幕结构" value="five-act" />
          <el-option label="自由编辑" value="free" />
        </el-select>
      </div>
      <div class="header-right">
        <el-button size="small" @click="handleAiGenerate">
          <el-icon><MagicStick /></el-icon>
          AI生成
        </el-button>
        <el-button size="small" type="primary" @click="handleSave">
          <el-icon><FolderOpened /></el-icon>
          保存
        </el-button>
      </div>
    </div>

    <div class="outline-content">
      <div class="outline-form">
        <el-form :model="outlineData" label-width="100px" size="default">
          <el-form-item label="标题">
            <el-input v-model="outlineData.title" placeholder="作品标题" />
          </el-form-item>
          <el-form-item label="题材">
            <el-select v-model="outlineData.genre" placeholder="选择题材" style="width: 100%">
              <el-option label="都市" value="urban" />
              <el-option label="玄幻" value="fantasy" />
              <el-option label="仙侠" value="xianxia" />
              <el-option label="穿越" value="transmigration" />
              <el-option label="科幻" value="scifi" />
              <el-option label="悬疑" value="mystery" />
            </el-select>
          </el-form-item>
          <el-form-item label="风格">
            <el-select v-model="outlineData.style" placeholder="选择风格" style="width: 100%">
              <el-option label="热血爽文" value="passionate" />
              <el-option label="治愈系" value="healing" />
              <el-option label="搞笑" value="comedy" />
              <el-option label="虐心" value="heart-wrenching" />
              <el-option label="悬疑" value="suspensive" />
            </el-select>
          </el-form-item>
          <el-form-item label="目标字数">
            <el-input-number v-model="outlineData.targetWordCount" :min="10000" :max="10000000" :step="10000" />
          </el-form-item>
        </el-form>
      </div>

      <div class="outline-structure">
        <div class="act-section" v-for="(act, actIndex) in outlineData.acts" :key="actIndex">
          <div class="act-header" @click="toggleAct(actIndex)">
            <el-icon v-if="act.expanded"><ArrowDown /></el-icon>
            <el-icon v-else><ArrowRight /></el-icon>
            <span class="act-title">{{ act.title }}</span>
            <span class="act-range">({{ act.chapterRange }})</span>
            <el-tag size="small" type="info">{{ act.plotPoints.length }}个情节点</el-tag>
          </div>

          <el-collapse-transition>
            <div v-show="act.expanded" class="act-body">
              <div class="act-description">
                <p class="core-conflict">
                  <strong>核心冲突：</strong>{{ act.coreConflict }}
                </p>
              </div>

              <div class="plot-points">
                <div
                  v-for="(point, pointIndex) in act.plotPoints"
                  :key="pointIndex"
                  class="plot-point-card"
                >
                  <div class="point-header">
                    <span class="point-number">{{ pointIndex + 1 }}</span>
                    <span class="point-title">{{ point.title }}</span>
                    <div class="point-actions">
                      <el-button size="small" link @click="handleEditPoint(actIndex, pointIndex)">
                        <el-icon><Edit /></el-icon>
                      </el-button>
                      <el-button size="small" link @click="handleAiDiscuss(actIndex, pointIndex)">
                        <el-icon><ChatDotRound /></el-icon>
                      </el-button>
                    </div>
                  </div>
                  <p class="point-description">{{ point.description }}</p>
                  <div class="point-events">
                    <el-tag
                      v-for="event in point.keyEvents"
                      :key="event"
                      size="small"
                      effect="plain"
                    >
                      {{ event }}
                    </el-tag>
                  </div>
                </div>

                <el-button class="add-point-btn" size="small" @click="handleAddPoint(actIndex)">
                  <el-icon><Plus /></el-icon>
                  添加情节点
                </el-button>
              </div>

              <div class="act-footer">
                <el-button size="small" @click="handleVersionHistory(actIndex)">
                  <el-icon><Clock /></el-icon>
                  版本历史
                </el-button>
              </div>
            </div>
          </el-collapse-transition>
        </div>
      </div>

      <div class="characters-section">
        <div class="section-header">
          <h4>👥 主要角色</h4>
          <el-button size="small" @click="handleAddCharacter">
            <el-icon><Plus /></el-icon>
            添加
          </el-button>
        </div>
        <div class="character-list">
          <div
            v-for="character in outlineData.characters"
            :key="character.id"
            class="character-item"
          >
            <el-avatar :size="40" :src="character.avatar">
              {{ character.name?.charAt(0) }}
            </el-avatar>
            <span class="character-name">{{ character.name }}</span>
            <span class="character-role">{{ character.role }}</span>
          </div>
        </div>
      </div>
    </div>

    <el-dialog
      v-model="showEditDialog"
      :title="editDialogTitle"
      width="600px"
      destroy-on-close
    >
      <el-form :model="editForm" label-width="100px">
        <el-form-item label="标题">
          <el-input v-model="editForm.title" />
        </el-form-item>
        <el-form-item label="描述">
          <el-input v-model="editForm.description" type="textarea" :rows="4" />
        </el-form-item>
        <el-form-item label="关键事件">
          <el-input v-model="editForm.newEvent" placeholder="输入事件后按回车添加">
            <template #append>
              <el-button @click="addEvent">添加</el-button>
            </template>
          </el-input>
          <div class="events-tags">
            <el-tag
              v-for="(event, idx) in editForm.events"
              :key="idx"
              closable
              size="small"
              @close="removeEvent(idx)"
            >
              {{ event }}
            </el-tag>
          </div>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showEditDialog = false">取消</el-button>
        <el-button type="primary" @click="handleConfirmEdit">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { ArrowDown, ArrowRight, Edit, ChatDotRound, Plus, MagicStick, FolderOpened, Clock } from '@element-plus/icons-vue'

interface PlotPoint {
  title: string
  description: string
  keyEvents: string[]
}

interface Act {
  title: string
  chapterRange: string
  coreConflict: string
  plotPoints: PlotPoint[]
  expanded: boolean
}

interface OutlineCharacter {
  id: string
  name: string
  role: string
  avatar?: string
}

interface OutlineData {
  title: string
  genre: string
  style: string
  targetWordCount: number
  acts: Act[]
  characters: OutlineCharacter[]
}

const props = defineProps<{
  modelValue?: OutlineData
}>()

const emit = defineEmits<{
  'update:modelValue': [value: OutlineData]
  save: [value: OutlineData]
  'ai-generate': []
  'edit-point': [actIndex: number, pointIndex: number]
  'ai-discuss': [actIndex: number, pointIndex: number]
}>()

const outlineTemplate = ref('three-act')

const outlineData = reactive<OutlineData>({
  title: '',
  genre: '',
  style: '',
  targetWordCount: 500000,
  acts: [
    {
      title: '第一幕：建置',
      chapterRange: '1-10章',
      coreConflict: '主角发现自己的特殊天赋，开启人生新篇章',
      plotPoints: [],
      expanded: true
    },
    {
      title: '第二幕：对抗',
      chapterRange: '11-80章',
      coreConflict: '主角面临重重困难，必须证明自己',
      plotPoints: [],
      expanded: false
    },
    {
      title: '第三幕：解决',
      chapterRange: '81-100章',
      coreConflict: '主角克服最终挑战，达到人生巅峰',
      plotPoints: [],
      expanded: false
    }
  ],
  characters: []
})

const showEditDialog = ref(false)
const editDialogTitle = ref('')
const editForm = reactive({
  actIndex: -1,
  pointIndex: -1,
  title: '',
  description: '',
  events: [] as string[],
  newEvent: ''
})

function toggleAct(index: number) {
  outlineData.acts[index].expanded = !outlineData.acts[index].expanded
}

function handleAiGenerate() {
  emit('ai-generate')
}

function handleSave() {
  emit('save', { ...outlineData })
}

function handleEditPoint(actIndex: number, pointIndex: number) {
  const point = outlineData.acts[actIndex].plotPoints[pointIndex]
  editDialogTitle.value = `编辑情节点 - ${point.title}`
  editForm.actIndex = actIndex
  editForm.pointIndex = pointIndex
  editForm.title = point.title
  editForm.description = point.description
  editForm.events = [...point.keyEvents]
  editForm.newEvent = ''
  showEditDialog.value = true
}

function handleAiDiscuss(actIndex: number, pointIndex: number) {
  emit('ai-discuss', actIndex, pointIndex)
}

function handleAddPoint(actIndex: number) {
  const newPoint: PlotPoint = {
    title: '新情节点',
    description: '请输入描述...',
    keyEvents: []
  }
  outlineData.acts[actIndex].plotPoints.push(newPoint)
}

function handleVersionHistory(actIndex: number) {
  console.log('Version history for act:', actIndex)
}

function handleAddCharacter() {
  console.log('Add character')
}

function addEvent() {
  if (editForm.newEvent.trim()) {
    editForm.events.push(editForm.newEvent.trim())
    editForm.newEvent = ''
  }
}

function removeEvent(index: number) {
  editForm.events.splice(index, 1)
}

function handleConfirmEdit() {
  if (editForm.actIndex >= 0 && editForm.pointIndex >= 0) {
    const point = outlineData.acts[editForm.actIndex].plotPoints[editForm.pointIndex]
    point.title = editForm.title
    point.description = editForm.description
    point.keyEvents = [...editForm.events]
  }
  showEditDialog.value = false
}
</script>

<style lang="scss" scoped>
.outline-editor {
  height: 100%;
  display: flex;
  flex-direction: column;
  background: var(--el-bg-color-page);
}

.outline-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  background: var(--el-bg-color);
  border-bottom: 1px solid var(--el-border-color-light);

  .header-left {
    display: flex;
    align-items: center;
    gap: 16px;

    h3 {
      margin: 0;
      font-size: 18px;
    }

    .template-select {
      width: 150px;
    }
  }

  .header-right {
    display: flex;
    gap: 8px;
  }
}

.outline-content {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
}

.outline-form {
  background: var(--el-bg-color);
  padding: 16px;
  border-radius: 8px;
  margin-bottom: 16px;
}

.outline-structure {
  background: var(--el-bg-color);
  border-radius: 8px;
  overflow: hidden;
}

.act-section {
  border-bottom: 1px solid var(--el-border-color-light);

  &:last-child {
    border-bottom: none;
  }
}

.act-header {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 16px;
  cursor: pointer;
  background: var(--el-fill-color-light);
  transition: background 0.2s;

  &:hover {
    background: var(--el-fill-color);
  }

  .act-title {
    font-weight: 600;
    font-size: 16px;
  }

  .act-range {
    color: var(--el-text-color-secondary);
    font-size: 14px;
  }
}

.act-body {
  padding: 16px;

  .act-description {
    margin-bottom: 16px;

    .core-conflict {
      margin: 0;
      padding: 12px;
      background: var(--el-color-primary-light-9);
      border-radius: 4px;
      font-size: 14px;
      line-height: 1.6;
    }
  }

  .plot-points {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  .plot-point-card {
    padding: 12px;
    border: 1px solid var(--el-border-color-light);
    border-radius: 8px;
    transition: border-color 0.2s;

    &:hover {
      border-color: var(--el-color-primary);
    }

    .point-header {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 8px;

      .point-number {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: var(--el-color-primary);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 600;
      }

      .point-title {
        flex: 1;
        font-weight: 600;
      }

      .point-actions {
        display: flex;
        gap: 4px;
      }
    }

    .point-description {
      margin: 0 0 12px;
      font-size: 14px;
      color: var(--el-text-color-secondary);
      line-height: 1.6;
    }

    .point-events {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
    }
  }

  .add-point-btn {
    width: 100%;
    border-style: dashed;
  }

  .act-footer {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px dashed var(--el-border-color-light);
  }
}

.characters-section {
  margin-top: 16px;
  background: var(--el-bg-color);
  border-radius: 8px;
  padding: 16px;

  .section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;

    h4 {
      margin: 0;
      font-size: 16px;
    }
  }

  .character-list {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
  }

  .character-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 12px;
    border: 1px solid var(--el-border-color-light);
    border-radius: 8px;
    min-width: 100px;
    cursor: pointer;
    transition: border-color 0.2s;

    &:hover {
      border-color: var(--el-color-primary);
    }

    .character-name {
      font-weight: 600;
      font-size: 14px;
    }

    .character-role {
      font-size: 12px;
      color: var(--el-text-color-secondary);
    }
  }
}

.events-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 8px;
}
</style>
