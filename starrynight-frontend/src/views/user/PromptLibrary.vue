<template>
  <div class="prompt-library page-container">
    <div class="page-header">
      <h1>提示词库</h1>
      <el-button type="primary" @click="showDialog = true">新建提示词</el-button>
    </div>

    <el-card class="filter-card">
      <div class="category-tabs">
        <el-radio-group v-model="activeCategory" @change="loadPrompts">
          <el-radio-button value="">全部分类</el-radio-button>
          <el-radio-button v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</el-radio-button>
        </el-radio-group>
        <el-input
          v-model="keyword"
          placeholder="搜索提示词…"
          clearable
          style="width: 220px"
          @clear="loadPrompts"
          @keyup.enter="loadPrompts"
        />
      </div>
    </el-card>

    <div v-if="!loading && prompts.length > 0" class="prompt-grid">
      <el-card
        v-for="item in prompts"
        :key="item.id"
        class="prompt-card"
        shadow="hover"
      >
        <div class="prompt-header">
          <span class="tag">{{ item.category }}</span>
          <span v-if="item.isBuiltin" class="builtin-tag">内置</span>
        </div>
        <h4 class="prompt-title">{{ item.title || item.name }}</h4>
        <p class="prompt-content">{{ item.content || item.description || item.promptTemplate }}</p>
        <div class="prompt-actions">
          <el-button text type="primary" size="small" @click="viewPrompt(item)">详情</el-button>
          <el-button text type="success" size="small" @click="applyPrompt(item)">一键使用</el-button>
          <el-popconfirm v-if="!item.isBuiltin" title="确定删除？" @confirm="deletePrompt(item)">
            <template #reference>
              <el-button text type="danger" size="small">删除</el-button>
            </template>
          </el-popconfirm>
        </div>
      </el-card>
    </div>

    <el-empty v-if="!loading && prompts.length === 0" description="暂无提示词，点击右上角新建" />

    <div class="pagination-wrapper" v-if="total > 0">
      <el-pagination
        v-model:current-page="page"
        v-model:page-size="pageSize"
        :total="total"
        :page-sizes="[12, 24, 48]"
        layout="total, sizes, prev, pager, next"
        @change="loadPrompts"
      />
    </div>

    <el-dialog v-model="showDialog" :title="editingPrompt ? '编辑提示词' : '新建提示词'" width="560px">
      <el-form :model="form" label-position="top" size="large">
        <el-form-item label="标题" required>
          <el-input v-model="form.title" placeholder="输入提示词标题" maxlength="50" show-word-limit />
        </el-form-item>
        <el-form-item label="分类" required>
          <el-select v-model="form.category" placeholder="选择分类" style="width: 100%">
            <el-option v-for="cat in categories" :key="cat" :label="cat" :value="cat" />
          </el-select>
        </el-form-item>
        <el-form-item label="描述">
          <el-input v-model="form.description" type="textarea" :rows="2" placeholder="描述提示词用途…" />
        </el-form-item>
        <el-form-item label="提示词内容" required>
          <el-input v-model="form.content" type="textarea" :rows="6" placeholder="输入提示词内容，支持变量占位 {{variable_name}}" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showDialog = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="submitPrompt">保存</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="showApplyDialog" title="使用提示词" width="520px">
      <template v-if="currentPrompt">
        <p class="apply-title">{{ currentPrompt.title || currentPrompt.name }}</p>
        <p class="apply-desc">{{ currentPrompt.description }}</p>
        <el-divider />
        <div v-if="currentPrompt.variables && currentPrompt.variables.length > 0" class="variable-form">
          <el-form :model="variableForm" label-position="top">
            <el-form-item v-for="v in currentPrompt.variables" :key="v.name" :label="v.description || v.name">
              <el-input v-model="variableForm[v.name]" :placeholder="v.description || v.name" />
            </el-form-item>
          </el-form>
        </div>
        <div v-else class="prompt-preview">
          <pre>{{ currentPrompt.content || currentPrompt.promptTemplate }}</pre>
        </div>
      </template>
      <template #footer>
        <el-button @click="showApplyDialog = false">取消</el-button>
        <el-button @click="copyAppliedPrompt">复制到剪贴板</el-button>
        <el-button type="success" @click="applyToEditor">应用到当前编辑器</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { listPrompts, createPrompt, deletePrompt as deletePromptApi, getPrompt, applyPrompt } from '@/api/prompt'
import { setEditorPrompt } from '@/utils/editorPrompt'

interface PromptItem {
  id: number
  title?: string
  name?: string
  content?: string
  promptTemplate?: string
  category: string
  description?: string
  variables?: any[]
  isBuiltin?: boolean
  usageCount?: number
}

const loading = ref(false)
const saving = ref(false)
const total = ref(0)
const page = ref(1)
const pageSize = ref(12)
const keyword = ref('')
const activeCategory = ref('')
const categories = ref<string[]>(['剧情', '角色', '文风', '世界观', '冲突', '自定义'])
const prompts = ref<PromptItem[]>([])
const showDialog = ref(false)
const showApplyDialog = ref(false)
const currentPrompt = ref<PromptItem | null>(null)
const editingPrompt = ref<PromptItem | null>(null)

const form = reactive({
  title: '',
  category: '',
  description: '',
  content: ''
})

