<template>
  <div class="community-page">
    <div class="community-header">
      <div class="community-header__inner">
        <div class="community-header__info">
          <div class="community-header__icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
          </div>
          <div>
            <h1 class="community-header__title">星夜社区</h1>
            <p class="community-header__desc">已通过审核的帖子会展示在此，欢迎交流创作心得。</p>
          </div>
        </div>
        <button class="community-header__action" @click="goNew">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
            <path d="M15 5l4 4"/>
          </svg>
          发布帖子
        </button>
      </div>
    </div>

    <div class="community-body">
      <div class="community-body__inner">
        <div v-if="loading && !rows.length" class="post-skeleton">
          <div v-for="n in 4" :key="n" class="skeleton-card">
            <div class="skeleton-card__line skeleton-card__line--title"></div>
            <div class="skeleton-card__line skeleton-card__line--body"></div>
            <div class="skeleton-card__line skeleton-card__line--short"></div>
          </div>
        </div>

        <div v-else-if="!loading && !rows.length" class="community-empty">
          <svg width="72" height="72" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="0.8" opacity="0.25">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
          </svg>
          <h3>暂无帖子</h3>
          <p>来做第一个分享者吧</p>
          <button class="community-empty__btn" @click="goNew">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="M15 5l4 4"/></svg>
            发布第一篇帖子
          </button>
        </div>

        <div v-else class="post-list">
          <article
            v-for="(item, idx) in rows"
            :key="item.id"
            class="post-card"
            :style="{ animationDelay: `${0.06 * idx}s` }"
            @click="$router.push(`/community/post/${item.id}`)"
          >
            <div class="post-card__inner">
              <div class="post-card__avatar">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              </div>
              <div class="post-card__body">
                <h2 class="post-card__title">{{ item.title || '（无标题）' }}</h2>
                <p class="post-card__excerpt">{{ excerpt(item.content) }}</p>
                <div class="post-card__meta">
                  <span class="post-card__meta-item">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    {{ item.authorUsername || '用户' }}
                  </span>
                  <span class="post-card__meta-item">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    {{ item.createTime?.replace('T', ' ').slice(0, 16) }}
                  </span>
                  <span class="post-card__meta-item">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    {{ item.viewCount ?? 0 }} 浏览
                  </span>
                </div>
              </div>
              <div class="post-card__arrow">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
              </div>
            </div>
          </article>
        </div>

        <div v-if="total > 0" class="community-pager">
          <el-pagination
            v-model:current-page="page"
            v-model:page-size="size"
            :total="total"
            :page-sizes="[10, 20]"
            layout="total, sizes, prev, pager, next"
            @current-change="load"
            @size-change="load"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { useUserSessionStore } from '@/stores/auth'
import { listCommunityPosts } from '@/api/community'
import type { CommunityPostItem } from '@/types/api'

const router = useRouter()
const userS = useUserSessionStore()

const loading = ref(false)
const rows = ref<CommunityPostItem[]>([])
const page = ref(1)
const size = ref(10)
const total = ref(0)

function excerpt(text: string) {
  const t = (text || '').replace(/\s+/g, ' ').trim()
  return t.length > 160 ? `${t.slice(0, 160)}…` : t || '—'
}

async function load() {
  loading.value = true
  try {
    const data = await listCommunityPosts(page.value, size.value)
    rows.value = data.records || []
    total.value = data.total ?? 0
  } catch {
    rows.value = []
    total.value = 0
  } finally {
    loading.value = false
  }
}

function goNew() {
  if (!userS.isLoggedIn) {
    ElMessage.info('请先登录后再发帖')
    router.push({ name: 'Login', query: { redirect: '/community/new' } })
    return
  }
  router.push('/community/new')
}

onMounted(() => {
  load()
})
</script>

<style lang="scss" scoped>
.community-page {
  min-height: 100%;
}

