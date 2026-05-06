<template>
  <div class="admin-vip-members page-container">
    <div class="page-header">
      <h1>VIP 管理</h1>
      <p class="page-header__desc">
        配置前台会员套餐（价格、时长、权益 JSON）、各等级下的权益项（额度、次数等），以及需要时单独调整用户会员状态。
      </p>
    </div>

    <div class="page-content">
      <el-card class="vip-main-card">
        <el-tabs v-model="mainTab" class="vip-tabs" @tab-change="onMainTabChange">
          <!-- 套餐与定价 -->
          <el-tab-pane label="套餐与定价" name="packages">
            <div class="tab-toolbar">
              <el-button type="primary" @click="openPackageDialog(true)">新建套餐</el-button>
              <el-button @click="loadPackages">刷新</el-button>
            </div>
            <el-table :data="packages" stripe v-loading="packagesLoading" border>
              <el-table-column prop="packageCode" label="编码" width="130" show-overflow-tooltip />
              <el-table-column prop="packageName" label="名称" min-width="140" show-overflow-tooltip />
              <el-table-column label="等级" width="100">
                <template #default="{ row }">
                  <el-tag size="small" :type="memberLevelTagType(row.memberLevel)">{{ memberLevelLabel(row.memberLevel) }}</el-tag>
                </template>
              </el-table-column>
              <el-table-column prop="durationDays" label="时长(天)" width="96" align="right" />
              <el-table-column label="售价(元)" width="110" align="right">
                <template #default="{ row }">{{ formatMoney(row.price) }}</template>
              </el-table-column>
              <el-table-column label="原价(元)" width="110" align="right">
                <template #default="{ row }">{{ row.originalPrice != null ? formatMoney(row.originalPrice) : '—' }}</template>
              </el-table-column>
              <el-table-column prop="dailyFreeQuota" label="日免费额度" width="120" align="right" />
              <el-table-column label="排序" width="72" align="right" prop="sortOrder" />
              <el-table-column label="上架" width="80" align="center">
                <template #default="{ row }">
                  <el-tag :type="row.status === 1 ? 'success' : 'info'" size="small">{{ row.status === 1 ? '是' : '否' }}</el-tag>
                </template>
              </el-table-column>
              <el-table-column label="操作" width="100" fixed="right">
                <template #default="{ row }">
                  <el-button type="primary" link @click="openPackageDialog(false, row)">编辑</el-button>
                </template>
              </el-table-column>
            </el-table>
          </el-tab-pane>

          <!-- 等级权益 -->
          <el-tab-pane label="等级权益" name="benefits">
            <div class="tab-toolbar">
              <el-select v-model="benefitLevelFilter" placeholder="筛选等级" clearable style="width: 160px" @change="loadBenefits">
                <el-option label="全部等级" :value="null" />
                <el-option label="普通 (1)" :value="1" />
                <el-option label="VIP (2)" :value="2" />
                <el-option label="高级 VIP (3)" :value="3" />
              </el-select>
              <el-button @click="loadBenefits">刷新</el-button>
            </div>
            <el-alert type="info" :closable="false" show-icon class="benefit-hint">
              权益值一般为 JSON，如每日额度：<code>{"value":50000}</code>；布尔权益：<code>{"value":true}</code>。键名（benefit_key）由系统约定，请勿随意改名。
            </el-alert>
            <el-table :data="benefitConfigs" stripe v-loading="benefitsLoading" border>
              <el-table-column prop="memberLevel" label="等级" width="88" align="center">
                <template #default="{ row }">{{ memberLevelLabel(row.memberLevel) }}</template>
              </el-table-column>
              <el-table-column prop="benefitKey" label="权益键" min-width="160" show-overflow-tooltip />
              <el-table-column prop="benefitName" label="名称" min-width="120" show-overflow-tooltip />
              <el-table-column label="权益值" min-width="200">
                <template #default="{ row }">
                  <span class="mono-clip" :title="row.benefitValue || ''">{{ row.benefitValue || '—' }}</span>
                </template>
              </el-table-column>
              <el-table-column label="启用" width="72" align="center">
                <template #default="{ row }">
                  <el-tag :type="row.enabled === 1 ? 'success' : 'info'" size="small">{{ row.enabled === 1 ? '是' : '否' }}</el-tag>
                </template>
              </el-table-column>
              <el-table-column prop="description" label="说明" min-width="160" show-overflow-tooltip />
              <el-table-column label="操作" width="88" fixed="right">
                <template #default="{ row }">
                  <el-button type="primary" link @click="openBenefitDialog(row)">编辑</el-button>
                </template>
              </el-table-column>
            </el-table>
          </el-tab-pane>

          <!-- 会员用户 -->
          <el-tab-pane label="会员用户" name="users">
            <div class="tab-toolbar">
              <el-input
                v-model="searchKeyword"
                placeholder="搜索用户名/邮箱/手机"
                style="width: 280px"
                clearable
                @keyup.enter="handleSearch"
              >
                <template #prefix>
                  <el-icon><Search /></el-icon>
                </template>
              </el-input>
              <el-select v-model="statusFilter" placeholder="账号状态" clearable style="width: 120px">
                <el-option label="正常" :value="1" />
                <el-option label="禁用" :value="0" />
              </el-select>
              <el-select v-model="tierFilter" placeholder="会员范围" style="width: 200px" @change="handleSearch">
                <el-option label="仅付费会员（VIP / 高级）" value="paid" />
                <el-option label="全部等级" value="all" />
                <el-option label="普通用户" value="1" />
                <el-option label="VIP" value="2" />
                <el-option label="高级 VIP" value="3" />
              </el-select>
              <el-button type="primary" @click="handleSearch">搜索</el-button>
              <el-button @click="goUserAdmin">用户管理</el-button>
            </div>
            <el-table :data="users" stripe v-loading="loading">
              <el-table-column prop="id" label="编号" width="80" />
              <el-table-column prop="username" label="用户名" min-width="120" show-overflow-tooltip />
              <el-table-column prop="email" label="邮箱" min-width="160" show-overflow-tooltip />
              <el-table-column label="会员等级" width="120">
                <template #default="{ row }">
                  <el-tag :type="memberLevelTagType(row.memberLevel)" size="small">
                    {{ memberLevelLabel(row.memberLevel) }}
                  </el-tag>
                </template>
              </el-table-column>
              <el-table-column label="到期时间" min-width="180" class-name="col-expire">
                <template #default="{ row }">
                  <span class="expire-clip" :title="chinaDateTimeTitle(row.memberExpireTime)">
                    {{ formatExpireDisplay(row.memberExpireTime) }}
                  </span>
                </template>
              </el-table-column>
              <el-table-column prop="status" label="状态" width="90">
                <template #default="{ row }">
                  <el-tag :type="row.status === 1 ? 'success' : 'danger'" size="small">
                    {{ row.status === 1 ? '正常' : '禁用' }}
                  </el-tag>
                </template>
              </el-table-column>
              <el-table-column label="操作" width="120" class-name="col-actions">
                <template #default="{ row }">
                  <el-button type="primary" size="small" plain @click="openVipDrawer(row)">调整会员</el-button>
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
          </el-tab-pane>
        </el-tabs>
      </el-card>
    </div>

    <!-- 套餐编辑 -->
    <el-dialog
      v-model="packageDialogVisible"
      :title="packageIsCreate ? '新建套餐' : '编辑套餐'"
      width="560px"
      destroy-on-close
      @closed="onPackageDialogClosed"
    >
      <el-form ref="packageFormRef" :model="packageForm" :rules="packageRules" label-width="108px">
        <el-form-item v-if="packageIsCreate" label="套餐编码" prop="packageCode">
          <el-input v-model="packageForm.packageCode" placeholder="唯一英文编码，如 VIP_MONTHLY" maxlength="50" show-word-limit />
        </el-form-item>
        <el-form-item v-else label="套餐编码">
          <el-input :model-value="packageForm.packageCode" disabled />
        </el-form-item>
        <el-form-item label="名称" prop="packageName">
          <el-input v-model="packageForm.packageName" maxlength="100" show-word-limit />
        </el-form-item>
        <el-form-item label="描述">
          <el-input v-model="packageForm.description" type="textarea" :rows="2" maxlength="500" show-word-limit />
        </el-form-item>
        <el-form-item label="会员等级" prop="memberLevel">
          <el-select v-model="packageForm.memberLevel" style="width: 100%">
            <el-option label="普通 (1)" :value="1" />
            <el-option label="VIP (2)" :value="2" />
            <el-option label="高级 VIP (3)" :value="3" />
          </el-select>
        </el-form-item>
        <el-form-item label="时长(天)" prop="durationDays">
          <el-input-number v-model="packageForm.durationDays" :min="1" :max="36500" style="width: 100%" />
        </el-form-item>
        <el-form-item label="售价(元)" prop="price">
          <el-input-number v-model="packageForm.price" :min="0" :precision="2" :step="1" style="width: 100%" />
        </el-form-item>
        <el-form-item label="原价(元)">
          <el-input-number v-model="packageForm.originalPrice" :min="0" :precision="2" :step="1" style="width: 100%" />
        </el-form-item>
        <el-form-item label="日免费额度" prop="dailyFreeQuota">
          <el-input-number v-model="packageForm.dailyFreeQuota" :min="0" :max="999999999999" :step="1000" style="width: 100%" />
        </el-form-item>
        <el-form-item label="排序" prop="sortOrder">
          <el-input-number v-model="packageForm.sortOrder" :min="0" :max="9999" style="width: 100%" />
        </el-form-item>
        <el-form-item label="上架">
          <el-switch v-model="packageForm.status" :active-value="1" :inactive-value="0" active-text="上架" inactive-text="下架" />
        </el-form-item>
        <el-form-item label="权益 JSON">
          <el-input v-model="packageForm.features" type="textarea" :rows="4" placeholder='可选，如 {"outline_limit":100}' />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="packageDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="packageSaving" @click="submitPackage">保存</el-button>
      </template>
    </el-dialog>

    <!-- 权益编辑 -->
    <el-dialog v-model="benefitDialogVisible" title="编辑权益" width="520px" destroy-on-close @closed="onBenefitDialogClosed">
      <template v-if="benefitEditing">
        <el-descriptions :column="1" border size="small" class="benefit-meta">
          <el-descriptions-item label="等级">{{ memberLevelLabel(benefitEditing.memberLevel) }}</el-descriptions-item>
          <el-descriptions-item label="权益键">{{ benefitEditing.benefitKey }}</el-descriptions-item>
        </el-descriptions>
        <el-form label-position="top" class="benefit-form">
          <el-form-item label="显示名称" required>
            <el-input v-model="benefitForm.benefitName" maxlength="100" show-word-limit />
          </el-form-item>
          <el-form-item label="权益值（JSON）">
            <el-input v-model="benefitForm.benefitValue" type="textarea" :rows="4" placeholder='如 {"value":50000}' />
          </el-form-item>
          <el-form-item label="说明">
            <el-input v-model="benefitForm.description" type="textarea" :rows="2" maxlength="500" show-word-limit />
          </el-form-item>
          <el-form-item label="启用">
            <el-switch v-model="benefitForm.enabled" :active-value="1" :inactive-value="0" />
          </el-form-item>
        </el-form>
      </template>
      <template #footer>
        <el-button @click="benefitDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="benefitSaving" @click="submitBenefit">保存</el-button>
      </template>
    </el-dialog>

    <!-- 用户侧栏：调整会员 -->
    <el-drawer
      v-model="drawerVisible"
      :title="drawerTitle"
      direction="rtl"
      size="440px"
      destroy-on-close
      @closed="onDrawerClosed"
    >
      <div v-if="editingRow" class="vip-drawer">
        <el-descriptions :column="1" border size="small" class="vip-desc">
          <el-descriptions-item label="用户编号">{{ editingRow.id }}</el-descriptions-item>
          <el-descriptions-item label="用户名">{{ editingRow.username }}</el-descriptions-item>
          <el-descriptions-item label="邮箱">{{ editingRow.email || '—' }}</el-descriptions-item>
        </el-descriptions>
        <h4 class="vip-form-title">调整会员</h4>
        <el-form label-position="top" class="vip-form">
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
            <el-button type="primary" :loading="saving" @click="handleSaveUserVip">保存</el-button>
          </el-form-item>
        </el-form>
      </div>
    </el-drawer>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import type { FormInstance, FormRules } from 'element-plus'
