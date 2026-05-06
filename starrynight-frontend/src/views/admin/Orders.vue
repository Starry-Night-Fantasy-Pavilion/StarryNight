<template>
  <div class="admin-orders page-container">
    <div class="page-header">
      <h1>订单管理</h1>
      <div class="actions">
        <el-input v-model="keyword" placeholder="搜索订单号/商品" clearable style="width: 240px;" />
        <el-select v-model="statusFilter" clearable placeholder="状态筛选" style="width: 150px;">
          <el-option label="待支付" :value="0" />
          <el-option label="已支付" :value="1" />
          <el-option label="已退款" :value="2" />
          <el-option label="已关闭" :value="3" />
        </el-select>
        <el-button @click="handleSearch">查询</el-button>
        <el-button type="primary" :loading="exporting" @click="exportCsv">导出CSV</el-button>
      </div>
    </div>

    <el-card>
      <el-table :data="orders" stripe v-loading="loading">
        <el-table-column prop="orderNo" label="订单号" min-width="220" />
        <el-table-column prop="username" label="用户" width="140" />
        <el-table-column prop="productName" label="商品" min-width="180" />
        <el-table-column label="金额" width="120">
          <template #default="{ row }">￥{{ Number(row.amount).toFixed(2) }}</template>
        </el-table-column>
        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="statusTagType(row.status)">{{ statusText(row.status) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="payTime" label="支付时间" width="180" />
        <el-table-column prop="createTime" label="创建时间" width="180" />
        <el-table-column label="操作" width="180" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" @click="openStatusDialog(row)">改状态</el-button>
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

    <el-dialog v-model="statusDialogVisible" title="更新订单状态" width="420px">
      <el-form label-width="80px">
        <el-form-item label="订单号">
          <span>{{ currentOrder?.orderNo }}</span>
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="statusValue" style="width: 220px;">
            <el-option label="待支付" :value="0" />
            <el-option label="已支付" :value="1" />
            <el-option label="已退款" :value="2" />
            <el-option label="已关闭" :value="3" />
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="statusDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitting" @click="submitStatus">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { ElMessage } from 'element-plus'
import type { AdminOrderItem } from '@/types/api'
import { listAdminOrders, updateAdminOrderStatus } from '@/api/order'
import axios from 'axios'
import { useOpsSessionStore } from '@/stores/auth'

const keyword = ref('')
const statusFilter = ref<number | undefined>(undefined)
const currentPage = ref(1)
const pageSize = ref(10)
const total = ref(0)
const loading = ref(false)
const orders = ref<AdminOrderItem[]>([])

const statusDialogVisible = ref(false)
const currentOrder = ref<AdminOrderItem | null>(null)
const statusValue = ref(0)
const submitting = ref(false)
const exporting = ref(false)
const authStore = useOpsSessionStore()

function statusText(status: number) {
  return ['待支付', '已支付', '已退款', '已关闭'][status] || '未知'
}

function statusTagType(status: number) {
  return ['warning', 'success', 'info', 'danger'][status] || 'info'
}

async function loadOrders() {
  loading.value = true
  try {
    const res = await listAdminOrders({
      page: currentPage.value,
      size: pageSize.value,
      keyword: keyword.value || undefined,
      status: statusFilter.value
    })
    orders.value = res.data.records
    total.value = Number(res.data.total)
  } finally {
    loading.value = false
  }
}

function handleSearch() {
  currentPage.value = 1
  loadOrders()
}

function handleSizeChange() {
  currentPage.value = 1
  loadOrders()
}

function handleCurrentChange() {
  loadOrders()
}

function openStatusDialog(row: AdminOrderItem) {
  currentOrder.value = row
  statusValue.value = row.status
  statusDialogVisible.value = true
}

async function submitStatus() {
  if (!currentOrder.value) return
  submitting.value = true
  try {
    await updateAdminOrderStatus(currentOrder.value.id, statusValue.value)
    ElMessage.success('订单状态已更新')
    statusDialogVisible.value = false
    await loadOrders()
  } finally {
    submitting.value = false
  }
}

loadOrders()

async function exportCsv() {
  exporting.value = true
  try {
    const res = await axios.get('/api/admin/orders/export', {
      params: {
        keyword: keyword.value || undefined,
        status: statusFilter.value,
        limit: 5000
      },
      responseType: 'blob',
      headers: authStore.accessToken ? { Authorization: `Bearer ${authStore.accessToken}` } : undefined
    })
    const blob = new Blob([res.data], { type: 'text/csv;charset=utf-8' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `orders_${new Date().toISOString().slice(0, 10)}.csv`
    document.body.appendChild(a)
    a.click()
    a.remove()
    URL.revokeObjectURL(url)
  } catch (e) {
    ElMessage.error('导出失败')
    throw e
  } finally {
    exporting.value = false
  }
}
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
</style>
