<template>
  <div class="novel-detail">
    <div class="detail-topbar">
      <div class="dt-left">
        <el-button link class="back-btn" @click="$router.back()">
          <el-icon><ArrowLeft /></el-icon>返回
        </el-button>
        <span class="dt-title">{{ novel?.title || '加载中..' }}</span>
        <span class="dt-badge" :class="`bs-${statusType}`">{{ statusText }}</span>
      </div>
      <div class="dt-right">
        <el-tooltip
          :disabled="!exportBlocked"
          content="请先完成实名核验后再导出，可在个人中心填写证件信息并发起核验"
          placement="bottom"
        >
          <span class="export-dd-wrap">
            <el-dropdown :disabled="exportBlocked" @command="handleExport">
              <el-button class="btn-ghost" :disabled="exportBlocked">
                导出<el-icon class="ml-1"><ArrowDown /></el-icon>
              </el-button>
              <template #dropdown>
                <el-dropdown-menu>
                  <el-dropdown-item command="word">Word</el-dropdown-item>
                  <el-dropdown-item command="txt">TXT</el-dropdown-item>
                  <el-dropdown-item command="html">HTML</el-dropdown-item>
                </el-dropdown-menu>
              </template>
            </el-dropdown>
          </span>
        </el-tooltip>
        <el-button class="btn-primary" @click="$router.push(`/novel/${novelId}/editor`)">
          <el-icon><EditPen /></el-icon>开始创作        </el-button>
      </div>
    </div>

    <div class="detail-layout">
      <aside class="detail-side">
        <div class="ds-cover-block">
          <div class="cover-wrap">
            <el-image v-if="novel?.cover" :src="novel.cover" fit="cover" class="cover-img" />
            <div v-else class="cover-place"><span>{{ novel?.title?.charAt(0) || '无' }}</span></div>
          </div>
          <div class="ds-tags">
            <span class="dtag dt-genre">{{ genreText }}</span>
          </div>
        </div>
        <div class="ds-stats">
          <div class="dstat"><span class="dsv">{{ formatWord(novel?.wordCount||0) }}</span><span class="dsl">总字数</span></div>
          <div class="dstat"><span class="dsv">{{ novel?.chapterCount || 0 }}</span><span class="dsl">章节</span></div>
          <div class="dstat"><span class="dsv">{{ progress }}%</span><span class="dsl">进度</span></div>
        </div>
        <div class="ds-progress">
          <div class="prog-bar"><div class="prog-fill" :style="{ width: `${progress}%` }"></div></div>
        </div>

        <nav class="ds-nav">
          <router-link
            v-for="item in navItems"
            :key="item.path"
            :to="`/novel/${novelId}${item.path}`"
            class="ds-nav-item"
            :class="{ active: activePath === item.path }"
          >
            <component :is="item.icon" :size="18" />
            <span>{{ item.label }}</span>
          </router-link>
        </nav>

        <div class="ds-tools">
          <h5>快捷工具</h5>
          <div class="dst-link" @click="$router.push(`/novel/${novelId}/foresight`)"><el-icon><WarningFilled /></el-icon>伏笔管理</div>
          <div class="dst-link" @click="$router.push(`/novel/${novelId}/branch`)"><el-icon><Share /></el-icon>分支版本</div>
          <div class="dst-link" @click="$router.push(`/novel/${novelId}/engine`)"><el-icon><Cpu /></el-icon>星夜引擎</div>
        </div>
      </aside>

      <main class="detail-main">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import {
  ArrowLeft, ArrowDown, EditPen, Collection, FolderOpened, UserFilled, Clock, TrendCharts, Document, WarningFilled, Share, Cpu
} from '@element-plus/icons-vue'
import type { Novel } from '@/types/api'
import { getNovel, exportNovel, downloadWordFile } from '@/api/novel'
import { useUserSessionStore } from '@/stores/auth'
import { fetchRegisterOptions } from '@/api/authPublic'

