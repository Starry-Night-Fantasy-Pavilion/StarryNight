<template>
  <div class="novel-volumes page-container">
    <div class="page-header">
      <div class="header-left">
        <h2>📑 卷管理</h2>
        <el-tag type="info">{{ volumes.length }} 卷</el-tag>
      </div>
      <div class="header-actions">
        <el-button @click="handleGenerateVolumes">
          <el-icon><MagicStick /></el-icon>
          AI生成分卷
        </el-button>
        <el-button type="primary" @click="handleCreateVolume">
          <el-icon><Plus /></el-icon>
          新建卷
        </el-button>
      </div>
    </div>

    <div class="page-content">
      <VolumeEditor
        v-if="selectedVolumeId"
        ref="volumeEditorRef"
        :volumes="volumes"
        :current-volume-id="selectedVolumeId"
        @update:currentVolumeId="handleVolumeChange"
        @save="handleVolumeSave"
        @ai-generate="handleAiGenerate"
        @ai-conflict="handleAiConflict"
        @ai-chapter="handleAiChapter"
        @generate-all-outlines="handleGenerateAllOutlines"
        @create-volume="handleCreateVolumeDialog"
      />

      <div v-else-if="volumes.length === 0" class="empty-state">
        <el-empty description="暂无分卷">
          <template #image>
            <el-icon class="empty-icon" :size="80"><FolderOpened /></el-icon>
          </template>
          <div class="empty-actions">
            <el-button type="primary" @click="handleCreateVolume">
              创建第一卷
            </el-button>
            <el-button type="success" @click="handleGenerateVolumes">
              <el-icon><MagicStick /></el-icon>
              AI生成分卷
            </el-button>
          </div>
        </el-empty>
      </div>

      <div v-else class="volume-grid">
        <el-card
          v-for="volume in volumes"
          :key="volume.id"
          class="volume-card"
          :class="{ 'is-selected': selectedVolumeId === volume.id }"
          shadow="hover"
          @click="handleSelectVolume(volume)"
        >
          <template #header>
            <div class="card-header">
              <span class="volume-title">{{ volume.title }}</span>
              <el-tag :type="volume.status === 1 ? 'success' : 'warning'" size="small">
                {{ volume.status === 1 ? '已完成' : '进行中' }}
              </el-tag>
            </div>
          </template>
          <div class="volume-info">
            <p class="volume-description">{{ volume.description || '暂无描述' }}</p>
            <div class="volume-stats">
              <div class="stat-item">
                <el-icon><Document /></el-icon>
                <span>{{ volume.chapterCount || 0 }} 章</span>
              </div>
              <div class="stat-item">
                <el-icon><Edit /></el-icon>
                <span>{{ formatWordCount(volume.wordCount) }}</span>
              </div>
            </div>
          </div>
          <div class="volume-actions">
            <el-button size="small" type="primary" @click.stop="handleEditVolume(volume)">
              编辑
            </el-button>
            <el-dropdown trigger="click" @command="(cmd) => handleCommand(cmd, volume)">
              <el-button size="small" @click.stop="">
                <el-icon><MoreFilled /></el-icon>
              </el-button>
              <template #dropdown>
                <el-dropdown-menu>
                  <el-dropdown-item command="outline">查看大纲</el-dropdown-item>
                  <el-dropdown-item command="chapters">章节列表</el-dropdown-item>
                  <el-dropdown-item command="duplicate">复制</el-dropdown-item>
                  <el-dropdown-item command="delete" divided style="color: var(--el-color-danger)">
                    删除
                  </el-dropdown-item>
                </el-dropdown-menu>
              </template>
            </el-dropdown>
          </div>
        </el-card>
      </div>
    </div>

    <el-dialog
      v-model="showVolumeDialog"
      :title="editingVolume ? '编辑卷' : '新建卷'"
      width="500px"
      destroy-on-close
    >
      <el-form ref="formRef" :model="volumeForm" :rules="rules" label-width="80px">
        <el-form-item label="卷标题" prop="title">
          <el-input v-model="volumeForm.title" placeholder="请输入卷标题" maxlength="100" show-word-limit />
        </el-form-item>
        <el-form-item label="描述">
          <el-input
            v-model="volumeForm.description"
            type="textarea"
            :rows="3"
            placeholder="请输入卷描述"
            maxlength="500"
            show-word-limit
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showVolumeDialog = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="handleSaveVolume">保存</el-button>
      </template>
    </el-dialog>

    <el-dialog
      v-model="showGenerateDialog"
      title="AI生成分卷"
      width="500px"
      destroy-on-close
    >
      <el-form :model="generateForm" label-width="100px">
        <el-form-item label="分卷数量">
          <el-input-number v-model="generateForm.count" :min="1" :max="12" />
        </el-form-item>
        <el-form-item label="卷标题模板">
          <el-input v-model="generateForm.template" placeholder="如：第X卷：{name}" />
        </el-form-item>
      </el-form>
      <p class="generate-tip">系统将根据您的大纲内容自动生成分卷建议</p>
      <template #footer>
        <el-button @click="showGenerateDialog = false">取消</el-button>
        <el-button type="primary" :loading="generating" @click="confirmGenerate">生成</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import type { FormInstance, FormRules } from 'element-plus'
