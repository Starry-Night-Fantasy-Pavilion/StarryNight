<template>
  <div class="novel-timeline page-container">
    <div class="page-header">
      <h2>📅 时间线视图</h2>
      <div class="header-actions">
        <el-radio-group v-model="viewMode" size="small">
          <el-radio-button label="vertical">纵向</el-radio-button>
          <el-radio-button label="horizontal">横向</el-radio-button>
        </el-radio-group>
        <el-button @click="handleAiGenerate">
          <el-icon><MagicStick /></el-icon>
          AI生成时间线
        </el-button>
      </div>
    </div>

    <div class="page-content">
      <div v-if="events.length" class="timeline-container" :class="viewMode">
        <el-timeline>
          <el-timeline-item
            v-for="event in events"
            :key="event.id"
            :timestamp="event.time"
            :type="getEventType(event.type)"
            :hollow="event.isMilestone"
            placement="top"
          >
            <el-card shadow="hover" class="timeline-card">
              <template #header>
                <div class="card-header">
                  <span class="event-title">{{ event.title }}</span>
                  <el-tag v-if="event.isMilestone" size="small" type="warning">里程碑</el-tag>
                </div>
              </template>
              <div class="card-body">
                <p class="event-description">{{ event.description }}</p>
                <div v-if="event.characters?.length" class="event-characters">
                  <span class="label">涉及角色：</span>
                  <el-tag
                    v-for="char in event.characters"
                    :key="char.id"
                    size="small"
                    class="character-tag"
                  >
                    {{ char.name }}
                  </el-tag>
                </div>
                <div v-if="event.location" class="event-location">
                  <span class="label">地点：</span>
                  <span>{{ event.location }}</span>
                </div>
              </div>
              <div class="card-footer">
                <el-button size="small" link @click="handleEdit(event)">编辑</el-button>
                <el-button size="small" link type="primary" @click="handleAddSubEvent(event)">添加子事件</el-button>
              </div>
            </el-card>
          </el-timeline-item>
        </el-timeline>
      </div>

      <el-empty v-else description="暂无时间线事件">
        <el-button type="primary" @click="handleAiGenerate">让AI帮你生成时间线</el-button>
      </el-empty>
    </div>

    <el-dialog v-model="showEditDialog" :title="editingEvent ? '编辑事件' : '添加事件'" width="600px" destroy-on-close>
      <el-form :model="eventForm" label-width="100px">
        <el-form-item label="事件标题" required>
          <el-input v-model="eventForm.title" placeholder="请输入事件标题" />
        </el-form-item>
        <el-form-item label="发生时间">
          <el-input v-model="eventForm.time" placeholder="如：故事开始前3年" />
        </el-form-item>
        <el-form-item label="事件类型">
          <el-select v-model="eventForm.type" style="width: 100%">
            <el-option label="主线事件" value="main" />
            <el-option label="支线事件" value="sub" />
            <el-option label="回忆" value="memory" />
            <el-option label="未来" value="future" />
          </el-select>
        </el-form-item>
        <el-form-item label="事件描述">
          <el-input v-model="eventForm.description" type="textarea" :rows="3" />
        </el-form-item>
        <el-form-item label="发生地点">
          <el-input v-model="eventForm.location" placeholder="事件发生的地点" />
        </el-form-item>
        <el-form-item label="涉及角色">
          <el-select v-model="eventForm.characterIds" multiple placeholder="选择涉及的角色" style="width: 100%">
            <el-option
              v-for="char in characters"
              :key="char.id"
              :label="char.name"
              :value="char.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="里程碑">
          <el-switch v-model="eventForm.isMilestone" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showEditDialog = false">取消</el-button>
        <el-button type="primary" @click="handleSaveEvent">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import { MagicStick } from '@element-plus/icons-vue'

interface TimelineEvent {
  id: number
  title: string
  time: string
  type: string
  description: string
  location?: string
  characters?: Array<{ id: number; name: string }>
  isMilestone?: boolean
}

interface Character {
  id: number
  name: string
}

const route = useRoute()
const novelId = ref(route.params.id as string)

const viewMode = ref<'vertical' | 'horizontal'>('vertical')
const events = ref<TimelineEvent[]>([])
const characters = ref<Character[]>([])
const showEditDialog = ref(false)
const editingEvent = ref<TimelineEvent | null>(null)

const eventForm = reactive({
  title: '',
  time: '',
  type: 'main',
  description: '',
  location: '',
  characterIds: [] as number[],
  isMilestone: false
})

function getEventType(type: string): string {
  const map: Record<string, string> = {
    main: 'primary',
    sub: 'info',
    memory: 'warning',
    future: 'success'
  }
  return map[type] || 'info'
}

function handleAiGenerate() {
  ElMessage.info('AI生成时间线功能开发中')
}

function handleEdit(event: TimelineEvent) {
  editingEvent.value = event
  Object.assign(eventForm, {
    title: event.title,
    time: event.time,
    type: event.type,
    description: event.description,
    location: event.location,
    characterIds: event.characters?.map(c => c.id) || [],
    isMilestone: event.isMilestone
  })
  showEditDialog.value = true
}

function handleAddSubEvent(event: TimelineEvent) {
  editingEvent.value = null
  Object.assign(eventForm, {
    title: '',
    time: '',
    type: 'sub',
    description: '',
    location: '',
    characterIds: [],
    isMilestone: false
  })
  showEditDialog.value = true
}

function handleSaveEvent() {
  if (!eventForm.title) {
    ElMessage.warning('请输入事件标题')
    return
  }

  if (editingEvent.value) {
    const index = events.value.findIndex(e => e.id === editingEvent.value!.id)
    if (index !== -1) {
      events.value[index] = {
        ...editingEvent.value,
        ...eventForm,
        characters: eventForm.characterIds.map(id => ({
          id,
          name: characters.value.find(c => c.id === id)?.name || ''
        }))
      }
    }
  } else {
    events.value.push({
      id: Date.now(),
      ...eventForm,
      characters: eventForm.characterIds.map(id => ({
        id,
        name: characters.value.find(c => c.id === id)?.name || ''
      }))
    } as TimelineEvent)
  }

  showEditDialog.value = false
  ElMessage.success('保存成功')
}

onMounted(() => {
  characters.value = [
    { id: 1, name: '林天' },
    { id: 2, name: '苏雪' }
  ]
})
</script>

<style lang="scss" scoped>
.novel-timeline {
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

  .timeline-container {
    &.horizontal {
      :deep(.el-timeline) {
        display: flex;
        overflow-x: auto;
        padding: 20px 0;

        .el-timeline-item {
          flex-shrink: 0;
          margin-right: 40px;
        }
      }
    }
  }

  .timeline-card {
    .card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;

      .event-title {
        font-weight: 600;
      }
    }

    .card-body {
      .event-description {
        margin: 0 0 12px;
        font-size: 14px;
        color: var(--el-text-color-regular);
        line-height: 1.6;
      }

      .event-characters,
      .event-location {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        margin-bottom: 8px;

        .label {
          color: var(--el-text-color-secondary);
        }

        .character-tag {
          margin-right: 4px;
        }
      }
    }

    .card-footer {
      display: flex;
      gap: 8px;
      padding-top: 12px;
      border-top: 1px dashed var(--el-border-color-light);
    }
  }
}
</style>