const route = useRoute()
const auth = useUserSessionStore()
const realnameGateEnabled = ref(false)
const novelId = computed(() => route.params.id as string)
const novel = ref<Novel | null>(null)
const activePath = ref('')

const navItems = [
  { path: '/outline', label: '大纲管理', icon: Collection },
  { path: '/chapters', label: '章节管理', icon: Document },
  { path: '/volumes', label: '卷管理', icon: FolderOpened },
  { path: '/character', label: '角色库', icon: UserFilled },
  { path: '/timeline', label: '时间线', icon: Clock },
  { path: '/rhythm', label: '节奏仪表', icon: TrendCharts }
]

const genreMap: Record<string,string> = {
  urban:'都市', fantasy:'玄幻', xianxia:'仙侠', transmigration:'穿越', scifi:'科幻', mystery:'悬疑', romance:'言情'
}
const statusMap: Record<number,{t:string;ty:string}> = {
  0:{t:'草稿',ty:'slate'},1:{t:'写作中',ty:'purple'},2:{t:'完结',ty:'green'},3:{t:'已发布',ty:'amber'}
}
const genreText = computed(() => genreMap[novel.value?.genre||''] || novel.value?.genre || '未知')
const statusText = computed(() => statusMap[novel.value?.status||0]?.t || '未知')
const statusType = computed(() => statusMap[novel.value?.status||0]?.ty || 'slate')
const progress = computed(() => Math.min(100, Math.round(((novel.value?.wordCount||0) / 500000) * 100)))

const exportBlocked = computed(
  () => realnameGateEnabled.value && auth.userInfo != null && !auth.userInfo.realNameVerified
)

function formatWord(n:number) { return n>=10000?`${(n/10000).toFixed(1)}万`:String(n) }

function updateActive() {
  const p = route.path.replace(`/novel/${novelId.value}`, '') || '/outline'
  activePath.value = p.startsWith('/') ? p : `/${p}`
}

watch(() => route.path, () => updateActive())

onMounted(async () => {
  updateActive()
  try {
    const o = await fetchRegisterOptions()
    realnameGateEnabled.value = o.realNameVerificationEnabled === true
    await auth.initProfileIfNeeded()
  } catch {
    realnameGateEnabled.value = false
  }
  try {
    const r = await getNovel(Number(novelId.value))
    if (r) novel.value = r
  } catch {
    /* ignore */
  }
})

async function handleExport(fmt: 'txt'|'html'|'word') {
  if (!novel.value?.id) return
  if (exportBlocked.value) {
    ElMessage.warning('请先完成实名核验后再导出，可在个人中心填写证件信息并发起核验')
    return
  }
  try {
    if (fmt === 'word') { await downloadWordFile(novel.value.id,`${novel.value.title}.docx`); ElMessage.success('导出成功') }
    else {
      const c = await exportNovel(novel.value.id, fmt)
      const b = new Blob([c],{type:fmt==='html'?'text/html':'text/plain'})
      const u = URL.createObjectURL(b)
      const a = document.createElement('a'); a.href=u; a.download=`${novel.value.title}.${fmt}`
      document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(u)
      ElMessage.success('导出成功')
    }
  } catch { ElMessage.error('导出失败') }
}
</script>

