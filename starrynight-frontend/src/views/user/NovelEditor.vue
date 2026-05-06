<template>
  <div class="novel-editor page-container">
    <div class="page-header">
      <div>
        <h1>作品编辑工作台</h1>
        <p class="desc">按开发文档提供大纲、卷纲、细纲、正文四种模式切换。</p>
      </div>
      <div class="header-actions">
        <span v-if="autoSaveStatus" class="auto-save-status" :class="autoSaveStatus">
          <el-icon v-if="autoSaveStatus === 'saving'"><Loading /></el-icon>
          <el-icon v-else-if="autoSaveStatus === 'saved'"><CircleCheck /></el-icon>
          <el-icon v-else-if="autoSaveStatus === 'error'"><CircleClose /></el-icon>
          {{ autoSaveStatusText }}
        </span>
        <el-dropdown @command="handleQuickAction">
          <el-button type="default">
            快捷工具<el-icon class="el-icon--right"><arrow-down /></el-icon>
          </el-button>
          <template #dropdown>
            <el-dropdown-menu>
              <el-dropdown-item command="knowledge">知识库</el-dropdown-item>
              <el-dropdown-item command="prompt">提示词库</el-dropdown-item>
              <el-dropdown-item command="material">素材库</el-dropdown-item>
              <el-dropdown-item command="character">角色库</el-dropdown-item>
              <el-dropdown-item command="style">风格扩写</el-dropdown-item>
              <el-dropdown-item command="toolbox">工具箱</el-dropdown-item>
            </el-dropdown-menu>
          </template>
        </el-dropdown>
        <el-button type="primary" :loading="saving" @click="saveCurrent">保存</el-button>
      </div>
    </div>

    <el-card>
      <el-tabs v-model="activeMode">
        <el-tab-pane label="大纲编辑" name="outline" />
        <el-tab-pane label="卷纲编辑" name="volume" />
        <el-tab-pane label="细纲编辑" name="chapter-outline" />
        <el-tab-pane label="正文编辑" name="content" />
      </el-tabs>

      <div class="editor-toolbar">
        <el-button size="small" @click="showKnowledgeRef = !showKnowledgeRef">
          <el-icon><Link /></el-icon>
          @知识库引用        </el-button>
        <el-button size="small" type="warning" @click="showContentConsistencyCheck = true">
          <el-icon><DocumentChecked /></el-icon>
          一致性检查        </el-button>
        <el-button size="small" type="primary" @click="showAiChat = true">
          <el-icon><ChatDotRound /></el-icon>
          AI对话
        </el-button>
      </div>

      <div v-if="showKnowledgeRef" class="knowledge-ref-panel">
        <KnowledgeReference @select="handleKnowledgeSelect" />
      </div>

      <div
        v-if="activeMode !== 'chapter-outline'"
        class="editor-drop-zone"
        :class="{ 'drag-over': isDragOver }"
        @dragover="handleDragOver"
        @dragleave="handleDragLeave"
        @drop="handleDrop"
      >
        <el-input
          v-model="content"
          type="textarea"
          :rows="16"
          :placeholder="placeholderText"
        />
        <div v-if="isDragOver" class="drop-overlay">
          <el-icon :size="48"><Upload /></el-icon>
          <span>松开鼠标插入素材</span>
        </div>
      </div>
      <div v-if="activeMode === 'outline'" class="action-row mt8">
        <el-button type="primary" plain :loading="outlineGenerateLoading" @click="generateOutlinePlan">
          AI生成大纲
        </el-button>
      </div>

      <div v-if="activeMode === 'volume'" class="chapter-draft-panel">
        <el-divider>卷纲生成（按开发文档）</el-divider>
        <div class="row">
          <el-input-number v-model="volumeGenerateCount" :min="1" :max="12" />
          <el-input v-model="content" placeholder="可选：粘贴大纲摘要辅助生成分卷" />
        </div>
        <div class="action-row">
          <el-button type="primary" :loading="volumeGenerateLoading" @click="generateVolumesPlan">生成分卷建议</el-button>
          <el-button :disabled="volumeDrafts.length === 0" :loading="saving" @click="saveGeneratedVolumes">保存分卷到后端</el-button>
        </div>
        <div v-if="volumeDrafts.length > 0" class="draft-cards">
          <el-card v-for="(v, idx) in volumeDrafts" :key="idx" class="draft-card">
            <template #header>
              <div class="draft-card-header">
                <span>第{{ v.volumeOrder }} 卷</span>
              </div>
            </template>
            <el-input v-model="v.title" placeholder="卷标题" />
            <el-input v-model="v.description" class="mt8" type="textarea" :rows="4" placeholder="卷描述" />
          </el-card>
        </div>
      </div>

      <div v-if="activeMode === 'chapter-outline'" class="chapter-draft-panel">
        <el-divider>章节细纲生成（按开发文档）</el-divider>
        <div class="row">
          <el-select v-model="draftForm.volumeId" placeholder="选择卷" filterable>
            <el-option v-for="v in volumes" :key="v.id" :label="v.title" :value="v.id" />
          </el-select>
          <el-input-number v-model="draftForm.chapterCount" :min="1" :max="20" />
          <el-input-number v-model="draftForm.targetWordCount" :min="500" :max="10000" :step="100" />
          <el-input v-model="draftForm.chapterType" placeholder="章节类型（可选）" />
        </div>
        <div class="action-row">
          <el-button type="primary" :loading="draftPlanLoading" @click="generateChapterDraftPlan">生成细纲卡片</el-button>
          <el-button :disabled="chapterDraftCards.length === 0" @click="runBackendConnectionCheck">衔接检查</el-button>
          <el-button :disabled="chapterDraftCards.length === 0" @click="saveCurrent">保存细纲到后端</el-button>
        </div>
        <div v-if="chapterDraftCards.length > 0" class="draft-cards">
          <el-card v-for="(card, idx) in chapterDraftCards" :key="`${card.chapterNo}-${idx}`" class="draft-card">
            <template #header>
              <div class="draft-card-header">
                <span>第{{ card.chapterNo }} 章</span>
                <div class="draft-card-actions">
                  <el-tag size="small">{{ card.status }}</el-tag>
                  <el-button link type="primary" @click="regenerateSingleDraft(card)">重生成本章</el-button>
                </div>
              </div>
            </template>
            <el-alert
              v-for="(msg, mi) in getCardIssues(card.chapterNo)"
              :key="`${card.chapterNo}-${mi}`"
              :title="msg"
              :type="issueLevelToType(card.chapterNo, mi)"
              :closable="false"
              show-icon
              class="mb8"
            />
            <el-input v-model="card.title" placeholder="章节标题" />
            <el-input v-model="card.coreEvent" class="mt8" placeholder="核心事件" />
            <el-input
              :model-value="formatDraftContent(card)"
              class="mt8"
              type="textarea"
              :rows="8"
              readonly
            />
          </el-card>
        </div>
      </div>

      <div class="workshop" v-if="activeMode === 'content'">
        <el-divider>章节生成车间（核心接入）</el-divider>
        <el-input v-model="intent.coreEvent" placeholder="核心事件（必填）" />
        <div class="row">
          <el-input v-model="intent.sceneLocation" placeholder="场景地点" />
          <el-input v-model="intent.atmosphere" placeholder="氛围" />
          <el-input v-model="intent.emotionalTone" placeholder="情绪基调" />
        </div>
        <div class="row">
          <el-select v-model="intent.generationMode" placeholder="生成模式">
            <el-option label="保守（conservative）" value="conservative" />
            <el-option label="均衡（balanced）" value="balanced" />
            <el-option label="创意（creative）" value="creative" />
          </el-select>
          <el-select
            v-if="intent.generationMode === 'creative'"
            v-model="intent.rewriteStrength"
            placeholder="创意强度"
          >
            <el-option label="保守控制" value="conservative" />
            <el-option label="风格增强" value="stylized" />
          </el-select>
          <el-input v-else placeholder="仅创意模式可调强度" disabled />
          <el-select v-model="intent.candidateCount" placeholder="候选数量">
            <el-option label="A/B 双版本" :value="2" />
            <el-option label="单版本" :value="1" />
            <el-option label="三版本" :value="3" />
          </el-select>
          <el-input v-model="characterIdsText" placeholder="出场角色ID，逗号分隔" />
        </div>
        <div class="action-row">
          <el-button type="success" :loading="workshopLoading" @click="runWorkshop">生成 C-Prompt 预览</el-button>
          <el-button type="primary" :loading="draftLoading" @click="generateDraft">生成正文草稿</el-button>
          <el-button
            v-if="selectedDraft"
            type="warning"
            plain
            @click="applyDraftToEditor"
          >
            一键回填到正文编辑器          </el-button>
          <el-button
            v-if="content.trim()"
            type="primary"
            plain
            :loading="continueLoading"
            @click="runContinueWriting"
          >
            智能续写
          </el-button>
          <el-button
            v-if="content.trim()"
            plain
            :loading="consistencyLoading"
            @click="runAiConsistencyCheck"
          >
            AI一致性检查          </el-button>
          <el-button
            v-if="content.trim()"
            plain
            :loading="plotSuggestionLoading"
            @click="runPlotSuggestion"
          >
            情节建议
          </el-button>
          <el-button
            v-if="expandForm.chapterOutlineId && content.trim()"
            :loading="expandVersionLoading"
            @click="saveCurrentDraftVersion"
          >
            保存正文草稿版本
          </el-button>
        </div>

        <el-alert
          v-if="workshopResult"
          class="result-status"
          :title="workshopResult.consistencyReport?.passed ? '一致性自检通过' : '一致性自检发现问题'"
          :type="workshopResult.consistencyReport?.passed ? 'success' : 'warning'"
          show-icon
          :closable="false"
        />
        <el-input
          v-if="workshopResult"
          v-model="workshopResult.cPrompt"
          type="textarea"
          :rows="10"
          readonly
          placeholder="C-Prompt 将显示在这里"
        />
        <el-radio-group v-if="draftOptions.length > 1" v-model="selectedDraftIndex" class="draft-switch">
          <el-radio-button
            v-for="(_, idx) in draftOptions"
            :key="idx"
            :label="idx"
          >
            {{ draftLabels[idx] || `版本 ${String.fromCharCode(65 + idx)}` }}
          </el-radio-button>
        </el-radio-group>
        <el-input
          v-if="selectedDraft"
          :model-value="selectedDraft"
          class="draft-box"
          type="textarea"
          :rows="12"
          readonly
          placeholder="候选正文将显示在这里"
        />
        <el-card v-if="plotSuggestions.length > 0" class="draft-box">
          <template #header>情节建议（按开发文档）</template>
          <el-alert
            v-for="(s, idx) in plotSuggestions"
            :key="idx"
            :title="`${idx + 1}. ${s}`"
            type="info"
            :closable="false"
            show-icon
            class="mb8"
          />
        </el-card>

        <el-divider>正文扩写（按开发文档）</el-divider>
        <div class="row">
          <el-select v-model="expandForm.volumeId" placeholder="选择卷（用于筛选细纲）" filterable>
            <el-option v-for="v in volumes" :key="v.id" :label="v.title" :value="v.id" />
          </el-select>
          <el-select v-model="expandForm.chapterOutlineId" placeholder="选择章节细纲" filterable>
            <el-option
              v-for="o in chapterOutlines"
              :key="o.id"
              :label="`${o.sortOrder ?? ''} ${o.title}`"
              :value="o.id"
            />
          </el-select>
          <el-input-number v-model="expandForm.expandRatio" :min="1" :max="5" />
          <el-checkbox v-model="expandForm.optimizeConnections">衔接优化</el-checkbox>
          <el-checkbox v-model="expandForm.postProcessEnabled">后处理</el-checkbox>
          <div class="generation-status" v-if="isGenerating">
            <el-icon class="is-loading"><Loading /></el-icon>
            <span>AI正在创作中，请稍候…</span>
          </div>
          <el-button type="primary" :loading="expandLoading" :disabled="isGenerating" @click="runContentExpand">扩写预演</el-button>
          <el-button v-if="expandResult?.content" type="warning" plain @click="applyExpandedToEditor">
            回填到正文编辑器
          </el-button>
          <el-button v-if="expandResult?.content" :loading="expandVersionLoading" @click="saveCurrentExpandedVersion">
            保存扩写版本
          </el-button>
          <el-button v-if="expandForm.chapterOutlineId" :loading="expandVersionLoading" @click="loadContentVersions">
            刷新版本列表
          </el-button>
        </div>
        <el-input
          v-model="expandForm.styleSample"
          type="textarea"
          :rows="4"
          placeholder="可选：粘贴风格样本（用于简单风格指纹分析）"
        />
        <el-input
          v-if="expandResult?.content"
          :model-value="expandResult.content"
          class="draft-box"
          type="textarea"
          :rows="12"
          readonly
          placeholder="扩写正文预演结果"
        />
        <div v-if="expandResult?.generationPlan?.length" class="plan-list">
          <el-card v-for="plan in expandResult.generationPlan" :key="plan.type" class="plan-card">
            <div class="plan-title">{{ plan.type }}</div>
            <div class="plan-meta">句数目标：{{ plan.sentenceCount }}</div>
            <div class="plan-meta">{{ plan.strategy }}</div>
            <el-button link type="primary" @click="regenerateSegment(plan.type)">重生成该段</el-button>
          </el-card>
        </div>
        <div v-if="expandResult?.segments?.length" class="segments-list">
          <el-card v-for="seg in expandResult.segments" :key="seg.type" class="segment-card">
            <template #header>
              <div class="segment-head">
                <span>{{ seg.type }}</span>
                <el-button link type="primary" @click="regenerateSegment(seg.type)">重生成该段</el-button>
              </div>
            </template>
            <el-input :model-value="seg.text" type="textarea" :rows="6" readonly />
          </el-card>
        </div>
        <div v-if="expandVersions.length > 0" class="version-panel">
          <el-divider>版本时间线（草稿 + 扩写）</el-divider>
          <div class="row">
            <el-select v-model="selectedVersionId" placeholder="选择历史版本" filterable>
              <el-option
                v-for="v in expandVersions"
                :key="v.id"
                :label="`${v.sourceType === 'draft' ? '草稿' : '扩写'} · ${v.title}（${v.wordCount}字）`"
                :value="v.id"
              />
            </el-select>
            <el-button
              :disabled="!selectedVersionId"
              :loading="expandVersionLoading"
              type="primary"
              plain
              @click="rollbackSelectedVersion"
            >
              回滚到该版本
            </el-button>
          </div>
        </div>
      </div>

      <div class="tips">
        <el-alert
          title="当前已实现：大纲保存与版本递增。卷纲、细纲、正文将按文档继续补齐。"
          type="info"
          show-icon
          :closable="false"
        />
      </div>

      <el-dialog v-model="showContentConsistencyCheck" title="正文一致性检查" width="720px">
        <div v-if="checkingContent" v-loading="checkingContent" class="checking-overlay"></div>

        <div v-if="contentCheckResults.length === 0 && !checkingContent" class="empty-check">
          <p>点击「开始检查」扫描当前正文内容，检测角色设定冲突。</p>
          <el-button type="primary" :loading="checkingContent" @click="performContentCheck">开始检查</el-button>
        </div>

        <div v-if="contentCheckResults.length > 0" class="check-results">
          <div class="results-summary">
            <el-tag :type="contentCheckResults.length > 0 ? 'danger' : 'success'" size="large">
              发现 {{ contentCheckResults.length }} 个问题            </el-tag>
          </div>
          <div v-for="(issue, idx) in contentCheckResults" :key="idx" class="issue-item">
            <div class="issue-header">
              <el-tag :type="issue.severity === 'error' ? 'danger' : 'warning'" size="small">
                {{ issue.severity === 'error' ? '严重' : '警告' }}
              </el-tag>
              <span class="issue-character">{{ issue.character }}</span>
            </div>
            <div class="issue-content">
              <p class="issue-desc">{{ issue.description }}</p>
              <div class="issue-evidence">"{{ issue.evidence }}"</div>
              <div class="issue-suggestion"><strong>建议：</strong>{{ issue.suggestion }}</div>
            </div>
          </div>
        </div>
      </el-dialog>
    </el-card>

    <AiChatPanel
      v-model="showAiChat"
      :current-node="currentNodeForChat"
      :outline-summary="outlineSummaryForChat"
      :novel-id="novelId"
      @accept-modification="handleAcceptModification"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { Link, Loading, CircleCheck, CircleClose, DocumentChecked, ChatDotRound, Upload } from '@element-plus/icons-vue'
