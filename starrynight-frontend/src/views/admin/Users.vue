<template>
  <div class="admin-users page-container">
    <div class="page-header">
      <h1>用户管理</h1>
    </div>

    <div class="page-content">
      <el-card>
        <template #header>
          <div class="card-header">
            <el-input
              v-model="searchKeyword"
              placeholder="搜索用户名/邮箱"
              style="width: 300px"
              clearable
              @keyup.enter="handleSearch"
            >
              <template #prefix>
                <el-icon><Search /></el-icon>
              </template>
            </el-input>
            <el-select v-model="statusFilter" placeholder="状态筛选" clearable style="width: 140px">
              <el-option label="正常" :value="1" />
              <el-option label="禁用" :value="0" />
            </el-select>
            <el-button type="primary" @click="handleSearch">搜索</el-button>
            <el-button type="success" @click="openCreateDialog">新增用户</el-button>
          </div>
        </template>

        <el-table :data="users" stripe v-loading="loading">
          <el-table-column prop="id" label="编号" width="80" />
          <el-table-column prop="username" label="用户名" min-width="120" />
          <el-table-column prop="email" label="邮箱" min-width="160" show-overflow-tooltip />
          <el-table-column prop="phone" label="手机号" width="120" />
          <el-table-column prop="status" label="状态" width="90">
            <template #default="{ row }">
              <el-tag :type="row.status === 1 ? 'success' : 'danger'" size="small">
                {{ row.status === 1 ? '正常' : '禁用' }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column label="会员" width="110">
            <template #default="{ row }">
              <span>{{ memberLevelLabel(row.memberLevel) }}</span>
            </template>
          </el-table-column>
          <el-table-column label="创作点" width="110" align="right">
            <template #default="{ row }">
              {{ formatQuota(row.freeQuota) }}
            </template>
          </el-table-column>
          <el-table-column label="星夜币" width="100" align="right">
            <template #default="{ row }">
              {{ formatStarryCoin(row.platformCurrency) }}
            </template>
          </el-table-column>
          <el-table-column label="注册时间" width="180" class-name="col-create-time">
            <template #default="{ row }">
              <span class="create-time-clip" :title="chinaDateTimeTitle(row.createTime)">
                {{ formatChinaDateTime(row.createTime) }}
              </span>
            </template>
          </el-table-column>
          <el-table-column label="注册IP" width="130" show-overflow-tooltip>
            <template #default="{ row }">
              <span class="ip-clip">{{ row.registerIp || '—' }}</span>
            </template>
          </el-table-column>
          <el-table-column label="最后登录" min-width="200" class-name="col-login-audit">
            <template #default="{ row }">
              <div class="login-audit-cell">
                <div class="login-audit-time">{{ formatChinaDateTime(row.lastLoginTime) }}</div>
                <div class="login-audit-ip">{{ row.lastLoginIp || '—' }}</div>
              </div>
            </template>
          </el-table-column>
          <el-table-column label="操作" width="220" class-name="col-actions">
            <template #default="{ row }">
              <div class="table-actions">
                <el-button type="primary" size="small" plain @click="openDetailDrawer(row)">详情</el-button>
                <el-button
                  size="small"
                  :type="row.status === 1 ? 'danger' : 'success'"
                  plain
                  @click="handleToggleStatus(row)"
                >
                  {{ row.status === 1 ? '禁用' : '启用' }}
                </el-button>
              </div>
            </template>
          </el-table-column>
        </el-table>

        <div class="pagination">
          <el-pagination
            v-model:current-page="currentPage"
            v-model:page-size="pageSize"
            :total="total"
            :page-sizes="[10, 20, 50, 100]"
            layout="total, sizes, prev, pager, next"
            @size-change="handleSizeChange"
            @current-change="handleCurrentChange"
          />
        </div>
      </el-card>
    </div>

    <el-dialog v-model="createDialogVisible" title="新增用户" width="520px" destroy-on-close>
      <el-form ref="createFormRef" :model="createForm" :rules="createRules" label-width="88px">
        <el-form-item label="用户名" prop="username">
          <el-input v-model="createForm.username" placeholder="4-20 位字母、数字或下划线" maxlength="20" show-word-limit />
        </el-form-item>
        <el-form-item label="密码" prop="password">
          <el-input v-model="createForm.password" type="password" show-password placeholder="6-32 位" maxlength="32" />
        </el-form-item>
        <el-form-item label="邮箱" prop="email">
          <el-input v-model="createForm.email" placeholder="选填" clearable />
        </el-form-item>
        <el-form-item label="手机号" prop="phone">
          <el-input v-model="createForm.phone" placeholder="选填，11 位" maxlength="11" clearable />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="createDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="createSubmitting" @click="handleCreateUser">创建</el-button>
      </template>
    </el-dialog>

    <el-drawer
      v-model="detailDrawerVisible"
      :title="detailTitle"
      direction="rtl"
      size="600px"
      destroy-on-close
      @closed="onDetailClosed"
    >
      <div v-loading="detailLoading" class="detail-drawer">
        <template v-if="detailUser">
          <el-descriptions :column="1" border size="small" class="detail-desc">
            <el-descriptions-item label="用户编号">{{ detailUser.id }}</el-descriptions-item>
            <el-descriptions-item label="用户名">{{ detailUser.username }}</el-descriptions-item>
            <el-descriptions-item label="昵称">{{ detailUser.nickname || '—' }}</el-descriptions-item>
            <el-descriptions-item label="邮箱">{{ detailUser.email || '—' }}</el-descriptions-item>
            <el-descriptions-item label="手机">{{ detailUser.phone || '—' }}</el-descriptions-item>
            <el-descriptions-item label="账号状态">
              <el-tag :type="detailUser.status === 1 ? 'success' : 'info'" size="small">
                {{ detailUser.status === 1 ? '正常' : '禁用' }}
              </el-tag>
            </el-descriptions-item>
            <el-descriptions-item label="注册时间">{{ formatChinaDateTime(detailUser.createTime) }}</el-descriptions-item>
            <el-descriptions-item label="注册 IP">{{ detailUser.registerIp || '—' }}</el-descriptions-item>
            <el-descriptions-item label="最后登录时间">{{ formatChinaDateTime(detailUser.lastLoginTime) }}</el-descriptions-item>
            <el-descriptions-item label="最后登录 IP">{{ detailUser.lastLoginIp || '—' }}</el-descriptions-item>
            <el-descriptions-item label="累计创作字数">
              {{ (detailUser.totalWordCount ?? 0).toLocaleString() }}
            </el-descriptions-item>
            <el-descriptions-item label="积分（资料字段）">{{ (detailUser.points ?? 0).toLocaleString() }}</el-descriptions-item>
            <el-descriptions-item label="混合支付">
              {{ detailUser.enableMixedPayment ? '开启' : '关闭' }}
            </el-descriptions-item>
            <el-descriptions-item label="创作点额度日期">{{ detailUser.freeQuotaDate || '—' }}</el-descriptions-item>
            <el-descriptions-item label="当前创作点">{{ formatQuota(detailUser.freeQuota) }}</el-descriptions-item>
            <el-descriptions-item label="当前星夜币">{{ formatStarryCoin(detailUser.platformCurrency) }}</el-descriptions-item>
          </el-descriptions>

          <h4 class="detail-section-title">实名与核验</h4>
          <el-descriptions :column="1" border size="small" class="detail-desc">
            <el-descriptions-item label="证件信息在库">
              {{ detailUser.hasIdentityOnFile ? '是' : '否' }}
            </el-descriptions-item>
            <el-descriptions-item label="真实姓名（脱敏）">{{ detailUser.realNameMasked || '—' }}</el-descriptions-item>
            <el-descriptions-item label="证件号（脱敏）">{{ detailUser.idCardMasked || '—' }}</el-descriptions-item>
            <el-descriptions-item label="人脸/三方核验">
              <el-tag :type="detailUser.realNameVerified === 1 ? 'success' : 'info'" size="small">
                {{ detailUser.realNameVerified === 1 ? '已通过' : '未通过' }}
              </el-tag>
            </el-descriptions-item>
            <el-descriptions-item label="核验外部单号">{{ detailUser.realNameVerifyOuterNo || '—' }}</el-descriptions-item>
            <el-descriptions-item label="认证费关联单号">{{ detailUser.realnameFeePaidRecordNo || '—' }}</el-descriptions-item>
            <el-descriptions-item label="认证费订单状态">{{ detailUser.realnameFeePayStatus || '—' }}</el-descriptions-item>
            <el-descriptions-item label="认证费金额（元）">{{
              detailUser.realnameFeePayAmount != null && detailUser.realnameFeePayAmount !== undefined
                ? Number(detailUser.realnameFeePayAmount).toFixed(2)
                : '—'
            }}</el-descriptions-item>
            <el-descriptions-item label="认证费支付时间">{{
              formatChinaDateTime(detailUser.realnameFeePayTime)
            }}</el-descriptions-item>
          </el-descriptions>

          <h4 class="detail-section-title">核验状态（运营修正）</h4>
          <p class="detail-hint">
            将状态改为「已通过」时，用户须已在库中登记姓名与证件号；外部单号若无则记为
            <code>ADMIN</code>。改为「未通过」会清空外部单号。标为已通过时会清空认证费关联单号（与线上面核验完成一致）。
          </p>
          <el-form label-position="top" class="detail-form">
            <el-form-item label="人脸/三方核验状态">
              <el-radio-group v-model="realnameVerifiedForm.realNameVerified">
                <el-radio :value="0">未通过</el-radio>
                <el-radio :value="1">已通过</el-radio>
              </el-radio-group>
            </el-form-item>
            <el-form-item>
              <el-button type="primary" :loading="realnameVerifiedSaving" @click="handleSaveRealnameVerified">
                保存核验状态
              </el-button>
            </el-form-item>
          </el-form>

          <h4 class="detail-section-title">第三方登录</h4>
          <el-descriptions :column="1" border size="small" class="detail-desc">
            <el-descriptions-item label="已绑定渠道">
              <template v-if="detailUser.oauthProviders?.length">
                <el-tag v-for="p in detailUser.oauthProviders" :key="p" size="small" class="oauth-tag">{{ p }}</el-tag>
              </template>
              <span v-else>—</span>
            </el-descriptions-item>
          </el-descriptions>

          <h4 class="detail-section-title">作品与用量统计</h4>
          <el-descriptions :column="1" border size="small" class="detail-desc">
            <el-descriptions-item label="作品数（未删除）">{{ (detailUser.novelCount ?? 0).toLocaleString() }}</el-descriptions-item>
            <el-descriptions-item label="累计创作字数">
              {{ (detailUser.totalWordCount ?? 0).toLocaleString() }}
            </el-descriptions-item>
            <el-descriptions-item label="累计消耗创作点（免费额度）">{{
              (detailUser.totalFreeUsed ?? 0).toLocaleString()
            }}</el-descriptions-item>
            <el-descriptions-item label="累计消耗创作点（付费侧）">{{
              (detailUser.totalPaidUsed ?? 0).toLocaleString()
            }}</el-descriptions-item>
            <el-descriptions-item label="累计充值（元，取整累计）">{{
              (detailUser.totalRecharged ?? 0).toLocaleString()
            }}</el-descriptions-item>
          </el-descriptions>

          <h4 class="detail-section-title">资产调整</h4>
          <p class="detail-hint">修改创作点会将「额度日期」更新为当天，避免被日切逻辑误清零。</p>
          <el-form label-position="top" class="detail-form">
            <el-form-item label="创作点余额">
              <el-input-number v-model="balanceForm.freeQuota" :min="0" :max="999999999999" :step="1000" style="width: 100%" />
            </el-form-item>
            <el-form-item label="星夜币余额">
              <el-input-number v-model="balanceForm.platformCurrency" :min="0" :precision="2" :step="10" style="width: 100%" />
            </el-form-item>
            <el-form-item>
              <el-button type="primary" :loading="balanceSaving" @click="handleSaveBalance">保存资产</el-button>
            </el-form-item>
          </el-form>

          <el-divider />

          <h4 class="detail-section-title">VIP 状态</h4>
          <el-form label-position="top" class="detail-form">
            <el-form-item label="会员等级">
              <el-select v-model="vipForm.memberLevel" style="width: 100%">
                <el-option label="普通用户" :value="1" />
                <el-option label="VIP" :value="2" />
                <el-option label="高级 VIP" :value="3" />
              </el-select>
            </el-form-item>
            <el-form-item label="到期时间（留空表示不设到期）">
              <el-date-picker
                v-model="vipForm.memberExpireTime"
                type="datetime"
                value-format="YYYY-MM-DDTHH:mm:ss"
                placeholder="选择到期时间"
                style="width: 100%"
                clearable
              />
            </el-form-item>
            <el-form-item>
              <el-button type="primary" :loading="vipSaving" @click="handleSaveVip">保存 VIP</el-button>
            </el-form-item>
          </el-form>
        </template>
      </div>
    </el-drawer>
  </div>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { chinaDateTimeTitle, formatChinaDateTime } from '@/utils/chinaDateTime'
import type { FormInstance, FormRules } from 'element-plus'
import type { AdminUserDetail, AdminUserItem } from '@/types/api'
import {
  createAdminUser,
  getAdminUserDetail,
  listAdminUsers,
  updateAdminUserBalance,
  updateAdminUserMembership,
  updateAdminUserRealnameVerified,
  updateUserStatus
} from '@/api/user'

const searchKeyword = ref('')
const statusFilter = ref<number | undefined>(undefined)
const currentPage = ref(1)
const pageSize = ref(10)
const total = ref(0)
const users = ref<AdminUserItem[]>([])
const loading = ref(false)

const createDialogVisible = ref(false)
const createFormRef = ref<FormInstance>()
const createSubmitting = ref(false)
const createForm = reactive({
  username: '',
  password: '',
  email: '',
  phone: ''
})

const detailDrawerVisible = ref(false)
const detailLoading = ref(false)
const detailUserId = ref<number | null>(null)
const detailUser = ref<AdminUserDetail | null>(null)
const balanceSaving = ref(false)
const vipSaving = ref(false)
const realnameVerifiedSaving = ref(false)
const realnameVerifiedForm = reactive<{ realNameVerified: 0 | 1 }>({ realNameVerified: 0 })
const balanceForm = reactive({ freeQuota: 0, platformCurrency: 0 })
const vipForm = reactive<{ memberLevel: number; memberExpireTime: string | null }>({
  memberLevel: 1,
  memberExpireTime: null
})

const detailTitle = computed(() => {
  const u = detailUser.value?.username
  return u ? `用户详情 · ${u}` : '用户详情'
})

const USERNAME_RE = /^[a-zA-Z0-9_]{4,20}$/

const createRules: FormRules = {
  username: [
    { required: true, message: '请输入用户名', trigger: 'blur' },
    {
      validator: (_r, v: string, cb) => {
        if (!v || !USERNAME_RE.test(v.trim())) {
          cb(new Error('须为 4-20 位字母、数字或下划线'))
        } else cb()
      },
      trigger: 'blur'
    }
  ],
  password: [
    { required: true, message: '请输入密码', trigger: 'blur' },
    { min: 6, max: 32, message: '密码长度为 6-32 位', trigger: 'blur' }
  ],
  email: [
    {
      validator: (_r, v: string, cb) => {
        const s = v?.trim()
        if (!s) return cb()
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(s)) cb(new Error('邮箱格式不正确'))
        else cb()
      },
      trigger: 'blur'
    }
  ],
  phone: [
    {
      validator: (_r, v: string, cb) => {
        const s = v?.trim()
        if (!s) return cb()
        if (!/^1[3-9]\d{9}$/.test(s)) cb(new Error('须为 11 位手机号'))
        else cb()
      },
      trigger: 'blur'
    }
  ]
}

