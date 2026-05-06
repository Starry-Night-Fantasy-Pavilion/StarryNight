<template>
  <div class="ops-profile page-container">
    <div class="page-header">
      <h1>个人中心</h1>
      <p class="page-header__lead">维护联系邮箱与登录密码；登录用户名仅超级管理员可在本页修改。</p>
    </div>

    <div class="page-content">
      <el-row :gutter="20">
        <el-col :xs="24" :lg="12">
          <el-card shadow="hover">
            <template #header>
              <span>账号资料</span>
            </template>
            <el-descriptions :column="1" border class="ops-profile__desc">
              <el-descriptions-item label="角色">{{ profile?.roleName || '—' }}</el-descriptions-item>
              <el-descriptions-item label="角色编码">{{ profile?.roleCode || '—' }}</el-descriptions-item>
            </el-descriptions>
            <el-form ref="profileFormRef" :model="profileForm" :rules="profileRules" label-width="100px" class="ops-profile__form">
              <el-form-item label="登录用户名" prop="username">
                <el-input
                  v-model="profileForm.username"
                  :disabled="!isSuperAdmin"
                  placeholder="仅超级管理员可修改"
                />
                <div v-if="!isSuperAdmin" class="form-hint">如需修改登录用户名，请联系超级管理员。</div>
              </el-form-item>
              <el-form-item label="邮箱" prop="email">
                <el-input v-model="profileForm.email" placeholder="可用于登录与接收通知" clearable />
              </el-form-item>
              <el-form-item>
                <el-button type="primary" :loading="profileSaving" @click="submitProfile">保存资料</el-button>
              </el-form-item>
            </el-form>
          </el-card>
        </el-col>
        <el-col :xs="24" :lg="12">
          <el-card shadow="hover">
            <template #header>
              <span>修改密码</span>
            </template>
            <el-form ref="pwdFormRef" :model="pwdForm" :rules="pwdRules" label-width="100px">
              <el-form-item label="当前密码" prop="oldPassword">
                <el-input v-model="pwdForm.oldPassword" type="password" show-password autocomplete="current-password" />
              </el-form-item>
              <el-form-item label="新密码" prop="newPassword">
                <el-input v-model="pwdForm.newPassword" type="password" show-password autocomplete="new-password" />
              </el-form-item>
              <el-form-item label="确认新密码" prop="confirmPassword">
                <el-input v-model="pwdForm.confirmPassword" type="password" show-password autocomplete="new-password" />
              </el-form-item>
              <el-form-item>
                <el-button type="primary" :loading="pwdSaving" @click="submitPassword">更新密码</el-button>
              </el-form-item>
            </el-form>
          </el-card>
        </el-col>
      </el-row>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessage } from 'element-plus'
import { useOpsSessionStore } from '@/stores/auth'
import { updateOpsSelfPassword, updateOpsSelfProfile } from '@/api/opsSelf'
import type { UserInfo } from '@/types/api'

const authStore = useOpsSessionStore()
const profile = ref<UserInfo | null>(null)
const profileFormRef = ref<FormInstance>()
const pwdFormRef = ref<FormInstance>()
const profileSaving = ref(false)
const pwdSaving = ref(false)

const profileForm = reactive({
  username: '',
  email: ''
})

const pwdForm = reactive({
  oldPassword: '',
  newPassword: '',
  confirmPassword: ''
})

const isSuperAdmin = computed(() => authStore.userInfo?.roleCode === 'SUPER_ADMIN')

const profileRules: FormRules = {
  email: [
    {
      validator: (_r, v: string, cb) => {
        const s = (v || '').trim()
        if (!s) {
          cb()
          return
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(s)) {
          cb(new Error('邮箱格式不正确'))
          return
        }
        cb()
      },
      trigger: 'blur'
    }
  ]
}

const pwdRules: FormRules = {
  oldPassword: [{ required: true, message: '请输入当前密码', trigger: 'blur' }],
  newPassword: [
    { required: true, message: '请输入新密码', trigger: 'blur' },
    { min: 6, max: 32, message: '密码长度为6-32位', trigger: 'blur' }
  ],
  confirmPassword: [
    { required: true, message: '请再次输入新密码', trigger: 'blur' },
    {
      validator: (_r, v: string, cb) => {
        if (v !== pwdForm.newPassword) cb(new Error('两次输入的密码不一致'))
        else cb()
      },
      trigger: 'blur'
    }
  ]
}

function syncFormFromStore() {
  const u = authStore.userInfo
  if (u) {
    profile.value = u
    profileForm.username = u.username || ''
    profileForm.email = u.email || ''
  }
}

async function loadProfile() {
  try {
    await authStore.fetchProfile()
    syncFormFromStore()
  } catch {
    /* fetchProfile 或网关已提示 */
  }
}

void loadProfile()

async function submitProfile() {
  if (!profileFormRef.value) return
  await profileFormRef.value.validate(async (ok) => {
    if (!ok) return
    profileSaving.value = true
    try {
      const payload: { email: string; username?: string } = {
        email: profileForm.email.trim()
      }
      if (isSuperAdmin.value) {
        const u = profileForm.username.trim()
        if (u !== (profile.value?.username || '')) {
          payload.username = u
        }
      }
      await updateOpsSelfProfile(payload)
      ElMessage.success('资料已保存')
      await authStore.fetchProfile()
      syncFormFromStore()
    } finally {
      profileSaving.value = false
    }
  })
}

async function submitPassword() {
  if (!pwdFormRef.value) return
  await pwdFormRef.value.validate(async (ok) => {
    if (!ok) return
    pwdSaving.value = true
    try {
      await updateOpsSelfPassword({
        oldPassword: pwdForm.oldPassword,
        newPassword: pwdForm.newPassword
      })
      ElMessage.success('密码已更新')
      pwdForm.oldPassword = ''
      pwdForm.newPassword = ''
      pwdForm.confirmPassword = ''
      pwdFormRef.value.resetFields()
    } finally {
      pwdSaving.value = false
    }
  })
}
</script>

<style scoped lang="scss">
.ops-profile__desc {
  margin-bottom: 16px;
}

.ops-profile__form {
  max-width: 480px;
}

.form-hint {
  margin-top: 4px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
</style>
