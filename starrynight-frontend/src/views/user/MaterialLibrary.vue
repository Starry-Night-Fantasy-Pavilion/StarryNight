<template>
  <div class="material-library page-container">
    <div class="page-header">
      <h1>素材库</h1>
      <div class="header-actions">
        <el-button type="success" @click="showRecommendDialog = true">
          <el-icon><MagicStick /></el-icon>
          智能推荐
        </el-button>
        <el-button type="primary" @click="showDialog = true">添加素材</el-button>
      </div>
    </div>

    <div class="page-content">
      <!-- 分类标签 -->
      <el-card class="filter-card">
        <div class="category-tabs">
          <el-radio-group v-model="activeCategory" @change="loadMaterials">
            <el-radio-button value="">全部分类</el-radio-button>
            <el-radio-button value="golden_finger">金手指</el-radio-button>
            <el-radio-button value="worldview">世界观</el-radio-button>
            <el-radio-button value="character_draft">角色草稿</el-radio-button>
            <el-radio-button value="conflict_idea">冲突桥段</el-radio-button>
            <el-radio-button value="style_fingerprint">风格指纹</el-radio-button>
            <el-radio-button value="custom">自定义</el-radio-button>
          </el-radio-group>
          <el-input
            v-model="keyword"
            placeholder="搜索素材..."
            clearable
            style="width: 220px"
            @clear="loadMaterials"
            @keyup.enter="loadMaterials"
          />
        </div>
      </el-card>

      <!-- 素材网格 -->
      <div v-if="!loading && materials.length > 0" class="material-grid">
        <el-card
          v-for="item in materials"
          :key="item.id"
          class="material-card"
          shadow="hover"
          draggable="true"
          @dragstart="handleDragStart($event, item)"
          @dragend="handleDragEnd"
        >
          <div class="material-thumb" :style="{ background: categoryColor(item.type) }">
            {{ categoryIcon(item.type) }}
          </div>
          <div class="material-body">
            <h4 class="material-title">{{ item.title }}</h4>
            <p class="material-desc">{{ item.description || item.content?.system_name || '暂无描述' }}</p>
            <div class="material-tags">
              <el-tag v-for="tag in (item.tags || []).slice(0, 3)" :key="tag" size="small" class="tag">{{ tag }}</el-tag>
            </div>
          </div>
          <div class="material-footer">
            <span class="material-type">{{ categoryLabel(item.type) }}</span>
            <div class="material-actions">
              <el-button text type="primary" size="small" @click="viewMaterial(item)">查看</el-button>
              <el-button text type="success" size="small" @click="copyToEditor(item)">插入编辑器</el-button>
              <el-popconfirm title="确定删除？" @confirm="deleteMaterial(item)">
                <template #reference>
                  <el-button text type="danger" size="small">删除</el-button>
                </template>
              </el-popconfirm>
            </div>
          </div>
        </el-card>
      </div>

      <el-empty v-if="!loading && materials.length === 0" description="暂无素材，点击右上角添加" />

      <div class="pagination-wrapper" v-if="total > 0">
        <el-pagination
          v-model:current-page="page"
          v-model:page-size="pageSize"
          :total="total"
          :page-sizes="[12, 24, 48]"
          layout="total, sizes, prev, pager, next"
          @change="loadMaterials"
        />
      </div>
    </div>

    <!-- 创建素材弹窗 -->
    <el-dialog v-model="showDialog" title="添加素材" width="560px">
      <el-form :model="form" label-position="top" size="large">
        <el-form-item label="素材标题" required>
          <el-input v-model="form.title" placeholder="输入素材标题" maxlength="50" show-word-limit />
        </el-form-item>
        <el-form-item label="分类" required>
          <el-select v-model="form.type" placeholder="选择分类" style="width: 100%">
            <el-option label="金手指" value="golden_finger" />
            <el-option label="世界观" value="worldview" />
            <el-option label="角色草稿" value="character_draft" />
            <el-option label="冲突桥段" value="conflict_idea" />
            <el-option label="风格指纹" value="style_fingerprint" />
            <el-option label="自定义" value="custom" />
          </el-select>
        </el-form-item>
        <el-form-item label="描述">
          <el-input v-model="form.description" type="textarea" :rows="3" placeholder="描述素材内容..." />
        </el-form-item>
        <el-form-item label="标签">
          <el-input v-model="form.tagsText" placeholder="输入标签，用逗号分隔" />
        </el-form-item>
        <el-form-item label="内容（JSON 格式）">
          <el-input
            v-model="form.contentText"
            type="textarea"
            :rows="4"
            placeholder='如：{"system_name":"抽奖系统","rules":["每日一次"]}'
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showDialog = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="submitMaterial">保存</el-button>
      </template>
    </el-dialog>

    <!-- 查看素材弹窗 -->
    <el-dialog v-model="showDetailDialog" title="素材详情" width="520px">
      <template v-if="currentItem">
        <el-descriptions :column="1" border size="small">
          <el-descriptions-item label="标题">{{ currentItem.title }}</el-descriptions-item>
          <el-descriptions-item label="分类">{{ categoryLabel(currentItem.type) }}</el-descriptions-item>
          <el-descriptions-item label="描述">{{ currentItem.description || '暂无' }}</el-descriptions-item>
          <el-descriptions-item label="标签">{{ (currentItem.tags || []).join('、') || '暂无' }}</el-descriptions-item>
        </el-descriptions>
        <el-divider />
        <h4 class="detail-section-title">内容详情</h4>
        <pre class="detail-json">{{ JSON.stringify(currentItem.content || currentItem, null, 2) }}</pre>
      </template>
    </el-dialog>

    <!-- 智能推荐弹窗 -->
    <el-dialog v-model="showRecommendDialog" title="智能推荐素材" width="720px" @close="resetRecommendForm">
      <el-form :model="recommendForm" label-position="top">
        <el-form-item label="当前创作上下文">
          <el-input
            v-model="recommendForm.context"
            type="textarea"
            :rows="4"
            placeholder="描述当前创作场景，如：主角正在与一个神秘的商人讨价还价，需要一个特殊的交易道具..."
          />
        </el-form-item>
        <el-form-item label="需要的素材类型">
          <el-select v-model="recommendForm.targetType" placeholder="选择素材类型（可多选）" multiple clearable style="width: 100%">
            <el-option label="金手指" value="golden_finger" />
            <el-option label="世界观" value="worldview" />
            <el-option label="角色草稿" value="character_draft" />
            <el-option label="冲突桥段" value="conflict_idea" />
            <el-option label="风格指纹" value="style_fingerprint" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" :loading="recommending" @click="getSmartRecommendations">获取推荐</el-button>
        </el-form-item>
      </el-form>

      <el-divider v-if="recommendations.length > 0" />

      <div v-if="recommendations.length > 0" class="recommendations-list">
        <h4>推荐结果</h4>
        <div v-for="(rec, idx) in recommendations" :key="idx" class="recommendation-item">
          <div class="rec-header">
            <div class="rec-title">
              <span class="rec-icon">{{ categoryIcon(rec.type) }}</span>
              <span class="rec-name">{{ rec.title }}</span>
              <el-tag size="small" type="success">匹配度{{ rec.score }}%</el-tag>
            </div>
            <div class="rec-actions">
              <el-button type="primary" size="small" @click="applyRecommendation(rec)">应用</el-button>
              <el-button type="success" size="small" @click="copyToEditor(rec)">复制</el-button>
            </div>
          </div>
          <div class="rec-reason">
            <strong>推荐理由：</strong>{{ rec.reason }}
          </div>
          <div class="rec-content">
            <p>{{ rec.description || rec.content?.system_name || '暂无描述' }}</p>
          </div>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { MagicStick } from '@element-plus/icons-vue'
