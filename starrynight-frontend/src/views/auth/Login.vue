<template>
  <div class="auth-page">
    <div class="auth-bg"><div class="ab-glow ab-top"></div><div class="ab-glow ab-bot"></div></div>
    <div class="auth-box">
      <div class="auth-logo">
        <svg viewBox="0 0 32 32" width="40" height="40" fill="none">
          <circle cx="16" cy="16" r="14" stroke="url(#alg)" stroke-width="1.5" opacity="0.5" />
          <circle cx="16" cy="16" r="4" fill="url(#alg)" />
          <circle cx="6" cy="8" r="1.2" fill="url(#alg)" opacity="0.6" />
          <circle cx="26" cy="10" r="0.8" fill="url(#alg)" opacity="0.4" />
          <defs><linearGradient id="alg" x1="0" y1="0" x2="32" y2="32"><stop offset="0%" stop-color="#a78bfa"/><stop offset="100%" stop-color="#6366f1"/></linearGradient></defs>
        </svg>
        <span class="al-text">星夜</span>
      </div>
      <h1 class="auth-title">欢迎回来</h1>
      <p class="auth-desc">登录你的账户，继续创作之旅</p>

      <el-form ref="formRef" :model="form" :rules="rules" size="large" @submit.prevent="doLogin">
        <el-form-item prop="account"><el-input v-model="form.account" placeholder="用户名或邮箱" :prefix-icon="User" /></el-form-item>
        <el-form-item prop="password"><el-input v-model="form.password" type="password" placeholder="密码" :prefix-icon="Lock" show-password @keyup.enter="doLogin" /></el-form-item>
        <div class="auth-opts">
          <el-checkbox v-model="remember">记住我</el-checkbox>
          <el-button link class="auth-link" @click="fpDlg=true">忘记密码？</el-button>
        </div>
        <el-button class="btn-submit" :loading="loading" native-type="submit">登录</el-button>
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
              class="btn-linuxdo"
              :href="oauthStartUrl('linuxdo')"
              aria-label="使用 LINUX DO 登录"
            >
              <img
                class="btn-linuxdo__logo"
                :src="OAUTH_LOGO.linuxdo"
                width="28"
                height="28"
                alt=""
                decoding="async"
                referrerpolicy="no-referrer"
              />
            </a>
            <a
              v-if="oauthOpts.githubEnabled"
              class="btn-oauth btn-oauth--github"
              :href="oauthStartUrl('github')"
            >
              <img class="btn-oauth__logo" :src="OAUTH_LOGO.github" width="26" height="26" alt="" decoding="async" referrerpolicy="no-referrer" />
              <span>GitHub</span>
            </a>
            <a
              v-if="oauthOpts.googleEnabled"
              class="btn-oauth btn-oauth--google"
              :href="oauthStartUrl('google')"
            >
              <img class="btn-oauth__logo" :src="OAUTH_LOGO.google" width="26" height="26" alt="" decoding="async" referrerpolicy="no-referrer" />
              <span>Google</span>
            </a>
            <a
              v-if="oauthOpts.wechatEnabled"
              class="btn-oauth btn-oauth--wechat"
              :href="oauthStartUrl('wechat')"
            >
              <img class="btn-oauth__logo" :src="OAUTH_LOGO.wechat" width="26" height="26" alt="" decoding="async" referrerpolicy="no-referrer" />
              <span>微信</span>
            </a>
            <a
              v-if="oauthOpts.qqEnabled"
              class="btn-oauth btn-oauth--qq"
              :href="oauthStartUrl('qq')"
            >
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

      <div class="auth-switch"><span>还没有账号？</span><router-link to="/auth/register" class="as-link">立即注册</router-link></div>
    </div>

    <el-dialog v-model="fpDlg" title="找回密码" width="400px">
      <el-form size="large"><el-form-item label="用户名或邮箱"><el-input v-model="fpAcc" /></el-form-item></el-form>
      <template #footer><el-button @click="fpDlg=false">取消</el-button><el-button class="btn-primary" @click="doForgot">发送重置邮件</el-button></template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
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

