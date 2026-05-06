<template>
  <div class="dashboard page-container">
    <div class="page-header">
      <h1>运营仪表盘</h1>
      <p class="page-header__lead">核心业务指标、增长与收入趋势一览，数据实时更新。</p>
    </div>

    <div class="page-content">
      <div class="stats-grid">
        <div
          v-for="(card, idx) in statCards"
          :key="card.key"
          class="stat-card"
          :class="card.colorClass"
          :style="{ animationDelay: `${idx * 60}ms` }"
          v-loading="loading"
        >
          <div class="stat-card__inner">
            <div class="stat-card__body">
              <span class="stat-card__label">{{ card.label }}</span>
              <span class="stat-card__value">{{ card.value }}</span>
              <span v-if="card.trend !== undefined" class="stat-card__trend" :class="card.trend >= 0 ? 'up' : 'down'">
                <el-icon :size="14"><component :is="card.trend >= 0 ? 'Top' : 'Bottom'" /></el-icon>
                {{ Math.abs(card.trend) }}%
              </span>
            </div>
            <div class="stat-card__icon">
              <el-icon :size="22"><component :is="card.icon" /></el-icon>
            </div>
          </div>
          <div class="stat-card__glow"></div>
        </div>
      </div>

      <div class="charts-grid">
        <div class="chart-card">
          <div class="chart-card__header">
            <h3 class="chart-card__title">用户增长趋势</h3>
            <span class="chart-card__period">近12个月</span>
          </div>
          <div class="chart-card__body">
            <v-chart :option="userChartOption" autoresize />
          </div>
        </div>
        <div class="chart-card">
          <div class="chart-card__header">
            <h3 class="chart-card__title">作品分类分布</h3>
            <span class="chart-card__period">当前统计</span>
          </div>
          <div class="chart-card__body">
            <v-chart :option="novelCategoryOption" autoresize />
          </div>
        </div>
        <div class="chart-card">
          <div class="chart-card__header">
            <h3 class="chart-card__title">订单趋势</h3>
            <span class="chart-card__period">近12个月</span>
          </div>
          <div class="chart-card__body">
            <v-chart :option="orderTrendOption" autoresize />
          </div>
        </div>
        <div class="chart-card">
          <div class="chart-card__header">
            <h3 class="chart-card__title">收入统计</h3>
            <span class="chart-card__period">近12个月</span>
          </div>
          <div class="chart-card__body">
            <v-chart :option="revenueOption" autoresize />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import VChart from 'vue-echarts'
import { use } from 'echarts/core'
import { CanvasRenderer } from 'echarts/renderers'
import { LineChart, PieChart, BarChart } from 'echarts/charts'
import { GridComponent, TooltipComponent, LegendComponent, TitleComponent } from 'echarts/components'
import { getAdminDashboardStats } from '@/api/dashboard'
import type { AdminDashboardStats } from '@/types/api'
import {
  User,
  Document,
  Bell,
  Goods,
  Cpu,
  TrendCharts,
  Files,
  Coin,
  Top,
  Bottom
} from '@element-plus/icons-vue'

use([CanvasRenderer, LineChart, PieChart, BarChart, GridComponent, TooltipComponent, LegendComponent, TitleComponent])

const loading = ref(false)
const stats = ref<AdminDashboardStats>({
  totalUsers: 0,
  totalNovels: 0,
  totalOrders: 0,
  totalAnnouncements: 0,
  activeUsers: 0,
  pendingOrders: 0
})

const aiStats = ref({
  totalApiCalls: 0,
  vectorDataSize: '0 MB'
})

interface StatCard {
  key: string
  label: string
  value: string | number
  icon: any
  colorClass: string
  trend?: number
}

