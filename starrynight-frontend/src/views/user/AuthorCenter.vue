<template>
  <div class="author-center">
    <div class="page-toolbar">
      <div>
        <h2 class="toolbar-title">创作中心</h2>
        <p class="toolbar-sub">管理你的作品和创作素材</p>
      </div>
      <el-button class="btn-primary" @click="showCreateDialog = true">
        <el-icon><Plus /></el-icon>创建作品
      </el-button>
    </div>

    <div class="stats-row">
      <div class="stat-item" v-for="s in statsItems" :key="s.key">
        <div class="stat-icon" :class="`grad-${s.color}`">
          <component :is="s.icon" :size="20" />
        </div>
        <div class="stat-info">
          <span class="stat-val">{{ s.value }}</span>
          <span class="stat-lbl">{{ s.label }}</span>
        </div>
      </div>
    </div>

    <div class="content-layout">
      <div class="content-main">
        <div class="panel">
          <div class="panel-header">
            <div class="panel-tabs">
              <button
                v-for="tab in tabs"
                :key="tab.value"
                class="tab-btn"
                :class="{ active: activeTab === tab.value }"
                @click="activeTab = tab.value"
              >{{ tab.label }}</button>
            </div>
          </div>

          <div v-if="filtered.length" class="novel-grid">
            <NovelCard
              v-for="novel in filtered"
              :key="novel.id"
              :novel="novel"
              @click="openNovel(novel)"
              @edit="openNovel(novel)"
              @command="(cmd, n) => handleCmd(cmd, n)"
            />
          </div>
          <div v-else class="empty-block">
            <svg viewBox="0 0 80 80" width="80" height="80" fill="none">
              <rect x="20" y="10" width="40" height="60" rx="4" stroke="currentColor" stroke-width="1.5" opacity="0.3" />
              <line x1="28" y1="24" x2="52" y2="24" stroke="currentColor" stroke-width="1.5" opacity="0.15" />
              <line x1="28" y1="34" x2="48" y2="34" stroke="currentColor" stroke-width="1.5" opacity="0.15" />
              <line x1="28" y1="44" x2="44" y2="44" stroke="currentColor" stroke-width="1.5" opacity="0.15" />
            </svg>
            <p>暂无作品</p>
            <el-button class="btn-primary" @click="showCreateDialog = true">创建第一部作品</el-button>
          </div>
        </div>
      </div>

      <aside class="content-side">
        <div class="side-panel">
          <h4 class="side-label">快捷入口</h4>
          <div class="side-links">
            <div class="side-link" @click="$router.push('/knowledge')">
              <span class="sl-icon sl-purple"><Collection /></span>
              <div><span class="sl-text">知识库</span><span class="sl-hint">{{ materialCounts.library }} 个</span></div>
              <el-icon class="sl-arrow"><ArrowRight /></el-icon>
            </div>
            <div class="side-link" @click="$router.push('/characters')">
              <span class="sl-icon sl-rose"><UserFilled /></span>
              <div><span class="sl-text">角色库</span><span class="sl-hint">{{ materialCounts.character }} 个</span></div>
              <el-icon class="sl-arrow"><ArrowRight /></el-icon>
            </div>
            <div class="side-link" @click="$router.push('/materials')">
              <span class="sl-icon sl-amber"><Files /></span>
              <div><span class="sl-text">素材库</span><span class="sl-hint">{{ materialCounts.material }} 个</span></div>
              <el-icon class="sl-arrow"><ArrowRight /></el-icon>
            </div>
          </div>
        </div>

        <div class="side-panel">
          <h4 class="side-label">工具箱</h4>
          <div class="tool-grid">
            <div class="tool-chip" v-for="t in tools" :key="t.name" @click="$router.push(t.route)">
              <span class="tc-icon"><component :is="t.icon" :size="16" /></span>
              <span class="tc-name">{{ t.name }}</span>
            </div>
          </div>
        </div>

        <div class="side-panel tip-panel">
          <div class="tip-top">
            <h4 class="side-label">创作灵感</h4>
            <el-button link class="tip-refresh" @click="refreshTip"><el-icon><Refresh /></el-icon></el-button>
          </div>
          <p class="tip-quote">"{{ currentTip.content }}"</p>
          <p class="tip-author">—…{{ currentTip.title }}</p>
        </div>
      </aside>
    </div>

    <el-dialog v-model="showCreateDialog" title="创建作品" width="560px">
      <el-form ref="formRef" :model="cForm" :rules="rules" label-position="top" size="large">
        <el-row :gutter="16">
          <el-col :span="16">
            <el-form-item label="作品标题" prop="title">
              <el-input v-model="cForm.title" placeholder="输入作品标题" maxlength="50" show-word-limit />
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="题材" prop="genre">
              <el-select v-model="cForm.genre" placeholder="选择" style="width:100%">
                <el-option label="都市" value="urban" />
                <el-option label="玄幻" value="fantasy" />
                <el-option label="仙侠" value="xianxia" />
                <el-option label="穿越" value="transmigration" />
                <el-option label="科幻" value="scifi" />
                <el-option label="悬疑" value="mystery" />
                <el-option label="言情" value="romance" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="简介" prop="synopsis">
          <el-input v-model="cForm.synopsis" type="textarea" :rows="4" placeholder="简要介绍你的故事…" maxlength="500" show-word-limit />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showCreateDialog = false">取消</el-button>
        <el-button class="btn-primary" :loading="creating" @click="handleCreate">开始创作</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import type { FormInstance, FormRules } from 'element-plus'