.community-header {
  background: $bg-surface;
  border-bottom: 1px solid $border-subtle;
  padding: $space-lg 0;

  &__inner {
    max-width: 820px;
    margin: 0 auto;
    padding: 0 $space-lg;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: $space-md;
    flex-wrap: wrap;
  }

  &__info {
    display: flex;
    align-items: flex-start;
    gap: $space-md;
  }

  &__icon {
    width: 52px;
    height: 52px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: $gradient-primary-btn;
    border-radius: $radius-md;
    color: #fff;
    flex-shrink: 0;
    box-shadow: 0 0 16px rgba(99, 102, 241, 0.25);
  }

  &__title {
    font-size: $font-size-2xl;
    font-weight: 700;
    color: $text-primary;
    margin: 0 0 2px;
    letter-spacing: -0.02em;
  }

  &__desc {
    font-size: $font-size-sm;
    color: $text-muted;
    margin: 0;
    line-height: 1.5;
  }

  &__action {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 11px 22px;
    background: $gradient-primary-btn;
    border: none;
    color: #fff;
    font-size: $font-size-md;
    font-weight: 600;
    border-radius: $radius-sm;
    cursor: pointer;
    transition: all $transition-fast;
    position: relative;
    overflow: hidden;

    &::before {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, rgba(255,255,255,0.1), transparent);
      pointer-events: none;
    }

    &:hover {
      box-shadow: 0 0 24px rgba(99, 102, 241, 0.4);
      transform: translateY(-1px);
    }

    &:active {
      transform: translateY(0);
    }
  }
}

.community-body {
  padding: $space-lg 0 $space-2xl;

  &__inner {
    max-width: 820px;
    margin: 0 auto;
    padding: 0 $space-lg;
  }
}

.post-skeleton {
  display: flex;
  flex-direction: column;
  gap: $space-md;
}

.skeleton-card {
  padding: $space-lg;
  background: $bg-surface;
  border: 1px solid $border-subtle;
  border-radius: $radius-lg;

  &__line {
    height: 14px;
    border-radius: $radius-xs;
    background: linear-gradient(90deg, $bg-elevated 25%, $border-subtle 50%, $bg-elevated 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s ease-in-out infinite;
    margin-bottom: $space-sm;

    &:last-child { margin-bottom: 0; }

    &--title { width: 55%; height: 18px; }
    &--body { width: 90%; }
    &--short { width: 35%; }
  }
}

@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

.community-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: $space-3xl $space-lg;
  text-align: center;

  h3 {
    font-size: $font-size-xl;
    font-weight: 600;
    color: $text-primary;
    margin: $space-md 0 $space-xs;
  }

  p {
    font-size: $font-size-md;
    color: $text-muted;
    margin: 0 0 $space-lg;
  }

  &__btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 22px;
    background: $gradient-primary-btn;
    border: none;
    color: #fff;
    font-size: $font-size-md;
    font-weight: 600;
    border-radius: $radius-sm;
    cursor: pointer;
    transition: all $transition-fast;

    &:hover {
      box-shadow: 0 0 20px rgba(99, 102, 241, 0.35);
      transform: translateY(-1px);
    }
  }
}

.post-list {
  display: flex;
  flex-direction: column;
  gap: $space-md;
  margin-bottom: $space-lg;
}

.post-card {
  cursor: pointer;
  border-radius: $radius-lg;
  background: $bg-surface;
  border: 1px solid $border-subtle;
  transition: all $transition-fast;
  animation: fadeInUp 0.4s ease-out both;

  &:hover {
    border-color: $border-emphasis;
    box-shadow: $shadow-elevated;
    transform: translateY(-1px);

    .post-card__arrow {
      opacity: 1;
      transform: translateX(0);
    }
  }

  &__inner {
    display: flex;
    align-items: flex-start;
    gap: $space-md;
    padding: $space-lg;
  }

  &__avatar {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: $primary-ghost;
    border-radius: $radius-full;
    color: $primary-light;
    flex-shrink: 0;
    margin-top: 2px;
  }

  &__body {
    flex: 1;
    min-width: 0;
  }

  &__title {
    font-size: $font-size-lg;
    font-weight: 600;
    color: $text-primary;
    margin: 0 0 $space-sm;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  &__excerpt {
    font-size: $font-size-md;
    color: $text-secondary;
    margin: 0 0 $space-md;
    line-height: 1.65;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  &__meta {
    display: flex;
    flex-wrap: wrap;
    gap: $space-md;
  }

  &__meta-item {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: $font-size-xs;
    color: $text-muted;

    svg { flex-shrink: 0; }
  }

  &__arrow {
    flex-shrink: 0;
    color: $text-muted;
    opacity: 0.4;
    transform: translateX(-4px);
    transition: all $transition-fast;
    margin-top: 10px;
  }
}

.community-pager {
  display: flex;
  justify-content: center;
  padding: $space-md 0;
}

@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(14px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