const statCards = computed<StatCard[]>(() => [
  {
    key: 'users',
    label: '用户总数',
    value: stats.value.totalUsers.toLocaleString(),
    icon: User,
    colorClass: 'stat-card--indigo',
    trend: 12.5
  },
  {
    key: 'novels',
    label: '作品总数',
    value: stats.value.totalNovels.toLocaleString(),
    icon: Document,
    colorClass: 'stat-card--emerald',
    trend: 8.2
  },
  {
    key: 'announcements',
    label: '已发布公告',
    value: stats.value.totalAnnouncements.toLocaleString(),
    icon: Bell,
    colorClass: 'stat-card--amber'
  },
  {
    key: 'orders',
    label: '订单总数',
    value: stats.value.totalOrders.toLocaleString(),
    icon: Goods,
    colorClass: 'stat-card--rose',
    trend: -3.1
  },
  {
    key: 'ai',
    label: 'AI 调用次数',
    value: aiStats.value.totalApiCalls.toLocaleString(),
    icon: Cpu,
    colorClass: 'stat-card--violet',
    trend: 24.8
  },
  {
    key: 'active',
    label: '活跃用户',
    value: stats.value.activeUsers.toLocaleString(),
    icon: TrendCharts,
    colorClass: 'stat-card--cyan',
    trend: 5.7
  },
  {
    key: 'vector',
    label: '向量数据',
    value: aiStats.value.vectorDataSize,
    icon: Files,
    colorClass: 'stat-card--pink'
  },
  {
    key: 'pending',
    label: '待处理订单',
    value: stats.value.pendingOrders.toLocaleString(),
    icon: Coin,
    colorClass: 'stat-card--teal'
  }
])

const chartTextColor = '#94a3b8'
const chartBorderColor = 'rgba(148, 163, 184, 0.1)'

const userChartOption = computed(() => {
  const trend = stats.value.userGrowthTrend || []
  return {
    tooltip: {
      trigger: 'axis',
      backgroundColor: '#1e293b',
      borderColor: 'rgba(148, 163, 184, 0.15)',
      textStyle: { color: '#e2e8f0', fontSize: 13 }
    },
    xAxis: {
      type: 'category',
      data: trend.map((t) => t.month.slice(5)),
      axisLine: { lineStyle: { color: chartBorderColor } },
      axisTick: { show: false },
      axisLabel: { color: chartTextColor, fontSize: 11 }
    },
    yAxis: {
      type: 'value',
      splitLine: { lineStyle: { color: chartBorderColor } },
      axisLabel: { color: chartTextColor, fontSize: 11 }
    },
    series: [{
      name: '新增用户',
      type: 'line',
      smooth: true,
      data: trend.map((t) => t.count),
      symbol: 'circle',
      symbolSize: 6,
      areaStyle: {
        color: {
          type: 'linear',
          x: 0, y: 0, x2: 0, y2: 1,
          colorStops: [
            { offset: 0, color: 'rgba(99, 102, 241, 0.28)' },
            { offset: 1, color: 'rgba(99, 102, 241, 0.02)' }
          ]
        }
      },
      lineStyle: { color: '#818cf8', width: 2.5 },
      itemStyle: { color: '#818cf8', borderColor: '#6366f1', borderWidth: 2 }
    }],
    grid: { left: '3%', right: '5%', bottom: '2%', top: '8%', containLabel: true }
  }
})

const novelCategoryOption = computed(() => {
  const dist = stats.value.novelCategoryDistribution || []
  return {
    tooltip: {
      trigger: 'item',
      backgroundColor: '#1e293b',
      borderColor: 'rgba(148, 163, 184, 0.15)',
      textStyle: { color: '#e2e8f0', fontSize: 13 }
    },
    legend: {
      orient: 'vertical',
      left: 'left',
      textStyle: { color: chartTextColor, fontSize: 12 }
    },
    series: [{
      name: '作品分类',
      type: 'pie',
      radius: ['45%', '70%'],
      center: ['55%', '50%'],
      data: dist.map((d) => ({ value: d.count, name: d.category })),
      emphasis: {
        itemStyle: { shadowBlur: 20, shadowOffsetX: 0, shadowColor: 'rgba(0, 0, 0, 0.5)' }
      },
      label: { show: false },
      itemStyle: {
        borderColor: 'rgba(11, 17, 32, 0.9)',
        borderWidth: 2
      }
    }]
  }
})

