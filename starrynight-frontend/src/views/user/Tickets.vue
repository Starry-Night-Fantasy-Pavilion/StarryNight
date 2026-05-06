<template>
  <div class="tickets-page page-container">
    <!-- 列表视图 -->
    <template v-if="view === 'list'">
      <div class="page-head">
        <div>
          <h1>我的工单</h1>
          <p class="sub">提交问题反馈、账号申诉或功能建议，我们会尽快处理。</p>
        </div>
        <el-button type="primary" @click="view = 'create'">
          <el-icon class="mr"><Plus /></el-icon>
          提交工单
        </el-button>
      </div>

      <div class="filter-bar">
        <el-radio-group v-model="filterStatus" size="small" @change="loadList">
          <el-radio-button value="">全部</el-radio-button>
          <el-radio-button value="OPEN">待处理</el-radio-button>
          <el-radio-button value="IN_PROGRESS">处理中</el-radio-button>
          <el-radio-button value="RESOLVED">已解决</el-radio-button>
          <el-radio-button value="CLOSED">已关闭</el-radio-button>
        </el-radio-group>
      </div>

      <el-skeleton :loading="loading && !list.length" animated :rows="4">
        <el-empty v-if="!loading && !list.length" description="暂无工单" />
        <div v-else class="ticket-cards">
          <div
            v-for="t in list"
            :key="t.id"
            class="ticket-card"
            @click="openDetail(t.id)"
          >
            <div class="tc-head">
              <el-tag :type="statusType(t.status)" size="small" class="tc-status">
                {{ statusLabel(t.status) }}
              </el-tag>
              <el-tag type="info" size="small" class="tc-cat">{{ categoryLabel(t.category) }}</el-tag>
              <span class="tc-no">{{ t.ticketNo }}</span>
              <el-tag :type="priorityType(t.priority)" size="small" class="tc-priority">
                {{ priorityLabel(t.priority) }}
              </el-tag>
            </div>
            <h2 class="tc-title">{{ t.title }}</h2>
            <div class="tc-meta">
              <span>{{ fmtTime(t.createTime) }}</span>
              <span v-if="t.updateTime !== t.createTime">最近更新 {{ fmtTime(t.updateTime) }}</span>
            </div>
          </div>
        </div>
      </el-skeleton>

      <div v-if="total > 0" class="pager">
        <el-pagination
          v-model:current-page="page"
          v-model:page-size="pageSize"
          :total="total"
          layout="total, prev, pager, next"
          @current-change="loadList"
        />
      </div>
    </template>

    <!-- 提交工单 -->
    <template v-else-if="view === 'create'">
      <div class="page-head">
        <div>
          <h1>提交工单</h1>
        </div>
        <el-button @click="view = 'list'">返回列表</el-button>
      </div>

      <div class="form-card">
        <el-form ref="formRef" :model="form" :rules="rules" label-width="100px" size="default">
          <el-form-item label="问题类型" prop="category">
            <el-select v-model="form.category" placeholder="请选择">
              <el-option v-for="c in categoryOptions" :key="c.value" :label="c.label" :value="c.value" />
            </el-select>
          </el-form-item>
          <el-form-item label="标题" prop="title">
            <el-input v-model="form.title" maxlength="200" show-word-limit placeholder="简述您的问题" />
          </el-form-item>
          <el-form-item label="详细描述" prop="content">
            <el-input
              v-model="form.content"
              type="textarea"
              :rows="6"
              maxlength="5000"
              show-word-limit
              placeholder="请详细描述遇到的问题，方便我们快速定位"
            />
          </el-form-item>
          <el-form-item>
            <el-button type="primary" :loading="submitting" @click="submitForm">提交工单</el-button>
            <el-button @click="view = 'list'">取消</el-button>
          </el-form-item>
        </el-form>
      </div>
    </template>

    <!-- 工单详情 -->
    <template v-else-if="view === 'detail' && current">
      <div class="page-head">
        <div>
          <h1>工单详情</h1>
          <p class="sub tc-no-sub">{{ current.ticketNo }}</p>
        </div>
        <el-button @click="view = 'list'">返回列表</el-button>
      </div>

      <div class="detail-card">
        <div class="detail-header">
          <div class="detail-tags">
            <el-tag :type="statusType(current.status)" size="default">{{ statusLabel(current.status) }}</el-tag>
            <el-tag type="info">{{ categoryLabel(current.category) }}</el-tag>
            <el-tag :type="priorityType(current.priority)">{{ priorityLabel(current.priority) }}</el-tag>
          </div>
          <h2 class="detail-title">{{ current.title }}</h2>
          <p class="detail-meta">提交于 {{ fmtTime(current.createTime) }}</p>
        </div>
        <div class="detail-content">{{ current.content }}</div>
      </div>

      <!-- 对话流 -->
      <div class="chat-flow">
        <div
          v-for="r in current.replies"
          :key="r.id"
          :class="['chat-bubble', r.authorType === 'USER' ? 'chat-bubble--user' : 'chat-bubble--ops']"
        >
          <div class="chat-meta">
            <span class="chat-author">{{ r.authorType === 'OPS' ? '客服' : '我' }}</span>
            <span class="chat-time">{{ fmtTime(r.createTime) }}</span>
          </div>
          <div class="chat-content">{{ r.content }}</div>
        </div>
        <div v-if="!current.replies?.length" class="chat-empty">暂无回复，等待客服处理中…</div>
      </div>

      <!-- 用户回复框 -->
      <div v-if="current.status !== 'CLOSED'" class="reply-box">
        <el-input
          v-model="replyContent"
          type="textarea"
          :rows="3"
          maxlength="2000"
          show-word-limit
          placeholder="追加说明或补充信息…"
        />
        <div class="reply-actions">
          <el-button type="primary" :loading="replySubmitting" @click="submitReply">发送</el-button>
        </div>
      </div>
      <el-alert v-else type="info" :closable="false" title="该工单已关闭，如有新问题请重新提交工单。" />
    </template>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, reactive } from 'vue'
