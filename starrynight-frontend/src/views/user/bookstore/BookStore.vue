<template>
  <div class="book-store">
    <div v-if="!loaded" class="store-loading">加载中…</div>
    <el-empty v-else-if="!homeEnabled" description="书城维护中，请稍后再试" class="store-empty" />
    <template v-else>
    <div class="store-header">
      <div class="header-content">
        <h1 class="logo">📚 {{ siteTitle }}</h1>
        <div class="search-bar">
          <el-input
            v-model="searchKeyword"
            placeholder="搜索书名、作者..."
            size="large"
            @keyup.enter="handleSearch"
          >
            <template #prefix>
              <el-icon><Search /></el-icon>
            </template>
          </el-input>
        </div>
        <div class="header-actions">
          <el-button @click="$router.push('/user/bookshelf')">我的书架</el-button>
          <el-button @click="$router.push('/user/bookstore/search')">高级搜索</el-button>
        </div>
      </div>
    </div>

    <div class="store-content">
      <div class="main-content">
        <el-carousel height="300px" :interval="5000" indicator-position="outside">
          <el-carousel-item v-for="(banner, idx) in banners" :key="banner.id ?? idx">
            <div
              class="banner-item"
              :style="{ backgroundImage: `url(${bannerImage(banner)})` }"
              @click="bannerBookId(banner) && goToDetail(bannerBookId(banner)!)"
            >
              <div class="banner-overlay">
                <h2>{{ banner.title }}</h2>
                <p>{{ banner.description }}</p>
              </div>
            </div>
          </el-carousel-item>
        </el-carousel>

        <div class="section">
          <div class="section-header">
            <h3>🔥 热门推荐</h3>
            <el-link type="primary" @click="$router.push('/user/bookstore/search?sort=hot')">查看更多</el-link>
          </div>
          <div class="book-grid">
            <div v-for="book in hotBooks" :key="String(book.id)" class="book-card" @click="goToDetail(Number(book.id))">
              <div class="book-cover">
                <img :src="book.cover" :alt="book.title" />
                <div class="book-tag" v-if="book.isVip">
                  <el-tag type="warning" size="small">VIP</el-tag>
                </div>
              </div>
              <div class="book-info">
                <h4 class="book-title">{{ book.title }}</h4>
                <p class="book-author">{{ book.author }}</p>
                <p class="book-desc">{{ book.description }}</p>
                <div class="book-meta">
                  <span class="meta-item">
                    <el-icon><View /></el-icon>
                    {{ formatViews(numViews(book.views)) }}
                  </span>
                  <span class="meta-item">
                    <el-icon><Star /></el-icon>
                    {{ book.rating }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="section">
          <div class="section-header">
            <h3>✨ 新书速递</h3>
            <el-link type="primary" @click="$router.push('/user/bookstore/search?sort=new')">查看更多</el-link>
          </div>
          <div class="book-grid">
            <div v-for="book in newBooks" :key="String(book.id)" class="book-card" @click="goToDetail(Number(book.id))">
              <div class="book-cover">
                <img :src="book.cover" :alt="book.title" />
                <div class="book-tag" v-if="book.isVip">
                  <el-tag type="warning" size="small">VIP</el-tag>
                </div>
              </div>
              <div class="book-info">
                <h4 class="book-title">{{ book.title }}</h4>
                <p class="book-author">{{ book.author }}</p>
                <p class="book-desc">{{ book.description }}</p>
                <div class="book-meta">
                  <span class="meta-item">
                    <el-icon><View /></el-icon>
                    {{ formatViews(numViews(book.views)) }}
                  </span>
                  <span class="meta-item">
                    <el-icon><Star /></el-icon>
                    {{ book.rating }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="section">
          <div class="section-header">
            <h3>🏆 畅销榜单</h3>
            <el-link type="primary" @click="$router.push('/user/bookstore/ranking')">查看榜单</el-link>
          </div>
          <div class="ranking-list">
            <div
              v-for="(book, index) in rankingBooks"
              :key="String(book.id)"
              class="ranking-item"
              @click="goToDetail(Number(book.id))"
            >
              <span class="ranking-num" :class="{ top: index < 3 }">{{ index + 1 }}</span>
              <img :src="book.cover" class="ranking-cover" />
              <div class="ranking-info">
                <h4>{{ book.title }}</h4>
                <p>{{ book.author }}</p>
                <span class="ranking-views">{{ formatViews(numViews(book.views)) }}阅读</span>
              </div>
            </div>
          </div>
        </div>

        <div class="section">
          <div class="section-header">
            <h3>📂 分类浏览</h3>
          </div>
          <div class="category-grid">
            <div v-for="category in categories" :key="category.id" class="category-card" @click="browseCategory(category.id)">
              <div class="category-icon">{{ category.icon }}</div>
              <span class="category-name">{{ category.name }}</span>
              <span class="category-count">{{ category.count }}本</span>
            </div>
          </div>
        </div>
      </div>

      <div class="sidebar">
        <el-card class="sidebar-card">
          <template #header>
            <span>📊 读者榜单</span>
          </template>
          <div class="reader-ranking">
            <div v-for="(reader, index) in topReaders" :key="index" class="reader-item">
              <span class="reader-rank" :class="{ top: index < 3 }">{{ index + 1 }}</span>
              <img :src="reader.avatar" class="reader-avatar" />
              <div class="reader-info">
                <span class="reader-name">{{ reader.name }}</span>
                <span class="reader-score">阅读 {{ reader.readCount }} 本</span>
              </div>
            </div>
          </div>
        </el-card>

        <el-card class="sidebar-card">
          <template #header>
            <span>📅 最新更新</span>
          </template>
          <div class="latest-updates">
            <div v-for="(update, uidx) in latestUpdates" :key="uidx" class="update-item">
              <span class="update-book">{{ update.bookTitle }}</span>
              <span class="update-chapter">第{{ update.chapter }}章</span>
              <span class="update-time">{{ update.time }}</span>
            </div>
          </div>
        </el-card>
      </div>
    </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Search, View, Star } from '@element-plus/icons-vue'
import { getBookstoreHomeCached } from '@/api/bookstore'
import { extractApiErrorMessage } from '@/utils/request'

const router = useRouter()
const searchKeyword = ref('')
const loaded = ref(false)
const homeEnabled = ref(true)
const siteTitle = ref('星夜书城')

const banners = ref<Record<string, unknown>[]>([])
const hotBooks = ref<Record<string, unknown>[]>([])
const newBooks = ref<Record<string, unknown>[]>([])
const rankingBooks = ref<Record<string, unknown>[]>([])
const categories = ref<{ id: number; name: string; icon: string; count: number }[]>([])
const topReaders = ref<Record<string, unknown>[]>([])
const latestUpdates = ref<Record<string, unknown>[]>([])

function bannerImage(b: Record<string, unknown>) {
  return String(b.imageUrl || b.image || '')
}

function bannerBookId(b: Record<string, unknown>) {
  const v = b.bookId
  return typeof v === 'number' ? v : v != null ? Number(v) : undefined
}

function numViews(v: unknown): number {
  if (typeof v === 'number') return v
  if (typeof v === 'string') return Number(v) || 0
  return 0
}

function formatViews(views: number): string {
  if (views >= 10000000) return (views / 10000000).toFixed(1) + '千万'
  if (views >= 10000) return (views / 10000).toFixed(1) + '万'
  return views.toString()
}

function handleSearch() {
  router.push(`/user/bookstore/search?keyword=${searchKeyword.value}`)
}

function goToDetail(bookId: number) {
  router.push(`/user/bookstore/detail/${bookId}`)
}

async function loadHome() {
  try {
    const data = await getBookstoreHomeCached()
    loaded.value = true
    homeEnabled.value = data.enabled
    siteTitle.value = data.siteTitle || '星夜书城'
    banners.value = data.banners || []
    hotBooks.value = data.hotBooks || []
    newBooks.value = data.newBooks || []
    rankingBooks.value = data.rankingBooks || []
    categories.value = data.categories || []
    topReaders.value = data.sidebarReaders || []
    latestUpdates.value = data.latestUpdates || []
  } catch (e) {
    loaded.value = true
    homeEnabled.value = false
    console.error(extractApiErrorMessage(e))
  }
}

function browseCategory(categoryId: number) {
  router.push(`/user/bookstore/search?category=${categoryId}`)
}

onMounted(loadHome)
</script>

<style lang="scss" scoped>
.book-store {
  min-height: 100vh;
  background: var(--el-bg-color-page);

  .store-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 20px 0;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);

    .header-content {
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      align-items: center;
      gap: 24px;

      .logo {
        font-size: 28px;
        color: white;
        margin: 0;
        white-space: nowrap;
      }

      .search-bar {
        flex: 1;
        max-width: 500px;
      }

      .header-actions {
        display: flex;
        gap: 12px;
      }
    }
  }

  .store-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 24px 0;
    display: flex;
    gap: 24px;

    .main-content {
      flex: 1;

      .banner-item {
        height: 300px;
        background-size: cover;
        background-position: center;
        cursor: pointer;
        position: relative;

        .banner-overlay {
          position: absolute;
          bottom: 0;
          left: 0;
          right: 0;
          padding: 24px;
          background: linear-gradient(transparent, rgba(0, 0, 0, 0.8));
          color: white;

          h2 {
            margin: 0 0 8px;
            font-size: 24px;
          }

          p {
            margin: 0;
            font-size: 14px;
          }
        }
      }

      .section {
        margin-top: 32px;

        .section-header {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-bottom: 16px;

          h3 {
            margin: 0;
            font-size: 18px;
          }
        }
      }

      .book-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;

        .book-card {
          cursor: pointer;
          transition: transform 0.2s;

          &:hover {
            transform: translateY(-4px);
          }

          .book-cover {
            position: relative;
            height: 160px;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 8px;

            img {
              width: 100%;
              height: 100%;
              object-fit: cover;
            }

            .book-tag {
              position: absolute;
              top: 8px;
              right: 8px;
            }
          }

          .book-info {
            .book-title {
              margin: 0 0 4px;
              font-size: 14px;
              font-weight: 600;
            }

            .book-author {
              margin: 0 0 4px;
              font-size: 12px;
              color: var(--el-text-color-secondary);
            }

            .book-desc {
              margin: 0 0 8px;
              font-size: 12px;
              color: var(--el-text-color-muted);
              overflow: hidden;
              text-overflow: ellipsis;
              white-space: nowrap;
            }

            .book-meta {
              display: flex;
              gap: 12px;
              font-size: 12px;
              color: var(--el-text-color-muted);

              .meta-item {
                display: flex;
                align-items: center;
                gap: 4px;
              }
            }
          }
        }
      }

      .ranking-list {
        .ranking-item {
          display: flex;
          align-items: center;
          gap: 12px;
          padding: 12px;
          cursor: pointer;
          border-bottom: 1px solid var(--el-border-color-lighter);

          &:last-child {
            border-bottom: none;
          }

          &:hover {
            background: var(--el-fill-color-light);
          }

          .ranking-num {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 600;
            color: var(--el-text-color-secondary);

            &.top {
              background: var(--el-color-danger);
              color: white;
              border-radius: 4px;
            }
          }

          .ranking-cover {
            width: 50px;
            height: 70px;
            object-fit: cover;
            border-radius: 4px;
          }

          .ranking-info {
            flex: 1;

            h4 {
              margin: 0 0 4px;
              font-size: 14px;
            }

            p {
              margin: 0 0 4px;
              font-size: 12px;
              color: var(--el-text-color-secondary);
            }

            .ranking-views {
              font-size: 11px;
              color: var(--el-text-color-muted);
            }
          }
        }
      }

      .category-grid {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 16px;

        .category-card {
          display: flex;
          flex-direction: column;
          align-items: center;
          padding: 20px;
          background: white;
          border-radius: 8px;
          cursor: pointer;
          transition: all 0.2s;

          &:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
          }

          .category-icon {
            font-size: 32px;
            margin-bottom: 8px;
          }

          .category-name {
            font-size: 14px;
            font-weight: 500;
          }

          .category-count {
            font-size: 12px;
            color: var(--el-text-color-muted);
          }
        }
      }
    }

    .sidebar {
      width: 280px;
      flex-shrink: 0;

      .sidebar-card {
        margin-bottom: 16px;

        .reader-ranking {
          .reader-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 0;
            border-bottom: 1px solid var(--el-border-color-lighter);

            &:last-child {
              border-bottom: none;
            }

            .reader-rank {
              width: 20px;
              height: 20px;
              display: flex;
              align-items: center;
              justify-content: center;
              font-size: 12px;
              font-weight: 600;
              color: var(--el-text-color-secondary);

              &.top {
                background: var(--el-color-warning);
                color: white;
                border-radius: 50%;
              }
            }

            .reader-avatar {
              width: 40px;
              height: 40px;
              border-radius: 50%;
            }

            .reader-info {
              flex: 1;
              display: flex;
              flex-direction: column;

              .reader-name {
                font-size: 13px;
                font-weight: 500;
              }

              .reader-score {
                font-size: 11px;
                color: var(--el-text-color-muted);
              }
            }
          }
        }

        .latest-updates {
          .update-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid var(--el-border-color-lighter);
            font-size: 12px;

            &:last-child {
              border-bottom: none;
            }

            .update-book {
              font-weight: 500;
              max-width: 80px;
              overflow: hidden;
              text-overflow: ellipsis;
              white-space: nowrap;
            }

            .update-chapter {
              color: var(--el-text-color-secondary);
            }

            .update-time {
              color: var(--el-text-color-muted);
            }
          }
        }
      }
    }
  }
}
</style>
