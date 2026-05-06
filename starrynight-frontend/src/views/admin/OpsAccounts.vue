<template>
  <div class="admin-ops-accounts page-container">
    <div class="page-header">
      <div class="page-header__inner">
        <div class="page-header__text">
          <h1>账号与权限</h1>
          <p class="page-header__lead">管理运营端账号、角色与菜单权限分配，保障运营后台的安全访问控制。</p>
        </div>
      </div>

      <div class="stats-summary">
        <div class="stat-item">
          <div class="stat-item__icon stat-item__icon--violet">
            <el-icon :size="18"><UserFilled /></el-icon>
          </div>
          <div class="stat-item__body">
            <span class="stat-item__label">运营账号</span>
            <span class="stat-item__value">{{ accountStats.total }}</span>
          </div>
        </div>
        <div class="stat-item">
          <div class="stat-item__icon stat-item__icon--emerald">
            <el-icon :size="18"><CircleCheck /></el-icon>
          </div>
          <div class="stat-item__body">
            <span class="stat-item__label">本页启用</span>
            <span class="stat-item__value">{{ accountStats.active }}</span>
          </div>
        </div>
        <div class="stat-item">
          <div class="stat-item__icon stat-item__icon--amber">
            <el-icon :size="18"><Avatar /></el-icon>
          </div>
          <div class="stat-item__body">
            <span class="stat-item__label">角色数量</span>
            <span class="stat-item__value">{{ roleStats.total }}</span>
          </div>
        </div>
        <div class="stat-item">
          <div class="stat-item__icon stat-item__icon--cyan">
            <el-icon :size="18"><Lock /></el-icon>
          </div>
          <div class="stat-item__body">
            <span class="stat-item__label">启用角色</span>
            <span class="stat-item__value">{{ roleStats.active }}</span>
          </div>
        </div>
      </div>
    </div>

    <div class="page-content">
      <div class="pill-tabs">
        <button
          class="pill-tab"
          :class="{ 'pill-tab--active': activeTab === 'accounts' }"
          @click="onTabChange('accounts')"
        >
          <el-icon :size="16"><UserFilled /></el-icon>
          <span>运营账号</span>
        </button>
        <button
          class="pill-tab"
          :class="{ 'pill-tab--active': activeTab === 'roles' }"
          @click="onTabChange('roles')"
        >
          <el-icon :size="16"><Lock /></el-icon>
          <span>角色与权限</span>
        </button>
      </div>

      <section v-if="activeTab === 'accounts'" class="tab-panel">
        <div class="toolbar">
          <div class="toolbar__search">
            <el-input
              v-model="keyword"
              placeholder="搜索运营账号"
              clearable
              class="toolbar__input"
              @keyup.enter="handleSearch"
            >
              <template #prefix>
                <el-icon><Search /></el-icon>
              </template>
            </el-input>
            <el-select v-model="statusFilter" clearable placeholder="状态筛选" class="toolbar__select">
              <el-option label="正常" :value="1" />
              <el-option label="禁用" :value="0" />
            </el-select>
            <el-button class="toolbar__btn-query" @click="handleSearch">
              <el-icon :size="16"><Search /></el-icon>
              <span>查询</span>
            </el-button>
          </div>
          <el-button type="primary" class="toolbar__btn-create" @click="openCreateDialog">
            <el-icon :size="16"><Plus /></el-icon>
            <span>新增运营账号</span>
          </el-button>
        </div>

        <div class="data-card">
          <el-table
            :data="accounts"
            v-loading="loading"
            class="data-table"
            stripe
            empty-text="暂无运营账号数据"
          >
            <el-table-column prop="id" label="编号" width="80" align="center" />
            <el-table-column prop="username" label="账号" min-width="160">
              <template #default="{ row }">
                <div class="table-user-cell">
                  <el-avatar :size="32" class="table-user-cell__avatar">
                    {{ row.username?.charAt(0) }}
                  </el-avatar>
                  <span class="table-user-cell__name">{{ row.username }}</span>
                </div>
              </template>
            </el-table-column>
            <el-table-column prop="email" label="邮箱" min-width="200" show-overflow-tooltip />
            <el-table-column prop="roleName" label="角色" min-width="180">
              <template #default="{ row }">
                <span class="role-badge">{{ row.roleName }}</span>
              </template>
            </el-table-column>
            <el-table-column label="状态" width="100" align="center">
              <template #default="{ row }">
                <span class="status-dot" :class="row.status === 1 ? 'status-dot--on' : 'status-dot--off'" />
                <span class="status-text">{{ row.status === 1 ? '正常' : '禁用' }}</span>
              </template>
            </el-table-column>
            <el-table-column prop="createTime" label="创建时间" min-width="180" />
            <el-table-column label="操作" width="220" fixed="right">
              <template #default="{ row }">
                <el-button class="action-link" type="primary" link @click="openEditDialog(row)">编辑</el-button>
                <el-button class="action-link" type="warning" link @click="openPasswordDialog(row)">重置密码</el-button>
              </template>
            </el-table-column>
          </el-table>

          <div class="data-card__foot">
            <span class="data-card__total">共 {{ total }} 条记录</span>
            <el-pagination
              v-model:current-page="page"
              v-model:page-size="size"
              :total="total"
              :page-sizes="[10, 20, 50]"
              layout="sizes, prev, pager, next"
              background
              small
              @size-change="handlePageChange"
              @current-change="handlePageChange"
            />
          </div>
        </div>
      </section>

      <section v-if="activeTab === 'roles'" class="tab-panel">
        <div class="toolbar">
          <div class="toolbar__search">
            <el-select v-model="queryStatus" clearable placeholder="状态筛选" class="toolbar__select">
              <el-option label="启用" :value="1" />
              <el-option label="禁用" :value="0" />
            </el-select>
            <el-button class="toolbar__btn-query" @click="loadRoles">
              <el-icon :size="16"><Search /></el-icon>
              <span>查询</span>
            </el-button>
          </div>
          <el-button type="primary" class="toolbar__btn-create" @click="openRoleCreateDialog">
            <el-icon :size="16"><Plus /></el-icon>
            <span>新增角色</span>
          </el-button>
        </div>

        <div class="data-card">
          <el-table
            :data="roles"
            v-loading="rolesLoading"
            class="data-table"
            stripe
            empty-text="暂无角色数据"
          >
            <el-table-column prop="name" label="角色名称" min-width="140">
              <template #default="{ row }">
                <span class="role-name-cell">
                  <span class="role-name-cell__text">{{ row.name }}</span>
                  <span v-if="row.code === 'SUPER_ADMIN'" class="super-badge">超管</span>
                </span>
              </template>
            </el-table-column>
            <el-table-column prop="code" label="角色编码" min-width="180">
              <template #default="{ row }">
                <code class="role-code">{{ row.code }}</code>
              </template>
            </el-table-column>
            <el-table-column prop="description" label="描述" min-width="220" show-overflow-tooltip />
            <el-table-column label="状态" width="100" align="center">
              <template #default="{ row }">
                <span class="status-dot" :class="row.status === 1 ? 'status-dot--on' : 'status-dot--off'" />
                <span class="status-text">{{ row.status === 1 ? '启用' : '禁用' }}</span>
              </template>
            </el-table-column>
            <el-table-column prop="userCount" label="成员数" width="90" align="center">
              <template #default="{ row }">
                <span class="member-count">{{ row.userCount ?? 0 }}</span>
              </template>
            </el-table-column>
            <el-table-column label="菜单权限" min-width="280">
              <template #default="{ row }">
                <div class="permission-tags">
                  <span
                    v-for="permission in row.menuPermissions"
                    :key="permission"
                    class="perm-tag"
                  >
                    {{ permissionLabel(permission) }}
                  </span>
                </div>
              </template>
            </el-table-column>
            <el-table-column label="操作" width="160" fixed="right">
              <template #default="{ row }">
                <el-button class="action-link" type="primary" link @click="openRoleEditDialog(row)">编辑</el-button>
                <el-button
                  v-if="row.code !== 'SUPER_ADMIN'"
                  class="action-link action-link--danger"
                  type="danger"
                  link
                  @click="handleRoleDelete(row)"
                >
                  删除
                </el-button>
              </template>
            </el-table-column>
          </el-table>
        </div>
      </section>
    </div>

    <el-dialog
      v-model="dialogVisible"
      :title="isEdit ? '编辑运营账号' : '新增运营账号'"
      width="540px"
      class="ops-dialog"
      :close-on-click-modal="false"
    >
      <el-form ref="formRef" :model="formModel" :rules="rules" label-width="80px" class="ops-form">
        <div class="form-section">
          <el-form-item label="账号" prop="username">
            <el-input v-model="formModel.username" :disabled="isEdit" placeholder="请输入账号名" />
          </el-form-item>
          <el-form-item label="邮箱" prop="email">
            <el-input v-model="formModel.email" placeholder="可选，可与用户名二选一登录" clearable />
          </el-form-item>
        </div>
        <div v-if="!isEdit" class="form-section">
          <el-form-item label="密码" prop="password">
            <el-input v-model="formModel.password" type="password" show-password placeholder="请输入密码" />
          </el-form-item>
        </div>
        <div class="form-section">
          <el-form-item label="角色" prop="roleId">
            <el-select v-model="formModel.roleId" class="form-full-width" placeholder="请选择角色">
              <el-option
                v-for="item in roleOptions"
                :key="item.id"
                :label="item.name"
                :value="item.id"
              />
            </el-select>
          </el-form-item>
          <el-form-item label="状态" prop="status">
            <el-radio-group v-model="formModel.status" class="status-radio-group">
              <el-radio-button :value="1">
                <span class="radio-label radio-label--on">正常</span>
              </el-radio-button>
              <el-radio-button :value="0">
                <span class="radio-label radio-label--off">禁用</span>
              </el-radio-button>
            </el-radio-group>
          </el-form-item>
        </div>
      </el-form>
      <template #footer>
        <div class="dialog-footer">
          <el-button class="btn-cancel" @click="dialogVisible = false">取消</el-button>
          <el-button type="primary" class="btn-save" :loading="submitLoading" @click="handleSubmit">保存</el-button>
        </div>
      </template>
    </el-dialog>

    <el-dialog
      v-model="passwordDialogVisible"
      title="重置运营账号密码"
      width="440px"
      class="ops-dialog"
      :close-on-click-modal="false"
    >
      <el-form ref="passwordFormRef" :model="passwordForm" :rules="passwordRules" label-width="90px" class="ops-form">
        <div class="form-section">
          <el-form-item label="账号">
            <span class="form-static-text">{{ passwordTarget?.username }}</span>
          </el-form-item>
          <el-form-item label="新密码" prop="password">
            <el-input v-model="passwordForm.password" type="password" show-password placeholder="请输入新密码" />
          </el-form-item>
        </div>
      </el-form>
      <template #footer>
        <div class="dialog-footer">
          <el-button class="btn-cancel" @click="passwordDialogVisible = false">取消</el-button>
          <el-button type="primary" class="btn-save" :loading="passwordSubmitting" @click="handleResetPassword">确认</el-button>
        </div>
      </template>
    </el-dialog>

    <el-dialog
      v-model="roleDialogVisible"
      :title="roleIsEdit ? '编辑角色' : '新增角色'"
      width="620px"
      class="ops-dialog"
      :close-on-click-modal="false"
    >
      <el-form ref="roleFormRef" :model="roleFormModel" :rules="roleRules" label-width="90px" class="ops-form">
        <div class="form-section">
          <el-form-item label="角色名称" prop="name">
            <el-input v-model="roleFormModel.name" placeholder="请输入角色名称" />
          </el-form-item>
          <el-form-item label="角色编码" prop="code">
            <el-input v-model="roleFormModel.code" :disabled="roleIsEdit" placeholder="请输入唯一的角色编码" />
          </el-form-item>
          <el-form-item label="角色描述">
            <el-input v-model="roleFormModel.description" placeholder="可选，简要描述该角色的用途" />
          </el-form-item>
          <el-form-item label="状态" prop="status">
            <el-radio-group v-model="roleFormModel.status" class="status-radio-group">
              <el-radio-button :value="1">
                <span class="radio-label radio-label--on">启用</span>
              </el-radio-button>
              <el-radio-button :value="0">
                <span class="radio-label radio-label--off">禁用</span>
              </el-radio-button>
            </el-radio-group>
          </el-form-item>
        </div>
        <div class="form-section">
          <el-form-item label="菜单权限" prop="menuPermissions" class="perm-form-item">
            <el-checkbox-group v-model="roleFormModel.menuPermissions" class="perm-checkbox-group">
              <el-checkbox
                v-for="item in permissionOptions"
                :key="item.value"
                :value="item.value"
                class="perm-checkbox"
              >
                <span class="perm-checkbox__label">{{ item.label }}</span>
              </el-checkbox>
            </el-checkbox-group>
          </el-form-item>
        </div>
      </el-form>
      <template #footer>
        <div class="dialog-footer">
          <el-button class="btn-cancel" @click="roleDialogVisible = false">取消</el-button>
          <el-button type="primary" class="btn-save" :loading="roleSubmitLoading" @click="handleRoleSubmit">保存</el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import type { FormInstance, FormRules } from 'element-plus'