import { ElMessage } from 'element-plus'
import type { FormInstance, FormRules } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import { createTicket, listMyTickets, getTicket, replyTicket } from '@/api/ticket'
import type { TicketItem, TicketStatus, TicketCategory } from '@/types/api'

type View = 'list' | 'create' | 'detail'

const view = ref<View>('list')
const loading = ref(false)
const list = ref<TicketItem[]>([])
const total = ref(0)
const page = ref(1)
const pageSize = ref(10)
const filterStatus = ref('')

const current = ref<TicketItem | null>(null)
const replyContent = ref('')
const replySubmitting = ref(false)

const formRef = ref<FormInstance>()
const submitting = ref(false)
const form = reactive({
  category: 'OTHER' as TicketCategory,
  title: '',
  content: ''
})
const rules: FormRules = {
  category: [{ required: true, message: '请选择问题类型' }],
  title: [{ required: true, message: '请填写标题', max: 200 }],
  content: [{ required: true, message: '请填写详细描述' }]
}

const categoryOptions = [
  { label: 'Bug 报告', value: 'BUG' },
  { label: '账号问题', value: 'ACCOUNT' },
  { label: '充值/计费', value: 'BILLING' },
  { label: '内容问题', value: 'CONTENT' },
  { label: '功能建议', value: 'FEATURE' },
  { label: '其他', value: 'OTHER' }
]

async function loadList() {
  loading.value = true
  try {
    const res = await listMyTickets({
      status: (filterStatus.value as TicketStatus) || undefined,
      page: page.value,
      size: pageSize.value
    })
    list.value = res.records ?? []
    total.value = Number(res.total ?? 0)
  } finally {
    loading.value = false
  }
}

async function openDetail(id: number) {
  try {
    current.value = await getTicket(id)
    replyContent.value = ''
    view.value = 'detail'
  } catch {
    ElMessage.error('加载工单失败')
  }
}

async function submitForm() {
  await formRef.value?.validate()
  submitting.value = true
  try {
    const ticket = await createTicket({ ...form })
    ElMessage.success('工单提交成功，我们将尽快处理')
    form.category = 'OTHER'
    form.title = ''
    form.content = ''
    view.value = 'list'
    await loadList()
    await openDetail(ticket.id)
  } catch {
    ElMessage.error('提交失败，请稍后重试')
  } finally {
    submitting.value = false
  }
}

async function submitReply() {
  if (!replyContent.value.trim()) {
    ElMessage.warning('请输入回复内容')
    return
  }
  if (!current.value) return
  replySubmitting.value = true
  try {
    await replyTicket(current.value.id, { content: replyContent.value })
    replyContent.value = ''
    current.value = await getTicket(current.value.id)
    ElMessage.success('回复成功')
  } catch {
    ElMessage.error('回复失败')
  } finally {
    replySubmitting.value = false
  }
}

function statusLabel(s: TicketStatus) {
  const m: Record<TicketStatus, string> = {
    OPEN: '待处理',
    IN_PROGRESS: '处理中',
    RESOLVED: '已解决',
    CLOSED: '已关闭'
  }
  return m[s] ?? s
}
function statusType(s: TicketStatus) {
  const m: Record<TicketStatus, string> = {
    OPEN: 'warning',
    IN_PROGRESS: 'primary',
    RESOLVED: 'success',
    CLOSED: 'info'
  }
  return m[s] ?? ''
}
function categoryLabel(c: string) {
  const m: Record<string, string> = {
    BUG: 'Bug',
    ACCOUNT: '账号',
    BILLING: '计费',
    CONTENT: '内容',
    FEATURE: '功能建议',
    OTHER: '其他'
  }
  return m[c] ?? c
}
function priorityLabel(p: string) {
  const m: Record<string, string> = { LOW: '低', NORMAL: '普通', HIGH: '高', URGENT: '紧急' }
  return m[p] ?? p
}
function priorityType(p: string) {
  const m: Record<string, string> = { LOW: 'info', NORMAL: '', HIGH: 'warning', URGENT: 'danger' }
  return m[p] ?? ''
}
function fmtTime(t?: string) {
  return t ? t.replace('T', ' ').slice(0, 16) : ''
}

