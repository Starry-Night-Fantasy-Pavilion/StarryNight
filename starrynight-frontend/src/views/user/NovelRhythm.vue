<template>
  <div class="novel-rhythm page-container">
    <div class="page-header">
      <h2>📊 叙事节奏仪表盘</h2>
      <div class="header-actions">
        <el-button @click="handleAnalyze">
          <el-icon><DataAnalysis /></el-icon>
          分析节奏
        </el-button>
        <el-button type="primary" @click="handleAiOptimize">
          <el-icon><MagicStick /></el-icon>
          AI优化建议
        </el-button>
      </div>
    </div>

    <div class="page-content">
      <div class="rhythm-overview">
        <el-row :gutter="16">
          <el-col :span="6">
            <div class="metric-card">
              <div class="metric-icon">
                <el-icon :size="24"><Clock /></el-icon>
              </div>
              <div class="metric-info">
                <span class="metric-value">{{ avgPacing.toFixed(1) }}</span>
                <span class="metric-label">平均节奏值</span>
              </div>
            </div>
          </el-col>
          <el-col :span="6">
            <div class="metric-card">
              <div class="metric-icon warning">
                <el-icon :size="24"><Warning /></el-icon>
              </div>
              <div class="metric-info">
                <span class="metric-value">{{ slowPacingCount }}</span>
                <span class="metric-label">节奏过慢章节</span>
              </div>
            </div>
          </el-col>
          <el-col :span="6">
            <div class="metric-card">
              <div class="metric-icon success">
                <el-icon :size="24"><TrendCharts /></el-icon>
              </div>
              <div class="metric-info">
                <span class="metric-value">{{ fastPacingCount }}</span>
                <span class="metric-label">节奏过快章节</span>
              </div>
            </div>
          </el-col>
          <el-col :span="6">
            <div class="metric-card">
              <div class="metric-icon info">
                <el-icon :size="24"><MagicStick /></el-icon>
              </div>
              <div class="metric-info">
                <span class="metric-value">{{ aiSuggestionCount }}</span>
                <span class="metric-label">AI优化建议</span>
              </div>
            </div>
          </el-col>
        </el-row>
      </div>

      <div class="rhythm-chart">
        <h3>章节节奏曲线</h3>
        <div class="chart-container">
          <v-chart :option="chartOption" autoresize />
        </div>
      </div>

      <div class="rhythm-analysis">
        <h3>节奏问题分析</h3>
        <div class="analysis-list">
          <el-alert
            v-for="issue in issues"
            :key="issue.chapterId"
            :title="issue.title"
            :type="issue.type"
            :description="issue.description"
            show-icon
          >
            <template #default>
              <div class="issue-detail">
                <span>影响范围：第{{ issue.chapterNo }}章</span>
                <el-button size="small" type="primary" link @click="handleGoToChapter(issue.chapterId)">
                  查看详情
                </el-button>
              </div>
            </template>
          </el-alert>
        </div>
      </div>

      <div class="ai-suggestions">
        <h3>💡 AI优化建议</h3>
        <div class="suggestions-list">
          <div
            v-for="suggestion in suggestions"
            :key="suggestion.id"
            class="suggestion-card"
          >
            <div class="suggestion-header">
              <span class="suggestion-type">{{ suggestion.type }}</span>
              <el-tag size="small" :type="suggestion.priority === 'high' ? 'danger' : 'info'">
                {{ suggestion.priority === 'high' ? '高优' : '建议' }}
              </el-tag>
            </div>
            <p class="suggestion-content">{{ suggestion.content }}</p>
            <div class="suggestion-actions">
              <el-button size="small" type="primary" @click="handleApplySuggestion(suggestion)">
                应用建议
              </el-button>
              <el-button size="small" @click="handleIgnoreSuggestion(suggestion)">
                忽略
              </el-button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { DataAnalysis, Clock, Warning, TrendCharts, MagicStick } from '@element-plus/icons-vue'
import type { EChartsOption } from 'echarts'

const route = useRoute()
const router = useRouter()
const novelId = computed(() => route.params.id as string)

const avgPacing = ref(6.5)
const slowPacingCount = ref(3)
const fastPacingCount = ref(2)
const aiSuggestionCount = ref(5)

const issues = reactive([
  {
    chapterId: 1,
    chapterNo: 5,
    title: '第5章节奏过慢',
    type: 'warning',
    description: '该章节描写过于冗长，建议精简场景描述'
  },
  {
    chapterId: 2,
    chapterNo: 12,
    title: '第12章信息密度不足',
    type: 'info',
    description: '该章节内容较少，建议增加一些互动或冲突'
  }
])