/** 与后端 PortalOAuthService.ZEVOST_TYPES 顺序、知我云文档 type 一致 */
const ZEVOST_TYPE_ORDER = [
  'qq',
  'wx',
  'alipay',
  'sina',
  'baidu',
  'douyin',
  'huawei',
  'xiaomi',
  'google',
  'microsoft',
  'twitter',
  'dingtalk',
  'gitee',
  'github'
] as const

const ZEVOST_TYPE_LABELS: Record<string, string> = {
  qq: 'QQ',
  wx: '微信',
  alipay: '支付宝',
  sina: '微博',
  baidu: '百度',
  douyin: '抖音',
  huawei: '华为',
  xiaomi: '小米',
  google: 'Google',
  microsoft: '微软',
  twitter: 'Twitter',
  dingtalk: '钉钉',
  gitee: 'Gitee',
  github: 'GitHub'
}

const router = useRouter()
const auth = useUserSessionStore()
const opsSession = useOpsSessionStore()
const formRef = ref<FormInstance>()
const loading = ref(false)
const remember = ref(false)
const fpDlg = ref(false)
const fpAcc = ref('')
const oauthOpts = ref<OauthLoginOptionsVO>({
  linuxdoEnabled: false,
  githubEnabled: false,
  googleEnabled: false,
  wechatEnabled: false,
  qqEnabled: false,
  zevostEnabled: false,
  zevostTypes: {}
})
/** 默认展开第三方登录区块 */
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
    case 'qq':
      return OAUTH_LOGO.qq
    case 'wx':
      return OAUTH_LOGO.wechat
    case 'google':
      return OAUTH_LOGO.google
    case 'github':
      return OAUTH_LOGO.github
    case 'gitee':
      return 'https://images.icon-icons.com/2450/PNG/512/gitee_icon_146848.png'
    default:
      return undefined
  }
}

onMounted(async () => {
  try {
    const opt = await authGatewayGet<OauthLoginOptionsVO>('/auth/oauth/options')
    oauthOpts.value = { ...oauthOpts.value, ...opt }
  } catch {
    oauthOpts.value = {
      linuxdoEnabled: false,
      githubEnabled: false,
      googleEnabled: false,
      wechatEnabled: false,
      qqEnabled: false,
      zevostEnabled: false,
      zevostTypes: {}
    }
  }
})
const form=reactive({ account:'',password:'' })
const rules:FormRules={ account:[{required:true,message:'请输入用户名或邮箱',trigger:'blur'}],password:[{required:true,message:'请输入密码',trigger:'blur'},{min:6,max:32,message:'6-32位',trigger:'blur'}] }
async function doLogin() { const v=await formRef.value?.validate().catch(()=>false);if(!v) return;loading.value=true;try { const r=await auth.login(form.account,form.password); if(r){opsSession.logout();ElMessage.success('登录成功');router.push('/home')} else ElMessage.error('账号或密码错误') } catch { ElMessage.error('登录失败') } finally { loading.value=false } }
async function doForgot() { if(!fpAcc.value.trim()){ ElMessage.warning('请输入用户名或邮箱');return };try { await auth.forgotPassword(fpAcc.value.trim()); ElMessage.success('密码重置邮件已发送'); fpDlg.value=false } catch { ElMessage.error('发送失败') } }
</script>