import { Search } from '@element-plus/icons-vue'
import type { AdminUserItem } from '@/types/api'
import { listAdminUsers, updateAdminUserMembership } from '@/api/user'
import {
  adminCreateVipPackage,
  adminListBenefitConfigs,
  adminListVipPackages,
  adminUpdateBenefitConfig,
  adminUpdateVipPackage,
  type AdminMemberBenefitConfig,
  type AdminVipPackage,
  type AdminVipPackagePayload
} from '@/api/adminVip'
import { ADMIN_CONSOLE_BASE_PATH } from '@/config/portal'
import { chinaDateTimeTitle, formatChinaDateTime } from '@/utils/chinaDateTime'

const router = useRouter()

const mainTab = ref<'packages' | 'benefits' | 'users'>('packages')

const packages = ref<AdminVipPackage[]>([])
const packagesLoading = ref(false)
const packageDialogVisible = ref(false)
const packageIsCreate = ref(true)
const packageSaving = ref(false)
const packageEditingId = ref<number | null>(null)
const packageFormRef = ref<FormInstance>()
const packageForm = reactive({
  packageCode: '',
  packageName: '',
  description: '',
  memberLevel: 2,
  durationDays: 30,
  price: 0,
  originalPrice: undefined as number | undefined,
  dailyFreeQuota: 50000,
  features: '',
  sortOrder: 0,
  status: 1
})

