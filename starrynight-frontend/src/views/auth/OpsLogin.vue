<template>
  <div class="ops-login">
    <div class="ops-login__bg" aria-hidden="true" />
    <div class="ops-login__grid" aria-hidden="true" />

    <div class="ops-login__shell">
      <aside class="ops-login__hero">
        <div class="ops-login__hero-inner">
          <div class="ops-login__brand">
            <span class="ops-login__logo-mark" />
            <span class="ops-login__logo-text">星夜</span>
          </div>
          <h2 class="ops-login__hero-title">运营工作台</h2>
          <p class="ops-login__hero-desc">
            数据看板、用户与订单、系统配置与风控策略的统一入口。会话与用户端隔离，请使用已开通的运营账号。
          </p>
          <ul class="ops-login__hero-list">
            <li>权限与审计对齐后台策略</li>
            <li>与用户端登录入口相互独立</li>
          </ul>
          <p class="ops-login__hero-foot">星夜阅· 运营平台</p>
        </div>
      </aside>

      <main class="ops-login__panel">
        <div class="ops-login__card">
          <header class="ops-login__card-head">
            <span class="ops-login__badge">运营专线</span>
            <h1 class="ops-login__card-title">登录运营中心</h1>
            <p class="ops-login__card-sub">请输入用户名或邮箱，以及密码</p>
          </header>

          <el-form
            ref="formRef"
            :model="form"
            :rules="rules"
            class="ops-login__form"
            label-position="top"
            @submit.prevent="handleLogin"
          >
            <el-form-item label="用户名或邮箱" prop="username">
              <el-input
                v-model="form.username"
                placeholder="用户名或已绑定的邮箱"
                size="large"
                :prefix-icon="User"
                clearable
              />
            </el-form-item>
            <el-form-item label="密码" prop="password">
              <el-input
                v-model="form.password"
                type="password"
                placeholder="密码"
                size="large"
                :prefix-icon="Lock"
                show-password
                clearable
                @keyup.enter="handleLogin"
              />
            </el-form-item>
            <div class="ops-login__row">
              <el-button link type="primary" class="ops-login__link" @click="resetDialogVisible = true">
                忘记密码？
              </el-button>
            </div>
            <el-form-item class="ops-login__submit-wrap">
              <el-button
                type="primary"
                size="large"
                class="ops-login__submit"
                :loading="loading"
                native-type="submit"
              >
                进入工作台
              </el-button>
            </el-form-item>
          </el-form>

          <footer class="ops-login__card-foot">
            <router-link class="ops-login__back" to="/auth/login">前往用户端登录</router-link>
          </footer>
        </div>
      </main>
    </div>

    <el-dialog
      v-model="resetDialogVisible"
      title="找回密码"
      width="420px"
      class="ops-login__dialog"
      align-center
    >
      <el-form :model="resetForm" label-width="90px">
        <el-form-item label="用户名">
          <el-input v-model="resetForm.username" placeholder="请输入用户名" />
        </el-form-item>
        <el-form-item label="验证码">
          <div class="code-row">
            <el-input v-model="resetForm.code" placeholder="请输入验证码" />
            <el-button :loading="sendingCode" @click="handleSendCode">发送验证码</el-button>
          </div>
        </el-form-item>
        <el-form-item label="新密码">
          <el-input
            v-model="resetForm.newPassword"
            type="password"
            show-password
            placeholder="6-32 位新密码"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="resetDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="resettingPwd" @click="handleResetPassword">确认重置</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useOpsSessionStore, useUserSessionStore } from '@/stores/auth'
import { ElMessage } from 'element-plus'
import type { FormInstance, FormRules } from 'element-plus'
import { User, Lock } from '@element-plus/icons-vue'
import { safeOpsPostLoginRedirect } from '@/utils/authRedirect'
import { ADMIN_CONSOLE_BASE_PATH } from '@/config/portal'

const router = useRouter()
const route = useRoute()
const authStore = useOpsSessionStore()
const userSession = useUserSessionStore()

const formRef = ref<FormInstance>()
const loading = ref(false)
const resetDialogVisible = ref(false)
const sendingCode = ref(false)
const resettingPwd = ref(false)

const form = reactive({
  username: '',
  password: ''
})

const resetForm = reactive({
  username: '',
  code: '',
  newPassword: ''
})

const rules: FormRules = {
  username: [{ required: true, message: '请输入用户名或邮箱', trigger: 'blur' }],
  password: [{ required: true, message: '请输入密码', trigger: 'blur' }]
}