import type { AdminRoleItem, OpsAccountItem } from '@/types/api'
import {
  createOpsAccount,
  listEnabledAdminRoles,
  listOpsAccounts,
  resetOpsAccountPassword,
  updateOpsAccount
} from '@/api/opsAccount'
import { createAdminRole, deleteAdminRole, listAdminRoles, updateAdminRole } from '@/api/role'
import {
  UserFilled,
  CircleCheck,
  Avatar,
  Lock,
  Search,
  Plus
} from '@element-plus/icons-vue'

const route = useRoute()
const router = useRouter()

const permissionOptions = [
  { value: 'dashboard', label: '仪表盘' },
  { value: 'users', label: '用户管理' },
  { value: 'categories', label: '分类管理' },
  { value: 'bookstore', label: '星夜书库' },
  { value: 'recommendations', label: '推荐管理' },
  { value: 'novels', label: '作品管理' },
  { value: 'community', label: '社区管理' },
  { value: 'announcements', label: '公告管理' },
  { value: 'activities', label: '活动管理' },
  { value: 'redeem-codes', label: '兑换码' },
  { value: 'growth-tasks', label: '任务管理' },
  { value: 'billing', label: '计费配置' },
  { value: 'orders', label: '订单管理' },
  { value: 'ai-config', label: 'AI配置' },
  { value: 'vector-db', label: '向量数据库' },
  { value: 'storage', label: '存储管理' },
  { value: 'system-config', label: '系统配置' },
  { value: 'logs', label: '操作日志' },
  { value: 'cache', label: '缓存查看' },
  { value: 'system', label: '系统设置' },
  { value: 'ops-accounts', label: '账号与权限' }
]