import {
  listMaterials,
  createMaterial,
  deleteMaterial as removeMaterial,
  recommendMaterials
} from '@/api/material'

interface MaterialItem {
  id: number
  title: string
  type: string
  description?: string
  tags?: string[]
  content?: any
  source?: string
  usageCount?: number
  createdAt: string
}

const loading = ref(false)
const saving = ref(false)
const total = ref(0)
const page = ref(1)
const pageSize = ref(12)
const keyword = ref('')
const activeCategory = ref('')
const materials = ref<MaterialItem[]>([])
const showDialog = ref(false)
const showDetailDialog = ref(false)
const showRecommendDialog = ref(false)
const currentItem = ref<MaterialItem | null>(null)
const recommending = ref(false)
const recommendations = ref<any[]>([])

const form = reactive({
  title: '',
  type: '',
  description: '',
  tagsText: '',
  contentText: ''
})

const recommendForm = reactive({
  context: '',
  targetType: [] as string[]
})

function categoryIcon(type: string): string {
  const map: Record<string, string> = {
    golden_finger: '⚡',
    worldview: '🌍',
    character_draft: '👤',
    conflict_idea: '💥',
    style_fingerprint: '🎨',
    custom: '📦'
  }
  return map[type] || '📄'
}

function categoryLabel(type: string): string {
  const map: Record<string, string> = {
    golden_finger: '金手指',
    worldview: '世界观',
    character_draft: '角色草稿',
    conflict_idea: '冲突桥段',
    style_fingerprint: '风格指纹',
    custom: '自定义',
  }
  return map[type] || type
}

