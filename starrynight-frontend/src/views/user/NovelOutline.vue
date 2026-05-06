<template>
  <div class="novel-outline page-container">
    <div class="page-header">
      <div class="header-left">
        <h2>📖 大纲管理</h2>
        <el-tag v-if="outline?.version" type="info">V{{ outline.version }}</el-tag>
      </div>
      <div class="header-actions">
        <el-button @click="showHistory = true">
          <el-icon><Clock /></el-icon>
          版本历史
        </el-button>
        <el-button @click="handleExport">
          <el-icon><Download /></el-icon>
          导出大纲
        </el-button>
        <el-button type="primary" @click="handleSave" :loading="saving">
          <el-icon><FolderOpened /></el-icon>
          保存
        </el-button>
      </div>
    </div>

    <div class="page-content">
      <OutlineEditor
        v-if="outline"
        ref="outlineEditorRef"
        v-model="outlineData"
        @ai-generate="handleAiGenerate"
        @save="handleEditorSave"
      />

      <div v-else class="empty-state">
        <el-empty description="暂无大纲，点击创建或让AI帮你生成">
          <template #image>
            <el-icon class="empty-icon" :size="80"><Document /></el-icon>
          </template>
          <div class="empty-actions">
            <el-button type="primary" @click="showTemplateDialog = true">
              <el-icon><Collection /></el-icon>
              从模板创建
            </el-button>
            <el-button type="success" @click="handleAiGenerate">
              <el-icon><MagicStick /></el-icon>
              AI生成大纲
            </el-button>
          </div>
        </el-empty>
      </div>
    </div>

    <el-dialog
      v-model="showGenerateDialog"
      title="AI生成大纲"
      width="700px"
      destroy-on-close
    >
      <el-form :model="generateForm" label-width="100px">
        <el-form-item label="核心创意">
          <el-input
            v-model="generateForm.coreIdea"
            type="textarea"
            :rows="4"
            placeholder="描述作品的核心创意、主线或主题"
          />
        </el-form-item>
        <el-form-item label="题材">
          <el-select v-model="generateForm.genre" placeholder="选择题材" style="width: 100%">
            <el-option label="都市" value="urban" />
            <el-option label="玄幻" value="fantasy" />
            <el-option label="仙侠" value="xianxia" />
            <el-option label="穿越" value="transmigration" />
            <el-option label="科幻" value="scifi" />
            <el-option label="悬疑" value="mystery" />
          </el-select>
        </el-form-item>
        <el-form-item label="风格">
          <el-select v-model="generateForm.style" placeholder="选择风格" style="width: 100%">
            <el-option label="热血爽文" value="passionate" />
            <el-option label="治愈系" value="healing" />
            <el-option label="搞笑" value="comedy" />
            <el-option label="虐心" value="heart-wrenching" />
            <el-option label="悬疑" value="suspensive" />
          </el-select>
        </el-form-item>
        <el-form-item label="目标字数">
          <el-input-number v-model="generateForm.targetWordCount" :min="10000" :max="10000000" :step="10000" />
          <span class="word-count-hint">字</span>
        </el-form-item>
        <el-form-item label="章节数量">
          <el-input-number v-model="generateForm.chapterCount" :min="10" :max="500" :step="10" />
          <span class="word-count-hint">章</span>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showGenerateDialog = false">取消</el-button>
        <el-button type="primary" :loading="generating" @click="confirmGenerate">
          开始生成
        </el-button>
      </template>
    </el-dialog>

    <el-dialog
      v-model="showTemplateDialog"
      title="选择大纲模板"
      width="800px"
      destroy-on-close
    >
      <TemplateSelector
        v-if="showTemplateDialog"
        @confirm="handleTemplateSelect"
        @cancel="showTemplateDialog = false"
      />
    </el-dialog>

    <el-dialog
      v-model="showHistory"
      title="版本历史"
      width="700px"
      destroy-on-close
    >
      <el-timeline>
        <el-timeline-item
          v-for="version in versionHistory"
          :key="version.id"
          :timestamp="version.createTime"
          :type="version.isCurrent ? 'primary' : 'info'"
          placement="top"
        >
          <el-card shadow="hover">
            <div class="version-item">
              <div class="version-info">
                <span class="version-title">V{{ version.version }}</span>
                <el-tag v-if="version.isCurrent" size="small" type="success">当前版本</el-tag>
              </div>
              <p class="version-desc">{{ version.description }}</p>
              <div class="version-actions">
                <el-button size="small" link @click="handleViewVersion(version)">查看</el-button>
                <el-button v-if="!version.isCurrent" size="small" link type="primary" @click="handleRestoreVersion(version)">
                  恢复此版本
                </el-button>
              </div>
            </div>
          </el-card>
        </el-timeline-item>
      </el-timeline>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import { MagicStick, FolderOpened, Clock, Download, Document, Collection } from '@element-plus/icons-vue'
import { listNovelOutlines, upsertNovelOutline } from '@/api/novelOutline'
import type { NovelOutline } from '@/types/api'
import OutlineEditor from './components/OutlineEditor.vue'
import TemplateSelector from './components/TemplateSelector.vue'

interface OutlineFormData {
  title: string
  genre: string
  style: string
  targetWordCount: number
  acts: Array<{
    title: string
    chapterRange: string
    coreConflict: string
    plotPoints: Array<{
      title: string
      description: string
      keyEvents: string[]
    }>
    expanded: boolean
  }>
  characters: Array<{
    id: string
    name: string
    role: string
    avatar?: string
  }>
}

