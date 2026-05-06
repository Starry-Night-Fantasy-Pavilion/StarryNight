<template>
  <div class="admin-growth-tasks page-container">
    <div class="page-header">
      <h1>任务管理</h1>
      <div class="actions">
        <el-input
          v-model="keyword"
          placeholder="编码/名称/描述"
          clearable
          style="width: 240px"
          @keyup.enter="load"
        />
        <el-button @click="load">查询</el-button>
        <el-button type="primary" @click="openCreate">新建任务</el-button>
      </div>
    </div>

    <el-alert
      type="info"
      :closable="false"
      class="tip"
      title="任务编码与触发动作（triggerAction）需与业务埋点一致；修改已上线任务的编码可能影响用户任务进度，请谨慎操作。"
    />

    <el-card>
      <el-table :data="rows" stripe v-loading="loading">
        <el-table-column prop="taskCode" label="编码" width="140" />
        <el-table-column prop="taskName" label="名称" min-width="120" />
        <el-table-column prop="taskType" label="类型" width="100" />
        <el-table-column prop="triggerAction" label="触发动作" width="130" show-overflow-tooltip />
        <el-table-column label="奖励" width="120">
          <template #default="{ row }">
            {{ row.rewardType }} / {{ row.rewardAmount }}
          </template>
        </el-table-column>
        <el-table-column label="每日上限" width="90">
          <template #default="{ row }">
            {{ row.maxDailyTimes == null ? '不限' : row.maxDailyTimes }}
          </template>
        </el-table-column>
        <el-table-column prop="sortOrder" label="排序" width="72" />
        <el-table-column label="启用" width="80">
          <template #default="{ row }">
            <el-tag :type="row.enabled === 1 ? 'success' : 'info'" size="small">
              {{ row.enabled === 1 ? '是' : '否' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="140" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link @click="openEdit(row)">编辑</el-button>
            <el-button type="danger" link @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pager">
        <el-pagination
          v-model:current-page="page"
          v-model:page-size="pageSize"
          :total="total"
          :page-sizes="[10, 20, 50]"
          layout="total, sizes, prev, pager, next"
          @size-change="load"
          @current-change="load"
        />
      </div>
    </el-card>

    <el-dialog v-model="dialogVisible" :title="isEdit ? '编辑任务' : '新建任务'" width="640px">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="112px">
        <el-form-item label="任务编码" prop="taskCode">
          <el-input v-model="form.taskCode" :disabled="isEdit" placeholder="如 CREATE_CHAPTER" />
        </el-form-item>
        <el-form-item label="名称" prop="taskName">
          <el-input v-model="form.taskName" />
        </el-form-item>
        <el-form-item label="类型" prop="taskType">
          <el-select v-model="form.taskType" style="width: 100%">
            <el-option label="每日任务 daily" value="daily" />
            <el-option label="成就任务 achievement" value="achievement" />
          </el-select>
        </el-form-item>
        <el-form-item label="描述">
          <el-input v-model="form.description" type="textarea" :rows="2" />
        </el-form-item>
        <el-form-item label="触发动作">
          <el-input v-model="form.triggerAction" placeholder="与后端埋点一致，如 create_chapter" />
        </el-form-item>
        <el-form-item label="奖励类型" prop="rewardType">
          <el-input v-model="form.rewardType" placeholder="如 free_quota" />
        </el-form-item>
        <el-form-item label="奖励数量" prop="rewardAmount">
          <el-input-number v-model="form.rewardAmount" :min="0" :step="1" style="width: 100%" />
        </el-form-item>
        <el-form-item label="条件值">
          <el-input-number v-model="form.conditionValue" :min="0" :step="1" style="width: 100%" />
        </el-form-item>
        <el-form-item label="条件运算符">
          <el-select v-model="form.conditionOperator" style="width: 100%" placeholder="默认 eq">
            <el-option label="eq" value="eq" />
            <el-option label="gte" value="gte" />
          </el-select>
        </el-form-item>
        <el-form-item label="每日上限">
          <el-input-number v-model="form.maxDailyTimes" :min="0" :step="1" style="width: 100%" />
          <div class="hint">0 或留空表示不限（将传 null）</div>
        </el-form-item>
        <el-form-item label="排序" prop="sortOrder">
          <el-input-number v-model="form.sortOrder" :min="0" :step="1" style="width: 100%" />
        </el-form-item>
        <el-form-item label="启用" prop="enabled">
          <el-switch v-model="form.enabled" :active-value="1" :inactive-value="0" />
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
import type { TaskConfigItem } from '@/types/api'
import {
  createTaskConfig,
  deleteTaskConfig,
  listTaskConfigs,
  updateTaskConfig
} from '@/api/taskConfig'

const rows = ref<TaskConfigItem[]>([])
const loading = ref(false)
const total = ref(0)
const page = ref(1)
const pageSize = ref(20)
const keyword = ref('')

const dialogVisible = ref(false)
const isEdit = ref(false)
const submitting = ref(false)
const formRef = ref<FormInstance>()

const form = reactive<TaskConfigItem>({
  taskCode: '',
  taskName: '',
  taskType: 'daily',
  description: '',
  triggerAction: '',
  rewardType: 'free_quota',
  rewardAmount: 10,
  conditionValue: 1,
  conditionOperator: 'eq',
  maxDailyTimes: undefined,
  sortOrder: 0,
  enabled: 1
})

const rules: FormRules = {
  taskCode: [{ required: true, message: '必填', trigger: 'blur' }],
  taskName: [{ required: true, message: '必填', trigger: 'blur' }],
  taskType: [{ required: true, message: '必填', trigger: 'change' }],
  rewardType: [{ required: true, message: '必填', trigger: 'blur' }],
  rewardAmount: [{ required: true, message: '必填', trigger: 'change' }],
  sortOrder: [{ required: true, message: '必填', trigger: 'change' }],
  enabled: [{ required: true, message: '必填', trigger: 'change' }]
}

async function load() {
  loading.value = true
  try {
    const data = await listTaskConfigs({
      keyword: keyword.value || undefined,
      page: page.value,
      size: pageSize.value
    })
    rows.value = data.records
    total.value = data.total
  } finally {
    loading.value = false
  }
}

function resetForm() {
  Object.assign(form, {
    id: undefined,
    taskCode: '',
    taskName: '',
    taskType: 'daily',
    description: '',
    triggerAction: '',
    rewardType: 'free_quota',
    rewardAmount: 10,
    conditionValue: 1,
    conditionOperator: 'eq',
    maxDailyTimes: undefined,
    sortOrder: 0,
    enabled: 1
  })
}

function openCreate() {
  isEdit.value = false
  resetForm()
  dialogVisible.value = true
}

function openEdit(row: TaskConfigItem) {
  isEdit.value = true
  Object.assign(form, {
    id: row.id,
    taskCode: row.taskCode,
    taskName: row.taskName,
    taskType: row.taskType,
    description: row.description || '',
    triggerAction: row.triggerAction || '',
    rewardType: row.rewardType,
    rewardAmount: row.rewardAmount,
    conditionValue: row.conditionValue ?? 1,
    conditionOperator: row.conditionOperator || 'eq',
    maxDailyTimes: row.maxDailyTimes ?? undefined,
    sortOrder: row.sortOrder,
    enabled: row.enabled
  })
  dialogVisible.value = true
}

function buildPayload(): TaskConfigItem {
  const payload = { ...form }
  const md = form.maxDailyTimes
  if (md === undefined || md === null || (typeof md === 'number' && md <= 0)) {
    payload.maxDailyTimes = undefined
  }
  if (!payload.conditionOperator) payload.conditionOperator = 'eq'
  return payload
}

async function submit() {
  if (!formRef.value) return
  const ok = await formRef.value.validate().catch(() => false)
  if (!ok) return
  submitting.value = true
  try {
    const payload = buildPayload()
    if (isEdit.value && form.id) {
      await updateTaskConfig(form.id, payload)
      ElMessage.success('已更新')
    } else {
      await createTaskConfig(payload)
      ElMessage.success('已创建')
    }
    dialogVisible.value = false
    await load()
  } finally {
    submitting.value = false
  }
}

async function handleDelete(row: TaskConfigItem) {
  await ElMessageBox.confirm(`确认删除任务「${row.taskName}」吗？`, '删除确认', { type: 'warning' })
  await deleteTaskConfig(row.id as number)
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
  flex-wrap: wrap;
}

.tip {
  margin-bottom: $space-md;
}

.pager {
  margin-top: $space-md;
  display: flex;
  justify-content: flex-end;
}

.hint {
  font-size: 12px;
  color: $text-secondary;
  margin-top: 4px;
}
</style>