function permissionLabel(value: string) {
  if (value === 'roles') return '账号与权限'
  if (value === 'queue') return '系统设置'
  return permissionOptions.find((item) => item.value === value)?.label || value
}

function normalizeMenuPermissionsForForm(perms: string[] | undefined): string[] {
  if (!perms?.length) return []
  const next = new Set(perms.filter((p) => p !== 'roles'))
  if (perms.includes('roles')) next.add('ops-accounts')
  if (next.has('queue')) {
    next.delete('queue')
    next.add('system')
  }
  return [...next]
}

const activeTab = ref<'accounts' | 'roles'>('accounts')

function syncTabFromRoute() {
  activeTab.value = route.query.tab === 'roles' ? 'roles' : 'accounts'
}

watch(
  () => route.query.tab,
  () => syncTabFromRoute(),
  { immediate: true }
)

function onTabChange(name: string) {
  const tab = name === 'roles' ? 'roles' : 'accounts'
  const q = { ...route.query } as Record<string, string | string[]>
  if (tab === 'roles') q.tab = 'roles'
  else delete q.tab
  router.replace({ path: route.path, query: q })
}

const loading = ref(false)
const submitLoading = ref(false)
const passwordSubmitting = ref(false)
const accounts = ref<OpsAccountItem[]>([])
const roleOptions = ref<AdminRoleItem[]>([])
const keyword = ref('')
const statusFilter = ref<number | undefined>(undefined)
const page = ref(1)
const size = ref(10)
const total = ref(0)

