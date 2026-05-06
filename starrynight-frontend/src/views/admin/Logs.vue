<template>
  <div class="admin-logs page-container">
    <div class="page-header">
      <h1>操作日志</h1>
      <div class="actions">
        <el-input v-model="operation" placeholder="操作名称" clearable style="width: 160px" />
        <el-input v-model="module" placeholder="模块" clearable style="width: 160px" />
        <el-input v-model="userIdText" placeholder="用户ID" clearable style="width: 120px" />
        <el-date-picker
          v-model="timeRange"
          type="datetimerange"
          start-placeholder="开始时间"
          end-placeholder="结束时间"
          value-format="YYYY-MM-DD HH:mm:ss"
          format="YYYY-MM-DD HH:mm:ss"
          style="width: 380px"
        />
        <el-button @click="handleSearch">查询</el-button>
      </div>
    </div>

    <el-card>
      <el-table :data="rows" v-loading="loading" stripe>
        <el-table-column prop="id" label="编号" width="90" />
        <el-table-column prop="username" label="用户" width="120" />
        <el-table-column prop="operation" label="操作" min-width="160" />
        <el-table-column prop="module" label="模块" width="140" />
        <el-table-column prop="requestMethod" label="方法" width="90" />
        <el-table-column prop="requestUrl" label="请求地址" min-width="240" show-overflow-tooltip />
        <el-table-column label="状态" width="90">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'danger'">
              {{ row.status === 1 ? '成功' : '失败' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="executionTime" label="耗时(ms)" width="110" />
        <el-table-column prop="createTime" label="时间" width="180" />
        <el-table-column label="操作" width="180" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" @click="openDetail(row)">详情</el-button>
            <el-button link type="danger" @click="handleDelete(row.id)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination">
        <el-pagination
          v-model:current-page="currentPage"
          v-model:page-size="pageSize"
          :total="total"
          :page-sizes="[10, 20, 50]"
          layout="total, sizes, prev, pager, next"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
        />
      </div>
    </el-card>

    <el-dialog v-model="detailVisible" title="日志详情" width="820px">
      <el-descriptions :column="2" border>
        <el-descriptions-item label="用户ID">{{ current?.userId }}</el-descriptions-item>
        <el-descriptions-item label="用户名">{{ current?.username }}</el-descriptions-item>
        <el-descriptions-item label="操作">{{ current?.operation }}</el-descriptions-item>
        <el-descriptions-item label="模块">{{ current?.module }}</el-descriptions-item>
        <el-descriptions-item label="请求方法">{{ current?.requestMethod }}</el-descriptions-item>
        <el-descriptions-item label="请求地址">{{ current?.requestUrl }}</el-descriptions-item>
        <el-descriptions-item label="来源地址">{{ current?.ipAddress }}</el-descriptions-item>
        <el-descriptions-item label="耗时(ms)">{{ current?.executionTime }}</el-descriptions-item>
        <el-descriptions-item label="时间">{{ current?.createTime }}</el-descriptions-item>
        <el-descriptions-item label="状态">
          {{ current?.status === 1 ? '成功' : '失败' }}
        </el-descriptions-item>
      </el-descriptions>

      <el-divider />
      <div class="detail-block">
        <div class="detail-title">请求参数</div>
        <el-input :model-value="current?.requestParams" type="textarea" :rows="6" readonly />
      </div>
      <div class="detail-block">
        <div class="detail-title">响应数据</div>
        <el-input :model-value="current?.responseData" type="textarea" :rows="6" readonly />
      </div>
      <div class="detail-block" v-if="current?.status === 0">
        <div class="detail-title">错误信息</div>
        <el-input :model-value="current?.errorMessage" type="textarea" :rows="4" readonly />
      </div>
      <div class="detail-block">
        <div class="detail-title">用户代理</div>
        <el-input :model-value="current?.userAgent" type="textarea" :rows="3" readonly />
      </div>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import type { OperationLogItem } from '@/types/api'
import { deleteOperationLog, listOperationLogs } from '@/api/log'

const operation = ref('')
const module = ref('')
const userIdText = ref('')
const timeRange = ref<[string, string] | null>(null)

const currentPage = ref(1)
const pageSize = ref(20)
const total = ref(0)
const rows = ref<OperationLogItem[]>([])
const loading = ref(false)

const detailVisible = ref(false)
const current = ref<OperationLogItem | null>(null)

function parseUserId() {
  const v = userIdText.value.trim()
  if (!v) return undefined
  const n = Number(v)
  return Number.isFinite(n) ? n : undefined
}

async function loadLogs() {
  loading.value = true
  try {
    const res = await listOperationLogs({
      page: currentPage.value,
      size: pageSize.value,
      userId: parseUserId(),
      operation: operation.value || undefined,
      module: module.value || undefined,
      startTime: timeRange.value?.[0],
      endTime: timeRange.value?.[1]
    })
    rows.value = res.data.records
    total.value = Number(res.data.total)
  } finally {
    loading.value = false
  }
}

function handleSearch() {
  currentPage.value = 1
  loadLogs()
}

function handleSizeChange() {
  currentPage.value = 1
  loadLogs()
}

function handleCurrentChange() {
  loadLogs()
}

function openDetail(row: OperationLogItem) {
  current.value = row
  detailVisible.value = true
}

async function handleDelete(id: number) {
  await ElMessageBox.confirm('确认删除该日志记录吗？', '删除确认', { type: 'warning' })
  await deleteOperationLog(id)
  ElMessage.success('已删除')
  await loadLogs()
}

loadLogs()
</script>

<style scoped lang="scss">
.actions {
  display: flex;
  gap: $space-md;
  align-items: center;
  flex-wrap: wrap;
}

.pagination {
  margin-top: $space-lg;
  display: flex;
  justify-content: flex-end;
}

.detail-block {
  margin-top: $space-md;
}

.detail-title {
  font-size: $font-size-md;
  font-weight: 700;
  color: $text-primary;
  margin-bottom: $space-sm;
  letter-spacing: -0.01em;
}
</style>

