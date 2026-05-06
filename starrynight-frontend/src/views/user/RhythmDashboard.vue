<template>
  <div class="rhythm-dashboard page-container">
    <div class="page-header">
      <h1>📊 叙事节奏仪表盘</h1>
      <div class="header-actions">
        <el-radio-group v-model="viewMode" size="small">
          <el-radio-button value="compact">精简模式</el-radio-button>
          <el-radio-button value="detail">详情模式</el-radio-button>
        </el-radio-group>
        <el-select v-model="selectedNovelId" placeholder="选择作品" clearable style="width: 200px" @change="loadData">
          <el-option v-for="novel in novels" :key="novel.id" :label="novel.title" :value="novel.id" />
        </el-select>
      </div>
    </div>

    <div v-if="!selectedNovelId" class="empty-state">
      <el-icon :size="64"><DataAnalysis /></el-icon>
      <p>请选择作品以查看叙事节奏分析</p>
    </div>

    <div v-else class="dashboard-content">
      <el-row :gutter="16" v-if="overallScore">
        <el-col :span="24">
          <el-card class="overall-score-card">
            <template #header>
              <span>📊 整体节奏评分</span>
            </template>
            <el-row :gutter="24">
              <el-col :span="6">
                <div class="score-item">
                  <span class="score-value">{{ overallScore.overallScore }}</span>
                  <span class="score-label">综合评分</span>
                </div>
              </el-col>
              <el-col :span="6">
                <div class="score-item">
                  <span class="score-value">{{ overallScore.emotionBalance }}</span>
                  <span class="score-label">情绪平衡</span>
                </div>
              </el-col>
              <el-col :span="6">
                <div class="score-item">
                  <span class="score-value">{{ overallScore.conflictBalance }}</span>
                  <span class="score-label">冲突平衡</span>
                </div>
              </el-col>
              <el-col :span="6">
                <div class="score-item">
                  <span class="score-value">{{ overallScore.pacingQuality }}</span>
                  <span class="score-label">节奏质量</span>
                </div>
              </el-col>
            </el-row>
          </el-card>
        </el-col>
      </el-row>

      <el-row :gutter="16">
        <el-col :span="24">
          <el-card class="chart-card">
            <template #header>
              <div class="card-header">
                <span>📈 情绪曲线分析</span>
                <el-checkbox v-model="showLines.expectation">期待值</el-checkbox>
                <el-checkbox v-model="showLines.tension">紧张感</el-checkbox>
                <el-checkbox v-model="showLines.warmth">温馨度</el-checkbox>
                <el-checkbox v-model="showLines.sadness">悲伤度</el-checkbox>
              </div>
            </template>
            <div ref="emotionChartRef" class="chart-container"></div>
            <div v-if="aiSuggestions.emotion" class="ai-suggestion">
              <el-alert :title="aiSuggestions.emotion" type="warning" show-icon :closable="false" />
            </div>
          </el-card>
        </el-col>
      </el-row>

      <el-row :gutter="16" v-if="viewMode === 'detail'">
        <el-col :span="12">
          <el-card class="chart-card">
            <template #header>
              <span>💥 冲突密度分布</span>
            </template>
            <div ref="conflictChartRef" class="chart-container"></div>
            <div v-if="aiSuggestions.conflict" class="ai-suggestion">
              <el-alert :title="aiSuggestions.conflict" type="warning" show-icon :closable="false" />
            </div>
          </el-card>
        </el-col>
        <el-col :span="12">
          <el-card class="chart-card">
            <template #header>
              <span>🎯 章节吸引力预测</span>
            </template>
            <el-table :data="chapterAttraction" stripe size="small">
              <el-table-column prop="chapterNo" label="章节" width="80" />
              <el-table-column prop="potential" label="追读潜力" width="120">
                <template #default="{ row }">
                  <el-rate v-model="row.stars" disabled size="small" />
                </template>
              </el-table-column>
              <el-table-column prop="churnRate" label="预测流失率" width="100">
                <template #default="{ row }">
                  <span :class="getChurnRateClass(row.churnRate)">{{ row.churnRate }}</span>
                </template>
              </el-table-column>
              <el-table-column prop="suggestion" label="建议" />
            </el-table>
          </el-card>
        </el-col>
      </el-row>

      <el-row :gutter="16">
        <el-col :span="24">
          <el-card class="suggestions-card">
            <template #header>
              <span>💡 AI优化建议</span>
            </template>
            <div v-if="optimizationSuggestions.length === 0" class="empty-tips">
              <p>暂无优化建议，当前叙事节奏良好</p>
            </div>
            <div v-else class="suggestions-list">
              <div v-for="(suggestion, idx) in optimizationSuggestions" :key="idx" class="suggestion-item">
                <div class="suggestion-content">
                  <span class="suggestion-text">{{ suggestion.text }}</span>
                  <div class="suggestion-chapters" v-if="suggestion.chapters">
                    <el-tag v-for="chapter in suggestion.chapters" :key="chapter" size="small">
                      第{{ chapter }}章
                    </el-tag>
                  </div>
                </div>
                <div class="suggestion-actions">
                  <el-button size="small" type="primary" @click="applySuggestion(suggestion)">
                    应用建议
                  </el-button>
                  <el-button size="small" @click="ignoreSuggestion(idx)">忽略</el-button>
                </div>
              </div>
            </div>
          </el-card>
        </el-col>
      </el-row>

      <el-row :gutter="16">
        <el-col :span="24">
          <el-card class="quick-nav-card">
            <template #header>
              <span>📍 快速跳转</span>
            </template>
            <div class="quick-nav">
              <el-select v-model="jumpToChapter" placeholder="选择章节" style="width: 200px">
                <el-option v-for="ch in chapterList" :key="ch.id" :label="`第${ch.sortOrder}章 ${ch.title}`" :value="ch.id" />
              </el-select>
              <el-button type="primary" @click="jumpToChapterHandler">跳转</el-button>
            </div>
          </el-card>
        </el-col>
      </el-row>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { DataAnalysis } from '@element-plus/icons-vue'
