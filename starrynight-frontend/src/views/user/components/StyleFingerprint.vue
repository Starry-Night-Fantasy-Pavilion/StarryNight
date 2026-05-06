<template>
  <div class="style-fingerprint">
    <div class="fingerprint-header">
      <h4>🎨 风格指纹</h4>
      <el-button size="small" link @click="handleRefresh">
        <el-icon><Refresh /></el-icon>
        刷新
      </el-button>
    </div>

    <div class="fingerprint-content">
      <div class="fingerprint-chart">
        <div class="chart-container">
          <div
            v-for="(item, index) in metrics"
            :key="index"
            class="metric-bar"
          >
            <div class="bar-label">{{ item.label }}</div>
            <div class="bar-container">
              <div
                class="bar-fill"
                :style="{
                  width: `${item.value}%`,
                  backgroundColor: getBarColor(item.category)
                }"
              ></div>
              <div class="bar-value">{{ item.displayValue }}</div>
            </div>
          </div>
        </div>
      </div>

      <div class="fingerprint-details">
        <div class="detail-item">
          <span class="detail-label">平均句长</span>
          <span class="detail-value">{{ fingerprint.avgSentenceLength || 0 }} 字</span>
          <el-tooltip content="衡量文本复杂度，越高说明句子越长">
            <el-icon><QuestionFilled /></el-icon>
          </el-tooltip>
        </div>
        <div class="detail-item">
          <span class="detail-label">对话比例</span>
          <span class="detail-value">{{ ((fingerprint.dialogueRatio || 0) * 100).toFixed(1) }}%</span>
          <el-tooltip content="对话占总内容的比例，影响节奏感">
            <el-icon><QuestionFilled /></el-icon>
          </el-tooltip>
        </div>
        <div class="detail-item">
          <span class="detail-label">描写密度</span>
          <span class="detail-value">{{ ((fingerprint.descriptionDensity || 0) * 100).toFixed(1) }}%</span>
          <el-tooltip content="环境/细节描写占总内容的比例">
            <el-icon><QuestionFilled /></el-icon>
          </el-tooltip>
        </div>
        <div class="detail-item">
          <span class="detail-label">节奏类型</span>
          <el-tag size="small" :type="pacingTypeTag">{{ fingerprint.pacingType || '未知' }}</el-tag>
        </div>
      </div>

      <div class="style-comparison" v-if="showComparison">
        <h5>与经典作品对比</h5>
        <div class="comparison-list">
          <div
            v-for="work in comparisonWorks"
            :key="work.name"
            class="comparison-item"
            :class="{ 'is-similar': work.similarity > 0.7 }"
          >
            <span class="work-name">{{ work.name }}</span>
            <div class="similarity-bar">
              <div
                class="bar-fill"
                :style="{
                  width: `${work.similarity * 100}%`,
                  backgroundColor: work.similarity > 0.7 ? 'var(--el-color-success)' : 'var(--el-color-primary)'
                }"
              ></div>
            </div>
            <span class="similarity-value">{{ (work.similarity * 100).toFixed(0) }}%</span>
          </div>
        </div>
      </div>
    </div>

    <div class="fingerprint-suggestions" v-if="suggestions.length">
      <h5>💡 风格优化建议</h5>
      <ul class="suggestion-list">
        <li v-for="(suggestion, index) in suggestions" :key="index">
          {{ suggestion }}
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive } from 'vue'
import { Refresh, QuestionFilled } from '@element-plus/icons-vue'

interface StyleFingerprint {
  avgSentenceLength: number
  dialogueRatio: number
  descriptionDensity: number
  pacingType: string
}

interface ComparisonWork {
  name: string
  similarity: number
}

const props = defineProps<{
  fingerprint: Partial<StyleFingerprint>
  showComparison?: boolean
}>()

const emit = defineEmits<{
  refresh: []
}>()

const suggestions = ref<string[]>([])

