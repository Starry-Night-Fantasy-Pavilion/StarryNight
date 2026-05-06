<template>
  <div class="auth-page">
    <div class="auth-bg"><div class="ab-glow ab-top"></div><div class="ab-glow ab-bot"></div></div>
    <div class="auth-box">
      <p class="auth-title" style="text-align: center; margin: 0">正在完成登录…</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useUserSessionStore } from '@/stores/auth'
import { ElMessage } from 'element-plus'

const route = useRoute()
const router = useRouter()
const auth = useUserSessionStore()

onMounted(async () => {
  const errRaw = route.query.oauth_error
  if (errRaw !== undefined && errRaw !== null && String(errRaw).length > 0) {
    ElMessage.error(decodeURIComponent(Array.isArray(errRaw) ? errRaw[0] : String(errRaw)))
    await router.replace('/auth/login')
    return
  }
  const sidRaw = route.query.sid
  const sid = typeof sidRaw === 'string' ? sidRaw : Array.isArray(sidRaw) ? sidRaw[0] : ''
  if (!sid) {
    ElMessage.error('缺少登录参数')
    await router.replace('/auth/login')
    return
  }
  try {
    await auth.exchangeOauthSid(sid)
    ElMessage.success('登录成功')
    await router.replace('/home')
  } catch (e) {
    ElMessage.error(e instanceof Error ? e.message : '登录失败')
    await router.replace('/auth/login')
  }
})
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
  padding: $space-2xl;
}
.auth-title {
  font-size: $font-size-lg;
  color: $text-secondary;
}
</style>