import { createNovelVolume, listNovelOutlines, listNovelVolumes, upsertNovelOutline, listOutlineVersions, rollbackOutlineVersion } from '@/api/novelOutline'
import { checkCharacterConsistency } from '@/api/character'
import { listenEditorPrompt, clearEditorPrompt, type EditorPrompt } from '@/utils/editorPrompt'
import KnowledgeReference from './components/KnowledgeReference.vue'
import AiChatPanel from './components/AiChatPanel.vue'
import {
  chapterWorkshopGenerate,
  chapterWorkshopPreview,
  checkChapterDraftConnections,
  generateChapterDraftsByAi,
  expandContentByAi,
  checkConsistencyByAi,
  getPlotSuggestionByAi,
  generateVolumesByAi,
  generateOutlineByAi,
  listChapterVersionsTimeline,
  rollbackChapterVersion,
  saveContentDraftVersion,
  saveContentExpandVersion,
  continueWriting
} from '@/api/novelWorkshop'
import type {
  ChapterDraftConnectionIssue,
  ChapterDraftItem,
  ChapterWorkshopResult,
  ConsistencyReport,
  ContentExpandResult,
  ContentVersionItem,
  PlotSuggestionResult,
  NovelOutlineItem,
  NovelVolume
} from '@/types/api'

