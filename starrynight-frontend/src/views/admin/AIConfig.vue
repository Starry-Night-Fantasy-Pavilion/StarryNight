<template>
  <div class="admin-ai-config page-container">
    <div class="page-header">
      <h1>AI 配置中心</h1>
    </div>

    <el-tabs>
      <el-tab-pane label="模型管理">
        <el-card>
          <template #header>
            <div class="header-actions">
              <el-select
                v-model="channelFilter"
                clearable
                placeholder="按计费渠道筛选"
                filterable
                style="width: 260px"
              >
                <el-option
                  v-for="c in billingChannels"
                  :key="c.id"
                  :label="`${c.channelName}（${c.channelCode}）`"
                  :value="c.id!"
                />
              </el-select>
              <el-button @click="loadModels">查询</el-button>
              <el-button type="primary" @click="openModelDialog()">新增模型</el-button>
            </div>
          </template>

          <el-alert type="info" :closable="false" show-icon class="model-channel-alert">
            模型与
            <router-link :to="`${adminBase}/billing`">计费配置 → 渠道管理</router-link>
            中的渠道一一绑定；筛选与新建前请先在「渠道管理」创建渠道。
          </el-alert>

          <el-table :data="models" v-loading="loadingModels" stripe>
            <el-table-column prop="modelName" label="模型名称" min-width="160" />
            <el-table-column prop="modelCode" label="模型编码" min-width="180" />
            <el-table-column label="计费渠道" min-width="200">
              <template #default="{ row }">
                <span v-if="row.channelName || row.channelCode">
                  {{ row.channelName || '—' }}
                  <span class="text-muted">（{{ row.channelCode || row.billingChannelId }}）</span>
                </span>
                <el-tag v-else type="warning" size="small">未绑定</el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="provider" label="厂商" width="120" />
            <el-table-column prop="sortOrder" label="排序" width="80" />
            <el-table-column label="状态" width="100">
              <template #default="{ row }">
                <el-tag :type="row.enabled === 1 ? 'success' : 'info'">
                  {{ row.enabled === 1 ? '启用' : '禁用' }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column label="操作" width="160" fixed="right">
              <template #default="{ row }">
                <el-button link type="primary" @click="openModelDialog(row)">编辑</el-button>
                <el-button link type="danger" @click="handleDeleteModel(row.id)">删除</el-button>
              </template>
            </el-table-column>
          </el-table>
        </el-card>
      </el-tab-pane>

      <el-tab-pane label="模板管理">
        <el-card>
          <template #header>
            <div class="header-actions">
              <el-select v-model="templateTypeFilter" clearable placeholder="按类型筛选" style="width: 160px">
                <el-option label="剧情" value="PLOT" />
                <el-option label="角色" value="CHARACTER" />
                <el-option label="文风" value="STYLE" />
                <el-option label="世界观" value="WORLD" />
                <el-option label="冲突" value="CONFLICT" />
              </el-select>
              <el-button @click="loadTemplates">查询</el-button>
              <el-button type="primary" @click="openTemplateDialog()">新增模板</el-button>
            </div>
          </template>

          <el-table :data="templates" v-loading="loadingTemplates" stripe>
            <el-table-column prop="name" label="模板名称" min-width="160" />
            <el-table-column prop="type" label="类型" width="120">
              <template #default="{ row }">
                <el-tag>{{ templateTypeLabel(row.type) }}</el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="description" label="描述" min-width="200" />
            <el-table-column prop="usageCount" label="使用次数" width="100" align="center" />
            <el-table-column label="状态" width="100">
              <template #default="{ row }">
                <el-tag :type="row.enabled === 1 ? 'success' : 'info'">
                  {{ row.enabled === 1 ? '启用' : '禁用' }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column label="操作" width="160" fixed="right">
              <template #default="{ row }">
                <el-button link type="primary" @click="openTemplateDialog(row)">编辑</el-button>
                <el-button link type="danger" @click="handleDeleteTemplate(row.id)">删除</el-button>
              </template>
            </el-table-column>
          </el-table>
        </el-card>
      </el-tab-pane>

      <el-tab-pane label="生成参数">
        <el-card>
          <template #header>
            <div class="header-actions">
              <h3>AI 生成参数配置</h3>
              <el-button type="primary" @click="saveGenerationParams">保存配置</el-button>
            </div>
          </template>

          <el-form :model="generationParams" label-width="140px" class="params-form">
            <el-form-item label="温度">
              <el-slider v-model="generationParams.temperature" :min="0" :max="2" :step="0.1" show-input />
              <div class="param-hint">控制随机性，较低值更确定，较高值更有创意</div>
            </el-form-item>

            <el-form-item label="最大令牌数">
              <el-input-number v-model="generationParams.maxTokens" :min="100" :max="8000" :step="100" />
              <div class="param-hint">单次生成的最大字符数限制</div>
            </el-form-item>

            <el-form-item label="核采样（Top P）">
              <el-slider v-model="generationParams.topP" :min="0" :max="1" :step="0.05" show-input />
              <div class="param-hint">控制词元采样的多样性</div>
            </el-form-item>

            <el-form-item label="频率惩罚">
              <el-slider v-model="generationParams.frequencyPenalty" :min="-2" :max="2" :step="0.1" show-input />
              <div class="param-hint">降低重复词元出现的频率</div>
            </el-form-item>

            <el-form-item label="存在惩罚">
              <el-slider v-model="generationParams.presencePenalty" :min="-2" :max="2" :step="0.1" show-input />
              <div class="param-hint">鼓励引入新的主题</div>
            </el-form-item>

            <el-divider />

            <el-form-item label="大纲生成温度">
              <el-slider v-model="generationParams.outlineTemperature" :min="0" :max="2" :step="0.1" show-input />
            </el-form-item>

            <el-form-item label="正文生成温度">
              <el-slider v-model="generationParams.contentTemperature" :min="0" :max="2" :step="0.1" show-input />
            </el-form-item>

            <el-form-item label="对话生成温度">
              <el-slider v-model="generationParams.chatTemperature" :min="0" :max="2" :step="0.1" show-input />
            </el-form-item>

            <el-divider />

            <el-form-item label="启用流式输出">
              <el-switch v-model="generationParams.enableStreaming" />
              <div class="param-hint">开启后AI生成内容将实时显示</div>
            </el-form-item>

            <el-form-item label="流式输出间隔 (ms)">
              <el-input-number v-model="generationParams.streamInterval" :min="50" :max="500" :step="10" />
              <div class="param-hint">流式输出时每个token的间隔时间</div>
            </el-form-item>
          </el-form>
        </el-card>
      </el-tab-pane>

      <el-tab-pane label="敏感词管理">
        <el-card>
          <template #header>
            <div class="header-actions">
              <el-select v-model="wordLevelFilter" clearable placeholder="按级别筛选" style="width: 160px">
                <el-option label="普通" :value="1" />
                <el-option label="高危" :value="2" />
              </el-select>
              <el-button @click="loadWords">查询</el-button>
              <el-button type="primary" @click="openWordDialog()">新增敏感词</el-button>
            </div>
          </template>

          <el-table :data="words" v-loading="loadingWords" stripe>
            <el-table-column prop="word" label="敏感词" min-width="220" />
            <el-table-column label="级别" width="120">
              <template #default="{ row }">{{ row.level === 2 ? '高危' : '普通' }}</template>
            </el-table-column>
            <el-table-column label="状态" width="100">
              <template #default="{ row }">
                <el-tag :type="row.enabled === 1 ? 'success' : 'info'">
                  {{ row.enabled === 1 ? '启用' : '禁用' }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column label="操作" width="160" fixed="right">
              <template #default="{ row }">
                <el-button link type="primary" @click="openWordDialog(row)">编辑</el-button>
                <el-button link type="danger" @click="handleDeleteWord(row.id)">删除</el-button>
              </template>
            </el-table-column>
          </el-table>
        </el-card>
      </el-tab-pane>
    </el-tabs>

    <el-dialog v-model="modelDialogVisible" title="模型配置" width="520px">
      <el-form label-width="100px">
        <el-form-item label="计费渠道" required>
          <el-select
            v-model="modelForm.billingChannelId"
            placeholder="请选择渠道（来自计费配置）"
            filterable
            style="width: 100%"
          >
            <el-option
              v-for="c in billingChannels"
              :key="c.id"
              :label="`${c.channelName}（${c.channelCode}）`"
              :value="c.id!"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="模型名称">
          <el-input v-model="modelForm.modelName" />
        </el-form-item>
        <el-form-item label="模型编码">
          <el-input v-model="modelForm.modelCode" />
        </el-form-item>
        <el-form-item label="厂商">
          <el-input v-model="modelForm.provider" placeholder="可空，默认取渠道名称" />
        </el-form-item>
        <el-form-item label="状态">
          <el-radio-group v-model="modelForm.enabled">
            <el-radio :value="1">启用</el-radio>
            <el-radio :value="0">禁用</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="modelForm.sortOrder" :min="0" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="modelDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitModel">保存</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="wordDialogVisible" title="敏感词配置" width="460px">
      <el-form label-width="90px">
        <el-form-item label="敏感词">
          <el-input v-model="wordForm.word" />
        </el-form-item>
        <el-form-item label="级别">
          <el-radio-group v-model="wordForm.level">
            <el-radio :value="1">普通</el-radio>
            <el-radio :value="2">高危</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="状态">
          <el-radio-group v-model="wordForm.enabled">
            <el-radio :value="1">启用</el-radio>
            <el-radio :value="0">禁用</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="wordDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitWord">保存</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="templateDialogVisible" title="模板配置" width="640px" @close="resetTemplateForm">
      <el-form :model="templateForm" label-position="top">
        <el-form-item label="模板名称" required>
          <el-input v-model="templateForm.name" placeholder="请输入模板名称" maxlength="50" show-word-limit />
        </el-form-item>
        <el-form-item label="模板类型" required>
          <el-select v-model="templateForm.type" placeholder="请选择模板类型" style="width: 100%">
            <el-option label="剧情模板" value="PLOT" />
            <el-option label="角色模板" value="CHARACTER" />
            <el-option label="文风模板" value="STYLE" />
            <el-option label="世界观模板" value="WORLD" />
            <el-option label="冲突模板" value="CONFLICT" />
          </el-select>
        </el-form-item>
        <el-form-item label="模板描述">
          <el-input v-model="templateForm.description" type="textarea" :rows="2" placeholder="请输入模板描述" maxlength="200" show-word-limit />
        </el-form-item>
        <el-form-item label="模板内容" required>
          <el-input
            v-model="templateForm.content"
            type="textarea"
            :rows="10"
            placeholder="请输入模板内容，支持变量占位符：{{title}}、{{genre}}、{{style}} 等"
          />
        </el-form-item>
        <el-form-item label="变量说明">
          <div class="variable-hint">
            <p>支持的变量占位符：</p>
            <code>{{title}}</code> - 作品标题
            <code>{{genre}}</code> - 题材类型
            <code>{{style}}</code> - 风格设定
            <code>{{core_idea}}</code> - 核心创意
            <code>{{character}}</code> - 角色信息
            <code>{{world}}</code> - 世界观设定
          </div>
        </el-form-item>
        <el-form-item label="状态">
          <el-radio-group v-model="templateForm.enabled">
            <el-radio :value="1">启用</el-radio>
            <el-radio :value="0">禁用</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="templateDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitTemplate">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import type { AiModelItem, AiSensitiveWordItem } from '@/types/api'
import {
  createAiModel,
  createAiSensitiveWord,
  deleteAiModel,
  deleteAiSensitiveWord,
  listAiModels,
  listAiSensitiveWords,
  updateAiModel,
  updateAiSensitiveWord,
  listAiTemplates,
  createAiTemplate,
  updateAiTemplate,
  deleteAiTemplate,
  getGenerationParams,
  saveGenerationParams as saveGenParams
} from '@/api/aiConfig'
import { listChannels, type ChannelDTO } from '@/api/billingAdmin'
import { ADMIN_CONSOLE_BASE_PATH } from '@/config/portal'

const adminBase = ADMIN_CONSOLE_BASE_PATH
const billingChannels = ref<ChannelDTO[]>([])
const channelFilter = ref<number | undefined>(undefined)
const wordLevelFilter = ref<number>()
const templateTypeFilter = ref<string>()
const loadingModels = ref(false)
const loadingWords = ref(false)
const loadingTemplates = ref(false)
const models = ref<AiModelItem[]>([])
const words = ref<AiSensitiveWordItem[]>([])
const templates = ref<any[]>([])

const modelDialogVisible = ref(false)
const wordDialogVisible = ref(false)
const templateDialogVisible = ref(false)

const modelForm = reactive<Partial<AiModelItem> & { billingChannelId?: number }>({
  id: undefined,
  modelCode: '',
  modelName: '',
  provider: '',
  billingChannelId: undefined,
  enabled: 1,
  sortOrder: 0
})

const wordForm = reactive<AiSensitiveWordItem>({
  id: undefined,
  word: '',
  level: 1,
  enabled: 1
})

const templateForm = reactive({
  id: 0,
  name: '',
  type: 'PLOT',
  description: '',
  content: '',
  enabled: 1
})

const generationParams = reactive({
  temperature: 0.7,
  maxTokens: 2000,
  topP: 0.9,
  frequencyPenalty: 0.0,
  presencePenalty: 0.0,
  outlineTemperature: 0.6,
  contentTemperature: 0.75,
  chatTemperature: 0.8,
  enableStreaming: true,
  streamInterval: 100
})

function resetModelForm() {
  Object.assign(modelForm, {
    id: undefined,
    modelCode: '',
    modelName: '',
    provider: '',
    billingChannelId: undefined,
    enabled: 1,
    sortOrder: 0
  })
}

function resetWordForm() {
  Object.assign(wordForm, {
    id: undefined,
    word: '',
    level: 1,
    enabled: 1
  })
}

async function loadBillingChannels() {
  try {
    billingChannels.value = (await listChannels(undefined, undefined)) ?? []
  } catch {
    billingChannels.value = []
  }
}

async function loadModels() {
  loadingModels.value = true
  try {
    models.value = (await listAiModels(channelFilter.value)) ?? []
  } finally {
    loadingModels.value = false
  }
}

async function loadWords() {
  loadingWords.value = true
  try {
    words.value = (await listAiSensitiveWords(wordLevelFilter.value)) ?? []
  } finally {
    loadingWords.value = false
  }
}

function openModelDialog(row?: AiModelItem) {
  if (row) {
    Object.assign(modelForm, row)
  } else {
    resetModelForm()
  }
  modelDialogVisible.value = true
}

function openWordDialog(row?: AiSensitiveWordItem) {
  if (row) {
    Object.assign(wordForm, row)
  } else {
    resetWordForm()
  }
  wordDialogVisible.value = true
}

async function submitModel() {
  if (modelForm.billingChannelId == null) {
    ElMessage.warning('请选择计费渠道')
    return
  }
  const payload = modelForm as AiModelItem
  if (modelForm.id) {
    await updateAiModel(modelForm.id, payload)
    ElMessage.success('模型已更新')
  } else {
    await createAiModel(payload)
    ElMessage.success('模型已创建')
  }
  modelDialogVisible.value = false
  await loadModels()
}

async function submitWord() {
  if (wordForm.id) {
    await updateAiSensitiveWord(wordForm.id, wordForm)
    ElMessage.success('敏感词已更新')
  } else {
    await createAiSensitiveWord(wordForm)
    ElMessage.success('敏感词已创建')
  }
  wordDialogVisible.value = false
  await loadWords()
}

async function handleDeleteModel(id?: number) {
  if (!id) return
  await ElMessageBox.confirm('确认删除该模型吗？', '删除确认', { type: 'warning' })
  await deleteAiModel(id)
  ElMessage.success('模型已删除')
  await loadModels()
}

async function handleDeleteWord(id?: number) {
  if (!id) return
  await ElMessageBox.confirm('确认删除该敏感词吗？', '删除确认', { type: 'warning' })
  await deleteAiSensitiveWord(id)
  ElMessage.success('敏感词已删除')
  await loadWords()
}

function templateTypeLabel(type: string): string {
  const labels: Record<string, string> = {
    PLOT: '剧情',
    CHARACTER: '角色',
    STYLE: '文风',
    WORLD: '世界观',
    CONFLICT: '冲突'
  }
  return labels[type] || type
}

async function loadTemplates() {
  loadingTemplates.value = true
  try {
    const res = await listAiTemplates(templateTypeFilter.value)
    templates.value = Array.isArray(res) ? res : []
  } catch {
    ElMessage.error('加载模板列表失败')
  } finally {
    loadingTemplates.value = false
  }
}

function openTemplateDialog(row?: any) {
  if (row) {
    Object.assign(templateForm, row)
  } else {
    resetTemplateForm()
  }
  templateDialogVisible.value = true
}

function resetTemplateForm() {
  Object.assign(templateForm, {
    id: 0,
    name: '',
    type: 'PLOT',
    description: '',
    content: '',
    enabled: 1
  })
}

async function submitTemplate() {
  if (!templateForm.name.trim()) {
    ElMessage.warning('请输入模板名称')
    return
  }
  if (!templateForm.content.trim()) {
    ElMessage.warning('请输入模板内容')
    return
  }

  try {
    if (templateForm.id) {
      await updateAiTemplate(templateForm.id, templateForm)
      ElMessage.success('模板已更新')
    } else {
      await createAiTemplate(templateForm)
      ElMessage.success('模板已创建')
    }
    templateDialogVisible.value = false
    await loadTemplates()
  } catch {
    ElMessage.error('保存失败')
  }
}

async function handleDeleteTemplate(id?: number) {
  if (!id) return
  await ElMessageBox.confirm('确认删除该模板吗？', '删除确认', { type: 'warning' })
  try {
    await deleteAiTemplate(id)
    ElMessage.success('模板已删除')
    await loadTemplates()
  } catch {
    ElMessage.error('删除失败')
  }
}

async function saveGenerationParams() {
  try {
    await saveGenParams(generationParams)
    ElMessage.success('生成参数已保存')
  } catch {
    ElMessage.error('保存失败')
  }
}

async function loadGenerationParams() {
  try {
    const res = await getGenerationParams()
    if (res && typeof res === 'object') {
      Object.assign(generationParams, res)
    }
  } catch {
    // 使用默认参数
  }
}

void loadBillingChannels().then(() => {
  void loadModels()
})
loadWords()
loadTemplates()
loadGenerationParams()
</script>

<style lang="scss" scoped>
.model-channel-alert {
  margin-bottom: $space-md;
}

.text-muted {
  color: $text-muted;
  font-size: $font-size-sm;
}

.header-actions {
  display: flex;
  gap: $space-md;
  align-items: center;
  flex-wrap: wrap;

  h3 {
    margin: 0;
    font-size: $font-size-lg;
    font-weight: 700;
    color: $text-primary;
    letter-spacing: -0.01em;
  }
}

.params-form {
  max-width: 700px;

  .param-hint {
    font-size: $font-size-xs;
    color: $text-muted;
    margin-top: $space-xs;
    line-height: 1.5;
  }
}

.variable-hint {
  background: rgba(99, 102, 241, 0.06);
  padding: $space-md;
  border-radius: $radius-sm;
  font-size: $font-size-sm;
  color: $text-secondary;
  border: 1px solid $border-subtle;

  p {
    margin: 0 0 $space-sm 0;
    font-weight: 600;
    color: $text-primary;
  }

  code {
    background: $primary-ghost;
    padding: 2px 8px;
    border-radius: $radius-xs;
    margin-right: $space-md;
    color: $primary-light;
    font-family: 'SF Mono', 'Fira Code', 'Cascadia Code', Consolas, monospace;
    font-size: 12px;
  }
}
</style>
