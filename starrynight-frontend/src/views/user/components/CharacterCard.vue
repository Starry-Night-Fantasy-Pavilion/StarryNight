<template>
  <el-card
    class="character-card"
    :class="{ 'is-compact': compact }"
    shadow="hover"
    @click="handleClick"
  >
    <div class="character-header">
      <el-avatar
        v-if="character.appearance"
        :size="compact ? 40 : 60"
        class="character-avatar"
        :src="character.appearance"
      >
        {{ character.name?.charAt(0) }}
      </el-avatar>
      <el-avatar v-else :size="compact ? 40 : 60" class="character-avatar" :icon="UserFilled" />
      <div class="character-basic">
        <h3 class="character-name">{{ character.name }}</h3>
        <p v-if="!compact" class="character-identity">{{ character.identity || '未设置身份' }}</p>
        <p v-if="!compact && character.gender" class="character-gender">
          <el-tag size="small">{{ character.gender }}</el-tag>
          <span v-if="character.age">{{ character.age }}</span>
        </p>
      </div>
    </div>

    <div v-if="!compact" class="character-body">
      <div v-if="character.personality?.traits?.length" class="character-trait">
        <span class="trait-label">性格标签：</span>
        <el-tag
          v-for="trait in character.personality.traits.slice(0, 3)"
          :key="trait"
          size="small"
          type="info"
        >
          {{ trait }}
        </el-tag>
        <span v-if="character.personality.traits.length > 3" class="more-trait">
          +{{ character.personality.traits.length - 3 }}
        </span>
      </div>

      <div v-if="character.abilities?.skills?.length" class="character-abilities">
        <span class="abilities-label">能力：</span>
        <el-tag
          v-for="skill in character.abilities.skills.slice(0, 2)"
          :key="skill"
          size="small"
          type="warning"
        >
          {{ skill }}
        </el-tag>
        <span v-if="character.abilities.skills.length > 2" class="more-abilities">
          +{{ character.abilities.skills.length - 2 }}
        </span>
      </div>

      <div v-if="character.background" class="character-background">
        <span class="background-label">背景：</span>
        <span class="background-text">{{ truncatedBackground }}</span>
      </div>
    </div>

    <div v-if="showActions" class="character-actions" @click.stop>
      <el-button size="small" type="primary" @click="handleEdit">
        <el-icon><Edit /></el-icon>
      </el-button>
      <el-button size="small" @click="handleView">
        <el-icon><View /></el-icon>
      </el-button>
      <el-dropdown trigger="click" @command="handleCommand">
        <el-button size="small">
          <el-icon><MoreFilled /></el-icon>
        </el-button>
        <template #dropdown>
          <el-dropdown-menu>
            <el-dropdown-item command="view">查看详情</el-dropdown-item>
            <el-dropdown-item command="edit">编辑</el-dropdown-item>
            <el-dropdown-item command="duplicate">复制</el-dropdown-item>
            <el-dropdown-item command="relationship">查看关系</el-dropdown-item>
            <el-dropdown-item command="delete" divided style="color: var(--el-color-danger)">删除</el-dropdown-item>
          </el-dropdown-menu>
        </template>
      </el-dropdown>
    </div>
  </el-card>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { UserFilled, Edit, View, MoreFilled } from '@element-plus/icons-vue'
import type { NovelCharacter } from '@/api/character'

interface Props {
  character: NovelCharacter
  compact?: boolean
  showActions?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  compact: false,
  showActions: true
})

const emit = defineEmits<{
  click: [character: NovelCharacter]
  edit: [character: NovelCharacter]
  view: [character: NovelCharacter]
  command: [command: string, character: NovelCharacter]
}>()

const truncatedBackground = computed(() => {
  if (!props.character.background) return ''
  return props.character.background.length > 80
    ? props.character.background.substring(0, 80) + '...'
    : props.character.background
})

function handleClick() {
  emit('click', props.character)
}

function handleEdit() {
  emit('edit', props.character)
}

function handleView() {
  emit('view', props.character)
}

function handleCommand(command: string) {
  emit('command', command, props.character)
}
</script>

<style lang="scss" scoped>
.character-card {
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;

  &:hover {
    transform: translateY(-2px);
  }

  &.is-compact {
    :deep(.el-card__body) {
      padding: 12px;
    }

    .character-header {
      padding: 0;
    }

    .character-name {
      font-size: 14px;
    }
  }
}

.character-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 0;

  .character-avatar {
    flex-shrink: 0;
    background: var(--el-color-primary-light-5);
    color: var(--el-color-primary-dark-2);
  }

  .character-basic {
    flex: 1;
    min-width: 0;

    .character-name {
      font-size: 16px;
      font-weight: 600;
      margin: 0;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .character-identity {
      font-size: 12px;
      color: var(--el-text-color-secondary);
      margin: 4px 0 0;
    }

    .character-gender {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 12px;
      color: var(--el-text-color-secondary);
      margin: 4px 0 0;
    }
  }
}

.character-body {
  padding: 12px 0 0;
  border-top: 1px solid var(--el-border-color-lighter);

  .character-trait,
  .character-abilities,
  .character-background {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    margin-bottom: 8px;
    font-size: 12px;

    .trait-label,
    .abilities-label,
    .background-label {
      flex-shrink: 0;
      color: var(--el-text-color-secondary);
    }

    .background-text {
      color: var(--el-text-color-regular);
      line-height: 1.5;
    }

    .more-trait,
    .more-abilities {
      color: var(--el-text-color-placeholder);
      font-size: 11px;
    }
  }
}

.character-actions {
  display: flex;
  gap: 8px;
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px dashed var(--el-border-color-lighter);
}
</style>
