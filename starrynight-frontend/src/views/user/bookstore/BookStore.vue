<template>
  <div class="book-store">
    <div v-if="!loaded" class="store-loading">
      <div class="store-loading__spinner">
        <svg class="store-loading__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
        </svg>
      </div>
      <p>加载中…</p>
    </div>

    <div v-else-if="!homeEnabled" class="store-empty-wrap">
      <div class="store-empty">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" opacity="0.3">
          <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
          <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
          <line x1="8" y1="7" x2="16" y2="7"/><line x1="8" y1="11" x2="14" y2="11"/>
        </svg>
        <p>书城维护中，请稍后再试</p>
      </div>
    </div>

    <template v-else>
      <div class="store-hero">
        <div class="store-hero__bg">
          <div class="store-hero__gradient"></div>
          <div class="store-hero__particles" aria-hidden="true"></div>
        </div>
        <div class="store-hero__content">
          <div class="store-hero__top">
            <router-link to="/" class="store-hero__logo">
              <svg viewBox="0 0 32 32" width="36" height="36" fill="none">
                <circle cx="16" cy="16" r="14" stroke="url(#bkLogoGrad)" stroke-width="1.5" opacity="0.5"/>
                <circle cx="16" cy="16" r="4" fill="url(#bkLogoGrad)"/>
                <circle cx="6" cy="8" r="1.2" fill="url(#bkLogoGrad)" opacity="0.55"/>
                <circle cx="26" cy="10" r="0.8" fill="url(#bkLogoGrad)" opacity="0.4"/>
                <defs><linearGradient id="bkLogoGrad" x1="0" y1="0" x2="32" y2="32"><stop offset="0%" stop-color="#a78bfa"/><stop offset="50%" stop-color="#818cf8"/><stop offset="100%" stop-color="#6366f1"/></linearGradient></defs>
              </svg>
              <span class="store-hero__title">{{ siteTitle }}</span>
            </router-link>
            <div class="store-hero__actions">
              <router-link to="/bookshelf" class="hero-btn hero-btn--ghost">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                我的书架
              </router-link>
              <router-link to="/bookstore/search" class="hero-btn hero-btn--secondary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                高级搜索
              </router-link>
            </div>
          </div>
          <div class="store-hero__search">
            <div class="hero-search">
              <svg class="hero-search__icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
              <input
                v-model="searchKeyword"
                class="hero-search__input"
                placeholder="搜索书名、作者..."
                @keyup.enter="handleSearch"
              />
            </div>
          </div>
        </div>
      </div>

      <div class="store-body">
        <div class="store-body__inner">
          <div class="store-main">
            <div v-if="banners.length" class="banner-section">
              <el-carousel height="320px" :interval="5000" indicator-position="none" arrow="always">
                <el-carousel-item v-for="(banner, idx) in banners" :key="banner.id ?? idx">
                  <div
                    class="banner-card"
                    :style="{ backgroundImage: `url(${bannerImage(banner)})` }"
                    @click="bannerBookId(banner) && goToDetail(bannerBookId(banner)!)"
                  >
                    <div class="banner-card__overlay">
                      <div class="banner-card__content">
                        <h2 class="banner-card__title">{{ banner.title }}</h2>
                        <p class="banner-card__desc">{{ banner.description }}</p>
                        <span v-if="bannerBookId(banner)" class="banner-card__cta">立即阅读 →</span>
                      </div>
                    </div>
                  </div>
                </el-carousel-item>
              </el-carousel>
            </div>

            <section class="content-section" v-for="(section, si) in sections" :key="section.key" :style="{ animationDelay: `${0.1 + si * 0.08}s` }">
              <div class="section-head">
                <div class="section-head__left">
                  <span class="section-head__icon" v-html="section.icon"></span>
                  <h3 class="section-head__title">{{ section.title }}</h3>
                </div>
                <router-link :to="section.link" class="section-head__more">
                  查看更多
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </router-link>
              </div>

              <div v-if="section.type === 'grid'" class="book-grid">
                <template v-if="!section.books.length">
                  <div class="section-empty">
                    <p>暂无上架书目。</p>
                    <p class="section-empty__hint">
                      请在运营端「书城管理」新建书目并填写<strong>书源 URL</strong>（文档 <code>/api/bookstore/book?url=</code>），上架后前台按书源接口拉取解析即可展示。
                    </p>
                  </div>
                </template>
                <template v-else>
                  <article
                    v-for="(book, bi) in section.books"
                    :key="String(book.id)"
                    class="book-card"
                    :style="{ animationDelay: `${0.05 + bi * 0.04}s` }"
                    @click="goToDetail(Number(book.id))"
                  >
                    <div class="book-card__cover">
                      <img :src="bookCoverSrc(book, 120, 160)" :alt="String(book.title)" loading="lazy" />
                      <div class="book-card__cover-shine"></div>
                      <span v-if="book.isVip" class="book-card__badge">VIP</span>
                    </div>
                    <div class="book-card__body">
                      <h4 class="book-card__title">{{ book.title }}</h4>
                      <p class="book-card__author">{{ book.author }}</p>
                      <p class="book-card__desc">{{ book.description }}</p>
                      <div class="book-card__stats">
                        <span class="book-card__stat">
                          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                          {{ formatViews(numViews(book.views)) }}
                        </span>
                        <span class="book-card__stat">
                          <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                          {{ book.rating }}
                        </span>
                      </div>
                    </div>
                  </article>
                </template>
              </div>

              <div v-else-if="section.type === 'ranking'" class="ranking-list">
                <div v-if="!section.books.length" class="section-empty section-empty--inline">畅销榜暂无数据</div>
                <template v-else>
                  <div
                    v-for="(book, ri) in section.books"
                    :key="String(book.id)"
                    class="ranking-item"
                    :style="{ animationDelay: `${0.05 + ri * 0.04}s` }"
                    @click="goToDetail(Number(book.id))"
                  >
                    <span class="ranking-item__num" :class="{ 'ranking-item__num--top': ri < 3 }">{{ ri + 1 }}</span>
                    <img :src="bookCoverSrc(book, 64, 88)" class="ranking-item__cover" loading="lazy" :alt="String(book.title)" />
                    <div class="ranking-item__info">
                      <h4 class="ranking-item__title">{{ book.title }}</h4>
                      <p class="ranking-item__author">{{ book.author }}</p>
                      <span class="ranking-item__views">{{ formatViews(numViews(book.views)) }} 阅读</span>
                    </div>
                  </div>
                </template>
              </div>

              <div v-else-if="section.type === 'category'" class="category-grid">
                <div
                  v-for="(cat, ci) in section.categories"
                  :key="cat.id"
                  class="category-card"
                  :style="{ animationDelay: `${0.04 + ci * 0.03}s` }"
                  @click="browseCategory(cat.id)"
                >
                  <span class="category-card__icon" v-html="categoryIcon(cat.id)"></span>
                  <span class="category-card__name">{{ cat.name }}</span>
                  <span class="category-card__count">{{ cat.count }} 本</span>
                </div>
              </div>
            </section>
          </div>

          <aside v-if="topReaders.length || latestUpdates.length" class="store-sidebar">
            <div v-if="topReaders.length" class="sidebar-card">
              <div class="sidebar-card__head">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                <span>读者榜单</span>
              </div>
              <div class="reader-list">
                <div v-for="(reader, index) in topReaders" :key="index" class="reader-item">
                  <span class="reader-item__rank" :class="{ 'reader-item__rank--top': index < 3 }">{{ index + 1 }}</span>
                  <img :src="reader.avatar" class="reader-item__avatar" :alt="reader.name" />
                  <div class="reader-item__info">
                    <span class="reader-item__name">{{ reader.name }}</span>
                    <span class="reader-item__score">阅读 {{ reader.readCount }} 本</span>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="latestUpdates.length" class="sidebar-card">
              <div class="sidebar-card__head">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <span>最新更新</span>
              </div>
              <div class="update-list">
                <div v-for="(update, uidx) in latestUpdates" :key="uidx" class="update-item">
                  <div class="update-item__left">
                    <span class="update-item__book">{{ update.bookTitle }}</span>
                    <span class="update-item__chapter">第{{ update.chapter }}章</span>
                  </div>
                  <span class="update-item__time">{{ update.time }}</span>
                </div>
              </div>
            </div>
          </aside>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { getBookstoreHomeCached } from '@/api/bookstore'