function memberLevelLabel(level: number | undefined) {
  if (level === 2) return 'VIP'
  if (level === 3) return '高级 VIP'
  return '普通'
}

function formatQuota(n: number | undefined) {
  return (n ?? 0).toLocaleString()
}

function formatStarryCoin(n: number | undefined) {
  return Number(n ?? 0).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 })
}

async function loadUsers() {
  loading.value = true
  try {
    const page = await listAdminUsers({
      page: currentPage.value,
      size: pageSize.value,
      keyword: searchKeyword.value || undefined,
      status: statusFilter.value
    })
    users.value = page?.records ?? []
    total.value = Number(page?.total ?? 0)
  } finally {
    loading.value = false
  }
}

function openCreateDialog() {
  Object.assign(createForm, { username: '', password: '', email: '', phone: '' })
  createDialogVisible.value = true
}

async function handleCreateUser() {
  if (!createFormRef.value) return
  const ok = await createFormRef.value.validate().catch(() => false)
  if (!ok) return
  createSubmitting.value = true
  try {
    await createAdminUser({
      username: createForm.username.trim(),
      password: createForm.password,
      email: createForm.email.trim() || undefined,
      phone: createForm.phone.trim() || undefined
    })
    ElMessage.success('用户已创建')
    createDialogVisible.value = false
    currentPage.value = 1
    await loadUsers()
  } catch {
    /* 错误信息由请求封装统一提示 */
  } finally {
    createSubmitting.value = false
  }
}

