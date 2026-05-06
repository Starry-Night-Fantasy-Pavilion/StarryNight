<template>
  <div class="reader-page" :class="{ 'night-mode': nightMode, 'eye-care-mode': eyeCareMode }">
    <div class="reader-header" v-show="showHeader">
      <div class="header-content">
        <el-button :icon="ArrowLeft" circle @click="goBack" />
        <span class="book-title">{{ bookTitle }}</span>
        <div class="header-actions">
          <el-button :icon="Star" circle @click="toggleBookmark" />
          <el-button :icon="Setting" circle @click="showSettings = true" />
        </div>
      </div>
    </div>

    <div class="reader-content" @click="toggleHeader">
      <div v-if="loadError" class="chapter-error">{{ loadError }}</div>
      <div v-else-if="!loading && !toc.length" class="chapter-empty">
        {{
          liveMode
            ? '本书暂无可读内容。请检查书源接口 /api/bookstore/book 与 url、sourceId 是否可用。'
            : '本书暂无章节数据。请确认运营端已配置书源 URL，或稍后再试。'
        }}
      </div>
      <template v-else>
        <div class="chapter-title">第 {{ currentChapterNo }} 章 {{ chapterTitle }}</div>
        <div v-if="loading" class="chapter-loading">加载中…</div>
        <div v-else class="chapter-content" v-html="chapterContent"></div>
      </template>
    </div>

    <div class="reader-footer" v-show="showHeader">
      <div class="footer-content">
        <div class="chapter-nav">
          <el-button :disabled="!hasPrevChapter || loading" @click="prevChapter">上一章</el-button>
          <el-select
            v-model="currentChapterNo"
            :disabled="!toc.length || loading"
            @change="onTocSelect"
            style="width: min(280px, 72vw)"
          >
            <el-option
              v-for="ch in toc"
              :key="ch.id"
              :label="`第${ch.chapterNo}章 ${ch.title}`"
              :value="ch.chapterNo"
            />
          </el-select>
          <el-button :disabled="!hasNextChapter || loading" @click="nextChapter">下一章</el-button>
        </div>
        <div class="reading-progress">
          <span>{{ Math.round(progress) }}%</span>
          <el-slider v-model="progress" :step="1" :max="100" @change="seekTo" />
        </div>
      </div>
    </div>

    <el-drawer v-model="showSettings" title="阅读设置" direction="btt" size="300px">
      <div class="settings-content">
        <div class="setting-item">
          <span class="setting-label">字体大小</span>
          <el-slider v-model="fontSize" :min="14" :max="28" :step="2" />
          <span class="setting-value">{{ fontSize }}px</span>
        </div>
        <div class="setting-item">
          <span class="setting-label">行高</span>
          <el-slider v-model="lineHeight" :min="1.5" :max="2.5" :step="0.2" />
        </div>
        <div class="setting-item">
          <span class="setting-label">背景色</span>
          <div class="bg-colors">
            <div
              v-for="bg in bgColors"
              :key="bg.value"
              class="bg-color"
              :class="{ active: background === bg.value }"
              :style="{ background: bg.value }"
              @click="background = bg.value"
            ></div>
          </div>
        </div>
        <div class="setting-item">
          <span class="setting-label">夜间模式</span>
          <el-switch v-model="nightMode" />
        </div>
        <div class="setting-item">
          <span class="setting-label">护眼模式</span>
          <el-switch v-model="eyeCareMode" />
        </div>
      </div>
    </el-drawer>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeft, Star, Setting } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import {
  getBookstoreBookCached,
  getBookstoreChaptersCached,
  getBookstoreChapterReadCached,
  type BookstoreChapterRead,
  type BookstoreChapterTocItem,
  type BookstoreLiveChapterApi
} from '@/api/bookstore'
import {
  readLiveTocFromSession,
  writeLiveTocToSession,
  fetchLiveBookCached,
  fetchLiveChapterCached,
  rewriteBookstoreImagesViaProxy,
  type LiveTocPayload
} from '@/utils/book-source-utils'
import { extractApiErrorMessage } from '@/utils/request'

const route = useRoute()
const router = useRouter()

const bookId = Number(route.params.id)
const startChapter = Number(route.params.chapter) || 1

const bookTitle = ref('')
const chapterTitle = ref('')
const currentChapterNo = ref(startChapter)
const toc = ref<BookstoreChapterTocItem[]>([])
const liveMode = ref(false)
const liveToc = ref<LiveTocPayload | null>(null)
const lastRead = ref<BookstoreChapterRead | null>(null)
const chapterContent = ref('')
const loading = ref(true)
const loadError = ref('')
const showHeader = ref(true)
const showSettings = ref(false)
const progress = ref(0)

