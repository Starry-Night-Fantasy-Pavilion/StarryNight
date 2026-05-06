<template>
  <div class="auth-page">
    <div class="auth-bg">
      <div class="ab-glow ab-top"></div>
      <div class="ab-glow ab-bot"></div>
    </div>

    <div class="auth-split">
      <aside class="auth-brand" aria-label="产品简介">
        <canvas ref="starCanvas" class="auth-brand__canvas" aria-hidden="true"></canvas>
        <div class="auth-brand__glow" aria-hidden="true"></div>
        <div class="auth-brand__inner">
          <div class="auth-brand__logo-row">
            <svg viewBox="0 0 32 32" class="auth-brand__mark" fill="none" aria-hidden="true">
              <circle cx="16" cy="16" r="14" stroke="url(#regBrand)" stroke-width="1.5" opacity="0.55" />
              <circle cx="16" cy="16" r="4" fill="url(#regBrand)" />
              <circle cx="6" cy="8" r="1.2" fill="url(#regBrand)" opacity="0.65" />
              <circle cx="26" cy="10" r="0.8" fill="url(#regBrand)" opacity="0.45" />
              <circle cx="8" cy="24" r="0.7" fill="url(#regBrand)" opacity="0.35" />
              <circle cx="24" cy="24" r="0.9" fill="url(#regBrand)" opacity="0.5" />
              <defs>
                <linearGradient id="regBrand" x1="0" y1="0" x2="32" y2="32">
                  <stop offset="0%" stop-color="#e9d5ff" />
                  <stop offset="50%" stop-color="#c4b5fd" />
                  <stop offset="100%" stop-color="#a5b4fc" />
                </linearGradient>
              </defs>
            </svg>
            <span class="auth-brand__name">星夜</span>
          </div>
          <p class="auth-brand__tag">加入星夜创作者社区</p>
          <p class="auth-brand__lead">注册后即可使用创作中心、知识库与书城；会员与用量可按需扩展。</p>
          <div class="auth-brand__features">
            <div class="ab-feature">
              <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M16.667 5L7.5 14.167 3.333 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              <span>免费注册，即刻开始创作</span>
            </div>
            <div class="ab-feature">
              <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M16.667 5L7.5 14.167 3.333 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              <span>AI 辅助，灵感源源不断</span>
            </div>
            <div class="ab-feature">
              <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M16.667 5L7.5 14.167 3.333 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              <span>社区互动，分享创作心得</span>
            </div>
          </div>
        </div>
      </aside>

      <div class="auth-form-wrap">
        <div class="auth-form-inner">
          <router-link to="/" class="auth-back">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            返回首页
          </router-link>

          <div class="auth-box">
            <div class="auth-box__header">
              <h1 class="auth-title">创建账号</h1>
              <p class="auth-desc">开启你的 AI 创作之旅</p>
            </div>

            <el-form ref="formRef" :model="form" :rules="dynamicRules" size="large" @submit.prevent="doReg">
              <div class="form-fields">
                <el-form-item prop="username">
                  <el-input
                    v-model="form.username"
                    placeholder="用户名（4-20 位）"
                    :prefix-icon="User"
                    class="auth-input"
                  />
                </el-form-item>
                <el-form-item prop="password">
                  <el-input
                    v-model="form.password"
                    type="password"
                    placeholder="密码（6-32 位）"
                    :prefix-icon="Lock"
                    show-password
                    class="auth-input"
                  />
                </el-form-item>
                <el-form-item prop="cfmPwd">
                  <el-input
                    v-model="form.cfmPwd"
                    type="password"
                    placeholder="确认密码"
                    :prefix-icon="Lock"
                    show-password
                    class="auth-input"
                  />
                </el-form-item>
                <el-form-item v-if="options.emailRegisterEnabled" prop="email">
                  <el-input
                    v-model="form.email"
                    placeholder="邮箱（选填）"
                    :prefix-icon="Message"
                    class="auth-input"
                  />
                </el-form-item>
                <el-form-item v-if="options.phoneRegisterEnabled" prop="phone">
                  <el-input
                    v-model="form.phone"
                    placeholder="手机号（选填，11 位）"
                    :prefix-icon="Iphone"
                    class="auth-input"
                    maxlength="11"
                  />
                </el-form-item>
              </div>

              <el-button class="btn-submit" :loading="loading" native-type="submit">
                <span v-if="!loading">注册</span>
              </el-button>
            </el-form>

            <div class="auth-switch">
              <span>已有账号？</span>
              <router-link to="/auth/login" class="as-link">立即登录</router-link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue'
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
const starCanvas = ref<HTMLCanvasElement>()

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

let starAnimationId: number | null = null