const activeMode = ref('outline')
const content = ref('')
const saving = ref(false)
const workshopLoading = ref(false)
const draftLoading = ref(false)
const draftPlanLoading = ref(false)
const connectionCheckLoading = ref(false)
const workshopResult = ref<ChapterWorkshopResult | null>(null)
const workshopHistory = ref<{ timestamp: Date; result: ChapterWorkshopResult }[]>([])
const chapterOutlines = ref<NovelOutlineItem[]>([])
const expandLoading = ref(false)
const expandResult = ref<ContentExpandResult | null>(null)
const expandVersionLoading = ref(false)
const expandVersions = ref<ContentVersionItem[]>([])
const selectedVersionId = ref<number | undefined>(undefined)
const continueLoading = ref(false)
const consistencyLoading = ref(false)
const plotSuggestionLoading = ref(false)
const showKnowledgeRef = ref(false)
const plotSuggestions = ref<string[]>([])
const volumeGenerateCount = ref(3)
const volumeGenerateLoading = ref(false)
const outlineGenerateLoading = ref(false)
const isGenerating = ref(false)
type VolumeDraft = { title: string; description?: string; volumeOrder: number }
const volumeDrafts = ref<VolumeDraft[]>([])
const expandForm = ref({
  volumeId: undefined as number | undefined,
  chapterOutlineId: undefined as number | undefined,
  expandRatio: 2,
  styleSample: '',
  optimizeConnections: true,
  postProcessEnabled: true
})
const volumes = ref<NovelVolume[]>([])
const chapterDraftCards = ref<ChapterDraftItem[]>([])
const draftForm = ref({
  volumeId: undefined as number | undefined,
  chapterCount: 6,
  targetWordCount: 2500,
  chapterType: 'standard'
})
const characterIdsText = ref('')
type WorkshopIntentForm = {
  coreEvent: string
  sceneLocation: string
  atmosphere: string
  emotionalTone: string
  generationMode?: 'conservative' | 'balanced' | 'creative'
  rewriteStrength?: 'conservative' | 'stylized'
  candidateCount?: number
}
const intent = ref<WorkshopIntentForm>({
  coreEvent: '',
  sceneLocation: '',
  atmosphere: '',
  emotionalTone: '',
  generationMode: 'conservative' as const,
  rewriteStrength: 'conservative' as 'conservative' | 'stylized',
  candidateCount: 2
})
const selectedDraftIndex = ref(0)
const autoSaveEnabled = ref(true)
const autoSaveStatus = ref<'saving' | 'saved' | 'error' | ''>('')
const autoSaveStatusText = ref('')
const showContentConsistencyCheck = ref(false)
const contentCheckResults = ref<any[]>([])
const isDragOver = ref(false)
const checkingContent = ref(false)
const showAiChat = ref(false)
let autoSaveTimer: ReturnType<typeof setTimeout> | null = null
let lastSavedContent = ''

