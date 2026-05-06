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
              <circle cx="16" cy="16" r="14" stroke="url(#algBrand)" stroke-width="1.5" opacity="0.55" />
              <circle cx="16" cy="16" r="4" fill="url(#algBrand)" />
              <circle cx="6" cy="8" r="1.2" fill="url(#algBrand)" opacity="0.65" />
              <circle cx="26" cy="10" r="0.8" fill="url(#algBrand)" opacity="0.45" />
              <circle cx="8" cy="24" r="0.7" fill="url(#algBrand)" opacity="0.35" />
              <circle cx="24" cy="24" r="0.9" fill="url(#algBrand)" opacity="0.5" />
              <defs>
                <linearGradient id="algBrand" x1="0" y1="0" x2="32" y2="32">
                  <stop offset="0%" stop-color="#e9d5ff" />
                  <stop offset="50%" stop-color="#c4b5fd" />
                  <stop offset="100%" stop-color="#a5b4fc" />
                </linearGradient>
              </defs>
            </svg>
            <span class="auth-brand__name">星夜</span>
          </div>
          <p class="auth-brand__tag">AI 驱动的长篇小说创作工作台</p>
          <p class="auth-brand__lead">结构化大纲、章节与世界观知识库，配合可控 AI 辅助，让长篇创作更稳、更连贯。</p>
          <div class="auth-brand__features">
            <div class="ab-feature">
              <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M16.667 5L7.5 14.167 3.333 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              <span>结构化大纲管理</span>
            </div>
            <div class="ab-feature">
              <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M16.667 5L7.5 14.167 3.333 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              <span>AI 智能辅助写作</span>
            </div>
            <div class="ab-feature">
              <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M16.667 5L7.5 14.167 3.333 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              <span>世界观知识库</span>
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
              <h1 class="auth-title">欢迎回来</h1>
              <p class="auth-desc">登录你的账户，继续创作之旅</p>
            </div>

            <el-form ref="formRef" :model="form" :rules="rules" size="large" @submit.prevent="doLogin">
              <div class="form-fields">
                <el-form-item prop="account">
                  <el-input
                    v-model="form.account"
                    placeholder="用户名或邮箱"
                    :prefix-icon="User"
                    class="auth-input"
                  />
                </el-form-item>
                <el-form-item prop="password">
                  <el-input
                    v-model="form.password"
                    type="password"
                    placeholder="密码"
                    :prefix-icon="Lock"
                    show-password
                    class="auth-input"
                    @keyup.enter="doLogin"
                  />
                </el-form-item>
              </div>

              <div class="auth-opts">
                <el-checkbox v-model="remember" class="auth-checkbox">
                  <span class="auth-checkbox__label">记住我</span>
                </el-checkbox>
                <el-button link class="auth-link" @click="fpDlg = true">忘记密码？</el-button>
              </div>

              <el-button class="btn-submit" :loading="loading" native-type="submit">
                <span v-if="!loading">登录</span>
              </el-button>
            </el-form>

            <el-collapse v-if="oauthAnyEnabled" v-model="oauthPanelOpen" class="oauth-collapse">
              <el-collapse-item name="oauth">
                <template #title>
                  <span class="oauth-collapse__title">第三方登录</span>
                </template>
                <div class="oauth-divider"><span>或</span></div>
                <div class="oauth-grid">
                  <a
                    v-if="oauthOpts.linuxdoEnabled"
                    class="btn-oauth btn-oauth--linuxdo"
                    :href="oauthStartUrl('linuxdo')"
                    aria-label="使用 LINUX DO 登录"
                  >
                    <img class="btn-oauth__logo" :src="OAUTH_LOGO.linuxdo" width="28" height="28" alt="" decoding="async" referrerpolicy="no-referrer" />
                  </a>
                  <a v-if="oauthOpts.githubEnabled" class="btn-oauth btn-oauth--github" :href="oauthStartUrl('github')">
                    <img class="btn-oauth__logo" :src="OAUTH_LOGO.github" width="26" height="26" alt="" decoding="async" referrerpolicy="no-referrer" />
                    <span>GitHub</span>
                  </a>
                  <a v-if="oauthOpts.googleEnabled" class="btn-oauth btn-oauth--google" :href="oauthStartUrl('google')">
                    <img class="btn-oauth__logo" :src="OAUTH_LOGO.google" width="26" height="26" alt="" decoding="async" referrerpolicy="no-referrer" />
                    <span>Google</span>
                  </a>
                  <a v-if="oauthOpts.wechatEnabled" class="btn-oauth btn-oauth--wechat" :href="oauthStartUrl('wechat')">
                    <img class="btn-oauth__logo" :src="OAUTH_LOGO.wechat" width="26" height="26" alt="" decoding="async" referrerpolicy="no-referrer" />
                    <span>微信</span>
                  </a>
                  <a v-if="oauthOpts.qqEnabled" class="btn-oauth btn-oauth--qq" :href="oauthStartUrl('qq')">
                    <img class="btn-oauth__logo" :src="OAUTH_LOGO.qq" width="26" height="26" alt="" decoding="async" referrerpolicy="no-referrer" />
                    <span>QQ</span>
                  </a>
                  <template v-if="zevostActiveTypes.length">
                    <div class="oauth-divider oauth-divider--sub"><span>聚合登录（知我云）</span></div>
                    <a
                      v-for="zt in zevostActiveTypes"
                      :key="'zevost-' + zt"
                      class="btn-oauth btn-oauth--zevost"
                      :href="zevostStartUrl(zt)"
                    >
                      <img
                        v-if="zevostTypeLogo(zt)"
                        class="btn-oauth__logo"
                        :src="zevostTypeLogo(zt)!"
                        width="26"
                        height="26"
                        alt=""
                        decoding="async"
                        referrerpolicy="no-referrer"
                      />
                      <span>{{ ZEVOST_TYPE_LABELS[zt] ?? zt }}</span>
                    </a>
                  </template>
                </div>
                <p class="oauth-hint">跳转至对应平台授权；若邮箱与已有账号一致将自动绑定，否则将创建本站账号</p>
              </el-collapse-item>
            </el-collapse>

            <div class="auth-switch">
              <span>还没有账号？</span>
              <router-link to="/auth/register" class="as-link">立即注册</router-link>
            </div>
          </div>
        </div>
      </div>
    </div>

    <el-dialog v-model="fpDlg" title="找回密码" width="440px" class="fp-dialog">
      <el-form size="large">
        <el-form-item label="用户名或邮箱">
          <el-input v-model="fpAcc" placeholder="请输入注册时使用的用户名或邮箱" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="fpDlg = false">取消</el-button>
        <el-button class="btn-primary" @click="doForgot">发送重置邮件</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, onUnmounted, computed } from 'vue'
