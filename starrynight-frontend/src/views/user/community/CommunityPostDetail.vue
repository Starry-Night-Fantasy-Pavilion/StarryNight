<template>
  <div class="community-detail page-container">
    <el-page-header @back="$router.push('/community')" title="返回社区">
      <template #extra>
        <el-button v-if="canEdit" type="primary" plain @click="$router.push(`/community/edit/${post?.id}`)">
          编辑
        </el-button>
        <el-button
          v-if="userS.isLoggedIn && post && userS.userInfo?.id !== post.userId"
          :loading="reporting"
          type="danger"
          plain
          @click="reportPost"
        >
          举报
        </el-button>
      </template>
    </el-page-header>

    <el-skeleton v-if="loading" animated :rows="6" />
    <el-empty v-else-if="!post" description="帖子不存在或未公开" />
    <template v-else-if="post">
      <header class="detail-head">
        <h1>{{ post.title || '（无标题）' }}</h1>
        <div class="meta">
          <span>{{ post.authorUsername || '用户' }}</span>
          <span>{{ post.createTime?.replace('T', ' ').slice(0, 16) }}</span>
          <span>浏览 {{ post.viewCount ?? 0 }}</span>
          <span>评论 {{ post.commentCount ?? 0 }}</span>
        </div>
        <el-alert v-if="authorAlert" class="author-alert" :type="authorAlert.type" :closable="false">
          <template #title>{{ authorAlert.title }}</template>
          <p v-if="authorAlert.desc" class="alert-desc">{{ authorAlert.desc }}</p>
        </el-alert>
      </header>
      <div class="detail-body">{{ post.content }}</div>

      <section v-if="canInteract" class="interact">
        <div class="like-row">
          <el-button
            :type="post.likedByMe ? 'primary' : 'default'"
            :loading="likeLoading"
            :disabled="!userS.isLoggedIn"
            @click="onToggleLike"
          >
            {{ post.likedByMe ? '已点赞' : '点赞' }} · {{ post.likeCount ?? 0 }}
          </el-button>
          <span v-if="!userS.isLoggedIn" class="like-tip">登录后可点赞</span>
        </div>

        <h3 class="comments-title">评论</h3>
        <el-skeleton v-if="commentsLoading" :rows="3" animated />
        <el-empty v-else-if="!comments.length" description="还没有评论" :image-size="72" />
        <ul v-else class="comment-list">
          <li
            v-for="c in comments"
            :key="c.id"
            class="comment-item"
            :class="{ 'is-reply': c.parentId }"
          >
            <div class="comment-head">
              <strong>{{ c.authorUsername || '用户' }}</strong>
              <el-tag
                v-if="c.auditStatus === 0"
                type="warning"
                size="small"
                effect="plain"
                class="cmt-audit-tag"
              >
                待审核
              </el-tag>
              <el-tag
                v-else-if="c.auditStatus === 2"
                type="danger"
                size="small"
                effect="plain"
                class="cmt-audit-tag"
              >
                未通过
              </el-tag>
              <span class="comment-time">{{ c.createTime?.replace('T', ' ').slice(0, 16) }}</span>
              <el-button
                v-if="userS.isLoggedIn && (c.auditStatus == null || c.auditStatus === 1)"
                type="primary"
                link
                size="small"
                @click="startReply(c)"
              >
                回复
              </el-button>
              <el-button
                v-if="userS.userInfo?.id === c.userId"
                type="danger"
                link
                size="small"
                :loading="deletingId === c.id"
                @click="onDeleteComment(c)"
              >
                删除
              </el-button>
              <el-button
                v-else-if="userS.isLoggedIn"
                type="danger"
                link
                size="small"
                :loading="reporting"
                @click="reportComment(c)"
              >
                举报
              </el-button>
            </div>
            <p v-if="c.parentId" class="reply-to">回复 #{{ c.parentId }}</p>
            <p v-if="c.auditStatus === 0" class="cmt-audit-hint">仅您可见，通过后展示给所有人。</p>
            <p v-else-if="c.auditStatus === 2 && c.moderationNote" class="cmt-reject-reason">
              {{ c.moderationNote }}
            </p>
            <p class="comment-body">{{ c.content }}</p>
          </li>
        </ul>

        <div v-if="userS.isLoggedIn" class="comment-form">
          <p v-if="replyTarget" class="reply-banner">
            回复 @{{ replyTarget.authorUsername }}
            <el-button type="primary" link size="small" @click="replyTarget = null">取消</el-button>
          </p>
          <el-input
            v-model="commentText"
            type="textarea"
            :rows="3"
            maxlength="2000"
            show-word-limit
            placeholder="写下你的看法…"
          />
          <el-button type="primary" class="submit-cmt" :loading="commentSubmitting" @click="submitComment">
            发表评论
          </el-button>
        </div>
        <p v-else class="login-tip">
          <router-link :to="{ name: 'Login', query: { redirect: route.fullPath } }">登录</router-link>
          后参与评论
        </p>
      </section>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { useUserSessionStore } from '@/stores/auth'