function categoryColor(type: string): string {
  const map: Record<string, string> = {
    golden_finger: 'linear-gradient(135deg, #f59e0b, #f97316)',
    worldview: 'linear-gradient(135deg, #6366f1, #8b5cf6)',
    character_draft: 'linear-gradient(135deg, #22c55e, #10b981)',
    conflict_idea: 'linear-gradient(135deg, #ef4444, #f43f5e)',
    style_fingerprint: 'linear-gradient(135deg, #ec4899, #f472b6)',
    custom: 'linear-gradient(135deg, #64748b, #94a3b8)'
  }
  return map[type] || 'linear-gradient(135deg, #6366f1, #8b5cf6)'
}

async function loadMaterials() {
  loading.value = true
  try {
    const params: Record<string, any> = { page: page.value, size: pageSize.value }
    if (keyword.value.trim()) params.keyword = keyword.value.trim()
    if (activeCategory.value) params.type = activeCategory.value

    const res = await listMaterials(params)
    materials.value = res.data?.records || res.data || []
    total.value = res.data?.total || 0
  } catch {
    materials.value = []
    total.value = 0
  } finally {
    loading.value = false
  }
}

function resetForm() {
  form.title = ''
  form.type = ''
  form.description = ''
  form.tagsText = ''
  form.contentText = ''
}

async function submitMaterial() {
  if (!form.title.trim()) {
    ElMessage.warning('请输入素材标题')
    return
  }
  if (!form.type) {
    ElMessage.warning('请选择分类')
    return
  }
  saving.value = true
  try {
    let content: any = undefined
    if (form.contentText.trim()) {
      try {
        content = JSON.parse(form.contentText.trim())
      } catch {
        content = form.contentText.trim()
      }
    }

    await createMaterial({
      title: form.title.trim(),
      type: form.type,
      description: form.description.trim() || undefined,
      tags: form.tagsText.trim() ? form.tagsText.split(/[,，]/).map(t => t.trim()).filter(Boolean) : [],
      content
    })
    ElMessage.success('素材已添加')
    showDialog.value = false
    resetForm()
    await loadMaterials()
  } catch {
    ElMessage.error('添加失败')
  } finally {
    saving.value = false
  }
}

function viewMaterial(item: MaterialItem) {
  currentItem.value = item
  showDetailDialog.value = true
}

async function deleteMaterial(item: MaterialItem) {
  try {
    await removeMaterial(item.id)
    ElMessage.success('已删除')
    await loadMaterials()
  } catch {
    ElMessage.error('删除失败')
  }
}

function handleDragStart(event: DragEvent, item: MaterialItem) {
  if (event.dataTransfer) {
    event.dataTransfer.effectAllowed = 'copy'
    event.dataTransfer.setData('application/json', JSON.stringify({
      id: item.id,
      title: item.title,
      type: item.type,
      content: item.content
    }))
  }
}