function handleSearch() {
  currentPage.value = 1
  loadUsers()
}

function handleSizeChange() {
  currentPage.value = 1
  loadUsers()
}

function handleCurrentChange() {
  loadUsers()
}

async function handleToggleStatus(row: AdminUserItem) {
  const nextStatus = row.status === 1 ? 0 : 1
  const actionText = nextStatus === 1 ? '启用' : '禁用'
  await ElMessageBox.confirm(`确认${actionText}用户「${row.username}」吗？`, '操作确认', {
    type: 'warning'
  })
  await updateUserStatus(row.id, nextStatus)
  ElMessage.success(`用户已${actionText}`)
  await loadUsers()
  if (detailUserId.value === row.id && detailUser.value) {
    detailUser.value.status = nextStatus
  }
}

async function openDetailDrawer(row: AdminUserItem) {
  detailUserId.value = row.id
  detailDrawerVisible.value = true
  await loadDetail()
}

async function loadDetail() {
  const id = detailUserId.value
  if (id == null) return
  detailLoading.value = true
  try {
    const d = await getAdminUserDetail(id)
    detailUser.value = d
    realnameVerifiedForm.realNameVerified = d.realNameVerified === 1 ? 1 : 0
    balanceForm.freeQuota = d.freeQuota ?? 0
    balanceForm.platformCurrency = Number(d.platformCurrency ?? 0)
    vipForm.memberLevel = d.memberLevel ?? 1
    vipForm.memberExpireTime = d.memberExpireTime ?? null
  } finally {
    detailLoading.value = false
  }
}

