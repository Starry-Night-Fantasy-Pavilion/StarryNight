<template>
  <div class="auth-page">
    <div class="auth-bg">
      <div class="ab-glow ab-top"></div>
      <div class="ab-glow ab-bot"></div>
    </div>
    <div class="auth-box">
      <div class="auth-logo">
        <svg viewBox="0 0 32 32" width="40" height="40" fill="none">
          <circle cx="16" cy="16" r="14" stroke="url(#rlg)" stroke-width="1.5" opacity="0.5" />
          <circle cx="16" cy="16" r="4" fill="url(#rlg)" />
          <circle cx="6" cy="8" r="1.2" fill="url(#rlg)" opacity="0.6" />
          <defs>
            <linearGradient id="rlg" x1="0" y1="0" x2="32" y2="32">
              <stop offset="0%" stop-color="#a78bfa" />
              <stop offset="100%" stop-color="#6366f1" />
            </linearGradient>
          </defs>
        </svg>
        <span class="al-text">星夜</span>
      </div>
      <h1 class="auth-title">创建账号</h1>
      <p class="auth-desc">开启你的 AI 创作之旅</p>

      <el-form ref="formRef" :model="form" :rules="dynamicRules" size="large" @submit.prevent="doReg">
        <el-form-item prop="username">
          <el-input v-model="form.username" placeholder="用户名" :prefix-icon="User" />
        </el-form-item>
        <el-form-item prop="password">
          <el-input
            v-model="form.password"
            type="password"
            placeholder="密码（6-32 位）"
            :prefix-icon="Lock"
            show-password
          />
        </el-form-item>
        <el-form-item prop="cfmPwd">
          <el-input v-model="form.cfmPwd" type="password" placeholder="确认密码" :prefix-icon="Lock" show-password />
        </el-form-item>
        <el-form-item v-if="options.emailRegisterEnabled" prop="email">
          <el-input v-model="form.email" placeholder="邮箱（选填）" :prefix-icon="Message" />
        </el-form-item>
        <el-form-item v-if="options.phoneRegisterEnabled" prop="phone">
          <el-input v-model="form.phone" placeholder="手机号（选填，11 位）" :prefix-icon="Iphone" maxlength="11" />
        </el-form-item>
        <el-button class="btn-submit" :loading="loading" native-type="submit">注册</el-button>
      </el-form>

      <div class="auth-switch">
        <span>已有账号？</span>
        <router-link to="/auth/login" class="as-link">立即登录</router-link>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useUserSessionStore, useOpsSessionStore } from '@/stores/auth'
import { ElMessage } from 'element-plus'
import type { FormInstance, FormRules } from 'element-plus'
import { User, Lock, Message, Iphone } from '@element-plus/icons-vue'
import { fetchRegisterOptions } from '@/api/authPublic'

const router = useRouter()
const auth = useUserSessionStore()
const opsSession = useOpsSessionStore()
const formRef = ref<FormInstance>()
const loading = ref(false)
const options = reactive({
  emailRegisterEnabled: true,
  phoneRegisterEnabled: true
})

const form = reactive({
  username: '',
  password: '',
  cfmPwd: '',
  email: '',
  phone: ''
})

const baseRules: FormRules = {
  username: [
    { required: true, message: '请输入用户名', trigger: 'blur' },
    { min: 4, max: 20, message: '用户名为 4-20 位', trigger: 'blur' }
  ],
  password: [
    { required: true, message: '请输入密码', trigger: 'blur' },
    { min: 6, max: 32, message: '密码为 6-32 位', trigger: 'blur' }
  ],
  cfmPwd: [
    { required: true, message: '请确认密码', trigger: 'blur' },
    {
      validator: (_r, v: string, cb) => {
        if (v !== form.password) cb(new Error('两次密码不一致'))
        else cb()
      },
      trigger: 'blur'
    }
  ]
}