import { Plus, Document, EditPen, TrendCharts, MagicStick, Collection, UserFilled, Files, Link, Notebook, Location, User, Refresh, ArrowRight } from '@element-plus/icons-vue'
import type { Novel } from '@/types/api'
import { listNovels, createNovel } from '@/api/novel'
import NovelCard from './components/NovelCard.vue'
import { useUserSessionStore } from '@/stores/auth'

const router = useRouter()
const auth = useUserSessionStore()
const formRef = ref<FormInstance>()
const activeTab = ref('all')
const showCreateDialog = ref(false)
const creating = ref(false)
const novels = ref<Novel[]>([])

const tabs = [
  { label: '全部', value: 'all' },
  { label: '写作中', value: 'writing' },
  { label: '已完成', value: 'finished' }
]

const materialCounts = reactive({ library: 5, character: 12, material: 28 })

const cForm = reactive({ title: '', genre: '', synopsis: '' })
const rules: FormRules = {
  title: [{ required: true, message: '请输入标题', trigger: 'blur' }],
  genre: [{ required: true, message: '请选择题材', trigger: 'change' }]
}

const tools = [
  { name: '金手指', icon: Link, route: '/tools' },
  { name: '书名生成', icon: Notebook, route: '/tools' },
  { name: '世界观', icon: Location, route: '/tools' },
  { name: '人物速成', icon: User, route: '/tools' }
]

const tips = [
  { title: '设定目标', content: '每天设定具体的写作目标，保持创作动力。' },
  { title: '善用AI', content: '在瓶颈期让AI提供情节建议，但保持自己的风格。' },
  { title: '节奏把控', content: '每个章节要有明确进展，避免拖慢节奏。' },
  { title: '回顾大纲', content: '写作中经常回顾大纲，确保方向一致。' }
]
const currentTip = ref(tips[0])

const statsItems = computed(() => [
  { key:'count', icon:Document, label:'作品数', color:'purple', value:novels.value.length },
  { key:'words', icon:EditPen, label:'总字数', color:'amber', value:formatWord(novels.value.reduce((s,n)=>s+(n.wordCount||0),0)) },
  { key:'month', icon:TrendCharts, label:'本月字数', color:'cyan', value:formatWord(0) },
  { key:'ai', icon:MagicStick, label:'AI使用', color:'emerald', value:0 }
])

const filtered = computed(() => {
  if (activeTab.value === 'writing') return novels.value.filter(n => n.status === 1)
  if (activeTab.value === 'finished') return novels.value.filter(n => n.status === 2)
  return novels.value
})

function formatWord(n: number) { return n >= 10000 ? `${(n/10000).toFixed(1)}万` : String(n) }

function refreshTip() { currentTip.value = tips[Math.floor(Math.random() * tips.length)] }

async function load() {
  try { const r = await listNovels({ page: 1, size: 100 }); novels.value = r?.records || [] } catch {}
}

async function handleCreate() {
  if (!formRef.value) return
  await formRef.value.validate(async (ok) => { if (!ok) return
    creating.value = true
    try {
      const r = await createNovel({ title: cForm.title, genre: cForm.genre, synopsis: cForm.synopsis })
      ElMessage.success('创建成功')
      showCreateDialog.value = false
      Object.assign(cForm, { title: '', genre: '', synopsis: '' })
      await load()
      if (r?.id) router.push(`/novel/${r.id}`)
    } catch { ElMessage.error('创建失败') } finally { creating.value = false }
  })
}

function openNovel(n: Novel) { router.push(`/novel/${n.id}`) }
function handleCmd(cmd: string, n: Novel) {
  if (cmd === 'outline') router.push(`/novel/${n.id}/outline`)
  if (cmd === 'delete') { /* delete */ }
}

onMounted(load)
</script>

<style lang="scss" scoped>
.author-center {
  padding: $space-xl;
  max-width: 1300px;
  margin: 0 auto;
}

.page-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: $space-lg;

  .toolbar-title { font-size: $font-size-xl; font-weight: 700; color: $text-primary; }
  .toolbar-sub { font-size: $font-size-sm; color: $text-muted; margin-top: 2px; }
}