const orderTrendOption = computed(() => {
  const trend = stats.value.orderTrend || []
  return {
    tooltip: {
      trigger: 'axis',
      backgroundColor: '#1e293b',
      borderColor: 'rgba(148, 163, 184, 0.15)',
      textStyle: { color: '#e2e8f0', fontSize: 13 }
    },
    xAxis: {
      type: 'category',
      data: trend.map((t) => t.month.slice(5)),
      axisLine: { lineStyle: { color: chartBorderColor } },
      axisTick: { show: false },
      axisLabel: { color: chartTextColor, fontSize: 11 }
    },
    yAxis: {
      type: 'value',
      splitLine: { lineStyle: { color: chartBorderColor } },
      axisLabel: { color: chartTextColor, fontSize: 11 }
    },
    series: [{
      name: '订单数',
      type: 'bar',
      data: trend.map((t) => t.count),
      barWidth: '50%',
      itemStyle: {
        borderRadius: [4, 4, 0, 0],
        color: {
          type: 'linear',
          x: 0, y: 0, x2: 0, y2: 1,
          colorStops: [
            { offset: 0, color: '#34d399' },
            { offset: 1, color: '#10b981' }
          ]
        }
      }
    }],
    grid: { left: '3%', right: '5%', bottom: '2%', top: '8%', containLabel: true }
  }
})

const revenueOption = computed(() => {
  const trend = stats.value.revenueTrend || []
  return {
    tooltip: {
      trigger: 'axis',
      backgroundColor: '#1e293b',
      borderColor: 'rgba(148, 163, 184, 0.15)',
      textStyle: { color: '#e2e8f0', fontSize: 13 }
    },
    xAxis: {
      type: 'category',
      data: trend.map((t) => t.month.slice(5)),
      axisLine: { lineStyle: { color: chartBorderColor } },
      axisTick: { show: false },
      axisLabel: { color: chartTextColor, fontSize: 11 }
    },
    yAxis: {
      type: 'value',
      splitLine: { lineStyle: { color: chartBorderColor } },
      axisLabel: { color: chartTextColor, fontSize: 11, formatter: '¥{value}' }
    },
    series: [{
      name: '收入',
      type: 'line',
      smooth: true,
      data: trend.map((t) => t.revenue),
      symbol: 'circle',
      symbolSize: 6,
      areaStyle: {
        color: {
          type: 'linear',
          x: 0, y: 0, x2: 0, y2: 1,
          colorStops: [
            { offset: 0, color: 'rgba(251, 191, 36, 0.25)' },
            { offset: 1, color: 'rgba(245, 158, 11, 0.02)' }
          ]
        }
      },
      lineStyle: { color: '#fbbf24', width: 2.5 },
      itemStyle: { color: '#fbbf24', borderColor: '#f59e0b', borderWidth: 2 }
    }],
    grid: { left: '3%', right: '5%', bottom: '2%', top: '8%', containLabel: true }
  }
})

async function loadStats() {
  loading.value = true
  try {
    const res = await getAdminDashboardStats()
    if (res.data) {
      stats.value = res.data
    }
  } catch (error) {
    console.error('加载仪表盘数据失败:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadStats()
})
</script>

<style lang="scss" scoped>
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: $space-lg;

  @media (max-width: 1440px) {
    grid-template-columns: repeat(4, 1fr);
  }

  @media (max-width: 1200px) {
    grid-template-columns: repeat(3, 1fr);
  }

  @media (max-width: 900px) {
    grid-template-columns: repeat(2, 1fr);
  }

  @media (max-width: 560px) {
    grid-template-columns: 1fr;
  }
}

.stat-card {
  position: relative;
  background: $bg-surface;
  border: 1px solid $border-default;
  border-radius: $radius-lg;
  padding: $space-lg;
  overflow: hidden;
  box-shadow: $shadow-card;
  transition: border-color $transition-base, box-shadow $transition-base, transform $transition-base;
  animation: fadeInUp 0.4s ease backwards;

  &:hover {
    border-color: $border-emphasis;
    transform: translateY(-2px);
    box-shadow: $shadow-elevated;

    .stat-card__glow {
      opacity: 1;
    }
  }
}

