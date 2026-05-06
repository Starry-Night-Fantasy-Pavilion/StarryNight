<template>
  <div class="content-editor" :class="{ 'is-preview': previewMode }">
    <div class="editor-header" v-if="!previewMode">
      <div class="header-left">
        <span class="chapter-title">{{ chapterTitle }}</span>
        <el-tag :type="saveStatusType" size="small">{{ saveStatusText }}</el-tag>
      </div>
      <div class="header-right">
        <span class="word-count">
          字数: {{ wordCount }} / {{ targetWordCount }}
        </span>
        <el-progress
          :percentage="wordCountPercentage"
          :status="wordCountStatus"
          :show-text="false"
          :stroke-width="6"
          class="word-progress"
        />
        <el-button size="small" @click="previewMode = true">
          <el-icon><View /></el-icon>
          预览
        </el-button>
        <el-button size="small" type="primary" :loading="saving" @click="handleSave">
          <el-icon><FolderOpened /></el-icon>
          保存
        </el-button>
      </div>
    </div>

    <div class="editor-toolbar" v-if="!previewMode">
      <div class="toolbar-group">
        <el-button-group>
          <el-button size="small" @click="execCommand('bold')" :class="{ active: formats.bold }">
            <el-icon><Bolder /></el-icon>
          </el-button>
          <el-button size="small" @click="execCommand('italic')" :class="{ active: formats.italic }">
            <el-icon><ElIconItalic /></el-icon>
          </el-button>
          <el-button size="small" @click="execCommand('underline')" :class="{ active: formats.underline }">
            <el-icon><ElIconUnderline /></el-icon>
          </el-button>
        </el-button-group>
      </div>
      <el-divider direction="vertical" />
      <div class="toolbar-group">
        <el-button-group>
          <el-button size="small" @click="execCommand('formatBlock', 'h1')">H1</el-button>
          <el-button size="small" @click="execCommand('formatBlock', 'h2')">H2</el-button>
          <el-button size="small" @click="execCommand('formatBlock', 'h3')">H3</el-button>
        </el-button-group>
      </div>
      <el-divider direction="vertical" />
      <div class="toolbar-group">
        <el-button size="small" @click="insertUnorderedList">
          <el-icon><ElIconTickets /></el-icon>
        </el-button>
        <el-button size="small" @click="insertOrderedList">
          <el-icon><ElIconList /></el-icon>
        </el-button>
      </div>
      <el-divider direction="vertical" />
      <div class="toolbar-group">
        <el-button size="small" @click="showLinkDialog = true">
          <el-icon><Link /></el-icon>
        </el-button>
        <el-button size="small" @click="showImageDialog = true">
          <el-icon><Picture /></el-icon>
        </el-button>
      </div>
      <el-divider direction="vertical" />
      <div class="toolbar-group">
        <el-button size="small" @click="showKnowledgeDialog = true">
          <el-icon><Collection /></el-icon>
          @引用
        </el-button>
        <el-button size="small" @click="showCharacterDialog = true">
          <el-icon><ChatLineSquare /></el-icon>
          对话生成
        </el-button>
        <el-button size="small" type="primary" :loading="aiLoading" @click="handleAiExpand">
          <el-icon><MagicStick /></el-icon>
          AI扩写
        </el-button>
        <el-button size="small" type="success" :loading="aiLoading" @click="handleAiContinue">
          <el-icon><Promotion /></el-icon>
          AI续写
        </el-button>
        <el-button size="small" type="warning" :loading="aiLoading" @click="handleEnhanceDescription">
          <el-icon><Brush /></el-icon>
          描写增强
        </el-button>
      </div>
    </div>

    <div class="preview-header" v-if="previewMode">
      <el-button size="small" @click="previewMode = false">
        <el-icon><Edit /></el-icon>
        返回编辑
      </el-button>
      <span class="preview-title">{{ chapterTitle }}</span>
    </div>

    <div
      ref="editorRef"
      class="editor-content"
      :contenteditable="!previewMode"
      @input="handleInput"
      @keyup="handleKeyUp"
      @mouseup="updateFormats"
      @touchend="updateFormats"
      v-html="content"
    ></div>

    <div class="editor-footer" v-if="!previewMode">
      <span class="footer-info">
        <el-icon><Clock /></el-icon>
        {{ lastSavedText }}
      </span>
      <span class="footer-shortcuts">
        <kbd>Ctrl</kbd>+<kbd>S</kbd> 保存
        <kbd>Ctrl</kbd>+<kbd>Z</kbd> 撤销
      </span>
    </div>

    <el-dialog v-model="showLinkDialog" title="插入链接" width="400px">
      <el-form :model="linkForm" label-width="80px">
        <el-form-item label="链接文字">
          <el-input v-model="linkForm.text" placeholder="链接显示文字" />
        </el-form-item>
        <el-form-item label="URL地址">
          <el-input v-model="linkForm.url" placeholder="https://..." />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showLinkDialog = false">取消</el-button>
        <el-button type="primary" @click="insertLink">确定</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="showImageDialog" title="插入图片" width="400px">
      <el-form :model="imageForm" label-width="80px">
        <el-form-item label="图片URL">
          <el-input v-model="imageForm.url" placeholder="https://..." />
        </el-form-item>
        <el-form-item label="替换文字">
          <el-input v-model="imageForm.alt" placeholder="图片描述" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showImageDialog = false">取消</el-button>
        <el-button type="primary" @click="insertImage">确定</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="showKnowledgeDialog" title="知识库引用" width="600px">
      <div class="knowledge-search-panel">
        <el-input v-model="knowledgeSearchQuery" placeholder="搜索知识库..." @input="searchKnowledge">
          <template #prefix>
            <el-icon><Search /></el-icon>
          </template>
        </el-input>
        <div class="knowledge-results">
          <div
            v-for="item in knowledgeResults"
            :key="item.id"
            class="knowledge-item"
            @click="insertKnowledgeRef(item)"
          >
            <el-tag size="small" type="primary">{{ item.type }}</el-tag>
            <span class="item-title">{{ item.title }}</span>
          </div>
        </div>
      </div>
    </el-dialog>

    <el-dialog v-model="showCharacterDialog" title="AI对话生成" width="600px" destroy-on-close>
      <el-form :model="dialogueForm" label-width="100px">
        <el-form-item label="角色名称" required>
          <el-input v-model="dialogueForm.characterName" placeholder="输入角色名称" />
        </el-form-item>
        <el-form-item label="角色设定">
          <el-input v-model="dialogueForm.characterProfile" type="textarea" :rows="2" placeholder="角色性格、背景等设定" />
        </el-form-item>
        <el-form-item label="当前情境">
          <el-input v-model="dialogueForm.situation" type="textarea" :rows="2" placeholder="当前场景、发生的事情" />
        </el-form-item>
        <el-form-item label="情绪氛围">
          <el-select v-model="dialogueForm.emotion" placeholder="选择情绪" style="width: 100%">
            <el-option label="紧张" value="tense" />
            <el-option label="欢快" value="happy" />
            <el-option label="悲伤" value="sad" />
            <el-option label="愤怒" value="angry" />
            <el-option label="温馨" value="warm" />
            <el-option label="悬疑" value="mysterious" />
          </el-select>
        </el-form-item>
        <el-form-item label="生成数量">
          <el-input-number v-model="dialogueForm.count" :min="1" :max="10" />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" :loading="aiLoading" @click="handleGenerateDialogue">生成对话</el-button>
        </el-form-item>
      </el-form>
      <div v-if="characterDialogueResult" class="dialogue-result">
        <el-divider content-position="left">生成结果</el-divider>
        <div class="result-content">
          <pre>{{ characterDialogueResult }}</pre>
        </div>
        <div class="result-actions">
          <el-button type="primary" @click="insertDialogue">插入到正文</el-button>
        </div>
      </div>
    </el-dialog>

    <el-dialog v-model="showEnhanceDialog" title="描写增强" width="500px" destroy-on-close>
      <el-form label-width="100px">
        <el-form-item label="增强类型">
          <el-select v-model="descriptionEnhanceType" style="width: 100%">
            <el-option value="appearance" label="外貌描写" />
            <el-option value="environment" label="环境描写" />
            <el-option value="action" label="动作描写" />
            <el-option value="psychology" label="心理描写" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" :loading="aiLoading" @click="handleEnhanceDescription">开始增强</el-button>
        </el-form-item>
      </el-form>
      <div v-if="enhanceResult" class="enhance-result">
        <el-divider content-position="left">增强结果</el-divider>
        <div class="result-content">
          <pre>{{ enhanceResult }}</pre>
        </div>
        <div class="result-actions">
          <el-button type="primary" @click="insertEnhancedText">插入到正文</el-button>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { ElMessage } from 'element-plus'