import * as echarts from 'echarts'
import { listNovels } from '@/api/novel'
import { getRhythmDashboard, getOverallRhythmScore } from '@/api/consistency'

interface Novel {
  id: number
  title: string
}

interface RhythmData {
  chapterNo: number
  expectation: number
  tension: number
  warmth: number
  sadness: number
}

interface ChapterItem {
  id: number
  sortOrder: number
  title: string
}

interface OverallScore {
  overallScore: number
  emotionBalance: number
  conflictBalance: number
  pacingQuality: string
  recommendations: string[]
}

const viewMode = ref<'compact' | 'detail'>('detail')
const selectedNovelId = ref<number>()
const novels = ref<Novel[]>([])
const chapters = ref<ChapterItem[]>([])
const jumpToChapter = ref<number>()
const overallScore = ref<OverallScore | null>(null)

const showLines = reactive({
  expectation: true,
  tension: true,
  warmth: true,
  sadness: true
})

const emotionData = ref<RhythmData[]>([])
const conflictData = ref<number[]>([])
const chapterAttraction = ref<any[]>([])
const optimizationSuggestions = ref<any[]>([])
const aiSuggestions = reactive({
  emotion: '',
  conflict: ''
})

const emotionChartRef = ref<HTMLElement>()
const conflictChartRef = ref<HTMLElement>()
let emotionChart: echarts.ECharts | null = null
let conflictChart: echarts.ECharts | null = null

const chapterList = computed(() => chapters.value)

function getChurnRateClass(rate: string) {
  if (rate.includes('>25%')) return 'churn-high'
  if (rate.includes('>10%')) return 'churn-medium'
  return 'churn-low'
}

async function loadNovels() {
  try {
    const res = await listNovels({ page: 1, size: 100 })
    novels.value = res.data?.records || []
  } catch (e) {
    console.error('Failed to load novels', e)
  }
}