import { extractApiErrorMessage } from '@/utils/request'
import { svgDataPlaceholder } from '@/utils/placeholderImage'

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

const sections = computed(() => [
  {
    key: 'hot',
    type: 'grid',
    title: '热门推荐',
    icon: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>',
    link: '/bookstore/search?sort=hot',
    books: hotBooks.value
  },
  {
    key: 'new',
    type: 'grid',
    title: '新书速递',
    icon: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>',
    link: '/bookstore/search?sort=new',
    books: newBooks.value
  },
  {
    key: 'ranking',
    type: 'ranking',
    title: '畅销榜单',
    icon: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5C7 4 6 9 6 9z"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5C17 4 18 9 18 9z"/><path d="M4 22h16"/><path d="M10 22V8c0-1.1.9-2 2-2s2 .9 2 2v14"/></svg>',
    link: '/bookstore/search?sort=hot',
    books: rankingBooks.value
  },
  {
    key: 'category',
    type: 'category',
    title: '分类浏览',
    icon: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>',
    link: '/bookstore/search',
    categories: categories.value
  }
])

const categoryIconSet: Record<number, string> = {
  1: '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
  2: '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>',
  3: '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>',
  4: '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>',
  5: '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
  6: '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>'
}

function categoryIcon(id: number): string {
  return categoryIconSet[id] || categoryIconSet[1]
}

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
function bookCoverSrc(book: Record<string, unknown>, w: number, h: number): string {
  const raw = book.cover
  const url = typeof raw === 'string' ? raw.trim() : ''
  const title =
    typeof book.title === 'string' && book.title.trim() ? book.title.trim().slice(0, 8) : '书'
  if (url) return url
  return svgDataPlaceholder(w, h, '4f46e5', 'ffffff', title)
}