const draftOptions = computed(() => {
  const drafts = workshopResult.value?.generatedDrafts || []
  if (drafts.length > 0) return drafts
  return workshopResult.value?.generatedDraft ? [workshopResult.value.generatedDraft] : []
})

const selectedDraft = computed(() => draftOptions.value[selectedDraftIndex.value] || '')

const draftLabels = computed(() => workshopResult.value?.generatedDraftLabels || [])
const backendIssues = ref<ChapterDraftConnectionIssue[]>([])

const currentNodeForChat = computed(() => {
  const modeMap: Record<string, string> = {
    outline: '大纲',
    volume: '卷纲',
    'chapter-outline': '细纲',
    content: '正文'
  }
  return {
    type: modeMap[activeMode.value] || '未知',
    title: activeMode.value === 'chapter-outline' ? `第${draftForm.value.chapterCount}章细纲` : `${modeMap[activeMode.value]}内容`,
    content: content.value
  }
})

const outlineSummaryForChat = computed(() => {
  if (chapterOutlines.value.length > 0) {
    return chapterOutlines.value.map(o => `${o.sortOrder}. ${o.title}: ${o.content}`).join('\n')
  }
  return volumes.value.map(v => `第${v.volumeOrder}卷: ${v.title}`).join('\n')
})

function handleAcceptModification(newContent: string) {
  content.value = newContent
  ElMessage.success('已应用AI修改建议')
}

const route = useRoute()
const router = useRouter()

let unsubscribePrompt: (() => void) | null = null

onMounted(() => {
  unsubscribePrompt = listenEditorPrompt((prompt: EditorPrompt) => {
    if (prompt.type === 'prompt') {
      const promptText = `\n===== 提示词：${prompt.promptTitle} =====\n${prompt.promptContent}\n`
      content.value += promptText
      ElMessage.success('已接收到提示词并插入到编辑器')
    }
  })
})

onUnmounted(() => {
  if (unsubscribePrompt) {
    unsubscribePrompt()
  }
  clearEditorPrompt()
  if (autoSaveTimer) {
    clearTimeout(autoSaveTimer)
  }
})

interface KnowledgeChunk {
  id: number
  knowledgeId: number
  content: string
}

function handleKnowledgeSelect(item: KnowledgeChunk) {
  const reference = `\n【引用知识库。{item.content}\n`
  content.value += reference
  showKnowledgeRef.value = false
  ElMessage.success('已插入知识库引用')
}

function handleDragOver(event: DragEvent) {
  event.preventDefault()
  if (event.dataTransfer) {
    event.dataTransfer.dropEffect = 'copy'
  }
  isDragOver.value = true
}

function handleDragLeave() {
  isDragOver.value = false
}

