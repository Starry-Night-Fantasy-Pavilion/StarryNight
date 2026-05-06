<template>
  <div class="toolbox-page">
    <div class="page-header">
      <h1>小工具箱</h1>
      <p class="desc">辅助创作的各类小工具，一键生成创意素材</p>
    </div>

    <div class="page-content">
      <div class="tool-grid">
        <!-- 金手指生成器 -->
        <el-card class="tool-card" shadow="hover">
          <template #header>
            <div class="tool-header">
              <span class="tool-icon">⚡</span>
              <span>金手指生成器</span>
            </div>
          </template>
          <p class="tool-desc">根据题材和核心创意，生成独特的金手指设定</p>
          <el-form :model="goldenFingerForm" label-position="top" size="small">
            <el-form-item label="题材类型">
              <el-select v-model="goldenFingerForm.genre" placeholder="选择题材" style="width: 100%">
                <el-option label="玄幻" value="玄幻" />
                <el-option label="仙侠" value="仙侠" />
                <el-option label="都市" value="都市" />
                <el-option label="科幻" value="科幻" />
                <el-option label="历史" value="历史" />
                <el-option label="游戏" value="游戏" />
              </el-select>
            </el-form-item>
            <el-form-item label="核心创意">
              <el-input v-model="goldenFingerForm.coreIdea" type="textarea" :rows="2" placeholder="一句话描述故事核心..." />
            </el-form-item>
            <el-form-item>
              <el-button type="primary" :loading="gfLoading" @click="generateGoldenFinger">
                生成金手挀
              </el-button>
            </el-form-item>
          </el-form>
          <el-collapse-transition>
            <div v-if="goldenFingerResult" class="result-box">
              <el-divider />
              <h4 class="result-title">生成结果</h4>
              <div class="result-content" v-html="renderMarkdown(goldenFingerResult)" />
              <div class="result-actions">
                <el-button text type="primary" size="small" @click="copyText(goldenFingerResult)">复制</el-button>
                <el-button text type="success" size="small" @click="saveToMaterial('金手指', goldenFingerResult, 'golden_finger')">保存到素材库</el-button>
              </div>
            </div>
          </el-collapse-transition>
        </el-card>

        <!-- 书名生成器 -->
        <el-card class="tool-card" shadow="hover">
          <template #header>
            <div class="tool-header">
              <span class="tool-icon">📖</span>
              <span>书名生成器</span>
            </div>
          </template>
          <p class="tool-desc">根据题材和关键词，生成吸引人的书名</p>
          <el-form :model="bookNameForm" label-position="top" size="small">
            <el-form-item label="题材类型">
              <el-select v-model="bookNameForm.genre" placeholder="选择题材" style="width: 100%">
                <el-option label="玄幻" value="玄幻" />
                <el-option label="仙侠" value="仙侠" />
                <el-option label="都市" value="都市" />
                <el-option label="科幻" value="科幻" />
                <el-option label="历史" value="历史" />
                <el-option label="游戏" value="游戏" />
              </el-select>
            </el-form-item>
            <el-form-item label="关键词">
              <el-input v-model="bookNameForm.keywords" placeholder="输入关键词，用逗号分隔" />
            </el-form-item>
            <el-form-item label="风格偏好">
              <el-select v-model="bookNameForm.style" placeholder="选择风格" style="width: 100%" clearable>
                <el-option label="热血" value="热血" />
                <el-option label="轻松" value="轻松" />
                <el-option label="黑暗" value="黑暗" />
                <el-option label="搞笑" value="搞笑" />
                <el-option label="文艺" value="文艺" />
              </el-select>
            </el-form-item>
            <el-form-item>
              <el-button type="primary" :loading="bnLoading" @click="generateBookNames">
                生成书名
              </el-button>
            </el-form-item>
          </el-form>
          <el-collapse-transition>
            <div v-if="bookNameResult.length > 0" class="result-box">
              <el-divider />
              <h4 class="result-title">推荐书名</h4>
              <ul class="name-list">
                <li v-for="(name, idx) in bookNameResult" :key="idx" class="name-item">
                  <div class="name-content">
                    <span class="name-text">{{ name }}</span>
                    <div class="name-actions">
                      <el-button text type="primary" size="small" @click="copyText(name)">复制</el-button>
                    </div>
                  </div>
                </li>
              </ul>
            </div>
          </el-collapse-transition>
        </el-card>

        <!-- 简介生成器 -->
        <el-card class="tool-card" shadow="hover">
          <template #header>
            <div class="tool-header">
              <span class="tool-icon">📝</span>
              <span>简介生成器</span>
            </div>
          </template>
          <p class="tool-desc">根据书名和核心设定，生成吸引人的作品简介</p>
          <el-form :model="synopsisForm" label-position="top" size="small">
            <el-form-item label="书名">
              <el-input v-model="synopsisForm.bookName" placeholder="输入书名" />
            </el-form-item>
            <el-form-item label="核心设定">
              <el-input v-model="synopsisForm.setting" type="textarea" :rows="2" placeholder="描述世界观、主角设定等..." />
            </el-form-item>
            <el-form-item label="风格">
              <el-select v-model="synopsisForm.style" placeholder="选择简介风格" style="width: 100%">
                <el-option label="悬念型（吸引好奇）" value="suspense" />
                <el-option label="情感型（打动人心）" value="emotional" />
                <el-option label="卖点型（突出爽点）" value="selling_point" />
                <el-option label="神秘型（制造神秘感）" value="mystery" />
                <el-option label="史诗型（大格局）" value="epic" />
              </el-select>
            </el-form-item>
            <el-form-item label="生成数量">
              <el-input-number v-model="synopsisForm.count" :min="1" :max="3" />
            </el-form-item>
            <el-form-item>
              <el-button type="primary" :loading="synLoading" @click="generateSynopsisHandler">
                生成简介
              </el-button>
            </el-form-item>
          </el-form>
          <el-collapse-transition>
            <div v-if="synopsisResult.length > 0" class="result-box">
              <el-divider />
              <h4 class="result-title">生成简介</h4>
              <div v-for="(syn, idx) in synopsisResult" :key="idx" class="synopsis-item">
                <div class="synopsis-index">版本 {{ idx + 1 }}</div>
                <div class="result-content">{{ syn.content }}</div>
                <div class="result-actions">
                  <el-button text type="primary" size="small" class="copy-btn" @click="copyText(syn.content)">复制</el-button>
                  <el-button text type="success" size="small" @click="saveToMaterial('简介', syn.content, 'custom')">保存</el-button>
                </div>
              </div>
            </div>
          </el-collapse-transition>
        </el-card>

        <!-- 世界观生成器 -->
        <el-card class="tool-card" shadow="hover">
          <template #header>
            <div class="tool-header">
              <span class="tool-icon">🌍</span>
              <span>世界观生成器</span>
            </div>
          </template>
          <p class="tool-desc">生成完整的世界观设定，包括地理、势力、力量体系等</p>
          <el-form :model="worldviewForm" label-position="top" size="small">
            <el-form-item label="题材类型">
              <el-select v-model="worldviewForm.genre" placeholder="选择题材" style="width: 100%">
                <el-option label="玄幻" value="玄幻" />
                <el-option label="仙侠" value="仙侠" />
                <el-option label="科幻" value="科幻" />
                <el-option label="西幻" value="西幻" />
              </el-select>
            </el-form-item>
            <el-form-item label="核心设定">
              <el-input v-model="worldviewForm.coreSetting" type="textarea" :rows="2" placeholder="世界的基本设定…" />
            </el-form-item>
            <el-form-item>
              <el-button type="primary" :loading="wvLoading" @click="generateWorldview">
                生成世界觀
              </el-button>
            </el-form-item>
          </el-form>
          <el-collapse-transition>
            <div v-if="worldviewResult" class="result-box">
              <el-divider />
              <h4 class="result-title">世界观设定</h4>
              <div class="result-content" v-html="renderMarkdown(worldviewResult)" />
              <div class="result-actions">
                <el-button text type="primary" size="small" @click="copyText(worldviewResult)">复制</el-button>
                <el-button text type="success" size="small" @click="saveToMaterial('世界观', worldviewResult, 'worldview')">保存到素材库</el-button>
              </div>
            </div>
          </el-collapse-transition>
        </el-card>

        <!-- 冲突/桥段生成器 -->
        <el-card class="tool-card" shadow="hover">
          <template #header>
            <div class="tool-header">
              <span class="tool-icon">💥</span>
              <span>冲突/桥段生成器</span>
            </div>
          </template>
          <p class="tool-desc">生成特定情境下的矛盾冲突或情节转折</p>
          <el-form :model="conflictForm" label-position="top" size="small">
            <el-form-item label="当前情境">
              <el-input v-model="conflictForm.scene" type="textarea" :rows="2" placeholder="描述当前故事情境..." />
            </el-form-item>
            <el-form-item label="冲突类型">
              <el-select v-model="conflictForm.type" placeholder="选择类型" style="width: 100%">
                <el-option label="人物冲突" value="character" />
                <el-option label="情节转折" value="plot_twist" />
                <el-option label="情感冲突" value="emotional" />
                <el-option label="势力冲突" value="faction" />
                <el-option label="道德困境" value="moral" />
              </el-select>
            </el-form-item>
            <el-form-item label="参与角色">
              <el-input v-model="conflictForm.characters" placeholder="输入角色名，用逗号分隔" />
            </el-form-item>
            <el-form-item>
              <el-button type="primary" :loading="cfLoading" @click="generateConflict">
                生成冲突
              </el-button>
            </el-form-item>
          </el-form>
          <el-collapse-transition>
            <div v-if="conflictResult" class="result-box">
              <el-divider />
              <h4 class="result-title">冲突/桥段</h4>
              <div class="result-content" v-html="renderMarkdown(conflictResult)" />
              <div class="result-actions">
                <el-button text type="primary" size="small" @click="copyText(conflictResult)">复制</el-button>
                <el-button text type="success" size="small" @click="saveToMaterial('冲突桥段', conflictResult, 'conflict_idea')">保存到素材库</el-button>
              </div>
            </div>
          </el-collapse-transition>
        </el-card>

        <!-- 人物速成工具 -->
        <el-card class="tool-card" shadow="hover">
          <template #header>
            <div class="tool-header">
              <span class="tool-icon">👤</span>
              <span>人物速成工具</span>
            </div>
          </template>
          <p class="tool-desc">一句话生成完整角色档案，支持快速导入角色库</p>
          <el-form :model="characterForm" label-position="top" size="small">
            <el-form-item label="一句话描述">
              <el-input v-model="characterForm.prompt" type="textarea" :rows="2" placeholder="例如：一个表面冷酷内心温柔的剑客，曾是帝国第一骑士..." />
            </el-form-item>
            <el-form-item label="所属作品">
              <el-select v-model="characterForm.novelId" placeholder="选择作品（可选）" style="width: 100%" clearable>
                <el-option v-for="novel in userNovels" :key="novel.id" :label="novel.title" :value="novel.id" />
              </el-select>
            </el-form-item>
            <el-form-item>
              <el-button type="primary" :loading="chLoading" @click="generateCharacter">
                生成角色
              </el-button>
            </el-form-item>
          </el-form>
          <el-collapse-transition>
            <div v-if="characterResult" class="result-box">
              <el-divider />
              <h4 class="result-title">角色档案</h4>
              <div class="result-content" v-html="renderMarkdown(characterResult)" />
              <div class="result-actions">
                <el-button text type="primary" size="small" @click="copyText(characterResult)">复制</el-button>
                <el-button text type="success" size="small" @click="saveToMaterial('角色草稿', characterResult, 'character_draft')">保存到素材库</el-button>
              </div>
            </div>
          </el-collapse-transition>
        </el-card>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { listNovels } from '@/api/novel'
