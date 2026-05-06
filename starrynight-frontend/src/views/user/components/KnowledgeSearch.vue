<template>
  <div class="knowledge-search">
    <div class="search-header">
      <el-input
        v-model="searchQuery"
        placeholder="搜索知识库内容..."
        size="large"
        :prefix-icon="Search"
        clearable
        @keyup.enter="handleSearch"
      >
        <template #append>
          <el-button :loading="searching" @click="handleSearch">
            <el-icon><Search /></el-icon>
          </el-button>
        </template>
      </el-input>
    </div>

    <div class="search-filters">
      <div class="filter-group">
        <span class="filter-label">搜索范围：</span>
        <el-checkbox-group v-model="selectedScopes">
          <el-checkbox label="knowledge">知识库</el-checkbox>
          <el-checkbox label="character">角色库</el-checkbox>
          <el-checkbox label="material">素材库</el-checkbox>
        </el-checkbox-group>
      </div>
      <div class="filter-group">
        <span class="filter-label">排序方式：</span>
        <el-radio-group v-model="sortBy" size="small">
          <el-radio-button label="relevance">相关性</el-radio-button>
          <el-radio-button label="recent">最近更新</el-radio-button>
        </el-radio-group>
      </div>
    </div>

    <div class="search-results" v-loading="searching">
      <div v-if="!hasSearched && recentSearches.length" class="recent-searches">
        <h5>最近搜索</h5>
        <div class="recent-list">
          <el-tag
            v-for="keyword in recentSearches"
            :key="keyword"
            class="recent-tag"
            @click="searchQuery = keyword; handleSearch()"
          >
            {{ keyword }}
          </el-tag>
        </div>
      </div>

      <div v-if="searching" class="search-loading">
        <el-icon class="is-loading"><Loading /></el-icon>
        <span>正在检索...</span>
      </div>

      <div v-else-if="hasSearched && results.length === 0" class="no-results">
        <el-empty description="未找到相关结果">
          <template #image>
            <el-icon :size="64"><Search /></el-icon>
          </template>
          <div class="search-suggestions">
            <p>建议：</p>
            <ul>
              <li>尝试使用更通用的关键词</li>
              <li>检查拼写是否正确</li>
              <li>使用同义词进行搜索</li>
            </ul>
          </div>
        </el-empty>
      </div>

      <div v-else-if="results.length" class="results-list">
        <div class="results-header">
          <span class="results-count">找到 {{ totalResults }} 个相关结果</span>
          <el-pagination
            v-model:current-page="currentPage"
            :page-size="pageSize"
            :total="totalResults"
            layout="prev, pager, next"
            small
          />
        </div>

        <div
          v-for="result in paginatedResults"
          :key="result.id"
          class="result-item"
          :class="result.type"
          @click="handleResultClick(result)"
        >
          <div class="result-header">
            <el-tag size="small" :type="getTypeTag(result.type)">
              {{ getTypeName(result.type) }}
            </el-tag>
            <span class="result-title" v-html="highlightKeyword(result.title)"></span>
          </div>
          <div class="result-snippet" v-html="highlightKeyword(result.snippet)"></div>
          <div class="result-meta">
            <span class="meta-item">
              <el-icon><User /></el-icon>
              {{ result.author || '未知' }}
            </span>
            <span class="meta-item">
              <el-icon><Clock /></el-icon>
              {{ formatTime(result.updateTime) }}
            </span>
            <span class="meta-item relevance">
              <el-icon><DataLine /></el-icon>
              相似度 {{ (result.relevance * 100).toFixed(0) }}%
            </span>
          </div>
          <div v-if="result.tags?.length" class="result-tags">
            <el-tag
              v-for="tag in result.tags"
              :key="tag"
              size="small"
              effect="plain"
            >
              {{ tag }}
            </el-tag>
          </div>
        </div>

        <el-pagination
          v-model:current-page="currentPage"
          :page-size="pageSize"
          :total="totalResults"
          layout="total, prev, pager, next"
          class="results-pagination"
        />
      </div>
    </div>

    <el-drawer
      v-model="showDetailDrawer"
      :title="selectedResult?.title"
      direction="rtl"
      size="600px"
    >
      <div v-if="selectedResult" class="detail-content">
        <div class="detail-header">
          <el-tag size="small" :type="getTypeTag(selectedResult.type)">
            {{ getTypeName(selectedResult.type) }}
          </el-tag>
          <el-button size="small" type="primary" @click="handleInsert(selectedResult)">
            <el-icon><Promotion /></el-icon>
            插入到编辑器
          </el-button>
        </div>

        <div class="detail-body">
          <div v-if="selectedResult.content" class="detail-section">
            <h5>详细内容</h5>
            <div class="detail-text" v-html="selectedResult.content"></div>
          </div>

          <div v-if="selectedResult.relatedItems?.length" class="detail-section">
            <h5>相关内容</h5>
            <div class="related-list">
              <div
                v-for="item in selectedResult.relatedItems"
                :key="item.id"
                class="related-item"
                @click="handleRelatedClick(item)"
              >
                <el-tag size="small">{{ getTypeName(item.type) }}</el-tag>
                <span class="related-title">{{ item.title }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </el-drawer>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive } from 'vue'
