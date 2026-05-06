<template>
  <div class="admin-redeem page-container">
    <div class="page-header">
      <h1>兑换码</h1>
      <div class="actions">
        <el-input
          v-model="keyword"
          placeholder="搜索码/批次"
          clearable
          style="width: 220px"
          @keyup.enter="load"
        />
        <el-button @click="load">查询</el-button>
        <el-button type="primary" @click="openCreate">新增单码</el-button>
        <el-button type="success" @click="openGenerate">批量生成</el-button>
      </div>
    </div>

    <el-card>
      <el-table :data="rows" stripe v-loading="loading">
        <el-table-column prop="code" label="兑换码" width="160" />
        <el-table-column prop="batchLabel" label="批次" min-width="120" show-overflow-tooltip />
        <el-table-column label="奖励" min-width="140">
          <template #default="{ row }">
            <span v-if="row.rewardType === 'platform_currency'">星夜币 {{ row.rewardCurrency }}</span>
            <span v-else>创作点 {{ row.rewardPoints }}</span>
          </template>
        </el-table-column>
        <el-table-column label="已兑/上限" width="120">
          <template #default="{ row }">
            {{ row.redemptionCount ?? 0 }} /
            {{ row.maxTotalRedemptions == null ? '∞' : row.maxTotalRedemptions }}
          </template>
        </el-table-column>
        <el-table-column prop="maxPerUser" label="每用户" width="80" />
        <el-table-column label="有效期" min-width="200">
          <template #default="{ row }">
            {{ row.validStart || '—' }} ~ {{ row.validEnd || '—' }}
          </template>
        </el-table-column>
        <el-table-column label="状态" width="80">
          <template #default="{ row }">
            <el-tag :type="row.enabled === 1 ? 'success' : 'info'" size="small">
              {{ row.enabled === 1 ? '启用' : '停用' }}
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

    <el-dialog v-model="editVisible" :title="isEdit ? '编辑兑换码' : '新增兑换码'" width="560px">
      <el-form ref="formRef" :model="form" :rules="formRules" label-width="112px">
        <el-form-item label="兑换码" prop="code">
          <el-input v-model="form.code" :disabled="isEdit" placeholder="大写字母与数字" />
        </el-form-item>
        <el-form-item label="批次备注" prop="batchLabel">
          <el-input v-model="form.batchLabel" />
        </el-form-item>
        <el-form-item label="奖励类型" prop="rewardType">
          <el-select v-model="form.rewardType" style="width: 100%">
            <el-option label="创作点" value="free_quota" />
            <el-option label="星夜币" value="platform_currency" />
          </el-select>
        </el-form-item>
        <el-form-item v-if="form.rewardType === 'free_quota'" label="创作点" prop="rewardPoints">
          <el-input-number v-model="form.rewardPoints" :min="1" :step="1" style="width: 100%" />
        </el-form-item>
        <el-form-item v-else label="星夜币" prop="rewardCurrency">
          <el-input-number v-model="form.rewardCurrency" :min="0.01" :precision="2" :step="1" style="width: 100%" />
        </el-form-item>
        <el-form-item label="总兑换上限">
          <el-input-number v-model="form.maxTotalRedemptions" :min="1" :step="1" style="width: 100%" />
          <div class="hint">留空表示不限</div>
        </el-form-item>
        <el-form-item label="每用户上限" prop="maxPerUser">
          <el-input-number v-model="form.maxPerUser" :min="1" :step="1" style="width: 100%" />
        </el-form-item>
        <el-form-item label="生效时间">
          <el-date-picker
            v-model="form.validStart"
            type="datetime"
            value-format="YYYY-MM-DDTHH:mm:ss"
            style="width: 100%"
            placeholder="可选"
          />
        </el-form-item>
        <el-form-item label="失效时间">
          <el-date-picker
            v-model="form.validEnd"
            type="datetime"
            value-format="YYYY-MM-DDTHH:mm:ss"
            style="width: 100%"
            placeholder="可选"
          />
        </el-form-item>
        <el-form-item label="启用" prop="enabled">
          <el-switch v-model="form.enabled" :active-value="1" :inactive-value="0" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="editVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitting" @click="submitForm">保存</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="genVisible" title="批量生成" width="560px">
      <el-form ref="genRef" :model="gen" :rules="genRules" label-width="112px">
        <el-form-item label="生成数量" prop="count">
          <el-input-number v-model="gen.count" :min="1" :max="500" :step="1" style="width: 100%" />
        </el-form-item>
        <el-form-item label="码长度" prop="codeLength">
          <el-input-number v-model="gen.codeLength" :min="8" :max="32" :step="1" style="width: 100%" />
        </el-form-item>
        <el-form-item label="前缀">
          <el-input v-model="gen.prefix" placeholder="可选，大写" maxlength="16" />
        </el-form-item>
        <el-form-item label="批次备注">
          <el-input v-model="gen.batchLabel" />
        </el-form-item>
        <el-form-item label="奖励类型" prop="rewardType">
          <el-select v-model="gen.rewardType" style="width: 100%">
            <el-option label="创作点" value="free_quota" />
            <el-option label="星夜币" value="platform_currency" />
          </el-select>
        </el-form-item>
        <el-form-item v-if="gen.rewardType === 'free_quota'" label="创作点" prop="rewardPoints">
          <el-input-number v-model="gen.rewardPoints" :min="1" :step="1" style="width: 100%" />
        </el-form-item>
        <el-form-item v-else label="星夜币" prop="rewardCurrency">
          <el-input-number v-model="gen.rewardCurrency" :min="0.01" :precision="2" :step="1" style="width: 100%" />
        </el-form-item>
        <el-form-item label="总兑换上限">
          <el-input-number v-model="gen.maxTotalRedemptions" :min="1" :step="1" style="width: 100%" />
          <div class="hint">留空表示每码 1 次（由单码规则决定）；可填每码总上限</div>
        </el-form-item>
        <el-form-item label="每用户上限" prop="maxPerUser">
          <el-input-number v-model="gen.maxPerUser" :min="1" :step="1" style="width: 100%" />
        </el-form-item>
        <el-form-item label="生效时间">
          <el-date-picker
            v-model="gen.validStart"
            type="datetime"
            value-format="YYYY-MM-DDTHH:mm:ss"
            style="width: 100%"
            placeholder="可选"
          />
        </el-form-item>
        <el-form-item label="失效时间">
          <el-date-picker
            v-model="gen.validEnd"
            type="datetime"
            value-format="YYYY-MM-DDTHH:mm:ss"
            style="width: 100%"
            placeholder="可选"
          />
        </el-form-item>
        <el-form-item label="启用" prop="enabled">
          <el-switch v-model="gen.enabled" :active-value="1" :inactive-value="0" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="genVisible = false">取消</el-button>
        <el-button type="primary" :loading="genLoading" @click="submitGenerate">生成</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import type { FormInstance, FormRules } from 'element-plus'