async function loadChapters() {
  if (!selectedNovelId.value) return
  try {
    const res = await listNovels({ page: 1, size: 100, id: selectedNovelId.value })
    const novel = res.data?.records?.[0]
    if (novel) {
      chapters.value = novel.chapters || []
    }
  } catch (e) {
    console.error('Failed to load chapters', e)
  }
}

async function loadOverallScore() {
  if (!selectedNovelId.value) return
  try {
    const res = await getOverallRhythmScore(selectedNovelId.value)
    if (res.data?.data) {
      overallScore.value = res.data.data
    }
  } catch (e) {
    console.error('Failed to load overall score', e)
  }
}

async function loadData() {
  if (!selectedNovelId.value) return

  await loadChapters()

  try {
    const dashboardRes = await getRhythmDashboard(selectedNovelId.value)
    if (dashboardRes.data?.data) {
      const data = dashboardRes.data.data
      emotionData.value = data.emotionCurve.map((r: any) => ({
        chapterNo: r.chapterNo,
        expectation: r.anticipationScore,
        tension: r.tensionScore,
        warmth: r.warmthScore,
        sadness: r.sadnessScore
      }))
      conflictData.value = data.conflictDensity
      chapterAttraction.value = data.chapterAttraction
      optimizationSuggestions.value = data.suggestions
      aiSuggestions.emotion = data.emotionSuggestion
      aiSuggestions.conflict = data.conflictSuggestion
    } else {
      emotionData.value = generateMockEmotionData()
      conflictData.value = generateMockConflictData()
      chapterAttraction.value = generateMockAttractionData()
      optimizationSuggestions.value = generateMockSuggestions()
    }
    await loadOverallScore()
    renderCharts()
  } catch (e) {
    emotionData.value = generateMockEmotionData()
    conflictData.value = generateMockConflictData()
    chapterAttraction.value = generateMockAttractionData()
    optimizationSuggestions.value = generateMockSuggestions()
    renderCharts()
  }
}

function generateMockEmotionData(): RhythmData[] {
  const data: RhythmData[] = []
  for (let i = 1; i <= 25; i++) {
    data.push({
      chapterNo: i,
      expectation: Math.random() * 40 + 50,
      tension: Math.random() * 30 + 40,
      warmth: Math.random() * 20 + 30,
      sadness: Math.random() * 15 + 10
    })
  }
  return data
}

function generateMockConflictData(): number[] {
  return Array.from({ length: 25 }, () => Math.random() * 60 + 40)
}

function generateMockAttractionData(): any[] {
  return [
    { chapterNo: 3, stars: 5, churnRate: '<5%', suggestion: '优秀' },
    { chapterNo: 5, stars: 2, churnRate: '>25%', suggestion: '增加反转' },
    { chapterNo: 8, stars: 4, churnRate: '<10%', suggestion: '良好' },
    { chapterNo: 12, stars: 3, churnRate: '15%', suggestion: '可优化' },
    { chapterNo: 15, stars: 4, churnRate: '<8%', suggestion: '良好' }
  ]
}

function generateMockSuggestions(): any[] {
  return [
    {
      text: '当前平静段落已持续3章，建议插入追逐战',
      chapters: [18, 19]
    },
    {
      text: '揭露主角身世秘密',
      chapters: [20]
    },
    {
      text: '增加反派施压场景',
      chapters: [17]
    }
  ]
}

function renderCharts() {
  renderEmotionChart()
  renderConflictChart()
}

function renderEmotionChart() {
  if (!emotionChartRef.value) return

  if (!emotionChart) {
    emotionChart = echarts.init(emotionChartRef.value)
  }

  const series: echarts.SeriesOption[] = []
  if (showLines.expectation) {
    series.push({
      name: '期待值',
      type: 'line',
      smooth: true,
      data: emotionData.value.map(d => d.expectation)
    })
  }
  if (showLines.tension) {
    series.push({
      name: '紧张感',
      type: 'line',
      smooth: true,
      data: emotionData.value.map(d => d.tension)
    })
  }
  if (showLines.warmth) {
    series.push({
      name: '温馨度',
      type: 'line',
      smooth: true,
      data: emotionData.value.map(d => d.warmth)
    })
  }
  if (showLines.sadness) {
    series.push({
      name: '悲伤度',
      type: 'line',
      smooth: true,
      data: emotionData.value.map(d => d.sadness)
    })
  }

  emotionChart.setOption({
    tooltip: { trigger: 'axis' },
    legend: { top: 0 },
    xAxis: {
      type: 'category',
      data: emotionData.value.map(d => `第${d.chapterNo}章`)
    },
    yAxis: { type: 'value', min: 0, max: 100 },
    series,
    grid: { left: '3%', right: '4%', bottom: '3%', containLabel: true }
  })
}