async function handleLogin() {
  if (!formRef.value) return

  await formRef.value.validate(async (valid) => {
    if (!valid) return

    loading.value = true
    try {
      await authStore.login(form.username, form.password)
      userSession.logout()
      ElMessage.success('登录成功')

      formRef.value?.clearValidate()

      const redirectPath = safeOpsPostLoginRedirect(route.query.redirect, ADMIN_CONSOLE_BASE_PATH)
      router.push(redirectPath)
    } catch {
      /* 请求层已提示 */
    } finally {
      loading.value = false
    }
  })
}

async function handleSendCode() {
  if (!resetForm.username.trim()) {
    ElMessage.warning('请先输入用户名')
    return
  }
  sendingCode.value = true
  try {
    await authStore.sendResetCode(resetForm.username.trim())
    ElMessage.success('验证码已发送，请查看系统日志（开发阶段）')
  } finally {
    sendingCode.value = false
  }
}

async function handleResetPassword() {
  if (!resetForm.username.trim() || !resetForm.code.trim() || !resetForm.newPassword.trim()) {
    ElMessage.warning('请完整填写用户名、验证码和新密码')
    return
  }
  resettingPwd.value = true
  try {
    await authStore.resetPassword(
      resetForm.username.trim(),
      resetForm.code.trim(),
      resetForm.newPassword
    )
    ElMessage.success('密码重置成功，请使用新密码登录')
    resetDialogVisible.value = false
    resetForm.code = ''
    resetForm.newPassword = ''
  } finally {
    resettingPwd.value = false
  }
}
</script>

<style lang="scss" scoped>
.ops-login {
  position: relative;
  min-height: 100vh;
  overflow-x: hidden;
  color: #e2e8f0;
}

