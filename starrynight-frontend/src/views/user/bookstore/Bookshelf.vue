<template>
  <div class="bookshelf page-container">
    <div class="page-header">
      <h1>📚 我的书架</h1>
      <div class="header-actions">
        <el-radio-group v-model="viewMode" size="small">
          <el-radio-button value="grid">网格</el-radio-button>
          <el-radio-button value="list">列表</el-radio-button>
        </el-radio-group>
      </div>
    </div>

    <div class="shelf-tabs">
      <el-tabs v-model="activeTab">
        <el-tab-pane label="正在追读" name="reading">
          <div v-if="readingBooks.length === 0" class="empty-state">
            <el-icon :size="64"><Reading /></el-icon>
            <p>暂无追读书籍</p>
            <el-button type="primary" @click="$router.push('/bookstore')">去星夜书库看看</el-button>
          </div>
          <div v-else-if="viewMode === 'grid'" class="book-grid">
            <div v-for="book in readingBooks" :key="book.id" class="book-card" @click="goToDetail(book)">
              <div class="book-cover">
                <img :src="book.cover" :alt="book.title" />
                <div class="book-progress">
                  <el-progress :percentage="book.progress" :show-text="false" color="#67C23A" />
                </div>
              </div>
              <div class="book-info">
                <h4 class="book-title">{{ book.title }}</h4>
                <p class="book-chapter">看到第{{ book.currentChapter }}章</p>
                <div class="book-actions">
                  <el-button size="small" type="primary" @click.stop="continueReading(book)">继续阅读</el-button>
                  <el-button size="small" @click.stop="removeFromShelf(book)">移出</el-button>
                </div>
              </div>
            </div>
          </div>
          <el-table v-else :data="readingBooks" @row-click="continueReading">
            <el-table-column prop="title" label="书名" min-width="200" />
            <el-table-column prop="author" label="作者" width="120" />
            <el-table-column label="进度" width="200">
              <template #default="{ row }">
                <el-progress :percentage="row.progress" />
              </template>
            </el-table-column>
            <el-table-column label="看到" width="100">
              <template #default="{ row }">第{{ row.currentChapter }}章</template>
            </el-table-column>
            <el-table-column label="操作" width="160" fixed="right">
              <template #default="{ row }">
                <el-button type="primary" size="small" @click.stop="continueReading(row)">阅读</el-button>
                <el-button size="small" @click.stop="removeFromShelf(row)">移出</el-button>
              </template>
            </el-table-column>
          </el-table>
        </el-tab-pane>

        <el-tab-pane label="已完结" name="finished">
          <div v-if="finishedBooks.length === 0" class="empty-state">
            <el-icon :size="64"><Finished /></el-icon>
            <p>暂无已完结书籍</p>
          </div>
          <div v-else-if="viewMode === 'grid'" class="book-grid">
            <div v-for="book in finishedBooks" :key="book.id" class="book-card" @click="goToDetail(book)">
              <div class="book-cover">
                <img :src="book.cover" :alt="book.title" />
                <div class="finished-tag">
                  <el-tag type="success" size="small">已完结</el-tag>
                </div>
              </div>
              <div class="book-info">
                <h4 class="book-title">{{ book.title }}</h4>
                <p class="book-author">{{ book.author }}</p>
              </div>
            </div>
          </div>
          <el-table v-else :data="finishedBooks" @row-click="goToDetail">
            <el-table-column prop="title" label="书名" min-width="200" />
            <el-table-column prop="author" label="作者" width="120" />
            <el-table-column prop="wordCount" label="字数" width="100" />
            <el-table-column label="操作" width="120" fixed="right">
              <template #default="{ row }">
                <el-button type="primary" size="small" @click.stop="reRead(row)">重读</el-button>
              </template>
            </el-table-column>
          </el-table>
        </el-tab-pane>

        <el-tab-pane label="阅读历史" name="history">
          <div v-if="Object.keys(historyGroups).length === 0" class="empty-state">
            <p>暂无阅读历史</p>
          </div>
          <div v-else class="history-list">
            <div v-for="(group, date) in historyGroups" :key="date" class="history-group">
              <div class="history-date">{{ date }}</div>
              <div class="history-items">
                <div v-for="item in group" :key="item.id" class="history-item" @click="continueReading(item)">
                  <img :src="item.cover" class="history-cover" />
                  <div class="history-info">
                    <span class="history-title">{{ item.title }}</span>
                    <span class="history-chapter">看到第{{ item.currentChapter }}章</span>
                    <span class="history-time">{{ item.lastReadTime }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </el-tab-pane>
      </el-tabs>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Reading, Finished } from '@element-plus/icons-vue'

