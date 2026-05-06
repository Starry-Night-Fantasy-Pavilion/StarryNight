<template>
  <div class="knowledge-detail page-container">
    <div class="page-header">
      <el-button text @click="$router.back()">
        <el-icon><ArrowLeft /></el-icon>
        返回
      </el-button>
      <h1>{{ library?.name || '知识库详情' }}</h1>
      <div class="header-actions">
        <el-button type="primary" plain @click="showSearchDialog = true">
          <el-icon><Search /></el-icon>
          知识检索
        </el-button>
      </div>
    </div>

    <div class="page-content">
      <el-row :gutter="20">
        <el-col :span="6">
          <el-card class="info-card">
            <template #header>
              <span>基本信息</span>
            </template>
            <div class="info-list">
              <div class="info-item">
                <span class="label">分类</span>
                <el-tag :type="typeTagType(library?.type || '')" size="small">
                  {{ typeLabel(library?.type || '') }}
                </el-tag>
              </div>
              <div class="info-item">
                <span class="label">文档数：</span>
                <span>{{ library?.documentCount || 0 }}</span>
              </div>
              <div class="info-item">
                <span class="label">切片数：</span>
                <span>{{ library?.chunkCount || 0 }}</span>
              </div>
              <div class="info-item">
                <span class="label">状态：</span>
                <el-tag :type="statusTagType(library?.status || '')" size="small">
                  {{ statusLabel(library?.status || '') }}
                </el-tag>
              </div>
              <div class="info-item">
                <span class="label">描述</span>
                <span class="desc">{{ library?.description || '无' }}</span>
              </div>
              <div class="info-item">
                <span class="label">标签</span>
                <div class="tags">
                  <el-tag v-for="tag in library?.tags" :key="tag" size="small" class="tag">
                    {{ tag }}
                  </el-tag>
                  <span v-if="!library?.tags?.length">无</span>
                </div>
              </div>
            </div>
          </el-card>

          <el-card class="documents-card" style="margin-top: 16px;">
            <template #header>
              <span>文档列表</span>
            </template>
            <div v-if="documents.length === 0" class="empty-text">
              暂无文档
            </div>
            <div v-else class="document-list">
              <div
                v-for="doc in documents"
                :key="doc.id"
                class="document-item"
                :class="{ active: selectedDocId === doc.id }"
              >
                <div class="doc-info" @click="selectDocument(doc)">
                  <span class="doc-icon">{{ getDocIcon(doc.type) }}</span>
                  <span class="doc-name">{{ doc.name }}</span>
                </div>
                <div class="doc-actions">
                  <el-dropdown trigger="click">
                    <el-button text size="small" @click.stop>
                      <el-icon><MoreFilled /></el-icon>
                    </el-button>
                    <template #dropdown>
                      <el-dropdown-menu>
                        <el-dropdown-item @click.stop="previewDocument(doc)">预览</el-dropdown-item>
                        <el-dropdown-item
                          v-if="doc.status === 'ERROR'"
                          @click.stop="reparseDocument(doc)"
                        >
                          重新解析
                        </el-dropdown-item>
                        <el-dropdown-item @click.stop="deleteDocument(doc)">删除</el-dropdown-item>
                      </el-dropdown-menu>
                    </template>
                  </el-dropdown>
                </div>
              </div>
            </div>
          </el-card>
        </el-col>

        <el-col :span="18">
          <el-card class="chunks-card">
            <template #header>
              <div class="card-header">
                <span>切片预览</span>
                <el-select v-model="chunkFilter" placeholder="筛选状态" clearable style="width: 120px;">
                  <el-option label="全部" value="" />
                  <el-option label="已引用" value="USED" />
                  <el-option label="未引用" value="UNUSED" />
                </el-select>
              </div>
            </template>

            <div v-if="selectedDocId" class="chunks-content">
              <div v-if="chunksLoading" v-loading="chunksLoading" class="loading-container"></div>
              <div v-else-if="chunks.length === 0" class="empty-state">
                <el-icon :size="48"><Document /></el-icon>
                <p>暂无切片数据</p>
                <p class="tip">文档正在处理中，请稍后再试</p>
              </div>
              <div v-else class="chunks-list">
                <div
                  v-for="(chunk, index) in filteredChunks"
                  :key="chunk.id"
                  class="chunk-item"
                >
                  <div class="chunk-header">
                    <span class="chunk-index">#{{ index + 1 }}</span>
                    <el-tag v-if="chunk.status === 'USED'" type="success" size="small">已引用</el-tag>
                    <el-tag v-else type="info" size="small">未引用</el-tag>
                    <el-button text size="small" @click="previewChunk(chunk)">预览</el-button>
                  </div>
                  <div class="chunk-content">{{ chunk.content }}</div>
                  <div class="chunk-meta">
                    <span>字数：{{ chunk.content?.length || 0 }}</span>
                    <span v-if="chunk.referenceCount">引用次数：{{ chunk.referenceCount }}</span>
                  </div>
                </div>
              </div>
            </div>

            <div v-else class="empty-state">
              <el-icon :size="48"><Document /></el-icon>
              <p>请选择左侧文档查看切片</p>
            </div>
          </el-card>
        </el-col>
      </el-row>
    </div>

    <el-dialog v-model="showSearchDialog" title="知识检索" width="680px">
      <el-form :model="searchForm" label-position="top">
        <el-form-item label="检索内容">
          <el-input
            v-model="searchForm.query"
            type="textarea"
            :rows="4"
            placeholder="输入要检索的内容..."
          />
        </el-form-item>
        <el-form-item label="检索模式">
          <el-radio-group v-model="searchForm.mode">
            <el-radio value="keyword">关键词检索</el-radio>
            <el-radio value="semantic">语义检索</el-radio>
            <el-radio value="hybrid">混合检索</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="返回数量">
          <el-slider v-model="searchForm.topK" :min="1" :max="20" show-input />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showSearchDialog = false">取消</el-button>
        <el-button type="primary" :loading="searching" @click="performSearch">检索</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="showPreviewDialog" title="切片预览" width="720px">
      <div v-if="previewChunkData" class="preview-content">
        <div class="preview-meta">
          <span>切片 #{{ previewChunkData.index }}</span>
          <el-tag :type="statusTagType(previewChunkData.status)" size="small">
            {{ statusLabel(previewChunkData.status) }}
          </el-tag>
        </div>
        <div class="preview-text">{{ previewChunkData.content }}</div>
      </div>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { ArrowLeft, Search, Document, MoreFilled } from '@element-plus/icons-vue'
