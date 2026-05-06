<template>
  <div
    class="novel-card"
    @click="emit('click', novel)"
  >
    <div class="card-cover">
      <el-image v-if="novel.cover" :src="novel.cover" fit="cover" class="cover-img">
        <template #error><div class="cover-fb"><el-icon :size="28"><Notebook /></el-icon></div></template>
      </el-image>
      <div v-else class="cover-fb">
        <span class="cover-letter">{{ novel.title?.charAt(0) || '无' }}</span>
      </div>
      <span class="cover-tag" :class="`ct-${statusType}`">{{ statusText }}</span>
    </div>
    <div class="card-body">
      <h3 class="card-title">{{ novel.title || '未命名' }}</h3>
      <div class="card-meta">
        <span class="meta-genre">{{ genreText }}</span>
        <span class="meta-sep">·</span>
        <span>{{ formatWord(novel.wordCount || 0) }} 字</span>
      </div>
      <div class="card-footer" @click.stop>
        <el-button class="btn-edit" size="small" @click.stop="emit('edit', novel)">
          <el-icon><EditPen /></el-icon>继续编辑
        </el-button>
        <el-dropdown trigger="click" @command="(c: string) => emit('command', c, novel)">
          <el-button class="btn-more" size="small"><el-icon><MoreFilled /></el-icon></el-button>
          <template #dropdown>
            <el-dropdown-menu>
              <el-dropdown-item command="outline"><el-icon><EditPen /></el-icon>大纲</el-dropdown-item>
              <el-dropdown-item command="publish" divided><el-icon><Upload /></el-icon>发布</el-dropdown-item>
              <el-dropdown-item command="delete" divided style="color:#ef4444"><el-icon><Delete /></el-icon>删除</el-dropdown-item>
            </el-dropdown-menu>
          </template>
        </el-dropdown>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Notebook, EditPen, MoreFilled, Upload, Delete } from '@element-plus/icons-vue'
import type { Novel } from '@/types/api'

const props = defineProps<{ novel: Novel }>()
const emit = defineEmits<{
  click: [n: Novel]
  edit: [n: Novel]
  command: [cmd: string, n: Novel]
}>()

const genreMap: Record<string,string> = {
  urban:'都市', fantasy:'玄幻', xianxia:'仙侠', transmigration:'穿越', scifi:'科幻', mystery:'悬疑', romance:'言情'
}
const statusMap: Record<number,{text:string;type:string}> = {
  0:{text:'草稿',type:'slate'}, 1:{text:'写作中',type:'purple'}, 2:{text:'完结',type:'green'}, 3:{text:'已发布',type:'amber'}
}
const genreText = computed(() => genreMap[props.novel.genre||''] || props.novel.genre || '未知')
const statusText = computed(() => statusMap[props.novel.status]?.text || '未知')
const statusType = computed(() => statusMap[props.novel.status]?.type || 'slate')

function formatWord(n: number) { return n >= 10000 ? `${(n/10000).toFixed(1)}万` : String(n) }
</script>

<style lang="scss" scoped>
.novel-card {
  background: $bg-elevated;
  border: 1px solid $border-color;
  border-radius: $border-radius;
  overflow: hidden;
  cursor: pointer;
  transition: all $transition-normal;

  &:hover {
    border-color: rgba(99,102,241,0.25);
    box-shadow: 0 6px 24px rgba(99,102,241,0.1);
    transform: translateY(-3px);
  }
}

.card-cover {
  position: relative;
  height: 140px;
  overflow: hidden;
  background: linear-gradient(135deg, rgba(99,102,241,0.08), rgba(139,92,246,0.04));

  .cover-img { width: 100%; height: 100%; }
  .cover-fb {
    width: 100%; height: 100%;
    display: flex; align-items: center; justify-content: center;
    color: rgba(99,102,241,0.15);
  }
  .cover-letter { font-size: 52px; font-weight: 700; color: rgba(99,102,241,0.12); }
}

.cover-tag {
  position: absolute;
  top: 10px; right: 10px;
  font-size: 10px; font-weight: 600;
  padding: 2px 9px; border-radius: 5px;
  color: #fff;

  &.ct-purple { background: rgba(99,102,241,0.7); }
  &.ct-green  { background: rgba(16,185,129,0.7); }
  &.ct-amber  { background: rgba(245,158,11,0.7); }
  &.ct-slate  { background: rgba(148,163,184,0.4); }
}

.card-body {
  padding: $space-md;

  .card-title {
    font-size: $font-size-md; font-weight: 600; color: $text-primary;
    margin-bottom: 6px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
  }
}

.card-meta {
  display: flex; align-items: center; gap: 6px;
  font-size: $font-size-xs; color: $text-muted; margin-bottom: $space-sm;
  .meta-genre { color: $primary-light; }
  .meta-sep { opacity: 0.3; }
}

.card-footer {
  display: flex; gap: 6px;
}

.btn-edit {
  flex: 1;
  background: rgba(99,102,241,0.08);
  border: 1px solid rgba(99,102,241,0.12);
  color: $primary-light;
  font-weight: 500; border-radius: 8px;
  &:hover { background: rgba(99,102,241,0.15); border-color: rgba(99,102,241,0.2); }
}

.btn-more {
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.06);
  color: $text-muted; border-radius: 8px;
  &:hover { background: rgba(255,255,255,0.06); color: $text-primary; }
}
</style>