const accountStats = computed(() => {
  const totalAll = Number(total.value)
  const activeOnPage = accounts.value.filter((a) => a.status === 1).length
  return { total: totalAll, active: activeOnPage }
})

const roleStats = computed(() => {
  const total = roles.value.length
  const active = roles.value.filter((r) => r.status === 1).length
  return { total, active }
})

const dialogVisible = ref(false)
const isEdit = ref(false)
const formRef = ref<FormInstance>()
const formModel = reactive({
  id: undefined as number | undefined,
  username: '',
  email: '',
  password: '',
  roleId: undefined as number | undefined,
  status: 1
})
const rules: FormRules = {
  username: [{ required: true, message: '请输入账号', trigger: 'blur' }],
  password: [{ required: true, message: '请输入密码', trigger: 'blur' }],
  roleId: [{ required: true, message: '请选择角色', trigger: 'change' }],
  status: [{ required: true, message: '请选择状态', trigger: 'change' }]
}

const passwordDialogVisible = ref(false)
const passwordFormRef = ref<FormInstance>()
const passwordTarget = ref<OpsAccountItem | null>(null)
const passwordForm = reactive({ password: '' })
const passwordRules: FormRules = {
  password: [{ required: true, message: '请输入新密码', trigger: 'blur' }]
}