const fontSize = ref(18)
const lineHeight = ref(1.8)
const background = ref('#f5f5f5')
const nightMode = ref(false)
const eyeCareMode = ref(false)

const fontSizePx = computed(() => `${fontSize.value}px`)
const lineHeightStr = computed(() => String(lineHeight.value))

const bgColors = [
  { label: '白色', value: '#f5f5f5' },
  { label: '米色', value: '#faf8f5' },
  { label: '浅绿', value: '#e8f5e9' },
  { label: '浅蓝', value: '#e3f2fd' },
  { label: '深色', value: '#263238' }
]

const hasPrevChapter = computed(() => lastRead.value?.prevChapterNo != null)
const hasNextChapter = computed(() => lastRead.value?.nextChapterNo != null)

function toggleHeader() {
  showHeader.value = !showHeader.value
}

function goBack() {
  router.push(`/bookstore/detail/${bookId}`)
}

function toggleBookmark() {
  console.log('toggle bookmark')
}

function updateProgress() {
  const i = toc.value.findIndex((c) => c.chapterNo === currentChapterNo.value)
  if (i < 0 || !toc.value.length) {
    progress.value = 0
    return
  }
  progress.value = ((i + 1) / toc.value.length) * 100
}

function prevChapter() {
  const p = lastRead.value?.prevChapterNo
  if (p != null) {
    currentChapterNo.value = p
    void loadChapter()
  }
}

function nextChapter() {
  const n = lastRead.value?.nextChapterNo
  if (n != null) {
    currentChapterNo.value = n
    void loadChapter()
  }
}

function onTocSelect() {
  void loadChapter()
}

function applyLiveChapterPayload(d: BookstoreLiveChapterApi) {
  const payload = liveToc.value
  if (!payload) return
  const idx = payload.chapters.findIndex((c) => c.chapterNo === currentChapterNo.value)
  const n = payload.chapters.length
  const prevNo = idx > 0 ? payload.chapters[idx - 1].chapterNo : null
  const nextNo = idx >= 0 && idx < n - 1 ? payload.chapters[idx + 1].chapterNo : null
  const html = rewriteBookstoreImagesViaProxy(d.contentHtml)
  lastRead.value = {
    bookId,
    chapterNo: currentChapterNo.value,
    title: d.title,
    contentHtml: html,
    prevChapterNo: prevNo,
    nextChapterNo: nextNo,
    totalChapters: n
  }
  chapterTitle.value = d.title
  chapterContent.value = html
  updateProgress()
}

async function loadChapter() {
  if (!Number.isFinite(bookId) || bookId < 1) {
    loadError.value = '无效的书籍链接'
    loading.value = false
    return
  }
  if (!toc.value.length) {
    chapterContent.value = ''
    loading.value = false
    return
  }
  loading.value = true
  loadError.value = ''
  try {
    if (liveMode.value && liveToc.value) {
      const ch = liveToc.value.chapters.find((c) => c.chapterNo === currentChapterNo.value)
      if (!ch?.url) {
        throw new Error('无效章节或缺少章节 URL')
      }
      const d = await fetchLiveChapterCached(liveToc.value.sourceId, ch.url, (fresh) => applyLiveChapterPayload(fresh))
      applyLiveChapterPayload(d)
    } else {
      const d = await getBookstoreChapterReadCached(bookId, currentChapterNo.value, (fresh) => {
        lastRead.value = fresh
        chapterTitle.value = fresh.title
        chapterContent.value = fresh.contentHtml
        updateProgress()
      })
      lastRead.value = d
      chapterTitle.value = d.title
      chapterContent.value = d.contentHtml
      updateProgress()
    }
  } catch (e) {
    loadError.value = extractApiErrorMessage(e)
    ElMessage.error(loadError.value)
  } finally {
    loading.value = false
  }
}

function seekTo(val: number | number[]) {
  if (!toc.value.length) return
  const num = Array.isArray(val) ? Number(val[0]) : Number(val)
  const i = Math.max(0, Math.min(toc.value.length - 1, Math.round((num / 100) * toc.value.length) - 1))
  currentChapterNo.value = toc.value[i].chapterNo
  void loadChapter()
}

function handleKeydown(e: KeyboardEvent) {
  if (e.key === 'ArrowLeft' || e.key === 'a') {
    prevChapter()
  } else if (e.key === 'ArrowRight' || e.key === 'd') {
    nextChapter()
  } else if (e.key === 'Escape') {
    showSettings.value = false
  }
}

