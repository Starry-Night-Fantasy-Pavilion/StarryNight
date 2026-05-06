<template>
  <div class="community-list page-container">
    <div class="page-head">
      <div>
        <h1>星夜社区</h1>
        <p class="sub">已通过审核的帖子会展示在此，欢迎交流创作心得。</p>
      </div>
      <el-button type="primary" @click="goNew">
        <el-icon class="mr"><EditPen /></el-icon>
        发布帖子
      </el-button>
    </div>

    <el-skeleton :loading="loading && !rows.length" animated :rows="4">
      <el-empty v-if="!loading && !rows.length" description="暂无帖子，来做第一个分享者吧" />
      <div v-else class="post-cards">
        <article
          v-for="item in rows"
          :key="item.id"
          class="post-card"
          @click="$router.push(`/community/post/${item.id}`)"
        >
          <h2 class="post-title">{{ item.title || '（无标题）' }}</h2>
          <p class="post-excerpt">{{ excerpt(item.content) }}</p>
          <div class="post-meta">
            <span>{{ item.authorUsername || '用户' }}</span>
            <span>{{ item.createTime?.replace('T', ' ').slice(0, 16) }}</span>
            <span>浏览 {{ item.viewCount ?? 0 }}</span>
          </div>
        </article>
      </div>
    </el-skeleton>

    <div v-if="total > 0" class="pager">
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
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { EditPen } from '@element-plus/icons-vue'
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

<style scoped lang="scss">
.community-list {
  max-width: 800px;
  margin: 0 auto;
  padding: $space-lg;
}

.page-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: $space-md;
  margin-bottom: $space-lg;

  h1 {
    margin: 0 0 $space-xs;
    font-size: 1.5rem;
    font-weight: 600;
    color: $text-primary;
  }

  .sub {
    margin: 0;
    color: $text-secondary;
    font-size: 0.9rem;
  }
}

.mr {
  margin-right: 4px;
  vertical-align: middle;
}

.post-cards {
  display: flex;
  flex-direction: column;
  gap: $space-md;
}

.post-card {
  padding: $space-md $space-lg;
  border-radius: $border-radius;
  border: 1px solid $border-subtle;
  background: $bg-elevated;
  cursor: pointer;
  transition: border-color $transition-fast, box-shadow $transition-fast;

  &:hover {
    border-color: rgba(129, 140, 248, 0.45);
    box-shadow: $shadow-sm;
  }
}

.post-title {
  margin: 0 0 $space-sm;
  font-size: 1.1rem;
  font-weight: 600;
  color: $text-primary;
}

.post-excerpt {
  margin: 0 0 $space-sm;
  color: $text-secondary;
  font-size: 0.9rem;
  line-height: 1.55;
}

.post-meta {
  display: flex;
  flex-wrap: wrap;
  gap: $space-md;
  font-size: 0.8rem;
  color: $text-muted;
}

.pager {
  margin-top: $space-lg;
  display: flex;
  justify-content: center;
}
</style>
