<template>
  <div class="vip-page">
    <div class="page-toolbar">
      <div>
        <h2 class="toolbar-title">会员中心</h2>
        <p class="toolbar-sub">开通会员，享受更多创作额度与权益</p>
      </div>
    </div>

    <div class="vip-layout">
      <div class="vip-main">
        <div class="vip-card status-card">
          <div class="vsc-left">
            <div class="vsc-icon">
              <svg viewBox="0 0 40 40" width="48" height="48" fill="none">
                <path d="M20 4l5 10 11 2-8 7 2 11-10-6-10 6 2-11-8-7 11-2z" fill="url(#vstargrad)"/>
                <defs><linearGradient id="vstargrad"><stop offset="0%" stop-color="#818cf8"/><stop offset="100%" stop-color="#fbbf24"/></linearGradient></defs>
              </svg>
            </div>
            <div>
              <span class="vsc-name">{{ currentStatus?.memberLevelName || '未开通' }}</span>
              <span class="vsc-exp" v-if="currentStatus?.isActive && currentStatus?.expireTime">有效期至 {{ fmtDate(currentStatus.expireTime) }}</span>
              <span class="vsc-exp expired" v-else-if="currentStatus && !currentStatus.isActive">已过期</span>
            </div>
          </div>
          <div class="vsc-right">
            <div class="vsc-stat"><label>每日创作点</label><span>{{ (currentStatus?.dailyFreeQuota||0).toLocaleString() }} 点</span></div>
          </div>
        </div>

        <div class="vip-card">
          <h3>套餐选择</h3>
          <div class="tp-tabs">
            <button :class="{active:level===2}" @click="level=2;loadPkgs()">VIP 套餐</button>
            <button :class="{active:level===3}" @click="level=3;loadPkgs()">SVIP 套餐</button>
          </div>
          <div class="pkg-grid">
            <div v-for="p in pkgs" :key="p.id" class="pkg-card" :class="{hot:p.memberLevel===3}">
              <div class="pkg-ribbon" v-if="p.memberLevel===3">热门</div>
              <div class="pkg-name">{{ p.packageName }}</div>
              <div class="pkg-days">{{ p.durationDays }} 天</div>
              <div class="pkg-price"><span class="pp-sym">¥</span>{{ p.price }}<span class="pp-ori" v-if="p.originalPrice>p.price">¥{{ p.originalPrice }}</span></div>
              <div class="pkg-quota">每日 {{ (p.dailyFreeQuota||0).toLocaleString() }} 点</div>
              <el-button class="btn-primary pkg-btn" @click="buy(p)">立即开通</el-button>
            </div>
          </div>
        </div>

        <div class="vip-card" v-if="benefits">
          <h3>会员权益</h3>
          <div class="bf-grid">
            <div class="bf-item" v-for="(v,k) in dispBenefits" :key="k">
              <el-icon class="bf-check"><Check /></el-icon>
              <div><span class="bf-label">{{ lbl(k) }}</span><span class="bf-val">{{ v }}</span></div>
            </div>
          </div>
        </div>
      </div>

      <div class="vip-side">
        <div class="vs-card" v-if="hist.length">
          <h4>订阅记录</h4>
          <div class="hist-item" v-for="h in hist" :key="h.id">
            <span class="hi-name">{{ pkgName(h.packageId) }}</span>
            <span class="hi-date">{{ fmtDate(h.startTime) }} - {{ fmtDate(h.expireTime) }}</span>
            <span class="hi-st" :class="h.status.toLowerCase()">{{ stText(h.status) }}</span>
          </div>
        </div>
        <div class="vs-card">
          <h4>说明</h4>
          <p class="vstip" v-if="currentStatus && currentStatus.memberLevel<2">开通 VIP 可提升每日创作点上限，并解锁更多功能。</p>
          <p class="vstip" v-else-if="currentStatus && currentStatus.memberLevel===2">升级 SVIP 可获得更高每日额度与优先体验权益。</p>
          <p class="vstip" v-else>感谢支持，祝您创作愉快。</p>
        </div>
      </div>
    </div>

    <el-dialog v-model="buyDialog" title="确认订单" width="400px">
      <div v-if="selPkg" class="buy-info">
        <div class="bi-row"><span>套餐</span><span>{{ selPkg.packageName }}</span></div>
        <div class="bi-row"><span>时长</span><span>{{ selPkg.durationDays }} 天</span></div>
        <div class="bi-row"><span>金额</span><span class="bi-price">¥{{ selPkg.price }}</span></div>
      </div>
      <template #footer>
        <el-button @click="buyDialog=false">取消</el-button>
        <el-button class="btn-primary" :loading="buying" @click="confirmBuy">确认支付</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Check } from '@element-plus/icons-vue'