import { MagicStick, Plus, FolderOpened, Document, Edit, MoreFilled } from '@element-plus/icons-vue'
import { listVolumes, createVolume, updateVolume, deleteVolume, generateVolumes } from '@/api/novel'
import type { NovelVolume } from '@/types/api'
import VolumeEditor from './components/VolumeEditor.vue'

const route = useRoute()
const router = useRouter()
const novelId = computed(() => route.params.id as string)

const loading = ref(false)
const saving = ref(false)
const generating = ref(false)
const volumes = ref<NovelVolume[]>([])
const showVolumeDialog = ref(false)
const showGenerateDialog = ref(false)
const selectedVolumeId = ref<number | undefined>(undefined)
const editingVolume = ref<NovelVolume | null>(null)

const volumeEditorRef = ref<InstanceType<typeof VolumeEditor> | null>(null)
const formRef = ref<FormInstance>()

const volumeForm = ref({
  title: '',
  description: ''
})

const generateForm = ref({
  count: 3,
  template: '第X卷'
})

const rules: FormRules = {
  title: [{ required: true, message: '请输入卷标题', trigger: 'blur' }]
}

function formatWordCount(count?: number): string {
  if (!count) return '0'
  if (count >= 10000) return `${(count / 10000).toFixed(1)}万`
  return count.toString()
}

async function loadVolumes() {
  if (!novelId.value) return

  loading.value = true
  try {
    const list = await listVolumes(Number(novelId.value))
    volumes.value = list || []
  } catch (error) {
    console.error('Load volumes failed:', error)
    ElMessage.error('加载卷列表失败')
  } finally {
    loading.value = false
  }
}

function handleCreateVolume() {
  editingVolume.value = null
  volumeForm.value = { title: '', description: '' }
  showVolumeDialog.value = true
}

function handleCreateVolumeDialog(data: { title: string; description: string }) {
  volumeForm.value = data
  showVolumeDialog.value = true
}

function handleSelectVolume(volume: NovelVolume) {
  selectedVolumeId.value = volume.id
}

function handleVolumeChange(volumeId: number) {
  selectedVolumeId.value = volumeId
}

function handleEditVolume(volume: NovelVolume) {
  editingVolume.value = volume
  volumeForm.value = {
    title: volume.title,
    description: volume.description || ''
  }
  showVolumeDialog.value = true
}

async function handleSaveVolume() {
  if (!formRef.value) return

  await formRef.value.validate(async (valid) => {
    if (!valid) return

    saving.value = true
    try {
      const data = {
        novelId: Number(novelId.value),
        title: volumeForm.value.title,
        description: volumeForm.value.description,
        volumeOrder: editingVolume.value?.volumeOrder || (volumes.value.length + 1)
      }

      if (editingVolume.value) {
        await updateVolume(editingVolume.value.id, data)
      } else {
        await createVolume(data)
      }

      ElMessage.success(editingVolume.value ? '更新成功' : '创建成功')
      showVolumeDialog.value = false
      loadVolumes()
    } catch (error) {
      ElMessage.error('保存失败')
    } finally {
      saving.value = false
    }
  })
}

