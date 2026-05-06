<template>
  <div class="book-search page-container">
    <div class="search-header">
      <div class="search-input-wrapper">
        <el-input
          v-model="searchKeyword"
          placeholder="搜索书名、作者..."
          size="large"
          @keyup.enter="handleSearch"
        >
          <template #prefix>
            <el-icon><Search /></el-icon>
          </template>
          <template #append>
            <el-button :icon="Search" @click="handleSearch">搜索</el-button>
          </template>
        </el-input>
      </div>
    </div>

    <div class="search-content">
      <div class="filter-sidebar">
        <el-card>
          <template #header>
            <span>筛选条件</span>
          </template>

          <el-form label-position="top">
            <el-form-item label="分类">
              <el-checkbox-group v-model="selectedCategories">
                <el-checkbox v-for="cat in categories" :key="cat.id" :value="cat.id">
                  {{ cat.name }} ({{ cat.count }})
                </el-checkbox>
              </el-checkbox-group>
            </el-form-item>

            <el-form-item label="标签">
              <el-checkbox-group v-model="selectedTags">
                <el-checkbox v-for="tag in tags" :key="tag" :value="tag">
                  {{ tag }}
                </el-checkbox>
              </el-checkbox-group>
            </el-form-item>

            <el-form-item label="字数范围">
              <el-radio-group v-model="wordCountRange">
                <el-radio value="">不限</el-radio>
                <el-radio value="0-50">50万以下</el-radio>
                <el-radio value="50-100">50-100万</el-radio>
                <el-radio value="100-200">100-200万</el-radio>
                <el-radio value="200+">200万以上</el-radio>
              </el-radio-group>
            </el-form-item>

            <el-form-item label="会员类型">
              <el-radio-group v-model="membershipFilter">
                <el-radio value="">不限</el-radio>
                <el-radio value="vip">VIP免费</el-radio>
                <el-radio value="free">免费作品</el-radio>
              </el-radio-group>
            </el-form-item>

            <el-form-item label="状态">
              <el-radio-group v-model="statusFilter">
                <el-radio value="">不限</el-radio>
                <el-radio value="serial">连载中</el-radio>
                <el-radio value="finished">已完结</el-radio>
              </el-radio-group>
            </el-form-item>

            <el-form-item>
              <el-button type="primary" @click="applyFilters">应用筛选</el-button>
              <el-button @click="resetFilters">重置</el-button>
            </el-form-item>
          </el-form>
        </el-card>
      </div>

      <div class="results-main">
        <div v-if="resultsLoading" class="results-loading">加载中…</div>
        <div class="results-header">
          <div class="results-count">
            找到 <strong>{{ totalResults }}</strong> 部作品
          </div>
          <div class="results-sort">
            <span>排序:</span>
            <el-select v-model="sortBy" @change="handleSearch" style="width: 140px">
              <el-option label="综合排序" value="relevance" />
              <el-option label="热度最高" value="hot" />
              <el-option label="最新更新" value="update" />
              <el-option label="评分最高" value="rating" />
              <el-option label="字数最多" value="wordcount" />
            </el-select>
          </div>
        </div>

        <div v-if="!resultsLoading && searchResults.length === 0" class="empty-state">
          <el-icon :size="64"><Search /></el-icon>
          <p>未找到相关作品</p>
        </div>

        <div v-else-if="!resultsLoading" class="results-list">
          <div v-for="book in searchResults" :key="book.id" class="result-item" @click="goToDetail(book.id)">
            <img :src="book.cover || defaultCover" class="result-cover" alt="" />
            <div class="result-info">
              <h3 class="result-title">{{ book.title }}</h3>
              <p class="result-author">作者: {{ book.author || '—' }}</p>
              <div class="result-meta">
                <span v-if="book.category" class="meta-item">
                  <el-tag size="small">{{ book.category }}</el-tag>
                </span>
                <span class="meta-item">{{ formatWordCount(book.wordCount) }}</span>
                <span v-if="book.status" class="meta-item">{{ book.status }}</span>
              </div>
              <p class="result-desc">{{ book.description || '暂无简介' }}</p>
              <div v-if="book.tags?.length" class="result-tags">
                <el-tag v-for="tag in book.tags" :key="tag" size="small" type="info">{{ tag }}</el-tag>
              </div>
            </div>
            <div class="result-stats">
              <div class="stat-item">
                <span class="stat-value">{{ formatViews(book.views) }}</span>
                <span class="stat-label">阅读</span>
              </div>
              <div class="stat-item">
                <span class="stat-value">{{ book.rating ?? '—' }}</span>
                <span class="stat-label">评分</span>
              </div>
              <div class="stat-item">
                <span class="stat-value">{{ book.chapterCount ?? 0 }}</span>
                <span class="stat-label">章节</span>
              </div>
            </div>
          </div>
        </div>

        <el-pagination
          v-if="totalResults > 0"
          v-model:current-page="currentPage"
          v-model:page-size="pageSize"
          :total="totalResults"
          layout="prev, pager, next"
          @current-change="handlePageChange"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Search } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import { getBookstoreHomeCached, searchBookstoreBooks, type BookstoreSearchBook } from '@/api/bookstore'