import {
  getKnowledge,
  searchKnowledge,
  listDocuments,
  deleteDocument as removeDocument,
  reparseDocument as reprocessDocument,
  listDocumentChunks
} from '@/api/knowledge'

interface Library {
  id: number
  name: string
  type: string
  description?: string
  tags?: string[]
  documentCount: number
  chunkCount: number
  status: string
  createdAt: string
  updatedAt: string
}

interface Document {
  id: number
  name: string
  type: string
  status: string
  chunkCount: number
}

interface Chunk {
  id: number
  content: string
  status: string
  referenceCount?: number
  index?: number
}

const route = useRoute()
const router = useRouter()
const libraryId = computed(() => route.params.id as string)

const library = ref<Library | null>(null)
const documents = ref<Document[]>([])
const chunks = ref<Chunk[]>([])
const selectedDocId = ref<number | null>(null)
const chunkFilter = ref('')
const chunksLoading = ref(false)

const showSearchDialog = ref(false)
const showPreviewDialog = ref(false)
const searching = ref(false)
const previewChunkData = ref<Chunk | null>(null)

const searchForm = ref({
  query: '',
  mode: 'hybrid',
  topK: 10
})

function typeIcon(type: string): string {
  const map: Record<string, string> = {
    canon: '📜',
    reference: '📚',
    material: '📦',
    custom: '📁'
  }
  return map[type] || '📄'
}

function typeLabel(type: string): string {
  const map: Record<string, string> = {
    canon: '官方正史',
    reference: '参考资料',
    material: '素材',
    custom: '自定义',
  }
  return map[type] || type
}

function typeTagType(type: string): 'primary' | 'success' | 'warning' | 'info' {
  const map: Record<string, 'primary' | 'success' | 'warning' | 'info'> = {
    canon: 'primary',
    reference: 'success',
    material: 'warning',
    custom: 'info'
  }
  return map[type] || 'info'
}

function statusLabel(status: string): string {
  const map: Record<string, string> = {
    PROCESSING: '处理中',
    READY: '就绪',
    ERROR: '失败',
    USED: '已引用',
    UNUSED: '未引用',
  }
  return map[status] || status
}

function statusTagType(status: string): 'warning' | 'success' | 'danger' | 'info' {
  const map: Record<string, 'warning' | 'success' | 'danger' | 'info'> = {
    PROCESSING: 'warning',
    READY: 'success',
    ERROR: 'danger',
    USED: 'success',
    UNUSED: 'info'
  }
  return map[status] || 'info'
}

function getDocIcon(type: string): string {
  const map: Record<string, string> = {
    pdf: '📄',
    epub: '📖',
    txt: '📝',
    md: '📋'
  }
  return map[type] || '📄'
}

const filteredChunks = computed(() => {
  if (!chunkFilter.value) return chunks.value
  return chunks.value.filter(c => c.status === chunkFilter.value)
})

async function loadLibrary() {
  try {
    const res = await getKnowledge(Number(libraryId.value))
    if (res.data) {
      library.value = res.data
    }
  } catch {
    ElMessage.error('加载知识库信息失败')
  }
}

async function loadDocuments() {
  try {
    const res = await listDocuments(libraryId.value)
    if (res.data) {
      documents.value = res.data
      if (documents.value.length > 0) {
        selectDocument(documents.value[0])
      }
    }
  } catch {
    documents.value = []
  }
}

async function selectDocument(doc: Document) {
  selectedDocId.value = doc.id
  await loadChunks(doc.id)
}