function handleDrop(event: DragEvent) {
  event.preventDefault()
  isDragOver.value = false

  if (!event.dataTransfer) return

  const jsonData = event.dataTransfer.getData('application/json')
  if (jsonData) {
    try {
      const material = JSON.parse(jsonData)
      let insertText = ''

      if (material.type === 'golden_finger') {
        insertText = `\n【金手指。{material.title}\n${JSON.stringify(material.content, null, 2)}\n`
      } else if (material.type === 'worldview') {
        insertText = `\n【世界观设定。{material.title}\n${material.description || ''}\n`
      } else if (material.type === 'character_draft') {
        insertText = `\n【角色草稿。{material.title}\n${JSON.stringify(material.content, null, 2)}\n`
      } else if (material.type === 'conflict_idea') {
        insertText = `\n【冲突桥段。{material.title}\n${material.description || ''}\n`
      } else {
        insertText = `\n【素材。{material.title}\n${material.description || JSON.stringify(material.content, null, 2)}\n`
      }

      content.value += insertText
      ElMessage.success('素材已插入编辑器')
    } catch {
      ElMessage.error('素材格式错误')
    }
  }
}

function handleQuickAction(command: string) {
  const routes: Record<string, string> = {
    knowledge: '/knowledge-library',
    prompt: '/prompt-library',
    material: '/material-library',
    character: '/character-library',
    style: '/style-expand',
    toolbox: '/toolbox'
  }
  if (routes[command]) {
    router.push(routes[command])
  }
}
const novelId = Number(route.params.id)

async function loadOutline() {
  if (!novelId) return
  const res = await listNovelOutlines({ novelId, type: 'outline' })
  const root = res.data?.[0]
  content.value = root?.content || ''
}

async function loadVolumes() {
  if (!novelId) return
  const res = await listNovelVolumes(novelId)
  volumes.value = res.data || []
  if (!draftForm.value.volumeId && volumes.value.length > 0) {
    draftForm.value.volumeId = volumes.value[0].id
  }
  if (!expandForm.value.volumeId && volumes.value.length > 0) {
    expandForm.value.volumeId = volumes.value[0].id
  }
}

async function loadChapterOutlines() {
  if (!novelId || !expandForm.value.volumeId) return
  const res = await listNovelOutlines({
    novelId,
    type: 'chapter_outline',
    volumeId: expandForm.value.volumeId
  })
  chapterOutlines.value = res.data || []
  if (!expandForm.value.chapterOutlineId && chapterOutlines.value.length > 0) {
    expandForm.value.chapterOutlineId = chapterOutlines.value[0].id
  }
}

watch(
  () => expandForm.value.volumeId,
  async () => {
    expandForm.value.chapterOutlineId = undefined
    expandVersions.value = []
    selectedVersionId.value = undefined
    await loadChapterOutlines()
  }
)

watch(
  () => expandForm.value.chapterOutlineId,
  async () => {
    expandVersions.value = []
    selectedVersionId.value = undefined
    if (expandForm.value.chapterOutlineId) {
      await loadContentVersions()
    }
  }
)

watch(
  () => content.value,
  () => {
    if (!autoSaveEnabled.value || activeMode.value !== 'content') return
    triggerAutoSave()
  }
)

function triggerAutoSave() {
  if (autoSaveTimer) {
    clearTimeout(autoSaveTimer)
  }
  autoSaveStatus.value = ''
  autoSaveTimer = setTimeout(async () => {
    if (content.value === lastSavedContent) return
    await performAutoSave()
  }, 30000)
}

async function performContentCheck() {
  if (!content.value.trim()) {
    ElMessage.warning('正文内容为空，无法进行检查')
    return
  }

  checkingContent.value = true
  contentCheckResults.value = []
  try {
    const res = await checkCharacterConsistency({
      novelId: novelId.value,
      content: content.value
    })
    if (res.data) {
      contentCheckResults.value = res.data
      if (contentCheckResults.value.length === 0) {
        ElMessage.success('未发现角色一致性问题')
      } else {
        ElMessage.warning(`发现 ${contentCheckResults.value.length} 个潜在问题`)
      }
    }
  } catch {
    ElMessage.error('一致性检查失败，请重试')
  } finally {
    checkingContent.value = false
  }
}

async function performAutoSave() {
  if (!novelId || !autoSaveEnabled.value) return
  if (activeMode.value !== 'content' && activeMode.value !== 'outline') return

  autoSaveStatus.value = 'saving'
  autoSaveStatusText.value = '保存中..'
  try {
    if (activeMode.value === 'outline') {
      await upsertNovelOutline({
        novelId,
        type: 'outline',
        title: '作品大纲',
        content: content.value,
        sortOrder: 0,
        parentId: null as any
      })
    }
    lastSavedContent = content.value
    autoSaveStatus.value = 'saved'
    autoSaveStatusText.value = '已保存'
    setTimeout(() => {
      if (autoSaveStatus.value === 'saved') {
        autoSaveStatus.value = ''
      }
    }, 3000)
  } catch {
    autoSaveStatus.value = 'error'
    autoSaveStatusText.value = '保存失败'
  }
}

async function saveCurrent() {
  if (!novelId) return
  saving.value = true
  try {
    if (activeMode.value === 'outline') {
      await upsertNovelOutline({
        novelId,
        type: 'outline',
        title: '作品大纲',
        content: content.value,
        sortOrder: 0,
        parentId: null as any
      })
      ElMessage.success('大纲已保存')
      await loadOutline()
      return
    }
    if (activeMode.value === 'chapter-outline') {
      if (!draftForm.value.volumeId) {
        ElMessage.warning('请先选择卷')
        return
      }
      if (chapterDraftCards.value.length === 0) {
        ElMessage.warning('请先生成章节细纲卡片')
        return
      }
      for (const card of chapterDraftCards.value) {
        await upsertNovelOutline({
          novelId,
          volumeId: draftForm.value.volumeId,
          type: 'chapter_outline',
          title: card.title,
          content: formatDraftContent(card),
          sortOrder: card.chapterNo,
          parentId: undefined
        })
      }
      ElMessage.success('章节细纲已保存')
      return
    }
    if (activeMode.value === 'volume') {
      if (volumeDrafts.value.length === 0) {
        ElMessage.warning('请先生成分卷建议')
        return
      }
      await saveGeneratedVolumes()
      return
    }
    ElMessage.info('该模式尚未接入保存接口')
  } finally {
    saving.value = false
  }
}