async function handleSaveBalance() {
  const id = detailUserId.value
  if (id == null) return
  balanceSaving.value = true
  try {
    await updateAdminUserBalance(id, {
      freeQuota: balanceForm.freeQuota,
      platformCurrency: balanceForm.platformCurrency
    })
    ElMessage.success('资产已更新')
    await loadDetail()
    await loadUsers()
  } finally {
    balanceSaving.value = false
  }
}

async function handleSaveRealnameVerified() {
  const id = detailUserId.value
  if (id == null) return
  const next = realnameVerifiedForm.realNameVerified
  if (detailUser.value?.realNameVerified === next) {
    ElMessage.info('状态未变化')
    return
  }
  if (next === 1) {
    await ElMessageBox.confirm(
      '确认将该用户标为「实名核验已通过」？请确保已在其他渠道完成人工核验。',
      '操作确认',
      { type: 'warning' }
    )
  }
  realnameVerifiedSaving.value = true
  try {
    await updateAdminUserRealnameVerified(id, next)
    ElMessage.success('核验状态已更新')
    await loadDetail()
    await loadUsers()
  } catch {
    /* 错误由请求封装提示 */
  } finally {
    realnameVerifiedSaving.value = false
  }
}

async function handleSaveVip() {
  const id = detailUserId.value
  if (id == null) return
  vipSaving.value = true
  try {
    await updateAdminUserMembership(id, {
      memberLevel: vipForm.memberLevel,
      memberExpireTime: vipForm.memberExpireTime || null
    })
    ElMessage.success('VIP 已更新')
    await loadDetail()
    await loadUsers()
  } finally {
    vipSaving.value = false
  }
}