const suggestions = reactive([
  {
    id: 1,
    type: '节奏调整',
    priority: 'high',
    content: '建议在第8-10章增加一个小的冲突事件，可以提升读者的紧张感'
  },
  {
    id: 2,
    type: '情感曲线',
    priority: 'medium',
    content: '第15章的情感高潮可以提前，让读者更早进入紧张状态'
  }
])

const chartOption = computed<EChartsOption>(() => ({
  tooltip: {
    trigger: 'axis',
    formatter: (params: any) => {
      const p = params[0]
      return `第${p.dataIndex + 1}章<br/>节奏值: ${p.value}`
    }
  },
  xAxis: {
    type: 'category',
    data: Array.from({ length: 20 }, (_, i) => `第${i + 1}章`),
    axisLabel: { interval: 4 }
  },
  yAxis: {
    type: 'value',
    name: '节奏值',
    min: 0,
    max: 10
  },
  series: [
    {
      name: '节奏值',
      type: 'line',
      smooth: true,
      data: [5, 6, 7, 5, 8, 6, 7, 5, 9, 7, 6, 5, 8, 6, 9, 7, 6, 5, 7, 8],
      areaStyle: {
        color: {
          type: 'linear',
          x: 0, y: 0, x2: 0, y2: 1,
          colorStops: [
            { offset: 0, color: 'rgba(64, 158, 255, 0.3)' },
            { offset: 1, color: 'rgba(64, 158, 255, 0.05)' }
          ]
        }
      },
      lineStyle: {
        color: '#409EFF'
      },
      itemStyle: {
        color: '#409EFF'
      },
      markLine: {
        silent: true,
        lineStyle: { color: '#67C23A', type: 'dashed' },
        data: [{ yAxis: 6.5, name: '平均节奏' }]
      }
    }
  ],
  grid: {
    left: '3%',
    right: '4%',
    bottom: '3%',
    containLabel: true
  }
}))

function handleAnalyze() {
  console.log('Analyze rhythm')
}

function handleAiOptimize() {
  console.log('AI optimize')
}

function handleGoToChapter(chapterId: number) {
  router.push(`/novel/${novelId.value}/chapters?highlight=${chapterId}`)
}

function handleApplySuggestion(suggestion: any) {
  console.log('Apply suggestion:', suggestion)
}

function handleIgnoreSuggestion(suggestion: any) {
  const index = suggestions.findIndex(s => s.id === suggestion.id)
  if (index !== -1) {
    suggestions.splice(index, 1)
  }
}
</script>

<style lang="scss" scoped>
.novel-rhythm {
  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;

    h2 {
      margin: 0;
      font-size: 20px;
    }

    .header-actions {
      display: flex;
      gap: 12px;
    }
  }

  .rhythm-overview {
    margin-bottom: 24px;
  }

  .metric-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px;
    background: var(--el-bg-color);
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);

    .metric-icon {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--el-color-primary-light-9);
      color: var(--el-color-primary);

      &.warning {
        background: var(--el-color-warning-light-9);
        color: var(--el-color-warning);
      }

      &.success {
        background: var(--el-color-success-light-9);
        color: var(--el-color-success);
      }

      &.info {
        background: var(--el-color-info-light-9);
        color: var(--el-color-info);
      }
    }

    .metric-info {
      display: flex;
      flex-direction: column;

      .metric-value {
        font-size: 24px;
        font-weight: 700;
        color: var(--el-text-color-primary);
      }

      .metric-label {
        font-size: 13px;
        color: var(--el-text-color-secondary);
      }
    }
  }

  .rhythm-chart {
    background: var(--el-bg-color);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 24px;

    h3 {
      margin: 0 0 16px;
      font-size: 16px;
    }

    .chart-container {
      height: 300px;
    }
  }

  .rhythm-analysis {
    background: var(--el-bg-color);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 24px;

    h3 {
      margin: 0 0 16px;
      font-size: 16px;
    }

    .analysis-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .issue-detail {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 8px;
    }
  }

  .ai-suggestions {
    background: var(--el-bg-color);
    border-radius: 12px;
    padding: 20px;

    h3 {
      margin: 0 0 16px;
      font-size: 16px;
    }

    .suggestions-list {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 16px;
    }

    .suggestion-card {
      padding: 16px;
      border: 1px solid var(--el-border-color-light);
      border-radius: 8px;

      .suggestion-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;

        .suggestion-type {
          font-weight: 600;
          font-size: 14px;
        }
      }

      .suggestion-content {
        margin: 0 0 12px;
        font-size: 13px;
        color: var(--el-text-color-regular);
        line-height: 1.6;
      }

      .suggestion-actions {
        display: flex;
        gap: 8px;
      }
    }
  }
}
</style>