import { createMaterial } from '@/api/material'
import {
  generateSynopsis,
  generateBookNames,
  generateWorldView,
  generateConflicts,
  generateCharacterQuick,
  generateGoldenFinger,
  type SynopsisResult,
  type BookNameResult,
  type WorldViewResult,
  type ConflictResult,
  type CharacterQuickResult,
  type GoldenFingerResult
} from '@/api/aiTools'

const userNovels = ref<Array<{ id: number; title: string }>>([])

// 金手指生成器
const gfLoading = ref(false)
const goldenFingerForm = ref({ genre: '', coreIdea: '' })
const goldenFingerResult = ref('')

async function generateGoldenFinger() {
  if (!goldenFingerForm.value.genre) {
    ElMessage.warning('请选择题材类型')
    return
  }
  if (!goldenFingerForm.value.coreIdea.trim()) {
    ElMessage.warning('请输入核心创意')
    return
  }
  gfLoading.value = true
  try {
    const res = await generateGoldenFinger({
      genre: goldenFingerForm.value.genre,
      coreIdea: goldenFingerForm.value.coreIdea
    })
    if (res.data?.data && res.data.data.length > 0) {
      goldenFingerResult.value = res.data.data[0].content || ''
    } else {
      goldenFingerResult.value = '生成失败，请重试'
    }
  } catch {
    goldenFingerResult.value = '生成失败，请稍后重试'
  } finally {
    gfLoading.value = false
  }
}

