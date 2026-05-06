<template>
  <el-card
    class="material-card"
    :class="{ 'is-compact': compact }"
    shadow="hover"
    @click="handleClick"
  >
    <div class="material-header">
      <div class="material-icon" :style="{ backgroundColor: typeColor }">
        <el-icon :size="compact ? 20 : 24"><component :is="typeIcon" /></el-icon>
      </div>
      <div class="material-basic">
        <h3 class="material-title">{{ material.title }}</h3>
        <el-tag v-if="!compact" size="small" :type="typeTagType">{{ typeText }}</el-tag>
      </div>
    </div>

    <div v-if="!compact" class="material-body">
      <p v-if="material.description" class="material-description">
        {{ truncatedDescription }}
      </p>

      <div v-if="material.tags?.length" class="material-tags">
        <el-tag
          v-for="tag in material.tags.slice(0, 3)"
          :key="tag"
          size="small"
          effect="plain"
        >
          {{ tag }}
        </el-tag>
        <span v-if="material.tags.length > 3" class="more-tags">
          +{{ material.tags.length - 3 }}
        </span>
      </div>

      <div class="material-stats">
        <span class="usage-count">
          <el-icon><Connection /></el-icon>
          使用 {{ material.usageCount || 0 }} 次
        </span>
        <span class="update-time">{{ formatTime(material.updateTime) }}</span>
      </div>
    </div>

    <div v-if="showActions" class="material-actions" @click.stop>
      <el-button size="small" type="primary" @click="handleUse">
        <el-icon><Promotion /></el-icon>
        使用
      </el-button>
      <el-button size="small" @click="handleEdit">
        <el-icon><Edit /></el-icon>
      </el-button>
      <el-dropdown trigger="click" @command="handleCommand">
        <el-button size="small">
          <el-icon><MoreFilled /></el-icon>
        </el-button>
        <template #dropdown>
          <el-dropdown-menu>
            <el-dropdown-item command="view">查看</el-dropdown-item>
            <el-dropdown-item command="edit">编辑</el-dropdown-item>
            <el-dropdown-item command="copy">复制</el-dropdown-item>
            <el-dropdown-item command="favorite">收藏</el-dropdown-item>
            <el-dropdown-item command="delete" divided style="color: var(--el-color-danger)">删除</el-dropdown-item>
          </el-dropdown-menu>
        </template>
      </el-dropdown>
    </div>
  </el-card>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import {
  Document,
  Picture,
  Video,
  Headset,
  Edit,
  MoreFilled,
  Promotion,
  Connection,
  Clock,
  Collection,
  Folder
} from '@element-plus/icons-vue'
import type { MaterialItem } from '@/api/material'

interface Props {
  material: MaterialItem
  compact?: boolean
  showActions?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  compact: false,
  showActions: true
})

const emit = defineEmits<{
  click: [material: MaterialItem]
  use: [material: MaterialItem]
  edit: [material: MaterialItem]
  command: [command: string, material: MaterialItem]
}>()

const typeMap: Record<string, { text: string; icon: string; color: string; tagType: string }> = {
  scene: { text: '场景', icon: 'Picture', color: '#67C23A', tagType: 'success' },
  dialogue: { text: '对白', icon: 'Headset', color: '#409EFF', tagType: 'primary' },
  description: { text: '描写', icon: 'Document', color: '#E6A23C', tagType: 'warning' },
  action: { text: '动作', icon: 'Promotion', color: '#F56C6C', tagType: 'danger' },
  plot: { text: '情节', icon: 'Clock', color: '#909399', tagType: 'info' },
  setting: { text: '设定', icon: 'Folder', color: '#8E44AD', tagType: '' },
  default: { text: '素材', icon: 'Collection', color: '#606266', tagType: 'info' }
}

const typeInfo = computed(() => typeMap[props.material.type] || typeMap.default)
const typeText = computed(() => typeInfo.value.text)
const typeIcon = computed(() => (typeInfo.value.icon as any) || 'Collection')
const typeColor = computed(() => typeInfo.value.color)
const typeTagType = computed(() => typeInfo.value.tagType as any)

const truncatedDescription = computed(() => {
  if (!props.material.description) return ''
  return props.material.description.length > 100
    ? props.material.description.substring(0, 100) + '...'
    : props.material.description
})

function formatTime(time?: string): string {
  if (!time) return ''
  const date = new Date(time)
  return date.toLocaleDateString()
}

function handleClick() {
  emit('click', props.material)
}

function handleUse() {
  emit('use', props.material)
}

function handleEdit() {
  emit('edit', props.material)
}

function handleCommand(command: string) {
  emit('command', command, props.material)
}
</script>

<style lang="scss" scoped>
.material-card {
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;

  &:hover {
    transform: translateY(-2px);
  }

  &.is-compact {
    :deep(.el-card__body) {
      padding: 12px;
    }

    .material-icon {
      width: 36px;
      height: 36px;
    }

    .material-title {
      font-size: 14px;
    }
  }
}

.material-header {
  display: flex;
  align-items: center;
  gap: 12px;

  .material-icon {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    flex-shrink: 0;
  }

  .material-basic {
    flex: 1;
    min-width: 0;

    .material-title {
      font-size: 16px;
      font-weight: 600;
      margin: 0 0 4px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
  }
}

.material-body {
  padding: 12px 0 0;

  .material-description {
    font-size: 13px;
    color: var(--el-text-color-secondary);
    margin: 0 0 12px;
    line-height: 1.6;
  }

  .material-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 12px;

    .more-tags {
      font-size: 11px;
      color: var(--el-text-color-placeholder);
      line-height: 24px;
    }
  }

  .material-stats {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 12px;
    color: var(--el-text-color-secondary);

    .usage-count {
      display: flex;
      align-items: center;
      gap: 4px;
      color: var(--el-color-primary);
    }
  }
}

.material-actions {
  display: flex;
  gap: 8px;
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px dashed var(--el-border-color-lighter);
}
</style>