import type { RedeemCodeItem, RedeemGeneratePayload } from '@/types/api'
import {
  createRedeemCode,
  deleteRedeemCode,
  generateRedeemCodes,
  listRedeemCodes,
  updateRedeemCode
} from '@/api/redeemCode'

const rows = ref<RedeemCodeItem[]>([])
const loading = ref(false)
const total = ref(0)
const page = ref(1)
const pageSize = ref(10)
const keyword = ref('')

const editVisible = ref(false)
const genVisible = ref(false)
const isEdit = ref(false)
const submitting = ref(false)
const genLoading = ref(false)
const formRef = ref<FormInstance>()
const genRef = ref<FormInstance>()

const form = reactive<RedeemCodeItem>({
  code: '',
  batchLabel: '',
  rewardType: 'free_quota',
  rewardPoints: 100,
  rewardCurrency: 0,
  maxTotalRedemptions: undefined,
  maxPerUser: 1,
  validStart: undefined,
  validEnd: undefined,
  enabled: 1
})

const gen = reactive<RedeemGeneratePayload>({
  batchLabel: '',
  count: 10,
  codeLength: 12,
  prefix: '',
  rewardType: 'free_quota',
  rewardPoints: 100,
  rewardCurrency: 0,
  maxTotalRedemptions: undefined,
  maxPerUser: 1,
  validStart: undefined,
  validEnd: undefined,
  enabled: 1
})

