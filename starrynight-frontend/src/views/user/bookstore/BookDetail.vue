<template>
  <div class="book-detail">
    <div class="detail-header">
      <div class="header-content">
        <div class="book-cover">
          <img :src="detailCoverSrc" :alt="book.title" />
          <div class="cover-overlay">
            <el-tag v-if="book.isVip" type="warning">VIP</el-tag>
          </div>
        </div>
        <div class="book-info">
          <h1 class="book-title">{{ book.title }}</h1>
          <p class="book-author">作者: {{ book.author }}</p>
          <div class="book-meta">
            <span class="meta-item">
              <el-icon><View /></el-icon>
              {{ formatViews(book.views) }}阅读
            </span>
            <span class="meta-item">
              <el-icon><Star /></el-icon>
              {{ book.rating }}分
            </span>
            <span class="meta-item">
              <el-icon><Document /></el-icon>
              {{ book.wordCount }}万字
            </span>
            <span class="meta-item">
              <el-tag size="small">{{ book.category }}</el-tag>
            </span>
          </div>
          <div class="book-tags">
            <el-tag v-for="tag in book.tags" :key="tag" size="small" type="info">{{ tag }}</el-tag>
          </div>
          <div class="book-actions">
            <el-button type="primary" size="large" @click="startReading">开始阅读</el-button>
            <el-button size="large" @click="addToBookshelf">
              <el-icon><Collection /></el-icon>
              加入书架
            </el-button>
            <el-button size="large" @click="shareBook">
              <el-icon><Share /></el-icon>
            </el-button>
          </div>
        </div>
      </div>
    </div>

    <div class="detail-content">
      <div class="main-content">
        <el-card class="content-card">
          <template #header>
            <span>作品简介</span>
          </template>
          <p class="book-description">{{ book.description }}</p>
        </el-card>

        <el-card class="content-card">
          <template #header>
            <span>目录 ({{ chapters.length }}章)</span>
          </template>
          <div class="chapter-list">
            <div
              v-for="chapter in chapters"
              :key="chapter.id"
              class="chapter-item"
              @click="readChapter(chapter.chapterNo)"
            >
              <span class="chapter-title">{{ chapter.title }}</span>
              <span class="chapter-wordcount">{{ chapter.wordCount }}字</span>
            </div>
          </div>
        </el-card>

        <el-card v-if="reviews.length" class="content-card">
          <template #header>
            <span>热门书评</span>
          </template>
          <div class="reviews">
            <div v-for="review in reviews" :key="review.id" class="review-item">
              <div class="review-header">
                <img :src="review.avatar" class="review-avatar" />
                <div class="review-info">
                  <span class="reviewer-name">{{ review.name }}</span>
                  <el-rate v-model="review.rating" disabled size="small" />
                </div>
                <span class="review-time">{{ review.time }}</span>
              </div>
              <p class="review-content">{{ review.content }}</p>
            </div>
          </div>
        </el-card>
      </div>

      <div class="sidebar">
        <el-card v-if="authorBooks.length" class="sidebar-card">
          <template #header>
            <span>作者其他作品</span>
          </template>
          <div class="author-books">
            <div v-for="ab in authorBooks" :key="ab.id" class="author-book-item" @click="goToDetail(ab.id)">
              <img :src="ab.cover" class="author-book-cover" />
              <div class="author-book-info">
                <span class="author-book-title">{{ ab.title }}</span>
                <span class="author-book-views">{{ ab.views }}阅读</span>
              </div>
            </div>
          </div>
        </el-card>

        <el-card v-if="similarBooks.length" class="sidebar-card">
          <template #header>
            <span>相似推荐</span>
          </template>
          <div class="similar-books">
            <div v-for="sb in similarBooks" :key="sb.id" class="similar-book-item" @click="goToDetail(sb.id)">
              <img :src="sb.cover" class="similar-book-cover" />
              <div class="similar-book-info">
                <span class="similar-book-title">{{ sb.title }}</span>
                <span class="similar-book-author">{{ sb.author }}</span>
              </div>
            </div>
          </div>
        </el-card>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { View, Star, Document, Collection, Share } from '@element-plus/icons-vue'
import {
  getBookstoreBookCached,
  getBookstoreChaptersCached,
  type BookstoreChapterTocItem
} from '@/api/bookstore'
import {
  fetchLiveBookCached,
  writeLiveTocToSession,
  clearLiveTocSession
} from '@/utils/book-source-utils'
import { extractApiErrorMessage } from '@/utils/request'
import { svgDataPlaceholder } from '@/utils/placeholderImage'

const route = useRoute()
const router = useRouter()
const bookId = parseInt(route.params.id as string)

const book = ref({
  id: bookId,
  title: '',
  author: '',
  cover: '',
  description: '',
  views: 0,
  rating: 0,
  wordCount: 0,
  category: '',
  tags: [] as string[],
  isVip: false
})

const chapters = ref<BookstoreChapterTocItem[]>([])
/** 书评 / 同作者 / 相似推荐待对接接口，不展示假数据 */
const reviews = ref<{ id: number; name: string; avatar: string; rating: number; time: string; content: string }[]>([])
const authorBooks = ref<{ id: number; title: string; cover: string; views: number }[]>([])
const similarBooks = ref<{ id: number; title: string; author: string; cover: string }[]>([])

const detailCoverSrc = computed(() => {
  const c = (book.value.cover || '').trim()
  if (c) return c
  const t = (book.value.title || '书').slice(0, 8)
  return svgDataPlaceholder(180, 240, '4f46e5', 'ffffff', t)
})