import type { OauthLoginOptionsVO } from '@/types/api'
import { useRouter } from 'vue-router'
import { useUserSessionStore, useOpsSessionStore } from '@/stores/auth'
import { ElMessage } from 'element-plus'
import type { FormInstance, FormRules } from 'element-plus'
import { User, Lock } from '@element-plus/icons-vue'
import { authGatewayGet } from '@/utils/authGateway'
import { getApiBaseUrl } from '@/config/apiBase'

const OAUTH_LOGO = {
  linuxdo: 'https://avatars.githubusercontent.com/u/160804563?s=128&v=4',
  github: 'https://github.githubassets.com/images/modules/logos_page/GitHub-Mark.svg',
  google: 'https://images.icon-icons.com/673/PNG/512/Google_icon-icons.com_60497.png',
  wechat: 'https://images.icon-icons.com/2108/PNG/512/wechat_icon_130789.png',
  qq: 'https://images.icon-icons.com/1753/PNG/512/iconfinder-social-media-applications-10qq-4102582_113820.png'
} as const

const ZEVOST_TYPE_ORDER = [
  'qq', 'wx', 'alipay', 'sina', 'baidu', 'douyin',
  'huawei', 'xiaomi', 'google', 'microsoft', 'twitter',
  'dingtalk', 'gitee', 'github'
] as const

const ZEVOST_TYPE_LABELS: Record<string, string> = {
  qq: 'QQ', wx: '微信', alipay: '支付宝', sina: '微博',
  baidu: '百度', douyin: '抖音', huawei: '华为', xiaomi: '小米',
  google: 'Google', microsoft: '微软', twitter: 'Twitter',
  dingtalk: '钉钉', gitee: 'Gitee', github: 'GitHub'
}