const roles = ref<AdminRoleItem[]>([])
const rolesLoading = ref(false)
const queryStatus = ref<number | undefined>(undefined)
const roleDialogVisible = ref(false)
const roleIsEdit = ref(false)
const roleFormRef = ref<FormInstance>()
const roleSubmitLoading = ref(false)
const roleFormModel = reactive<AdminRoleItem>({
  id: undefined,
  name: '',
  code: '',
  description: '',
  status: 1,
  menuPermissions: []
})
const roleRules: FormRules = {
  name: [{ required: true, message: '请输入角色名称', trigger: 'blur' }],
  code: [{ required: true, message: '请输入角色编码', trigger: 'blur' }],
  status: [{ required: true, message: '请选择状态', trigger: 'change' }],
  menuPermissions: [{ type: 'array', required: true, message: '请选择菜单权限', trigger: 'change' }]
}

async function loadRoleOptions() {
  const res = await listEnabledAdminRoles()
  roleOptions.value = res.data
}

async function loadAccounts() {
  loading.value = true
  try {
    const res = await listOpsAccounts({
      page: page.value,
      size: size.value,
      keyword: keyword.value || undefined,
      status: statusFilter.value
    })
    accounts.value = res.data.records
    total.value = Number(res.data.total)
  } finally {
    loading.value = false
  }
}

async function loadRoles() {
  rolesLoading.value = true
  try {
    const res = await listAdminRoles(queryStatus.value)
    roles.value = res.data
  } finally {
    rolesLoading.value = false
  }
}

watch(
  activeTab,
  (tab) => {
    if (tab === 'roles') void loadRoles()
  },
  { immediate: true }
)

function handleSearch() {
  page.value = 1
  loadAccounts()
}

function handlePageChange() {
  loadAccounts()
}

function resetForm() {
  Object.assign(formModel, {
    id: undefined,
    username: '',
    email: '',
    password: '',
    roleId: undefined,
    status: 1
  })
}

function openCreateDialog() {
  isEdit.value = false
  resetForm()
  dialogVisible.value = true
}

function openEditDialog(row: OpsAccountItem) {
  isEdit.value = true
  Object.assign(formModel, {
    id: row.id,
    username: row.username,
    email: row.email ?? '',
    password: '',
    roleId: row.roleId,
    status: row.status
  })
  dialogVisible.value = true
}

async function handleSubmit() {
  if (!formRef.value) return
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return

  submitLoading.value = true
  try {
    if (isEdit.value && formModel.id) {
      await updateOpsAccount(formModel.id, {
        email: formModel.email.trim(),
        roleId: formModel.roleId as number,
        status: formModel.status
      })
      ElMessage.success('运营账号已更新')
    } else {
      await createOpsAccount({
        username: formModel.username.trim(),
        email: formModel.email.trim() || undefined,
        password: formModel.password,
        roleId: formModel.roleId as number,
        status: formModel.status
      })
      ElMessage.success('运营账号已创建')
    }
    dialogVisible.value = false
    await loadAccounts()
  } finally {
    submitLoading.value = false
  }
}

function openPasswordDialog(row: OpsAccountItem) {
  passwordTarget.value = row
  passwordForm.password = ''
  passwordDialogVisible.value = true
}

