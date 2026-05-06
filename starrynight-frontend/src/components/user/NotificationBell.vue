<template>
  <el-popover
    placement="bottom-end"
    :width="340"
    trigger="click"
    @show="loadNotifications"
    :show-arrow="false"
    popper-class="notify-popover"
  >
    <template #reference>
      <div class="notify-bell">
        <el-badge :value="unreadCount" :hidden="unreadCount === 0" :max="99">
          <div class="bell-btn">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
          </div>
        </el-badge>
      </div>
    </template>

    <div class="nf-panel">
      <div class="nf-header">
        <span class="nf-h-title">通知</span>
        <el-button v-if="unreadCount" link class="nf-h-action" @click.stop="markAll">全部已读</el-button>
      </div>

      <div class="nf-body" v-loading="loading">
        <div v-if="!list.length" class="nf-empty">
          <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.5" opacity="0.2">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 0 1-3.46 0"/>
          </svg>
          <p>暂无通知</p>
        </div>

        <div v-for="n in list" :key="n.id" class="nf-item" :class="{unread:n.isRead===0}" @click.stop="clickNotif(n)">
          <span class="nf-dot" v-if="n.isRead===0"></span>
          <div class="nf-info">
            <div class="nf-title">{{ n.title }}</div>
            <div class="nf-content">{{ n.content }}</div>
            <div class="nf-time">{{ fmtTime(n.createTime) }}</div>
          </div>
        </div>
      </div>

      <div class="nf-footer" v-if="list.length">
        <el-button link class="nf-h-action" @click.stop="goCenter">查看全部通知</el-button>
      </div>
    </div>
  </el-popover>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';import { useRouter } from 'vue-router'
import { getNotifications, getUnreadCount, markAsRead, markAllAsRead, type NotificationMessage } from '@/api/notification'

const props = defineProps<{ userId?: number }>()
const router = useRouter()
const list = ref<NotificationMessage[]>([])
const unreadCount = ref(0)
const loading = ref(false)
let timer: number|null = null

function fmtTime(t:string):string { if(!t) return ''; const d=new Date(t);const now=+new Date();const diff=now-d.getTime(); if(diff<6e4) return '刚刚';if(diff<36e5) return `${Math.floor(diff/6e4)}分钟前`;if(diff<864e5) return `${Math.floor(diff/36e5)}小时前`;return d.toLocaleDateString('zh-CN',{month:'2-digit',day:'2-digit'}) }

async function loadNotifications() { loading.value=true; try { const r=await getNotifications(props.userId||0,5); list.value=r.data } catch{} finally { loading.value=false } }
async function loadCount() { try { const r=await getUnreadCount(props.userId||0); unreadCount.value=r.data } catch{} }
async function clickNotif(n:NotificationMessage) { if(n.isRead===0){ await markAsRead(n.id);n.isRead=1;unreadCount.value=Math.max(0,unreadCount.value-1) } if(n.linkUrl) window.location.href=n.linkUrl; else goCenter() }
async function markAll() { try { await markAllAsRead(props.userId||0); unreadCount.value=0;list.value.forEach(n=>n.isRead=1) } catch{} }
function goCenter() { router.push({ name:'NotificationCenter', query:{ userId: String(props.userId||0) } }) }
onMounted(()=>{ loadCount(); timer=window.setInterval(loadCount,30000) })
onUnmounted(()=>{ if(timer){clearInterval(timer);timer=null} })
</script>

<style lang="scss">
.notify-popover {
  background: #1a1a22 !important;
  border: 1px solid rgba(255,255,255,0.06) !important;
  border-radius: 16px !important;
  padding: 0 !important;
}
</style>

<style lang="scss" scoped>
.notify-bell { cursor:pointer;display:flex;align-items:center; }
.bell-btn { width:34px;height:34px;border-radius:10px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.06);color:$text-secondary;display:flex;align-items:center;justify-content:center;transition:all $transition-fast;&:hover{background:rgba(255,255,255,0.08);border-color:rgba(255,255,255,0.12);color:$text-primary;} }
.nf-panel { overflow:hidden; }
.nf-header { display:flex;justify-content:space-between;align-items:center;padding:14px 16px;border-bottom:1px solid rgba(255,255,255,0.06); }
.nf-h-title { font-size:$font-size-sm;font-weight:600;color:$text-primary; }
.nf-h-action { font-size:$font-size-xs;color:$text-muted;&:hover{color:$primary-light;} }
.nf-body { max-height:360px;overflow-y:auto; }
.nf-empty { text-align:center;padding:40px 0;color:$text-muted;p{margin-top:8px;font-size:$font-size-sm;} }
.nf-item { display:flex;align-items:flex-start;gap:10px;padding:12px 16px;border-bottom:1px solid rgba(255,255,255,0.04);cursor:pointer;transition:background $transition-fast;&:hover{background:rgba(255,255,255,0.02);}&.unread{background:rgba(99,102,241,0.03);} }
.nf-dot { width:7px;height:7px;border-radius:50%;background:$primary-color;flex-shrink:0;margin-top:6px; }
.nf-info { flex:1;min-width:0; }
.nf-title { font-size:$font-size-sm;font-weight:500;color:$text-primary;overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }
.nf-content { font-size:$font-size-xs;color:$text-secondary;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin:2px 0 4px; }
.nf-time { font-size:10px;color:$text-muted; }
.nf-footer { text-align:center;padding:10px;border-top:1px solid rgba(255,255,255,0.06); }
</style>
