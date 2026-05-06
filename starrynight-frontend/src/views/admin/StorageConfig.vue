<template>
  <div class="storage-page page-container">
    <header class="storage-hero">
      <div class="storage-hero__text">
        <p class="storage-hero__eyebrow">基础设施</p>
        <h1>对象存储</h1>
        <p class="page-header__lead">
          配置写入系统配置表（键名含 <code>storage.minio</code> 等），保存后由后端即时加载。请勿在业务代码或 YAML 中硬编码密钥。
        </p>
      </div>
      <div class="storage-hero__actions">
        <el-button :icon="Refresh" @click="loadConfigs">刷新</el-button>
        <el-button type="primary" :icon="Plus" @click="openAddDialog">添加存储</el-button>
      </div>
    </header>

    <div class="storage-body">
      <div class="storage-callout" role="note">
        <el-icon class="storage-callout__icon" :size="18"><InfoFilled /></el-icon>
        <span>生产环境请使用强密钥，并定期轮换；默认存储仅可有一条。</span>
      </div>

      <el-row :gutter="20" class="storage-stats">
        <el-col :xs="24" :sm="8">
          <el-card class="storage-stat-card" shadow="never" v-loading="loading">
            <div class="storage-stat">
              <div class="storage-stat__icon storage-stat__icon--sky">
                <el-icon :size="22"><Coin /></el-icon>
              </div>
              <div class="storage-stat__meta">
                <span class="storage-stat__label">总存储容量</span>
                <span class="storage-stat__value">{{ formatBytes(stats.totalStorage) }}</span>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :xs="24" :sm="8">
          <el-card class="storage-stat-card" shadow="never" v-loading="loading">
            <div class="storage-stat">
              <div class="storage-stat__icon storage-stat__icon--violet">
                <el-icon :size="22"><Box /></el-icon>
              </div>
              <div class="storage-stat__meta">
                <span class="storage-stat__label">已使用</span>
                <span class="storage-stat__value">{{ formatBytes(stats.usedStorage) }}</span>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :xs="24" :sm="8">
          <el-card class="storage-stat-card storage-stat-card--usage" shadow="never" v-loading="loading">
            <div class="storage-stat storage-stat--usage">
              <div class="storage-stat__icon storage-stat__icon--amber">
                <el-icon :size="22"><PieChart /></el-icon>
              </div>
              <div class="storage-stat__meta storage-stat__meta--grow">
                <div class="storage-stat__row">
                  <span class="storage-stat__label">使用率</span>
                  <span class="storage-stat__pct">{{ usagePercent }}%</span>
                </div>
                <el-progress
                  :percentage="usagePercent"
                  :stroke-width="10"
                  :show-text="false"
                  color="#6366f1"
                />
              </div>
            </div>
          </el-card>
        </el-col>
      </el-row>

      <el-card class="storage-panel" shadow="never">
        <div class="storage-panel__head">
          <div class="storage-panel__title">
            <span class="storage-panel__name">存储配置</span>
            <el-tag type="info" effect="plain" round size="small">{{ configs.length }} 条</el-tag>
          </div>
        </div>

        <div class="storage-table-wrap">
          <el-table
            :data="configs"
            stripe
            v-loading="loading"
            class="storage-table"
            empty-text="暂无存储配置，点击右上角「添加存储」开始"
          >
            <el-table-column prop="name" label="名称" min-width="140">
              <template #default="{ row }">
                <div class="cell-name">
                  <span class="cell-name__text">{{ row.name }}</span>
                  <el-tag v-if="row.isDefault" type="success" effect="dark" size="small" round>默认</el-tag>
                </div>
              </template>
            </el-table-column>
            <el-table-column prop="type" label="类型" width="130">
              <template #default="{ row }">
                <span class="type-pill" :class="`type-pill--${row.type || 'minio'}`">
                  {{ storageTypeLabel(row.type) }}
                </span>
              </template>
            </el-table-column>
            <el-table-column prop="endpoint" label="端点" min-width="200">
              <template #default="{ row }">
                <code class="cell-mono">{{ row.endpoint }}</code>
              </template>
            </el-table-column>
            <el-table-column prop="bucket" label="存储桶" width="130">
              <template #default="{ row }">
                <code class="cell-mono cell-mono--sm">{{ row.bucket }}</code>
              </template>
            </el-table-column>
            <el-table-column prop="domain" label="访问域名" min-width="160">
              <template #default="{ row }">
                <span class="cell-muted">{{ row.domain || '—' }}</span>
              </template>
            </el-table-column>
            <el-table-column label="状态" width="100" align="center">
              <template #default="{ row }">
                <span class="status-dot" :class="row.enabled ? 'is-on' : 'is-off'" />
                <span class="status-text">{{ row.enabled ? '启用' : '禁用' }}</span>
              </template>
            </el-table-column>
            <el-table-column label="操作" width="220" fixed="right" align="right">
              <template #default="{ row }">
                <el-button link type="primary" @click="openEditDialog(row)">编辑</el-button>
                <el-button link type="primary" @click="testConnection(row)">测试</el-button>
                <el-button v-if="!row.isDefault" link type="warning" @click="setDefault(row)">默认</el-button>
                <el-button v-if="!row.isDefault" link type="danger" @click="deleteConfig(row)">删除</el-button>
              </template>
            </el-table-column>
          </el-table>
        </div>
      </el-card>
    </div>

    <el-dialog
      v-model="dialogVisible"
      :title="isEdit ? '编辑存储配置' : '添加存储配置'"
      width="640px"
      class="storage-dialog"
      align-center
      destroy-on-close
    >
      <el-form ref="formRef" :model="form" :rules="rules" label-width="100px" label-position="top">
        <p class="form-section-title">基础信息</p>
        <el-row :gutter="16">
          <el-col :span="24" :md="12">
            <el-form-item label="配置名称" prop="name">
              <el-input v-model="form.name" placeholder="如：生产环境 MinIO" clearable />
            </el-form-item>
          </el-col>
          <el-col :span="24" :md="12">
            <el-form-item label="存储类型" prop="type">
              <el-select v-model="form.type" placeholder="选择类型" style="width: 100%">
                <el-option label="MinIO" value="minio" />
                <el-option label="阿里云 OSS" value="oss" />
                <el-option label="AWS S3" value="s3" />
                <el-option label="腾讯云 COS" value="cos" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>

        <p class="form-section-title">访问凭证</p>
        <el-form-item label="端点地址" prop="endpoint">
          <el-input
            v-model="form.endpoint"
            placeholder="如 localhost:9000 或 oss-cn-hangzhou.aliyuncs.com"
            clearable
          />
          <span class="form-hint">存储服务的 API 访问地址，不含协议前缀时可按服务商文档填写</span>
        </el-form-item>
        <el-row :gutter="16">
          <el-col :span="24" :md="12">
            <el-form-item label="Access Key" prop="accessKey">
              <el-input v-model="form.accessKey" placeholder="访问密钥 ID" clearable />
            </el-form-item>
          </el-col>
          <el-col :span="24" :md="12">
            <el-form-item label="Secret Key" prop="secretKey">
              <el-input v-model="form.secretKey" type="password" show-password placeholder="私密密钥" clearable />
            </el-form-item>
          </el-col>
        </el-row>

        <p class="form-section-title">存储与访问</p>
        <el-form-item label="存储桶" prop="bucket">
          <el-input v-model="form.bucket" placeholder="Bucket 名称" clearable />
        </el-form-item>
        <el-form-item label="访问域名（可选）">
          <el-input v-model="form.domain" placeholder="CDN 或自定义域名，可不填" clearable />
        </el-form-item>

        <div class="form-switches">
          <el-form-item label="设为默认存储">
            <el-switch v-model="form.isDefault" inline-prompt active-text="是" inactive-text="否" />
          </el-form-item>
          <el-form-item label="启用此配置">
            <el-switch v-model="form.enabled" inline-prompt active-text="开" inactive-text="关" />
          </el-form-item>
        </div>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitForm">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import type { FormInstance, FormRules } from 'element-plus'