async function handleResetPassword() {
  if (!passwordFormRef.value || !passwordTarget.value?.id) return
  const valid = await passwordFormRef.value.validate().catch(() => false)
  if (!valid) return
  passwordSubmitting.value = true
  try {
    await resetOpsAccountPassword(passwordTarget.value.id, passwordForm.password)
    ElMessage.success('密码已重置')
    passwordDialogVisible.value = false
  } finally {
    passwordSubmitting.value = false
  }
}

function resetRoleForm() {
  Object.assign(roleFormModel, {
    id: undefined,
    name: '',
    code: '',
    description: '',
    status: 1,
    menuPermissions: []
  })
}

function openRoleCreateDialog() {
  roleIsEdit.value = false
  resetRoleForm()
  roleDialogVisible.value = true
}

function openRoleEditDialog(row: AdminRoleItem) {
  roleIsEdit.value = true
  Object.assign(roleFormModel, {
    ...row,
    menuPermissions: normalizeMenuPermissionsForForm(row.menuPermissions)
  })
  roleDialogVisible.value = true
}

async function handleRoleSubmit() {
  if (!roleFormRef.value) return
  const valid = await roleFormRef.value.validate().catch(() => false)
  if (!valid) return

  const payload: AdminRoleItem = {
    ...roleFormModel,
    menuPermissions: roleFormModel.menuPermissions.filter((p) => p !== 'roles')
  }

  roleSubmitLoading.value = true
  try {
    if (roleIsEdit.value && roleFormModel.id) {
      await updateAdminRole(roleFormModel.id, payload)
      ElMessage.success('角色已更新')
    } else {
      await createAdminRole(payload)
      ElMessage.success('角色已创建')
    }
    roleDialogVisible.value = false
    await loadRoles()
    await loadRoleOptions()
  } finally {
    roleSubmitLoading.value = false
  }
}

async function handleRoleDelete(row: AdminRoleItem) {
  await ElMessageBox.confirm(`确认删除角色「${row.name}」吗？`, '删除确认', {
    type: 'warning'
  })
  await deleteAdminRole(row.id as number)
  ElMessage.success('角色已删除')
  await loadRoles()
  await loadRoleOptions()
}

loadRoleOptions()
loadAccounts()
</script>

<style scoped lang="scss">
.admin-ops-accounts {
  background: transparent;
  border-radius: $radius-lg;
  overflow: hidden;
}

.page-header {
  padding: clamp($space-lg, 3vw, $space-xl);
  border-bottom: 1px solid $border-subtle;
}

.page-header__inner {
  margin-bottom: $space-md;
}

.page-header__text {
  h1 {
    margin: 0;
    font-size: $font-size-3xl;
    font-weight: 700;
    letter-spacing: -0.02em;
    color: $text-primary;
  }
}

.page-header__lead {
  margin: $space-sm 0 0;
  max-width: 56ch;
  font-size: $font-size-sm;
  font-weight: 400;
  line-height: 1.55;
  color: $text-muted;
}

.stats-summary {
  display: flex;
  gap: $space-md;
  flex-wrap: wrap;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 18px;
  background: $bg-elevated;
  border: 1px solid $border-subtle;
  border-radius: $radius-md;
  min-width: 160px;
  transition: border-color $transition-fast, box-shadow $transition-fast;

  &:hover {
    border-color: $border-default;
    box-shadow: $shadow-sm;
  }
}