import {
  createCommunityComment,
  createCommunityReport,
  deleteCommunityComment,
  getCommunityPost,
  listCommunityComments,
  toggleCommunityPostLike
} from '@/api/community'
import type { CommunityCommentItem, CommunityPostItem } from '@/types/api'

const route = useRoute()
const userS = useUserSessionStore()

const loading = ref(true)
const post = ref<CommunityPostItem | null>(null)
const comments = ref<CommunityCommentItem[]>([])
const commentsLoading = ref(false)
const commentText = ref('')
const commentSubmitting = ref(false)
const likeLoading = ref(false)
const deletingId = ref<number | null>(null)
const replyTarget = ref<CommunityCommentItem | null>(null)
const reporting = ref(false)

const postIdNum = computed(() => Number(route.params.id))

const canInteract = computed(() => post.value?.interactionEnabled === true)

const canEdit = computed(() => {
  if (!post.value || !userS.isLoggedIn || !userS.userInfo?.id) return false
  if (post.value.userId !== userS.userInfo.id) return false
  const s = post.value.auditStatus
  return s === 0 || s === 2
})

const authorAlert = computed((): { type: 'info' | 'warning' | 'error'; title: string; desc?: string } | null => {
  if (!post.value || !userS.isLoggedIn || !userS.userInfo?.id) return null
  if (post.value.userId !== userS.userInfo.id) return null
  const s = post.value.auditStatus
  if (s === 0) {
    return { type: 'info', title: '该帖正在审核中，仅您可见；通过后将对所有人展示。', desc: '需修改请点右上角「编辑」。' }
  }
  if (s === 2) {
    return {
      type: 'warning',
      title: '该帖未通过审核',
      desc: post.value.rejectReason ? `原因：${post.value.rejectReason}` : '请根据反馈修改后重新提交。'
    }
  }
  return null
})

async function loadPost() {
  const id = postIdNum.value
  if (!Number.isFinite(id)) {
    post.value = null
    loading.value = false
    return
  }
  loading.value = true
  try {
    post.value = await getCommunityPost(id)
  } catch {
    post.value = null
  } finally {
    loading.value = false
  }
}

async function loadComments() {
  const id = postIdNum.value
  if (!Number.isFinite(id) || !canInteract.value) {
    comments.value = []
    return
  }
  commentsLoading.value = true
  try {
    const data = await listCommunityComments(id, 1, 50)
    comments.value = data.records || []
  } catch {
    comments.value = []
  } finally {
    commentsLoading.value = false
  }
}

async function onToggleLike() {
  if (!post.value || !userS.isLoggedIn) {
    ElMessage.info('请先登录')
    return
  }
  likeLoading.value = true
  try {
    const r = await toggleCommunityPostLike(post.value.id)
    post.value.likedByMe = r.liked
    post.value.likeCount = r.likeCount
  } finally {
    likeLoading.value = false
  }
}

function startReply(c: CommunityCommentItem) {
  replyTarget.value = c
}

async function submitComment() {
  const text = commentText.value.trim()
  if (!text || !post.value) return
  commentSubmitting.value = true
  try {
    const created = await createCommunityComment({
      postId: post.value.id,
      parentId: replyTarget.value?.id ?? undefined,
      content: text
    })
    commentText.value = ''
    replyTarget.value = null
    if (created.auditStatus === 1) {
      ElMessage.success('已发布')
      post.value.commentCount = (post.value.commentCount ?? 0) + 1
    } else if (created.auditStatus === 0) {
      ElMessage.success('已提交，审核通过后公开可见')
    } else {
      ElMessage.success('已提交')
    }
    await loadComments()
  } finally {
    commentSubmitting.value = false
  }
}

async function reportPost() {
  if (!post.value || !userS.isLoggedIn) {
    ElMessage.info('请先登录')
    return
  }
  if (userS.userInfo?.id && post.value.userId === userS.userInfo.id) {
    ElMessage.warning('不能举报自己的内容')
    return
  }
  let reason = ''
  try {
    const r = await ElMessageBox.prompt('请输入举报原因（必填）', '举报帖子', {
      inputType: 'textarea',
      confirmButtonText: '提交',
      cancelButtonText: '取消',
      inputValidator: (v: string) => (!!v && !!v.trim()) || '请输入原因'
    })
    reason = r.value.trim()
  } catch {
    return
  }
  reporting.value = true
  try {
    await createCommunityReport({ kind: 'POST', postId: post.value.id, reason })
    ElMessage.success('已提交举报，感谢反馈')
  } finally {
    reporting.value = false
  }
}

