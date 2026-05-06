<template>
  <div class="vector-db-manager page-container">
    <div class="page-header">
      <h1>向量数据库管理</h1>
      <div class="header-actions">
        <el-button @click="refreshData">刷新</el-button>
        <el-button type="primary" @click="openAddNodeDialog">添加节点</el-button>
      </div>
    </div>

    <el-tabs v-model="activeTab">
      <el-tab-pane label="节点概览" name="overview">
        <el-row :gutter="16" class="stats-row">
          <el-col :span="6">
            <el-card shadow="hover">
              <div class="stat-card">
                <span class="stat-value">{{ stats.totalNodes }}</span>
                <span class="stat-label">总节点数</span>
              </div>
            </el-card>
          </el-col>
          <el-col :span="6">
            <el-card shadow="hover">
              <div class="stat-card">
                <span class="stat-value">{{ stats.totalVectors }}</span>
                <span class="stat-label">总向量数</span>
              </div>
            </el-card>
          </el-col>
          <el-col :span="6">
            <el-card shadow="hover">
              <div class="stat-card">
                <span class="stat-value">{{ stats.storageUsed }}</span>
                <span class="stat-label">存储使用</span>
              </div>
            </el-card>
          </el-col>
          <el-col :span="6">
            <el-card shadow="hover">
              <div class="stat-card">
                <span class="stat-value">{{ stats.clusterStatus }}</span>
                <span class="stat-label">集群状态</span>
              </div>
            </el-card>
          </el-col>
        </el-row>

        <el-card class="nodes-card">
          <template #header>
            <span>节点列表</span>
          </template>
          <el-table :data="nodes" stripe v-loading="loading">
            <el-table-column prop="name" label="节点名称" min-width="140" />
            <el-table-column prop="address" label="地址" min-width="180" />
            <el-table-column prop="status" label="状态" width="100">
              <template #default="{ row }">
                <el-tag :type="row.status === 'online' ? 'success' : 'danger'" size="small">
                  {{ row.status === 'online' ? '在线' : '离线' }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="vectorCount" label="向量数" width="120" align="center" />
            <el-table-column prop="load" label="负载" width="120">
              <template #default="{ row }">
                <el-progress :percentage="row.load || 0" :color="getLoadColor(row.load)" />
              </template>
            </el-table-column>
            <el-table-column label="操作" width="200" fixed="right">
              <template #default="{ row }">
                <el-button link type="primary" size="small" @click="viewNodeDetail(row)">详情</el-button>
                <el-button link type="warning" size="small" @click="restartNode(row)">重启</el-button>
                <el-button link type="danger" size="small" @click="deleteNode(row)">删除</el-button>
              </template>
            </el-table-column>
          </el-table>
        </el-card>
      </el-tab-pane>

      <el-tab-pane label="集合管理" name="collections">
        <el-card>
          <template #header>
            <div class="card-header">
              <span>向量集合列表</span>
              <el-button type="primary" size="small" @click="openCreateCollectionDialog">创建集合</el-button>
            </div>
          </template>
          <el-table :data="collections" stripe v-loading="loadingCollections">
            <el-table-column prop="name" label="集合名称" min-width="160" />
            <el-table-column prop="type" label="类型" width="120" />
            <el-table-column prop="vectorCount" label="向量数" width="100" align="center" />
            <el-table-column prop="dimension" label="向量维度" width="120" align="center" />
            <el-table-column prop="status" label="状态" width="100">
              <template #default="{ row }">
                <el-tag :type="row.status === 'ready' ? 'success' : 'warning'" size="small">
                  {{ row.status === 'ready' ? '就绪' : '构建中' }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column label="操作" width="240" fixed="right">
              <template #default="{ row }">
                <el-button link type="primary" size="small" @click="viewCollectionDetail(row)">详情</el-button>
                <el-button link type="success" size="small" @click="createSnapshot(row)">快照</el-button>
                <el-button link type="danger" size="small" @click="deleteCollection(row)">删除</el-button>
              </template>
            </el-table-column>
          </el-table>
        </el-card>
      </el-tab-pane>

      <el-tab-pane label="监控面板" name="monitor">
        <el-row :gutter="16">
          <el-col :span="12">
            <el-card>
              <template #header>
                <span>集群性能</span>
              </template>
              <div ref="performanceChartRef" class="chart-container"></div>
            </el-card>
          </el-col>
          <el-col :span="12">
            <el-card>
              <template #header>
                <span>告警列表</span>
              </template>
              <el-table :data="alerts" size="small">
                <el-table-column prop="time" label="时间" width="160" />
                <el-table-column prop="level" label="级别" width="100">
                  <template #default="{ row }">
                    <el-tag :type="getAlertType(row.level)" size="small">{{ alertLevelLabel(row.level) }}</el-tag>
                  </template>
                </el-table-column>
                <el-table-column prop="message" label="告警信息" />
              </el-table>
            </el-card>
          </el-col>
        </el-row>
      </el-tab-pane>

      <el-tab-pane label="池配置" name="poolConfig">
        <el-card>
          <template #header>
            <span>连接池配置</span>
          </template>
          <el-form :model="poolConfig" label-width="160px">
            <el-form-item label="最大连接数">
              <el-input-number v-model="poolConfig.maxConnections" :min="1" :max="100" />
            </el-form-item>
            <el-form-item label="最小空闲连接">
              <el-input-number v-model="poolConfig.minIdle" :min="0" :max="20" />
            </el-form-item>
            <el-form-item label="连接超时(ms)">
              <el-input-number v-model="poolConfig.connectionTimeout" :min="1000" :max="30000" :step="1000" />
            </el-form-item>
            <el-form-item label="最大向量数">
              <el-input-number v-model="poolConfig.maxVectors" :min="0" />
              <span class="form-hint">0表示无限制</span>
            </el-form-item>
            <el-form-item label="最大存储(GB)">
              <el-input-number v-model="poolConfig.maxStorage" :min="1" :max="1000" />
            </el-form-item>
            <el-form-item>
              <el-button type="primary" @click="savePoolConfig">保存配置</el-button>
            </el-form-item>
          </el-form>
        </el-card>
      </el-tab-pane>
    </el-tabs>

    <el-dialog v-model="addNodeDialogVisible" title="添加向量数据库节点" width="520px">
      <el-form :model="nodeForm" label-width="120px">
        <el-form-item label="节点名称" required>
          <el-input v-model="nodeForm.name" placeholder="如: qdrant-node-01" />
        </el-form-item>
        <el-form-item label="主机地址" required>
          <el-input v-model="nodeForm.host" placeholder="如: 192.168.1.101" />
        </el-form-item>
        <el-form-item label="端口">
          <el-input-number v-model="nodeForm.port" :min="1" :max="65535" />
        </el-form-item>
        <el-form-item label="接口密钥">
          <el-input v-model="nodeForm.apiKey" type="password" show-password placeholder="可选" />
        </el-form-item>
        <el-form-item label="最大向量数">
          <el-input-number v-model="nodeForm.maxVectors" :min="0" />
          <span class="form-hint">0表示无限制</span>
        </el-form-item>
        <el-form-item label="最大存储(GB)">
          <el-input-number v-model="nodeForm.maxStorage" :min="1" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="addNodeDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitAddNode">添加</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="createCollectionDialogVisible" title="创建向量集合" width="520px">
      <el-form :model="collectionForm" label-width="120px">
        <el-form-item label="集合名称" required>
          <el-input v-model="collectionForm.name" placeholder="如：novel_knowledge" />
        </el-form-item>
        <el-form-item label="向量维度" required>
          <el-input-number v-model="collectionForm.dimension" :min="64" :max="4096" />
          <div class="form-hint">
            <el-radio-group v-model="collectionForm.embeddingModel" size="small" @change="updateDimension">
              <el-radio label="ada-002">ada-002 (1536维)</el-radio>
              <el-radio label="3-small">3-small (1536维)</el-radio>
              <el-radio label="3-large">3-large (3072维)</el-radio>
            </el-radio-group>
          </div>
        </el-form-item>
        <el-form-item label="距离度量">
          <el-select v-model="collectionForm.distance">
            <el-option label="余弦相似度" value="Cosine" />
            <el-option label="点积" value="Dot" />
            <el-option label="欧氏距离" value="Euclidean" />
          </el-select>
        </el-form-item>
        <el-form-item label="最大向量数">
          <el-input-number v-model="collectionForm.maxVectors" :min="0" />
          <span class="form-hint">0表示无限制</span>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="createCollectionDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitCreateCollection">创建</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="nodeDetailDialogVisible" title="节点详情" width="640px">
      <el-descriptions v-if="selectedNode" :column="2" border>
        <el-descriptions-item label="节点名称">{{ selectedNode.name }}</el-descriptions-item>
        <el-descriptions-item label="地址">{{ selectedNode.address }}</el-descriptions-item>
        <el-descriptions-item label="状态">
          <el-tag :type="selectedNode.status === 'online' ? 'success' : 'danger'">
            {{ selectedNode.status === 'online' ? '在线' : '离线' }}
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="负载">{{ selectedNode.load }}%</el-descriptions-item>
        <el-descriptions-item label="向量数">{{ selectedNode.vectorCount }}</el-descriptions-item>
        <el-descriptions-item label="存储使用">{{ selectedNode.storageUsed }}</el-descriptions-item>
      </el-descriptions>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, watch } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import * as echarts from 'echarts'
import {
  getVectorStats,
  listVectorNodes,
  createVectorNode,
  deleteVectorNode,
  restartVectorNode,
  listVectorCollections,
  createVectorCollection,
  deleteVectorCollection,
  createVectorSnapshot,
  getVectorPoolConfig,
  saveVectorPoolConfig
} from '@/api/vectorDb'
import type { VectorNode, VectorCollection, VectorPoolConfig, VectorStats } from '@/api/vectorDb'

interface Alert {
  time: string
  level: string
  message: string
}

const activeTab = ref('overview')
const loading = ref(false)
const loadingCollections = ref(false)
const nodes = ref<VectorNode[]>([])
const collections = ref<VectorCollection[]>([])
const alerts = ref<Alert[]>([])
const selectedNode = ref<VectorNode | null>(null)

const stats = reactive<VectorStats>({
  totalNodes: 0,
  totalVectors: '0',
  storageUsed: '0GB / 0GB',
  clusterStatus: '未知'
})

const poolConfig = reactive<VectorPoolConfig>({
  maxConnections: 50,
  minIdle: 5,
  connectionTimeout: 5000,
  maxVectors: 1000000,
  maxStorage: 100
})

const addNodeDialogVisible = ref(false)
const nodeForm = reactive<VectorNode>({
  name: '',
  host: '',
  port: 6333,
  apiKey: '',
  maxVectors: 0,
  maxStorage: 50
})

const createCollectionDialogVisible = ref(false)
const collectionForm = reactive<VectorCollection>({
  name: '',
  dimension: 1536,
  embeddingModel: '3-small',
  distance: 'Cosine',
  maxVectors: 0
})

const nodeDetailDialogVisible = ref(false)

const performanceChartRef = ref<HTMLElement>()
let performanceChart: echarts.ECharts | null = null

function getLoadColor(load: number | undefined) {
  if (!load) return '#67C23A'
  if (load < 50) return '#67C23A'
  if (load < 80) return '#E6A23C'
  return '#F56C6C'
}

function getAlertType(level: string): 'success' | 'warning' | 'danger' | 'info' {
  const map: Record<string, 'success' | 'warning' | 'danger' | 'info'> = {
    Critical: 'danger',
    Warning: 'warning',
    Info: 'info',
    紧急: 'danger',
    警告: 'warning',
    提示: 'info'
  }
  return map[level] || 'info'
}

function alertLevelLabel(level: string) {
  const map: Record<string, string> = {
    Critical: '紧急',
    Warning: '警告',
    Info: '提示'
  }
  return map[level] || level
}

function updateDimension() {
  const dimensionMap: Record<string, number> = {
    'ada-002': 1536,
    '3-small': 1536,
    '3-large': 3072
  }
  collectionForm.dimension = dimensionMap[collectionForm.embeddingModel || '3-small'] || 1536
}

async function refreshData() {
  loading.value = true
  try {
    await loadStats()
    await loadNodes()
    await loadCollections()
    ElMessage.success('数据已刷新')
  } catch (error) {
    ElMessage.error('刷新失败')
  } finally {
    loading.value = false
  }
}

async function loadStats() {
  try {
    const res = await getVectorStats()
    if (res) {
      Object.assign(stats, res)
    }
  } catch {
    stats.totalNodes = 0
    stats.totalVectors = '0'
    stats.storageUsed = '0GB / 0GB'
    stats.clusterStatus = '未知'
  }
}

async function loadNodes() {
  try {
    const res = await listVectorNodes()
    if (res) {
      nodes.value = res
    }
  } catch {
    nodes.value = []
  }
}

async function loadCollections() {
  loadingCollections.value = true
  try {
    const res = await listVectorCollections()
    if (res) {
      collections.value = res
    }
  } catch {
    collections.value = []
  } finally {
    loadingCollections.value = false
  }
}

async function loadPoolConfig() {
  try {
    const res = await getVectorPoolConfig()
    if (res) {
      Object.assign(poolConfig, res)
    }
  } catch {
    // 使用默认值
  }
}

function viewNodeDetail(node: VectorNode) {
  selectedNode.value = node
  nodeDetailDialogVisible.value = true
}

async function restartNode(node: VectorNode) {
  try {
    await ElMessageBox.confirm(`确定重启节点 ${node.name} 吗?`, '提示', { type: 'warning' })
    await restartVectorNode(node.id!)
    ElMessage.success(`节点 ${node.name} 正在重启...`)
  } catch {
    // 用户取消
  }
}

async function deleteNode(node: VectorNode) {
  try {
    await ElMessageBox.confirm(`确定删除节点 ${node.name} 吗?`, '提示', { type: 'warning' })
    await deleteVectorNode(node.id!)
    ElMessage.success('节点已删除')
    await loadNodes()
    await loadStats()
  } catch {
    // 用户取消
  }
}

function openAddNodeDialog() {
  nodeForm.name = ''
  nodeForm.host = ''
  nodeForm.port = 6333
  nodeForm.apiKey = ''
  nodeForm.maxVectors = 0
  nodeForm.maxStorage = 50
  addNodeDialogVisible.value = true
}

async function submitAddNode() {
  if (!nodeForm.name.trim() || !nodeForm.host.trim()) {
    ElMessage.warning('请填写必填项')
    return
  }
  try {
    await createVectorNode(nodeForm)
    ElMessage.success('节点添加成功')
    addNodeDialogVisible.value = false
    await loadNodes()
    await loadStats()
  } catch {
    ElMessage.error('添加失败')
  }
}

function openCreateCollectionDialog() {
  collectionForm.name = ''
  collectionForm.dimension = 1536
  collectionForm.embeddingModel = '3-small'
  collectionForm.distance = 'Cosine'
  collectionForm.maxVectors = 0
  createCollectionDialogVisible.value = true
}

async function submitCreateCollection() {
  if (!collectionForm.name.trim()) {
    ElMessage.warning('请输入集合名称')
    return
  }
  try {
    await createVectorCollection(collectionForm)
    ElMessage.success('集合创建成功')
    createCollectionDialogVisible.value = false
    await loadCollections()
  } catch {
    ElMessage.error('创建失败')
  }
}

function viewCollectionDetail(collection: VectorCollection) {
  ElMessage.info(`查看集合：${collection.name}`)
}

async function createSnapshot(collection: VectorCollection) {
  try {
    await createVectorSnapshot(collection.id!)
    ElMessage.success(`正在为 ${collection.name} 创建快照...`)
  } catch {
    ElMessage.error('创建快照失败')
  }
}

async function deleteCollection(collection: VectorCollection) {
  try {
    await ElMessageBox.confirm(`确定删除集合「${collection.name}」吗？`, '提示', { type: 'warning' })
    await deleteVectorCollection(collection.id!)
    ElMessage.success('集合已删除')
    await loadCollections()
  } catch {
    // 用户取消
  }
}

async function savePoolConfig() {
  try {
    await saveVectorPoolConfig(poolConfig)
    ElMessage.success('连接池配置已保存')
  } catch {
    ElMessage.error('保存失败')
  }
}

function renderPerformanceChart() {
  if (!performanceChartRef.value) return

  if (!performanceChart) {
    performanceChart = echarts.init(performanceChartRef.value)
  }

  const option = {
    backgroundColor: 'transparent',
    tooltip: { trigger: 'axis' },
    legend: {
      data: ['每秒查询量', '延迟(ms)'],
      textStyle: { color: '#cbd5e1' }
    },
    xAxis: {
      type: 'category',
      data: ['00:00', '04:00', '08:00', '12:00', '16:00', '20:00', '24:00'],
      axisLine: { lineStyle: { color: 'rgba(148,163,184,0.15)' } },
      axisLabel: { color: '#64748b' }
    },
    yAxis: [
      {
        type: 'value',
        name: '查询量/秒',
        nameTextStyle: { color: '#64748b' },
        splitLine: { lineStyle: { color: 'rgba(148,163,184,0.08)' } },
        axisLabel: { color: '#64748b' }
      },
      {
        type: 'value',
        name: '延迟',
        nameTextStyle: { color: '#64748b' },
        splitLine: { show: false },
        axisLabel: { color: '#64748b' }
      }
    ],
    series: [
      {
        name: '每秒查询量',
        type: 'line',
        data: [120, 80, 200, 300, 280, 250, 180],
        smooth: true,
        lineStyle: { color: '#6366f1', width: 2 },
        itemStyle: { color: '#6366f1' },
        areaStyle: {
          color: {
            type: 'linear',
            x: 0, y: 0, x2: 0, y2: 1,
            colorStops: [
              { offset: 0, color: 'rgba(99,102,241,0.15)' },
              { offset: 1, color: 'rgba(99,102,241,0)' }
            ]
          }
        }
      },
      {
        name: '延迟(ms)',
        type: 'line',
        yAxisIndex: 1,
        data: [20, 25, 30, 28, 35, 32, 25],
        smooth: true,
        lineStyle: { color: '#f59e0b', width: 2 },
        itemStyle: { color: '#f59e0b' }
      }
    ]
  }

  performanceChart.setOption(option)
}

watch(activeTab, (val) => {
  if (val === 'monitor') {
    setTimeout(renderPerformanceChart, 100)
  }
})

onMounted(async () => {
  await loadStats()
  await loadNodes()
  await loadPoolConfig()

  alerts.value = [
    { time: '2026-04-28 14:30', level: 'Warning', message: '节点负载接近阈值' },
    { time: '2026-04-28 12:00', level: 'Info', message: '向量集合索引构建完成' }
  ]

  window.addEventListener('resize', () => {
    performanceChart?.resize()
  })
})
</script>

<style lang="scss" scoped>
.vector-db-manager {
  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .stats-row {
    margin-bottom: $space-md;

    .stat-card {
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: $space-lg;

      .stat-value {
        font-size: $font-size-2xl;
        font-weight: 700;
        color: $text-primary;
        letter-spacing: -0.02em;
      }

      .stat-label {
        font-size: $font-size-sm;
        color: $text-muted;
        margin-top: $space-sm;
        font-weight: 500;
      }
    }
  }

  .nodes-card {
    margin-top: $space-md;
  }

  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .chart-container {
    height: 300px;
  }

  .header-actions {
    display: flex;
    gap: $space-md;
    align-items: center;
  }

  .form-hint {
    display: block;
    margin-top: $space-sm;
    font-size: $font-size-xs;
    color: $text-muted;
    line-height: 1.5;
  }
}
</style>