function handleGenerateVolumes() {
  generateForm.value = { count: 3, template: '第X卷' }
  showGenerateDialog.value = true
}

async function confirmGenerate() {
  generating.value = true
  try {
    await generateVolumes(Number(novelId.value), generateForm.value.count)
    ElMessage.success('分卷生成成功')
    showGenerateDialog.value = false
    loadVolumes()
  } catch (error) {
    ElMessage.error('生成分卷失败')
  } finally {
    generating.value = false
  }
}

function handleVolumeSave(volume: NovelVolume, formData: any) {
  console.log('Volume save:', volume, formData)
}

function handleAiGenerate() {
  ElMessage.info('AI生成功能开发中')
}

function handleAiConflict(currentConflict: string) {
  console.log('AI conflict:', currentConflict)
}

function handleAiChapter(chapterIndex: number) {
  ElMessage.info(`AI辅助章节 ${chapterIndex + 1}`)
}

function handleGenerateAllOutlines() {
  ElMessage.info('批量生成章节大纲功能开发中')
}

function handleCommand(command: string, volume: NovelVolume) {
  switch (command) {
    case 'outline':
      selectedVolumeId.value = volume.id
      break
    case 'chapters':
      router.push(`/novel/${novelId.value}/chapters?volumeId=${volume.id}`)
      break
    case 'duplicate':
      handleDuplicateVolume(volume)
      break
    case 'delete':
      handleDeleteVolume(volume)
      break
  }
}

async function handleDuplicateVolume(volume: NovelVolume) {
  try {
    await createVolume({
      novelId: Number(novelId.value),
      title: `${volume.title} (副本)`,
      description: volume.description,
      volumeOrder: volumes.value.length + 1
    })
    ElMessage.success('复制成功')
    loadVolumes()
  } catch (error) {
    ElMessage.error('复制失败')
  }
}

async function handleDeleteVolume(volume: NovelVolume) {
  try {
    await ElMessageBox.confirm(
      '确定要删除这个卷吗？删除后卷内的章节将移至作品根目录。',
      '提示',
      { type: 'warning' }
    )

    await deleteVolume(volume.id)
    ElMessage.success('删除成功')
    if (selectedVolumeId.value === volume.id) {
      selectedVolumeId.value = undefined
    }
    loadVolumes()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('删除失败')
    }
  }
}

onMounted(() => {
  loadVolumes()
})
</script>

<style lang="scss" scoped>
.novel-volumes {
  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;

    .header-left {
      display: flex;
      align-items: center;
      gap: 12px;

      h2 {
        margin: 0;
        font-size: 20px;
      }
    }

    .header-actions {
      display: flex;
      gap: 12px;
    }
  }

  .empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 80px 20px;
    background: var(--el-bg-color);
    border-radius: 12px;

    .empty-icon {
      color: var(--el-text-color-placeholder);
    }

    .empty-actions {
      display: flex;
      gap: 16px;
      margin-top: 24px;
    }
  }

  .volume-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 16px;
  }

  .volume-card {
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
      transform: translateY(-2px);
    }

    &.is-selected {
      border-color: var(--el-color-primary);
      background: var(--el-color-primary-light-9);
    }

    .card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;

      .volume-title {
        font-weight: 600;
        font-size: 15px;
      }
    }

    .volume-info {
      .volume-description {
        margin: 0 0 12px;
        font-size: 13px;
        color: var(--el-text-color-secondary);
        min-height: 40px;
        line-height: 1.5;
      }

      .volume-stats {
        display: flex;
        gap: 24px;
        margin-bottom: 12px;

        .stat-item {
          display: flex;
          align-items: center;
          gap: 6px;
          font-size: 13px;
          color: var(--el-text-color-secondary);
        }
      }
    }

    .volume-actions {
      display: flex;
      gap: 8px;
      padding-top: 12px;
      border-top: 1px dashed var(--el-border-color-light);
    }
  }

  .generate-tip {
    color: var(--el-text-color-secondary);
    font-size: 13px;
    margin-top: 8px;
  }
}
</style>
