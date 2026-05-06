<template>
  <div class="chapter-outline-editor">
    <div class="editor-header">
      <div class="header-left">
        <h3>📝 章节细纲编辑</h3>
        <span class="chapter-indicator">{{ chapterTitle }}</span>
      </div>
      <div class="header-right">
        <el-button size="small" @click="handleToggleView">
          <el-icon><Grid /></el-icon>
          {{ viewMode === 'card' ? '列表模式' : '卡片模式' }}
        </el-button>
        <el-button size="small" @click="handleAiChat">
          <el-icon><ChatDotRound /></el-icon>
          AI对话
        </el-button>
        <el-button size="small" type="primary" @click="handleExpandContent">
          <el-icon><Document /></el-icon>
          扩写正文
        </el-button>
      </div>
    </div>

    <div class="editor-content">
      <div class="chapter-info-form">
        <el-form :model="chapterForm" label-width="100px" size="default">
          <el-form-item label="章节标题">
            <el-input v-model="chapterForm.title" placeholder="章节标题" />
          </el-form-item>
          <el-form-item label="章节序号">
            <el-input-number v-model="chapterForm.chapterOrder" :min="1" />
          </el-form-item>
          <el-form-item label="目标字数">
            <el-input-number v-model="chapterForm.targetWordCount" :min="500" :max="50000" :step="500" />
          </el-form-item>
          <el-form-item label="章节类型">
            <el-select v-model="chapterForm.chapterType" style="width: 100%">
              <el-option label="标准章节" value="standard" />
              <el-option label="过渡章节" value="transition" />
              <el-option label="高潮章节" value="climax" />
              <el-option label="结局章节" value="ending" />
            </el-select>
          </el-form-item>
        </el-form>
      </div>

      <div class="scene-setting">
        <h4>🎬 场景设定</h4>
        <el-form :model="chapterForm.scene" label-width="80px" size="default">
          <el-form-item label="地点">
            <el-input v-model="chapterForm.scene.location" placeholder="场景地点" />
          </el-form-item>
          <el-form-item label="时间">
            <el-input v-model="chapterForm.scene.time" placeholder="时间设定" />
          </el-form-item>
          <el-form-item label="氛围">
            <el-input v-model="chapterForm.scene.atmosphere" placeholder="如：紧张、期待" />
          </el-form-item>
          <el-form-item label="出场人物">
            <el-select v-model="chapterForm.scene.characters" multiple placeholder="选择出场人物" style="width: 100%">
              <el-option
                v-for="char in availableCharacters"
                :key="char.id"
                :label="char.name"
                :value="char.id"
              />
            </el-select>
          </el-form-item>
        </el-form>
      </div>

      <div class="plot-points-section">
        <h4>📋 情节点 {{ viewMode === 'card' ? '(卡片模式)' : '(列表模式)' }}</h4>

        <div v-if="viewMode === 'card'" class="plot-points-cards">
          <div
            v-for="(point, index) in chapterForm.plotPoints"
            :key="index"
            class="plot-point-card"
            :class="{ 'is-active': activePointIndex === index }"
            @click="selectPoint(index)"
          >
            <div class="point-number">{{ getPointLabel(index) }}</div>
            <div class="point-content">
              <el-input
                v-model="point.title"
                placeholder="情节点标题"
                class="point-title-input"
              />
              <el-input
                v-model="point.description"
                type="textarea"
                :rows="2"
                placeholder="描述这个情节点..."
              />
            </div>
            <div class="point-actions">
              <el-button size="small" link @click.stop="handleEditPoint(index)">
                <el-icon><Edit /></el-icon>
              </el-button>
              <el-button size="small" link @click.stop="handleAiPoint(index)">
                <el-icon><MagicStick /></el-icon>
              </el-button>
            </div>
          </div>
        </div>

        <div v-else class="plot-points-table">
          <el-table :data="chapterForm.plotPoints" border size="small">
            <el-table-column label="序号" width="60" align="center">
              <template #default="{ $index }">
                {{ getPointLabel($index) }}
              </template>
            </el-table-column>
            <el-table-column label="类型" width="100">
              <template #default="{ row }">
                <el-select v-model="row.type" size="small">
                  <el-option label="开端" value="opening" />
                  <el-option label="发展" value="development" />
                  <el-option label="转折" value="turning" />
                  <el-option label="高潮" value="climax" />
                  <el-option label="结尾" value="ending" />
                </el-select>
              </template>
            </el-table-column>
            <el-table-column label="情节点">
              <template #default="{ row }">
                <el-input v-model="row.title" size="small" placeholder="情节点标题" />
              </template>
            </el-table-column>
            <el-table-column label="描述">
              <template #default="{ row }">
                <el-input v-model="row.description" size="small" placeholder="描述" />
              </template>
            </el-table-column>
            <el-table-column label="情感变化" width="120">
              <template #default="{ row }">
                <el-input v-model="row.emotionalChange" size="small" placeholder="如：紧张→放松" />
              </template>
            </el-table-column>
            <el-table-column label="操作" width="80" align="center">
              <template #default="{ $index }">
                <el-button size="small" link @click="handleAiPoint($index)">
                  <el-icon><MagicStick /></el-icon>
                </el-button>
              </template>
            </el-table-column>
          </el-table>
        </div>

        <div class="add-point-row">
          <el-button size="small" @click="handleAddPoint">
            <el-icon><Plus /></el-icon>
            添加情节点
          </el-button>
          <span class="point-hint">建议包含：开端、发展、转折、高潮、结尾</span>
        </div>
      </div>

      <div class="emotion-curve">
        <h4>💭 情感曲线</h4>
        <div class="curve-display">
          <div class="curve-canvas">
            <div class="curve-line">
              <div
                v-for="(point, index) in chapterForm.plotPoints"
                :key="index"
                class="curve-point"
                :style="getCurvePointStyle(point.emotionalLevel)"
              >
                <el-tooltip :content="`${getPointLabel(index)}: ${point.emotionalChange || '未设置'}`">
                  <span class="point-marker"></span>
                </el-tooltip>
              </div>
            </div>
            <div class="curve-labels">
              <span>紧张</span>
              <span>兴奋</span>
              <span>放松</span>
            </div>
          </div>
          <div class="curve-legend">
            <span
              v-for="(point, index) in chapterForm.plotPoints"
              :key="index"
              class="legend-item"
            >
              {{ getPointLabel(index) }}: {{ point.emotionalChange || '未设置' }}
            </span>
          </div>
        </div>
      </div>

      <div class="key-dialogue">
        <h4>🎯 关键对白</h4>
        <div class="dialogue-list">
          <div
            v-for="(dialogue, index) in chapterForm.dialogues"
            :key="index"
            class="dialogue-item"
          >
            <div class="dialogue-speaker">{{ dialogue.speaker }}:</div>
            <div class="dialogue-content">"{{ dialogue.content }}"</div>
            <div class="dialogue-purpose">{{ dialogue.purpose }}</div>
          </div>
          <div v-if="!chapterForm.dialogues.length" class="empty-dialogue">
            暂无关键对白
          </div>
        </div>
        <el-button size="small" class="add-dialogue-btn" @click="handleAddDialogue">
          <el-icon><Plus /></el-icon>
          添加对白
        </el-button>
      </div>
    </div>

    <el-dialog
      v-model="showDialogueDialog"
      title="添加关键对白"
      width="500px"
      destroy-on-close
    >
      <el-form :model="dialogueForm" label-width="80px">
        <el-form-item label="说话人">
          <el-input v-model="dialogueForm.speaker" />
        </el-form-item>
        <el-form-item label="对白内容">
          <el-input v-model="dialogueForm.content" type="textarea" :rows="3" />
        </el-form-item>
        <el-form-item label="作用">
          <el-input v-model="dialogueForm.purpose" placeholder="这句对白的作用" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showDialogueDialog = false">取消</el-button>
        <el-button type="primary" @click="handleConfirmDialogue">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { Grid, ChatDotRound, Document, Edit, MagicStick, Plus } from '@element-plus/icons-vue'