import { Refresh, Plus, InfoFilled, Coin, Box, PieChart } from '@element-plus/icons-vue'
import {
  listStorageConfigs,
  createStorageConfig,
  updateStorageConfig,
  deleteStorageConfig,
  testStorageConnection,
  getStorageStats,
  type StorageConfig
} from '@/api/storageAdmin'

const loading = ref(false)
const dialogVisible = ref(false)
const isEdit = ref(false)
const formRef = ref<FormInstance>()

const configs = ref<StorageConfig[]>([])
const stats = ref<StorageConfig>({
  name: '',
  type: '',
  endpoint: '',
  bucket: '',
  enabled: true,
  isDefault: false,
  totalStorage: 0,
  usedStorage: 0
})

const form = reactive<StorageConfig>({
  name: '',
  type: 'minio',
  endpoint: '',
  accessKey: '',
  secretKey: '',
  bucket: '',
  domain: '',
  enabled: true,
  isDefault: false
})

const rules: FormRules = {
  name: [{ required: true, message: '请输入配置名称', trigger: 'blur' }],
  type: [{ required: true, message: '请选择存储类型', trigger: 'change' }],
  endpoint: [{ required: true, message: '请输入端点地址', trigger: 'blur' }],
  accessKey: [{ required: true, message: '请输入访问密钥', trigger: 'blur' }],
  secretKey: [{ required: true, message: '请输入私密密钥', trigger: 'blur' }],
  bucket: [{ required: true, message: '请输入存储桶名称', trigger: 'blur' }]
}