async function loadChunks(docId: number) {
  chunksLoading.value = true
  try {
    const res = await listDocumentChunks(Number(libraryId.value), docId)
    if (res.data) {
      chunks.value = res.data.map((c: any, i: number) => ({
        ...c,
        index: i + 1
      }))
    } else {
      chunks.value = []
    }
  } catch {
    chunks.value = []
  } finally {
    chunksLoading.value = false
  }
}

function previewChunk(chunk: Chunk) {
  previewChunkData.value = chunk
  showPreviewDialog.value = true
}

function previewDocument(doc: Document) {
  ElMessage.info(`文档预览功能开发中: ${doc.name}`)
}

async function reparseDocument(doc: Document) {
  try {
    await reprocessDocument(libraryId.value, doc.id)
    ElMessage.success('重新解析任务已提交')
    await loadDocuments()
  } catch {
    ElMessage.error('操作失败，请重试')
  }
}

async function deleteDocument(doc: Document) {
  try {
    await removeDocument(libraryId.value, doc.id)
    ElMessage.success('文档已删除')
    if (selectedDocId.value === doc.id) {
      selectedDocId.value = null
      chunks.value = []
    }
    await loadDocuments()
    await loadLibrary()
  } catch {
    ElMessage.error('删除失败，请重试')
  }
}

async function performSearch() {
  if (!searchForm.value.query.trim()) {
    ElMessage.warning('请输入检索内容')
    return
  }
  searching.value = true
  try {
    const res = await searchKnowledge({
      libraryId: libraryId.value,
      query: searchForm.value.query,
      mode: searchForm.value.mode as 'keyword' | 'semantic' | 'hybrid',
      topK: searchForm.value.topK
    })
    if (res.data) {
      chunks.value = res.data.map((c: any, i: number) => ({
        ...c,
        index: i + 1
      }))
      showSearchDialog.value = false
      ElMessage.success(`检索到 ${chunks.value.length} 条结果`)
    }
  } catch {
    ElMessage.error('检索失败')
  } finally {
    searching.value = false
  }
}

onMounted(() => {
  loadLibrary()
  loadDocuments()
})
</script>

<style lang="scss" scoped>
.page-header {
  display: flex;
  align-items: center;
  gap: $space-lg;
  padding: $space-lg $space-xl;
  background: $bg-white;
  border-bottom: 1px solid $border-color;

  h1 {
    font-size: $font-size-xl;
    font-weight: 600;
    flex: 1;
  }
}

.header-actions {
  display: flex;
  gap: $space-sm;
}

.page-content {
  padding: $space-xl;
}

.info-list {
  .info-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: $space-md;
    font-size: $font-size-sm;

    .label {
      color: $text-secondary;
      min-width: 70px;
    }

    .desc {
      color: $text-primary;
      flex: 1;
    }

    .tags {
      display: flex;
      flex-wrap: wrap;
      gap: 4px;

      .tag {
        margin-right: 0;
      }
    }
  }
}

.document-list {
  .document-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: $space-sm;
    padding: $space-sm;
    cursor: pointer;
    border-radius: $border-radius;
    transition: background 0.2s;

    &:hover {
      background: $bg-gray;
    }

    &.active {
      background: var(--el-color-primary-light-9);
    }

    .doc-info {
      display: flex;
      align-items: center;
      gap: $space-sm;
      flex: 1;
      overflow: hidden;

      .doc-icon {
        font-size: 16px;
        flex-shrink: 0;
      }

      .doc-name {
        font-size: $font-size-sm;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
    }

    .doc-actions {
      flex-shrink: 0;
    }
  }
}

.empty-text {
  color: $text-muted;
  font-size: $font-size-sm;
  text-align: center;
  padding: $space-md;
}

.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.chunks-content {
  min-height: 400px;
}

.loading-container {
  height: 400px;
}

.empty-state {
  height: 400px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: $text-muted;

  p {
    margin: $space-sm 0;
  }

  .tip {
    font-size: $font-size-xs;
  }
}

.chunks-list {
  display: flex;
  flex-direction: column;
  gap: $space-md;
}

.chunk-item {
  border: 1px solid $border-color;
  border-radius: $border-radius;
  padding: $space-md;

  .chunk-header {
    display: flex;
    align-items: center;
    gap: $space-sm;
    margin-bottom: $space-sm;

    .chunk-index {
      font-weight: 600;
      color: $text-primary;
    }
  }

  .chunk-content {
    font-size: $font-size-sm;
    color: $text-primary;
    line-height: 1.6;
    max-height: 100px;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .chunk-meta {
    display: flex;
    gap: $space-md;
    margin-top: $space-sm;
    font-size: $font-size-xs;
    color: $text-muted;
  }
}

.preview-content {
  .preview-meta {
    display: flex;
    align-items: center;
    gap: $space-sm;
    margin-bottom: $space-md;
    font-weight: 600;
  }

  .preview-text {
    font-size: $font-size-sm;
    line-height: 1.8;
    color: $text-primary;
    white-space: pre-wrap;
  }
}
</style>