const packageRules: FormRules = {
  packageCode: [
    {
      validator: (_r, v, cb) => {
        if (!packageIsCreate.value) return cb()
        if (!v || !String(v).trim()) cb(new Error('请输入套餐编码'))
        else cb()
      },
      trigger: 'blur'
    }
  ],
  packageName: [{ required: true, message: '请输入名称', trigger: 'blur' }],
  memberLevel: [{ required: true, message: '请选择等级', trigger: 'change' }],
  durationDays: [{ required: true, message: '请填写时长', trigger: 'blur' }],
  price: [{ required: true, message: '请填写售价', trigger: 'blur' }],
  dailyFreeQuota: [{ required: true, message: '请填写日额度', trigger: 'blur' }],
  sortOrder: [{ required: true, message: '请填写排序', trigger: 'blur' }]
}

const benefitConfigs = ref<AdminMemberBenefitConfig[]>([])
const benefitsLoading = ref(false)
const benefitLevelFilter = ref<number | null>(null)
const benefitDialogVisible = ref(false)
const benefitEditing = ref<AdminMemberBenefitConfig | null>(null)
const benefitSaving = ref(false)
const benefitForm = reactive({
  benefitName: '',
  benefitValue: '' as string | null,
  description: '',
  enabled: 1
})

const searchKeyword = ref('')
const statusFilter = ref<number | undefined>(undefined)
const tierFilter = ref<'paid' | 'all' | '1' | '2' | '3'>('paid')
const currentPage = ref(1)
const pageSize = ref(10)
const total = ref(0)
const users = ref<AdminUserItem[]>([])
const loading = ref(false)