function formatViews(views: number): string {
  if (views >= 10000000) return (views / 10000000).toFixed(1) + '千万'
  if (views >= 10000) return (views / 10000).toFixed(1) + '万'
  return views.toString()
}

function startReading() {
  const first = chapters.value[0]
  if (!first) {
    ElMessage.warning('本书暂无章节：请确认已配置书源 URL，并按文档走 /api/bookstore/book。')
    return
  }
  router.push(`/bookstore/reader/${bookId}/${first.chapterNo}`)
}

function readChapter(chapterNo: number) {
  router.push(`/bookstore/reader/${bookId}/${chapterNo}`)
}

function addToBookshelf() {
  console.log('add to bookshelf')
}

function shareBook() {
  console.log('share')
}

function goToDetail(id: number) {
  router.push(`/bookstore/detail/${id}`)
}

onMounted(async () => {
  try {
    const d = await getBookstoreBookCached(bookId)
    const wan = d.wordCount != null ? Math.round((d.wordCount / 10000) * 10) / 10 : 0
    book.value = {
      id: d.id,
      title: d.title || '',
      author: d.author || '',
      cover: d.cover || '',
      description: d.description || '',
      views: Number(d.views) || 0,
      rating: Number(d.rating) || 0,
      wordCount: wan,
      category: d.category || '',
      tags: d.tags || [],
      isVip: Boolean(d.isVip)
    }

    if (d.liveParseAvailable) {
      try {
        const live = await fetchLiveBookCached(bookId)
        if (live.chapters?.length) {
          chapters.value = live.chapters.map((c, i) => ({
            id: i + 1,
            chapterNo: i + 1,
            title: c.title,
            wordCount: 0
          }))
          writeLiveTocToSession(bookId, {
            sourceId: bookId,
            chapters: live.chapters.map((c, i) => ({
              chapterNo: i + 1,
              title: c.title,
              url: c.url
            }))
          })
          return
        }
      } catch {
        /* 回退库内章节 */
      }
    }

    clearLiveTocSession(bookId)
    const toc = await getBookstoreChaptersCached(bookId)
    chapters.value = toc || []
  } catch (e) {
    ElMessage.error(extractApiErrorMessage(e))
  }
})
</script>

<style lang="scss" scoped>
.book-detail {
  min-height: 100vh;
  background: var(--el-bg-color-page);

  .detail-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 40px 0;

    .header-content {
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      gap: 32px;
      padding: 0 24px;

      .book-cover {
        width: 180px;
        height: 240px;
        border-radius: 8px;
        overflow: hidden;
        position: relative;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);

        img {
          width: 100%;
          height: 100%;
          object-fit: cover;
        }

        .cover-overlay {
          position: absolute;
          top: 8px;
          right: 8px;
        }
      }

      .book-info {
        color: white;

        .book-title {
          font-size: 32px;
          margin: 0 0 12px;
        }

        .book-author {
          font-size: 16px;
          margin: 0 0 16px;
          opacity: 0.9;
        }

        .book-meta {
          display: flex;
          gap: 20px;
          margin-bottom: 12px;

          .meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
          }
        }

        .book-tags {
          display: flex;
          gap: 8px;
          margin-bottom: 24px;
        }

        .book-actions {
          display: flex;
          gap: 12px;
        }
      }
    }
  }

  .detail-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 24px;
    display: flex;
    gap: 24px;

    .main-content {
      flex: 1;

      .content-card {
        margin-bottom: 16px;

        .book-description {
          line-height: 1.8;
          color: var(--el-text-color-regular);
        }

        .chapter-list {
          .chapter-item {
            display: flex;
            justify-content: space-between;
            padding: 12px;
            cursor: pointer;
            border-bottom: 1px solid var(--el-border-color-lighter);

            &:last-child {
              border-bottom: none;
            }

            &:hover {
              background: var(--el-fill-color-light);
            }

            .chapter-title {
              font-size: 14px;
            }

            .chapter-wordcount {
              font-size: 12px;
              color: var(--el-text-color-muted);
            }
          }
        }

        .reviews {
          .review-item {
            padding: 16px 0;
            border-bottom: 1px solid var(--el-border-color-lighter);

            &:last-child {
              border-bottom: none;
            }

            .review-header {
              display: flex;
              align-items: center;
              gap: 12px;
              margin-bottom: 8px;

              .review-avatar {
                width: 40px;
                height: 40px;
                border-radius: 50%;
              }

              .review-info {
                flex: 1;
                display: flex;
                flex-direction: column;
                gap: 4px;

                .reviewer-name {
                  font-weight: 500;
                }
              }

              .review-time {
                font-size: 12px;
                color: var(--el-text-color-muted);
              }
            }

            .review-content {
              margin: 0;
              line-height: 1.6;
            }
          }
        }
      }
    }

    .sidebar {
      width: 280px;
      flex-shrink: 0;

      .sidebar-card {
        margin-bottom: 16px;

        .author-books, .similar-books {
          .author-book-item, .similar-book-item {
            display: flex;
            gap: 12px;
            padding: 8px 0;
            cursor: pointer;

            &:hover {
              opacity: 0.8;
            }

            .author-book-cover, .similar-book-cover {
              width: 50px;
              height: 70px;
              object-fit: cover;
              border-radius: 4px;
            }

            .author-book-info, .similar-book-info {
              flex: 1;
              display: flex;
              flex-direction: column;
              justify-content: center;

              .author-book-title, .similar-book-title {
                font-size: 13px;
                font-weight: 500;
                margin-bottom: 4px;
              }

              .author-book-views, .similar-book-author {
                font-size: 11px;
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