const formRules: FormRules = {
  code: [{ required: true, message: '请输入兑换码', trigger: 'blur' }],
  rewardType: [{ required: true, message: '请选择类型', trigger: 'change' }],
  maxPerUser: [{ required: true, message: '必填', trigger: 'change' }],
  enabled: [{ required: true, message: '必填', trigger: 'change' }]
}

const genRules: FormRules = {
  count: [{ required: true, message: '必填', trigger: 'change' }],
  codeLength: [{ required: true, message: '必填', trigger: 'change' }],
  rewardType: [{ required: true, message: '必填', trigger: 'change' }],
  maxPerUser: [{ required: true, message: '必填', trigger: 'change' }],
  enabled: [{ required: true, message: '必填', trigger: 'change' }]
}

async function load() {
  loading.value = true
  try {
    const data = await listRedeemCodes({
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
    code: '',
    batchLabel: '',
    rewardType: 'free_quota',
    rewardPoints: 100,
    rewardCurrency: 1,
    maxTotalRedemptions: undefined,
    maxPerUser: 1,
    validStart: undefined,
    validEnd: undefined,
    enabled: 1
  })
}

function openCreate() {
  isEdit.value = false
  resetForm()
  editVisible.value = true
}

function openEdit(row: RedeemCodeItem) {
  isEdit.value = true
  Object.assign(form, {
    id: row.id,
    code: row.code,
    batchLabel: row.batchLabel || '',
    rewardType: row.rewardType,
    rewardPoints: row.rewardPoints ?? 0,
    rewardCurrency: row.rewardCurrency ?? 0,
    maxTotalRedemptions: row.maxTotalRedemptions ?? undefined,
    maxPerUser: row.maxPerUser,
    validStart: row.validStart ?? undefined,
    validEnd: row.validEnd ?? undefined,
    enabled: row.enabled
  })
  editVisible.value = true
}

function openGenerate() {
  Object.assign(gen, {
    batchLabel: '',
    count: 10,
    codeLength: 12,
    prefix: '',
    rewardType: 'free_quota',
    rewardPoints: 100,
    rewardCurrency: 1,
    maxTotalRedemptions: undefined,
    maxPerUser: 1,
    validStart: undefined,
    validEnd: undefined,
    enabled: 1
  })
  genVisible.value = true
}

async function submitForm() {
  if (!formRef.value) return
  const ok = await formRef.value.validate().catch(() => false)
  if (!ok) return
  submitting.value = true
  try {
    const payload = { ...form }
    if (payload.maxTotalRedemptions === undefined || payload.maxTotalRedemptions === null) {
      ;(payload as Record<string, unknown>).maxTotalRedemptions = undefined
    }
    if (isEdit.value && form.id) {
      await updateRedeemCode(form.id, payload)
      ElMessage.success('已更新')
    } else {
      await createRedeemCode(payload)
      ElMessage.success('已创建')
    }
    editVisible.value = false
    await load()
  } finally {
    submitting.value = false
  }
}

async function submitGenerate() {
  if (!genRef.value) return
  const ok = await genRef.value.validate().catch(() => false)
  if (!ok) return
  genLoading.value = true
  try {
    const payload: RedeemGeneratePayload = { ...gen }
    if (!payload.prefix) payload.prefix = ''
    const created = await generateRedeemCodes(payload)
    ElMessage.success(`已生成 ${created.length} 条`)
    genVisible.value = false
    await load()
  } finally {
    genLoading.value = false
  }
}

async function handleDelete(row: RedeemCodeItem) {
  await ElMessageBox.confirm(`确认删除兑换码「${row.code}」吗？`, '删除确认', { type: 'warning' })
  await deleteRedeemCode(row.id as number)
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