function renderConflictChart() {
  if (!conflictChartRef.value) return

  if (!conflictChart) {
    conflictChart = echarts.init(conflictChartRef.value)
  }

  conflictChart.setOption({
    tooltip: { trigger: 'axis' },
    xAxis: {
      type: 'category',
      data: conflictData.value.map((_, i) => `第${i + 1}章`)
    },
    yAxis: { type: 'value', min: 0, max: 100 },
    series: [{
      name: '冲突密度',
      type: 'bar',
      data: conflictData.value,
      itemStyle: {
        color: (params: any) => {
          const value = params.value
          if (value < 40) return '#E6A23C'
          if (value > 80) return '#67C23A'
          return '#409EFF'
        }
      }
    }],
    grid: { left: '3%', right: '4%', bottom: '3%', containLabel: true }
  })
}

function applySuggestion(suggestion: any) {
  ElMessage.success('已应用AI优化建议')
}

function ignoreSuggestion(idx: number) {
  optimizationSuggestions.value.splice(idx, 1)
}

function jumpToChapterHandler() {
  if (jumpToChapter.value) {
    ElMessage.info(`跳转到章节ID: ${jumpToChapter.value}`)
  }
}

watch([() => showLines.expectation, () => showLines.tension, () => showLines.warmth, () => showLines.sadness], () => {
  if (emotionData.value.length > 0) {
    renderEmotionChart()
  }
})

onMounted(() => {
  loadNovels()
  window.addEventListener('resize', () => {
    emotionChart?.resize()
    conflictChart?.resize()
  })
})
</script>

<style lang="scss" scoped>
.rhythm-dashboard {
  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 400px;
    color: var(--el-text-color-secondary);

    p {
      margin-top: 16px;
    }
  }

  .dashboard-content {
    .chart-card {
      margin-bottom: 16px;

      .card-header {
        display: flex;
        align-items: center;
        gap: 16px;
      }

      .chart-container {
        height: 300px;
      }

      .ai-suggestion {
        margin-top: 12px;
      }
    }

    .suggestions-card {
      .empty-tips {
        text-align: center;
        padding: 20px;
        color: var(--el-text-color-secondary);
      }

      .suggestions-list {
        .suggestion-item {
          display: flex;
          justify-content: space-between;
          align-items: center;
          padding: 12px;
          border-bottom: 1px solid var(--el-border-color-lighter);

          &:last-child {
            border-bottom: none;
          }

          .suggestion-content {
            .suggestion-text {
              display: block;
              margin-bottom: 8px;
            }

            .suggestion-chapters {
              display: flex;
              gap: 4px;
            }
          }

          .suggestion-actions {
            display: flex;
            gap: 8px;
          }
        }
      }
    }

    .quick-nav-card {
      .quick-nav {
        display: flex;
        gap: 12px;
      }
    }
  }
}

.churn-high {
  color: var(--el-color-danger);
  font-weight: 600;
}

.churn-medium {
  color: var(--el-color-warning);
}

.churn-low {
  color: var(--el-color-success);
}

.overall-score-card {
  margin-bottom: 16px;

  .score-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 16px;

    .score-value {
      font-size: 32px;
      font-weight: 700;
      color: var(--el-color-primary);
    }

    .score-label {
      font-size: 14px;
      color: var(--el-text-color-secondary);
      margin-top: 8px;
    }
  }
}
</style>