const drawerVisible = ref(false)
const editingRow = ref<AdminUserItem | null>(null)
const saving = ref(false)
const vipForm = reactive<{ memberLevel: number; memberExpireTime: string | null }>({
  memberLevel: 1,
  memberExpireTime: null
})

const drawerTitle = computed(() => {
  const u = editingRow.value?.username
  return u ? `调整会员 · ${u}` : '调整会员'
})

function memberLevelLabel(level: number | undefined) {
  if (level === 2) return 'VIP'
  if (level === 3) return '高级 VIP'
  return '普通'
}

function memberLevelTagType(level: number | undefined) {
  if (level === 3) return 'warning'
  if (level === 2) return 'success'
  return 'info'
}

function formatMoney(n: number | undefined) {
  if (n == null || Number.isNaN(n)) return '—'
  return Number(n).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 })
}

function formatExpireDisplay(v: string | null | undefined) {
  if (v == null || String(v).trim() === '') return '未设置'
  const s = formatChinaDateTime(v)
  return s === '—' ? '未设置' : s
}

function goUserAdmin() {
  router.push(`${ADMIN_CONSOLE_BASE_PATH}/users`)
}

function onMainTabChange(name: string | number) {
  if (name === 'packages' && packages.value.length === 0 && !packagesLoading.value) loadPackages()
  if (name === 'benefits' && benefitConfigs.value.length === 0 && !benefitsLoading.value) loadBenefits()
  if (name === 'users' && users.value.length === 0 && !loading.value) loadUsers()
}