function formatViews(views: number): string {
  if (views >= 10000000) return (views / 10000000).toFixed(1) + '千万'
  if (views >= 10000) return (views / 10000).toFixed(1) + '万'
  return views.toString()
}
function handleSearch() {
  router.push(`/bookstore/search?keyword=${searchKeyword.value}`)
}
function goToDetail(bookId: number) {
  router.push(`/bookstore/detail/${bookId}`)
}
function browseCategory(categoryId: number) {
  router.push(`/bookstore/search?category=${categoryId}`)
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

onMounted(loadHome)
</script>

<style lang="scss" scoped>
.book-store {
  min-height: 100vh;
  background: $bg-canvas;
}

.store-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 60vh;
  gap: $space-md;
  color: $text-muted;
  font-size: $font-size-md;

  &__spinner {
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
    color: $primary-light;
  }

  &__icon { width: 40px; height: 40px; }
}

@keyframes spin { to { transform: rotate(360deg); } }

.store-empty-wrap {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 60vh;
}

.store-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: $space-md;
  color: $text-muted;
  font-size: $font-size-lg;
  padding: $space-2xl;
  text-align: center;
}

.store-hero {
  position: relative;
  overflow: hidden;
  padding: $space-2xl 0 $space-xl;

  &__bg {
    position: absolute;
    inset: 0;
    background: linear-gradient(155deg, #0f0a2e 0%, #1e1b4b 30%, #312e81 60%, #4338ca 100%);
  }

  &__gradient {
    position: absolute;
    inset: 0;
    background:
      radial-gradient(ellipse 80% 50% at 20% 50%, rgba(129, 140, 248, 0.15), transparent),
      radial-gradient(ellipse 60% 40% at 80% 30%, rgba(167, 139, 250, 0.12), transparent);
  }

  &__particles {
    position: absolute;
    inset: 0;
    opacity: 0.35;
    background-image:
      radial-gradient(1px 1px at 15% 20%, rgba(255,255,255,0.5), transparent),
      radial-gradient(1px 1px at 45% 65%, rgba(255,255,255,0.3), transparent),
      radial-gradient(1.5px 1.5px at 75% 25%, rgba(199,210,254,0.4), transparent),
      radial-gradient(1px 1px at 85% 70%, rgba(255,255,255,0.25), transparent),
      radial-gradient(1px 1px at 30% 85%, rgba(255,255,255,0.2), transparent);
  }

  &__content {
    position: relative;
    z-index: 1;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 $space-lg;
  }

  &__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: $space-md;
  }

  &__logo {
    display: flex;
    align-items: center;
    gap: $space-sm;
    text-decoration: none;
  }

  &__title {
    font-size: 28px;
    font-weight: 800;
    letter-spacing: 0.08em;
    background: linear-gradient(135deg, #e9d5ff, #c4b5fd, #a5b4fc);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }

  &__actions {
    display: flex;
    gap: $space-sm;
  }

  &__search {
    margin-top: $space-xl;
    max-width: 520px;
  }
}

.hero-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 9px 18px;
  border-radius: $radius-sm;
  font-size: $font-size-sm;
  font-weight: 600;
  text-decoration: none;
  transition: all $transition-fast;

  &--ghost {
    color: rgba(255,255,255,0.9);
    border: 1px solid rgba(255,255,255,0.2);
    backdrop-filter: blur(8px);

    &:hover {
      background: rgba(255,255,255,0.1);
      border-color: rgba(255,255,255,0.35);
    }
  }

  &--secondary {
    background: rgba(255,255,255,0.15);
    color: #fff;
    border: 1px solid rgba(255,255,255,0.15);

    &:hover {
      background: rgba(255,255,255,0.22);
    }
  }
}

.hero-search {
  display: flex;
  align-items: center;
  background: rgba(255,255,255,0.15);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255,255,255,0.2);
  border-radius: $radius-md;
  padding: 0 18px;
  transition: all $transition-fast;

  &:focus-within {
    background: rgba(255,255,255,0.22);
    border-color: rgba(255,255,255,0.4);
    box-shadow: 0 0 24px rgba(129,140,248,0.2);
  }

  &__icon {
    flex-shrink: 0;
    color: rgba(255,255,255,0.6);
  }

  &__input {
    flex: 1;
    background: none;
    border: none;
    outline: none;
    padding: 14px 14px;
    font-size: 16px;
    color: #fff;
    min-width: 0;

    &::placeholder {
      color: rgba(255,255,255,0.45);
    }
  }
}

