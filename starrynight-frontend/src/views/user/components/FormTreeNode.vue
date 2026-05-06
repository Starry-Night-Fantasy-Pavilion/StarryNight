<template>
  <div class="form-tree-node">
    <div class="form-node" :class="{ 'has-children': form.childFormIds?.length }">
      <div class="node-header">
        <span class="form-name">{{ form.name }}</span>
        <div class="node-actions">
          <el-button size="small" text @click="$emit('edit', form)">
            <el-icon><Edit /></el-icon>
          </el-button>
          <el-button size="small" text type="danger" @click="$emit('delete', form)">
            <el-icon><Delete /></el-icon>
          </el-button>
        </div>
      </div>
      <div class="node-stats">
        <span class="stat">⚔️ {{ form.abilityVector?.power || 0 }}</span>
        <span class="stat">💨 {{ form.abilityVector?.speed || 0 }}</span>
        <span class="stat" v-if="form.evolutionConditions?.deviceRequired">
          🔑 {{ form.evolutionConditions.deviceRequired }}
        </span>
      </div>
      <div class="node-abilities" v-if="form.abilityVector?.specialAbilities?.length">
        <el-tag v-for="ability in form.abilityVector.specialAbilities" :key="ability" size="small" type="warning">
          {{ ability }}
        </el-tag>
      </div>
      <div class="node-weaknesses" v-if="form.abilityVector?.weaknesses?.length">
        <el-tag v-for="weakness in form.abilityVector.weaknesses" :key="weakness" size="small" type="danger">
          {{ weakness }}
        </el-tag>
      </div>
    </div>
    <div v-if="childForms.length > 0" class="child-nodes">
      <el-divider content-position="left">
        <span class="evolution-label">↓ 进化形态</span>
      </el-divider>
      <div class="children-grid">
        <FormTreeNode
          v-for="child in childForms"
          :key="child.id"
          :form="child"
          :all-forms="allForms"
          @edit="$emit('edit', $event)"
          @delete="$emit('delete', $event)"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Edit, Delete } from '@element-plus/icons-vue'
import type { Form } from '@/api/tokusatsu'

interface Props {
  form: Form
  allForms: Form[]
}

const props = defineProps<Props>()

defineEmits<{
  edit: [form: Form]
  delete: [form: Form]
}>()

const childForms = computed(() =>
  props.allForms.filter(f => f.parentFormId === props.form.id)
)
</script>

<style lang="scss" scoped>
.form-tree-node {
  .form-node {
    width: 200px;
    padding: 12px;
    border: 2px solid var(--el-border-color);
    border-radius: 8px;
    background: var(--el-fill-color-lighter);
    transition: all 0.2s;

    &:hover {
      border-color: var(--el-color-primary);
      box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
    }

    &.has-children {
      border-color: var(--el-color-primary-light-5);
    }

    .node-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 8px;

      .form-name {
        font-weight: 600;
        font-size: 14px;
      }

      .node-actions {
        display: flex;
        gap: 4px;
      }
    }

    .node-stats {
      display: flex;
      gap: 8px;
      font-size: 12px;
      color: var(--el-text-color-secondary);
      margin-bottom: 8px;

      .stat {
        padding: 2px 6px;
        background: var(--el-fill-color);
        border-radius: 4px;
      }
    }

    .node-abilities,
    .node-weaknesses {
      display: flex;
      flex-wrap: wrap;
      gap: 4px;
      margin-top: 8px;
    }
  }

  .child-nodes {
    margin-top: 12px;
    margin-left: 24px;

    .evolution-label {
      font-size: 12px;
      color: var(--el-text-color-secondary);
    }

    .children-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 16px;
      margin-top: 8px;
    }
  }
}
</style>
