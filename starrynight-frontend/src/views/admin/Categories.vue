<template>
  <div class="admin-categories page-container">
    <div class="page-header">
      <h1>分类管理</h1>
      <div class="actions">
        <el-button type="primary" @click="openCreate">
          <el-icon><Plus /></el-icon>
          新建分类
        </el-button>
      </div>
    </div>

    <el-alert
      type="info"
      :closable="false"
      class="tip"
      title="系统已预置「男频」「女频」两个频道。新建时填写频道名称与可选题材即可；编码由系统自动生成。仅频道时题材请留空。"
    />

    <el-card>
      <el-table :data="rows" stripe v-loading="loading">
        <el-table-column prop="level1Name" label="频道" min-width="120" />
        <el-table-column prop="level2Name" label="题材" min-width="120" />
        <el-table-column prop="novelCount" label="作品数" width="88" align="center" />
        <el-table-column prop="bookCount" label="书城书" width="88" align="center" />
        <el-table-column prop="sort" label="排序" width="72" align="center" />
        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-switch
              v-model="row.status"
              :active-value="1"
              :inactive-value="0"
              @change="() => persistStatus(row)"
            />
          </template>
        </el-table-column>
        <el-table-column label="操作" width="140" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="openEdit(row)">编辑</el-button>
            <el-popconfirm title="确定删除？" @confirm="handleDelete(row)">
              <template #reference>
                <el-button type="danger" link size="small">删除</el-button>
              </template>
            </el-popconfirm>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <el-dialog v-model="dialogVisible" :title="isEdit ? '编辑分类' : '新建分类'" width="480px" @close="resetForm">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="100px">
        <el-form-item label="频道" prop="level1Name">
          <el-input v-model="form.level1Name" placeholder="如：男频、女频" maxlength="32" show-word-limit />
        </el-form-item>
        <el-form-item label="题材" prop="level2Name">
          <el-input v-model="form.level2Name" placeholder="选填；留空表示仅频道（无子题材）" maxlength="32" show-word-limit />
        </el-form-item>
        <el-form-item label="排序" prop="sort">
          <el-input-number v-model="form.sort" :min="0" :max="9999" />
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
import type { FormInstance, FormRules } from 'element-plus'
import type { NovelCategoryMutate, NovelCategoryRow } from '@/api/category'
import {
  createNovelCategory,
  deleteNovelCategory,
  listNovelCategories,
  updateNovelCategory
} from '@/api/category'

const loading = ref(false)
const saving = ref(false)
const dialogVisible = ref(false)
const isEdit = ref(false)
const editId = ref(0)
const rows = ref<NovelCategoryRow[]>([])
const formRef = ref<FormInstance>()

const form = reactive({
  level1Name: '',
  level2Name: '',
  sort: 0,
  status: 1
})

const rules: FormRules = {
  level1Name: [{ required: true, message: '请输入频道名称', trigger: 'blur' }]
}

async function load() {
  loading.value = true
  try {
    rows.value = await listNovelCategories()
  } catch {
    ElMessage.error('加载失败')
  } finally {
    loading.value = false
  }
}

function rowToMutate(row: NovelCategoryRow): NovelCategoryMutate {
  const isL1 = row.level2Name === '—'
  return {
    level1Name: row.level1Name,
    level2Name: isL1 ? '' : row.level2Name,
    sort: row.sort,
    status: row.status
  }
}

async function persistStatus(row: NovelCategoryRow) {
  try {
    await updateNovelCategory(row.id, rowToMutate(row))
    ElMessage.success('已更新状态')
  } catch {
    row.status = row.status === 1 ? 0 : 1
    ElMessage.error('状态更新失败')
  }
}

function openCreate() {
  isEdit.value = false
  editId.value = 0
  resetForm()
  dialogVisible.value = true
}

function openEdit(row: NovelCategoryRow) {
  isEdit.value = true
  editId.value = row.id
  const isL1 = row.level2Name === '—'
  form.level1Name = row.level1Name
  form.level2Name = isL1 ? '' : row.level2Name
  form.sort = row.sort
  form.status = row.status
  dialogVisible.value = true
}

function resetForm() {
  form.level1Name = ''
  form.level2Name = ''
  form.sort = 0
  form.status = 1
  formRef.value?.resetFields()
}

async function submitForm() {
  const ok = await formRef.value?.validate().catch(() => false)
  if (!ok) return
  saving.value = true
  try {
    const payload: NovelCategoryMutate = {
      level1Name: form.level1Name.trim(),
      level2Name: form.level2Name.trim(),
      sort: form.sort,
      status: form.status
    }
    if (isEdit.value) {
      await updateNovelCategory(editId.value, payload)
      ElMessage.success('已更新')
    } else {
      await createNovelCategory(payload)
      ElMessage.success('已创建')
    }
    dialogVisible.value = false
    await load()
  } catch {
    ElMessage.error('保存失败')
  } finally {
    saving.value = false
  }
}

async function handleDelete(row: NovelCategoryRow) {
  try {
    await deleteNovelCategory(row.id)
    ElMessage.success('已删除')
    await load()
  } catch {
    ElMessage.error('删除失败')
  }
}

onMounted(load)
</script>

<style lang="scss" scoped>
.admin-categories {
  .page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: $space-md;
    flex-wrap: wrap;
  }

  .actions {
    display: flex;
    gap: $space-md;
    align-items: center;
    flex-shrink: 0;
  }
}

.tip {
  margin-bottom: $space-md;
}
</style>