const router = useRouter()
const auth = useUserSessionStore()
const opsSession = useOpsSessionStore()
const formRef = ref<FormInstance>()
const loading = ref(false)
const remember = ref(false)
const fpDlg = ref(false)
const fpAcc = ref('')
const starCanvas = ref<HTMLCanvasElement>()

const oauthOpts = ref<OauthLoginOptionsVO>({
  linuxdoEnabled: false, githubEnabled: false, googleEnabled: false,
  wechatEnabled: false, qqEnabled: false, zevostEnabled: false, zevostTypes: {}
})
const oauthPanelOpen = ref<string[]>(['oauth'])

const zevostActiveTypes = computed(() => {
  if (!oauthOpts.value.zevostEnabled) return []
  const m = oauthOpts.value.zevostTypes || {}
  return ZEVOST_TYPE_ORDER.filter((k) => m[k])
})

const oauthAnyEnabled = computed(
  () =>
    oauthOpts.value.linuxdoEnabled ||
    oauthOpts.value.githubEnabled ||
    oauthOpts.value.googleEnabled ||
    oauthOpts.value.wechatEnabled ||
    oauthOpts.value.qqEnabled ||
    zevostActiveTypes.value.length > 0
)

function oauthStartUrl(provider: string) {
  return `${getApiBaseUrl()}/auth/oauth/${provider}/start`
}

function zevostStartUrl(type: string) {
  return `${getApiBaseUrl()}/auth/oauth/zevost/${type}/start`
}

function zevostTypeLogo(type: string): string | undefined {
  switch (type) {
    case 'qq': return OAUTH_LOGO.qq
    case 'wx': return OAUTH_LOGO.wechat
    case 'google': return OAUTH_LOGO.google
    case 'github': return OAUTH_LOGO.github
    case 'gitee': return 'https://images.icon-icons.com/2450/PNG/512/gitee_icon_146848.png'
    default: return undefined
  }
}

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
  const count = 60
  const w = () => canvas.width / devicePixelRatio
  const h = () => canvas.height / devicePixelRatio

  for (let i = 0; i < count; i++) {
    stars.push({
      x: Math.random() * w(),
      y: Math.random() * h(),
      r: Math.random() * 1.4 + 0.3,
      speed: Math.random() * 0.3 + 0.05,
      opacity: Math.random() * 0.6 + 0.15,
      twinkleSpeed: Math.random() * 0.02 + 0.005,
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
    const opt = await authGatewayGet<OauthLoginOptionsVO>('/auth/oauth/options')
    oauthOpts.value = { ...oauthOpts.value, ...opt }
  } catch {
    oauthOpts.value = {
      linuxdoEnabled: false, githubEnabled: false, googleEnabled: false,
      wechatEnabled: false, qqEnabled: false, zevostEnabled: false, zevostTypes: {}
    }
  }
})

onUnmounted(() => {
  if (starAnimationId) cancelAnimationFrame(starAnimationId)
})

const form = reactive({ account: '', password: '' })
const rules: FormRules = {
  account: [{ required: true, message: '请输入用户名或邮箱', trigger: 'blur' }],
  password: [
    { required: true, message: '请输入密码', trigger: 'blur' },
    { min: 6, max: 32, message: '密码为 6-32 位', trigger: 'blur' }
  ]
}

async function doLogin() {
  const v = await formRef.value?.validate().catch(() => false)
  if (!v) return
  loading.value = true
  try {
    const r = await auth.login(form.account, form.password)
    if (r) {
      opsSession.logout()
      ElMessage.success('登录成功')
      router.push('/home')
    } else {
      ElMessage.error('账号或密码错误')
    }
  } catch {
    ElMessage.error('登录失败')
  } finally {
    loading.value = false
  }
}

