<template>
  <div class="story-flow-chart">
    <div class="chart-header">
      <h4>📊 故事流程图</h4>
      <div class="chart-controls">
        <el-radio-group v-model="viewMode" size="small">
          <el-radio-button label="timeline">时间线视图</el-radio-button>
          <el-radio-button label="structure">结构视图</el-radio-button>
        </el-radio-group>
        <el-button size="small" @click="handleExpandAll">
          <el-icon><Expand /></el-icon>
          展开全部
        </el-button>
        <el-button size="small" @click="handleCollapseAll">
          <el-icon><Fold /></el-icon>
          收起全部
        </el-button>
      </div>
    </div>

    <div class="chart-container" v-if="viewMode === 'timeline'">
      <div class="timeline-view">
        <div class="timeline-track">
          <div
            v-for="(act, actIndex) in timelineData"
            :key="actIndex"
            class="timeline-act"
          >
            <div class="act-marker" :style="{ backgroundColor: act.color }">
              <span class="act-label">{{ act.title }}</span>
            </div>
            <div class="act-connector">
              <div class="connector-line" :style="{ backgroundColor: act.color }"></div>
            </div>
            <div class="act-content">
              <div
                v-for="(chapter, chIndex) in act.chapters"
                :key="chIndex"
                class="chapter-node"
                :class="{
                  'is-active': chapter.id === activeChapterId,
                  'has-foreshadowing': chapter.hasForeshadowing,
                  'has-climax': chapter.isClimax
                }"
                @click="handleChapterClick(chapter)"
              >
                <div class="chapter-indicator" :style="{ borderColor: act.color }">
                  <span class="chapter-number">{{ chapter.chapterNo }}</span>
                </div>
                <div class="chapter-info">
                  <span class="chapter-title">{{ chapter.title }}</span>
                  <div class="chapter-tags">
                    <el-tag v-if="chapter.hasForeshadowing" size="small" type="warning">伏笔</el-tag>
                    <el-tag v-if="chapter.isClimax" size="small" type="danger">高潮</el-tag>
                    <el-tag v-if="chapter.hasConflict" size="small" type="info">冲突</el-tag>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="chart-container" v-else>
      <div class="structure-view">
        <div class="structure-level start-end">
          <div class="level-label">开篇</div>
          <div class="level-nodes">
            <div
              v-for="node in structureData.start"
              :key="node.id"
              class="structure-node start"
              @click="handleChapterClick(node)"
            >
              <span class="node-title">{{ node.title }}</span>
              <span class="node-chapter">第{{ node.chapterNo }}章</span>
            </div>
          </div>
        </div>

        <div class="level-connector">
          <el-icon><Bottom /></el-icon>
        </div>

        <div class="structure-level rising">
          <div class="level-label">上升</div>
          <div class="level-nodes">
            <div
              v-for="node in structureData.rising"
              :key="node.id"
              class="structure-node rising"
              :class="{ 'has-climax': node.isClimax }"
              @click="handleChapterClick(node)"
            >
              <span class="node-title">{{ node.title }}</span>
              <span class="node-chapter">第{{ node.chapterNo }}章</span>
            </div>
          </div>
        </div>

        <div class="level-connector">
          <el-icon><Bottom /></el-icon>
        </div>

        <div class="structure-level climax">
          <div class="level-label">高潮</div>
          <div class="level-nodes">
            <div
              v-for="node in structureData.climax"
              :key="node.id"
              class="structure-node climax"
              @click="handleChapterClick(node)"
            >
              <span class="node-title">{{ node.title }}</span>
              <span class="node-chapter">第{{ node.chapterNo }}章</span>
            </div>
          </div>
        </div>

        <div class="level-connector">
          <el-icon><Bottom /></el-icon>
        </div>

        <div class="structure-level falling">
          <div class="level-label">下落</div>
          <div class="level-nodes">
            <div
              v-for="node in structureData.falling"
              :key="node.id"
              class="structure-node falling"
              @click="handleChapterClick(node)"
            >
              <span class="node-title">{{ node.title }}</span>
              <span class="node-chapter">第{{ node.chapterNo }}章</span>
            </div>
          </div>
        </div>

        <div class="level-connector">
          <el-icon><Bottom /></el-icon>
        </div>

        <div class="structure-level resolution">
          <div class="level-label">结局</div>
          <div class="level-nodes">
            <div
              v-for="node in structureData.resolution"
              :key="node.id"
              class="structure-node resolution"
              @click="handleChapterClick(node)"
            >
              <span class="node-title">{{ node.title }}</span>
              <span class="node-chapter">第{{ node.chapterNo }}章</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="chart-stats">
      <div class="stat-item">
        <el-icon><Document /></el-icon>
        <span>总章节: {{ totalChapters }}</span>
      </div>
      <div class="stat-item warning">
        <el-icon><Warning /></el-icon>
        <span>伏笔: {{ foreshadowingCount }}</span>
      </div>
      <div class="stat-item danger">
        <el-icon><Lightning /></el-icon>
        <span>高潮: {{ climaxCount }}</span>
      </div>
      <div class="stat-item">
        <el-icon><Clock /></el-icon>
        <span>冲突: {{ conflictCount }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive } from 'vue'