const variableForm = reactive<Record<string, string>>({})

function resetForm() {
  form.title = ''
  form.category = ''
  form.description = ''
  form.content = ''
}

async function loadPrompts() {
  loading.value = true
  try {
    const params: Record<string, any> = { page: page.value, size: pageSize.value }
    if (keyword.value.trim()) params.keyword = keyword.value.trim()
    if (activeCategory.value) params.category = activeCategory.value

    const res = await listPrompts(params)
    prompts.value = (res.data.records || res.data || []) as PromptItem[]
    total.value = res.data.total || 0
  } catch {
    prompts.value = []
    total.value = 0
  } finally {
    loading.value = false
  }
}

async function submitPrompt() {
  if (!form.title.trim()) {
    ElMessage.warning('请输入标题')
    return
  }
  if (!form.category) {
    ElMessage.warning('请选择分类')
    return
  }
  if (!form.content.trim()) {
    ElMessage.warning('请输入提示词内容')
    return
  }

  saving.value = true
  try {
    await createPrompt({
      title: form.title.trim(),
      category: form.category,
      description: form.description.trim() || undefined,
      content: form.content.trim()
    })
    ElMessage.success('提示词已保存')
    showDialog.value = false
    resetForm()
    await loadPrompts()
  } catch {
    ElMessage.error('保存失败')
  } finally {
    saving.value = false
  }
}

async function deletePrompt(item: PromptItem) {
  try {
    await deletePromptApi(item.id)
    ElMessage.success('已删除')
    await loadPrompts()
  } catch {
    ElMessage.error('删除失败')
  }
}

function viewPrompt(item: PromptItem) {
  currentPrompt.value = item
  showApplyDialog.value = true
}

async function applyPrompt(item: PromptItem) {
  currentPrompt.value = item

  if (item.variables && item.variables.length > 0) {
    Object.keys(variableForm).forEach(k => delete variableForm[k])
    item.variables.forEach((v: any) => {
      variableForm[v.name] = v.defaultValue || ''
    })
    showApplyDialog.value = true
  } else {
    const text = item.content || item.promptTemplate || ''
    navigator.clipboard.writeText(text).then(() => {
      ElMessage.success('已复制到剪贴板，请在编辑器中粘贴')
    }).catch(() => {
      ElMessage.error('复制失败')
    })
  }
}

async function copyAppliedPrompt() {
  if (!currentPrompt.value) return

  try {
    if (currentPrompt.value.variables && currentPrompt.value.variables.length > 0) {
      const res = await applyPrompt(currentPrompt.value.id, variableForm)
      navigator.clipboard.writeText(res.data).then(() => {
        ElMessage.success('已复制到剪贴板，请在编辑器中粘贴')
        showApplyDialog.value = false
      }).catch(() => {
        ElMessage.error('复制失败')
      })
    } else {
      const text = currentPrompt.value.content || currentPrompt.value.promptTemplate || ''
      navigator.clipboard.writeText(text).then(() => {
        ElMessage.success('已复制到剪贴板，请在编辑器中粘贴')
        showApplyDialog.value = false
      }).catch(() => {
        ElMessage.error('复制失败')
      })
    }
  } catch {
    ElMessage.error('应用失败')
  }
}

function applyToEditor() {
  if (!currentPrompt.value) return

  const content = currentPrompt.value.content || currentPrompt.value.promptTemplate || ''

  setEditorPrompt({
    type: 'prompt',
    promptId: currentPrompt.value.id,
    promptTitle: currentPrompt.value.title || currentPrompt.value.name || '',
    promptContent: content,
    variables: Object.keys(variableForm).length > 0 ? { ...variableForm } : undefined
  })

  ElMessage.success('已发送到当前编辑器，请切换到编辑页面查看')
  showApplyDialog.value = false
}

onMounted(() => {
  loadPrompts()
})
</script>

<style lang="scss" scoped>
.prompt-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: $space-lg;
  padding: $space-lg 0;
}

.prompt-card {
  .prompt-header {
    display: flex;
    align-items: center;
    gap: $space-sm;
    margin-bottom: $space-sm;

    .tag {
      display: inline-block;
      padding: 2px 10px;
      border-radius: 999px;
      font-size: 12px;
      color: #fff;
      background: linear-gradient(135deg, #10b981, #14b8a6);
    }

    .builtin-tag {
      display: inline-block;
      padding: 2px 8px;
      border-radius: 4px;
      font-size: 11px;
      color: #fff;
      background: linear-gradient(135deg, #6366f1, #8b5cf6);
    }
  }

  .prompt-title {
    margin-bottom: $space-xs;
  }

  .prompt-content {
    color: $text-secondary;
    font-size: $font-size-sm;
    line-height: 1.6;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    margin-bottom: $space-md;
  }

  .prompt-actions {
    display: flex;
    gap: $space-xs;
  }
}

.apply-title {
  font-size: 18px;
  font-weight: 600;
  margin-bottom: $space-xs;
}

.apply-desc {
  color: $text-secondary;
  margin-bottom: 0;
}

.variable-form {
  max-height: 300px;
  overflow-y: auto;
}

.prompt-preview {
  pre {
    background: $bg-light;
    padding: $space-md;
    border-radius: $border-radius;
    white-space: pre-wrap;
    word-break: break-all;
  }
}
</style>