async function loadPackages() {
  packagesLoading.value = true
  try {
    packages.value = (await adminListVipPackages()) ?? []
  } finally {
    packagesLoading.value = false
  }
}

function resetPackageForm() {
  packageForm.packageCode = ''
  packageForm.packageName = ''
  packageForm.description = ''
  packageForm.memberLevel = 2
  packageForm.durationDays = 30
  packageForm.price = 30
  packageForm.originalPrice = undefined
  packageForm.dailyFreeQuota = 50000
  packageForm.features = ''
  packageForm.sortOrder = 0
  packageForm.status = 1
}

function openPackageDialog(create: boolean, row?: AdminVipPackage) {
  packageIsCreate.value = create
  packageEditingId.value = create ? null : row?.id ?? null
  resetPackageForm()
  if (!create && row) {
    packageForm.packageCode = row.packageCode
    packageForm.packageName = row.packageName
    packageForm.description = row.description ?? ''
    packageForm.memberLevel = row.memberLevel
    packageForm.durationDays = row.durationDays
    packageForm.price = Number(row.price)
    packageForm.originalPrice = row.originalPrice != null ? Number(row.originalPrice) : undefined
    packageForm.dailyFreeQuota = row.dailyFreeQuota
    packageForm.features = row.features ?? ''
    packageForm.sortOrder = row.sortOrder
    packageForm.status = row.status
  }
  packageDialogVisible.value = true
}

function onPackageDialogClosed() {
  packageEditingId.value = null
  packageFormRef.value?.clearValidate()
}

function toPackagePayload(): AdminVipPackagePayload {
  let features = packageForm.features?.trim() || undefined
  if (features) {
    try {
      JSON.parse(features)
    } catch {
      throw new Error('权益 JSON 格式不正确')
    }
  }
  return {
    packageCode: packageIsCreate.value ? packageForm.packageCode.trim() : undefined,
    packageName: packageForm.packageName.trim(),
    description: packageForm.description?.trim() || undefined,
    memberLevel: packageForm.memberLevel,
    durationDays: packageForm.durationDays,
    price: packageForm.price,
    originalPrice: packageForm.originalPrice,
    dailyFreeQuota: packageForm.dailyFreeQuota,
    features,
    sortOrder: packageForm.sortOrder,
    status: packageForm.status
  }
}

async function submitPackage() {
  const form = packageFormRef.value
  if (!form) return
  const valid = await form.validate().catch(() => false)
  if (!valid) return
  let payload: AdminVipPackagePayload
  try {
    payload = toPackagePayload()
  } catch (e) {
    ElMessage.error(e instanceof Error ? e.message : '保存失败')
    return
  }
  packageSaving.value = true
  try {
    if (packageIsCreate.value) {
      await adminCreateVipPackage({
        ...payload,
        packageCode: packageForm.packageCode.trim()
      })
      ElMessage.success('已创建套餐')
    } else if (packageEditingId.value != null) {
      await adminUpdateVipPackage(packageEditingId.value, payload)
      ElMessage.success('已保存')
    }
    packageDialogVisible.value = false
    await loadPackages()
  } finally {
    packageSaving.value = false
  }
}

async function loadBenefits() {
  benefitsLoading.value = true
  try {
    const lv = benefitLevelFilter.value
    benefitConfigs.value = (await adminListBenefitConfigs(lv === null ? undefined : lv)) ?? []
  } finally {
    benefitsLoading.value = false
  }
}

function openBenefitDialog(row: AdminMemberBenefitConfig) {
  benefitEditing.value = row
  benefitForm.benefitName = row.benefitName
  benefitForm.benefitValue = row.benefitValue ?? ''
  benefitForm.description = row.description ?? ''
  benefitForm.enabled = row.enabled
  benefitDialogVisible.value = true
}