import { getVipPackages, getMemberBenefits, getMemberStatus, getSubscriptionHistory, activateMembership, type VipPackage, type MemberBenefits, type MemberSubscription } from '@/api/vip'

const currentStatus = ref<any>(null); const benefits = ref<MemberBenefits|null>(null)
const pkgs = ref<VipPackage[]>([]); const hist = ref<MemberSubscription[]>([])
const level = ref(2); const selPkg = ref<VipPackage|null>(null)
const buyDialog = ref(false); const buying = ref(false)

const dispBenefits = computed(() => {
  if (!benefits.value) return {}
  const r:Record<string,any>={}
  for (const [k,v] of Object.entries(benefits.value)) {
    if (!['memberLevel','memberLevelName','isActive','expireTime','dailyFreeQuota'].includes(k)) r[k]=v
  }
  return r
})
const lblMap: Record<string, string> = {
  daily_free_quota: '\u6bcf\u65e5\u989d\u5ea6',
  outline_per_day: '\u5927\u7eb2\u6b21\u6570',
  content_per_day: '\u6b63\u6587\u6b21\u6570',
  knowledge_library_limit: '\u77e5\u8bc6\u5e93\u6570\u91cf',
  priority_support: '\u4f18\u5148\u5ba2\u670d',
  exclusive_channel: '\u4e13\u5c5eAI',
}
function lbl(k: string) {
  return lblMap[k] || k
}
function fmtDate(d: string) {
  return d ? new Date(d).toLocaleDateString('zh-CN') : '-'
}
function pkgName(id: number) {
  return pkgs.value.find(p => p.id === id)?.packageName || `\u5957\u9910${id}`
}
function stText(s: string) {
  const m: Record<string, string> = {
    ACTIVE: '\u751f\u6548\u4e2d',
    EXPIRED: '\u5df2\u8fc7\u671f',
    CANCELLED: '\u5df2\u53d6\u6d88',
  }
  return m[s] || s
}

async function loadAll() {
  try { currentStatus.value = await getMemberStatus() } catch {}
  try { benefits.value = await getMemberBenefits() } catch {}
  try { hist.value = await getSubscriptionHistory() } catch {}
  loadPkgs()
}
async function loadPkgs() { try { pkgs.value = await getVipPackages(level.value) } catch {} }
function buy(p:VipPackage) { selPkg.value=p; buyDialog.value=true }
async function confirmBuy() {
  if (!selPkg.value) return
  buying.value = true
  try {
    await activateMembership(selPkg.value.id)
    buyDialog.value = false
    ElMessage.success('\u5f00\u901a\u6210\u529f')
    loadAll()
  } catch {
    ElMessage.error('\u652f\u4ed8\u5931\u8d25')
  } finally {
    buying.value = false
  }
}
onMounted(loadAll)
</script>

