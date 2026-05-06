<template>
  <div class="volume-editor">
    <div class="volume-header">
      <div class="header-left">
        <h3>📑 卷纲编辑</h3>
        <el-select v-if="volumes.length > 0" v-model="currentVolumeId" size="small" class="volume-select">
          <el-option
            v-for="vol in volumes"
            :key="vol.id"
            :label="vol.title"
            :value="vol.id"
          />
        </el-select>
      </div>
      <div class="header-right">
        <el-button size="small" @click="handleAiGenerate">
          <el-icon><MagicStick /></el-icon>
          AI生成本卷
        </el-button>
        <el-button size="small" type="primary" @click="handleSave">
          <el-icon><FolderOpened /></el-icon>
          保存
        </el-button>
      </div>
    </div>

    <div class="volume-content">
      <div v-if="currentVolume" class="volume-form">
        <el-form :model="volumeForm" label-width="100px" size="default">
          <el-form-item label="卷标题">
            <el-input v-model="volumeForm.title" placeholder="第X卷：卷名" />
          </el-form-item>
          <el-form-item label="本卷主题">
            <el-input v-model="volumeForm.theme" placeholder="本卷核心主题" />
          </el-form-item>
          <el-form-item label="章节数">
            <el-input-number v-model="volumeForm.chapterCount" :min="1" :max="100" />
          </el-form-item>
          <el-form-item label="预估字数">
            <el-input-number v-model="volumeForm.targetWordCount" :min="1000" :max="1000000" :step="1000" />
            <span class="word-count-hint">万字</span>
          </el-form-item>
        </el-form>
      </div>

      <div v-if="currentVolume" class="volume-conflict">
        <h4>📝 本卷核心冲突</h4>
        <el-input
          v-model="volumeForm.coreConflict"
          type="textarea"
          :rows="3"
          placeholder="描述本卷的主要矛盾和冲突..."
        />
        <el-button size="small" class="ai-hint-btn" @click="handleAiConflict">
          <el-icon><ChatDotRound /></el-icon>
          让AI帮我想更多冲突点
        </el-button>
      </div>

      <div v-if="currentVolume" class="chapters-preview">
        <div class="chapters-header">
          <h4>📖 章节预览</h4>
          <div class="chapter-actions">
            <el-button size="small" @click="handleReorder">
              <el-icon><Rank /></el-icon>
              调整顺序
            </el-button>
            <el-button size="small" type="primary" @click="handleGenerateAllChapters">
              <el-icon><MagicStick /></el-icon>
              生成本卷所有细纲
            </el-button>
          </div>
        </div>

        <div class="chapter-list">
          <div
            v-for="(chapter, index) in volumeForm.chapters"
            :key="chapter.id || index"
            class="chapter-item"
            :class="{ 'is-active': selectedChapterIndex === index }"
            @click="selectChapter(index)"
          >
            <div class="chapter-info">
              <span class="chapter-number">第{{ chapter.chapterNo }}章</span>
              <span class="chapter-title">{{ chapter.title || '未命名章节' }}</span>
              <el-tag v-if="chapter.status === 0" size="small" type="info">草稿</el-tag>
              <el-tag v-else-if="chapter.status === 1" size="small" type="success">已完成</el-tag>
            </div>
            <div class="chapter-core">
              <strong>核心：</strong>{{ chapter.core || '未设置' }}
            </div>
            <div class="chapter-key">
              <strong>关键：</strong>{{ chapter.keyPoint || '未设置' }}
            </div>
            <div class="chapter-actions">
              <el-button size="small" link @click.stop="handleEditChapter(index)">
                <el-icon><Edit /></el-icon>
              </el-button>
              <el-button size="small" link @click.stop="handleAiChapter(index)">
                <el-icon><ChatDotRound /></el-icon>
              </el-button>
              <el-button size="small" link @click.stop="handleGenerateOutline(index)">
                <el-icon><Document /></el-icon>
              </el-button>
            </div>
          </div>

          <el-button class="add-chapter-btn" size="small" @click="handleAddChapter">
            <el-icon><Plus /></el-icon>
            添加章节
          </el-button>
        </div>
      </div>

      <div v-else class="empty-state">
        <el-empty description="请先选择或创建一个分卷">
          <el-button type="primary" @click="handleCreateVolume">创建分卷</el-button>
        </el-empty>
      </div>
    </div>

    <el-dialog
      v-model="showChapterDialog"
      :title="chapterDialogTitle"
      width="700px"
      destroy-on-close
    >
      <el-form :model="chapterForm" label-width="100px">
        <el-form-item label="章节标题">
          <el-input v-model="chapterForm.title" placeholder="章节标题" />
        </el-form-item>
        <el-form-item label="章节序号">
          <el-input-number v-model="chapterForm.chapterNo" :min="1" />
        </el-form-item>
        <el-form-item label="核心事件">
          <el-input v-model="chapterForm.core" type="textarea" :rows="2" placeholder="本章节的核心事件" />
        </el-form-item>
        <el-form-item label="关键点">
          <el-input v-model="chapterForm.keyPoint" type="textarea" :rows="2" placeholder="本章的关键转折点" />
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
      <template #footer>
        <el-button @click="showChapterDialog = false">取消</el-button>
        <el-button type="primary" @click="handleConfirmChapter">确定</el-button>
      </template>
    </el-dialog>

    <el-dialog
      v-model="showVolumeDialog"
      title="创建分卷"
      width="500px"
      destroy-on-close
    >
      <el-form :model="newVolumeForm" label-width="100px">
        <el-form-item label="卷标题">
          <el-input v-model="newVolumeForm.title" placeholder="第X卷：卷名" />
        </el-form-item>
        <el-form-item label="卷描述">
          <el-input v-model="newVolumeForm.description" type="textarea" :rows="3" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showVolumeDialog = false">取消</el-button>
        <el-button type="primary" @click="handleConfirmCreateVolume">创建</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import { MagicStick, FolderOpened, ChatDotRound, Rank, Edit, Document, Plus } from '@element-plus/icons-vue'