import { View, FolderOpened, Clock, Link, Picture, Collection, MagicStick, Promotion, Search, Edit, ElIconTickets, ElIconList, ChatLineSquare, Brush } from '@element-plus/icons-vue'
import type { KnowledgeItem } from '@/api/knowledge'
import {
  expandContent,
  continueWriting,
  enhanceDescription,
  generateDialogues,
  type DescriptionEnhanceRequest
} from '@/api/aiTools'

interface Props {
  modelValue?: string
  chapterTitle?: string
  targetWordCount?: number
  autoSave?: boolean
  autoSaveInterval?: number
  novelId?: number
  chapterId?: number
  characters?: Array<{ id: number; name: string; personality?: string }>
}

interface KnowledgeResult {
  id: number
  type: string
  title: string
  content: string
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: '',
  chapterTitle: '未命名章节',
  targetWordCount: 3000,
  autoSave: true,
  autoSaveInterval: 30000
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
  save: [content: string]
  'ai-expand': [content: string]
  'ai-continue': [content: string]
}>()

const editorRef = ref<HTMLElement | null>(null)
const content = ref(props.modelValue)
const previewMode = ref(false)
const saving = ref(false)
const lastSaved = ref<Date | null>(null)
const isDirty = ref(false)

const formats = reactive({
  bold: false,
  italic: false,
  underline: false
})

