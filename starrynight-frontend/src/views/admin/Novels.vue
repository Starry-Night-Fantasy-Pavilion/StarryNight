<template>
  <div class="admin-novels page-container">
    <div class="page-header">
      <h1>作品管理</h1>
    </div>

    <div class="page-content">
      <el-card>
        <template #header>
          <div class="card-header">
            <el-input
              v-model="searchKeyword"
              placeholder="搜索作品名称"
              style="width: 300px;"
              clearable
            >
              <template #prefix>
                <el-icon><Search /></el-icon>
              </template>
            </el-input>
            <el-select v-model="auditStatus" placeholder="审核状态" style="width: 150px;">
              <el-option label="全部" value="" />
              <el-option label="待审核" :value="0" />
              <el-option label="已通过" :value="1" />
              <el-option label="已拒绝" :value="2" />
            </el-select>
            <el-button type="primary" @click="handleSearch">搜索</el-button>
          </div>
        </template>

        <el-table :data="novels" stripe v-loading="loading">
          <el-table-column prop="id" label="编号" width="80" />
          <el-table-column prop="title" label="作品名称" />
          <el-table-column prop="username" label="作者" />
          <el-table-column prop="genre" label="题材" />
          <el-table-column prop="wordCount" label="字数" width="100">
            <template #default="{ row }">
              {{ row.wordCount || 0 }}
            </template>
          </el-table-column>
          <el-table-column prop="auditStatus" label="审核状态">
            <template #default="{ row }">
              <el-tag
                :type="getAuditType(row.auditStatus || 0)"
                size="small"
              >
                {{ getAuditText(row.auditStatus || 0) }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="createTime" label="创建时间" />
          <el-table-column label="操作" width="180">
            <template #default="{ row }">
              <el-button type="primary" link size="small" @click="handleView(row)">查看</el-button>
              <el-button
                v-if="row.auditStatus === 0"
                type="success"
                link
                size="small"
                @click="handleApprove(row)"
              >
                通过
              </el-button>
              <el-button
                v-if="row.auditStatus === 0"
                type="danger"
                link
                size="small"
                @click="handleReject(row)"
              >
                拒绝
              </el-button>
            </template>
          </el-table-column>
        </el-table>

        <div class="pagination">
          <el-pagination
            v-model:current-page="currentPage"
            v-model:page-size="pageSize"
            :total="total"
            :page-sizes="[10, 20, 50, 100]"
            layout="total, sizes, prev, pager, next"
            @size-change="handleSizeChange"
            @current-change="handleCurrentChange"
          />
        </div>
      </el-card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { listNovels, exportNovel, approveNovel, rejectNovel } from '@/api/novel'
import type { Novel } from '@/types/api'
import { Search } from '@element-plus/icons-vue'

interface NovelVO extends Novel {
  username?: string
  wordCount?: number
  auditStatus?: number
}

const searchKeyword = ref('')
const auditStatus = ref<number | ''>('')
const currentPage = ref(1)
const pageSize = ref(10)
const total = ref(0)
const novels = ref<NovelVO[]>([])
const loading = ref(false)

async function fetchNovels() {
  loading.value = true
  try {
    const params: Record<string, any> = {
      page: currentPage.value,
      size: pageSize.value
    }
    if (searchKeyword.value) {
      params.title = searchKeyword.value
    }
    if (auditStatus.value !== '') {
      params.auditStatus = auditStatus.value
    }

    const res = await listNovels(params)
    if (res.code === 0 && res.data) {
      novels.value = res.data.records || []
      total.value = res.data.total || 0
    } else {
      ElMessage.error(res.message || '获取作品列表失败')
    }
  } catch (error: any) {
    ElMessage.error(error.message || '请求失败')
  } finally {
    loading.value = false
  }
}

function getAuditType(status: number) {
  const types = ['warning', 'success', 'danger']
  return types[status] || 'info'
}

function getAuditText(status: number) {
  const texts = ['待审核', '已通过', '已拒绝']
  return texts[status] || '未知'
}

function handleSearch() {
  currentPage.value = 1
  fetchNovels()
}

function handleSizeChange() {
  currentPage.value = 1
  fetchNovels()
}

function handleCurrentChange() {
  fetchNovels()
}

function handleView(row: NovelVO) {
  ElMessage.info(`查看作品: ${row.title}`)
}

async function handleApprove(row: NovelVO) {
  try {
    await approveNovel(row.id)
    ElMessage.success(`已通过作品: ${row.title}`)
    await fetchNovels()
  } catch {
    ElMessage.error('操作失败')
  }
}

async function handleReject(row: NovelVO) {
  try {
    await rejectNovel(row.id)
    ElMessage.warning(`已拒绝作品: ${row.title}`)
    await fetchNovels()
  } catch {
    ElMessage.error('操作失败')
  }
}

onMounted(() => {
  fetchNovels()
})
</script>

<style lang="scss" scoped>
.card-header {
  display: flex;
  align-items: center;
  gap: $space-md;
  flex-wrap: wrap;
}

.pagination {
  margin-top: $space-lg;
  display: flex;
  justify-content: flex-end;
}
</style>
