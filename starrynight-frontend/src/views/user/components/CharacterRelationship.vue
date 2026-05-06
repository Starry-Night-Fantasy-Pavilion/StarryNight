<template>
  <div class="relationship-graph">
    <div class="graph-header">
      <h3>角色关系图</h3>
      <div class="graph-controls">
        <el-button size="small" @click="resetZoom">重置视图</el-button>
        <el-select v-model="relationshipFilter" placeholder="筛选关系" clearable size="small" style="width: 120px">
          <el-option label="全部" value="" />
          <el-option v-for="type in relationshipTypes" :key="type" :label="type" :value="type" />
        </el-select>
      </div>
    </div>

    <div ref="graphContainer" class="graph-container" @wheel="handleWheel">
      <svg ref="svgRef" :viewBox="`0 0 ${svgWidth} ${svgHeight}`" class="graph-svg">
        <defs>
          <marker id="arrowhead" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">
            <polygon points="0 0, 10 3.5, 0 7" fill="#666" />
          </marker>
        </defs>

        <g :transform="`translate(${transform.x}, ${transform.y}) scale(${transform.scale})`">
          <g v-for="edge in filteredEdges" :key="`${edge.source}-${edge.target}`">
            <line
              :x1="getNodePosition(edge.source)?.x"
              :y1="getNodePosition(edge.source)?.y"
              :x2="getNodePosition(edge.target)?.x"
              :y2="getNodePosition(edge.target)?.y"
              :stroke="getEdgeColor(edge.type)"
              stroke-width="2"
              marker-end="url(#arrowhead)"
            />
            <text
              :x="(getNodePosition(edge.source)?.x + getNodePosition(edge.target)?.x) / 2"
              :y="(getNodePosition(edge.source)?.y + getNodePosition(edge.target)?.y) / 2 - 8"
              class="edge-label"
              text-anchor="middle"
              :fill="getEdgeColor(edge.type)"
            >
              {{ edge.type }}
            </text>
          </g>

          <g
            v-for="node in graphData.nodes"
            :key="node.id"
            :transform="`translate(${nodePositions[node.id]?.x || 0}, ${nodePositions[node.id]?.y || 0})`"
            class="node-group"
            @mousedown="startDrag($event, node)"
            @click="selectNode(node)"
          >
            <circle r="30" :fill="getNodeColor(node)" class="node-circle" />
            <text dy="4" text-anchor="middle" class="node-name">{{ node.name?.charAt(0) || '?' }}</text>
            <text dy="50" text-anchor="middle" class="node-label">{{ node.name }}</text>
            <text dy="65" text-anchor="middle" class="node-identity">{{ node.identity || '' }}</text>
          </g>
        </g>
      </svg>

      <div v-if="selectedNode" class="node-detail-panel">
        <div class="panel-header">
          <h4>{{ selectedNode.name }}</h4>
          <el-button text size="small" @click="selectedNode = null">关闭</el-button>
        </div>
        <div class="panel-body">
          <p><strong>身份：</strong>{{ selectedNode.identity || '未设定' }}</p>
          <p><strong>性别：</strong>{{ selectedNode.gender || '未设定' }}</p>
          <div class="related-characters">
            <h5>关联角色</h5>
            <div v-for="edge in getRelatedEdges(selectedNode.id)" :key="edge.id" class="related-item">
              <span class="relation-type" :style="{ color: getEdgeColor(edge.type) }">{{ edge.type }}</span>
              <span class="target-name">{{ getTargetName(edge) }}</span>
            </div>
            <p v-if="getRelatedEdges(selectedNode.id).length === 0" class="no-related">暂无关联</p>
          </div>
        </div>
      </div>
    </div>

    <div v-if="loading" class="loading-overlay">
      <el-icon class="is-loading"><Loading /></el-icon>
      <span>加载中...</span>
    </div>

    <div v-if="!loading && graphData.nodes.length === 0" class="empty-state">
      <el-empty description="暂无角色关系数据" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { Loading } from '@element-plus/icons-vue'
import { getCharacterGraph } from '@/api/character'

interface GraphNode {
  id: number
  name: string
  identity: string
  gender: string
}

interface GraphEdge {
  id?: number
  source: number
  target: number
  type: string
}

interface GraphData {
  nodes: GraphNode[]
  edges: GraphEdge[]
}

