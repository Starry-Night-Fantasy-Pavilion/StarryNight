<template>
  <div class="admin-bookstore page-container">
    <div class="page-header">
      <h1>书城管理</h1>
    </div>

    <el-tabs v-model="activeTab">
      <el-tab-pane label="基础配置" name="cfg">
        <el-card v-loading="cfgLoading">
          <el-form label-width="140px" style="max-width: 720px">
            <el-form-item label="启用书城">
              <el-switch v-model="cfg.enabled" />
            </el-form-item>
            <el-form-item label="页头标题">
              <el-input v-model="cfg.siteTitle" placeholder="星夜书城" />
            </el-form-item>
            <el-form-item label="轮播 JSON">
              <el-input
                v-model="cfg.bannersJson"
                type="textarea"
                :rows="6"
                placeholder='[{"title":"","description":"","imageUrl":"","bookId":1}]'
              />
            </el-form-item>
            <el-form-item label="侧栏读者榜 JSON">
              <el-input v-model="cfg.sidebarReadersJson" type="textarea" :rows="4" placeholder="[]" />
            </el-form-item>
            <el-form-item label="最新更新 JSON">
              <el-input
                v-model="cfg.latestUpdatesJson"
                type="textarea"
                :rows="4"
                placeholder='[{"bookTitle":"","chapter":1,"time":""}]'
              />
            </el-form-item>
            <el-form-item>
              <el-button type="primary" :loading="cfgSaving" @click="saveCfg">保存配置</el-button>
            </el-form-item>
          </el-form>
        </el-card>
      </el-tab-pane>

      <el-tab-pane label="书源管理" name="sources">
        <el-card>
          <el-alert
            type="info"
            :closable="false"
            class="sources-tip"
            title="每条记录仅允许一种书源：填写「书源 URL」或「书源 JSON」二选一。JSON 可粘贴或选择本地 .json 文件。列表标题由服务端根据 URL / JSON 自动生成。仍可通过「章节」维护本站正文。"
          />
          <div class="toolbar">
            <el-input v-model="kw" placeholder="标题 / URL / JSON 片段" clearable style="width: 240px" @keyup.enter="loadBooks" />
            <el-button @click="loadBooks">查询</el-button>
            <el-button type="primary" @click="openBook()">新建书源</el-button>
          </div>
          <el-table :data="bookRows" stripe v-loading="bookLoading" class="book-table">
            <el-table-column prop="id" label="ID" width="72" />
            <el-table-column label="方式" width="72" align="center">
              <template #default="{ row }">
                <el-tag :type="rowSourceKind(row) === 'URL' ? 'primary' : 'success'" size="small">
                  {{ rowSourceKind(row) }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="title" label="列表标题" min-width="160" show-overflow-tooltip />
            <el-table-column label="书源摘要" min-width="220" show-overflow-tooltip>
              <template #default="{ row }">{{ rowSourcePreview(row) }}</template>
            </el-table-column>
            <el-table-column label="上架" width="72">
              <template #default="{ row }">
                <el-tag :type="row.status === 1 ? 'success' : 'info'" size="small">
                  {{ row.status === 1 ? '是' : '否' }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column label="操作" width="200" fixed="right">
              <template #default="{ row }">
                <el-button type="primary" link @click="openBook(row)">编辑</el-button>
                <el-button type="primary" link @click="openChapters(row)">章节</el-button>
                <el-popconfirm title="确定删除此书源？" @confirm="removeBook(row.id!)">
                  <template #reference>
                    <el-button type="danger" link>删除</el-button>
                  </template>
                </el-popconfirm>
              </template>
            </el-table-column>
          </el-table>
          <div class="pager">
            <el-pagination
              v-model:current-page="page"
              v-model:page-size="pageSize"
              :total="total"
              :page-sizes="[10, 20, 50]"
              layout="total, sizes, prev, pager, next"
              @size-change="loadBooks"
              @current-change="loadBooks"
            />
          </div>
        </el-card>
      </el-tab-pane>
    </el-tabs>

    <el-dialog v-model="bookDlg" :title="bookForm.id ? '编辑书源' : '新建书源'" width="720px" top="6vh">
      <el-form :model="bookForm" label-width="108px">
        <el-form-item label="书源类型">
          <el-radio-group v-model="sourceMode">
            <el-radio-button label="url">书源 URL</el-radio-button>
            <el-radio-button label="json">书源 JSON</el-radio-button>
          </el-radio-group>
        </el-form-item>
        <template v-if="sourceMode === 'url'">
          <el-form-item label="书源 URL" required>
            <el-input
              v-model="bookForm.sourceUrl"
              type="textarea"
              :rows="4"
              placeholder="远程详情页或章节目录页完整 URL，例如 https://..."
            />
          </el-form-item>
        </template>
        <template v-else>
          <el-form-item label="书源 JSON" required>
            <div class="json-actions">
              <input
                ref="jsonFileInputRef"
                type="file"
                accept=".json,application/json"
                class="json-file-input"
                @change="onPickJsonFile"
              />
              <el-button @click="triggerPickJson">选择本地 JSON 文件</el-button>
              <span class="json-hint">与 URL 二选一；可粘贴或上传文件</span>
            </div>
            <el-input
              v-model="bookForm.sourceJson"
              type="textarea"
              :rows="12"
              class="mono-json"
              placeholder="粘贴完整书源规则 JSON（须合法 JSON）"
            />
          </el-form-item>
        </template>
      </el-form>
      <template #footer>
        <el-button @click="bookDlg = false">取消</el-button>
        <el-button type="primary" :loading="bookSaving" @click="saveBook">保存</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="chapterDlg" :title="`章节 — ${chapterBookTitle}`" width="720px" destroy-on-close>
      <div class="chapter-toolbar">
        <el-button type="primary" @click="openChapterEdit()">新增章节</el-button>
      </div>
      <el-table :data="chapterRows" stripe v-loading="chapterLoading" class="chapter-table">
        <el-table-column prop="chapterNo" label="序号" width="72" />
        <el-table-column prop="title" label="标题" min-width="160" show-overflow-tooltip />
        <el-table-column prop="wordCount" label="字数" width="88" />
        <el-table-column label="操作" width="160" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link @click="openChapterEdit(row)">编辑</el-button>
            <el-popconfirm title="删除该章节？" @confirm="removeChapter(row)">
              <template #reference>
                <el-button type="danger" link>删除</el-button>
              </template>
            </el-popconfirm>
          </template>
        </el-table-column>
      </el-table>
    </el-dialog>

    <el-dialog
      v-model="chapterEditDlg"
      :title="chapterForm.id != null ? '编辑章节' : '新增章节'"
      width="640px"
      destroy-on-close
      @close="resetChapterForm"
    >
      <el-form v-loading="chapterDetailLoading" label-width="88px">
        <el-form-item label="序号" required>
          <el-input-number v-model="chapterForm.chapterNo" :min="1" :max="99999" />
        </el-form-item>
        <el-form-item label="标题" required>
          <el-input v-model="chapterForm.title" maxlength="200" show-word-limit />
        </el-form-item>
        <el-form-item label="正文">
          <el-input
            v-model="chapterForm.content"
            type="textarea"
            :rows="14"
            placeholder="可粘贴纯文本（空行分段）或 HTML；无尖括号时服务端会转安全段落。"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="chapterEditDlg = false">取消</el-button>
        <el-button type="primary" :loading="chapterSaving" @click="saveChapter">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { ElMessage } from 'element-plus'
import type { BookstoreBookAdmin, BookstoreChapterAdminRow } from '@/api/bookstore'
import {
  createAdminBookstoreBook,
  createAdminBookstoreChapter,
  deleteAdminBookstoreBook,
  deleteAdminBookstoreChapter,
  getAdminBookstoreChapter,
  getAdminBookstoreConfig,
  listAdminBookstoreBooks,
  listAdminBookstoreChapters,
  saveAdminBookstoreConfig,
  updateAdminBookstoreBook,
  updateAdminBookstoreChapter
} from '@/api/bookstore'

const activeTab = ref('cfg')
const cfgLoading = ref(false)
const cfgSaving = ref(false)
const cfg = reactive({
  enabled: true,
  siteTitle: '',
  bannersJson: '[]',
  sidebarReadersJson: '[]',
  latestUpdatesJson: '[]'
})

async function loadCfg() {
  cfgLoading.value = true
  try {
    const d = await getAdminBookstoreConfig()
    cfg.enabled = d.enabled
    cfg.siteTitle = d.siteTitle
    cfg.bannersJson = d.bannersJson
    cfg.sidebarReadersJson = d.sidebarReadersJson
    cfg.latestUpdatesJson = d.latestUpdatesJson
  } finally {
    cfgLoading.value = false
  }
}

async function saveCfg() {
  cfgSaving.value = true
  try {
    await saveAdminBookstoreConfig({
      enabled: cfg.enabled,
      siteTitle: cfg.siteTitle,
      bannersJson: cfg.bannersJson,
      sidebarReadersJson: cfg.sidebarReadersJson,
      latestUpdatesJson: cfg.latestUpdatesJson
    })
    ElMessage.success('已保存')
  } finally {
    cfgSaving.value = false
  }
}

const kw = ref('')
const page = ref(1)
const pageSize = ref(10)
const total = ref(0)
const bookLoading = ref(false)
const bookRows = ref<BookstoreBookAdmin[]>([])

async function loadBooks() {
  bookLoading.value = true
  try {
    const data = await listAdminBookstoreBooks({
      keyword: kw.value || undefined,
      page: page.value,
      size: pageSize.value
    })
    bookRows.value = data.records
    total.value = data.total
  } finally {
    bookLoading.value = false
  }
}

const sourceMode = ref<'url' | 'json'>('url')
const jsonFileInputRef = ref<HTMLInputElement | null>(null)

const bookDlg = ref(false)
const bookSaving = ref(false)
const bookForm = reactive<BookstoreBookAdmin>({
  title: '',
  author: '',
  coverUrl: '',
  intro: '',
  categoryId: undefined,
  isVip: 0,
  rating: 8,
  wordCount: 0,
  readCount: 0,
  sortOrder: 0,
  status: 1,
  tags: '',
  sourceUrl: '',
  sourceJson: ''
})

function rowSourceKind(row: BookstoreBookAdmin): string {
  if (row.sourceUrl?.trim()) return 'URL'
  if (row.sourceJson?.trim()) return 'JSON'
  return '—'
}

function rowSourcePreview(row: BookstoreBookAdmin): string {
  const u = row.sourceUrl?.trim()
  if (u) {
    return u.length > 120 ? `${u.slice(0, 117)}…` : u
  }
  const j = row.sourceJson?.trim()
  if (j) {
    return `JSON · ${j.length} 字符`
  }
  return '—'
}

function syncSourceModeFromForm() {
  const u = !!bookForm.sourceUrl?.trim()
  const j = !!bookForm.sourceJson?.trim()
  if (u && j) {
    sourceMode.value = 'url'
    ElMessage.warning('本条同时存在 URL 与 JSON，已按「书源 URL」打开；保存后将只保留当前所选类型')
  } else if (j) {
    sourceMode.value = 'json'
  } else {
    sourceMode.value = 'url'
  }
}

function triggerPickJson() {
  jsonFileInputRef.value?.click()
}

function onPickJsonFile(e: Event) {
  const input = e.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return
  const reader = new FileReader()
  reader.onload = () => {
    bookForm.sourceJson = String(reader.result ?? '')
    ElMessage.success('已读取 JSON 文件')
  }
  reader.onerror = () => ElMessage.error('读取文件失败')
  reader.readAsText(file, 'UTF-8')
  input.value = ''
}

function openBook(row?: BookstoreBookAdmin) {
  if (row) {
    Object.assign(bookForm, row)
    syncSourceModeFromForm()
  } else {
    sourceMode.value = 'url'
    Object.assign(bookForm, {
      id: undefined,
      title: '',
      author: '',
      coverUrl: '',
      intro: '',
      categoryId: undefined,
      isVip: 0,
      rating: 8,
      wordCount: 0,
      readCount: 0,
      sortOrder: 0,
      status: 1,
      tags: '',
      sourceUrl: '',
      sourceJson: ''
    })
  }
  bookDlg.value = true
}

async function saveBook() {
  if (sourceMode.value === 'url') {
    bookForm.sourceJson = ''
    if (!bookForm.sourceUrl?.trim()) {
      ElMessage.warning('请填写书源 URL')
      return
    }
  } else {
    bookForm.sourceUrl = ''
    if (!bookForm.sourceJson?.trim()) {
      ElMessage.warning('请填写或上传书源 JSON')
      return
    }
  }
  bookForm.title = ''
  bookForm.author = ''
  bookForm.coverUrl = ''
  bookForm.intro = ''
  bookForm.tags = ''
  bookForm.categoryId = undefined
  bookSaving.value = true
  try {
    if (bookForm.id) {
      await updateAdminBookstoreBook(bookForm.id, bookForm)
    } else {
      await createAdminBookstoreBook(bookForm)
    }
    ElMessage.success('已保存')
    bookDlg.value = false
    await loadBooks()
  } finally {
    bookSaving.value = false
  }
}

async function removeBook(id: number) {
  await deleteAdminBookstoreBook(id)
  ElMessage.success('已删除')
  await loadBooks()
}

const chapterDlg = ref(false)
const chapterBookId = ref<number | null>(null)
const chapterBookTitle = ref('')
const chapterRows = ref<BookstoreChapterAdminRow[]>([])
const chapterLoading = ref(false)
const chapterEditDlg = ref(false)
const chapterDetailLoading = ref(false)
const chapterSaving = ref(false)
const chapterForm = reactive<{ id?: number; chapterNo: number; title: string; content: string }>({
  chapterNo: 1,
  title: '',
  content: ''
})

function resetChapterForm() {
  chapterForm.id = undefined
  chapterForm.chapterNo = 1
  chapterForm.title = ''
  chapterForm.content = ''
}

async function openChapters(row: BookstoreBookAdmin) {
  chapterBookId.value = row.id ?? null
  chapterBookTitle.value = row.title || ''
  chapterDlg.value = true
  await loadChapterList()
}

async function loadChapterList() {
  if (chapterBookId.value == null) return
  chapterLoading.value = true
  try {
    chapterRows.value = await listAdminBookstoreChapters(chapterBookId.value)
  } catch {
    ElMessage.error('加载章节失败')
  } finally {
    chapterLoading.value = false
  }
}

async function openChapterEdit(row?: BookstoreChapterAdminRow) {
  chapterEditDlg.value = true
  chapterDetailLoading.value = true
  try {
    if (row && chapterBookId.value != null) {
      const d = await getAdminBookstoreChapter(chapterBookId.value, row.id)
      chapterForm.id = d.id
      chapterForm.chapterNo = d.chapterNo
      chapterForm.title = d.title
      chapterForm.content = d.content || ''
    } else {
      chapterForm.id = undefined
      const maxNo = chapterRows.value.reduce((m, c) => Math.max(m, c.chapterNo), 0)
      chapterForm.chapterNo = maxNo + 1
      chapterForm.title = ''
      chapterForm.content = ''
    }
  } catch {
    ElMessage.error('加载章节详情失败')
    chapterEditDlg.value = false
  } finally {
    chapterDetailLoading.value = false
  }
}

async function saveChapter() {
  if (chapterBookId.value == null) return
  if (!chapterForm.title.trim()) {
    ElMessage.warning('请填写章节标题')
    return
  }
  chapterSaving.value = true
  try {
    if (chapterForm.id != null) {
      await updateAdminBookstoreChapter(chapterBookId.value, chapterForm.id, {
        chapterNo: chapterForm.chapterNo,
        title: chapterForm.title.trim(),
        content: chapterForm.content
      })
    } else {
      await createAdminBookstoreChapter(chapterBookId.value, {
        chapterNo: chapterForm.chapterNo,
        title: chapterForm.title.trim(),
        content: chapterForm.content
      })
    }
    ElMessage.success('已保存')
    chapterEditDlg.value = false
    await loadChapterList()
    await loadBooks()
  } catch {
    ElMessage.error('保存失败')
  } finally {
    chapterSaving.value = false
  }
}

async function removeChapter(row: BookstoreChapterAdminRow) {
  if (chapterBookId.value == null) return
  try {
    await deleteAdminBookstoreChapter(chapterBookId.value, row.id)
    ElMessage.success('已删除')
    await loadChapterList()
    await loadBooks()
  } catch {
    ElMessage.error('删除失败')
  }
}

onMounted(async () => {
  await loadCfg()
  await loadBooks()
})
</script>

<style scoped lang="scss">
.toolbar {
  display: flex;
  gap: $space-md;
  align-items: center;
  margin-bottom: $space-md;
  flex-wrap: wrap;
}

.sources-tip {
  margin-bottom: $space-md;
}

.book-table {
  margin-top: $space-sm;
}

.mono-json :deep(textarea) {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;
  font-size: 12px;
  line-height: 1.45;
}

.json-actions {
  display: flex;
  align-items: center;
  gap: $space-md;
  margin-bottom: $space-sm;
  flex-wrap: wrap;
}

.json-hint {
  font-size: 12px;
  color: var(--el-text-color-secondary);
}

.json-file-input {
  position: absolute;
  width: 0;
  height: 0;
  opacity: 0;
  pointer-events: none;
}

.pager {
  margin-top: $space-md;
  display: flex;
  justify-content: flex-end;
}

.chapter-toolbar {
  margin-bottom: $space-md;
}

.chapter-table {
  margin-top: $space-sm;
}
</style>