async function generateOutlinePlan() {
  if (!novelId) return
  outlineGenerateLoading.value = true
  try {
    const res = await generateOutlineByAi({
      novelId,
      coreIdea: content.value || undefined
    })
    if (res.data?.content) {
      content.value = res.data.content
      ElMessage.success('AI大纲已生成并回填')
    } else {
      ElMessage.warning('未生成到有效大纲内容')
    }
  } finally {
    outlineGenerateLoading.value = false
  }
}

async function generateVolumesPlan() {
  if (!novelId) return
  volumeGenerateLoading.value = true
  try {
    const res = await generateVolumesByAi({
      novelId,
      volumeCount: volumeGenerateCount.value
    })
    volumeDrafts.value = (res.data || []).map((v) => ({
      title: v.title,
      description: v.description,
      volumeOrder: v.volumeOrder
    }))
    ElMessage.success(`已生成${volumeDrafts.value.length} 卷建议`)
  } finally {
    volumeGenerateLoading.value = false
  }
}

async function saveGeneratedVolumes() {
  if (!novelId || volumeDrafts.value.length === 0) return
  saving.value = true
  try {
    for (const v of volumeDrafts.value) {
      await createNovelVolume({
        novelId,
        title: v.title,
        description: v.description || '',
        volumeOrder: v.volumeOrder
      })
    }
    await loadVolumes()
    ElMessage.success('分卷已保存到后端')
  } finally {
    saving.value = false
  }
}

async function generateChapterDraftPlan() {
  if (!novelId) return
  if (!draftForm.value.volumeId) {
    ElMessage.warning('请先选择卷')
    return
  }
  draftPlanLoading.value = true
  try {
    const res = await generateChapterDraftsByAi({
      volumeId: draftForm.value.volumeId,
      chapterCount: draftForm.value.chapterCount,
      targetWordCount: draftForm.value.targetWordCount,
      chapterType: draftForm.value.chapterType || undefined
    })
    chapterDraftCards.value = res.data || []
    backendIssues.value = []
    ElMessage.success(`已生成${chapterDraftCards.value.length} 张细纲卡片`)
  } finally {
    draftPlanLoading.value = false
  }
}

async function runBackendConnectionCheck() {
  if (!draftForm.value.volumeId) {
    ElMessage.warning('请先选择卷')
    return
  }
  if (chapterDraftCards.value.length === 0) return
  connectionCheckLoading.value = true
  try {
    const res = await checkChapterDraftConnections({
      volumeId: draftForm.value.volumeId,
      drafts: chapterDraftCards.value
    })
    backendIssues.value = res.data || []
    ElMessage.success('衔接检查完成')
  } finally {
    connectionCheckLoading.value = false
  }
}

function getCardIssues(chapterNo: number) {
  return backendIssues.value
    .filter((i) => i.chapterNo === chapterNo)
    .map((i) => (i.suggestion ? `${i.message}（${i.suggestion}）` : i.message))
}

function issueLevelToType(chapterNo: number, issueIndex: number) {
  const issues = backendIssues.value.filter((i) => i.chapterNo === chapterNo)
  const level = issues[issueIndex]?.level || 'info'
  if (level === 'error') return 'error'
  if (level === 'warn') return 'warning'
  return 'info'
}

async function regenerateSingleDraft(card: ChapterDraftItem) {
  if (!draftForm.value.volumeId) {
    ElMessage.warning('请先选择卷')
    return
  }
  try {
    const res = await generateChapterDraftsByAi({
      volumeId: draftForm.value.volumeId,
      chapterCount: draftForm.value.chapterCount,
      chapterNo: card.chapterNo,
      targetWordCount: draftForm.value.targetWordCount,
      chapterType: draftForm.value.chapterType || undefined
    })
    const regenerated = res.data?.[0]
    if (!regenerated) {
      ElMessage.warning('未生成到新细纲，请重试')
      return
    }
    const index = chapterDraftCards.value.findIndex((item) => item.chapterNo === card.chapterNo)
    if (index >= 0) {
      chapterDraftCards.value[index] = regenerated
    }
    ElMessage.success(`第${card.chapterNo}章已重生成`)
  } catch (e) {
    ElMessage.error('重生成失败')
  }
}

async function runWorkshop() {
  if (!novelId) return
  if (!intent.value.coreEvent.trim()) {
    ElMessage.warning('请先输入核心事件')
    return
  }
  workshopLoading.value = true
  try {
    const presentCharacterIds = characterIdsText.value
      .split(',')
      .map((v) => v.trim())
      .filter(Boolean)
    const res = await chapterWorkshopPreview({
      novelId,
      coreEvent: intent.value.coreEvent,
      sceneLocation: intent.value.sceneLocation || undefined,
      atmosphere: intent.value.atmosphere || undefined,
      emotionalTone: intent.value.emotionalTone || undefined,
      generationMode: intent.value.generationMode || undefined,
      rewriteStrength: intent.value.generationMode === 'creative' ? intent.value.rewriteStrength : undefined,
      sourceContent: content.value || undefined,
      presentCharacterIds,
      relatedOutlineNodes: []
    })
    workshopHistory.value.unshift({
      timestamp: new Date(),
      result: res.data
    })
    if (workshopHistory.value.length > 20) {
      workshopHistory.value.pop()
    }
    workshopResult.value = res.data
    selectedDraftIndex.value = 0
    ElMessage.success('车间预演完成')
  } finally {
    workshopLoading.value = false
  }
}