import { extractApiErrorMessage } from '@/utils/request'

const route = useRoute()
const router = useRouter()

const defaultCover =
  'data:image/svg+xml,' +
  encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" width="120" height="160" viewBox="0 0 120 160"><rect fill="#1e293b" width="120" height="160"/><text x="60" y="85" text-anchor="middle" fill="#94a3b8" font-size="12" font-family="sans-serif">无封面</text></svg>'
  )

const searchKeyword = ref('')
const selectedCategories = ref<number[]>([])
const selectedTags = ref<string[]>([])
const wordCountRange = ref('')
const membershipFilter = ref('')
const statusFilter = ref('')
const sortBy = ref('relevance')
const currentPage = ref(1)
const pageSize = ref(20)
const totalResults = ref(0)
const resultsLoading = ref(false)

const categories = ref<{ id: number; name: string; icon: string; count: number }[]>([])

const tags = ref(['热血', '修炼', '升级', '穿越', '系统', '搞笑', '智斗', '治愈'])

const searchResults = ref<BookstoreSearchBook[]>([])

function formatViews(v: unknown): string {
  if (v == null) return '0'
  const n = typeof v === 'number' ? v : Number(v)
  if (!Number.isFinite(n)) return '0'
  if (n >= 100000000) return (n / 100000000).toFixed(1) + '亿'
  if (n >= 10000) return (n / 10000).toFixed(1) + '万'
  return String(Math.round(n))
}

function formatWordCount(w: unknown): string {
  if (w == null || w === '') return '—'
  const n = typeof w === 'number' ? w : Number(w)
  if (!Number.isFinite(n)) return '—'
  return `${n}万字`
}

function handleSearch() {
  currentPage.value = 1
  void loadResults()
}

async function loadResults() {
  resultsLoading.value = true
  try {
    const completionStatus =
      statusFilter.value === 'serial' || statusFilter.value === 'finished' ? statusFilter.value : undefined
    const data = await searchBookstoreBooks({
      keyword: searchKeyword.value.trim() || undefined,
      categoryIds: selectedCategories.value.length ? selectedCategories.value : undefined,
      sort: sortBy.value,
      membership: membershipFilter.value || undefined,
      wordCountRange: wordCountRange.value || undefined,
      tags: selectedTags.value.length ? selectedTags.value : undefined,
      completionStatus,
      page: currentPage.value,
      size: pageSize.value
    })
    searchResults.value = data.records || []
    totalResults.value = Number(data.total) || 0
  } catch (e) {
    searchResults.value = []
    totalResults.value = 0
    ElMessage.error(extractApiErrorMessage(e))
  } finally {
    resultsLoading.value = false
  }
}

function applyFilters() {
  handleSearch()
}