// 书名生成噀
const bnLoading = ref(false)
const bookNameForm = ref({ genre: '', keywords: '', style: '' })
const bookNameResult = ref<string[]>([])

async function generateBookNames() {
  if (!bookNameForm.value.genre) {
    ElMessage.warning('请选择题材类型')
    return
  }
  bnLoading.value = true
  try {
    const res = await generateBookNames({
      genre: bookNameForm.value.genre,
      keywords: bookNameForm.value.keywords,
      style: bookNameForm.value.style || undefined
    })
    if (res.data?.data) {
      bookNameResult.value = res.data.data.map((n: BookNameResult) => n.name)
    } else {
      bookNameResult.value = []
    }
    if (bookNameResult.value.length === 0) {
      ElMessage.warning('未生成到书名，请调整参数重试')
    }
  } catch {
    bookNameResult.value = []
  } finally {
    bnLoading.value = false
  }
}

// 简介生成器
const synLoading = ref(false)
const synopsisForm = ref({ bookName: '', setting: '', style: 'suspense', count: 1 })
const synopsisResult = ref<SynopsisResult[]>([])

async function generateSynopsisHandler() {
  if (!synopsisForm.value.bookName.trim()) {
    ElMessage.warning('请输入书名')
    return
  }
  synLoading.value = true
  try {
    const res = await generateSynopsis({
      bookName: synopsisForm.value.bookName,
      setting: synopsisForm.value.setting,
      style: synopsisForm.value.style,
      count: synopsisForm.value.count
    })
    if (res.data?.data) {
      synopsisResult.value = res.data.data
    } else {
      synopsisResult.value = []
    }
  } catch {
    synopsisResult.value = []
    ElMessage.error('生成失败，请稍后重试')
  } finally {
    synLoading.value = false
  }
}