onMounted(loadList)
</script>

<style lang="scss" scoped>
.tickets-page {
  max-width: 860px;
  margin: 0 auto;
  padding: $space-xl $space-lg;
}

.page-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: $space-lg;
  margin-bottom: $space-xl;

  h1 {
    font-size: $font-size-2xl;
    font-weight: 700;
    color: $text-primary;
  }
  .sub {
    margin-top: $space-xs;
    color: $text-muted;
    font-size: $font-size-sm;
  }
}

.filter-bar {
  margin-bottom: $space-lg;
}

.mr {
  margin-right: 4px;
}

.ticket-cards {
  display: flex;
  flex-direction: column;
  gap: $space-md;
}

.ticket-card {
  padding: $space-lg;
  background: $bg-surface;
  border: 1px solid $border-default;
  border-radius: $radius-lg;
  cursor: pointer;
  transition: all $transition-fast;

  &:hover {
    border-color: $border-accent;
    box-shadow: $shadow-elevated;
    transform: translateY(-1px);
  }
}

.tc-head {
  display: flex;
  align-items: center;
  gap: $space-sm;
  margin-bottom: $space-sm;
  flex-wrap: wrap;
}

.tc-no {
  font-size: $font-size-xs;
  color: $text-muted;
  margin-left: auto;
}

.tc-title {
  font-size: $font-size-lg;
  font-weight: 600;
  color: $text-primary;
  margin-bottom: $space-xs;
}

.tc-meta {
  display: flex;
  gap: $space-md;
  font-size: $font-size-xs;
  color: $text-muted;
}

.form-card {
  background: $bg-surface;
  border: 1px solid $border-default;
  border-radius: $radius-lg;
  padding: $space-xl;
}

.detail-card {
  background: $bg-surface;
  border: 1px solid $border-default;
  border-radius: $radius-lg;
  padding: $space-xl;
  margin-bottom: $space-lg;
}

.detail-header {
  margin-bottom: $space-md;
  padding-bottom: $space-md;
  border-bottom: 1px solid $border-subtle;
}

.detail-tags {
  display: flex;
  gap: $space-sm;
  margin-bottom: $space-sm;
  flex-wrap: wrap;
}

.detail-title {
  font-size: $font-size-xl;
  font-weight: 700;
  color: $text-primary;
  margin: $space-sm 0 $space-xs;
}

.detail-meta {
  font-size: $font-size-xs;
  color: $text-muted;
}

.detail-content {
  white-space: pre-wrap;
  color: $text-secondary;
  font-size: $font-size-md;
  line-height: 1.7;
}

.tc-no-sub {
  font-size: $font-size-sm;
  color: $text-muted;
}

.chat-flow {
  display: flex;
  flex-direction: column;
  gap: $space-md;
  margin-bottom: $space-lg;
}

.chat-empty {
  text-align: center;
  color: $text-muted;
  padding: $space-xl;
  font-size: $font-size-sm;
}

.chat-bubble {
  max-width: 78%;
  padding: $space-md $space-lg;
  border-radius: $radius-lg;

  &--user {
    align-self: flex-end;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(56, 189, 248, 0.1));
    border: 1px solid rgba(99, 102, 241, 0.2);
  }

  &--ops {
    align-self: flex-start;
    background: $bg-elevated;
    border: 1px solid $border-default;
  }
}

.chat-meta {
  display: flex;
  align-items: center;
  gap: $space-sm;
  margin-bottom: $space-xs;
}

.chat-author {
  font-size: $font-size-sm;
  font-weight: 600;
  color: $text-secondary;
}

.chat-time {
  font-size: $font-size-xs;
  color: $text-muted;
}

.chat-content {
  font-size: $font-size-md;
  color: $text-primary;
  white-space: pre-wrap;
  line-height: 1.6;
}

.reply-box {
  background: $bg-surface;
  border: 1px solid $border-default;
  border-radius: $radius-lg;
  padding: $space-lg;
}

.reply-actions {
  margin-top: $space-md;
  display: flex;
  justify-content: flex-end;
}

.pager {
  margin-top: $space-xl;
  display: flex;
  justify-content: flex-end;
}
</style>
