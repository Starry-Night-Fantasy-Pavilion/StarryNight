<template>
  <div class="admin-bookstore page-container">
    <div class="page-header">
      <h1>书城管理</h1>
    </div>

    <el-tabs v-model="activeTab" @tab-change="onTabChange">
      <el-tab-pane label="基础配置" name="cfg">
        <el-card v-loading="cfgLoading">
          <el-form label-width="140px" style="max-width: 720px">
            <el-form-item label="启用书城">
              <el-switch v-model="cfg.enabled" />
            </el-form-item>
            <el-form-item label="页头标题">
              <el-input v-model="cfg.siteTitle" placeholder="星夜书库" />
            </el-form-item>
            <el-form-item>
              <el-button type="primary" :loading="cfgSaving" @click="saveCfg">保存配置</el-button>
            </el-form-item>
          </el-form>
        </el-card>
      </el-tab-pane>

      <el-tab-pane label="书源管理" name="sources">
        <el-card>
          <el-alert type="info" :closable="false" class="sources-tip">
            <template #title>
              <span>书源</span>
            </template>
            <p class="sources-tip__p">
              按开发文档：<code>GET /api/bookstore/sources</code> 取书源 → <code>sourceId</code> 即本书 id →
              <code>GET /api/bookstore/book?sourceId=&amp;url=</code>（<code>url</code> 可空，空则用下表保存的书源 URL）。
              运营在此维护每条书目的<strong>书源 URL</strong>，与文档一致，不另造概念。
            </p>
          </el-alert>
          <div class="toolbar">
            <el-input v-model="kw" placeholder="标题 / 作者 / 书源 URL" clearable style="width: 260px" @keyup.enter="loadBooks" />
            <el-button @click="loadBooks">查询</el-button>
            <el-button type="primary" @click="openBook()">新建书目</el-button>
          </div>
          <el-table :data="bookRows" stripe v-loading="bookLoading" class="book-table">
            <el-table-column prop="id" label="ID" width="72" />
            <el-table-column prop="title" label="列表标题" min-width="160" show-overflow-tooltip />
            <el-table-column label="书源 URL" min-width="220" show-overflow-tooltip>
              <template #default="{ row }">{{ rowSourcePreview(row) }}</template>
            </el-table-column>
            <el-table-column label="上架" width="72">
              <template #default="{ row }">
                <el-tag :type="row.status === 1 ? 'success' : 'info'" size="small">
                  {{ row.status === 1 ? '是' : '否' }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column label="操作" width="140" fixed="right">
              <template #default="{ row }">
                <el-button type="primary" link @click="openBook(row)">编辑</el-button>
                <el-popconfirm title="确定删除此书目？" @confirm="removeBook(row.id!)">
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

      <el-tab-pane label="书源集合" name="legado">
        <el-card>
          <el-alert type="info" :closable="false" class="sources-tip">
            <template #title>Legado 书源数组</template>
            <p class="sources-tip__p">
              从 Yiove 等地址拉取或上传本地 JSON（须为数组）。导入后 <code>GET /api/bookstore/sources</code> 优先列出本表中的书源，
              <code>sourceId</code> 为下表 ID；无数据时仍回退「书源管理」中的书目行。
            </p>
          </el-alert>
          <div class="toolbar legado-toolbar">
            <el-input
              v-model="legadoImportUrl"
              placeholder="书源集合 JSON 地址"
              clearable
              style="flex: 1; min-width: 240px; max-width: 640px"
            />
            <el-button type="primary" :loading="legadoImportLoading" @click="importLegadoFromUrl">从 URL 导入</el-button>
            <el-button :loading="legadoImportLoading" @click="legadoFileInput?.click()">上传 JSON</el-button>
            <input
              ref="legadoFileInput"
              type="file"
              accept=".json,application/json"
              class="hidden-file"
              @change="onLegadoFile"
            />
          </div>
          <div class="toolbar">
            <el-input
              v-model="legKw"
              placeholder="书源名称 / URL"
              clearable
              style="width: 260px"
              @keyup.enter="loadLegado"
            />
            <el-button @click="loadLegado">查询</el-button>
          </div>
          <el-table :data="legadoRows" stripe v-loading="legadoLoading" class="book-table">
            <el-table-column prop="id" label="ID" width="72" />
            <el-table-column prop="bookSourceName" label="书源名称" min-width="140" show-overflow-tooltip />
            <el-table-column label="书源 URL" min-width="180" show-overflow-tooltip>
              <template #default="{ row }">{{ row.bookSourceUrl || '—' }}</template>
            </el-table-column>
            <el-table-column prop="bookSourceGroup" label="分组" width="100" show-overflow-tooltip />
            <el-table-column label="规则" width="120">
              <template #default="{ row }">
                <el-tag v-if="row.hasRuleSearch" size="small" class="rule-tag">搜</el-tag>
                <el-tag v-if="row.hasRuleToc" type="warning" size="small" class="rule-tag">目</el-tag>
                <el-tag v-if="row.hasRuleContent" type="success" size="small" class="rule-tag">文</el-tag>
                <span v-if="!row.hasRuleSearch && !row.hasRuleToc && !row.hasRuleContent">—</span>
              </template>
            </el-table-column>
            <el-table-column label="备注摘要" min-width="160" show-overflow-tooltip>
              <template #default="{ row }">{{ row.commentSnippet || '—' }}</template>
            </el-table-column>
            <el-table-column label="启用" width="88">
              <template #default="{ row }">
                <el-switch
                  :model-value="row.enabled === 1"
                  @change="(v: boolean) => toggleLegado(row, v)"
                />
              </template>
            </el-table-column>
            <el-table-column label="操作" width="88" fixed="right">
              <template #default="{ row }">
                <el-popconfirm title="确定删除该书源？" @confirm="removeLegado(row.id)">
                  <template #reference>
                    <el-button type="danger" link>删除</el-button>
                  </template>
                </el-popconfirm>
              </template>
            </el-table-column>
          </el-table>
          <div class="pager">
            <el-pagination
              v-model:current-page="legPage"
              v-model:page-size="legPageSize"
              :total="legTotal"
              :page-sizes="[10, 20, 50]"
              layout="total, sizes, prev, pager, next"
              @size-change="loadLegado"
              @current-change="loadLegado"
            />
          </div>
        </el-card>
      </el-tab-pane>
    </el-tabs>

    <el-dialog v-model="bookDlg" :title="bookForm.id ? '编辑书源' : '新建书目'" width="640px" top="6vh">
      <el-form :model="bookForm" label-width="100px">
        <el-form-item label="书源 URL" required>
          <el-input
            v-model="bookForm.sourceUrl"
            type="textarea"
            :rows="5"
            placeholder="https://…（文档里 /api/bookstore/book?url= 的地址）"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="bookDlg = false">取消</el-button>
        <el-button type="primary" :loading="bookSaving" @click="saveBook">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { ElMessage } from 'element-plus'
import type { BookstoreBookAdmin, BookstoreLegadoSourceAdmin } from '@/api/bookstore'
import {
  createAdminBookstoreBook,
  deleteAdminBookstoreBook,
  deleteAdminLegadoSource,
  getAdminBookstoreConfig,
  importAdminLegadoSourcesFromJson,
  importAdminLegadoSourcesFromUrl,
  listAdminBookstoreBooks,
  listAdminLegadoSources,
  saveAdminBookstoreConfig,
  setAdminLegadoSourceEnabled,
  updateAdminBookstoreBook
} from '@/api/bookstore'

const DEFAULT_LEGADO_COLLECTION_URL =
  'https://shuyuan-api.yiove.com/import/book-source-collection/a8adf570-e115-4b99-87e3-ccaf298ae361'

const activeTab = ref('cfg')
const cfgLoading = ref(false)
const cfgSaving = ref(false)
const cfg = reactive({
  enabled: true,
  siteTitle: ''
})

async function loadCfg() {
  cfgLoading.value = true
  try {
    const d = await getAdminBookstoreConfig()
    cfg.enabled = d.enabled
    cfg.siteTitle = d.siteTitle
  } finally {
    cfgLoading.value = false
  }
}

async function saveCfg() {
  cfgSaving.value = true
  try {
    await saveAdminBookstoreConfig({
      enabled: cfg.enabled,
      siteTitle: cfg.siteTitle
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
  sourceUrl: ''
})

function rowSourcePreview(row: BookstoreBookAdmin): string {
  const u = row.sourceUrl?.trim()
  if (u) {
    return u.length > 120 ? `${u.slice(0, 117)}…` : u
  }
  return '—'
}

function openBook(row?: BookstoreBookAdmin) {
  if (row) {
    Object.assign(bookForm, row)
  } else {
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
      sourceUrl: ''
    })
  }
  bookDlg.value = true
}

async function saveBook() {
  const u = bookForm.sourceUrl?.trim()
  if (!u) {
    ElMessage.warning('请填写书源 URL')
    return
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

function onTabChange(name: string | number) {
  if (name === 'legado') {
    void loadLegado()
  }
}

const legadoImportUrl = ref(DEFAULT_LEGADO_COLLECTION_URL)
const legadoImportLoading = ref(false)
const legadoFileInput = ref<HTMLInputElement | null>(null)
const legKw = ref('')
const legPage = ref(1)
const legPageSize = ref(10)
const legTotal = ref(0)
const legadoLoading = ref(false)
const legadoRows = ref<BookstoreLegadoSourceAdmin[]>([])

async function loadLegado() {
  legadoLoading.value = true
  try {
    const data = await listAdminLegadoSources({
      keyword: legKw.value || undefined,
      page: legPage.value,
      size: legPageSize.value
    })
    legadoRows.value = data.records
    legTotal.value = data.total
  } finally {
    legadoLoading.value = false
  }
}

function reportLegadoImport(res: { inserted: number; updated: number; skipped: number; errors?: string[] }) {
  const parts = [`新增 ${res.inserted}`, `更新 ${res.updated}`, `跳过 ${res.skipped}`]
  ElMessage.success(parts.join('，'))
  if (res.errors?.length) {
    ElMessage.warning(res.errors.slice(0, 5).join('；') + (res.errors.length > 5 ? '…' : ''))
  }
}

async function importLegadoFromUrl() {
  const u = legadoImportUrl.value?.trim()
  if (!u) {
    ElMessage.warning('请填写 URL')
    return
  }
  legadoImportLoading.value = true
  try {
    const res = await importAdminLegadoSourcesFromUrl(u)
    reportLegadoImport(res)
    await loadLegado()
  } finally {
    legadoImportLoading.value = false
  }
}

async function onLegadoFile(ev: Event) {
  const input = ev.target as HTMLInputElement
  const file = input.files?.[0]
  input.value = ''
  if (!file) return
  legadoImportLoading.value = true
  try {
    const text = await file.text()
    const res = await importAdminLegadoSourcesFromJson(text)
    reportLegadoImport(res)
    await loadLegado()
  } finally {
    legadoImportLoading.value = false
  }
}

async function toggleLegado(row: BookstoreLegadoSourceAdmin, enabled: boolean) {
  await setAdminLegadoSourceEnabled(row.id, enabled)
  row.enabled = enabled ? 1 : 0
  ElMessage.success('已更新')
}

async function removeLegado(id: number) {
  await deleteAdminLegadoSource(id)
  ElMessage.success('已删除')
  await loadLegado()
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
.sources-tip__p {
  margin: 0.5em 0 0;
  font-size: 13px;
  line-height: 1.55;
  color: var(--el-text-color-regular);
}

.book-table {
  margin-top: $space-sm;
}

.pager {
  margin-top: $space-md;
  display: flex;
  justify-content: flex-end;
}

.legado-toolbar {
  margin-bottom: $space-sm;
}

.hidden-file {
  position: absolute;
  width: 0;
  height: 0;
  opacity: 0;
  pointer-events: none;
}

.rule-tag {
  margin-right: 4px;
}

</style>