function initStarCanvas() {
  const canvas = starCanvas.value
  if (!canvas) return
  const ctx = canvas.getContext('2d')
  if (!ctx) return

  const resize = () => {
    const rect = canvas.parentElement!.getBoundingClientRect()
    canvas.width = rect.width * devicePixelRatio
    canvas.height = rect.height * devicePixelRatio
    canvas.style.width = rect.width + 'px'
    canvas.style.height = rect.height + 'px'
    ctx.scale(devicePixelRatio, devicePixelRatio)
  }
  resize()
  window.addEventListener('resize', resize)

  interface Star {
    x: number; y: number; r: number; speed: number; opacity: number
    twinkleSpeed: number; twinklePhase: number
  }

  const stars: Star[] = []
  const count = 50
  const w = () => canvas.width / devicePixelRatio
  const h = () => canvas.height / devicePixelRatio

  for (let i = 0; i < count; i++) {
    stars.push({
      x: Math.random() * w(),
      y: Math.random() * h(),
      r: Math.random() * 1.3 + 0.3,
      speed: Math.random() * 0.25 + 0.04,
      opacity: Math.random() * 0.55 + 0.12,
      twinkleSpeed: Math.random() * 0.018 + 0.004,
      twinklePhase: Math.random() * Math.PI * 2
    })
  }

  function animate() {
    ctx.clearRect(0, 0, w(), h())
    const now = Date.now() * 0.001

    stars.forEach((s) => {
      s.y -= s.speed
      if (s.y < -5) { s.y = h() + 5; s.x = Math.random() * w() }
      const alpha = s.opacity * (0.5 + 0.5 * Math.sin(now * s.twinkleSpeed * 60 + s.twinklePhase))
      ctx.beginPath()
      ctx.arc(s.x, s.y, s.r, 0, Math.PI * 2)
      ctx.fillStyle = `rgba(255,255,255,${alpha.toFixed(2)})`
      ctx.fill()
    })

    starAnimationId = requestAnimationFrame(animate)
  }
  animate()
}

onMounted(async () => {
  initStarCanvas()
  try {
    const o = await fetchRegisterOptions()
    options.emailRegisterEnabled = o.emailRegisterEnabled !== false
    options.phoneRegisterEnabled = o.phoneRegisterEnabled !== false
  } catch {
    /* ignore */
  }
})

onUnmounted(() => {
  if (starAnimationId) cancelAnimationFrame(starAnimationId)
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
    /* error handled by store/gateway */
  } finally {
    loading.value = false
  }
}
</script>

<style lang="scss" scoped>
.auth-page {
  min-height: 100dvh;
  width: 100%;
  position: relative;
  overflow-x: hidden;
  background: $bg-canvas;
  display: flex;
  flex-direction: column;
}

.auth-bg {
  position: fixed;
  inset: 0;
  pointer-events: none;
  z-index: 0;
}

.ab-glow {
  position: absolute;
  border-radius: 50%;
  filter: blur(120px);
  opacity: 0.7;

  &.ab-top {
    top: -15%;
    right: -10%;
    width: min(85vw, 680px);
    height: min(85vw, 680px);
    background: radial-gradient(circle, rgba(99, 102, 241, 0.12), transparent 70%);
    animation: glowPulse 8s ease-in-out infinite;
  }

  &.ab-bot {
    bottom: -20%;
    left: -15%;
    width: min(80vw, 600px);
    height: min(80vw, 600px);
    background: radial-gradient(circle, rgba(167, 139, 250, 0.1), transparent 70%);
    animation: glowPulse 8s ease-in-out infinite 4s;
  }
}

@keyframes glowPulse {
  0%, 100% { opacity: 0.5; transform: scale(1); }
  50% { opacity: 0.85; transform: scale(1.12); }
}

.auth-split {
  position: relative;
  z-index: 1;
  flex: 1 1 auto;
  display: flex;
  flex-direction: column;
  width: 100%;
  min-height: 100dvh;

  @media (min-width: 960px) {
    flex-direction: row;
    align-items: stretch;
  }
}

.auth-brand {
  position: relative;
  flex: none;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: $space-xl;
  min-height: 14rem;
  overflow: hidden;
  color: #f8fafc;
  background: linear-gradient(155deg, #0f0a2e 0%, #1e1b4b 25%, #312e81 55%, #4338ca 80%, #4f46e5 100%);

  @media (min-width: 960px) {
    flex: 1 1 45%;
    max-width: 580px;
    min-height: 100dvh;
    padding: clamp(2rem, 5vw, 4rem);
    align-items: center;
  }
}

.auth-brand__canvas {
  position: absolute;
  inset: 0;
  z-index: 0;
  pointer-events: none;
}

.auth-brand__glow {
  position: absolute;
  width: min(140%, 560px);
  height: min(140%, 560px);
  top: -15%;
  right: -30%;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(165, 180, 252, 0.28), transparent 65%);
  filter: blur(8px);
  pointer-events: none;
  z-index: 0;
}

.auth-brand__inner {
  position: relative;
  z-index: 1;
  max-width: 24rem;
  text-align: center;
  animation: fadeInUp 0.8s ease-out;

  @media (min-width: 960px) {
    text-align: left;
    max-width: 28rem;
  }
}

.auth-brand__logo-row {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 14px;
  margin-bottom: $space-md;

  @media (min-width: 960px) {
    justify-content: flex-start;
  }
}