function resetFilters() {
  selectedCategories.value = []
  selectedTags.value = []
  wordCountRange.value = ''
  membershipFilter.value = ''
  statusFilter.value = ''
  handleSearch()
}

function handlePageChange() {
  void loadResults()
}

function goToDetail(bookId: number) {
  router.push(`/bookstore/detail/${bookId}`)
}

function syncFromRoute() {
  const keyword = route.query.keyword
  const category = route.query.category
  const sort = route.query.sort

  if (typeof keyword === 'string' && keyword) searchKeyword.value = keyword
  if (typeof category === 'string' && category) {
    const id = parseInt(category, 10)
    if (!Number.isNaN(id)) selectedCategories.value = [id]
  }
  if (typeof sort === 'string' && sort) sortBy.value = sort
}

async function loadCategories() {
  try {
    const home = await getBookstoreHomeCached()
    categories.value = home.categories || []
  } catch {
    categories.value = []
  }
}

onMounted(async () => {
  syncFromRoute()
  await loadCategories()
  await loadResults()
})

watch(
  () => route.query,
  async () => {
    syncFromRoute()
    await loadResults()
  },
  { deep: true }
)
</script>

<style lang="scss" scoped>
.book-search {
  .search-header {
    background: var(--el-fill-color-light);
    padding: 24px;
    margin-bottom: 24px;

    .search-input-wrapper {
      max-width: 800px;
      margin: 0 auto;
    }
  }

  .search-content {
    display: flex;
    gap: 24px;
    max-width: 1200px;
    margin: 0 auto;

    .filter-sidebar {
      width: 240px;
      flex-shrink: 0;

      :deep(.el-form-item__label) {
        font-weight: 500;
      }

      :deep(.el-checkbox) {
        display: block;
        margin-bottom: 8px;
      }

      :deep(.el-radio) {
        display: block;
        margin-bottom: 8px;
      }
    }

    .results-main {
      flex: 1;

      .results-loading {
        padding: 24px;
        text-align: center;
        color: var(--el-text-color-secondary);
      }

      .results-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--el-border-color-lighter);

        .results-count {
          color: var(--el-text-color-secondary);
        }

        .results-sort {
          display: flex;
          align-items: center;
          gap: 8px;
          color: var(--el-text-color-secondary);
        }
      }

      .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 60px;
        color: var(--el-text-color-secondary);
      }

      .results-list {
        .result-item {
          display: flex;
          gap: 16px;
          padding: 20px;
          border-bottom: 1px solid var(--el-border-color-lighter);
          cursor: pointer;
          transition: background 0.2s;

          &:hover {
            background: var(--el-fill-color-light);
          }

          .result-cover {
            width: 100px;
            height: 133px;
            object-fit: cover;
            border-radius: 6px;
            flex-shrink: 0;
          }

          .result-info {
            flex: 1;

            .result-title {
              margin: 0 0 8px;
              font-size: 18px;
            }

            .result-author {
              margin: 0 0 12px;
              font-size: 14px;
              color: var(--el-text-color-secondary);
            }

            .result-meta {
              display: flex;
              gap: 16px;
              margin-bottom: 12px;

              .meta-item {
                font-size: 13px;
                color: var(--el-text-color-muted);
              }
            }

            .result-desc {
              margin: 0 0 12px;
              font-size: 13px;
              color: var(--el-text-color-regular);
              line-height: 1.6;
              display: -webkit-box;
              -webkit-line-clamp: 2;
              -webkit-box-orient: vertical;
              overflow: hidden;
            }

            .result-tags {
              display: flex;
              gap: 8px;
            }
          }

          .result-stats {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
            width: 80px;
            flex-shrink: 0;

            .stat-item {
              display: flex;
              flex-direction: column;
              align-items: center;

              .stat-value {
                font-size: 18px;
                font-weight: 600;
                color: var(--el-text-color);
              }

              .stat-label {
                font-size: 12px;
                color: var(--el-text-color-muted);
              }
            }
          }
        }
      }
    }
  }
}
</style>