async function generateDraft() {
  if (!novelId) return
  if (!intent.value.coreEvent.trim()) {
    ElMessage.warning('请先输入核心事件')
    return
  }
  draftLoading.value = true
  try {
    const presentCharacterIds = characterIdsText.value
      .split(',')
      .map((v) => v.trim())
      .filter(Boolean)
    const res = await chapterWorkshopGenerate({
      novelId,
      coreEvent: intent.value.coreEvent,
      sceneLocation: intent.value.sceneLocation || undefined,
      atmosphere: intent.value.atmosphere || undefined,
      emotionalTone: intent.value.emotionalTone || undefined,
      generationMode: intent.value.generationMode || undefined,
      rewriteStrength: intent.value.generationMode === 'creative' ? intent.value.rewriteStrength : undefined,
      candidateCount: intent.value.candidateCount,
      sourceContent: content.value || undefined,
      presentCharacterIds,
      relatedOutlineNodes: []
    })
    workshopHistory.value.unshift({
      timestamp: new Date(),
      result: res.data
    })
    if (workshopHistory.value.length > 20) {
      workshopHistory.value.pop()
    }
    workshopResult.value = res.data
    selectedDraftIndex.value = 0
    ElMessage.success('候选正文已生成')
  } finally {
    draftLoading.value = false
  }
}

function applyDraftToEditor() {
  const draft = selectedDraft.value
  if (!draft) return
  content.value = draft
  ElMessage.success('已回填到正文编辑器')
}

async function runContinueWriting() {
  if (!content.value.trim()) {
    ElMessage.warning('请先输入/生成正文内容')
    return
  }
  continueLoading.value = true
  isGenerating.value = true
  try {
    const res = await continueWriting({
      sourceContent: content.value,
      expandRatio: expandForm.value.expandRatio,
      styleSample: expandForm.value.styleSample || undefined,
      optimizeConnections: expandForm.value.optimizeConnections,
      postProcessEnabled: expandForm.value.postProcessEnabled,
      novelId: novelId ?? undefined
    })
    content.value = res.data.content
    ElMessage.success('智能续写完成')
  } finally {
    continueLoading.value = false
    isGenerating.value = false
  }
}

async function runAiConsistencyCheck() {
  if (!content.value.trim()) {
    ElMessage.warning('请先输入/生成正文内容')
    return
  }
  consistencyLoading.value = true
  try {
    const presentCharacterIds = characterIdsText.value
      .split(',')
      .map((v) => v.trim())
      .filter(Boolean)
    const res = await checkConsistencyByAi({
      novelId,
      generatedText: content.value,
      coreEvent: intent.value.coreEvent || '正文一致性检查',
      sceneLocation: intent.value.sceneLocation || undefined,
      atmosphere: intent.value.atmosphere || undefined,
      emotionalTone: intent.value.emotionalTone || undefined,
      generationMode: intent.value.generationMode || undefined,
      presentCharacterIds,
      relatedOutlineNodes: []
    })
    const report = res.data as ConsistencyReport
    workshopResult.value = {
      ...(workshopResult.value || {
        cPrompt: '',
        recalledContext: [],
        generatedDraft: '',
        generatedDrafts: [],
        generatedDraftLabels: []
      }),
      consistencyReport: report
    }
    ElMessage.success(report.passed ? '一致性检查通过' : '一致性检查发现问题')
  } finally {
    consistencyLoading.value = false
  }
}

async function runPlotSuggestion() {
  if (!content.value.trim()) {
    ElMessage.warning('请先输入/生成正文内容')
    return
  }
  plotSuggestionLoading.value = true
  try {
    const res = await getPlotSuggestionByAi({
      novelId,
      currentContent: content.value,
      coreEvent: intent.value.coreEvent || undefined,
      sceneLocation: intent.value.sceneLocation || undefined,
      emotionalTone: intent.value.emotionalTone || undefined
    })
    const data = res.data as PlotSuggestionResult
    plotSuggestions.value = data.suggestions || []
    ElMessage.success(plotSuggestions.value.length > 0 ? '已生成情节建议' : '暂无可用建议')
  } finally {
    plotSuggestionLoading.value = false
  }
}

const placeholderText = computed(() => {
  const map: Record<string, string> = {
    outline: '输入或粘贴故事整体大纲…',
    volume: '输入卷纲目标、核心冲突和关键事件...',
    'chapter-outline': '输入章节细纲，包含开端/发展/高潮/结尾...',
    content: '输入正文内容，支持 AI 续写和润色…'
  }
  return map[activeMode.value] || '请输入内容…'
})

loadOutline()
loadVolumes()
loadChapterOutlines()

async function runContentExpand() {
  if (!expandForm.value.chapterOutlineId) {
    ElMessage.warning('请先选择章节细纲')
    return
  }
  expandLoading.value = true
  isGenerating.value = true
  try {
    const res = await expandContentByAi({
      chapterOutlineId: expandForm.value.chapterOutlineId,
      expandRatio: expandForm.value.expandRatio,
      styleSample: expandForm.value.styleSample || undefined,
      optimizeConnections: expandForm.value.optimizeConnections,
      postProcessEnabled: expandForm.value.postProcessEnabled
    })
    expandResult.value = res.data
    ElMessage.success('扩写预演完成')
  } finally {
    expandLoading.value = false
    isGenerating.value = false
  }
}

async function saveCurrentExpandedVersion() {
  if (!expandForm.value.chapterOutlineId || !expandResult.value?.content) {
    ElMessage.warning('没有可保存的扩写内容')
    return
  }
  expandVersionLoading.value = true
  try {
    await saveContentExpandVersion({
      chapterOutlineId: expandForm.value.chapterOutlineId,
      content: expandResult.value.content
    })
    ElMessage.success('扩写版本已保存')
    await loadContentVersions()
  } finally {
    expandVersionLoading.value = false
  }
}

async function saveCurrentDraftVersion() {
  if (!expandForm.value.chapterOutlineId || !content.value.trim()) {
    ElMessage.warning('没有可保存的正文草稿')
    return
  }
  expandVersionLoading.value = true
  try {
    await saveContentDraftVersion({
      chapterOutlineId: expandForm.value.chapterOutlineId,
      content: content.value
    })
    ElMessage.success('正文草稿版本已保存')
    await loadContentVersions()
  } finally {
    expandVersionLoading.value = false
  }
}

