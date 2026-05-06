<template>
  <div class="style-expand page-container">
    <div class="page-header">
      <h1>风格扩写</h1>
      <el-button type="primary" @click="showSampleDialog = true">上传样本</el-button>
    </div>

    <div class="page-content">
      <el-row :gutter="24">
        <!-- 左侧：样本管琀-->
        <el-col :xs="24" :lg="10">
          <el-card class="samples-card">
            <template #header>
              <div class="card-header">
                <span>风格样本</span>
                <el-button text type="primary" size="small" @click="showSampleDialog = true">+ 新增</el-button>
              </div>
            </template>

            <div v-if="samples.length > 0" class="sample-list">
              <div
                v-for="sample in samples"
                :key="sample.id"
                class="sample-item"
                :class="{ active: activeSampleId === sample.id }"
                @click="selectSample(sample)"
              >
                <div class="sample-info">
                  <h4 class="sample-name">{{ sample.name }}</h4>
                  <span class="sample-meta">{{ sample.styleLabel || '未分类' }} · {{ sample.wordCount || 0 }} 字</span>
                </div>
                <el-button
                  text
                  type="danger"
                  size="small"
                  @click.stop="deleteSample(sample)"
                >删除</el-button>
              </div>
            </div>

            <el-empty v-else description="暂无样本，请上传风格样本" :image-size="80" />
          </el-card>
        </el-col>

        <!-- 右侧：扩写区埀-->
        <el-col :xs="24" :lg="14">
          <el-card class="expand-card">
            <template #header>
              <div class="card-header">
                <span>扩写内容</span>
                <div class="expand-actions" v-if="activeSample">
                  <el-tag size="small" type="success" effect="plain">
                    已选择：{{ activeSample.name }}
                  </el-tag>
                </div>
              </div>
            </template>

            <el-form label-position="top" size="large">
              <el-form-item label="输入段落">
                <el-input
                  v-model="inputText"
                  type="textarea"
                  :rows="6"
                  placeholder="粘贴需要扩写的段落..."
                  maxlength="5000"
                  show-word-limit
                />
              </el-form-item>
              <el-form-item label="扩写风格">
                <el-select v-model="expandStyle" placeholder="选择扩写风格" style="width: 100%">
                  <el-option label="学习样本风格" value="sample" />
                  <el-option label="详细描写" value="detailed" />
                  <el-option label="简洁精炼" value="concise" />
                  <el-option label="情绪渲染" value="emotional" />
                  <el-option label="电影感画面" value="cinematic" />
                </el-select>
              </el-form-item>
              <el-form-item label="扩写强度">
                <el-slider
                  v-model="expandIntensity"
                  :min="1"
                  :max="5"
                  :marks="{ 1: '轻度', 3: '适中', 5: '大幅' }"
                  show-stops
                />
              </el-form-item>
              <el-form-item>
                <el-button
                  type="primary"
                  :loading="expanding"
                  :disabled="!inputText"
                  @click="startExpand"
                  size="large"
                >
                  开始扩冀                </el-button>
                <el-button
                  :disabled="!resultText"
                  @click="copyResult"
                  size="large"
                >复制结果</el-button>
              </el-form-item>
            </el-form>

            <!-- 扩写结果 -->
            <div v-if="resultText" class="result-section">
              <el-divider />
              <h3 class="result-title">扩写结果</h3>
              <div class="result-content">
                <p>{{ resultText }}</p>
              </div>
              <div class="result-meta">
                <span>扩写前：{{ inputText.length }} 字</span>
                <span>扩写后：{{ resultText.length }} 字</span>
                <span>风格：{{ styleLabel }}</span>
              </div>
            </div>
          </el-card>
        </el-col>
      </el-row>
    </div>

    <!-- 上传样本弹窗 -->
    <el-dialog v-model="showSampleDialog" title="上传风格样本" width="520px">
      <el-form :model="sampleForm" label-position="top" size="large">
        <el-form-item label="样本名称" required>
          <el-input v-model="sampleForm.name" placeholder="如：我的仙侠文风" maxlength="30" show-word-limit />
        </el-form-item>
        <el-form-item label="样本内容" required>
          <el-input
            v-model="sampleForm.content"
            type="textarea"
            :rows="8"
            placeholder="粘贴一段能代表你写作风格的文字（建议 200-1000 字）..."
            maxlength="10000"
            show-word-limit
          />
        </el-form-item>
        <el-form-item label="风格标签（可选）">
          <el-input v-model="sampleForm.styleLabel" placeholder="如：仙侠、轻松、细腻" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showSampleDialog = false">取消</el-button>
        <el-button type="primary" :loading="uploading" @click="submitSample">上传并分析</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { get, post, del } from '@/utils/request'

interface StyleSample {
  id: number
  name: string
  content?: string
  styleLabel?: string
  wordCount?: number
  createdAt: string
}

