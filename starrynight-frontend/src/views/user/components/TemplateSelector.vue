<template>
  <div class="template-selector">
    <div class="selector-header">
      <h4>📋 选择创作模板</h4>
      <el-input
        v-model="searchKeyword"
        placeholder="搜索模板..."
        size="small"
        :prefix-icon="Search"
        clearable
      />
    </div>

    <div class="template-categories">
      <el-radio-group v-model="selectedCategory" size="small">
        <el-radio-button label="all">全部</el-radio-button>
        <el-radio-button label="story">小说</el-radio-button>
        <el-radio-button label="screenplay">剧本</el-radio-button>
        <el-radio-button label="custom">自定义</el-radio-button>
      </el-radio-group>
    </div>

    <div class="template-grid">
      <div
        v-for="template in filteredTemplates"
        :key="template.id"
        class="template-card"
        :class="{ 'is-selected': selectedTemplate?.id === template.id }"
        @click="handleSelect(template)"
      >
        <div class="template-icon" :style="{ backgroundColor: template.color }">
          <el-icon :size="32"><component :is="template.icon" /></el-icon>
        </div>
        <div class="template-info">
          <h5 class="template-name">{{ template.name }}</h5>
          <p class="template-desc">{{ template.description }}</p>
          <div class="template-meta">
            <el-tag size="small" type="info">{{ template.chapterCount }}章</el-tag>
            <el-tag size="small" type="info">{{ template.wordCount }}万字</el-tag>
          </div>
        </div>
        <div v-if="selectedTemplate?.id === template.id" class="selected-indicator">
          <el-icon><Check /></el-icon>
        </div>
      </div>
    </div>

    <div v-if="selectedTemplate" class="template-preview">
      <h5>模板详情：{{ selectedTemplate.name }}</h5>
      <div class="preview-content">
        <div class="preview-section">
          <h6>结构特点</h6>
          <ul>
            <li v-for="(feature, index) in selectedTemplate.features" :key="index">
              {{ feature }}
            </li>
          </ul>
        </div>
        <div class="preview-section" v-if="selectedTemplate.preview">
          <h6>章节预览</h6>
          <div class="chapter-preview">
            <div
              v-for="(chapter, index) in selectedTemplate.preview"
              :key="index"
              class="chapter-item"
            >
              <span class="chapter-num">第{{ chapter.no }}章</span>
              <span class="chapter-title">{{ chapter.title }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="selector-footer">
      <el-button @click="handleCancel">取消</el-button>
      <el-button type="primary" :disabled="!selectedTemplate" @click="handleConfirm">
        应用模板
      </el-button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive } from 'vue'
import { Check, Search, Document, Film, Edit, Collection, Clock, Star } from '@element-plus/icons-vue'

interface TemplateChapter {
  no: number
  title: string
}

interface Template {
  id: string
  name: string
  description: string
  category: string
  icon: string
  color: string
  chapterCount: number
  wordCount: number
  features: string[]
  preview?: TemplateChapter[]
}

const props = defineProps<{
  modelValue?: Template | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: Template | null]
  confirm: [template: Template]
  cancel: []
}>()

const searchKeyword = ref('')
const selectedCategory = ref('all')