interface VersionHistory {
  id: number
  version: number
  description: string
  createTime: string
  isCurrent: boolean
}

const route = useRoute()
const novelId = computed(() => route.params.id as string)

const loading = ref(false)
const saving = ref(false)
const generating = ref(false)
const outline = ref<NovelOutline | null>(null)
const outlineData = reactive<OutlineFormData>({
  title: '',
  genre: '',
  style: '',
  targetWordCount: 500000,
  acts: [],
  characters: []
})

const showGenerateDialog = ref(false)
const showTemplateDialog = ref(false)
const showHistory = ref(false)

const generateForm = reactive({
  coreIdea: '',
  genre: '',
  style: '',
  targetWordCount: 500000,
  chapterCount: 100
})

const versionHistory = ref<VersionHistory[]>([
  { id: 1, version: 2, description: '优化了第二幕的核心冲突', createTime: '2026-05-01 15:30:00', isCurrent: true },
  { id: 2, version: 1, description: '初始大纲版本', createTime: '2026-04-28 10:00:00', isCurrent: false }
])

const outlineEditorRef = ref<InstanceType<typeof OutlineEditor> | null>(null)

async function loadOutlines() {
  if (!novelId.value) return

  loading.value = true
  try {
    const list = await listNovelOutlines({
      novelId: Number(novelId.value),
      type: 'outline'
    })
    if (list && list.length > 0) {
      outline.value = list[0]
      parseOutlineToForm(outline.value)
    }
  } catch (error) {
    console.error('Load outlines failed:', error)
  } finally {
    loading.value = false
  }
}

function parseOutlineToForm(data: NovelOutline) {
  try {
    if (data.content) {
      const parsed = JSON.parse(data.content)
      Object.assign(outlineData, parsed)
    }
  } catch {
    outlineData.title = data.title || ''
  }
}

function handleAiGenerate() {
  generateForm.coreIdea = ''
  generateForm.genre = outlineData.genre
  generateForm.style = outlineData.style
  showGenerateDialog.value = true
}

async function confirmGenerate() {
  generating.value = true
  try {
    ElMessage.success('大纲生成中，请稍候...')
    showGenerateDialog.value = false

    outlineData.genre = generateForm.genre
    outlineData.style = generateForm.style
    outlineData.targetWordCount = generateForm.targetWordCount

    outlineData.acts = [
      {
        title: '第一幕：建置',
        chapterRange: `1-${Math.floor(generateForm.chapterCount * 0.1)}章`,
        coreConflict: '主角发现自己的特殊天赋，开启人生新篇章',
        plotPoints: [],
        expanded: true
      },
      {
        title: '第二幕：对抗',
        chapterRange: `${Math.floor(generateForm.chapterCount * 0.1 + 1)}-${Math.floor(generateForm.chapterCount * 0.8)}章`,
        coreConflict: '主角面临重重困难，必须证明自己',
        plotPoints: [],
        expanded: false
      },
      {
        title: '第三幕：解决',
        chapterRange: `${Math.floor(generateForm.chapterCount * 0.8 + 1)}-${generateForm.chapterCount}章`,
        coreConflict: '主角克服最终挑战，达到人生巅峰',
        plotPoints: [],
        expanded: false
      }
    ]

    ElMessage.success('AI大纲生成成功')
  } catch (error) {
    console.error('Generate failed:', error)
    ElMessage.error('生成失败')
  } finally {
    generating.value = false
  }
}

function handleTemplateSelect(template: any) {
  console.log('Selected template:', template)
  showTemplateDialog.value = false
  ElMessage.success('模板应用成功')
}

async function handleSave() {
  saving.value = true
  try {
    const content = JSON.stringify(outlineData)
    await upsertNovelOutline({
      id: outline.value?.id,
      novelId: Number(novelId.value),
      title: outlineData.title,
      type: 'outline',
      content
    })
    ElMessage.success('保存成功')
  } catch (error) {
    ElMessage.error('保存失败')
  } finally {
    saving.value = false
  }
}

function handleEditorSave(data: OutlineFormData) {
  Object.assign(outlineData, data)
  handleSave()
}

function handleExport() {
  ElMessage.info('导出功能开发中')
}

function handleViewVersion(version: VersionHistory) {
  console.log('View version:', version)
}

function handleRestoreVersion(version: VersionHistory) {
  ElMessage.success(`已恢复到V${version.version}`)
  showHistory.value = false
}

onMounted(() => {
  loadOutlines()
})
</script>

<style lang="scss" scoped>
.novel-outline {
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

  .empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 80px 20px;
    background: var(--el-bg-color);
    border-radius: 12px;

    .empty-icon {
      color: var(--el-text-color-placeholder);
    }

    .empty-actions {
      display: flex;
      gap: 16px;
      margin-top: 24px;
    }
  }

  .word-count-hint {
    margin-left: 8px;
    color: var(--el-text-color-secondary);
  }

  .version-item {
    .version-info {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 8px;

      .version-title {
        font-weight: 600;
        font-size: 16px;
      }
    }

    .version-desc {
      margin: 0 0 12px;
      font-size: 14px;
      color: var(--el-text-color-secondary);
    }

    .version-actions {
      display: flex;
      gap: 8px;
    }
  }
}
</style>