.store-body {
  padding: $space-lg 0 $space-2xl;

  &__inner {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 $space-lg;
    display: flex;
    gap: 28px;

    @media (max-width: 1023px) {
      flex-direction: column;
    }
  }
}

.store-main {
  flex: 1 1 auto;
  min-width: 0;
}

.banner-section {
  margin-bottom: $space-xl;
  border-radius: $radius-xl;
  overflow: hidden;
  box-shadow: $shadow-card;

  :deep(.el-carousel__container) { border-radius: $radius-xl; }
  :deep(.el-carousel__arrow) {
    background: rgba(255,255,255,0.12);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: $radius-full;
    width: 44px;
    height: 44px;
    color: #fff;
    transition: all $transition-fast;

    &:hover {
      background: rgba(255,255,255,0.22);
    }
  }
  :deep(.el-carousel__indicators) {
    bottom: 16px;

    .el-carousel__indicator {
      padding: 4px;

      .el-carousel__button {
        width: 8px;
        height: 8px;
        border-radius: $radius-full;
        background: rgba(255,255,255,0.4);
        opacity: 1;
      }

      &.is-active .el-carousel__button {
        background: #fff;
        width: 24px;
      }
    }
  }
}

.banner-card {
  height: 320px;
  background-size: cover;
  background-position: center;
  cursor: pointer;
  position: relative;

  &__overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(
      to top,
      rgba(0,0,0,0.7) 0%,
      rgba(0,0,0,0.15) 50%,
      rgba(0,0,0,0.1) 100%
    );
    display: flex;
    align-items: flex-end;
  }

  &__content {
    padding: $space-xl $space-2xl;
    color: #fff;
    max-width: 560px;
  }

  &__title {
    font-size: 26px;
    font-weight: 700;
    margin: 0 0 $space-sm;
    text-shadow: 0 2px 8px rgba(0,0,0,0.4);
  }

  &__desc {
    font-size: $font-size-md;
    margin: 0 0 $space-md;
    line-height: 1.6;
    opacity: 0.9;
    text-shadow: 0 1px 4px rgba(0,0,0,0.3);
  }

  &__cta {
    display: inline-block;
    font-size: $font-size-md;
    font-weight: 600;
    padding: 8px 20px;
    background: rgba(255,255,255,0.2);
    backdrop-filter: blur(8px);
    border-radius: $radius-sm;
    border: 1px solid rgba(255,255,255,0.25);
    transition: all $transition-fast;

    &:hover {
      background: rgba(255,255,255,0.3);
    }
  }
}