async function loadContentVersions() {
  if (!expandForm.value.chapterOutlineId) return
  expandVersionLoading.value = true
  try {
    const res = await listChapterVersionsTimeline(novelId, expandForm.value.chapterOutlineId)
    expandVersions.value = res.data || []
    if (!selectedVersionId.value && expandVersions.value.length > 0) {
      selectedVersionId.value = expandVersions.value[0].id
    }
  } finally {
    expandVersionLoading.value = false
  }
}

async function rollbackSelectedVersion() {
  if (!selectedVersionId.value) {
    ElMessage.warning('请先选择历史版本')
    return
  }
  if (!expandForm.value.chapterOutlineId) {
    ElMessage.warning('请先选择章节细纲')
    return
  }
  expandVersionLoading.value = true
  try {
    const res = await rollbackChapterVersion(novelId, expandForm.value.chapterOutlineId, selectedVersionId.value)
    const item = res.data
    if (!item?.content) {
      ElMessage.warning('回滚失败，版本内容为空')
      return
    }
    expandResult.value = {
      ...(expandResult.value || { segments: [], generationPlan: [], wordCount: 0 }),
      content: item.content,
      wordCount: item.wordCount || item.content.replace(/\s+/g, '').length
    }
    content.value = item.content
    ElMessage.success('已回滚并回填到正文编辑器')
  } finally {
    expandVersionLoading.value = false
  }
}

async function regenerateSegment(segmentType: string) {
  if (!expandForm.value.chapterOutlineId || !expandResult.value) return
  const prev = expandResult.value
  await runContentExpand()
  if (!expandResult.value) return
  const target = expandResult.value.segments.find((s) => s.type === segmentType)
  if (!target) {
    expandResult.value = prev
    return
  }
  const merged = prev.segments.map((s) => (s.type === segmentType ? target : s))
  expandResult.value = {
    ...expandResult.value,
    segments: merged,
    content: merged.map((s) => s.text).join('\n\n')
  }
  ElMessage.success(`已重生成 ${segmentType} 段`)
}

function applyExpandedToEditor() {
  const text = expandResult.value?.content
  if (!text) return
  content.value = text
  ElMessage.success('已回填到正文编辑器')
}

function formatDraftContent(card: ChapterDraftItem) {
  const lines: string[] = []
  lines.push(`核心事件：${card.coreEvent}`)
  lines.push(
    `场景：${card.sceneSetting.location} / ${card.sceneSetting.time} / ${card.sceneSetting.atmosphere}`
  )
  lines.push(`衔接：${card.connectionNote}`)
  lines.push('情节点：')
  card.plotPoints.forEach((p) =>
    lines.push(`${p.order}. [${p.type}] ${p.description}（${p.emotionalChange}）`)
  )
  lines.push('关键对白：')
  card.keyDialogues.forEach((d) =>
    lines.push(`- ${d.speaker}：${d.content}（${d.purpose}）`)
  )
  lines.push('伏笔：')
  card.foreshadowing.forEach((f) => lines.push(`- ${f.setup}（${f.type}）`))
  return lines.join('\n')
}

</script>

<style lang="scss" scoped>
.desc {
  margin-top: $space-xs;
  color: $text-secondary;
}

.tips {
  margin-top: $space-md;
}

.workshop {
  margin-top: $space-md;
}

.row {
  margin: $space-sm 0;
  display: grid;
  gap: $space-sm;
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.result-status {
  margin: $space-md 0 $space-sm;
}

.action-row {
  margin-bottom: $space-sm;
  display: flex;
  gap: $space-sm;
}

.draft-box {
  margin-top: $space-sm;
}

.draft-switch {
  margin-top: $space-sm;
}

.chapter-draft-panel {
  margin-top: $space-md;
}

.draft-cards {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: $space-sm;
}

.draft-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.mt8 {
  margin-top: 8px;
}

.mb8 {
  margin-bottom: 8px;
}

.draft-card-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

.plan-list,
.segments-list {
  margin-top: 8px;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 8px;
}

.plan-title {
  font-weight: 600;
}

.plan-meta {
  margin-top: 4px;
  color: $text-secondary;
}

.segment-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.version-panel {
  margin-top: 8px;
}

.editor-toolbar {
  margin-bottom: 12px;
}

.knowledge-ref-panel {
  margin-bottom: 16px;
}

.editor-drop-zone {
  position: relative;
  transition: all 0.3s;

  &.drag-over {
    outline: 2px dashed var(--el-color-primary);
    outline-offset: 4px;
    border-radius: 4px;
  }

  .drop-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: rgba(99, 102, 241, 0.1);
    border-radius: 4px;
    z-index: 10;
    color: var(--el-color-primary);
    font-size: 16px;
    gap: 8px;
  }
}

.auto-save-status {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  padding: 4px 12px;
  border-radius: 4px;

  &.saving {
    color: var(--el-color-primary);
  }

  &.saved {
    color: var(--el-color-success);
  }

  &.error {
    color: var(--el-color-danger);
  }
}

.checking-overlay {
  min-height: 200px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.empty-check {
  text-align: center;
  padding: 40px 0;

  p {
    color: #909399;
    margin-bottom: 16px;
  }
}

.check-results {
  max-height: 500px;
  overflow-y: auto;

  .results-summary {
    margin-bottom: 16px;
  }
}

.issue-item {
  border: 1px solid #ebeef5;
  border-radius: 4px;
  padding: 12px;
  margin-bottom: 12px;

  .issue-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;

    .issue-character {
      font-weight: 600;
      font-size: 14px;
    }
  }

  .issue-content {
    font-size: 13px;
    line-height: 1.6;

    .issue-desc {
      margin: 0 0 8px 0;
    }

    .issue-evidence {
      background: #f5f7fa;
      padding: 8px;
      border-radius: 4px;
      font-style: italic;
      color: #606266;
      margin-bottom: 8px;
    }

    .issue-suggestion {
      color: #409eff;
    }
  }
}

.generation-status {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 20px;
  font-size: 13px;
  margin-right: 8px;

  .el-icon {
    font-size: 16px;
  }
}
</style>