const showLinkDialog = ref(false)
const linkForm = reactive({
  text: '',
  url: ''
})

const showImageDialog = ref(false)
const imageForm = reactive({
  url: '',
  alt: ''
})

const showKnowledgeDialog = ref(false)
const knowledgeSearchQuery = ref('')
const knowledgeResults = ref<KnowledgeResult[]>([])

const showCharacterDialog = ref(false)
const showEnhanceDialog = ref(false)
const aiLoading = ref(false)
const selectedText = ref('')
const characterDialogueResult = ref('')
const descriptionEnhanceType = ref<DescriptionEnhanceRequest['type']>('description')
const enhanceResult = ref('')

const dialogueForm = reactive({
  characterName: '',
  characterProfile: '',
  situation: '',
  emotion: '',
  count: 3
})

let autoSaveTimer: number | null = null

const wordCount = computed(() => {
  const text = content.value.replace(/<[^>]+>/g, '').replace(/\s+/g, ' ').trim()
  return text.length
})

const wordCountPercentage = computed(() => {
  return Math.min(100, (wordCount.value / props.targetWordCount) * 100)
})

const wordCountStatus = computed(() => {
  if (wordCountPercentage.value >= 100) return 'success'
  if (wordCountPercentage.value >= 80) return 'warning'
  return undefined
})

const saveStatusType = computed(() => {
  if (saving.value) return 'info'
  if (isDirty.value) return 'warning'
  return 'success'
})

const saveStatusText = computed(() => {
  if (saving.value) return '保存中...'
  if (isDirty.value) return '待保存'
  return '已保存'
})

const lastSavedText = computed(() => {
  if (!lastSaved.value) return '尚未保存'
  const now = new Date()
  const diff = now.getTime() - lastSaved.value.getTime()
  if (diff < 60000) return '刚刚保存'
  if (diff < 3600000) return `${Math.floor(diff / 60000)}分钟前保存`
  return `${lastSaved.value.toLocaleTimeString()} 保存`
})

watch(() => props.modelValue, (val) => {
  if (val !== content.value) {
    content.value = val
    isDirty.value = false
  }
})

watch(content, (val) => {
  emit('update:modelValue', val)
  isDirty.value = true
})

function handleInput() {
  if (editorRef.value) {
    content.value = editorRef.value.innerHTML
  }
}

function handleKeyUp() {
  updateFormats()
}

function updateFormats() {
  formats.bold = document.queryCommandState('bold')
  formats.italic = document.queryCommandState('italic')
  formats.underline = document.queryCommandState('underline')
}

function execCommand(command: string, value?: string) {
  document.execCommand(command, false, value)
  editorRef.value?.focus()
  updateFormats()
}

function insertUnorderedList() {
  document.execCommand('insertUnorderedList', false)
  editorRef.value?.focus()
}

function insertOrderedList() {
  document.execCommand('insertOrderedList', false)
  editorRef.value?.focus()
}

function insertLink() {
  if (linkForm.url) {
    const url = linkForm.url.startsWith('http') ? linkForm.url : `https://${linkForm.url}`
    document.execCommand('insertHTML', false, `<a href="${url}" target="_blank">${linkForm.text || linkForm.url}</a>`)
  }
  showLinkDialog.value = false
  linkForm.text = ''
  linkForm.url = ''
}

function insertImage() {
  if (imageForm.url) {
    document.execCommand('insertImage', false, imageForm.url)
  }
  showImageDialog.value = false
  imageForm.url = ''
  imageForm.alt = ''
}

