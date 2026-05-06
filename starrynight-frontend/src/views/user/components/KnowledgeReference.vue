<template>
  <div class="knowledge-reference-panel">
    <div class="reference-header">
      <el-input
        v-model="searchKeyword"
        placeholder="搜索知识库..."
        size="small"
        clearable
        @input="handleSearch"
      >
        <template #prefix>
          <el-icon><Search /></el-icon>
        </template>
      </el-input>
    </div>

    <div v-if="loading" class="reference-loading">
      <el-icon class="is-loading"><Loading /></el-icon>
      <span>搜索中...</span>
    </div>

    <div v-else-if="results.length > 0" class="reference-results">
      <div
        v-for="item in results"
        :key="item.id"
        class="reference-item"
        @click="selectItem(item)"
      >
        <div class="item-icon">{{ getTypeIcon(item.type) }}</div>
        <div class="item-content">
          <div class="item-title">{{ item.content?.slice(0, 60) || '无内容' }}</div>
          <div class="item-meta">
            <el-tag size="small" type="info">{{ item.knowledgeId }}</el-tag>
          </div>
        </div>
      </div>
    </div>

    <div v-else-if="searchKeyword" class="reference-empty">
      <el-empty description="未找到相关知识" :image-size="60" />
    </div>

    <div v-else class="reference-hint">
      <p>输入关键词搜索知识库内容</p>
      <p class="hint-text">选择后将插入引用标记到编辑器</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Search, Loading } from '@element-plus/icons-vue'
import { searchAllChunks } from '@/api/knowledge'

interface KnowledgeChunk {
  id: number
  knowledgeId: number
  content: string
  metadata?: Record<string, any>
}

const emit = defineEmits<{
  (e: 'select', item: KnowledgeChunk): void
}>()

const searchKeyword = ref('')
const results = ref<KnowledgeChunk[]>([])
const loading = ref(false)
let searchTimer: ReturnType<typeof setTimeout> | null = null

function getTypeIcon(type?: string): string {
  const icons: Record<string, string> = {
    '设定': '📖',
    '世界观': '🌍',
    '规则': '⚙️',
    '背景': '📜',
    'custom': '📝'
  }
  return icons[type || 'custom'] || '📄'
}

function handleSearch() {
  if (searchTimer) {
    clearTimeout(searchTimer)
  }

  if (!searchKeyword.value.trim()) {
    results.value = []
    return
  }

  searchTimer = setTimeout(async () => {
    loading.value = true
    try {
      const res = await searchAllChunks(searchKeyword.value, { page: 1, size: 10 })
      if (res.data?.records) {
        results.value = res.data.records as KnowledgeChunk[]
      } else if (res.data) {
        results.value = Array.isArray(res.data) ? res.data as KnowledgeChunk[] : []
      }
    } catch (error) {
      console.error('Search failed:', error)
      results.value = []
    } finally {
      loading.value = false
    }
  }, 300)
}

function selectItem(item: KnowledgeChunk) {
  emit('select', item)
  searchKeyword.value = ''
  results.value = []
}
</script>

<style lang="scss" scoped>
.knowledge-reference-panel {
  background: white;
  border: 1px solid #e4e7ed;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
}

.reference-header {
  padding: 12px;
  border-bottom: 1px solid #f0f0f0;
}

.reference-loading,
.reference-empty,
.reference-hint {
  padding: 24px;
  text-align: center;
  color: #909399;
  font-size: 13px;
}

.reference-results {
  max-height: 300px;
  overflow-y: auto;
}

.reference-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px;
  cursor: pointer;
  transition: background 0.2s;

  &:hover {
    background: #f5f7fa;
  }

  .item-icon {
    font-size: 20px;
    flex-shrink: 0;
  }

  .item-content {
    flex: 1;
    min-width: 0;

    .item-title {
      font-size: 13px;
      color: #303133;
      line-height: 1.4;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .item-meta {
      margin-top: 4px;
    }
  }
}

.hint-text {
  font-size: 12px;
  color: #c0c4cc;
  margin-top: 4px;
}
</style>