.content-section {
  margin-top: $space-2xl;
  animation: fadeInUp 0.5s ease-out both;

  &:first-of-type { margin-top: 0; }
}

.section-empty {
  grid-column: 1 / -1;
  padding: $space-2xl $space-lg;
  text-align: center;
  color: var(--el-text-color-secondary);
  font-size: $font-size-md;
  line-height: 1.65;

  p {
    margin: 0 0 $space-sm;
  }

  &__hint {
    font-size: $font-size-sm;
    opacity: 0.9;
  }

  code {
    font-size: 12px;
    padding: 0 4px;
    border-radius: 4px;
    background: var(--el-fill-color-light);
  }

  &--inline {
    grid-column: auto;
    padding: $space-lg;
  }
}

.section-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: $space-lg;

  &__left {
    display: flex;
    align-items: center;
    gap: $space-sm;
  }

  &__icon {
    display: flex;
    color: $primary-light;
  }

  &__title {
    font-size: 20px;
    font-weight: 700;
    color: $text-primary;
    margin: 0;
  }

  &__more {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: $font-size-sm;
    font-weight: 500;
    color: $text-muted;
    text-decoration: none;
    transition: all $transition-fast;

    &:hover {
      color: $primary-light;
      gap: 8px;
    }
  }
}

.book-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 18px;
  contain: layout style;

  @media (max-width: 1100px) { grid-template-columns: repeat(3, 1fr); }
  @media (max-width: 768px) { grid-template-columns: repeat(2, 1fr); }
  @media (max-width: 480px) { grid-template-columns: 1fr; }
}