.stat-item__icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 10px;
  color: #fff;
  flex-shrink: 0;

  &--violet { background: linear-gradient(135deg, #6366f1, #4f46e5); }
  &--emerald { background: linear-gradient(135deg, #34d399, #059669); }
  &--amber { background: linear-gradient(135deg, #fbbf24, #d97706); }
  &--cyan { background: linear-gradient(135deg, #22d3ee, #0891b2); }
}

.stat-item__body {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.stat-item__label {
  font-size: $font-size-xs;
  font-weight: 500;
  color: $text-muted;
  line-height: 1.2;
}

.stat-item__value {
  font-size: $font-size-2xl;
  font-weight: 700;
  letter-spacing: -0.03em;
  color: $text-primary;
  line-height: 1.15;
}

.page-content {
  padding: clamp($space-lg, 2.5vw, $space-xl);
}

.pill-tabs {
  display: inline-flex;
  gap: 4px;
  padding: 4px;
  background: $bg-elevated;
  border-radius: $radius-md;
  margin-bottom: $space-lg;
  border: 1px solid $border-subtle;
}

.pill-tab {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 8px 20px;
  border: none;
  border-radius: 9px;
  background: transparent;
  color: $text-muted;
  font-size: $font-size-sm;
  font-weight: 500;
  cursor: pointer;
  transition: all $transition-fast;
  font-family: inherit;
  line-height: 1.4;

  &:hover {
    color: $text-secondary;
    background: rgba(148, 163, 184, 0.08);
  }

  &--active {
    color: $text-primary !important;
    background: $primary-ghost !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
    font-weight: 600;
  }
}

.tab-panel {
  animation: fade-slide-in 0.22s ease;
}

@keyframes fade-slide-in {
  from {
    opacity: 0;
    transform: translateY(6px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: $space-md;
  flex-wrap: wrap;
  margin-bottom: $space-md;
}

.toolbar__search {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
  flex: 1;
  min-width: 0;
}

.toolbar__input {
  width: 260px;
}

.toolbar__select {
  width: 150px;
}

.toolbar__btn-query {
  border-radius: $radius-sm;
  padding: 8px 18px;
  font-weight: 500;
  border-color: $border-subtle;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: transparent;
  color: $text-secondary;

  &:hover {
    border-color: $border-default;
    color: $primary-light;
  }
}

.toolbar__btn-create {
  border-radius: $radius-sm;
  padding: 8px 20px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.data-card {
  border: 1px solid $border-subtle;
  border-radius: $radius-lg;
  overflow: hidden;
  box-shadow: $shadow-card;
  transition: box-shadow $transition-fast, border-color $transition-fast;

  &:hover {
    border-color: $border-default;
  }
}

.data-card__foot {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  padding: 14px 18px;
  border-top: 1px solid $border-subtle;
  background: rgba(255, 255, 255, 0.02);
}

.data-card__total {
  font-size: $font-size-sm;
  color: $text-muted;
  font-weight: 500;
}

.data-table {
  :deep(th.el-table__cell) {
    color: $text-muted;
    font-weight: 600;
    font-size: $font-size-sm;
    border-bottom: 2px solid $border-subtle;
  }

  :deep(.el-table__body tr:hover > td) {
    background: rgba(99, 102, 241, 0.04) !important;
  }

  :deep(.el-table__empty-block) {
    min-height: 160px;
  }

  :deep(.el-table__empty-text) {
    color: $text-muted;
    font-size: $font-size-sm;
  }
}

.table-user-cell {
  display: flex;
  align-items: center;
  gap: 10px;
}

.table-user-cell__avatar {
  flex-shrink: 0;
  background: linear-gradient(135deg, #6366f1, #38bdf8);
  color: #fff;
  font-weight: 600;
  font-size: $font-size-sm;
}

.table-user-cell__name {
  font-weight: 600;
  color: $text-primary;
  font-size: $font-size-sm;
}

.status-dot {
  display: inline-block;
  width: 7px;
  height: 7px;
  border-radius: 50%;
  margin-right: 6px;
  vertical-align: middle;

  &--on {
    background: $success-color;
    box-shadow: 0 0 0 3px $success-glow;
  }

  &--off {
    background: $text-disabled;
    box-shadow: 0 0 0 3px rgba(148, 163, 184, 0.12);
  }
}

.status-text {
  font-size: $font-size-sm;
  font-weight: 500;
  color: $text-secondary;
  vertical-align: middle;
}

.role-badge {
  display: inline-block;
  padding: 3px 12px;
  border-radius: $radius-sm;
  font-size: $font-size-xs;
  font-weight: 500;
  color: $primary-light;
  background: $primary-ghost;
  border: 1px solid $border-accent;
}

.role-name-cell {
  display: flex;
  align-items: center;
  gap: 8px;
}

.role-name-cell__text {
  font-weight: 600;
  color: $text-primary;
}

.super-badge {
  display: inline-block;
  padding: 1px 7px;
  border-radius: $radius-xs;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.04em;
  color: #fff;
  background: linear-gradient(135deg, #ef4444, #dc2626);
}

.role-code {
  padding: 2px 8px;
  border-radius: $radius-xs;
  font-size: $font-size-xs;
  font-weight: 600;
  background: $bg-elevated;
  color: $text-muted;
  font-family: 'SF Mono', 'Fira Code', 'Cascadia Code', monospace;
  letter-spacing: 0.03em;
}

.member-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 28px;
  height: 26px;
  padding: 0 8px;
  border-radius: $radius-full;
  font-size: $font-size-sm;
  font-weight: 600;
  color: $text-secondary;
  background: $bg-elevated;
}

.permission-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
}

.perm-tag {
  display: inline-block;
  padding: 2px 10px;
  border-radius: 5px;
  font-size: $font-size-xs;
  font-weight: 500;
  color: $text-secondary;
  background: rgba(148, 163, 184, 0.06);
  border: 1px solid $border-subtle;
  transition: border-color $transition-fast, color $transition-fast;

  &:hover {
    border-color: $border-default;
    color: $text-primary;
  }
}

.action-link {
  font-weight: 500;
  font-size: $font-size-sm;

  &--danger {
    &:hover {
      color: #dc2626 !important;
    }
  }
}

:deep(.ops-dialog) {
  .el-dialog {
    border-radius: $radius-lg;
    overflow: hidden;
    box-shadow: $shadow-xl;
  }

  .el-dialog__header {
    padding: 22px 28px 14px;
    border-bottom: 1px solid $border-subtle;
    margin: 0;
  }

  .el-dialog__title {
    font-size: $font-size-lg;
    font-weight: 700;
    color: $text-primary;
    letter-spacing: -0.01em;
  }

  .el-dialog__body {
    padding: 20px 28px;
  }

  .el-dialog__footer {
    padding: 14px 28px 22px;
    border-top: 1px solid $border-subtle;
  }
}

.ops-form {
  .form-section {
    & + .form-section {
      margin-top: 4px;
      padding-top: 16px;
      border-top: 1px solid $border-subtle;
    }
  }

  .el-form-item {
    margin-bottom: 18px;
  }

  :deep(.el-form-item__label) {
    font-weight: 600;
    color: $text-secondary;
    font-size: $font-size-sm;
  }

  .form-full-width {
    width: 100%;
  }
}

.form-static-text {
  font-weight: 600;
  color: $text-primary;
  font-size: $font-size-sm;
}

.status-radio-group {
  :deep(.el-radio-button) {
    .el-radio-button__inner {
      border-radius: 9px;
      padding: 7px 22px;
      font-weight: 500;
      font-size: $font-size-sm;
      border-color: $border-subtle;
      background: $bg-surface;
      color: $text-secondary;
      transition: all $transition-fast;
    }

    &:first-child .el-radio-button__inner {
      border-left-color: $border-subtle;
    }
  }

  :deep(.el-radio-button.is-active .el-radio-button__inner) {
    background: $primary-color;
    border-color: $primary-color;
    color: #fff;
    box-shadow: $glow-primary;
  }
}

.radio-label {
  display: flex;
  align-items: center;
  gap: 6px;

  &::before {
    content: '';
    display: inline-block;
    width: 6px;
    height: 6px;
    border-radius: 50%;
  }

  &--on::before { background: $success-color; }
  &--off::before { background: $text-disabled; }
}

.perm-form-item {
  :deep(.el-form-item__content) {
    display: block;
  }
}

.perm-checkbox-group {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 6px;

  :deep(.el-checkbox) {
    margin-right: 0;
  }
}

.perm-checkbox {
  :deep(.el-checkbox__input) {
    vertical-align: middle;
  }

  :deep(.el-checkbox__label) {
    font-size: $font-size-sm;
    color: $text-secondary;
    vertical-align: middle;
    padding-left: 6px;
  }
}

.perm-checkbox__label {
  font-size: $font-size-sm;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

.btn-cancel {
  border-radius: $radius-sm;
  padding: 9px 24px;
  font-weight: 500;
  border-color: $border-subtle;

  &:hover {
    border-color: $border-default;
    color: $text-secondary;
  }
}

.btn-save {
  border-radius: $radius-sm;
  padding: 9px 24px;
  font-weight: 600;
}
</style>