<style lang="scss" scoped>
.vip-page { padding:$space-xl; max-width:1200px; margin:0 auto; }
.page-toolbar { margin-bottom:$space-lg; .toolbar-title{font-size:$font-size-xl;font-weight:700;color:$text-primary;} .toolbar-sub{font-size:$font-size-sm;color:$text-muted;margin-top:2px;} }
.vip-layout { display:grid; grid-template-columns: 1fr 300px; gap:$space-lg; }
.vip-main { display:flex; flex-direction:column; gap:$space-lg; }
.vip-card { background:$bg-surface; border:1px solid $border-color; border-radius:$border-radius-lg; padding:$space-xl;
  h3 { font-size:$font-size-md;font-weight:600;color:$text-primary;margin-bottom:$space-lg; }
}
.status-card { display:flex; justify-content:space-between; align-items:center; }
.vsc-left { display:flex;align-items:center;gap:$space-md; }
.vsc-icon { display:flex; }
.vsc-name { font-size:$font-size-lg;font-weight:700;color:$text-primary;display:block; }
.vsc-exp { font-size:$font-size-xs;color:$text-muted;display:block;margin-top:2px; &.expired{color:#ef4444;} }
.vsc-stat { display:flex;flex-direction:column;align-items:flex-end; label{font-size:$font-size-xs;color:$text-muted;} span{font-size:$font-size-lg;font-weight:700;color:$text-primary;} }
.tp-tabs { display:inline-flex;gap:0;background:rgba(255,255,255,0.03);border-radius:10px;padding:3px;margin-bottom:$space-lg;
  button { padding:8px 20px;border:none;background:transparent;color:$text-secondary;font-size:$font-size-sm;border-radius:8px;cursor:pointer;transition:all $transition-fast;
    &:hover{color:$text-primary;} &.active{background:rgba(99,102,241,0.15);color:$primary-light;} }
}
.pkg-grid { display:grid; grid-template-columns: repeat(3,1fr); gap:$space-md; }
.pkg-card { position:relative; padding:$space-xl; text-align:center; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.06); border-radius:$border-radius; transition:all $transition-normal;
  &.hot{border-color:rgba(99,102,241,0.25);background:rgba(99,102,241,0.04);}
  &:hover{border-color:rgba(99,102,241,0.2);transform:translateY(-2px);}
}
.pkg-ribbon { position:absolute;top:-1px;right:12px;padding:3px 12px;font-size:11px;font-weight:600;background:linear-gradient(135deg,$primary-color,$accent-color);color:#fff;border-radius:0 0 8px 8px; }
.pkg-name { font-size:$font-size-md;font-weight:600;color:$text-primary;margin-bottom:4px; }
.pkg-days { font-size:$font-size-xs;color:$text-muted;margin-bottom:$space-md; }
.pkg-price { margin-bottom:$space-md; .pp-sym{font-size:$font-size-lg;color:$text-muted;} font-size:$font-size-3xl;font-weight:700;color:$text-primary; .pp-ori{font-size:$font-size-sm;color:$text-muted;text-decoration:line-through;margin-left:$space-sm;} }
.pkg-quota { font-size:$font-size-sm;color:$text-secondary;margin-bottom:$space-lg; }
.pkg-btn { width:100%;height:44px; }
.btn-primary { background:linear-gradient(135deg,$primary-color,$primary-dark); border:none; color:#fff; font-weight:600; border-radius:10px;
  &:hover{background:linear-gradient(135deg,$primary-light,$primary-color);box-shadow:0 0 20px rgba(99,102,241,0.35);} }
.bf-grid { display:grid;grid-template-columns:repeat(2,1fr);gap:$space-sm; }
.bf-item { display:flex;align-items:center;gap:$space-sm;padding:$space-sm $space-md;background:rgba(255,255,255,0.02);border-radius:8px; }
.bf-check { width:24px;height:24px;border-radius:6px;background:rgba(16,185,129,0.12);color:#34d399;display:flex;align-items:center;justify-content:center; }
.bf-label { font-size:$font-size-xs;color:$text-muted;display:block; }
.bf-val { font-size:$font-size-sm;font-weight:500;color:$text-primary;display:block; }
.vip-side { display:flex;flex-direction:column;gap:$space-md; }
.vs-card { background:$bg-surface;border:1px solid $border-color;border-radius:$border-radius-lg;padding:$space-md;
  h4{font-size:10px;font-weight:600;color:$text-muted;text-transform:uppercase;letter-spacing:1px;margin-bottom:$space-sm;}
}
.hist-item { padding:$space-sm;background:rgba(255,255,255,0.02);border-radius:8px;margin-bottom:6px;
  .hi-name{font-size:$font-size-sm;font-weight:500;color:$text-primary;display:block;}
  .hi-date{font-size:$font-size-xs;color:$text-muted;display:block;margin-top:2px;}
  .hi-st{display:inline-block;margin-top:4px;font-size:10px;padding:2px 6px;border-radius:4px;
    &.active{background:rgba(16,185,129,0.12);color:#34d399;}
    &.expired{background:rgba(239,68,68,0.12);color:#ef4444;}
    &.cancelled{background:rgba(148,163,184,0.12);color:#94a3b8;}}
}
.vstip { font-size:$font-size-sm;color:$text-secondary;line-height:1.6; }
</style>