import type { NovelCharacter } from '@/api/character'

interface PlotPoint {
  type: string
  title: string
  description: string
  emotionalChange: string
  emotionalLevel: number
}

interface Dialogue {
  speaker: string
  content: string
  purpose: string
}

interface SceneSetting {
  location: string
  time: string
  atmosphere: string
  characters: number[]
}

interface ChapterFormData {
  title: string
  chapterOrder: number
  targetWordCount: number
  chapterType: string
  scene: SceneSetting
  plotPoints: PlotPoint[]
  dialogues: Dialogue[]
}

const props = defineProps<{
  chapterTitle?: string
  availableCharacters?: NovelCharacter[]
}>()

const emit = defineEmits<{
  save: [data: ChapterFormData]
  'ai-chat': []
  'expand-content': []
  'ai-point': [pointIndex: number]
}>()

const viewMode = ref<'card' | 'table'>('card')
const activePointIndex = ref(-1)
const showDialogueDialog = ref(false)

const chapterForm = reactive<ChapterFormData>({
  title: '',
  chapterOrder: 1,
  targetWordCount: 3000,
  chapterType: 'standard',
  scene: {
    location: '',
    time: '',
    atmosphere: '',
    characters: []
  },
  plotPoints: [
    { type: 'opening', title: '开端', description: '', emotionalChange: '期待', emotionalLevel: 3 },
    { type: 'development', title: '发展', description: '', emotionalChange: '好奇', emotionalLevel: 4 },
    { type: 'turning', title: '转折', description: '', emotionalChange: '紧张', emotionalLevel: 7 },
    { type: 'climax', title: '高潮', description: '', emotionalChange: '兴奋', emotionalLevel: 9 },
    { type: 'ending', title: '结尾', description: '', emotionalChange: '满足', emotionalLevel: 5 }
  ],
  dialogues: []
})