async function searchKnowledge() {
  await new Promise(resolve => setTimeout(resolve, 300))
  knowledgeResults.value = [
    { id: 1, type: '知识', title: '修仙境界设定', content: '炼气、筑基、金丹、元婴、化神...' },
    { id: 2, type: '角色', title: '林天', content: '主角，天生灵体...' },
    { id: 3, type: '素材', title: '宗门场景描写', content: '云雾缭绕，灵气充沛...' }
  ]
}

function insertKnowledgeRef(item: KnowledgeResult) {
  document.execCommand('insertHTML', false, `<span class="knowledge-ref" data-id="${item.id}" contentedable="false">[${item.title}]</span>`)
  showKnowledgeDialog.value = false
  knowledgeSearchQuery.value = ''
}

async function handleAiExpand() {
  if (!content.value) {
    ElMessage.warning('请先输入内容')
    return
  }
  aiLoading.value = true
  try {
    const res = await expandContent(content.value, props.targetWordCount)
    if (res.data?.data) {
      content.value = res.data.data.expanded
      ElMessage.success('扩写成功')
    }
  } catch (e) {
    ElMessage.error('扩写失败')
  } finally {
    aiLoading.value = false
  }
}

async function handleAiContinue() {
  if (!content.value) {
    ElMessage.warning('请先输入内容')
    return
  }
  aiLoading.value = true
  try {
    const res = await continueWriting(content.value, 1.5)
    if (res.data?.data) {
      content.value += res.data.data.content
      ElMessage.success('续写成功')
    }
  } catch (e) {
    ElMessage.error('续写失败')
  } finally {
    aiLoading.value = false
  }
}

async function handleEnhanceDescription() {
  if (!selectedText.value && !content.value) {
    ElMessage.warning('请先选中要增强的文本或输入内容')
    return
  }
  const textToEnhance = selectedText.value || content.value.substring(0, 500)
  aiLoading.value = true
  try {
    const res = await enhanceDescription({
      content: textToEnhance,
      type: descriptionEnhanceType.value,
      intensity: 'vivid'
    })
    if (res.data?.data) {
      enhanceResult.value = res.data.data.enhanced
      showEnhanceDialog.value = true
      ElMessage.success('增强成功')
    }
  } catch (e) {
    ElMessage.error('增强失败')
  } finally {
    aiLoading.value = false
  }
}

async function handleGenerateDialogue() {
  if (!dialogueForm.characterName) {
    ElMessage.warning('请输入角色名称')
    return
  }
  aiLoading.value = true
  try {
    const res = await generateDialogues({
      characterName: dialogueForm.characterName,
      characterProfile: dialogueForm.characterProfile,
      situation: dialogueForm.situation,
      emotion: dialogueForm.emotion,
      count: dialogueForm.count
    })
    if (res.data?.data) {
      characterDialogueResult.value = res.data.data.map(d => d.content).join('\n\n')
      ElMessage.success('对话生成成功')
    }
  } catch (e) {
    ElMessage.error('生成失败')
  } finally {
    aiLoading.value = false
  }
}

function insertEnhancedText() {
  if (enhanceResult.value) {
    document.execCommand('insertText', false, enhanceResult.value)
    enhanceResult.value = ''
  }
}

function insertDialogue() {
  if (characterDialogueResult.value) {
    document.execCommand('insertText', false, characterDialogueResult.value)
    characterDialogueResult.value = ''
    showCharacterDialog.value = false
  }
}

async function handleSave() {
  saving.value = true
  try {
    await new Promise(resolve => setTimeout(resolve, 500))
    emit('save', content.value)
    lastSaved.value = new Date()
    isDirty.value = false
  } finally {
    saving.value = false
  }
}

function startAutoSave() {
  if (props.autoSave && autoSaveTimer === null) {
    autoSaveTimer = window.setInterval(() => {
      if (isDirty.value) {
        handleSave()
      }
    }, props.autoSaveInterval)
  }
}

function stopAutoSave() {
  if (autoSaveTimer !== null) {
    clearInterval(autoSaveTimer)
    autoSaveTimer = null
  }
}

function handleKeyDown(e: KeyboardEvent) {
  if (e.ctrlKey && e.key === 's') {
    e.preventDefault()
    handleSave()
  }
}

onMounted(() => {
  startAutoSave()
  document.addEventListener('keydown', handleKeyDown)
  nextTick(() => {
    if (editorRef.value) {
      editorRef.value.innerHTML = content.value
    }
  })
})