import { Search, Loading, User, Clock, DataLine, Promotion } from '@element-plus/icons-vue'
import type { KnowledgeItem } from '@/api/knowledge'
import type { NovelCharacter } from '@/api/character'
import type { MaterialItem } from '@/api/material'

interface SearchResult {
  id: number
  type: 'knowledge' | 'character' | 'material'
  title: string
  snippet: string
  content?: string
  author?: string
  updateTime?: string
  relevance: number
  tags?: string[]
  relatedItems?: Array<{
    id: number
    type: string
    title: string
  }>
}

const props = defineProps<{
  knowledgeList?: KnowledgeItem[]
  characterList?: NovelCharacter[]
  materialList?: MaterialItem[]
}>()

const emit = defineEmits<{
  'result-click': [result: SearchResult]
  'insert': [result: SearchResult]
}>()

const searchQuery = ref('')
const searching = ref(false)
const hasSearched = ref(false)
const selectedScopes = ref(['knowledge', 'character', 'material'])
const sortBy = ref<'relevance' | 'recent'>('relevance')
const currentPage = ref(1)
const pageSize = ref(10)
const showDetailDrawer = ref(false)
const selectedResult = ref<SearchResult | null>(null)

const recentSearches = ref<string[]>([])

const results = ref<SearchResult[]>([])

const totalResults = computed(() => results.value.length)

const paginatedResults = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value
  return results.value.slice(start, start + pageSize.value)
})

function getTypeTag(type: string): string {
  const map: Record<string, string> = {
    knowledge: 'primary',
    character: 'success',
    material: 'warning'
  }
  return map[type] || 'info'
}

function getTypeName(type: string): string {
  const map: Record<string, string> = {
    knowledge: '知识库',
    character: '角色',
    material: '素材'
  }
  return map[type] || type
}

function formatTime(time?: string): string {
  if (!time) return ''
  const date = new Date(time)
  return date.toLocaleDateString()
}

function highlightKeyword(text: string): string {
  if (!searchQuery.value) return text
  const regex = new RegExp(`(${searchQuery.value})`, 'gi')
  return text.replace(regex, '<mark>$1</mark>')
}

function performSearch(): SearchResult[] {
  if (!searchQuery.value.trim()) return []

  const query = searchQuery.value.toLowerCase()
  const searchResults: SearchResult[] = []

  if (selectedScopes.value.includes('knowledge') && props.knowledgeList) {
    props.knowledgeList.forEach(item => {
      if (
        item.title?.toLowerCase().includes(query) ||
        item.description?.toLowerCase().includes(query)
      ) {
        searchResults.push({
          id: item.id!,
          type: 'knowledge',
          title: item.title || '',
          snippet: item.description || '',
          author: '知识库',
          updateTime: item.updateTime,
          relevance: item.title?.toLowerCase().includes(query) ? 0.9 : 0.6,
          tags: item.type ? [item.type] : []
        })
      }
    })
  }

  if (selectedScopes.value.includes('character') && props.characterList) {
    props.characterList.forEach(char => {
      if (
        char.name?.toLowerCase().includes(query) ||
        char.identity?.toLowerCase().includes(query) ||
        char.background?.toLowerCase().includes(query)
      ) {
        searchResults.push({
          id: char.id!,
          type: 'character',
          title: char.name || '',
          snippet: char.identity || char.background || '',
          author: '角色库',
          updateTime: char.updateTime,
          relevance: char.name?.toLowerCase().includes(query) ? 0.9 : 0.6,
          tags: char.personality?.traits?.slice(0, 3)
        })
      }
    })
  }

  if (selectedScopes.value.includes('material') && props.materialList) {
    props.materialList.forEach(mat => {
      if (
        mat.title?.toLowerCase().includes(query) ||
        mat.description?.toLowerCase().includes(query) ||
        mat.content?.toLowerCase().includes(query)
      ) {
        searchResults.push({
          id: mat.id!,
          type: 'material',
          title: mat.title || '',
          snippet: mat.description || mat.content?.substring(0, 100) || '',
          author: '素材库',
          updateTime: mat.updateTime,
          relevance: mat.title?.toLowerCase().includes(query) ? 0.9 : 0.6,
          tags: mat.tags
        })
      }
    })
  }

  return sortBy.value === 'relevance'
    ? searchResults.sort((a, b) => b.relevance - a.relevance)
    : searchResults.sort((a, b) => new Date(b.updateTime || 0).getTime() - new Date(a.updateTime || 0).getTime())
}

