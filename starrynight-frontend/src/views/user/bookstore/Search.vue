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

        <div v-if="searchResults.length === 0" class="empty-state">
          <el-icon :size="64"><Search /></el-icon>
          <p>未找到相关作品</p>
        </div>

        <div v-else class="results-list">
          <div v-for="book in searchResults" :key="book.id" class="result-item" @click="goToDetail(book.id)">
            <img :src="book.cover" class="result-cover" />
            <div class="result-info">
              <h3 class="result-title">{{ book.title }}</h3>
              <p class="result-author">作者: {{ book.author }}</p>
              <div class="result-meta">
                <span class="meta-item">
                  <el-tag size="small">{{ book.category }}</el-tag>
                </span>
                <span class="meta-item">{{ book.wordCount }}万字</span>
                <span class="meta-item">{{ book.status }}</span>
              </div>
              <p class="result-desc">{{ book.description }}</p>
              <div class="result-tags">
                <el-tag v-for="tag in book.tags" :key="tag" size="small" type="info">{{ tag }}</el-tag>
              </div>
            </div>
            <div class="result-stats">
              <div class="stat-item">
                <span class="stat-value">{{ book.views }}</span>
                <span class="stat-label">阅读</span>
              </div>
              <div class="stat-item">
                <span class="stat-value">{{ book.rating }}</span>
                <span class="stat-label">评分</span>
              </div>
              <div class="stat-item">
                <span class="stat-value">{{ book.chapterCount }}</span>
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
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Search } from '@element-plus/icons-vue'

const route = useRoute()
const router = useRouter()

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

const categories = ref([
  { id: 1, name: '玄幻', count: 1234 },
  { id: 2, name: '仙侠', count: 890 },
  { id: 3, name: '都市', count: 1567 },
  { id: 4, name: '科幻', count: 432 },
  { id: 5, name: '悬疑', count: 321 },
  { id: 6, name: '武侠', count: 567 }
])

const tags = ref(['热血', '修炼', '升级', '穿越', '系统', '搞笑', '智斗', '治愈'])

const searchResults = ref<any[]>([])

function handleSearch() {
  currentPage.value = 1
  loadResults()
}

function loadResults() {
  searchResults.value = [
    {
      id: 1,
      title: '仙逆',
      author: '耳根',
      cover: 'https://via.placeholder.com/120x160/409EFF/ffffff?text=仙逆',
      category: '仙侠',
      wordCount: 598,
      status: '已完结',
      views: '1234万',
      rating: 4.9,
      chapterCount: 1256,
      description: '顺为凡，逆则仙！一个资质平庸的少年踏入修真之路，他资质平庸，却凭借着坚韧不拔的心性，一步步向着巅峰迈进。',
      tags: ['热血', '修炼', '成长']
    },
    {
      id: 2,
      title: '斗破苍穹',
      author: '天蚕土豆',
      cover: 'https://via.placeholder.com/120x160/67C23A/ffffff?text=斗破',
      category: '玄幻',
      wordCount: 532,
      status: '已完结',
      views: '2345万',
      rating: 4.8,
      chapterCount: 1890,
      description: '这里是属于斗气的世界，没有花俏艳丽的魔法，有的，仅仅是繁衍到巅峰的斗气！莫欺少年穷！',
      tags: ['热血', '修炼', '逆袭']
    },
    {
      id: 3,
      title: '完美世界',
      author: '辰东',
      cover: 'https://via.placeholder.com/120x160/E6A23C/ffffff?text=完美世界',
      category: '玄幻',
      wordCount: 678,
      status: '已完结',
      views: '1987万',
      rating: 4.7,
      chapterCount: 2156,
      description: '一粒尘可填海，一根草斩尽日月星辰，弹指间天翻地覆。独断万古的存在！',
      tags: ['热血', '修炼', '史诗']
    }
  ]
  totalResults.value = 3
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
  loadResults()
}

function goToDetail(bookId: number) {
  router.push(`/user/bookstore/detail/${bookId}`)
}

onMounted(() => {
  const keyword = route.query.keyword as string
  const category = route.query.category as string
  const sort = route.query.sort as string

  if (keyword) searchKeyword.value = keyword
  if (category) selectedCategories.value = [parseInt(category)]
  if (sort) sortBy.value = sort

  loadResults()
})
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