const metrics = computed(() => [
  {
    label: '句长指数',
    value: Math.min(100, (props.fingerprint.avgSentenceLength || 0) / 2),
    displayValue: `${props.fingerprint.avgSentenceLength || 0}字`,
    category: 'sentence'
  },
  {
    label: '对话占比',
    value: ((props.fingerprint.dialogueRatio || 0) * 100),
    displayValue: `${((props.fingerprint.dialogueRatio || 0) * 100).toFixed(1)}%`,
    category: 'dialogue'
  },
  {
    label: '描写密度',
    value: ((props.fingerprint.descriptionDensity || 0) * 100),
    displayValue: `${((props.fingerprint.descriptionDensity || 0) * 100).toFixed(1)}%`,
    category: 'description'
  },
  {
    label: '节奏评分',
    value: 75,
    displayValue: '良好',
    category: 'pacing'
  }
])

const pacingTypeTag = computed(() => {
  const type = props.fingerprint.pacingType
  if (type === '快节奏') return 'danger'
  if (type === '慢节奏') return 'success'
  return 'primary'
})

const comparisonWorks = reactive<ComparisonWork[]>([
  { name: '《斗破苍穹》', similarity: 0.82 },
  { name: '《完美世界》', similarity: 0.75 },
  { name: '《凡人修仙传》', similarity: 0.68 }
])

function getBarColor(category: string): string {
  const colors: Record<string, string> = {
    sentence: 'var(--el-color-primary)',
    dialogue: 'var(--el-color-success)',
    description: 'var(--el-color-warning)',
    pacing: 'var(--el-color-info)'
  }
  return colors[category] || 'var(--el-color-primary)'
}

function handleRefresh() {
  emit('refresh')
}
</script>

<style lang="scss" scoped>
.style-fingerprint {
  background: var(--el-bg-color);
  border-radius: 8px;
  padding: 16px;
}

.fingerprint-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;

  h4 {
    margin: 0;
    font-size: 14px;
  }
}

.fingerprint-content {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.fingerprint-chart {
  .chart-container {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  .metric-bar {
    display: flex;
    align-items: center;
    gap: 12px;

    .bar-label {
      width: 80px;
      font-size: 13px;
      color: var(--el-text-color-secondary);
    }

    .bar-container {
      flex: 1;
      display: flex;
      align-items: center;
      gap: 8px;

      .bar-fill {
        height: 16px;
        border-radius: 8px;
        transition: width 0.3s ease;
        min-width: 4px;
      }

      .bar-value {
        width: 50px;
        font-size: 12px;
        color: var(--el-text-color-regular);
        text-align: right;
      }
    }
  }
}

.fingerprint-details {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
  padding: 12px;
  background: var(--el-fill-color-light);
  border-radius: 8px;

  .detail-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;

    .detail-label {
      color: var(--el-text-color-secondary);
    }

    .detail-value {
      font-weight: 600;
      color: var(--el-text-color-primary);
    }
  }
}

.style-comparison {
  h5 {
    margin: 0 0 12px;
    font-size: 13px;
  }

  .comparison-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .comparison-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 12px;
    background: var(--el-fill-color-light);
    border-radius: 4px;

    &.is-similar {
      background: var(--el-color-success-light-9);
    }

    .work-name {
      width: 120px;
      font-size: 13px;
    }

    .similarity-bar {
      flex: 1;
      height: 8px;
      background: var(--el-fill-color);
      border-radius: 4px;
      overflow: hidden;

      .bar-fill {
        height: 100%;
        border-radius: 4px;
        transition: width 0.3s ease;
      }
    }

    .similarity-value {
      width: 40px;
      font-size: 12px;
      color: var(--el-text-color-secondary);
      text-align: right;
    }
  }
}

.fingerprint-suggestions {
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid var(--el-border-color-light);

  h5 {
    margin: 0 0 12px;
    font-size: 13px;
  }

  .suggestion-list {
    margin: 0;
    padding-left: 20px;
    font-size: 13px;
    color: var(--el-text-color-regular);

    li {
      margin-bottom: 6px;
      line-height: 1.5;

      &:last-child {
        margin-bottom: 0;
      }
    }
  }
}
</style>