onUnmounted(() => {
  stopAutoSave()
  document.removeEventListener('keydown', handleKeyDown)
})
</script>

<style lang="scss" scoped>
.content-editor {
  display: flex;
  flex-direction: column;
  height: 100%;
  background: var(--el-bg-color);
  border-radius: 8px;
  overflow: hidden;

  &.is-preview {
    .editor-content {
      padding: 32px 64px;
      font-size: 16px;
      line-height: 2;
      background: white;
    }
  }
}

.editor-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: var(--el-fill-color-light);
  border-bottom: 1px solid var(--el-border-color-light);

  .header-left {
    display: flex;
    align-items: center;
    gap: 12px;

    .chapter-title {
      font-weight: 600;
      font-size: 15px;
    }
  }

  .header-right {
    display: flex;
    align-items: center;
    gap: 16px;

    .word-count {
      font-size: 13px;
      color: var(--el-text-color-secondary);
    }

    .word-progress {
      width: 100px;
    }
  }
}

.editor-toolbar {
  display: flex;
  align-items: center;
  padding: 8px 16px;
  background: var(--el-fill-color-light);
  border-bottom: 1px solid var(--el-border-color-light);
  flex-wrap: wrap;
  gap: 8px;

  .toolbar-group {
    display: flex;
    gap: 4px;

    .active {
      background: var(--el-color-primary-light-9);
      color: var(--el-color-primary);
    }
  }
}

.preview-header {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 12px 16px;
  background: var(--el-fill-color-light);
  border-bottom: 1px solid var(--el-border-color-light);

  .preview-title {
    font-weight: 600;
    font-size: 18px;
  }
}

.editor-content {
  flex: 1;
  padding: 24px 32px;
  overflow-y: auto;
  outline: none;
  font-size: 15px;
  line-height: 1.8;
  color: var(--el-text-color-regular);

  &:empty::before {
    content: '开始输入正文内容...';
    color: var(--el-text-color-placeholder);
    font-style: italic;
  }

  :deep(h1) {
    font-size: 24px;
    font-weight: 700;
    margin: 24px 0 16px;
    color: var(--el-text-color-primary);
  }

  :deep(h2) {
    font-size: 20px;
    font-weight: 600;
    margin: 20px 0 12px;
    color: var(--el-text-color-primary);
  }

  :deep(h3) {
    font-size: 17px;
    font-weight: 600;
    margin: 16px 0 10px;
  }

  :deep(p) {
    margin: 12px 0;
  }

  :deep(a) {
    color: var(--el-color-primary);
    text-decoration: underline;
  }

  :deep(img) {
    max-width: 100%;
    border-radius: 4px;
    margin: 12px 0;
  }

  :deep(.knowledge-ref) {
    display: inline-block;
    padding: 2px 8px;
    background: var(--el-color-primary-light-9);
    color: var(--el-color-primary);
    border-radius: 4px;
    font-size: 13px;
    cursor: pointer;
  }

  :deep(ul),
  :deep(ol) {
    margin: 12px 0;
    padding-left: 24px;
  }

  :deep(li) {
    margin: 6px 0;
  }
}

.editor-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 16px;
  background: var(--el-fill-color-light);
  border-top: 1px solid var(--el-border-color-light);
  font-size: 12px;
  color: var(--el-text-color-secondary);

  .footer-shortcuts {
    display: flex;
    align-items: center;
    gap: 4px;

    kbd {
      display: inline-block;
      padding: 2px 6px;
      background: var(--el-fill-color);
      border: 1px solid var(--el-border-color);
      border-radius: 3px;
      font-size: 11px;
      font-family: inherit;
    }
  }
}

.knowledge-search-panel {
  .knowledge-results {
    margin-top: 16px;
    max-height: 300px;
    overflow-y: auto;
  }

  .knowledge-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border: 1px solid var(--el-border-color-light);
    border-radius: 8px;
    margin-bottom: 8px;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
      border-color: var(--el-color-primary);
      background: var(--el-color-primary-light-9);
    }

    .item-title {
      font-size: 14px;
    }
  }
}

.dialogue-result,
.enhance-result {
  margin-top: 16px;

  .result-content {
    background: var(--el-fill-color-light);
    border-radius: 8px;
    padding: 16px;
    max-height: 300px;
    overflow-y: auto;

    pre {
      margin: 0;
      white-space: pre-wrap;
      font-family: inherit;
      font-size: 14px;
      line-height: 1.8;
    }
  }

  .result-actions {
    margin-top: 12px;
    text-align: right;
  }
}
</style>