function handleDragEnd() {
}

async function copyToEditor(item: MaterialItem) {
  const text = item.content ? JSON.stringify(item.content, null, 2) : (item.description || item.title)
  try {
    await navigator.clipboard.writeText(text)
    ElMessage.success('已复制到剪贴板，请在编辑器中粘贴')
  } catch {
    ElMessage.error('复制失败')
  }
}

function resetRecommendForm() {
  recommendForm.context = ''
  recommendForm.targetType = []
  recommendations.value = []
}

async function getSmartRecommendations() {
  if (!recommendForm.context.trim()) {
    ElMessage.warning('请输入创作上下文')
    return
  }

  recommending.value = true
  try {
    const res = await recommendMaterials({
      context: recommendForm.context.trim(),
      types: recommendForm.targetType.length > 0 ? recommendForm.targetType : undefined
    })
    if (res.data) {
      recommendations.value = res.data
      ElMessage.success(`找到 ${recommendations.value.length} 个推荐素材`)
    }
  } catch {
    ElMessage.error('获取推荐失败，请重试')
  } finally {
    recommending.value = false
  }
}

function applyRecommendation(item: any) {
  currentItem.value = item
  showRecommendDialog.value = false
  showDetailDialog.value = true
}

onMounted(() => {
  loadMaterials()
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

.filter-card {
  margin-bottom: $space-lg;
}

.category-tabs {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: $space-md;
}

.material-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: $space-lg;
}

.material-card {
  display: flex;
  flex-direction: column;
}

.material-thumb {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  color: #fff;
  margin-bottom: $space-md;
}

.material-body {
  flex: 1;

  .material-title {
    font-size: $font-size-md;
    font-weight: 600;
    margin-bottom: $space-xs;
  }

  .material-desc {
    font-size: $font-size-sm;
    color: $text-secondary;
    line-height: 1.5;
    margin-bottom: $space-sm;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
}

.material-tags {
  display: flex;
  flex-wrap: wrap;
  gap: $space-xs;

  .tag {
    font-size: $font-size-xs;
  }
}

.material-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: $space-md;
  padding-top: $space-sm;
  border-top: 1px solid $border-color;
}

.material-type {
  font-size: $font-size-xs;
  color: $text-muted;
}

.material-actions {
  display: flex;
  gap: $space-xs;
}

.pagination-wrapper {
  display: flex;
  justify-content: center;
  margin-top: $space-lg;
}

.detail-section-title {
  font-size: $font-size-sm;
  font-weight: 600;
  margin-bottom: $space-sm;
}

.detail-json {
  background: $bg-gray;
  border: 1px solid $border-color;
  border-radius: $border-radius;
  padding: $space-md;
  font-size: $font-size-sm;
  line-height: 1.6;
  max-height: 300px;
  overflow: auto;
  white-space: pre-wrap;
}

.recommendations-list {
  max-height: 400px;
  overflow-y: auto;

  h4 {
    margin: 0 0 $space-md 0;
    font-size: $font-size-md;
  }
}

.recommendation-item {
  border: 1px solid $border-color;
  border-radius: $border-radius;
  padding: $space-md;
  margin-bottom: $space-md;

  .rec-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: $space-sm;

    .rec-title {
      display: flex;
      align-items: center;
      gap: $space-sm;

      .rec-icon {
        font-size: 18px;
      }

      .rec-name {
        font-weight: 600;
        font-size: $font-size-md;
      }
    }

    .rec-actions {
      display: flex;
      gap: $space-sm;
    }
  }

  .rec-reason {
    font-size: $font-size-sm;
    color: $text-secondary;
    background: var(--el-color-primary-light-9);
    padding: $space-sm;
    border-radius: 4px;
    margin-bottom: $space-sm;
  }

  .rec-content {
    font-size: $font-size-sm;
    color: $text-secondary;
    line-height: 1.6;
  }
}

@media (max-width: 768px) {
  .page-content {
    padding: $space-md;
  }

  .material-grid {
    grid-template-columns: 1fr;
  }
}
</style>