.book-card {
  cursor: pointer;
  border-radius: $radius-lg;
  background: $bg-surface;
  border: 1px solid $border-subtle;
  overflow: hidden;
  transition: all $transition-fast;
  animation: fadeInUp 0.4s ease-out both;

  &:hover {
    transform: translateY(-4px);
    box-shadow: $shadow-elevated;
    border-color: $border-emphasis;

    .book-card__cover img {
      transform: scale(1.06);
    }

    .book-card__cover-shine {
      opacity: 0.2;
    }
  }

  &__cover {
    position: relative;
    height: 180px;
    overflow: hidden;
    background: $bg-elevated;

    img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.4s ease;
    }
  }

  &__cover-shine {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.15), transparent 60%);
    opacity: 0;
    transition: opacity 0.3s ease;
    pointer-events: none;
  }

  &__badge {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 3px 10px;
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    border-radius: $radius-xs;
    letter-spacing: 0.04em;
  }

  &__body {
    padding: $space-sm $space-md $space-md;
  }

  &__title {
    font-size: $font-size-md;
    font-weight: 600;
    color: $text-primary;
    margin: 0 0 2px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  &__author {
    font-size: $font-size-xs;
    color: $text-muted;
    margin: 0 0 4px;
  }

  &__desc {
    font-size: $font-size-xs;
    color: $text-muted;
    margin: 0 0 8px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  &__stats {
    display: flex;
    gap: 14px;
  }

  &__stat {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    color: $text-muted;

    svg { flex-shrink: 0; }
  }
}

.ranking-list {
  display: flex;
  flex-direction: column;
}

.ranking-item {
  display: flex;
  align-items: center;
  gap: $space-md;
  padding: $space-md;
  cursor: pointer;
  border-bottom: 1px solid $border-subtle;
  transition: all $transition-fast;
  animation: fadeInUp 0.4s ease-out both;

  &:last-child { border-bottom: none; }

  &:hover {
    background: $primary-ghost;
    border-radius: $radius-md;
  }

  &__num {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 700;
    color: $text-muted;
    flex-shrink: 0;

    &--top {
      background: linear-gradient(135deg, #ef4444, #dc2626);
      color: #fff;
      border-radius: $radius-xs;
    }
  }

  &__cover {
    width: 52px;
    height: 72px;
    object-fit: cover;
    border-radius: $radius-xs;
    flex-shrink: 0;
    box-shadow: $shadow-sm;
  }

  &__info {
    flex: 1;
    min-width: 0;

    h4 {
      font-size: $font-size-md;
      font-weight: 600;
      color: $text-primary;
      margin: 0;
    }

    p {
      font-size: $font-size-xs;
      color: $text-muted;
      margin: 2px 0;
    }
  }

  &__views {
    font-size: 11px;
    color: $text-muted;
  }
}

.category-grid {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 14px;
  contain: layout style;

  @media (max-width: 1100px) { grid-template-columns: repeat(4, 1fr); }
  @media (max-width: 768px) { grid-template-columns: repeat(3, 1fr); }
  @media (max-width: 480px) { grid-template-columns: repeat(2, 1fr); }
}

.category-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: $space-lg $space-md;
  border-radius: $radius-lg;
  background: $bg-surface;
  border: 1px solid $border-subtle;
  cursor: pointer;
  transition: all $transition-fast;
  animation: fadeInUp 0.4s ease-out both;

  &:hover {
    transform: translateY(-4px);
    box-shadow: $shadow-elevated;
    border-color: $border-emphasis;
  }

  &__icon {
    color: $primary-light;
    margin-bottom: $space-sm;
    display: flex;
  }

  &__name {
    font-size: $font-size-sm;
    font-weight: 600;
    color: $text-primary;
    margin-bottom: 2px;
  }

  &__count {
    font-size: $font-size-xs;
    color: $text-muted;
  }
}

.store-sidebar {
  width: 280px;
  flex-shrink: 0;

  @media (max-width: 1023px) {
    width: 100%;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: $space-md;
  }

  @media (max-width: 640px) {
    grid-template-columns: 1fr;
  }
}

.sidebar-card {
  background: $bg-surface;
  border: 1px solid $border-subtle;
  border-radius: $radius-lg;
  padding: $space-lg;
  margin-bottom: $space-md;
  box-shadow: $shadow-card;

  @media (max-width: 1023px) { margin-bottom: 0; }

  &__head {
    display: flex;
    align-items: center;
    gap: $space-sm;
    font-size: $font-size-md;
    font-weight: 700;
    color: $text-primary;
    margin-bottom: $space-md;
    padding-bottom: $space-md;
    border-bottom: 1px solid $border-subtle;

    svg { color: $primary-light; }
  }
}

.reader-list, .update-list {
  &__empty {
    text-align: center;
    color: $text-muted;
    font-size: $font-size-sm;
    padding: $space-md 0;
  }
}

.reader-item {
  display: flex;
  align-items: center;
  gap: $space-sm;
  padding: $space-sm 0;
  border-bottom: 1px solid $border-subtle;

  &:last-child { border-bottom: none; }

  &__rank {
    width: 22px;
    height: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    color: $text-muted;
    flex-shrink: 0;

    &--top {
      background: linear-gradient(135deg, #f59e0b, #d97706);
      color: #fff;
      border-radius: $radius-full;
      width: 20px;
      height: 20px;
    }
  }

  &__avatar {
    width: 38px;
    height: 38px;
    border-radius: $radius-full;
    object-fit: cover;
    flex-shrink: 0;
    border: 2px solid $border-subtle;
  }

  &__info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
  }

  &__name {
    font-size: $font-size-sm;
    font-weight: 600;
    color: $text-primary;
  }

  &__score {
    font-size: 11px;
    color: $text-muted;
  }
}

.update-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: $space-sm 0;
  border-bottom: 1px solid $border-subtle;
  gap: $space-sm;

  &:last-child { border-bottom: none; }

  &__left {
    display: flex;
    flex-direction: column;
    min-width: 0;
    flex: 1;
  }

  &__book {
    font-size: $font-size-sm;
    font-weight: 600;
    color: $text-primary;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  &__chapter {
    font-size: 11px;
    color: $text-muted;
  }

  &__time {
    font-size: 11px;
    color: $text-muted;
    flex-shrink: 0;
  }
}

@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(12px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