const samples = ref<StyleSample[]>([])
const activeSampleId = ref<number | null>(null)
const activeSample = computed(() => samples.value.find(s => s.id === activeSampleId.value) || null)
const inputText = ref('')
const expandStyle = ref('sample')
const expandIntensity = ref(3)
const resultText = ref('')
const expanding = ref(false)
const showSampleDialog = ref(false)
const uploading = ref(false)

const sampleForm = reactive({
  name: '',
  content: '',
  styleLabel: ''
})

const styleLabel = computed(() => {
  const map: Record<string, string> = {
    sample: '学习样本风格',
    detailed: '详细描写',
    concise: '简洁精炼',
    emotional: '情绪渲染',
    cinematic: '电影感画面',
  }
  return map[expandStyle.value] || expandStyle.value
})

async function loadSamples() {
  try {
    const res = await get<any>('/style-samples/list')
    samples.value = res || []
  } catch {
    samples.value = []
  }
}

function selectSample(sample: StyleSample) {
  activeSampleId.value = sample.id
  ElMessage.success(`已选择样本：${sample.name}`)
}

async function submitSample() {
  if (!sampleForm.name.trim()) {
    ElMessage.warning('请输入样本名称')
    return
  }
  if (!sampleForm.content.trim()) {
    ElMessage.warning('请输入样本内容')
    return
  }
  uploading.value = true
  try {
    await post('/style-samples', {
      name: sampleForm.name.trim(),
      content: sampleForm.content.trim(),
      styleLabel: sampleForm.styleLabel.trim() || undefined
    })
    ElMessage.success('样本上传成功，正在分析风格特征')
    showSampleDialog.value = false
    sampleForm.name = ''
    sampleForm.content = ''
    sampleForm.styleLabel = ''
    await loadSamples()
  } catch {
    ElMessage.error('上传失败')
  } finally {
    uploading.value = false
  }
}

async function startExpand() {
  const text = inputText.value.trim()
  if (!text) {
    ElMessage.warning('请输入需要扩写的段落')
    return
  }
  expanding.value = true
  try {
    const res = await post<any>('/style-expand', {
      text,
      style: expandStyle.value,
      intensity: expandIntensity.value,
      sampleId: expandStyle.value === 'sample' ? activeSampleId.value : undefined
    })
    resultText.value = res?.result || res?.text || '扩写完成，但未返回结果'
    ElMessage.success('扩写完成')
  } catch {
    ElMessage.error('扩写失败，请重试')
  } finally {
    expanding.value = false
  }
}

function copyResult() {
  if (resultText.value) {
    navigator.clipboard.writeText(resultText.value).then(() => {
      ElMessage.success('已复制到剪贴板')
    })
  }
}

async function deleteSample(sample: StyleSample) {
  try {
    await del(`/style-samples/${sample.id}`)
    if (activeSampleId.value === sample.id) {
      activeSampleId.value = null
    }
    ElMessage.success('已删除')
    await loadSamples()
  } catch {
    ElMessage.error('删除失败')
  }
}

onMounted(() => {
  loadSamples()
})
</script>

<style lang="scss" scoped>
.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: $space-lg $space-xl;
  background: $bg-white;
  border-bottom: 1px solid $border-color;

  h1 {
    font-size: $font-size-xl;
    font-weight: 600;
  }
}

.page-content {
  padding: $space-xl;
  max-width: 1400px;
  margin: 0 auto;
}

.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.samples-card {
  margin-bottom: $space-lg;
}

.sample-list {
  display: flex;
  flex-direction: column;
  gap: $space-sm;
}

.sample-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: $space-md;
  border: 1px solid $border-color;
  border-radius: $border-radius;
  cursor: pointer;
  transition: all 0.2s;

  &:hover {
    border-color: $primary-color;
    background: rgba($primary-color, 0.03);
  }

  &.active {
    border-color: $primary-color;
    background: rgba($primary-color, 0.06);
  }
}

.sample-info {
  flex: 1;
  min-width: 0;
}

.sample-name {
  font-size: $font-size-sm;
  font-weight: 600;
  margin-bottom: $space-xs;
}

.sample-meta {
  font-size: $font-size-xs;
  color: $text-muted;
}

.expand-card {
  margin-bottom: $space-lg;
}

.expand-actions {
  display: flex;
  align-items: center;
  gap: $space-sm;
}

.result-section {
  margin-top: $space-md;
}

.result-title {
  font-size: $font-size-md;
  font-weight: 600;
  margin-bottom: $space-md;
}

.result-content {
  background: $bg-gray;
  border: 1px solid $border-color;
  border-radius: $border-radius;
  padding: $space-lg;
  font-size: $font-size-sm;
  line-height: 1.8;
  white-space: pre-wrap;
}

.result-meta {
  display: flex;
  gap: $space-lg;
  margin-top: $space-md;
  font-size: $font-size-xs;
  color: $text-muted;
}

@media (max-width: 768px) {
  .page-content {
    padding: $space-md;
  }

  .result-meta {
    flex-direction: column;
    gap: $space-xs;
  }
}
</style>
