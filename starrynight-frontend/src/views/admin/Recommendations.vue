<template>
  <div class="admin-recommendations page-container">
    <div class="page-header">
      <h1>推荐管理</h1>
      <el-button type="primary" @click="showAddDialog">
        <el-icon><Plus /></el-icon>
        新增推荐
      </el-button>
    </div>

    <div class="page-content">
      <el-card>
        <el-table :data="recommendations" stripe v-loading="loading">
          <el-table-column prop="id" label="编号" width="80" />
          <el-table-column prop="title" label="推荐标题" min-width="150">
            <template #default="{ row }">
              <div class="rec-title">
                <el-tag v-if="row.type === 'HOME'" type="success" size="small">首页</el-tag>
                <el-tag v-else-if="row.type === 'CATEGORY'" type="warning" size="small">分类</el-tag>
                <el-tag v-else type="info" size="small">编辑</el-tag>
                <span>{{ row.title }}</span>
              </div>
            </template>
          </el-table-column>
          <el-table-column prop="novelTitle" label="推荐作品" min-width="150">
            <template #default="{ row }">
              <div class="novel-info">
                <span class="novel-cover" v-if="row.cover">
                  <img :src="row.cover" :alt="row.novelTitle" />
                </span>
                <span>{{ row.novelTitle || '-' }}</span>
              </div>
            </template>
          </el-table-column>
          <el-table-column prop="position" label="位置" width="120">
            <template #default="{ row }">
              <span>{{ positionLabel(row.position) }}</span>
            </template>
          </el-table-column>
          <el-table-column prop="sort" label="排序" width="80" align="center" />
          <el-table-column prop="startTime" label="开始时间" width="120">
            <template #default="{ row }">
              {{ row.startTime ? formatDate(row.startTime) : '永久' }}
            </template>
          </el-table-column>
          <el-table-column prop="endTime" label="结束时间" width="120">
            <template #default="{ row }">
              {{ row.endTime ? formatDate(row.endTime) : '永久' }}
            </template>
          </el-table-column>
          <el-table-column prop="status" label="状态" width="100">
            <template #default="{ row }">
              <el-tag :type="row.status === 1 ? 'success' : 'info'" size="small">
                {{ row.status === 1 ? '生效中' : '已过期' }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column label="操作" width="160" fixed="right">
            <template #default="{ row }">
              <el-button type="primary" link size="small" @click="handleEdit(row)">编辑</el-button>
              <el-popconfirm title="确定删除此推荐？" @confirm="handleDelete(row)">
                <template #reference>
                  <el-button type="danger" link size="small">删除</el-button>
                </template>
              </el-popconfirm>
            </template>
          </el-table-column>
        </el-table>

        <div class="pagination-wrapper">
          <el-pagination
            v-model:current-page="queryForm.page"
            v-model:page-size="queryForm.size"
            :total="total"
            :page-sizes="[10, 20, 50]"
            layout="total, sizes, prev, pager, next"
            @change="loadRecommendations"
          />
        </div>
      </el-card>
    </div>

    <el-dialog
      v-model="dialogVisible"
      :title="dialogTitle"
      width="600px"
      @close="resetForm"
    >
      <el-form :model="form" label-position="top" :rules="rules" ref="formRef">
        <el-form-item label="推荐标题" prop="title">
          <el-input v-model="form.title" placeholder="请输入推荐标题" maxlength="50" show-word-limit />
        </el-form-item>
        <el-form-item label="推荐类型" prop="type">
          <el-radio-group v-model="form.type">
            <el-radio value="HOME">首页推荐</el-radio>
            <el-radio value="CATEGORY">分类推荐</el-radio>
            <el-radio value="EDITOR">编辑精选</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="选择作品" prop="novelId">
          <el-select
            v-model="form.novelId"
            placeholder="请选择要推荐的作品"
            filterable
            remote
            :remote-method="searchNovels"
            :loading="novelLoading"
            style="width: 100%"
          >
            <el-option
              v-for="novel in novelOptions"
              :key="novel.id"
              :label="novel.title"
              :value="novel.id"
            >
              <div class="novel-option">
                <span>{{ novel.title }}</span>
                <span class="novel-author">{{ novel.author }}</span>
              </div>
            </el-option>
          </el-select>
        </el-form-item>
        <el-form-item label="推荐位置" prop="position">
          <el-select v-model="form.position" placeholder="请选择推荐位置" style="width: 100%">
            <el-option label="横幅轮播" value="BANNER" />
            <el-option label="本周强推" value="WEEKLY_HOT" />
            <el-option label="新书推荐" value="NEW_BOOKS" />
            <el-option label="编辑推荐" value="EDITOR_PICK" />
            <el-option label="分类精选" value="CATEGORY_HOT" />
          </el-select>
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="form.sort" :min="0" :max="9999" />
        </el-form-item>
        <el-form-item label="生效时间">
          <el-date-picker
            v-model="dateRange"
            type="daterange"
            range-separator="至"
            start-placeholder="开始日期"
            end-placeholder="结束日期"
            format="YYYY-MM-DD"
            value-format="YYYY-MM-DD"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="状态">
          <el-radio-group v-model="form.status">
            <el-radio :value="1">启用</el-radio>
            <el-radio :value="0">禁用</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="submitForm">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import {
  listRecommendations,
  createRecommendation,
  updateRecommendation,
  deleteRecommendation,
  searchNovels as searchNovelsApi
} from '@/api/recommendation'

interface Recommendation {
  id: number
  title: string
  type: string
  novelId: number
  novelTitle?: string
  cover?: string
  position: string
  sort: number
  startTime?: string
  endTime?: string
  status: number
  createTime: string
}

interface NovelOption {
  id: number
  title: string
  author?: string
}

const loading = ref(false)
const saving = ref(false)
const novelLoading = ref(false)
const dialogVisible = ref(false)
const dialogTitle = ref('新增推荐')
const recommendations = ref<Recommendation[]>([])
const novelOptions = ref<NovelOption[]>([])
const total = ref(0)
const formRef = ref()
const dateRange = ref<[string, string] | null>(null)

const queryForm = reactive({
  page: 1,
  size: 10
})

const form = reactive({
  id: 0,
  title: '',
  type: 'HOME',
  novelId: 0,
  position: 'WEEKLY_HOT',
  sort: 0,
  startTime: '',
  endTime: '',
  status: 1
})

const rules = {
  title: [{ required: true, message: '请输入推荐标题', trigger: 'blur' }],
  type: [{ required: true, message: '请选择推荐类型', trigger: 'change' }],
  novelId: [{ required: true, message: '请选择推荐作品', trigger: 'change' }],
  position: [{ required: true, message: '请选择推荐位置', trigger: 'change' }]
}

function positionLabel(position: string): string {
  const labels: Record<string, string> = {
    BANNER: '横幅轮播',
    WEEKLY_HOT: '本周强推',
    NEW_BOOKS: '新书推荐',
    EDITOR_PICK: '编辑推荐',
    CATEGORY_HOT: '分类精选'
  }
  return labels[position] || position
}

function formatDate(date: string): string {
  return date.split('T')[0]
}

async function loadRecommendations() {
  loading.value = true
  try {
    const res = await listRecommendations({
      page: queryForm.page,
      size: queryForm.size
    })
    if (res.data) {
      recommendations.value = res.data.records || res.data || []
      total.value = res.data.total || 0
    }
  } catch {
    ElMessage.error('加载推荐列表失败')
  } finally {
    loading.value = false
  }
}

async function searchNovels(query: string) {
  if (!query) {
    novelOptions.value = []
    return
  }
  novelLoading.value = true
  try {
    const res = await searchNovelsApi(query)
    if (res.data) {
      novelOptions.value = res.data.slice(0, 10)
    }
  } catch {
    novelOptions.value = []
  } finally {
    novelLoading.value = false
  }
}

function showAddDialog() {
  dialogTitle.value = '新增推荐'
  novelOptions.value = []
  dialogVisible.value = true
}

function handleEdit(row: Recommendation) {
  dialogTitle.value = '编辑推荐'
  form.id = row.id
  form.title = row.title
  form.type = row.type
  form.novelId = row.novelId
  form.position = row.position
  form.sort = row.sort
  form.status = row.status
  if (row.startTime || row.endTime) {
    dateRange.value = [row.startTime?.split('T')[0] || '', row.endTime?.split('T')[0] || '']
  }
  if (row.novelTitle) {
    novelOptions.value = [{ id: row.novelId, title: row.novelTitle }]
  }
  dialogVisible.value = true
}

function resetForm() {
  form.id = 0
  form.title = ''
  form.type = 'HOME'
  form.novelId = 0
  form.position = 'WEEKLY_HOT'
  form.sort = 0
  form.startTime = ''
  form.endTime = ''
  form.status = 1
  dateRange.value = null
  formRef.value?.resetFields()
}

async function submitForm() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  saving.value = true
  try {
    const data = {
      title: form.title,
      type: form.type,
      novelId: form.novelId,
      position: form.position,
      sort: form.sort,
      startTime: dateRange.value?.[0] || null,
      endTime: dateRange.value?.[1] || null,
      status: form.status
    }

    if (form.id) {
      await updateRecommendation(form.id, data)
      ElMessage.success('更新成功')
    } else {
      await createRecommendation(data)
      ElMessage.success('创建成功')
    }
    dialogVisible.value = false
    await loadRecommendations()
  } catch {
    ElMessage.error('保存失败')
  } finally {
    saving.value = false
  }
}

async function handleDelete(row: Recommendation) {
  try {
    await deleteRecommendation(row.id)
    ElMessage.success('删除成功')
    await loadRecommendations()
  } catch {
    ElMessage.error('删除失败')
  }
}

onMounted(() => {
  loadRecommendations()
})
</script>

<style lang="scss" scoped>
.rec-title {
  display: flex;
  align-items: center;
  gap: $space-sm;
}

.novel-info {
  display: flex;
  align-items: center;
  gap: $space-sm;

  .novel-cover {
    width: 30px;
    height: 40px;
    border-radius: $radius-xs;
    overflow: hidden;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);

    img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
  }
}

.novel-option {
  display: flex;
  justify-content: space-between;
  align-items: center;

  .novel-author {
    font-size: $font-size-xs;
    color: $text-muted;
  }
}

.pagination-wrapper {
  display: flex;
  justify-content: flex-end;
  margin-top: $space-lg;
}
</style>