<style lang="scss" scoped>
.auth-page { height:100vh;display:flex;align-items:center;justify-content:center;background:$bg-root;position:relative;overflow:hidden; }
.auth-bg { position:fixed;inset:0;pointer-events:none; }
.ab-glow { position:absolute;border-radius:50%;filter:blur(100px);
  &.ab-top{top:-200px;right:-200px;width:600px;height:600px;background:rgba(99,102,241,0.06);}
  &.ab-bot{bottom:-200px;left:-200px;width:600px;height:600px;background:rgba(167,139,250,0.04);}
}
.auth-box { position:relative;z-index:1;width:100%;max-width:420px;padding:0 $space-xl;
  background:$bg-surface;border:1px solid $border-color;border-radius:$border-radius-xl;padding:$space-2xl;
}
.auth-logo { display:flex;align-items:center;justify-content:center;gap:10px;margin-bottom:$space-lg; }
.al-text { font-size:22px;font-weight:700;background:linear-gradient(135deg,#a78bfa,#818cf8);-webkit-background-clip:text;-webkit-text-fill-color:transparent; }
.auth-title { font-size:$font-size-xl;font-weight:700;color:$text-primary;text-align:center;margin-bottom:$space-xs; }
.auth-desc { font-size:$font-size-sm;color:$text-secondary;text-align:center;margin-bottom:$space-lg; }
:deep(.el-input__wrapper){ background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08);border-radius:12px;box-shadow:none; }
:deep(.el-input__wrapper:hover){ border-color:rgba(255,255,255,0.12); }
:deep(.el-input__wrapper.is-focus){ border-color:$primary-color;box-shadow:0 0 0 3px rgba(99,102,241,0.1); }
.auth-opts { display:flex;justify-content:space-between;align-items:center;margin-bottom:$space-md; }
.auth-link { font-size:$font-size-sm;color:$text-muted;&:hover{color:$primary-light;} }
.btn-submit { width:100%;height:48px;background:linear-gradient(135deg,#6366f1,#4f46e5);border:none;color:#fff;font-weight:600;font-size:16px;border-radius:12px;&:hover{background:linear-gradient(135deg,#818cf8,#6366f1);box-shadow:0 0 24px rgba(99,102,241,0.35);transform:translateY(-1px);} }
.auth-switch { text-align:center;margin-top:$space-lg;font-size:$font-size-sm;color:$text-muted; }
.as-link { color:$primary-light;font-weight:500;margin-left:$space-xs;&:hover{color:$accent-color;} }
.btn-primary { background:linear-gradient(135deg,$primary-color,$primary-dark);border:none;color:#fff;font-weight:600;border-radius:8px;&:hover{box-shadow:0 0 16px rgba(99,102,241,0.3);} }
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
    font-size: $font-size-sm;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
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
  color: $text-primary;
}
.oauth-divider { display:flex;align-items:center;margin:$space-lg 0;color:$text-muted;font-size:$font-size-sm;
  &::before,&::after{content:'';flex:1;height:1px;background:rgba(255,255,255,0.08);}
  span{padding:0 $space-md;}
}
.oauth-divider--sub {
  margin-top: $space-md;
  margin-bottom: 10px;
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
.btn-linuxdo {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  width: 100%;
  min-height: 48px;
  padding: 10px 14px;
  border-radius: 12px;
  font-weight: 600;
  font-size: 15px;
  text-decoration: none;
  color: #ececec;
  background: linear-gradient(180deg, #2c2c32 0%, #18181b 100%);
  border: 1px solid rgba(253, 184, 19, 0.42);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.05);
  &:hover {
    color: #fff;
    border-color: #fdb813;
    box-shadow:
      0 0 0 3px rgba(253, 184, 19, 0.12),
      0 6px 24px rgba(0, 0, 0, 0.35);
  }
}
.btn-linuxdo__logo {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  object-fit: cover;
  object-position: center;
  flex-shrink: 0;
  display: block;
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
  min-height: 46px;
  padding: 8px 14px;
  border-radius: 12px;
  font-weight: 600;
  font-size: 15px;
  text-decoration: none;
  color: #f3f4f6;
  border: 1px solid rgba(255, 255, 255, 0.12);
  transition: filter 0.15s, box-shadow 0.15s;
  &:hover {
    filter: brightness(1.06);
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.35);
  }
}
.btn-oauth__logo {
  width: 26px;
  height: 26px;
  object-fit: contain;
  flex-shrink: 0;
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
  color: #fff;
}
.btn-oauth--wechat {
  background: linear-gradient(180deg, #06ae56 0%, #059669 100%);
  border-color: rgba(255, 255, 255, 0.15);
  color: #fff;
}
.btn-oauth--qq {
  background: linear-gradient(180deg, #12b7f5 0%, #0e8bc9 100%);
  color: #fff;
}
.oauth-hint { margin-top:$space-sm;font-size:12px;color:$text-muted;text-align:center;line-height:1.5; }
</style>