const templates = reactive<Template[]>([
  {
    id: 'three-act',
    name: '黄金三幕式',
    description: '经典叙事结构，适合大多数长篇小说',
    category: 'story',
    icon: 'Film',
    color: '#409EFF',
    chapterCount: 100,
    wordCount: 50,
    features: [
      '第一幕（1-10章）：建置，介绍主角和世界观',
      '第二幕（11-80章）：对抗，主角面临挑战',
      '第三幕（81-100章）：解决，冲突得到解决'
    ],
    preview: [
      { no: 1, title: '开场事件' },
      { no: 10, title: '第一转折点' },
      { no: 50, title: '中点转折' },
      { no: 75, title: '第二转折点' },
      { no: 100, title: '结局' }
    ]
  },
  {
    id: 'hero-journey',
    name: '英雄之旅',
    description: '神话叙事模式，适合奇幻、冒险题材',
    category: 'story',
    icon: 'Star',
    color: '#E6A23C',
    chapterCount: 80,
    wordCount: 40,
    features: [
      '普通世界 → 冒险召唤 → 拒绝召唤',
      '遇到导师 → 跨越门槛 → 考验、盟友、敌人',
      '接近深渊 → 磨难 → 回报 → 返回之路'
    ]
  },
  {
    id: 'five-act',
    name: '五幕结构',
    description: '莎士比亚式结构，适合戏剧性强的小说',
    category: 'story',
    icon: 'Edit',
    color: '#F56C6C',
    chapterCount: 60,
    wordCount: 30,
    features: [
      '第一幕：介绍（1-10章）',
      '第二幕：上升动作（11-25章）',
      '第三幕：高point（26-35章）',
      '第四幕：下降动作（36-50章）',
      '第五幕：危机与结局（51-60章）'
    ]
  },
  {
    id: 'serial',
    name: '单元连续剧',
    description: '每章相对独立但有主线贯穿，适合连载作品',
    category: 'story',
    icon: 'Clock',
    color: '#67C23A',
    chapterCount: 200,
    wordCount: 100,
    features: [
      '主线剧情贯穿始终',
      '每10-15章一个中等单元',
      '每3-5章一个小point',
      '适合超长篇连载'
    ]
  },
  {
    id: 'screenplay-classic',
    name: '经典剧本式',
    description: '三幕结构，适合改编影视的作品',
    category: 'screenplay',
    icon: 'Film',
    color: '#909399',
    chapterCount: 40,
    wordCount: 20,
    features: [
      '建置（25%）：引入人物和情境',
      '对抗（50%）：核心冲突展开',
      '解决（25%）：冲突解决'
    ]
  },
  {
    id: 'custom',
    name: '自定义模板',
    description: '从空白开始，自由定义结构',
    category: 'custom',
    icon: 'Collection',
    color: '#8E44AD',
    chapterCount: 0,
    wordCount: 0,
    features: [
      '完全自由的结构设计',
      '可以导入现有大纲',
      '支持AI辅助规划'
    ]
  }
])

const selectedTemplate = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
})

const filteredTemplates = computed(() => {
  let result = templates

  if (selectedCategory.value !== 'all') {
    result = result.filter(t => t.category === selectedCategory.value)
  }

  if (searchKeyword.value) {
    const keyword = searchKeyword.value.toLowerCase()
    result = result.filter(t =>
      t.name.toLowerCase().includes(keyword) ||
      t.description.toLowerCase().includes(keyword)
    )
  }

  return result
})

function handleSelect(template: Template) {
  selectedTemplate.value = template
}

function handleConfirm() {
  if (selectedTemplate.value) {
    emit('confirm', selectedTemplate.value)
  }
}

function handleCancel() {
  emit('cancel')
}
</script>

<style lang="scss" scoped>
.template-selector {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.selector-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;

  h4 {
    margin: 0;
    font-size: 14px;
  }

  .el-input {
    width: 200px;
  }
}

.template-categories {
  margin-bottom: 16px;
}

.template-grid {
  flex: 1;
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
  overflow-y: auto;
  margin-bottom: 16px;
}

.template-card {
  position: relative;
  display: flex;
  gap: 12px;
  padding: 12px;
  border: 1px solid var(--el-border-color-light);
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;

  &:hover {
    border-color: var(--el-color-primary);
    background: var(--el-color-primary-light-9);
  }

  &.is-selected {
    border-color: var(--el-color-primary);
    background: var(--el-color-primary-light-9);
  }

  .template-icon {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    flex-shrink: 0;
  }

  .template-info {
    flex: 1;
    min-width: 0;

    .template-name {
      margin: 0 0 4px;
      font-size: 14px;
      font-weight: 600;
    }

    .template-desc {
      margin: 0 0 8px;
      font-size: 12px;
      color: var(--el-text-color-secondary);
      line-height: 1.4;
    }

    .template-meta {
      display: flex;
      gap: 6px;
    }
  }

  .selected-indicator {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: var(--el-color-primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
  }
}

.template-preview {
  padding: 16px;
  background: var(--el-fill-color-light);
  border-radius: 8px;
  margin-bottom: 16px;

  h5 {
    margin: 0 0 12px;
    font-size: 13px;
  }

  .preview-content {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  .preview-section {
    h6 {
      margin: 0 0 8px;
      font-size: 12px;
      color: var(--el-text-color-secondary);
    }

    ul {
      margin: 0;
      padding-left: 20px;
      font-size: 13px;

      li {
        margin-bottom: 4px;
      }
    }
  }

  .chapter-preview {
    display: flex;
    flex-direction: column;
    gap: 4px;

    .chapter-item {
      display: flex;
      gap: 8px;
      font-size: 13px;

      .chapter-num {
        color: var(--el-text-color-secondary);
        flex-shrink: 0;
      }

      .chapter-title {
        color: var(--el-text-color-regular);
      }
    }
  }
}

.selector-footer {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  padding-top: 16px;
  border-top: 1px solid var(--el-border-color-light);
}
</style>
