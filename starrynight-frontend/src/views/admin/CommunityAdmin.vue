<template>
  <div class="admin-community page-container">
    <div class="page-header">
      <h1>{{ pageTitle }}</h1>
    </div>

    <el-alert
      type="info"
      :closable="false"
      class="community-tip"
      title="敏感词策略自动审核：高风险自动驳回/拦截；命中复核级进入下方「审核工单」队列，通过后对用户可见。"
    />

    <el-tabs v-model="activeTab" class="community-tabs" @tab-change="onTabChange">
      <el-tab-pane label="审核工单" name="workorders">
        <el-card shadow="never">
          <div class="review-toolbar">
            <span class="wo-hint">待审帖子与待审评论合并队列，按时间倒序</span>
            <el-button
              v-if="woSelectedRows.length"
              type="primary"
              :loading="woBatchApproveSubmitting"
              @click="confirmBatchWoApprove"
            >
              批量通过（{{ woSelectedRows.length }}）
            </el-button>
            <el-button
              v-if="woSelectedRows.length"
              type="danger"
              :loading="woBatchRejectSubmitting"
              @click="openBatchWoReject"
            >
              批量驳回（{{ woSelectedRows.length }}）
            </el-button>
            <el-button :loading="woLoading" @click="loadWorkOrders">刷新</el-button>
          </div>
          <el-table
            :data="woRows"
            stripe
            v-loading="woLoading"
            class="review-table"
            @selection-change="(rows: CommunityWorkOrderItem[]) => (woSelectedRows.value = rows)"
          >
            <el-table-column type="selection" width="44" />
            <el-table-column label="类型" width="88">
              <template #default="{ row }">
                <el-tag :type="row.kind === 'POST' ? 'primary' : 'success'" size="small">
                  {{ row.kind === 'POST' ? '帖子' : '评论' }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="username" label="用户" width="120" show-overflow-tooltip />
            <el-table-column prop="postId" label="帖子ID" width="88" />
            <el-table-column label="标题/摘要" min-width="160" show-overflow-tooltip>
              <template #default="{ row }">
                <span v-if="row.kind === 'POST'">{{ row.titleSnippet || '—' }}</span>
                <span v-else class="text-muted">评论 · 帖子 {{ row.postId }}</span>
              </template>
            </el-table-column>
            <el-table-column label="内容预览" min-width="200" show-overflow-tooltip>
              <template #default="{ row }">{{ row.contentPreview || '—' }}</template>
            </el-table-column>
            <el-table-column label="系统说明" min-width="140" show-overflow-tooltip>
              <template #default="{ row }">{{ row.reasonNote || '—' }}</template>
            </el-table-column>
            <el-table-column prop="createTime" label="时间" width="170" />
            <el-table-column label="操作" width="200" fixed="right">
              <template #default="{ row }">
                <el-button
                  type="primary"
                  link
                  :loading="woActionKey === actionKey(row, 'approve')"
                  @click="onWoApprove(row)"
                >
                  通过
                </el-button>
                <el-button type="danger" link @click="openWoReject(row)">驳回</el-button>
              </template>
            </el-table-column>
          </el-table>
          <div class="pager">
            <el-pagination
              v-model:current-page="woPage"
              v-model:page-size="woSize"
              :total="woTotal"
              :page-sizes="[10, 20, 50]"
              layout="total, sizes, prev, pager, next"
              @current-change="loadWorkOrders"
              @size-change="loadWorkOrders"
            />
          </div>
        </el-card>

        <el-dialog v-model="woRejectVisible" title="驳回" width="480px" @closed="woRejectReason = ''">
          <el-input
            v-model="woRejectReason"
            type="textarea"
            :rows="4"
            maxlength="500"
            show-word-limit
            placeholder="可选"
          />
          <template #footer>
            <el-button @click="woRejectVisible = false">取消</el-button>
            <el-button type="danger" :loading="woRejectSubmitting" @click="confirmWoReject">确认驳回</el-button>
          </template>
        </el-dialog>

        <el-dialog v-model="woBatchRejectVisible" title="批量驳回" width="480px" @closed="woBatchRejectReason = ''">
          <el-input
            v-model="woBatchRejectReason"
            type="textarea"
            :rows="4"
            maxlength="500"
            show-word-limit
            placeholder="可选"
          />
          <template #footer>
            <el-button @click="woBatchRejectVisible = false">取消</el-button>
            <el-button type="danger" :loading="woBatchRejectSubmitting" @click="confirmBatchWoReject">确认驳回</el-button>
          </template>
        </el-dialog>
      </el-tab-pane>
      <el-tab-pane label="内容审核" name="review">
        <el-card shadow="never">
          <div class="review-toolbar">
            <el-select
              v-model="auditFilter"
              clearable
              placeholder="审核状态"
              style="width: 160px"
              @change="loadPosts"
            >
              <el-option label="待审" :value="0" />
              <el-option label="已通过" :value="1" />
              <el-option label="已驳回" :value="2" />
            </el-select>
            <el-button :loading="loading" @click="loadPosts">刷新</el-button>
          </div>
          <el-table :data="rows" stripe v-loading="loading" class="review-table">
            <el-table-column prop="id" label="ID" width="72" />
            <el-table-column prop="authorUsername" label="用户" width="120" show-overflow-tooltip />
            <el-table-column prop="title" label="标题" min-width="140" show-overflow-tooltip />
            <el-table-column label="正文" min-width="200" show-overflow-tooltip>
              <template #default="{ row }">{{ row.content }}</template>
            </el-table-column>
            <el-table-column label="审核" width="100">
              <template #default="{ row }">
                <el-tag :type="auditTagType(row.auditStatus)">{{ auditLabel(row.auditStatus) }}</el-tag>
              </template>
            </el-table-column>
            <el-table-column label="上架" width="88">
              <template #default="{ row }">
                <span>{{ row.onlineStatus === 1 ? '是' : '否' }}</span>
              </template>
            </el-table-column>
            <el-table-column prop="createTime" label="提交时间" width="170" />
            <el-table-column label="操作" width="220" fixed="right">
              <template #default="{ row }">
                <el-button
                  v-if="row.auditStatus === 0"
                  type="primary"
                  link
                  :loading="actionId === row.id && actionKind === 'approve'"
                  @click="onApprove(row)"
                >
                  通过
                </el-button>
                <el-button
                  v-if="row.auditStatus === 0"
                  type="danger"
                  link
                  @click="openReject(row)"
                >
                  驳回
                </el-button>
                <el-button
                  v-if="row.auditStatus === 1 && row.onlineStatus === 1"
                  type="warning"
                  link
                  :loading="actionId === row.id && actionKind === 'down'"
                  @click="onTakeDown(row)"
                >
                  下架
                </el-button>
              </template>
            </el-table-column>
          </el-table>
          <div class="pager">
            <el-pagination
              v-model:current-page="page"
              v-model:page-size="size"
              :total="total"
              :page-sizes="[10, 20, 50]"
              layout="total, sizes, prev, pager, next"
              @current-change="loadPosts"
              @size-change="loadPosts"
            />
          </div>
        </el-card>

        <el-dialog v-model="rejectVisible" title="驳回原因" width="480px" @closed="rejectReason = ''">
          <el-input v-model="rejectReason" type="textarea" :rows="4" maxlength="500" show-word-limit placeholder="可选，将展示给运营侧记录" />
          <template #footer>
            <el-button @click="rejectVisible = false">取消</el-button>
            <el-button type="danger" :loading="rejectSubmitting" @click="confirmReject">确认驳回</el-button>
          </template>
        </el-dialog>
      </el-tab-pane>
      <el-tab-pane label="评论巡查" name="comments">
        <el-card shadow="never">
          <div class="review-toolbar">
            <el-select
              v-model="commentAuditFilter"
              placeholder="审核状态"
              style="width: 130px"
              @change="loadCommentsTab"
            >
              <el-option label="全部" value="" />
              <el-option label="待审" :value="0" />
              <el-option label="已通过" :value="1" />
              <el-option label="已驳回" :value="2" />
            </el-select>
            <el-input
              v-model="commentPostId"
              clearable
              placeholder="帖子 ID（可选）"
              style="width: 140px"
              @keyup.enter="loadCommentsTab"
            />
            <el-input
              v-model="commentKeyword"
              clearable
              placeholder="正文关键词"
              style="width: 200px"
              @keyup.enter="loadCommentsTab"
            />
            <el-button :loading="commentLoading" @click="loadCommentsTab">查询</el-button>
          </div>
          <el-table :data="commentRows" stripe v-loading="commentLoading" class="review-table">
            <el-table-column prop="id" label="评论ID" width="88" />
            <el-table-column prop="postId" label="帖子ID" width="88" />
            <el-table-column prop="postTitle" label="帖子标题" min-width="120" show-overflow-tooltip />
            <el-table-column prop="authorUsername" label="用户" width="110" show-overflow-tooltip />
            <el-table-column label="审核" width="96">
              <template #default="{ row }">
                <el-tag :type="commentAuditTagType(row.auditStatus)" size="small">
                  {{ commentAuditLabel(row.auditStatus) }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column label="内容" min-width="200" show-overflow-tooltip>
              <template #default="{ row }">{{ row.content }}</template>
            </el-table-column>
            <el-table-column label="备注" min-width="120" show-overflow-tooltip>
              <template #default="{ row }">{{ row.moderationNote || '—' }}</template>
            </el-table-column>
            <el-table-column prop="parentId" label="父评论" width="88">
              <template #default="{ row }">{{ row.parentId ?? '—' }}</template>
            </el-table-column>
            <el-table-column prop="createTime" label="时间" width="170" />
            <el-table-column label="操作" width="220" fixed="right">
              <template #default="{ row }">
                <el-button
                  v-if="row.auditStatus === 0"
                  type="primary"
                  link
                  :loading="cmtActionId === row.id && cmtActionKind === 'approve'"
                  @click="onApproveCommentRow(row)"
                >
                  通过
                </el-button>
                <el-button
                  v-if="row.auditStatus === 0"
                  type="danger"
                  link
                  @click="openCmtReject(row)"
                >
                  驳回
                </el-button>
                <el-button
                  type="danger"
                  link
                  :loading="deletingCommentId === row.id"
                  @click="onDeleteComment(row)"
                >
                  删除
                </el-button>
              </template>
            </el-table-column>
          </el-table>
          <div class="pager">
            <el-pagination
              v-model:current-page="commentPage"
              v-model:page-size="commentSize"
              :total="commentTotal"
              :page-sizes="[10, 20, 50]"
              layout="total, sizes, prev, pager, next"
              @current-change="loadCommentsTab"
              @size-change="loadCommentsTab"
            />
          </div>
        </el-card>

        <el-dialog v-model="cmtRejectVisible" title="驳回评论" width="480px" @closed="cmtRejectReason = ''">
          <el-input
            v-model="cmtRejectReason"
            type="textarea"
            :rows="4"
            maxlength="500"
            show-word-limit
            placeholder="可选"
          />
          <template #footer>
            <el-button @click="cmtRejectVisible = false">取消</el-button>
            <el-button type="danger" :loading="cmtRejectSubmitting" @click="confirmCmtReject">确认驳回</el-button>
          </template>
        </el-dialog>
      </el-tab-pane>
      <el-tab-pane label="话题与版块" name="topics">
        <el-card shadow="never">
          <el-empty description="话题、版块、置顶规则等将在此配置（后续迭代）" />
        </el-card>
      </el-tab-pane>
      <el-tab-pane label="举报与风控" name="reports">
        <el-card shadow="never">
          <el-alert
            type="info"
            :closable="false"
            title="敏感词用于社区自动审核：level=1 → 进入待审队列；level≥2 → 自动驳回/拦截。"
            class="community-tip"
          />

          <div class="review-toolbar">
            <el-select
              v-model="swLevelFilter"
              clearable
              placeholder="等级（可选）"
              style="width: 160px"
              @change="loadSensitiveWords"
            >
              <el-option label="level=1（待复核）" :value="1" />
              <el-option label="level=2（拦截）" :value="2" />
              <el-option label="level=3（拦截）" :value="3" />
            </el-select>
            <el-button :loading="swLoading" @click="loadSensitiveWords">刷新</el-button>
            <el-button type="primary" @click="openSwCreate">新增敏感词</el-button>
          </div>

          <el-table :data="swRows" stripe v-loading="swLoading" class="review-table">
            <el-table-column prop="id" label="ID" width="80" />
            <el-table-column prop="word" label="词" min-width="160" show-overflow-tooltip />
            <el-table-column prop="level" label="等级" width="90" />
            <el-table-column label="启用" width="90">
              <template #default="{ row }">
                <el-switch
                  :model-value="row.enabled === 1"
                  @change="(v:boolean) => onToggleSwEnabled(row, v)"
                />
              </template>
            </el-table-column>
            <el-table-column label="操作" width="180" fixed="right">
              <template #default="{ row }">
                <el-button type="primary" link @click="openSwEdit(row)">编辑</el-button>
                <el-button type="danger" link @click="onDeleteSw(row)">删除</el-button>
              </template>
            </el-table-column>
          </el-table>

          <el-dialog v-model="swDialogVisible" :title="swEditingId ? '编辑敏感词' : '新增敏感词'" width="520px">
            <el-form :model="swForm" label-width="90px">
              <el-form-item label="词">
                <el-input v-model="swForm.word" maxlength="100" show-word-limit placeholder="例如：违法、诈骗…" />
              </el-form-item>
              <el-form-item label="等级">
                <el-input-number v-model="swForm.level" :min="1" :max="9" />
                <span class="text-muted" style="margin-left: 8px">1=待复核；≥2=拦截</span>
              </el-form-item>
              <el-form-item label="启用">
                <el-switch v-model="swFormEnabled" />
              </el-form-item>
            </el-form>
            <template #footer>
              <el-button @click="swDialogVisible = false">取消</el-button>
              <el-button type="primary" :loading="swSubmitting" @click="submitSw">保存</el-button>
            </template>
          </el-dialog>

          <el-divider content-position="left">举报工单</el-divider>

          <div class="review-toolbar">
            <el-select
              v-model="rptStatusFilter"
              clearable
              placeholder="状态（可选）"
              style="width: 160px"
              @change="loadReports"
            >
              <el-option label="待处理" :value="0" />
              <el-option label="已处理" :value="1" />
              <el-option label="已忽略" :value="2" />
            </el-select>
            <el-button
              v-if="rptSelectedRows.length"
              type="info"
              @click="openBatchIgnore"
            >
              批量忽略（{{ rptSelectedRows.length }}）
            </el-button>
            <el-button
              v-if="rptSelectedRows.length"
              type="primary"
              @click="openBatchResolve"
            >
              批量处理（{{ rptSelectedRows.length }}）
            </el-button>
            <el-button :loading="rptLoading" @click="loadReports">刷新</el-button>
          </div>

          <el-table
            :data="rptRows"
            stripe
            v-loading="rptLoading"
            class="review-table"
            @selection-change="(rows: AdminCommunityReportItem[]) => (rptSelectedRows.value = rows)"
          >
            <el-table-column type="selection" width="44" />
            <el-table-column prop="id" label="ID" width="80" />
            <el-table-column label="类型" width="88">
              <template #default="{ row }">
                <el-tag :type="row.kind === 'POST' ? 'primary' : 'success'" size="small">
                  {{ row.kind === 'POST' ? '帖子' : '评论' }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="reporterUsername" label="举报人" width="110" show-overflow-tooltip />
            <el-table-column prop="targetUsername" label="被举报人" width="110" show-overflow-tooltip />
            <el-table-column prop="postId" label="帖子ID" width="88" />
            <el-table-column prop="commentId" label="评论ID" width="88">
              <template #default="{ row }">{{ row.commentId ?? '—' }}</template>
            </el-table-column>
            <el-table-column prop="reason" label="原因" width="140" show-overflow-tooltip />
            <el-table-column label="内容预览" min-width="200" show-overflow-tooltip>
              <template #default="{ row }">{{ row.contentPreview || '—' }}</template>
            </el-table-column>
            <el-table-column label="说明" min-width="160" show-overflow-tooltip>
              <template #default="{ row }">{{ row.detail || '—' }}</template>
            </el-table-column>
            <el-table-column label="状态" width="96">
              <template #default="{ row }">
                <el-tag :type="row.status === 0 ? 'warning' : row.status === 1 ? 'success' : 'info'" size="small">
                  {{ row.status === 0 ? '待处理' : row.status === 1 ? '已处理' : '已忽略' }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="createTime" label="提交时间" width="170" />
            <el-table-column label="操作" width="260" fixed="right">
              <template #default="{ row }">
                <template v-if="row.status === 0">
                  <el-button type="primary" link @click="openResolve(row)">处理</el-button>
                  <el-button type="info" link @click="openIgnore(row)">忽略</el-button>
                </template>
                <span v-else class="text-muted">{{ row.handleAction || '—' }}</span>
              </template>
            </el-table-column>
          </el-table>

          <div class="pager">
            <el-pagination
              v-model:current-page="rptPage"
              v-model:page-size="rptSize"
              :total="rptTotal"
              :page-sizes="[10, 20, 50]"
              layout="total, sizes, prev, pager, next"
              @current-change="loadReports"
              @size-change="loadReports"
            />
          </div>

          <el-dialog v-model="rptNoteDialogVisible" :title="rptNoteMode === 'ignore' ? '忽略举报' : '处理举报'" width="520px">
            <div v-if="rptNoteMode === 'resolve'" style="margin-bottom: 12px">
              <el-select v-model="rptResolveAction" style="width: 220px">
                <el-option label="不做内容动作（仅标记已处理）" value="NONE" />
                <el-option label="下架帖子" value="TAKE_DOWN_POST" />
                <el-option label="删除评论" value="DELETE_COMMENT" />
              </el-select>
            </div>
            <el-input
              v-model="rptNoteText"
              type="textarea"
              :rows="4"
              maxlength="500"
              show-word-limit
              placeholder="处理备注（可选）"
            />
            <template #footer>
              <el-button @click="rptNoteDialogVisible = false">取消</el-button>
              <el-button
                type="primary"
                :loading="rptNoteSubmitting"
                @click="rptNoteTarget ? confirmHandleReport() : confirmHandleBatchReport()"
              >
                确认
              </el-button>
            </template>
          </el-dialog>
        </el-card>
      </el-tab-pane>
    </el-tabs>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import type {
  AdminCommunityCommentItem,
  AdminCommunityPostItem,
  AdminCommunityReportItem,
  AiSensitiveWordItem,
  CommunityWorkOrderItem
} from '@/types/api'
import {
  approveCommunityComment,
  approveCommunityPost,
  deleteAdminCommunityComment,
  listAdminCommunityComments,
  listAdminCommunityPosts,
  ignoreAdminCommunityReport,
  listAdminCommunityReports,
  listCommunityWorkOrders,
  rejectCommunityComment,
  rejectCommunityPost,
  resolveAdminCommunityReport,
  takeDownCommunityPost
} from '@/api/communityAdmin'
import {
  createAiSensitiveWord,
  deleteAiSensitiveWord,
  listAiSensitiveWords,
  updateAiSensitiveWord
} from '@/api/aiConfig'

const route = useRoute()

const pageTitle = computed(() => {
  const p = String(route.path || '').toLowerCase()
  return p.endsWith('/risk-control') ? '举报与风控' : '社区管理'
})

function initialTabFromRoute(): string {
  const p = (route.path || '').toLowerCase()
  if (p.endsWith('/risk-control')) return 'reports'
  const q = String(route.query.tab || '').trim()
  if (q === 'reports' || q === 'workorders' || q === 'review' || q === 'comments') return q
  return 'workorders'
}

const activeTab = ref(initialTabFromRoute())

const woLoading = ref(false)
const woRows = ref<CommunityWorkOrderItem[]>([])
const woSelectedRows = ref<CommunityWorkOrderItem[]>([])
const woPage = ref(1)
const woSize = ref(20)
const woTotal = ref(0)
const woActionKey = ref<string | null>(null)
const woRejectVisible = ref(false)
const woRejectReason = ref('')
const woRejectSubmitting = ref(false)
const woRejectTarget = ref<CommunityWorkOrderItem | null>(null)
const woBatchApproveSubmitting = ref(false)
const woBatchRejectVisible = ref(false)
const woBatchRejectReason = ref('')
const woBatchRejectSubmitting = ref(false)
const loading = ref(false)
const rows = ref<AdminCommunityPostItem[]>([])
const auditFilter = ref<number | undefined>(0)
const page = ref(1)
const size = ref(10)
const total = ref(0)
const actionId = ref<number | null>(null)
const actionKind = ref<'approve' | 'down' | null>(null)
const rejectVisible = ref(false)
const rejectReason = ref('')
const rejectSubmitting = ref(false)
const rejectTarget = ref<AdminCommunityPostItem | null>(null)

const commentRows = ref<AdminCommunityCommentItem[]>([])
const commentLoading = ref(false)
const commentPage = ref(1)
const commentSize = ref(20)
const commentTotal = ref(0)
const commentPostId = ref('')
const commentKeyword = ref('')
/** 空字符串表示不按审核状态筛选 */
const commentAuditFilter = ref<string | number>('')
const deletingCommentId = ref<number | null>(null)
const cmtActionId = ref<number | null>(null)
const cmtActionKind = ref<'approve' | null>(null)
const cmtRejectVisible = ref(false)
const cmtRejectReason = ref('')
const cmtRejectSubmitting = ref(false)
const cmtRejectTarget = ref<AdminCommunityCommentItem | null>(null)

const swLoading = ref(false)
const swRows = ref<AiSensitiveWordItem[]>([])
const swLevelFilter = ref<number | undefined>(undefined)
const swDialogVisible = ref(false)
const swSubmitting = ref(false)
const swEditingId = ref<number | null>(null)
const swForm = ref<{ word: string; level: number; enabled: number }>({ word: '', level: 1, enabled: 1 })
const swFormEnabled = computed({
  get: () => swForm.value.enabled === 1,
  set: (v: boolean) => {
    swForm.value.enabled = v ? 1 : 0
  }
})

const rptLoading = ref(false)
const rptRows = ref<AdminCommunityReportItem[]>([])
const rptStatusFilter = ref<number | undefined>(0)
const rptPage = ref(1)
const rptSize = ref(20)
const rptTotal = ref(0)
const rptActionKey = ref<string | null>(null)
const rptNoteDialogVisible = ref(false)
const rptNoteSubmitting = ref(false)
const rptNoteText = ref('')
const rptNoteMode = ref<'ignore' | 'resolve'>('resolve')
const rptNoteTarget = ref<AdminCommunityReportItem | null>(null)
const rptResolveAction = ref<'NONE' | 'TAKE_DOWN_POST' | 'DELETE_COMMENT'>('NONE')
const rptSelectedRows = ref<AdminCommunityReportItem[]>([])

function auditLabel(s: number) {
  if (s === 1) return '已通过'
  if (s === 2) return '已驳回'
  return '待审'
}

function auditTagType(s: number) {
  if (s === 1) return 'success'
  if (s === 2) return 'danger'
  return 'warning'
}

function commentAuditLabel(s: number | undefined) {
  if (s === 1) return '已通过'
  if (s === 2) return '已驳回'
  if (s === 0) return '待审'
  return '—'
}

function commentAuditTagType(s: number | undefined) {
  if (s === 1) return 'success'
  if (s === 2) return 'danger'
  if (s === 0) return 'warning'
  return 'info'
}

function actionKey(row: CommunityWorkOrderItem, action: 'approve') {
  return `${row.kind}-${row.targetId}-${action}`
}

async function loadWorkOrders() {
  woLoading.value = true
  try {
    const data = await listCommunityWorkOrders({
      page: woPage.value,
      size: woSize.value
    })
    woRows.value = data.records || []
    woTotal.value = data.total ?? 0
    woSelectedRows.value = []
  } catch {
    woRows.value = []
    woTotal.value = 0
    woSelectedRows.value = []
  } finally {
    woLoading.value = false
  }
}

async function onWoApprove(row: CommunityWorkOrderItem) {
  try {
    await ElMessageBox.confirm(
      row.kind === 'POST' ? '确认通过该帖？' : '确认通过该评论？通过后对用户可见。',
      '通过审核',
      { type: 'warning', confirmButtonText: '通过', cancelButtonText: '取消' }
    )
  } catch {
    return
  }
  const key = actionKey(row, 'approve')
  woActionKey.value = key
  try {
    if (row.kind === 'POST') {
      await approveCommunityPost(row.targetId)
    } else {
      await approveCommunityComment(row.targetId)
    }
    ElMessage.success('已通过')
    await loadWorkOrders()
    refreshBadgesNow()
  } finally {
    woActionKey.value = null
  }
}

function openWoReject(row: CommunityWorkOrderItem) {
  woRejectTarget.value = row
  woRejectReason.value = ''
  woRejectVisible.value = true
}

async function confirmWoReject() {
  const row = woRejectTarget.value
  if (!row) return
  woRejectSubmitting.value = true
  try {
    const reason = woRejectReason.value.trim() || undefined
    if (row.kind === 'POST') {
      await rejectCommunityPost(row.targetId, reason)
    } else {
      await rejectCommunityComment(row.targetId, reason)
    }
    ElMessage.success('已驳回')
    woRejectVisible.value = false
    await loadWorkOrders()
    refreshBadgesNow()
  } finally {
    woRejectSubmitting.value = false
  }
}

function openBatchWoReject() {
  woBatchRejectReason.value = ''
  woBatchRejectVisible.value = true
}

async function confirmBatchWoApprove() {
  const rows = woSelectedRows.value
  if (!rows.length) return
  try {
    await ElMessageBox.confirm(`确认批量通过 ${rows.length} 条？`, '批量通过', {
      type: 'warning',
      confirmButtonText: '通过',
      cancelButtonText: '取消'
    })
  } catch {
    return
  }
  woBatchApproveSubmitting.value = true
  try {
    for (const r of rows) {
      if (r.kind === 'POST') {
        await approveCommunityPost(r.targetId)
      } else {
        await approveCommunityComment(r.targetId)
      }
    }
    ElMessage.success(`已批量通过 ${rows.length} 条`)
    await loadWorkOrders()
    refreshBadgesNow()
  } finally {
    woBatchApproveSubmitting.value = false
  }
}

async function confirmBatchWoReject() {
  const rows = woSelectedRows.value
  if (!rows.length) return
  woBatchRejectSubmitting.value = true
  try {
    const reason = woBatchRejectReason.value.trim() || undefined
    for (const r of rows) {
      if (r.kind === 'POST') {
        await rejectCommunityPost(r.targetId, reason)
      } else {
        await rejectCommunityComment(r.targetId, reason)
      }
    }
    ElMessage.success(`已批量驳回 ${rows.length} 条`)
    woBatchRejectVisible.value = false
    await loadWorkOrders()
    refreshBadgesNow()
  } finally {
    woBatchRejectSubmitting.value = false
  }
}

async function loadPosts() {
  loading.value = true
  try {
    const data = await listAdminCommunityPosts({
      auditStatus: auditFilter.value,
      page: page.value,
      size: size.value
    })
    rows.value = data.records || []
    total.value = data.total ?? 0
  } catch {
    rows.value = []
  } finally {
    loading.value = false
  }
}

async function onApprove(row: AdminCommunityPostItem) {
  try {
    await ElMessageBox.confirm('确认通过该帖？通过后将对所有用户可见（未下架时）。', '通过审核', {
      type: 'warning',
      confirmButtonText: '通过',
      cancelButtonText: '取消'
    })
  } catch {
    return
  }
  actionId.value = row.id
  actionKind.value = 'approve'
  try {
    await approveCommunityPost(row.id)
    ElMessage.success('已通过')
    await loadPosts()
    refreshBadgesNow()
  } finally {
    actionId.value = null
    actionKind.value = null
  }
}

function openReject(row: AdminCommunityPostItem) {
  rejectTarget.value = row
  rejectReason.value = ''
  rejectVisible.value = true
}

async function confirmReject() {
  const row = rejectTarget.value
  if (!row) return
  rejectSubmitting.value = true
  try {
    await rejectCommunityPost(row.id, rejectReason.value.trim() || undefined)
    ElMessage.success('已驳回')
    rejectVisible.value = false
    await loadPosts()
    refreshBadgesNow()
  } finally {
    rejectSubmitting.value = false
  }
}

function onTabChange(name: string | number) {
  if (name === 'workorders') {
    loadWorkOrders()
  }
  if (name === 'review') {
    loadPosts()
  }
  if (name === 'comments') {
    loadCommentsTab()
  }
  if (name === 'reports') {
    loadSensitiveWords()
    loadReports()
  }
}

function parseOptionalPostId(): number | undefined {
  const raw = commentPostId.value.trim()
  if (!raw) return undefined
  const n = Number(raw)
  return Number.isFinite(n) && n > 0 ? n : undefined
}

function parseCommentAuditParam(): number | undefined {
  const v = commentAuditFilter.value
  if (v === '' || v === undefined || v === null) return undefined
  const n = Number(v)
  if (!Number.isFinite(n) || (n !== 0 && n !== 1 && n !== 2)) return undefined
  return n
}

async function loadCommentsTab() {
  commentLoading.value = true
  try {
    const data = await listAdminCommunityComments({
      postId: parseOptionalPostId(),
      keyword: commentKeyword.value.trim() || undefined,
      auditStatus: parseCommentAuditParam(),
      page: commentPage.value,
      size: commentSize.value
    })
    commentRows.value = data.records || []
    commentTotal.value = data.total ?? 0
  } catch {
    commentRows.value = []
    commentTotal.value = 0
  } finally {
    commentLoading.value = false
  }
}

async function onApproveCommentRow(row: AdminCommunityCommentItem) {
  try {
    await ElMessageBox.confirm('确认通过该评论？通过后对所有用户可见。', '通过审核', {
      type: 'warning',
      confirmButtonText: '通过',
      cancelButtonText: '取消'
    })
  } catch {
    return
  }
  cmtActionId.value = row.id
  cmtActionKind.value = 'approve'
  try {
    await approveCommunityComment(row.id)
    ElMessage.success('已通过')
    await loadCommentsTab()
    refreshBadgesNow()
  } finally {
    cmtActionId.value = null
    cmtActionKind.value = null
  }
}

function openCmtReject(row: AdminCommunityCommentItem) {
  cmtRejectTarget.value = row
  cmtRejectReason.value = ''
  cmtRejectVisible.value = true
}

async function confirmCmtReject() {
  const row = cmtRejectTarget.value
  if (!row) return
  cmtRejectSubmitting.value = true
  try {
    await rejectCommunityComment(row.id, cmtRejectReason.value.trim() || undefined)
    ElMessage.success('已驳回')
    cmtRejectVisible.value = false
    await loadCommentsTab()
    refreshBadgesNow()
  } finally {
    cmtRejectSubmitting.value = false
  }
}

async function onDeleteComment(row: AdminCommunityCommentItem) {
  try {
    await ElMessageBox.confirm('删除后不可恢复，对应帖子评论数会减 1。', '删除评论', {
      type: 'warning',
      confirmButtonText: '删除',
      cancelButtonText: '取消'
    })
  } catch {
    return
  }
  deletingCommentId.value = row.id
  try {
    await deleteAdminCommunityComment(row.id)
    ElMessage.success('已删除')
    await loadCommentsTab()
  } finally {
    deletingCommentId.value = null
  }
}

async function onTakeDown(row: AdminCommunityPostItem) {
  try {
    await ElMessageBox.confirm('下架后用户端列表不再展示该帖（数据保留）。', '下架', {
      type: 'warning',
      confirmButtonText: '下架',
      cancelButtonText: '取消'
    })
  } catch {
    return
  }
  actionId.value = row.id
  actionKind.value = 'down'
  try {
    await takeDownCommunityPost(row.id)
    ElMessage.success('已下架')
    await loadPosts()
  } finally {
    actionId.value = null
    actionKind.value = null
  }
}

async function loadSensitiveWords() {
  swLoading.value = true
  try {
    const data = await listAiSensitiveWords(swLevelFilter.value)
    swRows.value = data || []
  } catch {
    swRows.value = []
  } finally {
    swLoading.value = false
  }
}

function reportActionKey(row: AdminCommunityReportItem, mode: 'ignore' | 'resolve') {
  return `rpt-${row.id}-${mode}`
}

async function loadReports() {
  rptLoading.value = true
  try {
    const data = await listAdminCommunityReports({
      status: rptStatusFilter.value,
      page: rptPage.value,
      size: rptSize.value
    })
    rptRows.value = data.records || []
    rptTotal.value = data.total ?? 0
  } catch {
    rptRows.value = []
    rptTotal.value = 0
  } finally {
    rptLoading.value = false
  }
}

function refreshBadgesNow() {
  window.dispatchEvent(new Event('admin-badges-refresh'))
}

function openIgnore(row: AdminCommunityReportItem) {
  rptNoteMode.value = 'ignore'
  rptNoteTarget.value = row
  rptNoteText.value = ''
  rptResolveAction.value = 'NONE'
  rptNoteDialogVisible.value = true
}

function openResolve(row: AdminCommunityReportItem) {
  rptNoteMode.value = 'resolve'
  rptNoteTarget.value = row
  rptNoteText.value = ''
  rptResolveAction.value = row.kind === 'POST' ? 'TAKE_DOWN_POST' : 'DELETE_COMMENT'
  rptNoteDialogVisible.value = true
}

async function confirmHandleReport() {
  const row = rptNoteTarget.value
  if (!row) return
  rptNoteSubmitting.value = true
  const key = reportActionKey(row, rptNoteMode.value)
  rptActionKey.value = key
  try {
    const note = rptNoteText.value.trim() || undefined
    if (rptNoteMode.value === 'ignore') {
      await ignoreAdminCommunityReport(row.id, note)
      ElMessage.success('已忽略')
    } else {
      await resolveAdminCommunityReport(row.id, { action: rptResolveAction.value, note })
      ElMessage.success('已处理')
    }
    rptNoteDialogVisible.value = false
    await loadReports()
    refreshBadgesNow()
  } finally {
    rptNoteSubmitting.value = false
    rptActionKey.value = null
  }
}

function openBatchIgnore() {
  const rows = rptSelectedRows.value.filter((r) => r.status === 0)
  if (!rows.length) return
  rptNoteMode.value = 'ignore'
  rptNoteTarget.value = null
  rptNoteText.value = ''
  rptResolveAction.value = 'NONE'
  rptNoteDialogVisible.value = true
}

function openBatchResolve() {
  const rows = rptSelectedRows.value.filter((r) => r.status === 0)
  if (!rows.length) return
  rptNoteMode.value = 'resolve'
  rptNoteTarget.value = null
  rptNoteText.value = ''
  rptResolveAction.value = 'NONE'
  rptNoteDialogVisible.value = true
}

async function confirmHandleBatchReport() {
  const rows = rptSelectedRows.value.filter((r) => r.status === 0)
  if (!rows.length) return
  rptNoteSubmitting.value = true
  try {
    const note = rptNoteText.value.trim() || undefined
    if (rptNoteMode.value === 'ignore') {
      for (const r of rows) {
        await ignoreAdminCommunityReport(r.id, note)
      }
      ElMessage.success(`已忽略 ${rows.length} 条`)
    } else {
      for (const r of rows) {
        await resolveAdminCommunityReport(r.id, { action: rptResolveAction.value, note })
      }
      ElMessage.success(`已处理 ${rows.length} 条`)
    }
    rptNoteDialogVisible.value = false
    rptSelectedRows.value = []
    await loadReports()
    refreshBadgesNow()
  } finally {
    rptNoteSubmitting.value = false
  }
}

function openSwCreate() {
  swEditingId.value = null
  swForm.value = { word: '', level: 1, enabled: 1 }
  swDialogVisible.value = true
}

function openSwEdit(row: AiSensitiveWordItem) {
  swEditingId.value = row.id ?? null
  swForm.value = { word: row.word, level: row.level, enabled: row.enabled }
  swDialogVisible.value = true
}

async function submitSw() {
  const word = swForm.value.word.trim()
  const level = Number(swForm.value.level)
  if (!word) {
    ElMessage.warning('请输入敏感词')
    return
  }
  if (!Number.isFinite(level) || level < 1) {
    ElMessage.warning('等级不合法')
    return
  }
  swSubmitting.value = true
  try {
    const payload: AiSensitiveWordItem = { word, level, enabled: swForm.value.enabled }
    if (swEditingId.value) {
      await updateAiSensitiveWord(swEditingId.value, payload)
    } else {
      await createAiSensitiveWord(payload)
    }
    ElMessage.success('已保存')
    swDialogVisible.value = false
    await loadSensitiveWords()
  } finally {
    swSubmitting.value = false
  }
}

async function onDeleteSw(row: AiSensitiveWordItem) {
  if (!row.id) return
  try {
    await ElMessageBox.confirm(`确认删除敏感词「${row.word}」？`, '删除', {
      type: 'warning',
      confirmButtonText: '删除',
      cancelButtonText: '取消'
    })
  } catch {
    return
  }
  await deleteAiSensitiveWord(row.id)
  ElMessage.success('已删除')
  await loadSensitiveWords()
}

async function onToggleSwEnabled(row: AiSensitiveWordItem, enabled: boolean) {
  if (!row.id) return
  const next = enabled ? 1 : 0
  try {
    await updateAiSensitiveWord(row.id, { word: row.word, level: row.level, enabled: next })
    row.enabled = next
    ElMessage.success(enabled ? '已启用' : '已停用')
  } catch {
    // ignore
  }
}

onMounted(() => {
  if (activeTab.value === 'reports') {
    loadSensitiveWords()
    loadReports()
  } else if (activeTab.value === 'review') {
    loadPosts()
  } else if (activeTab.value === 'comments') {
    loadCommentsTab()
  } else {
    loadWorkOrders()
  }
})

watch(
  () => route.path,
  () => {
    const next = initialTabFromRoute()
    if (next !== activeTab.value) {
      activeTab.value = next
      onTabChange(next)
    }
  }
)
</script>

<style scoped lang="scss">
.community-tip {
  margin-bottom: $space-md;
}

.community-tabs {
  margin-top: $space-sm;
}

.review-toolbar {
  display: flex;
  gap: $space-sm;
  align-items: center;
  margin-bottom: $space-md;
}

.wo-hint {
  flex: 1;
  font-size: 13px;
  color: var(--el-text-color-secondary);
}

.text-muted {
  color: var(--el-text-color-secondary);
}

.review-table {
  width: 100%;
}

.pager {
  margin-top: $space-md;
  display: flex;
  justify-content: flex-end;
}
</style>