// 世界观生成器
const wvLoading = ref(false)
const worldviewForm = ref({ genre: '', coreSetting: '' })
const worldviewResult = ref('')

async function generateWorldview() {
  if (!worldviewForm.value.genre) {
    ElMessage.warning('请选择题材类型')
    return
  }
  if (!worldviewForm.value.coreSetting.trim()) {
    ElMessage.warning('请输入核心设定')
    return
  }
  wvLoading.value = true
  try {
    const res = await generateWorldView({
      genre: worldviewForm.value.genre,
      coreSetting: worldviewForm.value.coreSetting
    })
    if (res.data?.data && res.data.data.length > 0) {
      worldviewResult.value = res.data.data[0].content || ''
    } else {
      worldviewResult.value = '生成失败，请重试'
    }
  } catch {
    worldviewResult.value = '生成失败，请稍后重试'
  } finally {
    wvLoading.value = false
  }
}

// 冲突/桥段生成噀
const cfLoading = ref(false)
const conflictForm = ref({ scene: '', type: 'character', characters: '' })
const conflictResult = ref('')

async function generateConflict() {
  if (!conflictForm.value.scene.trim()) {
    ElMessage.warning('请输入当前情境')
    return
  }
  cfLoading.value = true
  try {
    const res = await generateConflicts({
      scene: conflictForm.value.scene,
      type: conflictForm.value.type,
      characters: conflictForm.value.characters ? conflictForm.value.characters.split(',') : undefined
    })
    if (res.data?.data && res.data.data.length > 0) {
      conflictResult.value = res.data.data[0].content || ''
    } else {
      conflictResult.value = '生成失败，请重试'
    }
  } catch {
    conflictResult.value = '生成失败，请稍后重试'
  } finally {
    cfLoading.value = false
  }
}

// 人物速成工具
const chLoading = ref(false)
const characterForm = ref({ prompt: '', novelId: undefined as number | undefined })
const characterResult = ref('')

