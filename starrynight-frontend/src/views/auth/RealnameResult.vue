<template>
  <div class="auth-page">
    <div class="auth-bg">
      <div class="ab-glow ab-top"></div>
      <div class="ab-glow ab-bot"></div>
    </div>
    <div class="auth-box rn-box">
      <h1 class="auth-title">实名核验</h1>
      <p v-if="loading" class="auth-desc">正在同步状态…</p>
      <template v-else>
        <el-alert v-if="verified" type="success" :closable="false" show-icon title="已通过实名核验" />
        <el-alert v-else type="info" :closable="false" show-icon>
          <template #title>
            <span>若您刚完成人脸流程，异步确认可能需要数秒；可稍后在个人中心查看状态。</span>
          </template>
        </el-alert>
        <div class="rn-actions">
          <el-button class="btn-primary" @click="goProfile">个人中心</el-button>
          <el-button class="btn-ghost" :loading="refreshing" @click="refresh">刷新状态</el-button>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useUserSessionStore } from '@/stores/auth'

const router = useRouter()
const auth = useUserSessionStore()
const loading = ref(true)
const refreshing = ref(false)

const verified = computed(() => auth.userInfo?.realNameVerified === true)

async function refresh() {
  if (!auth.accessToken) return
  refreshing.value = true
  try {
    await auth.fetchProfile()
  } finally {
    refreshing.value = false
  }
}

function goProfile() {
  router.push({ name: 'Profile' })
}

onMounted(async () => {
  try {
    if (auth.accessToken) await auth.fetchProfile()
  } finally {
    loading.value = false
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
  .ab-glow {
    position: absolute;
    width: 480px;
    height: 480px;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.35;
  }
  .ab-top {
    top: -120px;
    right: -80px;
    background: radial-gradient(circle, #a78bfa 0%, transparent 70%);
  }
  .ab-bot {
    bottom: -160px;
    left: -100px;
    background: radial-gradient(circle, #6366f1 0%, transparent 70%);
  }
}
.auth-box {
  position: relative;
  width: 100%;
  max-width: 400px;
  padding: $space-xl;
  border-radius: $border-radius-lg;
  background: $bg-surface;
  border: 1px solid $border-color;
  box-shadow: 0 24px 48px rgba(0, 0, 0, 0.12);
}
.rn-box {
  max-width: 440px;
}
.auth-title {
  font-size: $font-size-xl;
  font-weight: 700;
  color: $text-primary;
  margin: 0 0 $space-md;
  text-align: center;
}
.auth-desc {
  text-align: center;
  color: $text-muted;
  font-size: $font-size-sm;
  margin: 0 0 $space-lg;
}
.rn-actions {
  display: flex;
  flex-wrap: wrap;
  gap: $space-sm;
  justify-content: center;
  margin-top: $space-lg;
}
.btn-primary {
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  border: none;
  color: #fff;
}
.btn-ghost {
  background: transparent;
  border: 1px solid $border-color;
  color: $text-primary;
}
</style>