const dynamicRules = computed<FormRules>(() => {
  const r: FormRules = { ...baseRules }
  if (options.emailRegisterEnabled) {
    r.email = [{ type: 'email', message: '请输入正确邮箱', trigger: 'blur' }]
  }
  if (options.phoneRegisterEnabled) {
    r.phone = [
      {
        validator: (_r, v: string, cb) => {
          if (!v?.trim()) return cb()
          if (!/^1[3-9]\d{9}$/.test(v.trim())) cb(new Error('请输入 11 位手机号'))
          else cb()
        },
        trigger: 'blur'
      }
    ]
  }
  return r
})

onMounted(async () => {
  try {
    const o = await fetchRegisterOptions()
    options.emailRegisterEnabled = o.emailRegisterEnabled !== false
    options.phoneRegisterEnabled = o.phoneRegisterEnabled !== false
  } catch {
    /* 接口失败时默认全开，避免挡注册 */
  }
})

async function doReg() {
  const v = await formRef.value?.validate().catch(() => false)
  if (!v) return
  loading.value = true
  try {
    await auth.register({
      username: form.username.trim(),
      password: form.password,
      email: options.emailRegisterEnabled && form.email?.trim() ? form.email.trim() : undefined,
      phone: options.phoneRegisterEnabled && form.phone?.trim() ? form.phone.trim() : undefined
    })
    opsSession.logout()
    ElMessage.success('注册成功')
    router.push('/author')
  } catch {
    /* 错误文案由 store / 网关抛出 */
  } finally {
    loading.value = false
  }
}
</script>

<style lang="scss" scoped>
.auth-page {
  height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: $bg-root;
  position: relative;
  overflow: hidden;
}
.auth-bg {
  position: fixed;
  inset: 0;
  pointer-events: none;
}
.ab-glow {
  position: absolute;
  border-radius: 50%;
  filter: blur(100px);
  &.ab-top {
    top: -200px;
    right: -200px;
    width: 600px;
    height: 600px;
    background: rgba(99, 102, 241, 0.06);
  }
  &.ab-bot {
    bottom: -200px;
    left: -200px;
    width: 600px;
    height: 600px;
    background: rgba(167, 139, 250, 0.04);
  }
}
.auth-box {
  position: relative;
  z-index: 1;
  width: 100%;
  max-width: 440px;
  background: $bg-surface;
  border: 1px solid $border-color;
  border-radius: $border-radius-xl;
  padding: $space-2xl;
}
.auth-logo {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  margin-bottom: $space-lg;
}
.al-text {
  font-size: 22px;
  font-weight: 700;
  background: linear-gradient(135deg, #a78bfa, #818cf8);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}
.auth-title {
  font-size: $font-size-xl;
  font-weight: 700;
  color: $text-primary;
  text-align: center;
  margin-bottom: $space-xs;
}
.auth-desc {
  font-size: $font-size-sm;
  color: $text-secondary;
  text-align: center;
  margin-bottom: $space-lg;
}
:deep(.el-input__wrapper) {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  box-shadow: none;
}
:deep(.el-input__wrapper:hover) {
  border-color: rgba(255, 255, 255, 0.12);
}
:deep(.el-input__wrapper.is-focus) {
  border-color: $primary-color;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}
.btn-submit {
  width: 100%;
  height: 48px;
  background: linear-gradient(135deg, #6366f1, #4f46e5);
  border: none;
  color: #fff;
  font-weight: 600;
  font-size: 16px;
  border-radius: 12px;
  &:hover {
    background: linear-gradient(135deg, #818cf8, #6366f1);
    box-shadow: 0 0 24px rgba(99, 102, 241, 0.35);
    transform: translateY(-1px);
  }
}
.auth-switch {
  text-align: center;
  margin-top: $space-lg;
  font-size: $font-size-sm;
  color: $text-muted;
}
.as-link {
  color: $primary-light;
  font-weight: 500;
  margin-left: $space-xs;
  &:hover {
    color: $accent-color;
  }
}
</style>