const router = useRouter()
const viewMode = ref<'grid' | 'list'>('grid')
const activeTab = ref('reading')

interface Book {
  id: number
  title: string
  author: string
  cover: string
  progress: number
  currentChapter: number
  wordCount?: number
  lastReadTime?: string
}

/** 书架数据待对接阅读进度 API；当前不注入任何假书 */
const readingBooks = ref<Book[]>([])
const finishedBooks = ref<Book[]>([])

const historyGroups = computed(() => {
  const groups: Record<string, Book[]> = {}
  readingBooks.value.forEach(book => {
    const date = book.lastReadTime?.split(' ')[0] || '今天'
    if (!groups[date]) groups[date] = []
    groups[date].push(book)
  })
  return groups
})

function goToDetail(book: Book) {
  router.push(`/bookstore/detail/${book.id}`)
}

function continueReading(book: Book) {
  router.push(`/bookstore/reader/${book.id}/${book.currentChapter}`)
}

function removeFromShelf(book: Book) {
  ElMessageBox.confirm(`确定将《${book.title}》移出书架吗?`, '提示', {
    type: 'warning'
  }).then(() => {
    readingBooks.value = readingBooks.value.filter(b => b.id !== book.id)
    ElMessage.success('已移出书架')
  }).catch(() => {})
}

function reRead(book: Book) {
  book.currentChapter = 1
  book.progress = 0
  readingBooks.value.push(book)
  ElMessage.success('已加入追读')
}
</script>

<style lang="scss" scoped>
.bookshelf {
  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;

    h1 {
      margin: 0;
    }
  }

  .empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px;
    color: var(--el-text-color-secondary);

    p {
      margin: 16px 0;
    }
  }

  .book-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 24px;

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

        .book-progress {
          position: absolute;
          bottom: 0;
          left: 0;
          right: 0;
          padding: 4px;
          background: rgba(0, 0, 0, 0.5);
        }

        .finished-tag {
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
          overflow: hidden;
          text-overflow: ellipsis;
          white-space: nowrap;
        }

        .book-chapter, .book-author {
          margin: 0 0 8px;
          font-size: 12px;
          color: var(--el-text-color-secondary);
        }

        .book-actions {
          display: flex;
          gap: 8px;
        }
      }
    }
  }

  .history-list {
    .history-group {
      margin-bottom: 24px;

      .history-date {
        font-size: 14px;
        font-weight: 600;
        color: var(--el-text-color-secondary);
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 1px solid var(--el-border-color-lighter);
      }

      .history-items {
        .history-item {
          display: flex;
          gap: 12px;
          padding: 12px;
          cursor: pointer;
          border-radius: 8px;

          &:hover {
            background: var(--el-fill-color-light);
          }

          .history-cover {
            width: 60px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
          }

          .history-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;

            .history-title {
              font-weight: 500;
              margin-bottom: 4px;
            }

            .history-chapter {
              font-size: 13px;
              color: var(--el-text-color-secondary);
            }

            .history-time {
              font-size: 12px;
              color: var(--el-text-color-muted);
              margin-top: 4px;
            }
          }
        }
      }
    }
  }
}
</style>