.ops-login__bg {
  position: fixed;
  inset: 0;
  background:
    radial-gradient(ellipse 120% 80% at 18% 18%, rgba(99, 102, 241, 0.38), transparent 52%),
    radial-gradient(ellipse 90% 55% at 88% 12%, rgba(56, 189, 248, 0.14), transparent 48%),
    radial-gradient(ellipse 70% 50% at 50% 100%, rgba(15, 23, 42, 0.9), transparent 55%),
    linear-gradient(168deg, #070b14 0%, #0f172a 38%, #111827 100%);
  z-index: 0;
}

.ops-login__grid {
  position: fixed;
  inset: 0;
  background-image:
    linear-gradient(rgba(148, 163, 184, 0.06) 1px, transparent 1px),
    linear-gradient(90deg, rgba(148, 163, 184, 0.06) 1px, transparent 1px);
  background-size: 48px 48px;
  mask-image: radial-gradient(ellipse 85% 70% at 50% 50%, black 20%, transparent 75%);
  pointer-events: none;
  z-index: 0;
}

.ops-login__shell {
  position: relative;
  z-index: 1;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  width: 100%;
  max-width: 1120px;
  margin: 0 auto;
  padding: clamp($space-lg, 5vw, $space-xl);

  @media (min-width: 900px) {
    flex-direction: row;
    align-items: stretch;
    max-width: 1040px;
  }
}

.ops-login__hero {
  flex: 1;
  display: flex;
  align-items: center;
  padding: $space-xl 0;

  @media (min-width: 900px) {
    padding: $space-xl $space-xl $space-xl 0;
    max-width: 46%;
  }
}

.ops-login__hero-inner {
  width: 100%;
}

.ops-login__brand {
  display: flex;
  align-items: center;
  gap: $space-sm;
  margin-bottom: $space-xl;
}

.ops-login__logo-mark {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: linear-gradient(135deg, #6366f1, #38bdf8);
  box-shadow: 0 10px 28px rgba(99, 102, 241, 0.35);
}

.ops-login__logo-text {
  font-size: $font-size-xl;
  font-weight: 700;
  letter-spacing: 0.02em;
  color: #f8fafc;
}

.ops-login__hero-title {
  font-size: clamp(26px, 4vw, 34px);
  font-weight: 700;
  line-height: 1.25;
  color: #f8fafc;
  margin: 0 0 $space-md;
}

.ops-login__hero-desc {
  margin: 0 0 $space-lg;
  font-size: $font-size-md;
  line-height: 1.65;
  color: #94a3b8;
  max-width: 36ch;
}

.ops-login__hero-list {
  margin: 0 0 $space-xl;
  padding-left: 1.15rem;
  color: #cbd5e1;
  font-size: $font-size-sm;
  line-height: 1.8;

  li::marker {
    color: #38bdf8;
  }
}

.ops-login__hero-foot {
  margin: 0;
  font-size: $font-size-xs;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: #64748b;
}

.ops-login__panel {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: $space-lg 0;

  @media (min-width: 900px) {
    padding: $space-xl 0 $space-xl $space-md;
  }
}

.ops-login__card {
  width: 100%;
  max-width: 420px;
  padding: clamp($space-lg, 4vw, $space-xl);
  background: rgba(15, 23, 42, 0.62);
  border: 1px solid rgba(148, 163, 184, 0.22);
  border-radius: 18px;
  box-shadow:
    0 0 0 1px rgba(255, 255, 255, 0.05) inset,
    0 4px 24px rgba(0, 0, 0, 0.2),
    0 32px 64px -16px rgba(0, 0, 0, 0.55);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  transition: border-color 0.25s ease, box-shadow 0.25s ease;

  &:focus-within {
    border-color: rgba(99, 102, 241, 0.35);
    box-shadow:
      0 0 0 1px rgba(255, 255, 255, 0.06) inset,
      0 0 0 1px rgba(99, 102, 241, 0.2),
      0 32px 64px -16px rgba(0, 0, 0, 0.55);
  }
}

.ops-login__card-head {
  margin-bottom: $space-lg;
}

.ops-login__badge {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.06em;
  color: #38bdf8;
  background: rgba(56, 189, 248, 0.12);
  border: 1px solid rgba(56, 189, 248, 0.25);
  margin-bottom: $space-md;
}

.ops-login__card-title {
  margin: 0 0 $space-xs;
  font-size: $font-size-xl;
  font-weight: 700;
  color: #f8fafc;
}

.ops-login__card-sub {
  margin: 0;
  font-size: $font-size-sm;
  color: #94a3b8;
}

.ops-login__form {
  :deep(.el-form-item__label) {
    color: #cbd5e1;
    font-weight: 500;
  }

  :deep(.el-input__wrapper) {
    background: rgba(248, 250, 252, 0.97);
    border-radius: 12px;
    box-shadow: 0 0 0 1px rgba(148, 163, 184, 0.22) inset;
    transition: box-shadow 0.2s ease, background 0.2s ease;
  }

  :deep(.el-input__wrapper:hover) {
    background: #fff;
    box-shadow: 0 0 0 1px rgba(99, 102, 241, 0.25) inset;
  }

  :deep(.el-input__wrapper.is-focus) {
    background: #fff;
    box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.45) inset, 0 0 0 1px rgba(56, 189, 248, 0.2);
  }
}

.ops-login__row {
  display: flex;
  justify-content: flex-end;
  margin-top: -$space-sm;
  margin-bottom: $space-sm;
}

.ops-login__link {
  color: #38bdf8 !important;
  font-size: $font-size-sm;
}

.ops-login__submit-wrap {
  margin-bottom: 0;
}

.ops-login__submit {
  width: 100%;
  height: 46px;
  font-weight: 600;
  letter-spacing: 0.02em;
  border: none;
  border-radius: 12px !important;
  background: linear-gradient(100deg, #6366f1 0%, #4f46e5 55%, #4338ca 100%) !important;
  box-shadow: 0 10px 32px rgba(79, 70, 229, 0.4), 0 1px 0 rgba(255, 255, 255, 0.12) inset;
  transition: transform 0.15s ease, filter 0.2s ease, box-shadow 0.2s ease;

  &:hover {
    filter: brightness(1.05);
    box-shadow: 0 14px 36px rgba(79, 70, 229, 0.45), 0 1px 0 rgba(255, 255, 255, 0.14) inset;
  }

  &:active {
    transform: translateY(1px);
  }
}

.ops-login__card-foot {
  margin-top: $space-lg;
  padding-top: $space-lg;
  border-top: 1px solid rgba(148, 163, 184, 0.12);
  text-align: center;
}

.ops-login__back {
  font-size: $font-size-sm;
  color: #94a3b8;
  text-decoration: none;
  transition: color $transition-fast;

  &:hover {
    color: #38bdf8;
  }
}

.code-row {
  width: 100%;
  display: grid;
  grid-template-columns: 1fr auto;
  gap: $space-sm;
}
</style>

<style lang="scss">
.ops-login__dialog.el-dialog {
  border-radius: 16px !important;
  overflow: hidden;
  border: 1px solid rgba(148, 163, 184, 0.14) !important;
  box-shadow: 0 24px 64px rgba(15, 23, 42, 0.35) !important;
}

.ops-login__dialog .el-dialog__header {
  padding: 18px 20px 14px;
  margin: 0;
  font-weight: 600;
  border-bottom: 1px solid rgba(148, 163, 184, 0.1);
}

.ops-login__dialog .el-dialog__body {
  padding: 20px 20px 8px;
}

.ops-login__dialog .el-dialog__footer {
  padding: 12px 20px 18px;
  border-top: 1px solid rgba(148, 163, 184, 0.08);
}
</style>