const usagePercent = computed(() => {
  if (!stats.value.totalStorage || stats.value.totalStorage === 0) return 0
  return Math.min(100, Math.round((stats.value.usedStorage / stats.value.totalStorage) * 100))
})

function storageTypeLabel(type: string | undefined) {
  if (!type) return '-'
  const map: Record<string, string> = {
    minio: 'MinIO',
    oss: '阿里云 OSS',
    s3: 'AWS S3',
    cos: '腾讯云 COS'
  }
  return map[type] || type.toUpperCase()
}

function formatBytes(bytes: number | undefined): string {
  if (!bytes) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

async function loadConfigs() {
  loading.value = true
  try {
    const [configsRes, statsRes] = await Promise.all([listStorageConfigs(), getStorageStats()])
    if (configsRes.data) {
      configs.value = configsRes.data
    }
    if (statsRes.data) {
      stats.value = statsRes.data
    }
  } catch {
    ElMessage.error('加载配置失败')
  } finally {
    loading.value = false
  }
}

function openAddDialog() {
  isEdit.value = false
  Object.assign(form, {
    id: undefined,
    name: '',
    type: 'minio',
    endpoint: '',
    accessKey: '',
    secretKey: '',
    bucket: '',
    domain: '',
    enabled: true,
    isDefault: false
  })
  dialogVisible.value = true
}

function openEditDialog(row: StorageConfig) {
  isEdit.value = true
  Object.assign(form, row)
  dialogVisible.value = true
}

async function submitForm() {
  if (!formRef.value) return
  try {
    await formRef.value.validate()
    if (isEdit.value && form.id) {
      await updateStorageConfig(form.id, form)
      ElMessage.success('配置已更新')
    } else {
      await createStorageConfig(form)
      ElMessage.success('配置已创建')
    }
    dialogVisible.value = false
    await loadConfigs()
  } catch {
    ElMessage.error('保存失败')
  }
}

async function testConnection(row: StorageConfig) {
  try {
    await ElMessageBox.confirm(`确定测试「${row.name}」的连接？`, '测试连接', { type: 'info' })
  } catch {
    return
  }
  try {
    await testStorageConnection(row.id!)
    ElMessage.success('连接成功')
  } catch {
    ElMessage.error('连接失败')
  }
}

async function setDefault(row: StorageConfig) {
  try {
    await updateStorageConfig(row.id!, { ...row, isDefault: true })
    ElMessage.success('已设为默认配置')
    await loadConfigs()
  } catch {
    ElMessage.error('设置失败')
  }
}

async function deleteConfig(row: StorageConfig) {
  try {
    await ElMessageBox.confirm(`确定删除「${row.name}」？此操作不可恢复。`, '删除确认', { type: 'warning' })
    await deleteStorageConfig(row.id!)
    ElMessage.success('配置已删除')
    await loadConfigs()
  } catch {
    // 取消
  }
}

onMounted(() => {
  loadConfigs()
})
</script>

<style lang="scss" scoped>
.storage-page {
  min-height: auto;
}

.storage-hero {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  justify-content: space-between;
  gap: $space-lg;
  padding: $space-xl $space-xl $space-lg;

  h1 {
    font-size: $font-size-3xl;
    font-weight: 700;
    letter-spacing: -0.03em;
    color: $text-primary;
    margin: 4px 0 0;
  }

  .page-header__lead {
    margin-top: $space-sm;
    max-width: 52rem;
    color: $text-secondary;
    font-size: $font-size-sm;
    line-height: 1.65;

    code {
      font-size: 0.85em;
      padding: 1px 6px;
      border-radius: $radius-xs;
      background: $primary-ghost;
      color: $primary-light;
    }
  }
}

.storage-hero__eyebrow {
  font-size: $font-size-xs;
  font-weight: 600;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: $text-muted;
  margin: 0;
}

.storage-hero__actions {
  display: flex;
  flex-wrap: wrap;
  gap: $space-sm;
  align-items: center;
}

.storage-body {
  padding: $space-lg $space-xl $space-xl;
  max-width: 1400px;
  margin: 0 auto;
}

.storage-callout {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 12px 16px;
  margin-bottom: $space-lg;
  border-radius: $radius-md;
  border: 1px solid $border-accent;
  background: linear-gradient(90deg, rgba(56, 189, 248, 0.08), rgba(99, 102, 241, 0.04));
  font-size: $font-size-sm;
  color: $text-secondary;
  line-height: 1.5;
}

.storage-callout__icon {
  color: $accent-light;
  flex-shrink: 0;
  margin-top: 1px;
}

.storage-stats {
  margin-bottom: $space-lg;
}

.storage-stat-card {
  border-radius: $radius-lg;
  border: 1px solid $border-subtle;
  margin-bottom: $space-md;
  transition: box-shadow $transition-fast, border-color $transition-fast;

  @media (min-width: 768px) {
    margin-bottom: 0;
  }

  &:hover {
    border-color: $border-accent;
    box-shadow: $glow-primary;
  }

  :deep(.el-card__body) {
    padding: 18px 20px;
  }
}

.storage-stat {
  display: flex;
  align-items: center;
  gap: 16px;
}

.storage-stat--usage {
  align-items: stretch;
}

.storage-stat__icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 52px;
  height: 52px;
  border-radius: 14px;
  color: #fff;
  flex-shrink: 0;
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
}