async function doForgot() {
  if (!fpAcc.value.trim()) { ElMessage.warning('请输入用户名或邮箱'); return }
  try {
    await auth.forgotPassword(fpAcc.value.trim())
    ElMessage.success('密码重置邮件已发送')
    fpDlg.value = false
  } catch {
    ElMessage.error('发送失败')
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
  margin-bottom: 18px;
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

.auth-opts {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: $space-md;
  padding: 0 2px;
}

.auth-checkbox {
  --el-checkbox-font-size: 14px;
}

.auth-checkbox__label {
  color: $text-secondary;
  font-size: $font-size-md;
}

.auth-link {
  font-size: $font-size-md;
  color: $text-muted;
  font-weight: 500;
  padding: 4px 0;

  &:hover {
    color: $primary-light;
  }
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

.oauth-collapse {
  margin-top: $space-md;
  border: none;
  --el-collapse-border-color: transparent;

  :deep(.el-collapse-item__header) {
    height: auto;
    min-height: 48px;
    line-height: 1.4;
    padding: $space-sm 0;
    background: transparent;
    color: $text-secondary;
    font-size: $font-size-md;
    border-bottom: 1px solid $border-subtle;
  }

  :deep(.el-collapse-item__arrow) {
    color: $text-muted;
  }

  :deep(.el-collapse-item__wrap) {
    background: transparent;
    border-bottom: none;
  }

  :deep(.el-collapse-item__content) {
    padding: $space-md 0 0;
    color: inherit;
  }
}

.oauth-collapse__title {
  font-weight: 600;
  font-size: $font-size-md;
  color: $text-secondary;
}

.oauth-divider {
  display: flex;
  align-items: center;
  margin: $space-md 0;
  color: $text-muted;
  font-size: $font-size-sm;

  &::before, &::after {
    content: '';
    flex: 1;
    height: 1px;
    background: $border-subtle;
  }

  span { padding: 0 $space-md; }
}

.oauth-divider--sub {
  margin-top: $space-sm;
  margin-bottom: 10px;
}

.oauth-grid {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.btn-oauth {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  width: 100%;
  min-height: 50px;
  padding: 12px 16px;
  border-radius: $radius-md;
  font-weight: 600;
  font-size: 15px;
  text-decoration: none;
  color: #f3f4f6;
  border: 1px solid rgba(255, 255, 255, 0.12);
  transition: all $transition-fast;

  &:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 24px rgba(0, 0, 0, 0.35);
  }
}

.btn-oauth__logo {
  width: 26px;
  height: 26px;
  object-fit: contain;
  flex-shrink: 0;
}

.btn-oauth--linuxdo {
  background: linear-gradient(180deg, #2c2c32 0%, #18181b 100%);
  border-color: rgba(253, 184, 19, 0.42);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.05);

  &:hover {
    border-color: #fdb813;
    box-shadow: 0 0 0 3px rgba(253, 184, 19, 0.1), 0 6px 24px rgba(0, 0, 0, 0.4);
  }

  .btn-oauth__logo {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    object-fit: cover;
  }
}

.btn-oauth--github {
  background: linear-gradient(180deg, #2d333b 0%, #161b22 100%);

  .btn-oauth__logo {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 6px;
    padding: 2px;
  }
}

.btn-oauth--google {
  background: linear-gradient(180deg, #3c4043 0%, #202124 100%);
}

.btn-oauth--wechat {
  background: linear-gradient(180deg, #07c160 0%, #06ae56 100%);
}

.btn-oauth--qq {
  background: linear-gradient(180deg, #12b7f5 0%, #0e8bc9 100%);
}

.btn-oauth--zevost {
  background: linear-gradient(180deg, #334155 0%, #1e293b 100%);
  border-color: rgba(148, 163, 184, 0.25);
  color: #e2e8f0;

  .btn-oauth__logo {
    border-radius: 6px;
    background: rgba(255, 255, 255, 0.06);
    padding: 2px;
    box-sizing: border-box;
  }
}

.oauth-hint {
  margin-top: $space-md;
  font-size: $font-size-xs;
  color: $text-muted;
  text-align: center;
  line-height: 1.6;
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

.btn-primary {
  background: $gradient-primary-btn;
  border: none;
  color: #fff;
  font-weight: 600;
  border-radius: $radius-sm;

  &:hover {
    box-shadow: 0 0 20px rgba(99, 102, 241, 0.35);
  }
}

:deep(.fp-dialog) {
  .el-dialog__header { padding: $space-lg $space-lg 0; }
  .el-dialog__body { padding: $space-lg; }
}

@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(16px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