import type { NovelVolume } from '@/types/api'

interface ChapterItem {
  id?: number
  chapterNo: number
  title: string
  core: string
  keyPoint: string
  status: number
  chapterType: string
}

interface VolumeFormData {
  title: string
  theme: string
  chapterCount: number
  targetWordCount: number
  coreConflict: string
  chapters: ChapterItem[]
}

const props = defineProps<{
  volumes: NovelVolume[]
  currentVolumeId?: number
}>()

const emit = defineEmits<{
  'update:currentVolumeId': [value: number]
  save: [volume: NovelVolume, formData: VolumeFormData]
  'ai-generate': []
  'ai-conflict': [currentConflict: string]
  'ai-chapter': [chapterIndex: number]
  'generate-all-outlines': []
  'create-volume': [data: { title: string; description: string }]
}>()

const currentVolumeId = ref<number | undefined>(props.currentVolumeId)

watch(() => props.currentVolumeId, (val) => {
  currentVolumeId.value = val
})

watch(currentVolumeId, (val) => {
  emit('update:currentVolumeId', val!)
})

const currentVolume = computed(() => {
  return props.volumes.find(v => v.id === currentVolumeId.value)
})

const volumeForm = reactive<VolumeFormData>({
  title: '',
  theme: '',
  chapterCount: 20,
  targetWordCount: 60000,
  coreConflict: '',
  chapters: []
})

watch(currentVolume, (vol) => {
  if (vol) {
    volumeForm.title = vol.title
    volumeForm.theme = ''
    volumeForm.chapterCount = vol.chapterCount || 20
    volumeForm.targetWordCount = (vol.wordCount) || 60000
    volumeForm.coreConflict = vol.description || ''
    if (!volumeForm.chapters.length && vol.chapterCount) {
      volumeForm.chapters = Array.from({ length: vol.chapterCount }, (_, i) => ({
        chapterNo: i + 1,
        title: '',
        core: '',
        keyPoint: '',
        status: 0,
        chapterType: 'standard'
      }))
    }
  }
}, { immediate: true })

