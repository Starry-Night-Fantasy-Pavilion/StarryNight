<template>
  <div class="relationship-graph">
    <div class="graph-header">
      <h4>🔗 角色关系图谱</h4>
      <div class="graph-controls">
        <el-radio-group v-model="layoutType" size="small">
          <el-radio-button label="force">力导向图</el-radio-button>
          <el-radio-button label="circular">环形布局</el-radio-button>
        </el-radio-group>
        <el-button size="small" @click="handleZoomIn">
          <el-icon><ZoomIn /></el-icon>
        </el-button>
        <el-button size="small" @click="handleZoomOut">
          <el-icon><ZoomOut /></el-icon>
        </el-button>
        <el-button size="small" @click="handleResetView">
          <el-icon><Refresh /></el-icon>
        </el-button>
      </div>
    </div>

    <div class="graph-container" ref="graphContainer">
      <v-chart :option="chartOption" autoresize @click="handleNodeClick" />
    </div>

    <div class="graph-legend">
      <div class="legend-item" v-for="category in categories" :key="category.name">
        <span class="legend-color" :style="{ backgroundColor: category.color }"></span>
        <span class="legend-label">{{ category.label }}</span>
      </div>
      <div class="legend-item">
        <span class="legend-line solid"></span>
        <span class="legend-label">友好关系</span>
      </div>
      <div class="legend-item">
        <span class="legend-line dashed"></span>
        <span class="legend-label">敌对关系</span>
      </div>
    </div>

    <div class="graph-info" v-if="selectedNode">
      <el-card shadow="hover">
        <template #header>
          <div class="info-header">
            <span>{{ selectedNode.name }}</span>
            <el-button size="small" link @click="selectedNode = null">
              <el-icon><Close /></el-icon>
            </el-button>
          </div>
        </template>
        <div class="info-content">
          <div class="info-item">
            <span class="info-label">身份：</span>
            <span class="info-value">{{ selectedNode.identity || '未设置' }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">性格：</span>
            <el-tag
              v-for="trait in selectedNode.personality?.traits || []"
              :key="trait"
              size="small"
              class="info-tag"
            >
              {{ trait }}
            </el-tag>
          </div>
          <div class="info-item">
            <span class="info-label">关系：</span>
            <div class="relation-list">
              <div
                v-for="rel in selectedNode.relationships"
                :key="rel.targetId"
                class="relation-item"
              >
                <span class="rel-target">{{ rel.targetName }}</span>
                <el-tag size="small" :type="getRelationType(rel.type)">
                  {{ rel.type }}
                </el-tag>
              </div>
            </div>
          </div>
        </div>
      </el-card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive, onMounted } from 'vue'
import { ZoomIn, ZoomOut, Refresh, Close } from '@element-plus/icons-vue'
import type { NovelCharacter } from '@/api/character'
import type { CharacterRelationship } from '@/api/character'

interface CharacterNode {
  id: number
  name: string
  identity?: string
  personality?: {
    traits: string[]
  }
  relationships: CharacterRelationship[]
  avatar?: string
  category: number
}

interface GraphLink {
  source: number
  target: number
  label: string
  type: 'friendly' | 'hostile' | 'neutral'
}

interface GraphData {
  nodes: CharacterNode[]
  links: GraphLink[]
}

const props = defineProps<{
  data: GraphData
}>()

const emit = defineEmits<{
  'node-click': [character: CharacterNode]
  'relationship-click': [link: GraphLink]
}>()

const layoutType = ref<'force' | 'circular'>('force')
const selectedNode = ref<CharacterNode | null>(null)
const graphContainer = ref<HTMLElement | null>(null)
const zoomLevel = ref(1)

const categories = [
  { name: 'protagonist', label: '主角', color: '#F56C6C' },
  { name: 'supporting', label: '配角', color: '#409EFF' },
  { name: 'antagonist', label: '反派', color: '#67C23A' },
  { name: 'npc', label: 'NPC', color: '#909399' }
]

