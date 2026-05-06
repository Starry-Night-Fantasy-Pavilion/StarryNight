<template>
  <el-card
    class="chapter-card"
    :class="{
      'is-active': isActive,
      'is-expanded': expanded,
      'is-draft': chapter.status === 0
    }"
    shadow="hover"
    @click="handleClick"
  >
    <div class="chapter-header" @click.stop="toggleExpand">
      <div class="chapter-info">
        <el-icon v-if="expanded" class="expand-icon"><ArrowDown /></el-icon>
        <el-icon v-else class="expand-icon"><ArrowRight /></el-icon>
        <span class="chapter-number">{{ chapterTitle }}</span>
        <el-tag v-if="chapter.status === 0" type="info" size="small">草稿</el-tag>
        <el-tag v-if="chapter.version > 1" type="warning" size="small">V{{ chapter.version }}</el-tag>
      </div>
      <div class="chapter-meta">
        <span class="word-count">{{ formatWordCount(chapter.wordCount) }}</span>
        <span class="update-time">{{ formatTime(chapter.updateTime) }}</span>
      </div>
    </div>

    <el-collapse-transition>
      <div v-show="expanded" class="chapter-content">
        <div v-if="chapter.outline" class="chapter-outline">
          <h4>章节大纲</h4>
          <p>{{ chapter.outline }}</p>
        </div>
        <div v-if="chapter.content" class="chapter-preview">
          <h4>内容预览</h4>
          <p class="preview-text">{{ previewContent }}</p>
        </div>
        <div class="chapter-actions">
          <el-button size="small" type="primary" @click.stop="handleEdit">
            <el-icon><Edit /></el-icon>
            编辑
          </el-button>
          <el-button size="small" @click.stop="handleAiAssist">
            <el-icon><MagicStick /></el-icon>
            AI辅助
          </el-button>
          <el-dropdown trigger="click" @command="handleCommand">
            <el-button size="small">
              <el-icon><MoreFilled /></el-icon>
            </el-button>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item command="view">查看全文</el-dropdown-item>
                <el-dropdown-item command="copy">复制内容</el-dropdown-item>
                <el-dropdown-item command="history">版本历史</el-dropdown-item>
                <el-dropdown-item command="delete" divided style="color: var(--el-color-danger)">删除</el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </div>
      </div>
    </el-collapse-transition>
  </el-card>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { ArrowDown, ArrowRight, Edit, MoreFilled, MagicStick } from '@element-plus/icons-vue'
import type { NovelChapter } from '@/types/api'

interface Props {
  chapter: NovelChapter
  isActive?: boolean
  expanded?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isActive: false,
  expanded: false
})

const emit = defineEmits<{
  click: [chapter: NovelChapter]
  edit: [chapter: NovelChapter]
  aiAssist: [chapter: NovelChapter]
  command: [command: string, chapter: NovelChapter]
  toggle: [chapter: NovelChapter]
}>()

const chapterTitle = computed(() => props.chapter.title || `第${props.chapter.chapterOrder}章`)

const previewContent = computed(() => {
  if (!props.chapter.content) return '暂无内容'
  const text = props.chapter.content.replace(/<[^>]+>/g, '').replace(/\s+/g, ' ')
  return text.length > 150 ? text.substring(0, 150) + '...' : text
})

function formatWordCount(count: number): string {
  if (count >= 10000) {
    return `${(count / 10000).toFixed(1)}万字`
  }
  return `${count}字`
}

function formatTime(time?: string): string {
  if (!time) return ''
  const date = new Date(time)
  const now = new Date()
  const diff = now.getTime() - date.getTime()
  const days = Math.floor(diff / (1000 * 60 * 60 * 24))

  if (days === 0) return '今天'
  if (days === 1) return '昨天'
  if (days < 7) return `${days}天前`
  if (days < 30) return `${Math.floor(days / 7)}周前`
  return date.toLocaleDateString()
}

function handleClick() {
  emit('click', props.chapter)
}

function handleEdit() {
  emit('edit', props.chapter)
}

function handleAiAssist() {
  emit('aiAssist', props.chapter)
}

function handleCommand(command: string) {
  emit('command', command, props.chapter)
}

function toggleExpand() {
  emit('toggle', props.chapter)
}
</script>

<style lang="scss" scoped>
.chapter-card {
  margin-bottom: 8px;
  transition: all 0.2s;

  &.is-active {
    border-color: var(--el-color-primary);
    background: var(--el-color-primary-light-9);
  }

  &.is-draft {
    opacity: 0.8;
  }

  &:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }
}

.chapter-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  cursor: pointer;
  padding: 8px 0;

  .chapter-info {
    display: flex;
    align-items: center;
    gap: 8px;

    .expand-icon {
      font-size: 14px;
      color: var(--el-text-color-secondary);
    }

    .chapter-number {
      font-weight: 600;
      font-size: 14px;
    }
  }

  .chapter-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 12px;
    color: var(--el-text-color-secondary);

    .word-count {
      color: var(--el-color-primary);
    }
  }
}

.chapter-content {
  padding: 12px 0 0;
  border-top: 1px solid var(--el-border-color-lighter);

  h4 {
    font-size: 12px;
    font-weight: 600;
    margin: 0 0 8px;
    color: var(--el-text-color-regular);
  }

  p {
    margin: 0;
    font-size: 13px;
    color: var(--el-text-color-secondary);
    line-height: 1.6;
  }

  .chapter-outline {
    margin-bottom: 12px;
    padding: 8px;
    background: var(--el-fill-color-light);
    border-radius: 4px;
  }

  .chapter-preview {
    margin-bottom: 12px;

    .preview-text {
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
  }

  .chapter-actions {
    display: flex;
    gap: 8px;
    padding-top: 8px;
    border-top: 1px dashed var(--el-border-color-lighter);
  }
}
</style>