async function handleSearch() {
  if (!searchQuery.value.trim()) return

  searching.value = true
  hasSearched.value = true

  if (!recentSearches.value.includes(searchQuery.value)) {
    recentSearches.value.unshift(searchQuery.value)
    if (recentSearches.value.length > 5) {
      recentSearches.value.pop()
    }
  }

  await new Promise(resolve => setTimeout(resolve, 500))

  results.value = performSearch()
  currentPage.value = 1
  searching.value = false
}

function handleResultClick(result: SearchResult) {
  selectedResult.value = result
  showDetailDrawer.value = true
  emit('result-click', result)
}

function handleRelatedClick(item: { id: number; type: string; title: string }) {
  const found = results.value.find(r => r.id === item.id && r.type === item.type)
  if (found) {
    selectedResult.value = found
  }
}

function handleInsert(result: SearchResult) {
  emit('insert', result)
  showDetailDrawer.value = false
}
</script>

<style lang="scss" scoped>
.knowledge-search {
  display: flex;
  flex-direction: column;
  height: 100%;
  background: var(--el-bg-color);
  border-radius: 8px;
}

.search-header {
  padding: 16px;
  border-bottom: 1px solid var(--el-border-color-light);
}

.search-filters {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  padding: 12px 16px;
  background: var(--el-fill-color-light);
  border-bottom: 1px solid var(--el-border-color-light);

  .filter-group {
    display: flex;
    align-items: center;
    gap: 8px;

    .filter-label {
      font-size: 13px;
      color: var(--el-text-color-secondary);
    }
  }
}

.search-results {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
}

.recent-searches {
  h5 {
    margin: 0 0 12px;
    font-size: 13px;
    color: var(--el-text-color-secondary);
  }

  .recent-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;

    .recent-tag {
      cursor: pointer;
    }
  }
}

.search-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 48px;
  color: var(--el-text-color-secondary);
}

.no-results {
  text-align: center;
  padding: 48px;

  .search-suggestions {
    text-align: left;
    max-width: 300px;
    margin: 16px auto 0;

    p {
      margin: 0 0 8px;
      font-weight: 500;
    }

    ul {
      margin: 0;
      padding-left: 20px;
      font-size: 13px;
      color: var(--el-text-color-secondary);

      li {
        margin-bottom: 4px;
      }
    }
  }
}

.results-list {
  .results-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;

    .results-count {
      font-size: 13px;
      color: var(--el-text-color-secondary);
    }
  }
}

.result-item {
  padding: 16px;
  border: 1px solid var(--el-border-color-light);
  border-radius: 8px;
  margin-bottom: 12px;
  cursor: pointer;
  transition: all 0.2s;

  &:hover {
    border-color: var(--el-color-primary);
    background: var(--el-color-primary-light-9);
  }

  .result-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;

    .result-title {
      font-weight: 600;
      font-size: 15px;

      :deep(mark) {
        background: var(--el-color-warning-light-3);
        color: inherit;
        padding: 0 2px;
        border-radius: 2px;
      }
    }
  }

  .result-snippet {
    font-size: 13px;
    color: var(--el-text-color-regular);
    line-height: 1.6;
    margin-bottom: 12px;

    :deep(mark) {
      background: var(--el-color-warning-light-3);
      color: inherit;
      padding: 0 2px;
      border-radius: 2px;
    }
  }

  .result-meta {
    display: flex;
    gap: 16px;
    font-size: 12px;
    color: var(--el-text-color-secondary);
    margin-bottom: 8px;

    .meta-item {
      display: flex;
      align-items: center;
      gap: 4px;

      &.relevance {
        color: var(--el-color-primary);
      }
    }
  }

  .result-tags {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
  }
}

.results-pagination {
  margin-top: 16px;
  justify-content: center;
}

.detail-content {
  .detail-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--el-border-color-light);
    margin-bottom: 16px;
  }

  .detail-body {
    .detail-section {
      margin-bottom: 24px;

      h5 {
        margin: 0 0 12px;
        font-size: 13px;
        color: var(--el-text-color-secondary);
      }

      .detail-text {
        font-size: 14px;
        line-height: 1.8;
        color: var(--el-text-color-regular);
      }

      .related-list {
        display: flex;
        flex-direction: column;
        gap: 8px;

        .related-item {
          display: flex;
          align-items: center;
          gap: 8px;
          padding: 8px 12px;
          background: var(--el-fill-color-light);
          border-radius: 4px;
          cursor: pointer;
          transition: background 0.2s;

          &:hover {
            background: var(--el-fill-color);
          }

          .related-title {
            font-size: 13px;
          }
        }
      }
    }
  }
}
</style>
