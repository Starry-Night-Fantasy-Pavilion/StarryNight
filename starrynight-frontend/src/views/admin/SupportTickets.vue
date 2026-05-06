<template>
  <div class="admin-tickets page-container">
    <template v-if="!detailId">
      <div class="page-header">
        <h1>工单管理</h1>
        <p class="page-header__lead">处理用户提交的反馈、申诉与问题工单。</p>
      </div>

      <!-- 统计卡片 -->
      <div class="stat-row">
        <div class="stat-card">
          <span class="stat-val">{{ stats.openCount }}</span>
          <span class="stat-label">待处理 / 处理中</span>
        </div>
      </div>

      <!-- 筛选栏 -->
      <el-card shadow="never" class="filter-card">
        <div class="filter-row">
          <el-select v-model="filterStatus" clearable placeholder="全部状态" @change="reload" style="width:130px">
            <el-option label="待处理" value="OPEN" />
            <el-option label="处理中" value="IN_PROGRESS" />
            <el-option label="已解决" value="RESOLVED" />
            <el-option label="已关闭" value="CLOSED" />
          </el-select>
          <el-select v-model="filterCategory" clearable placeholder="全部分类" @change="reload" style="width:130px">
            <el-option v-for="c in categoryOptions" :key="c.value" :label="c.label" :value="c.value" />
          </el-select>
          <el-input
            v-model="keyword"
            placeholder="搜索工单号/标题"
            clearable
            style="width:220px"
            @keyup.enter="reload"
            @clear="reload"
          />
          <el-button type="primary" @click="reload">搜索</el-button>
          <el-button :loading="tableLoading" @click="reload" style="margin-left:auto">刷新</el-button>
        </div>
      </el-card>

      <el-card shadow="never">
        <el-table :data="rows" stripe v-loading="tableLoading" @row-click="openDetail">
          <el-table-column label="工单号" prop="ticketNo" width="150" />
          <el-table-column label="状态" width="90">
            <template #default="{ row }">
              <el-tag :type="statusType(row.status)" size="small">{{ statusLabel(row.status) }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column label="优先级" width="80">
            <template #default="{ row }">
              <el-tag :type="priorityType(row.priority)" size="small">{{ priorityLabel(row.priority) }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column label="分类" width="90">
            <template #default="{ row }">{{ categoryLabel(row.category) }}</template>
          </el-table-column>
          <el-table-column label="标题" min-width="200" show-overflow-tooltip prop="title" />
          <el-table-column label="用户" width="110" prop="username" show-overflow-tooltip />
          <el-table-column label="提交时间" width="150">
            <template #default="{ row }">{{ fmtTime(row.createTime) }}</template>
          </el-table-column>
          <el-table-column label="操作" width="80">
            <template #default="{ row }">
              <el-button link type="primary" @click.stop="openDetail(row)">处理</el-button>
            </template>
          </el-table-column>
        </el-table>

        <div class="pagination">
          <el-pagination
            v-model:current-page="page"
            v-model:page-size="pageSize"
            :total="total"
            layout="total, prev, pager, next"
            @current-change="loadList"
          />
        </div>
      </el-card>
    </template>

    <!-- 工单详情/处理 -->
    <template v-else-if="detail">
      <div class="page-header">
        <h1>处理工单</h1>
        <p class="page-header__lead">{{ detail.ticketNo }}</p>
      </div>
      <el-button @click="detailId = null" style="margin-bottom:16px">
        ← 返回列表
      </el-button>

      <div class="detail-layout">
        <!-- 左侧：信息 + 对话流 -->
        <div class="detail-main">
          <el-card shadow="never" class="detail-info-card">
            <div class="detail-tags">
              <el-tag :type="statusType(detail.status)">{{ statusLabel(detail.status) }}</el-tag>
              <el-tag type="info">{{ categoryLabel(detail.category) }}</el-tag>
              <el-tag :type="priorityType(detail.priority)">{{ priorityLabel(detail.priority) }}</el-tag>
            </div>
            <h2 class="detail-title">{{ detail.title }}</h2>
            <p class="detail-meta">
              用户：<strong>{{ detail.username || detail.userId }}</strong>
              &nbsp;|&nbsp;
              提交于 {{ fmtTime(detail.createTime) }}
              <span v-if="detail.assignedToName">&nbsp;|&nbsp; 负责人：{{ detail.assignedToName }}</span>
            </p>
            <div class="detail-content">{{ detail.content }}</div>
          </el-card>

          <div class="chat-flow">
            <div
              v-for="r in detail.replies"
              :key="r.id"
              :class="['chat-bubble', r.authorType === 'USER' ? 'chat-bubble--user' : 'chat-bubble--ops']"
            >
              <div class="chat-meta">
                <span class="chat-author">{{ r.authorType === 'OPS' ? `[客服] ${r.authorName}` : r.authorName }}</span>
                <span v-if="r.internal" class="chat-internal">仅内部可见</span>
                <span class="chat-time">{{ fmtTime(r.createTime) }}</span>
              </div>
              <div class="chat-content">{{ r.content }}</div>
            </div>
            <div v-if="!detail.replies?.length" class="chat-empty">暂无回复</div>
          </div>

          <el-card v-if="detail.status !== 'CLOSED'" shadow="never" class="reply-card">
            <template #header>回复工单</template>
            <el-input
              v-model="replyContent"
              type="textarea"
              :rows="4"
              maxlength="2000"
              show-word-limit
              placeholder="输入回复内容（对用户可见）"
            />
            <div class="reply-opts">
              <el-checkbox v-model="replyInternal">仅内部备注（用户不可见）</el-checkbox>
              <el-button type="primary" :loading="replySubmitting" @click="submitReply">发送回复</el-button>
            </div>
          </el-card>
        </div>

        <!-- 右侧：操作面板 -->
        <div class="detail-side">
          <el-card shadow="never">
            <template #header>工单操作</template>
            <div class="side-section">
              <div class="side-label">修改状态</div>
              <el-select v-model="editStatus" style="width:100%">
                <el-option label="待处理" value="OPEN" />
                <el-option label="处理中" value="IN_PROGRESS" />
                <el-option label="已解决" value="RESOLVED" />
                <el-option label="已关闭" value="CLOSED" />
              </el-select>
            </div>
            <div class="side-section">
              <div class="side-label">优先级</div>
              <el-select v-model="editPriority" style="width:100%">
                <el-option label="低" value="LOW" />
                <el-option label="普通" value="NORMAL" />
                <el-option label="高" value="HIGH" />
                <el-option label="紧急" value="URGENT" />
              </el-select>
            </div>
            <el-button
              type="primary"
              style="width:100%;margin-top:12px"
              :loading="updateSubmitting"
              @click="submitUpdate"
            >保存修改</el-button>

            <el-divider />

            <el-button
              v-if="detail.status !== 'CLOSED'"
              type="danger"
              plain
              style="width:100%"
              @click="closeTicketDialog = true"
            >关闭工单</el-button>
          </el-card>
        </div>
      </div>
    </template>

    <!-- 关闭工单弹窗 -->
    <el-dialog v-model="closeTicketDialog" title="关闭工单" width="420px">
      <el-form>
        <el-form-item label="关闭原因">
          <el-input
            v-model="closeReason"
            type="textarea"
            :rows="3"
            placeholder="请填写关闭原因（可选）"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="closeTicketDialog = false">取消</el-button>
        <el-button type="danger" :loading="closeSubmitting" @click="confirmClose">确认关闭</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, reactive, watch } from 'vue'
import { ElMessage } from 'element-plus'
import {
  adminListTickets,
  adminGetTicket,
  adminUpdateTicket,
  adminReplyTicket,
  adminCloseTicket,
  adminGetTicketStats
} from '@/api/ticket'
import type { TicketItem, TicketStatus } from '@/types/api'

const rows = ref<TicketItem[]>([])
const total = ref(0)
const page = ref(1)
const pageSize = ref(20)
const tableLoading = ref(false)
const filterStatus = ref('')
const filterCategory = ref('')
const keyword = ref('')

const stats = reactive({ openCount: 0 })

const detailId = ref<number | null>(null)
const detail = ref<TicketItem | null>(null)

const editStatus = ref<TicketStatus>('OPEN')
const editPriority = ref('NORMAL')

const replyContent = ref('')
const replyInternal = ref(false)
const replySubmitting = ref(false)

const updateSubmitting = ref(false)

const closeTicketDialog = ref(false)
const closeReason = ref('')
const closeSubmitting = ref(false)

const categoryOptions = [
  { label: 'Bug 报告', value: 'BUG' },
  { label: '账号问题', value: 'ACCOUNT' },
  { label: '充值/计费', value: 'BILLING' },
  { label: '内容问题', value: 'CONTENT' },
  { label: '功能建议', value: 'FEATURE' },
  { label: '其他', value: 'OTHER' }
]

async function loadStats() {
  try {
    const s = await adminGetTicketStats()
    stats.openCount = s.openCount
  } catch {}
}

async function loadList() {
  tableLoading.value = true
  try {
    const res = await adminListTickets({
      status: (filterStatus.value as TicketStatus) || undefined,
      category: filterCategory.value || undefined,
      keyword: keyword.value || undefined,
      page: page.value,
      size: pageSize.value
    })
    rows.value = res.records ?? []
    total.value = Number(res.total ?? 0)
  } finally {
    tableLoading.value = false
  }
}

function reload() {
  page.value = 1
  loadList()
}

async function openDetail(row: TicketItem) {
  detailId.value = row.id
  await refreshDetail()
}

async function refreshDetail() {
  if (!detailId.value) return
  const t = await adminGetTicket(detailId.value)
  detail.value = t
  editStatus.value = t.status
  editPriority.value = t.priority
  replyContent.value = ''
}

async function submitReply() {
  if (!replyContent.value.trim()) {
    ElMessage.warning('请输入回复内容')
    return
  }
  replySubmitting.value = true
  try {
    await adminReplyTicket(detailId.value!, {
      content: replyContent.value,
      internal: replyInternal.value
    })
    ElMessage.success('回复成功')
    await refreshDetail()
    window.dispatchEvent(new Event('admin-badges-refresh'))
  } catch {
    ElMessage.error('回复失败')
  } finally {
    replySubmitting.value = false
  }
}

async function submitUpdate() {
  updateSubmitting.value = true
  try {
    await adminUpdateTicket(detailId.value!, {
      status: editStatus.value,
      priority: editPriority.value as any
    })
    ElMessage.success('已保存')
    await refreshDetail()
    await loadList()
    window.dispatchEvent(new Event('admin-badges-refresh'))
  } catch {
    ElMessage.error('保存失败')
  } finally {
    updateSubmitting.value = false
  }
}

async function confirmClose() {
  closeSubmitting.value = true
  try {
    await adminCloseTicket(detailId.value!, closeReason.value)
    closeTicketDialog.value = false
    closeReason.value = ''
    ElMessage.success('工单已关闭')
    await refreshDetail()
    await loadList()
    window.dispatchEvent(new Event('admin-badges-refresh'))
  } catch {
    ElMessage.error('操作失败')
  } finally {
    closeSubmitting.value = false
  }
}

function statusLabel(s: string) {
  const m: Record<string, string> = {
    OPEN: '待处理', IN_PROGRESS: '处理中', RESOLVED: '已解决', CLOSED: '已关闭'
  }
  return m[s] ?? s
}
function statusType(s: string) {
  const m: Record<string, string> = {
    OPEN: 'warning', IN_PROGRESS: 'primary', RESOLVED: 'success', CLOSED: 'info'
  }
  return m[s] ?? ''
}
function categoryLabel(c: string) {
  const m: Record<string, string> = {
    BUG: 'Bug', ACCOUNT: '账号', BILLING: '计费', CONTENT: '内容', FEATURE: '功能建议', OTHER: '其他'
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

onMounted(() => {
  loadStats()
  loadList()
})
</script>

<style lang="scss" scoped>
.stat-row {
  display: flex;
  gap: $space-lg;
  margin-bottom: $space-lg;
}

.stat-card {
  padding: $space-lg $space-xl;
  background: $bg-surface;
  border: 1px solid $border-default;
  border-radius: $radius-lg;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: $space-xs;
  min-width: 140px;
}

.stat-val {
  font-size: $font-size-3xl;
  font-weight: 700;
  color: $primary-color;
  line-height: 1.2;
}

.stat-label {
  font-size: $font-size-sm;
  color: $text-muted;
}

.filter-card {
  margin-bottom: $space-lg;
}

.filter-row {
  display: flex;
  align-items: center;
  gap: $space-sm;
  flex-wrap: wrap;
}

.detail-layout {
  display: flex;
  gap: $space-lg;
  align-items: flex-start;
}

.detail-main {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: $space-lg;
}

.detail-side {
  width: 240px;
  flex-shrink: 0;
}

.detail-info-card {}

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
  font-size: $font-size-sm;
  color: $text-muted;
  margin-bottom: $space-md;
}

.detail-content {
  white-space: pre-wrap;
  color: $text-secondary;
  font-size: $font-size-md;
  line-height: 1.7;
  padding: $space-md;
  background: $bg-canvas;
  border-radius: $radius-md;
  border: 1px solid $border-subtle;
}

.chat-flow {
  display: flex;
  flex-direction: column;
  gap: $space-md;
}

.chat-empty {
  text-align: center;
  color: $text-muted;
  padding: $space-lg;
  font-size: $font-size-sm;
}

.chat-bubble {
  max-width: 85%;
  padding: $space-md $space-lg;
  border-radius: $radius-lg;

  &--user {
    align-self: flex-end;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.12), rgba(56, 189, 248, 0.08));
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
  flex-wrap: wrap;
}

.chat-author {
  font-size: $font-size-sm;
  font-weight: 600;
  color: $text-secondary;
}

.chat-time {
  font-size: $font-size-xs;
  color: $text-muted;
  margin-left: auto;
}

.chat-internal {
  font-size: $font-size-xs;
  color: $warning-color;
  background: $warning-ghost;
  padding: 1px 6px;
  border-radius: $radius-sm;
}

.chat-content {
  font-size: $font-size-md;
  color: $text-primary;
  white-space: pre-wrap;
  line-height: 1.6;
}

.reply-card {}

.reply-opts {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: $space-md;
}

.side-section {
  margin-bottom: $space-md;
}

.side-label {
  font-size: $font-size-sm;
  font-weight: 600;
  color: $text-muted;
  margin-bottom: $space-xs;
}
</style>