.storage-stat__icon--sky {
  background: linear-gradient(145deg, #0ea5e9, #0284c7);
}

.storage-stat__icon--violet {
  background: linear-gradient(145deg, #6366f1, #4f46e5);
}

.storage-stat__icon--amber {
  background: linear-gradient(145deg, #f59e0b, #d97706);
}

.storage-stat__meta {
  min-width: 0;
}

.storage-stat__meta--grow {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 10px;
  justify-content: center;
}

.storage-stat__row {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 8px;
}

.storage-stat__label {
  display: block;
  font-size: $font-size-sm;
  color: $text-muted;
  font-weight: 500;
}

.storage-stat__value {
  display: block;
  font-size: $font-size-xl;
  font-weight: 700;
  letter-spacing: -0.02em;
  color: $text-primary;
  margin-top: 2px;
}

.storage-stat__pct {
  font-size: $font-size-lg;
  font-weight: 700;
  color: $text-primary;
  letter-spacing: -0.02em;
}

.storage-panel {
  border-radius: $radius-lg;
  border: 1px solid $border-subtle;
  overflow: hidden;

  :deep(.el-card__body) {
    padding: 0;
  }
}

.storage-panel__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.03), rgba(255, 255, 255, 0.01));
  border-bottom: 1px solid $border-subtle;
}

.storage-panel__title {
  display: flex;
  align-items: center;
  gap: 10px;
}

.storage-panel__name {
  font-weight: 600;
  font-size: $font-size-md;
  color: $text-primary;
  letter-spacing: -0.01em;
}

.storage-table-wrap {
  padding: 0 4px 12px;
}

.type-pill {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  border-radius: $radius-full;
  font-size: $font-size-xs;
  font-weight: 600;
  letter-spacing: 0.02em;
}

.type-pill--minio {
  background: $primary-ghost;
  color: $primary-light;
}

.type-pill--oss,
.type-pill--cos {
  background: rgba(14, 165, 233, 0.12);
  color: #7dd3fc;
}

.type-pill--s3 {
  background: $warning-ghost;
  color: #fbbf24;
}

.cell-mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: $font-size-xs;
  color: $text-secondary;
  word-break: break-all;
}

.cell-mono--sm {
  font-size: 11px;
}

.cell-muted {
  font-size: $font-size-sm;
  color: $text-muted;
}

.cell-name {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.cell-name__text {
  font-weight: 600;
  color: $text-primary;
}

.status-dot {
  display: inline-block;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  margin-right: 6px;
  vertical-align: middle;
}

.status-dot.is-on {
  background: $success-color;
  box-shadow: 0 0 0 3px $success-glow;
}

.status-dot.is-off {
  background: $text-disabled;
}

.status-text {
  font-size: $font-size-sm;
  color: $text-secondary;
  vertical-align: middle;
}

.form-section-title {
  font-size: $font-size-xs;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: $text-muted;
  margin: 8px 0 12px;
  padding-bottom: 6px;
  border-bottom: 1px solid $border-subtle;
}

.form-hint {
  display: block;
  margin-top: 6px;
  font-size: $font-size-xs;
  color: $text-muted;
  line-height: 1.45;
}

.form-switches {
  display: flex;
  flex-wrap: wrap;
  gap: $space-xl;
  margin-top: 8px;
  padding-top: 8px;
  border-top: 1px dashed $border-subtle;

  :deep(.el-form-item) {
    margin-bottom: 0;
  }
}
</style>
