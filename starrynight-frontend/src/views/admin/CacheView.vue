<template>
  <div class="admin-cache page-container">
    <div class="page-header">
      <h1>缓存查看</h1>
    </div>

    <el-alert
      type="warning"
      :closable="false"
      class="tip"
      title="展示 Redis 中的键值预览（Spring Cache 一般为「缓存名::键」）。删除键或清空缓存区会影响在线用户会话与配置缓存，请谨慎操作。"
    />

    <el-card class="block">
      <template #header>Spring 缓存区</template>
      <div class="row">
        <el-select v-model="springName" placeholder="选择缓存区" filterable style="width: 260px">
          <el-option v-for="n in springNames" :key="n" :label="n" :value="n" />
        </el-select>
        <el-button type="danger" plain :disabled="!springName" @click="clearSpring">清空该缓存区</el-button>
        <el-button @click="loadNames">刷新列表</el-button>
      </div>
    </el-card>

    <el-card class="block">
      <template #header>Redis 键扫描</template>
      <div class="row">
        <el-input v-model="pattern" placeholder="如 userInfo::* 或 *systemConfig*" style="width: 320px" clearable />
        <el-input-number v-model="limit" :min="1" :max="500" />
        <el-button type="primary" :loading="scanning" @click="scan">扫描</el-button>
      </div>
      <el-table :data="keys" stripe class="mt" max-height="420">
        <el-table-column prop="key" label="Key" min-width="220" show-overflow-tooltip />
        <el-table-column prop="ttlSeconds" label="TTL(s)" width="100" />
        <el-table-column prop="valuePreview" label="预览" min-width="280" show-overflow-tooltip />
        <el-table-column label="操作" width="160" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link @click="showFull(row.key)">全文</el-button>
            <el-popconfirm title="确定删除该 Redis 键？" @confirm="delKey(row.key)">
              <template #reference>
                <el-button type="danger" link>删除</el-button>
              </template>
            </el-popconfirm>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <el-dialog v-model="fullDlg" title="值预览" width="640px">
      <el-input v-model="fullText" type="textarea" :rows="16" readonly />
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import type { CacheKeyEntry } from '@/api/cacheAdmin'
import {
  clearSpringCache,
  deleteRedisKey,
  getRedisValuePreview,
  listCacheNames,
  scanRedisKeys
} from '@/api/cacheAdmin'

const springNames = ref<string[]>([])
const springName = ref('')
const pattern = ref('userInfo::*')
const limit = ref(100)
const keys = ref<CacheKeyEntry[]>([])
const scanning = ref(false)
const fullDlg = ref(false)
const fullText = ref('')

async function loadNames() {
  springNames.value = await listCacheNames()
}

async function clearSpring() {
  if (!springName.value) return
  await ElMessageBox.confirm(`确定清空缓存区「${springName.value}」？`, '确认', { type: 'warning' })
  await clearSpringCache(springName.value)
  ElMessage.success('已清空')
}

async function scan() {
  scanning.value = true
  try {
    keys.value = await scanRedisKeys({ pattern: pattern.value || undefined, limit: limit.value })
  } finally {
    scanning.value = false
  }
}

async function showFull(key: string) {
  fullText.value = await getRedisValuePreview(key)
  fullDlg.value = true
}

async function delKey(key: string) {
  await deleteRedisKey(key)
  ElMessage.success('已删除')
  await scan()
}

onMounted(loadNames)
</script>

<style scoped lang="scss">
.tip {
  margin-bottom: $space-md;
}

.block {
  margin-bottom: $space-md;
}

.row {
  display: flex;
  gap: $space-md;
  align-items: center;
  flex-wrap: wrap;
}

.mt {
  margin-top: $space-md;
}
</style>