<style lang="scss" scoped>
.novel-detail {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.detail-topbar {
  height: 48px; min-height: 48px;
  display: flex; align-items: center; justify-content: space-between;
  padding: 0 $space-xl;
  background: $bg-surface;
  border-bottom: 1px solid $border-color;
}

.dt-left {
  display: flex; align-items: center; gap: $space-sm;

  .dt-title { font-size: $font-size-md; font-weight: 600; color: $text-primary; }
}

.back-btn { color: $text-secondary; font-size: $font-size-sm; &:hover { color: $text-primary; } }

.dt-badge {
  font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 5px;
  &.bs-purple { background: rgba(99,102,241,0.15); color: #a5b4fc; }
  &.bs-green  { background: rgba(16,185,129,0.12); color: #34d399; }
  &.bs-amber  { background: rgba(245,158,11,0.12); color: #fbbf24; }
  &.bs-slate  { background: rgba(148,163,184,0.12); color: #94a3b8; }
}

.dt-right { display: flex; gap: $space-sm; }

.export-dd-wrap {
  display: inline-block;
}

.btn-ghost {
  background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
  color: $text-secondary; border-radius: 8px;
  &:hover { background: rgba(255,255,255,0.06); color: $text-primary; }
}

.ml-1 { margin-left: 4px; }

.btn-primary {
  background: linear-gradient(135deg, $primary-color, $primary-dark);
  border: none; color: #fff; font-weight: 600; border-radius: 8px;
  &:hover { background: linear-gradient(135deg, $primary-light, $primary-color); box-shadow: 0 0 20px rgba(99,102,241,0.3); }
}

.detail-layout {
  flex: 1;
  display: flex;
  overflow: hidden;
}

.detail-side {
  width: 240px; min-width: 240px;
  background: $bg-surface;
  border-right: 1px solid $border-color;
  overflow-y: auto;
  padding: $space-lg;
  display: flex; flex-direction: column; gap: $space-md;
}

.ds-cover-block {
  display: flex; flex-direction: column; align-items: center; gap: $space-sm;
}

.cover-wrap {
  width: 100px; height: 140px; border-radius: 8px; overflow: hidden;
  background: linear-gradient(135deg, rgba(99,102,241,0.08), rgba(139,92,246,0.04));
  display: flex; align-items: center; justify-content: center;
  .cover-img { width: 100%; height: 100%; }
  .cover-place span { font-size: 40px; font-weight: 700; color: rgba(99,102,241,0.12); }
}

.ds-tags { display: flex; gap: 4px; }
.dtag { font-size: 11px; padding: 2px 10px; border-radius: 5px; &.dt-genre { background: rgba(99,102,241,0.1); color: #a5b4fc; } }

.ds-stats { display: flex; justify-content: space-around; }
.dstat { display: flex; flex-direction: column; align-items: center; }
.dsv { font-size: $font-size-lg; font-weight: 700; color: $text-primary; }
.dsl { font-size: $font-size-xs; color: $text-muted; }

.ds-progress { padding: 0 4px; }
.prog-bar { height: 4px; background: rgba(255,255,255,0.06); border-radius: 2px; overflow: hidden; }
.prog-fill { height: 100%; background: linear-gradient(90deg, $primary-color, $accent-color); border-radius: 2px; transition: width $transition-normal; }

.ds-nav { display: flex; flex-direction: column; gap: 2px; }

.ds-nav-item {
  display: flex; align-items: center; gap: 10px;
  padding: 9px 12px; border-radius: 8px;
  font-size: $font-size-sm; font-weight: 500; color: $text-secondary;
  transition: all $transition-fast;

  &:hover { background: rgba(255,255,255,0.03); color: $text-primary; }
  &.active { background: rgba(99,102,241,0.08); color: $primary-light; }
}

.ds-tools {
  border-top: 1px solid $border-color; padding-top: $space-md;

  h5 { font-size: 10px; font-weight: 600; color: $text-muted; text-transform: uppercase; letter-spacing: 1px; margin-bottom: $space-sm; }
  .dst-link {
    display: flex; align-items: center; gap: 8px;
    padding: 7px 10px; border-radius: 8px;
    font-size: $font-size-sm; color: $text-muted; cursor: pointer;
    transition: all $transition-fast;
    &:hover { background: rgba(255,255,255,0.03); color: $text-secondary; }
  }
}

.detail-main {
  flex: 1;
  overflow-y: auto;
  padding: $space-xl;
}
</style>