onMounted(async () => {
  if (!Number.isFinite(bookId) || bookId < 1) {
    loadError.value = '无效的书籍链接'
    loading.value = false
    return
  }
  try {
    const meta = await getBookstoreBookCached(bookId)
    bookTitle.value = meta.title || '书籍'

    let session = readLiveTocFromSession(bookId)
    if ((!session?.chapters?.length || session.sourceId !== bookId) && meta.liveParseAvailable) {
      try {
        const live = await fetchLiveBookCached(bookId)
        if (live.chapters?.length) {
          session = {
            sourceId: bookId,
            chapters: live.chapters.map((c, i) => ({
              chapterNo: i + 1,
              title: c.title,
              url: c.url
            }))
          }
          writeLiveTocToSession(bookId, session)
        }
      } catch {
        /* 走库内目录 */
      }
    }

    if (session?.chapters?.length && session.sourceId === bookId) {
      liveMode.value = true
      liveToc.value = session
      toc.value = session.chapters.map((c) => ({
        id: c.chapterNo,
        chapterNo: c.chapterNo,
        title: c.title,
        wordCount: 0
      }))
    } else {
      liveMode.value = false
      liveToc.value = null
      const list = await getBookstoreChaptersCached(bookId)
      toc.value = list || []
    }

    if (toc.value.length) {
      const exists = toc.value.some((c) => c.chapterNo === currentChapterNo.value)
      if (!exists) {
        currentChapterNo.value = toc.value[0].chapterNo
      }
      await loadChapter()
    } else {
      loading.value = false
    }
  } catch (e) {
    loadError.value = extractApiErrorMessage(e)
    loading.value = false
    ElMessage.error(loadError.value)
  }
  document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
})
</script>

<style lang="scss" scoped>
.reader-page {
  min-height: 100vh;
  background: v-bind(background);
  transition: background 0.3s;

  &.night-mode {
    background: #1a1a1a;
    color: #ccc;

    .reader-header, .reader-footer {
      background: #2d2d2d;
      border-color: #444;
    }

    .chapter-title {
      color: #999;
    }
  }

  &.eye-care-mode {
    background: #faf8f5;

    .chapter-content {
      filter: sepia(0.1);
    }
  }

  .reader-header {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    background: white;
    border-bottom: 1px solid var(--el-border-color-lighter);
    z-index: 100;

    .header-content {
      max-width: 1200px;
      margin: 0 auto;
      padding: 12px 24px;
      display: flex;
      align-items: center;
      gap: 16px;

      .book-title {
        flex: 1;
        font-weight: 500;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }

      .header-actions {
        display: flex;
        gap: 8px;
      }
    }
  }

  .reader-content {
    max-width: 800px;
    margin: 0 auto;
    padding: 80px 24px 120px;
    cursor: pointer;

    .chapter-title {
      font-size: 20px;
      font-weight: 600;
      text-align: center;
      margin-bottom: 24px;
      color: var(--el-text-color);
    }

    .chapter-content {
      color: var(--el-text-color-regular);

      :deep(p) {
        font-size: v-bind(fontSizePx);
        line-height: v-bind(lineHeightStr);
      }
    }

    .chapter-loading,
    .chapter-empty,
    .chapter-error {
      text-align: center;
      padding: 48px 16px;
      color: var(--el-text-color-secondary);
    }

    .chapter-error {
      color: var(--el-color-danger);
    }
  }

  .reader-footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: white;
    border-top: 1px solid var(--el-border-color-lighter);
    z-index: 100;

    .footer-content {
      max-width: 1200px;
      margin: 0 auto;
      padding: 12px 24px;

      .chapter-nav {
        display: flex;
        justify-content: center;
        gap: 16px;
        margin-bottom: 12px;
      }

      .reading-progress {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 13px;
        color: var(--el-text-color-secondary);
      }
    }
  }

  .settings-content {
    padding: 0 16px;

    .setting-item {
      margin-bottom: 24px;

      .setting-label {
        display: block;
        margin-bottom: 12px;
        font-size: 14px;
        color: var(--el-text-color-regular);
      }

      .setting-value {
        margin-left: 12px;
        font-size: 13px;
        color: var(--el-text-color-secondary);
      }

      .bg-colors {
        display: flex;
        gap: 12px;

        .bg-color {
          width: 36px;
          height: 36px;
          border-radius: 6px;
          cursor: pointer;
          border: 2px solid transparent;

          &.active {
            border-color: var(--el-color-primary);
          }

          &:hover {
            transform: scale(1.1);
          }
        }
      }
    }
  }
}
</style>