async function generateCharacter() {
  if (!characterForm.value.prompt.trim()) {
    ElMessage.warning('请输入角色描述')
    return
  }
  chLoading.value = true
  try {
    const res = await generateCharacterQuick({
      prompt: characterForm.value.prompt,
      novelId: characterForm.value.novelId
    })
    if (res.data?.data) {
      const data = res.data.data
      characterResult.value = [
        `**姓名**: ${data.name}`,
        data.gender ? `**性别**: ${data.gender}` : '',
        data.age ? `**年龄**: ${data.age}` : '',
        data.identity ? `**身份**: ${data.identity}` : '',
        data.personality?.length ? `**性格**: ${data.personality.join(', ')}` : '',
        data.background ? `**背景**: ${data.background}` : ''
      ].filter(Boolean).join('\n\n')
    } else {
      characterResult.value = '生成失败，请重试'
    }
  } catch {
    characterResult.value = '生成失败，请稍后重试'
  } finally {
    chLoading.value = false
  }
}

// 工具函数
function renderMarkdown(text: string): string {
  if (!text) return ''
  return text
    .replace(/&/g, '&')
    .replace(/</g, '<')
    .replace(/>/g, '>')
    .replace(/### (.+)/g, '<h5>$1</h5>')
    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
    .replace(/\n/g, '<br>')
}

function copyText(text: string) {
  navigator.clipboard.writeText(text).then(() => {
    ElMessage.success('已复制到剪贴板')
  })
}

async function saveToMaterial(title: string, content: string, type: string) {
  try {
    await createMaterial({
      title,
      type,
      content,
      description: title
    })
    ElMessage.success('已保存到素材库')
  } catch {
    ElMessage.error('保存失败，请重试')
  }
}

onMounted(async () => {
  try {
    const res = await listNovels({ page: 1, size: 100 })
    userNovels.value = res.data?.records || []
  } catch {
    userNovels.value = []
  }
})
</script>

<style lang="scss" scoped>
.toolbox-page {
  min-height: 100vh;
  background: $bg-gray;
}

.page-header {
  padding: $space-lg $space-xl;
  background: $bg-white;
  border-bottom: 1px solid $border-color;

  h1 {
    font-size: $font-size-xl;
    font-weight: 600;
  }

  .desc {
    margin-top: $space-xs;
    color: $text-secondary;
    font-size: $font-size-sm;
  }
}

.page-content {
  padding: $space-xl;
  max-width: 1400px;
  margin: 0 auto;
}

.tool-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(480px, 1fr));
  gap: $space-lg;
}

.tool-card {
  :deep(.el-card__header) {
    padding: $space-md $space-lg;
  }
}

.tool-header {
  display: flex;
  align-items: center;
  gap: $space-sm;
  font-weight: 600;
  font-size: $font-size-md;

  .tool-icon {
    font-size: 20px;
  }
}

.tool-desc {
  color: $text-secondary;
  font-size: $font-size-sm;
  margin-bottom: $space-md;
  line-height: 1.5;
}

.result-box {
  margin-top: $space-sm;
}

.result-title {
  font-size: $font-size-sm;
  font-weight: 600;
  color: $primary-color;
  margin-bottom: $space-sm;
}

.result-content {
  background: $bg-gray;
  border: 1px solid $border-color;
  border-radius: $border-radius;
  padding: $space-md;
  font-size: $font-size-sm;
  line-height: 1.8;
  white-space: pre-wrap;
  max-height: 300px;
  overflow-y: auto;

  h5 {
    font-size: $font-size-md;
    margin: $space-sm 0;
    color: $text-primary;
  }

  strong {
    color: $primary-color;
  }
}

.name-list {
  list-style: none;
  padding: 0;
}

.name-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: $space-sm $space-md;
  background: $bg-gray;
  border: 1px solid $border-color;
  border-radius: $border-radius;
  margin-bottom: $space-sm;

  .name-text {
    font-weight: 500;
    font-size: $font-size-md;
  }
}

.copy-btn {
  margin-top: $space-sm;
}

.synopsis-item {
  margin-bottom: $space-md;
  padding-bottom: $space-md;
  border-bottom: 1px dashed $border-color;

  &:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
  }

  .synopsis-index {
    font-size: $font-size-xs;
    color: $text-muted;
    margin-bottom: $space-xs;
  }
}

@media (max-width: 768px) {
  .tool-grid {
    grid-template-columns: 1fr;
  }

  .page-content {
    padding: $space-md;
  }
}
</style>