async function reportComment(c: CommunityCommentItem) {
  if (!userS.isLoggedIn) {
    ElMessage.info('请先登录')
    return
  }
  if (userS.userInfo?.id && c.userId === userS.userInfo.id) {
    ElMessage.warning('不能举报自己的内容')
    return
  }
  let reason = ''
  try {
    const r = await ElMessageBox.prompt('请输入举报原因（必填）', '举报评论', {
      inputType: 'textarea',
      confirmButtonText: '提交',
      cancelButtonText: '取消',
      inputValidator: (v: string) => (!!v && !!v.trim()) || '请输入原因'
    })
    reason = r.value.trim()
  } catch {
    return
  }
  reporting.value = true
  try {
    await createCommunityReport({ kind: 'COMMENT', commentId: c.id, reason })
    ElMessage.success('已提交举报，感谢反馈')
  } finally {
    reporting.value = false
  }
}

async function onDeleteComment(c: CommunityCommentItem) {
  try {
    await ElMessageBox.confirm('删除该条评论？', '删除', { type: 'warning' })
  } catch {
    return
  }
  deletingId.value = c.id
  try {
    await deleteCommunityComment(c.id)
    ElMessage.success('已删除')
    await loadComments()
    if (post.value) {
      post.value.commentCount = Math.max(0, (post.value.commentCount ?? 0) - 1)
    }
  } finally {
    deletingId.value = null
  }
}

onMounted(() => {
  if (userS.isLoggedIn) userS.initProfileIfNeeded()
  loadPost()
})

watch(
  () => route.params.id,
  () => {
    loadPost()
  }
)

watch(
  () => [post.value?.id, post.value?.auditStatus, userS.isLoggedIn] as const,
  () => {
    if (post.value && canInteract.value) loadComments()
    else comments.value = []
  }
)
</script>

<style scoped lang="scss">
.community-detail {
  max-width: 800px;
  margin: 0 auto;
  padding: $space-lg;
}

.detail-head {
  margin-top: $space-md;

  h1 {
    margin: 0 0 $space-sm;
    font-size: 1.35rem;
    font-weight: 600;
    color: $text-primary;
  }

  .meta {
    display: flex;
    flex-wrap: wrap;
    gap: $space-md;
    font-size: 0.85rem;
    color: $text-muted;
    margin-bottom: $space-md;
  }
}

.author-alert {
  margin-bottom: $space-md;
}

.detail-body {
  white-space: pre-wrap;
  line-height: 1.75;
  color: $text-primary;
  font-size: 0.95rem;
}

.alert-desc {
  margin: 6px 0 0;
  font-size: 0.85rem;
  line-height: 1.5;
}

.interact {
  margin-top: $space-xl;
  padding-top: $space-lg;
  border-top: 1px solid $border-subtle;
}

.like-row {
  display: flex;
  align-items: center;
  gap: $space-sm;
  margin-bottom: $space-lg;
}

.like-tip {
  font-size: 0.85rem;
  color: $text-muted;
}

.comments-title {
  margin: 0 0 $space-md;
  font-size: 1rem;
  font-weight: 600;
  color: $text-primary;
}

.comment-list {
  list-style: none;
  margin: 0 0 $space-lg;
  padding: 0;
}

.comment-item {
  padding: $space-md 0;
  border-bottom: 1px solid $border-subtle;

  &.is-reply {
    padding-left: $space-md;
    border-left: 2px solid rgba(129, 140, 248, 0.35);
  }
}

.comment-head {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: $space-sm;
  font-size: 0.85rem;
  margin-bottom: $space-xs;

  strong {
    color: $text-primary;
  }
}

.comment-time {
  color: $text-muted;
}

.cmt-audit-tag {
  margin-left: 2px;
}

.cmt-audit-hint,
.cmt-reject-reason {
  margin: 0 0 $space-xs;
  font-size: 0.8rem;
  color: $text-secondary;
  line-height: 1.4;
}

.cmt-reject-reason {
  color: var(--el-color-danger);
}

.reply-to {
  margin: 0 0 $space-xs;
  font-size: 0.8rem;
  color: $text-secondary;
}

.comment-body {
  margin: 0;
  white-space: pre-wrap;
  line-height: 1.6;
  color: $text-primary;
  font-size: 0.9rem;
}

.comment-form {
  margin-top: $space-md;
}

.reply-banner {
  margin: 0 0 $space-sm;
  font-size: 0.85rem;
  color: $text-secondary;
}

.submit-cmt {
  margin-top: $space-sm;
}

.login-tip {
  margin: $space-md 0 0;
  font-size: 0.9rem;
  color: $text-secondary;

  a {
    color: var(--el-color-primary);
  }
}
</style>
