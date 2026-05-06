<template>
  <div class="diff-compare">
    <div class="diff-header">
      <h4>📋 修改对比</h4>
      <div class="diff-actions">
        <el-radio-group v-model="viewType" size="small">
          <el-radio-button label="split">分栏对比</el-radio-button>
          <el-radio-button label="unified">合并对比</el-radio-button>
        </el-radio-group>
        <el-button size="small" @click="handleAcceptAll">
          <el-icon><Check /></el-icon>
          接受全部
        </el-button>
      </div>
    </div>

    <div class="diff-content" :class="viewType">
      <div v-if="viewType === 'split'" class="split-view">
        <div class="diff-pane original">
          <div class="pane-header">
            <span class="pane-title">原内容</span>
            <el-tag v-if="originalVersion" size="small" type="info">
              V{{ originalVersion }}
            </el-tag>
          </div>
          <div class="pane-content" v-html="formattedOriginal"></div>
        </div>
        <div class="diff-divider">
          <el-icon><DArrowLeft /></el-icon>
        </div>
        <div class="diff-pane modified">
          <div class="pane-header">
            <span class="pane-title">修改后</span>
            <el-tag v-if="modifiedVersion" size="small" type="success">
              V{{ modifiedVersion }}
            </el-tag>
            <el-tag v-if="hasChanges" size="small" type="warning">
              🔥 已修改
            </el-tag>
          </div>
          <div class="pane-content" v-html="formattedModified"></div>
        </div>
      </div>

      <div v-else class="unified-view">
        <div class="unified-content" v-html="unifiedDiff"></div>
      </div>
    </div>

    <div class="diff-stats">
      <span class="stat-item">
        <el-icon><Plus /></el-icon>
        添加 {{ stats.added }} 行
      </span>
      <span class="stat-item removed">
        <el-icon><Delete /></el-icon>
        删除 {{ stats.removed }} 行
      </span>
      <span class="stat-item modified">
        <el-icon><Edit /></el-icon>
        修改 {{ stats.modified }} 处
      </span>
    </div>

    <div v-if="showActions" class="diff-footer">
      <el-button @click="handleReject">
        <el-icon><Close /></el-icon>
        拒绝
      </el-button>
      <el-button type="primary" @click="handleAccept">
        <el-icon><Check /></el-icon>
        接受
      </el-button>
      <el-button @click="handleDiscuss">
        <el-icon><ChatDotRound /></el-icon>
        与AI讨论
      </el-button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive } from 'vue'
import { Check, Close, DArrowLeft, Plus, Delete, Edit, ChatDotRound } from '@element-plus/icons-vue'

interface DiffStats {
  added: number
  removed: number
  modified: number
}

interface DiffLine {
  type: 'added' | 'removed' | 'unchanged' | 'modified'
  content: string
  lineNumber?: number
}

const props = defineProps<{
  originalContent: string
  modifiedContent: string
  originalVersion?: number
  modifiedVersion?: number
  showActions?: boolean
}>()

const emit = defineEmits<{
  accept: [content: string]
  reject: []
  discuss: []
}>()

const viewType = ref<'split' | 'unified'>('split')

const stats = reactive<DiffStats>({
  added: 0,
  removed: 0,
  modified: 0
})

const hasChanges = computed(() => {
  return props.originalContent !== props.modifiedContent
})

const formattedOriginal = computed(() => {
  return formatContent(props.originalContent)
})

const formattedModified = computed(() => {
  return formatContent(props.modifiedContent)
})

const unifiedDiff = computed(() => {
  return generateUnifiedDiff()
})

function formatContent(content: string): string {
  if (!content) return '<p class="empty-content">暂无内容</p>'
  return content
    .split('\n')
    .map(line => `<p>${escapeHtml(line) || '&nbsp;'}</p>`)
    .join('')
}