const props = defineProps<{
  novelId: number
}>()

const loading = ref(false)
const graphData = ref<GraphData>({ nodes: [], edges: [] })
const selectedNode = ref<GraphNode | null>(null)
const relationshipFilter = ref('')
const svgRef = ref<SVGSVGElement | null>(null)

const svgWidth = 800
const svgHeight = 600

const transform = reactive({
  x: 0,
  y: 0,
  scale: 1
})

const nodePositions = ref<Record<number, { x: number; y: number }>>({})

const relationshipTypes = computed(() => {
  const types = new Set<string>()
  graphData.value.edges.forEach(e => types.add(e.type))
  return Array.from(types)
})

const filteredEdges = computed(() => {
  if (!relationshipFilter.value) return graphData.value.edges
  return graphData.value.edges.filter(e => e.type === relationshipFilter.value)
})

function getNodeColor(node: GraphNode): string {
  const colors: Record<string, string> = {
    male: '#4a90d9',
    female: '#e91e63',
    other: '#9c27b0'
  }
  return colors[node.gender] || '#67c23a'
}

function getEdgeColor(type: string): string {
  const colors: Record<string, string> = {
    '师徒': '#e6a23c',
    '父子': '#409eff',
    '母子': '#f56c6c',
    '兄弟': '#67c23a',
    '姐妹': '#3caf9e',
    '夫妻': '#ec407a',
    '恋人': '#f48fb1',
    '朋友': '#8bc34a',
    '敌对': '#f44336',
    '盟友': '#00bcd4',
    '同事': '#9e9e9e',
    '同学': '#7e57c2'
  }
  return colors[type] || '#909399'
}

function getNodePosition(nodeId: number) {
  return nodePositions.value[nodeId]
}

function getRelatedEdges(nodeId: number): GraphEdge[] {
  return graphData.value.edges.filter(e => e.source === nodeId || e.target === nodeId)
}

function getTargetName(edge: GraphEdge): string {
  const targetId = edge.source === selectedNode.value?.id ? edge.target : edge.source
  const target = graphData.value.nodes.find(n => n.id === targetId)
  return target?.name || '未知'
}

function selectNode(node: GraphNode) {
  selectedNode.value = selectedNode.value?.id === node.id ? null : node
}

function startDrag(event: MouseEvent, node: GraphNode) {
  event.preventDefault()
}

function handleWheel(event: WheelEvent) {
  event.preventDefault()
  const delta = event.deltaY > 0 ? 0.9 : 1.1
  const newScale = Math.max(0.5, Math.min(2, transform.scale * delta))

  const rect = svgRef.value?.getBoundingClientRect()
  if (rect) {
    const mouseX = event.clientX - rect.left
    const mouseY = event.clientY - rect.top

    transform.x = mouseX - (mouseX - transform.x) * (newScale / transform.scale)
    transform.y = mouseY - (mouseY - transform.y) * (newScale / transform.scale)
  }

  transform.scale = newScale
}

function resetZoom() {
  transform.x = 0
  transform.y = 0
  transform.scale = 1
}