function onDetailClosed() {
  detailUserId.value = null
  detailUser.value = null
}

loadUsers()
</script>

<style lang="scss" scoped>
.card-header {
  display: flex;
  align-items: center;
  gap: $space-md;
  flex-wrap: wrap;
}

.pagination {
  margin-top: $space-lg;
  display: flex;
  justify-content: flex-end;
}

.text-muted {
  color: $text-muted;
  font-size: $font-size-sm;
}

.detail-drawer {
  padding: 0 4px 24px;
}

.detail-desc {
  margin-bottom: $space-lg;

  :deep(.el-descriptions__label) {
    color: $text-muted;
  }

  :deep(.el-descriptions__content) {
    color: $text-primary;
  }
}

.detail-section-title {
  margin: $space-lg 0 $space-sm;
  font-size: $font-size-lg;
  font-weight: 700;
  color: $text-primary;
  letter-spacing: -0.01em;
}

.oauth-tag {
  margin-right: $space-xs;
  margin-bottom: 2px;
}

.detail-hint {
  font-size: $font-size-xs;
  color: $text-muted;
  margin: 0 0 $space-md;
  line-height: 1.6;
}

.detail-form {
  max-width: 100%;

  :deep(.el-input-number) {
    width: 100%;
  }
}

/* 操作列：按钮分区 */
.table-actions {
  display: inline-flex;
  align-items: center;
  justify-content: flex-end;
  gap: $space-sm;
  flex-wrap: nowrap;
  width: 100%;
  min-height: 32px;
}

:deep(.col-actions .cell) {
  padding-top: 6px;
  padding-bottom: 6px;
}

/* 注册时间：格内裁切长串 */
:deep(.col-create-time .cell) {
  overflow: hidden;
  max-width: 100%;
}

.create-time-clip {
  display: block;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  line-height: 1.4;
  vertical-align: middle;
}

.ip-clip {
  display: block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-family: ui-monospace, monospace;
  font-size: $font-size-xs;
}

:deep(.col-login-audit .cell) {
  overflow: hidden;
  padding-top: 6px;
  padding-bottom: 6px;
}

.login-audit-cell {
  line-height: 1.35;
}

.login-audit-time {
  font-size: $font-size-xs;
}

.login-audit-ip {
  font-size: $font-size-xs;
  color: $text-muted;
  font-family: ui-monospace, monospace;
}

</style>