const selectedChapterIndex = ref(-1)
const showChapterDialog = ref(false)
const chapterDialogTitle = ref('')
const chapterForm = reactive<ChapterItem>({
  chapterNo: 1,
  title: '',
  core: '',
  keyPoint: '',
  status: 0,
  chapterType: 'standard'
})

const showVolumeDialog = ref(false)
const newVolumeForm = reactive({
  title: '',
  description: ''
})

function handleAiGenerate() {
  emit('ai-generate')
}

function handleSave() {
  if (currentVolume.value) {
    emit('save', currentVolume.value, { ...volumeForm })
  }
}

function handleAiConflict() {
  emit('ai-conflict', volumeForm.coreConflict)
}

function handleReorder() {
  console.log('Reorder chapters')
}

function handleGenerateAllChapters() {
  emit('generate-all-outlines')
}

function selectChapter(index: number) {
  selectedChapterIndex.value = index
}

function handleEditChapter(index: number) {
  const chapter = volumeForm.chapters[index]
  Object.assign(chapterForm, chapter)
  chapterDialogTitle.value = `编辑章节 - ${chapter.title || '第' + chapter.chapterNo + '章'}`
  showChapterDialog.value = true
}

function handleAiChapter(index: number) {
  emit('ai-chapter', index)
}

function handleGenerateOutline(index: number) {
  console.log('Generate outline for chapter:', index)
}

function handleAddChapter() {
  const newChapter: ChapterItem = {
    chapterNo: volumeForm.chapters.length + 1,
    title: '',
    core: '',
    keyPoint: '',
    status: 0,
    chapterType: 'standard'
  }
  volumeForm.chapters.push(newChapter)
  volumeForm.chapterCount = volumeForm.chapters.length
}

function handleConfirmChapter() {
  showChapterDialog.value = false
}

function handleCreateVolume() {
  newVolumeForm.title = ''
  newVolumeForm.description = ''
  showVolumeDialog.value = true
}

function handleConfirmCreateVolume() {
  emit('create-volume', { ...newVolumeForm })
  showVolumeDialog.value = false
}
</script>

<style lang="scss" scoped>
.volume-editor {
  height: 100%;
  display: flex;
  flex-direction: column;
  background: var(--el-bg-color-page);
}

.volume-header {
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

    .volume-select {
      width: 200px;
    }
  }

  .header-right {
    display: flex;
    gap: 8px;
  }
}

.volume-content {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
}

.volume-form {
  background: var(--el-bg-color);
  padding: 16px;
  border-radius: 8px;
  margin-bottom: 16px;

  .word-count-hint {
    margin-left: 8px;
    color: var(--el-text-color-secondary);
  }
}

.volume-conflict {
  background: var(--el-bg-color);
  padding: 16px;
  border-radius: 8px;
  margin-bottom: 16px;

  h4 {
    margin: 0 0 12px;
    font-size: 14px;
  }

  .ai-hint-btn {
    margin-top: 8px;
  }
}

.chapters-preview {
  background: var(--el-bg-color);
  border-radius: 8px;
  padding: 16px;

  .chapters-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;

    h4 {
      margin: 0;
      font-size: 14px;
    }

    .chapter-actions {
      display: flex;
      gap: 8px;
    }
  }
}

.chapter-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.chapter-item {
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

  .chapter-info {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;

    .chapter-number {
      font-weight: 600;
      color: var(--el-color-primary);
    }

    .chapter-title {
      flex: 1;
      font-weight: 500;
    }
  }

  .chapter-core,
  .chapter-key {
    font-size: 13px;
    color: var(--el-text-color-secondary);
    margin-bottom: 4px;

    strong {
      color: var(--el-text-color-regular);
    }
  }

  .chapter-actions {
    display: flex;
    gap: 4px;
    margin-top: 8px;
    padding-top: 8px;
    border-top: 1px dashed var(--el-border-color-light);
  }
}

.add-chapter-btn {
  width: 100%;
  border-style: dashed;
}

.empty-state {
  background: var(--el-bg-color);
  padding: 48px;
  border-radius: 8px;
  text-align: center;
}
</style>