const chartOption = computed(() => {
  const nodes = props.data.nodes.map(node => ({
    id: node.id,
    name: node.name,
    symbolSize: node.category === 0 ? 70 : (node.category === 2 ? 60 : 50),
    category: node.category,
    draggable: true,
    label: {
      show: true,
      formatter: node.name,
      fontSize: 12
    }
  }))

  const links = props.data.links.map(link => ({
    source: link.source,
    target: link.target,
    name: link.label,
    lineStyle: {
      type: link.type === 'hostile' ? 'dashed' : 'solid',
      color: link.type === 'hostile' ? '#F56C6C' : (link.type === 'friendly' ? '#67C23A' : '#909399'),
      width: 2
    },
    label: {
      show: true,
      formatter: link.label,
      fontSize: 10
    }
  }))

  return {
    tooltip: {
      trigger: 'item',
      formatter: (params: any) => {
        if (params.dataType === 'node') {
          const node = props.data.nodes.find(n => n.id === params.data.id)
          return node ? `${node.name}<br/>身份: ${node.identity || '未知'}` : ''
        }
        return params.data.name || ''
      }
    },
    legend: {
      show: false
    },
    series: [
      {
        type: 'graph',
        layout: layoutType.value,
        roam: true,
        nodeScaleRatio: 0.5,
        symbolKeepAspect: true,
        nodes: nodes,
        links: links,
        categories: categories.map(c => ({ name: c.name })),
        lineStyle: {
          curveness: 0.3
        },
        emphasis: {
          focus: 'adjacency',
          itemStyle: {
            borderWidth: 3,
            shadowBlur: 10,
            shadowColor: 'rgba(0, 0, 0, 0.3)'
          },
          lineStyle: {
            width: 4
          }
        },
        zoom: zoomLevel.value,
        label: {
          position: 'bottom',
          formatter: '{b}'
        },
        force: {
          repulsion: 2000,
          gravity: 0.1,
          edgeLength: [100, 300],
          layoutAnimation: true
        },
        circular: {
          rotateLabel: true
        }
      }
    ]
  }
})

function getRelationType(type: string): string {
  const friendlyTypes = ['朋友', '亲人', '爱人', '导师', '盟友']
  const hostileTypes = ['敌人', '仇人', '对手']

  if (friendlyTypes.includes(type)) return 'success'
  if (hostileTypes.includes(type)) return 'danger'
  return 'info'
}

function handleNodeClick(params: any) {
  if (params.dataType === 'node') {
    const node = props.data.nodes.find(n => n.id === params.data.id)
    if (node) {
      selectedNode.value = node
      emit('node-click', node)
    }
  } else if (params.dataType === 'edge') {
    const link = props.data.links.find(
      l => l.source === params.data.source && l.target === params.data.target
    )
    if (link) {
      emit('relationship-click', link)
    }
  }
}

function handleZoomIn() {
  zoomLevel.value = Math.min(3, zoomLevel.value + 0.2)
}

function handleZoomOut() {
  zoomLevel.value = Math.max(0.3, zoomLevel.value - 0.2)
}

function handleResetView() {
  zoomLevel.value = 1
  selectedNode.value = null
}
</script>

<style lang="scss" scoped>
.relationship-graph {
  display: flex;
  flex-direction: column;
  height: 100%;
  background: var(--el-bg-color);
  border-radius: 8px;
}

.graph-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  border-bottom: 1px solid var(--el-border-color-light);

  h4 {
    margin: 0;
    font-size: 14px;
  }

  .graph-controls {
    display: flex;
    gap: 8px;
    align-items: center;
  }
}

.graph-container {
  flex: 1;
  min-height: 400px;
}

.graph-legend {
  display: flex;
  gap: 16px;
  padding: 12px 16px;
  background: var(--el-fill-color-light);
  border-top: 1px solid var(--el-border-color-light);
  flex-wrap: wrap;

  .legend-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;

    .legend-color {
      width: 12px;
      height: 12px;
      border-radius: 2px;
    }

    .legend-line {
      width: 24px;
      height: 2px;
      background: var(--el-color-success);

      &.dashed {
        background: repeating-linear-gradient(
          90deg,
          var(--el-color-danger),
          var(--el-color-danger) 4px,
          transparent 4px,
          transparent 8px
        );
      }
    }
  }
}

.graph-info {
  padding: 12px 16px;

  .info-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 600;
  }

  .info-content {
    .info-item {
      display: flex;
      align-items: flex-start;
      gap: 8px;
      margin-bottom: 12px;

      &:last-child {
        margin-bottom: 0;
      }

      .info-label {
        flex-shrink: 0;
        color: var(--el-text-color-secondary);
        font-size: 13px;
      }

      .info-value {
        font-size: 13px;
      }

      .info-tag {
        margin-right: 4px;
      }

      .relation-list {
        display: flex;
        flex-direction: column;
        gap: 6px;

        .relation-item {
          display: flex;
          align-items: center;
          gap: 8px;

          .rel-target {
            font-size: 13px;
          }
        }
      }
    }
  }
}
</style>