.auth-brand__mark {
  width: 56px;
  height: 56px;
  flex-shrink: 0;
  filter: drop-shadow(0 0 12px rgba(165, 180, 252, 0.4));
  animation: float 5s ease-in-out infinite;
}

@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-6px); }
}

.auth-brand__name {
  font-size: clamp(1.6rem, 3.5vw, 2.2rem);
  font-weight: 800;
  letter-spacing: 0.16em;
  background: linear-gradient(135deg, #e9d5ff, #c4b5fd, #a5b4fc);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.auth-brand__tag {
  margin: 0 0 $space-md;
  font-size: $font-size-lg;
  font-weight: 600;
  line-height: 1.45;
  color: rgba(255, 255, 255, 0.95);
}

.auth-brand__lead {
  margin: 0 0 $space-lg;
  font-size: $font-size-md;
  line-height: 1.75;
  color: rgba(226, 232, 240, 0.85);
}

.auth-brand__features {
  display: flex;
  flex-direction: column;
  gap: $space-sm;
  margin-top: $space-lg;
}

.ab-feature {
  display: flex;
  align-items: center;
  gap: $space-sm;
  font-size: $font-size-sm;
  color: rgba(226, 232, 240, 0.82);
  padding: $space-xs 0;

  svg { flex-shrink: 0; opacity: 0.7; }
}

.auth-form-wrap {
  flex: 1 1 auto;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: $space-xl clamp($space-md, 4vw, $space-2xl) $space-2xl;
  box-sizing: border-box;
  width: 100%;
  min-height: 0;

  @media (min-width: 960px) {
    padding: $space-2xl clamp($space-xl, 4vw, 3.5rem);
    min-height: 100dvh;
  }
}

.auth-form-inner {
  width: 100%;
  max-width: 28rem;
  animation: fadeInUp 0.6s ease-out 0.15s both;
}

.auth-back {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-bottom: $space-lg;
  font-size: $font-size-md;
  font-weight: 500;
  color: $text-muted;
  text-decoration: none;
  transition: all $transition-fast;
  padding: 6px 0;

  &:hover {
    color: $primary-light;
    gap: 10px;
  }
}

.auth-box {
  position: relative;
  z-index: 1;
  width: 100%;
  padding: clamp($space-xl, 4vw, $space-2xl);
  background: $bg-surface;
  border: 1px solid $border-default;
  border-radius: $radius-xl;
  box-shadow: $shadow-card;
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
}

.auth-box__header {
  margin-bottom: $space-xl;
}

.auth-title {
  font-size: clamp(1.5rem, 2.5vw, 1.875rem);
  font-weight: 700;
  color: $text-primary;
  text-align: center;
  margin-bottom: $space-xs;
  letter-spacing: -0.02em;
}

.auth-desc {
  font-size: $font-size-md;
  color: $text-secondary;
  text-align: center;
  line-height: 1.6;
  margin: 0;
}

.form-fields {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

:deep(.el-form-item) {
  margin-bottom: 16px;
}

:deep(.auth-input .el-input__wrapper) {
  min-height: 50px;
  padding: 4px 16px;
  background: $bg-elevated;
  border: 1px solid $border-subtle;
  border-radius: $radius-md;
  box-shadow: none;
  transition: all $transition-fast;
}

:deep(.auth-input .el-input__wrapper:hover) {
  border-color: $border-emphasis;
  background: $bg-surface;
}

:deep(.auth-input .el-input__wrapper.is-focus) {
  border-color: $primary-color;
  background: $bg-surface;
  box-shadow: $glow-primary;
}

:deep(.auth-input .el-input__inner) {
  font-size: $font-size-md;
  color: $text-primary;

  &::placeholder {
    color: $text-muted;
    font-size: $font-size-md;
  }
}

:deep(.auth-input .el-input__prefix) {
  color: $text-muted;
}

:deep(.auth-input.is-focus .el-input__prefix) {
  color: $primary-light;
}

.btn-submit {
  width: 100%;
  min-height: 52px;
  height: auto;
  padding: 14px 20px;
  background: $gradient-primary-btn;
  border: none;
  color: #fff;
  font-weight: 600;
  font-size: 17px;
  border-radius: $radius-md;
  letter-spacing: 0.02em;
  transition: all $transition-fast;
  position: relative;
  overflow: hidden;
  margin-top: $space-sm;

  &::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.1), transparent);
    pointer-events: none;
  }

  &:hover {
    background: $gradient-primary-btn-hover;
    box-shadow: 0 0 28px rgba(99, 102, 241, 0.4);
    transform: translateY(-1px);
  }

  &:active {
    transform: translateY(0);
    box-shadow: 0 0 16px rgba(99, 102, 241, 0.25);
  }
}

.auth-switch {
  text-align: center;
  margin-top: $space-xl;
  font-size: $font-size-md;
  color: $text-muted;
}

.as-link {
  color: $primary-light;
  font-weight: 600;
  margin-left: $space-xs;
  transition: color $transition-fast;

  &:hover {
    color: $accent-color;
  }
}

@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(16px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