const dialogueForm = reactive<Dialogue>({
  speaker: '',
  content: '',
  purpose: ''
})

const pointLabels = ['①', '②', '③', '④', '⑤', '⑥', '⑦', '⑧']

function getPointLabel(index: number): string {
  return pointLabels[index] || (index + 1).toString()
}

function getCurvePointStyle(level: number): Record<string, string> {
  const bottom = Math.min(90, Math.max(10, level * 10))
  return {
    bottom: `${bottom}%`
  }
}

function selectPoint(index: number) {
  activePointIndex.value = index
}

function handleToggleView() {
  viewMode.value = viewMode.value === 'card' ? 'table' : 'card'
}

function handleAiChat() {
  emit('ai-chat')
}

function handleExpandContent() {
  emit('expand-content')
}

function handleEditPoint(index: number) {
  console.log('Edit point:', index)
}

function handleAiPoint(index: number) {
  emit('ai-point', index)
}

function handleAddPoint() {
  chapterForm.plotPoints.push({
    type: 'development',
    title: '',
    description: '',
    emotionalChange: '',
    emotionalLevel: 5
  })
}

function handleAddDialogue() {
  dialogueForm.speaker = ''
  dialogueForm.content = ''
  dialogueForm.purpose = ''
  showDialogueDialog.value = true
}

function handleConfirmDialogue() {
  if (dialogueForm.speaker && dialogueForm.content) {
    chapterForm.dialogues.push({ ...dialogueForm })
    showDialogueDialog.value = false
  }
}
</script>

<style lang="scss" scoped>
.chapter-outline-editor {
  height: 100%;
  display: flex;
  flex-direction: column;
  background: var(--el-bg-color-page);
}

.editor-header {
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

    .chapter-indicator {
      padding: 4px 12px;
      background: var(--el-color-primary-light-9);
      border-radius: 4px;
      font-size: 14px;
      color: var(--el-color-primary);
    }
  }

  .header-right {
    display: flex;
    gap: 8px;
  }
}

.editor-content {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.chapter-info-form,
.scene-setting,
.plot-points-section,
.emotion-curve,
.key-dialogue {
  background: var(--el-bg-color);
  padding: 16px;
  border-radius: 8px;

  h4 {
    margin: 0 0 12px;
    font-size: 14px;
    color: var(--el-text-color-primary);
  }
}

.plot-points-cards {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 12px;
}

.plot-point-card {
  padding: 12px;
  border: 1px solid var(--el-border-color-light);
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;

  &:hover {
    border-color: var(--el-color-primary);
  }

  &.is-active {
    border-color: var(--el-color-primary);
    background: var(--el-color-primary-light-9);
  }

  .point-number {
    font-size: 18px;
    font-weight: bold;
    color: var(--el-color-primary);
    margin-bottom: 8px;
  }

  .point-content {
    .point-title-input {
      margin-bottom: 8px;
    }
  }

  .point-actions {
    display: flex;
    gap: 4px;
    margin-top: 8px;
    justify-content: flex-end;
  }
}

.add-point-row {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: 12px;

  .point-hint {
    font-size: 12px;
    color: var(--el-text-color-secondary);
  }
}

.emotion-curve {
  .curve-display {
    .curve-canvas {
      position: relative;
      height: 120px;
      background: var(--el-fill-color-light);
      border-radius: 4px;
      padding: 20px;

      .curve-line {
        position: relative;
        height: 80px;
        border-bottom: 2px solid var(--el-border-color);
        display: flex;
        justify-content: space-between;
        align-items: flex-end;

        .curve-point {
          position: absolute;
          transform: translateX(-50%);

          .point-marker {
            display: block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--el-color-primary);
            cursor: pointer;
            transition: transform 0.2s;

            &:hover {
              transform: scale(1.3);
            }
          }
        }
      }

      .curve-labels {
        display: flex;
        justify-content: space-between;
        margin-top: 8px;
        font-size: 12px;
        color: var(--el-text-color-secondary);
      }
    }

    .curve-legend {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      margin-top: 12px;

      .legend-item {
        font-size: 12px;
        color: var(--el-text-color-secondary);
      }
    }
  }
}

.dialogue-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.dialogue-item {
  padding: 12px;
  background: var(--el-fill-color-light);
  border-radius: 4px;

  .dialogue-speaker {
    font-weight: 600;
    color: var(--el-color-primary);
    margin-bottom: 4px;
  }

  .dialogue-content {
    font-style: italic;
    margin-bottom: 4px;
  }

  .dialogue-purpose {
    font-size: 12px;
    color: var(--el-text-color-secondary);
  }
}

.empty-dialogue {
  padding: 24px;
  text-align: center;
  color: var(--el-text-color-secondary);
}

.add-dialogue-btn {
  margin-top: 12px;
}
</style>