function onBenefitDialogClosed() {
  benefitEditing.value = null
}

async function submitBenefit() {
  const row = benefitEditing.value
  if (!row?.id) return
  if (!benefitForm.benefitName?.trim()) {
    ElMessage.warning('请填写显示名称')
    return
  }
  const raw = benefitForm.benefitValue?.trim()
  if (raw) {
    try {
      JSON.parse(raw)
    } catch {
      ElMessage.error('权益值须为合法 JSON')
      return
    }
  }
  benefitSaving.value = true
  try {
    await adminUpdateBenefitConfig(row.id, {
      benefitName: benefitForm.benefitName.trim(),
      benefitValue: raw || null,
      description: benefitForm.description?.trim() || undefined,
      enabled: benefitForm.enabled
    })
    ElMessage.success('已保存')
    benefitDialogVisible.value = false
    await loadBenefits()
  } finally {
    benefitSaving.value = false
  }
}

async function loadUsers() {
  loading.value = true
  try {
    const params: {
      page: number
      size: number
      keyword?: string
      status?: number
      memberLevel?: number
      memberLevelMin?: number
    } = {
      page: currentPage.value,
      size: pageSize.value,
      keyword: searchKeyword.value || undefined,
      status: statusFilter.value
    }
    if (tierFilter.value === 'all') {
      /* empty */
    } else if (tierFilter.value === 'paid') {
      params.memberLevelMin = 2
    } else {
      params.memberLevel = Number(tierFilter.value)
    }
    const page = await listAdminUsers(params)
    users.value = page?.records ?? []
    total.value = Number(page?.total ?? 0)
  } finally {
    loading.value = false
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

function openVipDrawer(row: AdminUserItem) {
  editingRow.value = row
  vipForm.memberLevel = row.memberLevel ?? 1
  vipForm.memberExpireTime = row.memberExpireTime ?? null
  drawerVisible.value = true
}

function onDrawerClosed() {
  editingRow.value = null
}

async function handleSaveUserVip() {
  const row = editingRow.value
  if (!row) return
  saving.value = true
  try {
    await updateAdminUserMembership(row.id, {
      memberLevel: vipForm.memberLevel,
      memberExpireTime: vipForm.memberExpireTime || null
    })
    ElMessage.success('会员信息已更新')
    drawerVisible.value = false
    await loadUsers()
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadPackages()
})
</script>

<style lang="scss" scoped>
.page-header__desc {
  margin: $space-sm 0 0;
  font-size: $font-size-sm;
  color: $text-muted;
  max-width: 720px;
  line-height: 1.6;
}

.vip-main-card {
  :deep(.el-card__body) {
    padding-top: $space-md;
  }
}

.vip-tabs {
  :deep(.el-tabs__content) {
    padding-top: $space-md;
  }
}

.tab-toolbar {
  display: flex;
  align-items: center;
  gap: $space-md;
  flex-wrap: wrap;
  margin-bottom: $space-md;
}

.benefit-hint {
  margin-bottom: $space-md;

  code {
    font-size: $font-size-xs;
    padding: 2px 6px;
    border-radius: 4px;
    background: $bg-elevated;
  }
}

.mono-clip {
  display: block;
  font-family: ui-monospace, monospace;
  font-size: $font-size-xs;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.pagination {
  margin-top: $space-lg;
  display: flex;
  justify-content: flex-end;
}

:deep(.col-expire .cell) {
  overflow: hidden;
}

.expire-clip {
  display: block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.benefit-meta {
  margin-bottom: $space-md;
}

.benefit-form {
  max-width: 100%;
}

.vip-drawer {
  padding-bottom: $space-lg;
}

.vip-desc {
  margin-bottom: $space-lg;

  :deep(.el-descriptions__label) {
    color: $text-muted;
  }
}

.vip-form-title {
  margin: 0 0 $space-sm;
  font-size: $font-size-md;
  font-weight: 600;
  color: $text-primary;
}

.vip-form {
  max-width: 100%;
}
</style>
