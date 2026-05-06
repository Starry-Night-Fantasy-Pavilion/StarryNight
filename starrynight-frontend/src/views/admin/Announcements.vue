<template>
  <div class="admin-announcements page-container">
    <div class="page-header">
      <h1>公告管理</h1>
      <div class="actions">
        <el-select v-model="queryStatus" clearable placeholder="状态筛选" style="width: 160px">
          <el-option label="草稿" :value="0" />
          <el-option label="已发布" :value="1" />
          <el-option label="已下线" :value="2" />
        </el-select>
        <el-button @click="loadAnnouncements">查询</el-button>
        <el-button type="primary" @click="openCreateDialog">发布公告</el-button>
      </div>
    </div>

    <el-card>
      <el-table :data="rows" stripe v-loading="loading">
        <el-table-column prop="title" label="标题" min-width="220" />
        <el-table-column label="内容" min-width="280" show-overflow-tooltip>
          <template #default="{ row }">{{ row.content }}</template>
        </el-table-column>
        <el-table-column label="状态" width="120">
          <template #default="{ row }">
            <el-tag :type="statusTagType(row.status)">{{ statusLabel(row.status) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="publishTime" label="发布时间" width="180" />
        <el-table-column label="操作" width="180" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link @click="openEditDialog(row)">编辑</el-button>
            <el-button type="danger" link @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <el-dialog v-model="dialogVisible" :title="isEdit ? '编辑公告' : '发布公告'" width="620px">
      <el-form ref="formRef" :model="formModel" :rules="rules" label-width="90px">
        <el-form-item label="标题" prop="title">
          <el-input v-model="formModel.title" placeholder="请输入公告标题" />
        </el-form-item>
        <el-form-item label="内容" prop="content">
          <el-input v-model="formModel.content" type="textarea" :rows="6" placeholder="请输入公告内容" />
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-radio-group v-model="formModel.status">
            <el-radio :value="0">草稿</el-radio>
            <el-radio :value="1">已发布</el-radio>
            <el-radio :value="2">已下线</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitLoading" @click="handleSubmit">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import type { FormInstance, FormRules } from 'element-plus'
import type { AnnouncementItem } from '@/types/api'
import {
  createAnnouncement,
  deleteAnnouncement,
  listAnnouncements,
  updateAnnouncement
} from '@/api/announcement'

const rows = ref<AnnouncementItem[]>([])
const loading = ref(false)
const submitLoading = ref(false)
const dialogVisible = ref(false)
const isEdit = ref(false)
const queryStatus = ref<number | undefined>(undefined)
const formRef = ref<FormInstance>()

const formModel = reactive<AnnouncementItem>({
  id: undefined,
  title: '',
  content: '',
  status: 1
})

const rules: FormRules = {
  title: [{ required: true, message: '请输入公告标题', trigger: 'blur' }],
  content: [{ required: true, message: '请输入公告内容', trigger: 'blur' }],
  status: [{ required: true, message: '请选择状态', trigger: 'change' }]
}

function resetForm() {
  Object.assign(formModel, {
    id: undefined,
    title: '',
    content: '',
    status: 1,
    publishTime: undefined
  })
}

function statusLabel(status: number) {
  if (status === 1) return '已发布'
  if (status === 2) return '已下线'
  return '草稿'
}

function statusTagType(status: number) {
  if (status === 1) return 'success'
  if (status === 2) return 'info'
  return 'warning'
}

async function loadAnnouncements() {
  loading.value = true
  try {
    const res = await listAnnouncements(queryStatus.value)
    rows.value = res.data
  } finally {
    loading.value = false
  }
}

function openCreateDialog() {
  isEdit.value = false
  resetForm()
  dialogVisible.value = true
}

function openEditDialog(row: AnnouncementItem) {
  isEdit.value = true
  Object.assign(formModel, row)
  dialogVisible.value = true
}

async function handleSubmit() {
  if (!formRef.value) return
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return

  submitLoading.value = true
  try {
    if (isEdit.value && formModel.id) {
      await updateAnnouncement(formModel.id, formModel)
      ElMessage.success('公告已更新')
    } else {
      await createAnnouncement(formModel)
      ElMessage.success('公告已发布')
    }
    dialogVisible.value = false
    await loadAnnouncements()
  } finally {
    submitLoading.value = false
  }
}

async function handleDelete(row: AnnouncementItem) {
  await ElMessageBox.confirm(`确认删除公告「${row.title}」吗？`, '删除确认', {
    type: 'warning'
  })
  await deleteAnnouncement(row.id as number)
  ElMessage.success('公告已删除')
  await loadAnnouncements()
}

loadAnnouncements()
</script>

<style scoped lang="scss">
.actions {
  display: flex;
  gap: $space-md;
  align-items: center;
  flex-wrap: wrap;
}
</style>