.btn-primary {
  background: linear-gradient(135deg, $primary-color, $primary-dark);
  border: none; color: #fff; font-weight: 600; border-radius: 10px;
  padding: 10px 22px;
  &:hover { background: linear-gradient(135deg, $primary-light, $primary-color); box-shadow: 0 0 24px rgba(99,102,241,0.35); transform: translateY(-1px); }
}

.stats-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: $space-md;
  margin-bottom: $space-lg;
}

.stat-item {
  display: flex; align-items: center; gap: $space-sm;
  padding: $space-md;
  background: $bg-surface; border: 1px solid $border-color; border-radius: $border-radius;
}

.stat-icon {
  width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center;
  &.grad-purple  { background: linear-gradient(135deg, #6366f1, #818cf8); color: #fff; }
  &.grad-amber   { background: linear-gradient(135deg, #f59e0b, #fbbf24); color: #fff; }
  &.grad-cyan    { background: linear-gradient(135deg, #06b6d4, #22d3ee); color: #fff; }
  &.grad-emerald { background: linear-gradient(135deg, #10b981, #34d399); color: #fff; }
}

.stat-info { display: flex; flex-direction: column; }
.stat-val { font-size: $font-size-lg; font-weight: 700; color: $text-primary; }
.stat-lbl { font-size: $font-size-xs; color: $text-muted; }

.content-layout {
  display: grid;
  grid-template-columns: 1fr 280px;
  gap: $space-lg;
  align-items: start;
}

.panel {
  background: $bg-surface;
  border: 1px solid $border-color;
  border-radius: $border-radius-lg;
  overflow: hidden;
}

.panel-header {
  padding: $space-md $space-lg;
  border-bottom: 1px solid $border-color;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.panel-tabs {
  display: flex;
  gap: $space-xs;
  background: rgba(255,255,255,0.02);
  border-radius: 8px;
  padding: 3px;
}

.tab-btn {
  padding: 5px 16px;
  border: none;
  background: transparent;
  color: $text-secondary;
  font-size: $font-size-sm;
  border-radius: 6px;
  cursor: pointer;
  transition: all $transition-fast;

  &:hover { color: $text-primary; }
  &.active { background: rgba(99,102,241,0.15); color: $primary-light; }
}

.novel-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: $space-md;
  padding: $space-lg;
}

.empty-block {
  text-align: center;
  padding: 60px 0;
  color: $text-muted;

  svg { margin: 0 auto; display: block; }
  p { margin: $space-md 0; font-size: $font-size-sm; }
}

.content-side {
  display: flex;
  flex-direction: column;
  gap: $space-md;
}

.side-panel {
  background: $bg-surface;
  border: 1px solid $border-color;
  border-radius: $border-radius-lg;
  padding: $space-md;

  .side-label {
    font-size: 10px;
    font-weight: 600;
    color: $text-muted;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    margin-bottom: $space-sm;
  }
}

.side-links { display: flex; flex-direction: column; gap: 4px; }

.side-link {
  display: flex; align-items: center; gap: $space-sm;
  padding: $space-sm 12px; border-radius: 10px;
  cursor: pointer; transition: all $transition-fast;

  &:hover { background: rgba(255,255,255,0.03); }
}

.sl-icon {
  width: 32px; height: 32px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;

  &.sl-purple { background: rgba(99,102,241,0.12); color: #a5b4fc; }
  &.sl-rose   { background: rgba(244,114,182,0.12); color: #f472b6; }
  &.sl-amber  { background: rgba(251,191,36,0.12); color: #fbbf24; }
}

.sl-text { font-size: $font-size-sm; font-weight: 500; color: $text-primary; display: block; }
.sl-hint { font-size: $font-size-xs; color: $text-muted; }
.sl-arrow { color: $text-muted; font-size: 12px; margin-left: auto; }

.tool-grid {
  display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px;
}

.tool-chip {
  display: flex; flex-direction: column; align-items: center; gap: 6px;
  padding: $space-sm; border-radius: 10px;
  background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.04);
  cursor: pointer; transition: all $transition-fast;

  &:hover { background: rgba(99,102,241,0.04); border-color: rgba(99,102,241,0.12); }
  .tc-icon { color: $text-secondary; }
  .tc-name { font-size: $font-size-xs; color: $text-muted; }
}

.tip-panel {
  .tip-top { display: flex; justify-content: space-between; align-items: center; }
  .tip-refresh { color: $text-muted; &:hover { color: $primary-light; } }
  .tip-quote { font-size: $font-size-sm; color: $text-secondary; line-height: 1.7; font-style: italic; }
  .tip-author { font-size: $font-size-xs; color: $text-muted; margin-top: 8px; text-align: right; }
}
</style>
