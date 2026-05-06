<template>
  <div class="admin-activities page-container">
    <div class="page-header">
      <h1>活动管理</h1>
      <div class="actions">
        <el-button type="primary" @click="openCreate">新建活动</el-button>
      </div>
    </div>

    <el-card>
      <el-table :data="rows" stripe v-loading="loading">
        <el-table-column prop="title" label="标题" min-width="180" />
        <el-table-column prop="summary" label="摘要" min-width="220" show-overflow-tooltip />
        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="statusTag(row.status)">{{ statusLabel(row.status) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="时间" min-width="200">
          <template #default="{ row }">
            <span>{{ row.startTime || '—' }} ~ {{ row.endTime || '—' }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="sortOrder" label="排序" width="72" />
        <el-table-column label="操作" width="160" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link @click="openEdit(row)">编辑</el-button>
            <el-button type="danger" link @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <el-dialog v-model="dialogVisible" :title="isEdit ? '编辑活动' : '新建活动'" width="640px">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="96px">
        <el-form-item label="标题" prop="title">
          <el-input v-model="form.title" placeholder="活动标题" />
        </el-form-item>
        <el-form-item label="摘要" prop="summary">
          <el-input v-model="form.summary" type="textarea" :rows="3" placeholder="简短说明" />
        </el-form-item>
        <el-form-item label="链接" prop="linkUrl">
          <el-input v-model="form.linkUrl" placeholder="https://…" />
        </el-form-item>
        <el-form-item label="封面图" prop="coverUrl">
          <el-input v-model="form.coverUrl" placeholder="图片 URL（可选）" />
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-radio-group v-model="form.status">
            <el-radio :value="0">草稿</el-radio>
            <el-radio :value="1">已发布</el-radio>
            <el-radio :value="2">已结束</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="开始时间">
          <el-date-picker
            v-model="form.startTime"
            type="datetime"
            value-format="YYYY-MM-DDTHH:mm:ss"
            placeholder="可选"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="结束时间">
          <el-date-picker
            v-model="form.endTime"
            type="datetime"
            value-format="YYYY-MM-DDTHH:mm:ss"
            placeholder="可选"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="排序" prop="sortOrder">
          <el-input-number v-model="form.sortOrder" :min="0" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitting" @click="submit">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import type { FormInstance, FormRules } from 'element-plus'
import type { OpsCampaignItem } from '@/types/api'
import {
  createCampaign,
  deleteCampaign,
  listAdminCampaigns,
  updateCampaign
} from '@/api/campaign'

const rows = ref<OpsCampaignItem[]>([])
const loading = ref(false)
const dialogVisible = ref(false)
const isEdit = ref(false)
const submitting = ref(false)
const formRef = ref<FormInstance>()

const form = reactive<OpsCampaignItem>({
  title: '',
  summary: '',
  linkUrl: '',
  coverUrl: '',
  status: 1,
  startTime: undefined,
  endTime: undefined,
  sortOrder: 0
})

const rules: FormRules = {
  title: [{ required: true, message: '请输入标题', trigger: 'blur' }],
  status: [{ required: true, message: '请选择状态', trigger: 'change' }]
}

function statusLabel(s: number) {
  if (s === 1) return '已发布'
  if (s === 2) return '已结束'
  return '草稿'
}

function statusTag(s: number) {
  if (s === 1) return 'success'
  if (s === 2) return 'info'
  return 'warning'
}

function resetForm() {
  Object.assign(form, {
    id: undefined,
    title: '',
    summary: '',
    linkUrl: '',
    coverUrl: '',
    status: 1,
    startTime: undefined,
    endTime: undefined,
    sortOrder: 0
  })
}

async function load() {
  loading.value = true
  try {
    rows.value = await listAdminCampaigns()
  } finally {
    loading.value = false
  }
}

function openCreate() {
  isEdit.value = false
  resetForm()
  dialogVisible.value = true
}

function openEdit(row: OpsCampaignItem) {
  isEdit.value = true
  Object.assign(form, {
    id: row.id,
    title: row.title,
    summary: row.summary || '',
    linkUrl: row.linkUrl || '',
    coverUrl: row.coverUrl || '',
    status: row.status,
    startTime: row.startTime ?? undefined,
    endTime: row.endTime ?? undefined,
    sortOrder: row.sortOrder ?? 0
  })
  dialogVisible.value = true
}

async function submit() {
  if (!formRef.value) return
  const ok = await formRef.value.validate().catch(() => false)
  if (!ok) return
  submitting.value = true
  try {
    if (isEdit.value && form.id) {
      await updateCampaign(form.id, form)
      ElMessage.success('已更新')
    } else {
      await createCampaign(form)
      ElMessage.success('已创建')
    }
    dialogVisible.value = false
    await load()
  } finally {
    submitting.value = false
  }
}

async function handleDelete(row: OpsCampaignItem) {
  await ElMessageBox.confirm(`确认删除活动「${row.title}」吗？`, '删除确认', { type: 'warning' })
  await deleteCampaign(row.id as number)
  ElMessage.success('已删除')
  await load()
}

load()
</script>

<style scoped lang="scss">
.actions {
  display: flex;
  gap: $space-md;
  align-items: center;
}
</style>