function generateUnifiedDiff(): string {
  const originalLines = props.originalContent.split('\n')
  const modifiedLines = props.modifiedContent.split('\n')
  const result: string[] = []

  let i = 0, j = 0
  while (i < originalLines.length || j < modifiedLines.length) {
    if (i >= originalLines.length) {
      result.push(`<div class="diff-line added">+ ${escapeHtml(modifiedLines[j])}</div>`)
      stats.added++
      j++
    } else if (j >= modifiedLines.length) {
      result.push(`<div class="diff-line removed">- ${escapeHtml(originalLines[i])}</div>`)
      stats.removed++
      i++
    } else if (originalLines[i] === modifiedLines[j]) {
      result.push(`<div class="diff-line unchanged">  ${escapeHtml(originalLines[i])}</div>`)
      i++
      j++
    } else {
      result.push(`<div class="diff-line removed">- ${escapeHtml(originalLines[i])}</div>`)
      result.push(`<div class="diff-line added">+ ${escapeHtml(modifiedLines[j])}</div>`)
      stats.modified++
      i++
      j++
    }
  }

  return result.join('')
}

function escapeHtml(text: string): string {
  const div = document.createElement('div')
  div.textContent = text
  return div.innerHTML
}

function handleAcceptAll() {
  emit('accept', props.modifiedContent)
}

function handleAccept() {
  emit('accept', props.modifiedContent)
}

function handleReject() {
  emit('reject')
}

function handleDiscuss() {
  emit('discuss')
}
</script>

<style lang="scss" scoped>
.diff-compare {
  background: var(--el-bg-color);
  border-radius: 8px;
  overflow: hidden;
}

.diff-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: var(--el-fill-color-light);
  border-bottom: 1px solid var(--el-border-color-light);

  h4 {
    margin: 0;
    font-size: 14px;
  }

  .diff-actions {
    display: flex;
    gap: 12px;
    align-items: center;
  }
}

.diff-content {
  &.split-view {
    display: flex;
    height: 400px;

    .diff-pane {
      flex: 1;
      display: flex;
      flex-direction: column;
      overflow: hidden;

      &.original {
        border-right: 1px solid var(--el-border-color-light);
      }

      .pane-header {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        background: var(--el-fill-color);
        border-bottom: 1px solid var(--el-border-color-light);

        .pane-title {
          font-weight: 600;
          font-size: 13px;
        }
      }

      .pane-content {
        flex: 1;
        overflow-y: auto;
        padding: 12px;
        font-family: 'Monaco', 'Menlo', monospace;
        font-size: 13px;
        line-height: 1.6;
        white-space: pre-wrap;
        word-break: break-all;

        :deep(p) {
          margin: 0;
          padding: 2px 0;

          &.empty-content {
            color: var(--el-text-color-placeholder);
            font-style: italic;
          }
        }
      }
    }

    .diff-divider {
      width: 32px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--el-fill-color-light);
      color: var(--el-text-color-secondary);
    }
  }

  &.unified-view {
    .unified-content {
      max-height: 400px;
      overflow-y: auto;
      padding: 12px;
      font-family: 'Monaco', 'Menlo', monospace;
      font-size: 13px;
      line-height: 1.6;

      :deep(.diff-line) {
        margin: 0;
        padding: 2px 8px;
        white-space: pre-wrap;
        word-break: break-all;

        &.added {
          background: var(--el-color-success-light-9);
          color: var(--el-color-success);
        }

        &.removed {
          background: var(--el-color-danger-light-9);
          color: var(--el-color-danger);
        }

        &.unchanged {
          color: var(--el-text-color-regular);
        }
      }
    }
  }
}

.diff-stats {
  display: flex;
  gap: 24px;
  padding: 12px 16px;
  background: var(--el-fill-color-light);
  border-top: 1px solid var(--el-border-color-light);

  .stat-item {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 13px;
    color: var(--el-color-success);

    &.removed {
      color: var(--el-color-danger);
    }

    &.modified {
      color: var(--el-color-warning);
    }
  }
}

.diff-footer {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  padding: 12px 16px;
  border-top: 1px solid var(--el-border-color-light);
}
</style>