.stat-card__glow {
  position: absolute;
  inset: 0;
  opacity: 0;
  transition: opacity $transition-base;
  pointer-events: none;
}

.stat-card--indigo .stat-card__glow {
  background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.06) 0%, transparent 60%);
}

.stat-card--emerald .stat-card__glow {
  background: radial-gradient(circle at top right, rgba(16, 185, 129, 0.06) 0%, transparent 60%);
}

.stat-card--amber .stat-card__glow {
  background: radial-gradient(circle at top right, rgba(245, 158, 11, 0.06) 0%, transparent 60%);
}

.stat-card--rose .stat-card__glow {
  background: radial-gradient(circle at top right, rgba(239, 68, 68, 0.06) 0%, transparent 60%);
}

.stat-card--violet .stat-card__glow {
  background: radial-gradient(circle at top right, rgba(167, 139, 250, 0.06) 0%, transparent 60%);
}

.stat-card--cyan .stat-card__glow {
  background: radial-gradient(circle at top right, rgba(6, 182, 212, 0.06) 0%, transparent 60%);
}

.stat-card--pink .stat-card__glow {
  background: radial-gradient(circle at top right, rgba(244, 114, 182, 0.06) 0%, transparent 60%);
}

.stat-card--teal .stat-card__glow {
  background: radial-gradient(circle at top right, rgba(45, 212, 191, 0.06) 0%, transparent 60%);
}

.stat-card__inner {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  position: relative;
  z-index: 1;
}

.stat-card__body {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.stat-card__label {
  font-size: $font-size-sm;
  font-weight: 500;
  color: $text-muted;
  line-height: 1;
}

.stat-card__value {
  font-size: $font-size-3xl;
  font-weight: 700;
  color: $text-primary;
  line-height: 1.1;
  letter-spacing: -0.03em;
}

.stat-card__trend {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  font-size: $font-size-xs;
  font-weight: 600;
  line-height: 1;

  &.up {
    color: $success-color;
  }

  &.down {
    color: $danger-color;
  }
}

.stat-card__icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border-radius: $radius-md;

  .stat-card--indigo & {
    background: rgba(99, 102, 241, 0.12);
    color: #818cf8;
  }

  .stat-card--emerald & {
    background: $success-ghost;
    color: $success-color;
  }

  .stat-card--amber & {
    background: $warning-ghost;
    color: $warning-color;
  }

  .stat-card--rose & {
    background: $danger-ghost;
    color: $danger-color;
  }

  .stat-card--violet & {
    background: rgba(167, 139, 250, 0.12);
    color: #a78bfa;
  }

  .stat-card--cyan & {
    background: $info-ghost;
    color: $info-color;
  }

  .stat-card--pink & {
    background: rgba(244, 114, 182, 0.12);
    color: #f472b6;
  }

  .stat-card--teal & {
    background: rgba(45, 212, 191, 0.12);
    color: #2dd4bf;
  }
}

.charts-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: $space-lg;

  @media (max-width: 1024px) {
    grid-template-columns: 1fr;
  }
}

.chart-card {
  background: $bg-surface;
  border: 1px solid $border-default;
  border-radius: $radius-lg;
  overflow: hidden;
  box-shadow: $shadow-card;
  transition: border-color $transition-fast, box-shadow $transition-fast, transform $transition-fast;

  &:hover {
    border-color: $border-emphasis;
    box-shadow: $shadow-elevated;
    transform: translateY(-1px);
  }
}

.chart-card__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: $space-lg $space-lg calc($space-sm);
}

.chart-card__title {
  font-size: $font-size-lg;
  font-weight: 600;
  color: $text-primary;
  margin: 0;
}

.chart-card__period {
  font-size: $font-size-xs;
  color: $text-muted;
  font-weight: 500;
  padding: 4px 10px;
  background: rgba(148, 163, 184, 0.06);
  border-radius: $radius-xs;
}

.chart-card__body {
  width: 100%;
  height: 320px;
  padding: $space-sm;
}
</style>