import { Expand, Fold, Bottom, Document, Warning, Lightning, Clock } from '@element-plus/icons-vue'

interface StoryNode {
  id: number
  chapterNo: number
  title: string
  hasForeshadowing?: boolean
  hasConflict?: boolean
  isClimax?: boolean
}

interface TimelineAct {
  title: string
  color: string
  chapters: StoryNode[]
}

interface StructureData {
  start: StoryNode[]
  rising: StoryNode[]
  climax: StoryNode[]
  falling: StoryNode[]
  resolution: StoryNode[]
}

const props = defineProps<{
  chapters: StoryNode[]
}>()

const emit = defineEmits<{
  'chapter-click': [chapter: StoryNode]
}>()

const viewMode = ref<'timeline' | 'structure'>('timeline')
const activeChapterId = ref<number | null>(null)

const timelineData = computed<TimelineAct[]>(() => {
  const actColors = ['#409EFF', '#67C23A', '#E6A23C', '#F56C6C', '#909399']
  const acts: TimelineAct[] = []
  const chapterPerAct = Math.ceil(props.chapters.length / 3)

  for (let i = 0; i < 3; i++) {
    const start = i * chapterPerAct
    const end = Math.min(start + chapterPerAct, props.chapters.length)
    const actChapters = props.chapters.slice(start, end)

    acts.push({
      title: ['第一幕', '第二幕', '第三幕'][i],
      color: actColors[i],
      chapters: actChapters
    })
  }

  return acts
})

const structureData = computed<StructureData>(() => {
  const total = props.chapters.length
  return {
    start: props.chapters.slice(0, Math.ceil(total * 0.1)),
    rising: props.chapters.slice(Math.ceil(total * 0.1), Math.ceil(total * 0.5)),
    climax: props.chapters.slice(Math.ceil(total * 0.5), Math.ceil(total * 0.7)),
    falling: props.chapters.slice(Math.ceil(total * 0.7), Math.ceil(total * 0.9)),
    resolution: props.chapters.slice(Math.ceil(total * 0.9))
  }
})

const totalChapters = computed(() => props.chapters.length)
const foreshadowingCount = computed(() => props.chapters.filter(c => c.hasForeshadowing).length)
const climaxCount = computed(() => props.chapters.filter(c => c.isClimax).length)
const conflictCount = computed(() => props.chapters.filter(c => c.hasConflict).length)

function handleChapterClick(chapter: StoryNode) {
  activeChapterId.value = chapter.id
  emit('chapter-click', chapter)
}

function handleExpandAll() {
  console.log('Expand all')
}

function handleCollapseAll() {
  console.log('Collapse all')
}
</script>

<style lang="scss" scoped>
.story-flow-chart {
  display: flex;
  flex-direction: column;
  height: 100%;
  background: var(--el-bg-color);
  border-radius: 8px;
}