function calculateNodePositions() {
  const nodes = graphData.value.nodes
  const edges = graphData.value.edges
  const positions: Record<number, { x: number; y: number }> = {}

  if (nodes.length === 0) return

  const centerX = svgWidth / 2
  const centerY = svgHeight / 2
  const radius = Math.min(svgWidth, svgHeight) / 2 - 80

  nodes.forEach((node, index) => {
    const angle = (2 * Math.PI * index) / nodes.length - Math.PI / 2
    positions[node.id] = {
      x: centerX + radius * Math.cos(angle),
      y: centerY + radius * Math.sin(angle)
    }
  })

  const maxIterations = 100
  for (let i = 0; i < maxIterations; i++) {
    let moved = false

    for (const edge of edges) {
      const sourcePos = positions[edge.source]
      const targetPos = positions[edge.target]
      if (!sourcePos || !targetPos) continue

      const dx = targetPos.x - sourcePos.x
      const dy = targetPos.y - sourcePos.y
      const distance = Math.sqrt(dx * dx + dy * dy)
      const idealDistance = 150

      if (distance > idealDistance) {
        const force = (distance - idealDistance) / distance * 0.1
        const fx = dx * force
        const fy = dy * force

        if (!edges.some(e => e.target === edge.source || e.source === edge.target)) {
          positions[edge.target].x -= fx
          positions[edge.target].y -= fy
          moved = true
        }
      }
    }

    for (let j = 0; j < nodes.length; j++) {
      for (let k = j + 1; k < nodes.length; k++) {
        const pos1 = positions[nodes[j].id]
        const pos2 = positions[nodes[k].id]
        if (!pos1 || !pos2) continue

        const dx = pos2.x - pos1.x
        const dy = pos2.y - pos1.y
        const distance = Math.sqrt(dx * dx + dy * dy)
        const minDistance = 80

        if (distance < minDistance && distance > 0) {
          const force = (minDistance - distance) / distance * 0.05
          const fx = dx * force
          const fy = dy * force

          pos1.x -= fx
          pos1.y -= fy
          pos2.x += fx
          pos2.y += fy
          moved = true
        }
      }
    }

    for (const node of nodes) {
      const pos = positions[node.id]
      const dx = centerX - pos.x
      const dy = centerY - pos.y
      const distance = Math.sqrt(dx * dx + dy * dy)

      if (distance > radius) {
        pos.x += dx * 0.01
        pos.y += dy * 0.01
        moved = true
      }
    }

    if (!moved) break
  }

  for (const node of nodes) {
    const pos = positions[node.id]
    pos.x = Math.max(60, Math.min(svgWidth - 60, pos.x))
    pos.y = Math.max(60, Math.min(svgHeight - 60, pos.y))
  }

  nodePositions.value = positions
}

async function loadGraph() {
  if (!props.novelId) return

  loading.value = true
  try {
    const res = await getCharacterGraph(props.novelId)
    if (res.data) {
      graphData.value = res.data as GraphData
      calculateNodePositions()
    }
  } catch (error) {
    console.error('Failed to load relationship graph:', error)
  } finally {
    loading.value = false
  }
}

watch(() => props.novelId, () => {
  loadGraph()
})

onMounted(() => {
  loadGraph()
})
</script>

<style lang="scss" scoped>
.relationship-graph {
  position: relative;
  width: 100%;
  height: 100%;
  min-height: 400px;
  background: #fafafa;
  border-radius: 8px;
  overflow: hidden;
}

.graph-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  background: white;
  border-bottom: 1px solid #eee;

  h3 {
    margin: 0;
    font-size: 16px;
  }

  .graph-controls {
    display: flex;
    gap: 8px;
  }
}

.graph-container {
  position: relative;
  width: 100%;
  height: calc(100% - 52px);
  overflow: hidden;
}

.graph-svg {
  width: 100%;
  height: 100%;
  cursor: grab;

  &:active {
    cursor: grabbing;
  }
}

.node-group {
  cursor: pointer;

  .node-circle {
    transition: all 0.2s;
    stroke: white;
    stroke-width: 2;
  }

  &:hover .node-circle {
    transform: scale(1.1);
    filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.2));
  }
}

.node-name {
  fill: white;
  font-size: 16px;
  font-weight: bold;
  pointer-events: none;
}

.node-label {
  fill: #333;
  font-size: 14px;
  font-weight: 500;
  pointer-events: none;
}

.node-identity {
  fill: #999;
  font-size: 12px;
  pointer-events: none;
}

.edge-label {
  font-size: 11px;
  pointer-events: none;
}

.node-detail-panel {
  position: absolute;
  top: 16px;
  right: 16px;
  width: 240px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.15);
  overflow: hidden;

  .panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    background: #f5f5f5;
    border-bottom: 1px solid #eee;

    h4 {
      margin: 0;
      font-size: 14px;
    }
  }

  .panel-body {
    padding: 12px 16px;

    p {
      margin: 0 0 8px;
      font-size: 13px;
      color: #666;
    }

    .related-characters {
      margin-top: 12px;

      h5 {
        margin: 0 0 8px;
        font-size: 13px;
        color: #333;
      }

      .related-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 4px 0;
        font-size: 12px;

        .relation-type {
          font-weight: 500;
        }

        .target-name {
          color: #666;
        }
      }

      .no-related {
        color: #999;
        font-size: 12px;
      }
    }
  }
}

.loading-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.9);

  .el-icon {
    font-size: 32px;
    margin-bottom: 8px;
  }
}

.empty-state {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}
</style>