.chart-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  border-bottom: 1px solid var(--el-border-color-light);

  h4 {
    margin: 0;
    font-size: 14px;
  }

  .chart-controls {
    display: flex;
    gap: 8px;
    align-items: center;
  }
}

.chart-container {
  flex: 1;
  overflow: auto;
  padding: 16px;
}

.timeline-view {
  .timeline-track {
    display: flex;
    gap: 24px;
    min-height: 400px;
  }

  .timeline-act {
    flex: 1;
    display: flex;
    flex-direction: column;

    .act-marker {
      padding: 8px 16px;
      border-radius: 8px 8px 0 0;
      text-align: center;

      .act-label {
        color: white;
        font-weight: 600;
        font-size: 14px;
      }
    }

    .act-connector {
      height: 24px;
      display: flex;
      justify-content: center;

      .connector-line {
        width: 4px;
        height: 100%;
        border-radius: 2px;
      }
    }

    .act-content {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 12px;
      padding: 12px;
      background: var(--el-fill-color-light);
      border-radius: 0 0 8px 8px;
    }
  }

  .chapter-node {
    display: flex;
    gap: 12px;
    padding: 12px;
    background: var(--el-bg-color);
    border: 1px solid var(--el-border-color-light);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
      border-color: var(--el-color-primary);
      transform: translateX(4px);
    }

    &.is-active {
      border-color: var(--el-color-primary);
      background: var(--el-color-primary-light-9);
    }

    &.has-foreshadowing {
      border-left: 3px solid var(--el-color-warning);
    }

    &.has-climax {
      border-left: 3px solid var(--el-color-danger);
      background: var(--el-color-danger-light-9);
    }

    .chapter-indicator {
      width: 36px;
      height: 36px;
      border: 2px solid;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;

      .chapter-number {
        font-weight: 600;
        font-size: 12px;
      }
    }

    .chapter-info {
      flex: 1;
      min-width: 0;

      .chapter-title {
        display: block;
        font-weight: 500;
        font-size: 14px;
        margin-bottom: 4px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }

      .chapter-tags {
        display: flex;
        gap: 4px;
      }
    }
  }
}

.structure-view {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  padding: 24px;

  .structure-level {
    width: 100%;
    max-width: 600px;

    .level-label {
      text-align: center;
      font-weight: 600;
      font-size: 12px;
      color: var(--el-text-color-secondary);
      margin-bottom: 8px;
      text-transform: uppercase;
    }

    .level-nodes {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      justify-content: center;
    }
  }

  .structure-node {
    padding: 12px 20px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
    min-width: 100px;

    &:hover {
      transform: scale(1.05);
    }

    &.start {
      background: var(--el-color-primary-light-9);
      border: 1px solid var(--el-color-primary);
    }

    &.rising {
      background: var(--el-color-success-light-9);
      border: 1px solid var(--el-color-success);
    }

    &.climax {
      background: var(--el-color-danger-light-9);
      border: 2px solid var(--el-color-danger);
      transform: scale(1.1);

      &.has-climax {
        box-shadow: 0 4px 12px rgba(245, 108, 108, 0.3);
      }
    }

    &.falling {
      background: var(--el-color-warning-light-9);
      border: 1px solid var(--el-color-warning);
    }

    &.resolution {
      background: var(--el-color-info-light-9);
      border: 1px solid var(--el-color-info);
    }

    .node-title {
      display: block;
      font-weight: 500;
      font-size: 13px;
      margin-bottom: 4px;
    }

    .node-chapter {
      font-size: 11px;
      color: var(--el-text-color-secondary);
    }
  }

  .level-connector {
    color: var(--el-text-color-secondary);
    font-size: 20px;
  }
}

.chart-stats {
  display: flex;
  justify-content: center;
  gap: 24px;
  padding: 12px 16px;
  background: var(--el-fill-color-light);
  border-top: 1px solid var(--el-border-color-light);

  .stat-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: var(--el-text-color-regular);

    &.warning {
      color: var(--el-color-warning);
    }

    &.danger {
      color: var(--el-color-danger);
    }
  }
}
</style>
