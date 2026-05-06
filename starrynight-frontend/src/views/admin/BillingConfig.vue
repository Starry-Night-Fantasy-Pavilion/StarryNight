<template>
  <div class="billing-config page-container">
    <div class="page-header">
      <h1>计费系统配置</h1>
    </div>

    <el-tabs>
      <el-tab-pane label="全局配置">
        <el-card>
          <template #header>
            <span>全局计费参数</span>
          </template>

          <el-form :model="configForm" label-width="180px">
            <el-form-item label="每日免费额度">
              <el-input-number v-model="configForm.dailyFreeQuota" :min="0" :step="1000" />
              <span class="form-hint">创作点</span>
            </el-form-item>

            <el-form-item label="默认利润率">
              <el-input-number v-model="configForm.defaultProfitMargin" :min="0" :max="1" :step="0.05" :precision="2" />
              <span class="form-hint">{{ (configForm.defaultProfitMargin * 100).toFixed(0) }}%</span>
            </el-form-item>

            <el-form-item label="混合支付默认">
              <el-radio-group v-model="configForm.mixedPaymentDefault">
                <el-radio :value="true">开启</el-radio>
                <el-radio :value="false">关闭</el-radio>
              </el-radio-group>
            </el-form-item>

            <el-form-item label="免费额度重置时间">
              <el-select v-model="configForm.freeQuotaResetHour">
                <el-option v-for="hour in 24" :key="hour - 1" :label="`${hour - 1}:00`" :value="hour - 1" />
              </el-select>
              <span class="form-hint">服务器时区</span>
            </el-form-item>

            <el-form-item>
              <el-button type="primary" @click="saveConfig">保存配置</el-button>
            </el-form-item>
          </el-form>
        </el-card>
      </el-tab-pane>

      <el-tab-pane label="渠道管理">
        <el-card>
          <template #header>
            <div class="header-actions">
              <el-select v-model="channelTypeFilter" clearable placeholder="按类型筛选" style="width: 160px">
                <el-option label="按令牌计费" value="token" />
                <el-option label="按次计费" value="per_call" />
                <el-option label="按秒计费" value="per_second" />
                <el-option label="混合底费" value="hybrid" />
              </el-select>
              <el-button @click="loadChannels">查询</el-button>
              <el-button type="primary" @click="openChannelDialog()">新增渠道</el-button>
            </div>
          </template>

          <el-alert type="info" :closable="false" show-icon class="channel-ai-link-alert">
            此处维护的<strong>计费渠道</strong>与
            <router-link :to="`${adminBase}/ai-config`">AI 配置 → 模型管理</router-link>
            联动：新建模型时须选择渠道，调用时将使用该渠道的密钥与计费类型。
          </el-alert>

          <el-table :data="channels" v-loading="loadingChannels" stripe>
            <el-table-column prop="channelCode" label="渠道编码" width="140" />
            <el-table-column prop="channelName" label="渠道名称" min-width="160" />
            <el-table-column prop="channelType" label="计费类型" width="120">
              <template #default="{ row }">
                <el-tag v-if="row.channelType === 'token'" type="primary">按令牌</el-tag>
                <el-tag v-else-if="row.channelType === 'per_call'" type="success">按次</el-tag>
                <el-tag v-else-if="row.channelType === 'per_second'" type="warning">按秒</el-tag>
                <el-tag v-else type="info">混合</el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="costPer1kInput" label="输入成本" width="120">
              <template #default="{ row }">
                ¥{{ row.costPer1kInput?.toFixed(4) }}/千令牌
              </template>
            </el-table-column>
            <el-table-column prop="costPer1kOutput" label="输出成本" width="120">
              <template #default="{ row }">
                ¥{{ row.costPer1kOutput?.toFixed(4) }}/千令牌
              </template>
            </el-table-column>
            <el-table-column label="类型" width="100">
              <template #default="{ row }">
                <el-tag :type="row.isFree ? 'success' : 'warning'">
                  {{ row.isFree ? '免费' : '付费' }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column label="状态" width="100">
              <template #default="{ row }">
                <el-tag v-if="row.status === 'NORMAL'" type="success">正常</el-tag>
                <el-tag v-else-if="row.status === 'WARNING'" type="warning">警告</el-tag>
                <el-tag v-else-if="row.status === 'CIRCUIT_BROKEN'" type="danger">熔断</el-tag>
                <el-tag v-else type="info">禁用</el-tag>
              </template>
            </el-table-column>
            <el-table-column label="操作" width="180" fixed="right">
              <template #default="{ row }">
                <el-button link type="primary" @click="openChannelDialog(row)">编辑</el-button>
                <el-button link type="success" v-if="!row.enabled" @click="handleEnable(row.id)">启用</el-button>
                <el-button link type="danger" v-else @click="handleDisable(row.id)">禁用</el-button>
                <el-button link type="danger" @click="handleDeleteChannel(row.id)">删除</el-button>
              </template>
            </el-table-column>
          </el-table>
        </el-card>
      </el-tab-pane>

      <el-tab-pane label="成本监控">
        <el-card>
          <template #header>
            <span>今日成本概览</span>
          </template>

          <el-row :gutter="20">
            <el-col :span="6">
              <div class="stat-card">
                <div class="stat-label">今日免费额度总成本</div>
                <div class="stat-value">¥{{ dailyStats.freeCost?.toFixed(2) || '0.00' }}</div>
              </div>
            </el-col>
            <el-col :span="6">
              <div class="stat-card">
                <div class="stat-label">今日付费渠道成本</div>
                <div class="stat-value">¥{{ dailyStats.paidCost?.toFixed(2) || '0.00' }}</div>
              </div>
            </el-col>
            <el-col :span="6">
              <div class="stat-card">
                <div class="stat-label">今日总收入</div>
                <div class="stat-value">¥{{ dailyStats.revenue?.toFixed(2) || '0.00' }}</div>
              </div>
            </el-col>
            <el-col :span="6">
              <div class="stat-card">
                <div class="stat-label">今日毛利</div>
                <div class="stat-value profit">¥{{ calculateProfit() }}</div>
              </div>
            </el-col>
          </el-row>
        </el-card>
      </el-tab-pane>
    </el-tabs>

    <el-dialog v-model="channelDialogVisible" :title="channelForm.id ? '编辑渠道' : '新增渠道'" width="720px">
      <el-form :model="channelForm" label-width="140px">
        <el-form-item label="渠道编码" required>
          <el-input v-model="channelForm.channelCode" :disabled="!!channelForm.id" placeholder="如: openai-gpt4" />
        </el-form-item>

        <el-form-item label="渠道名称" required>
          <el-input v-model="channelForm.channelName" placeholder="如: OpenAI GPT-4" />
        </el-form-item>

        <el-form-item label="计费类型" required>
          <el-select v-model="channelForm.channelType">
            <el-option label="按令牌计费" value="token" />
            <el-option label="按次计费" value="per_call" />
            <el-option label="按秒计费" value="per_second" />
            <el-option label="混合底费" value="hybrid" />
          </el-select>
        </el-form-item>

        <el-divider content-position="left">接口调用</el-divider>

        <el-form-item label="接口地址">
          <el-input v-model="channelForm.apiBaseUrl" placeholder="如：https://api.openai.com/v1" />
          <span class="form-hint">大模型服务的基础地址</span>
        </el-form-item>

        <el-form-item label="接口密钥">
          <el-input v-model="channelForm.apiKey" type="password" show-password placeholder="如：sk- 开头的密钥" />
          <span class="form-hint">大模型服务的访问密钥</span>
        </el-form-item>

        <el-form-item label="模型名称">
          <el-input v-model="channelForm.modelName" placeholder="如: gpt-4o-mini, claude-3-sonnet" />
          <span class="form-hint">使用的模型标识</span>
        </el-form-item>

        <el-divider content-position="left">成本配置</el-divider>

        <el-form-item label="每千输入令牌成本">
          <el-input-number v-model="channelForm.costPer1kInput" :min="0" :precision="4" />
          <span class="form-hint">元</span>
        </el-form-item>

        <el-form-item label="每千输出令牌成本">
          <el-input-number v-model="channelForm.costPer1kOutput" :min="0" :precision="4" />
          <span class="form-hint">元</span>
        </el-form-item>

        <el-form-item label="单次调用成本">
          <el-input-number v-model="channelForm.costPerCall" :min="0" :precision="4" />
          <span class="form-hint">元</span>
        </el-form-item>

        <el-form-item label="每秒成本">
          <el-input-number v-model="channelForm.costPerSecond" :min="0" :precision="4" />
          <span class="form-hint">元</span>
        </el-form-item>

        <el-form-item label="混合底费">
          <el-input-number v-model="channelForm.baseCost" :min="0" :precision="4" />
          <span class="form-hint">元</span>
        </el-form-item>

        <el-divider content-position="left">其他配置</el-divider>

        <el-form-item label="是否免费渠道">
          <el-switch v-model="channelForm.isFree" />
        </el-form-item>

        <el-form-item label="排序">
          <el-input-number v-model="channelForm.sortOrder" :min="0" />
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="channelDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitChannel">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { ADMIN_CONSOLE_BASE_PATH } from '@/config/portal'
import {
  getBillingConfig,
  updateBillingConfig,
  listChannels,
  createChannel,
  updateChannel,
  deleteChannel,
  enableChannel,
  disableChannel,
  getDailyReport,
  type BillingConfigDTO,
  type ChannelDTO
} from '@/api/billingAdmin'

const adminBase = ADMIN_CONSOLE_BASE_PATH

const loadingChannels = ref(false)
const channelDialogVisible = ref(false)
const channelTypeFilter = ref<string>()

const channels = ref<ChannelDTO[]>([])
const dailyStats = ref<any>({})

const configForm = reactive<BillingConfigDTO>({
  dailyFreeQuota: 10000,
  defaultProfitMargin: 0.3,
  mixedPaymentDefault: true,
  freeQuotaResetHour: 0,
  platformCurrencyRate: 10,
  creationPointRate: 1000
})

const channelForm = reactive<ChannelDTO>({
  channelCode: '',
  channelName: '',
  channelType: 'token',
  apiBaseUrl: '',
  apiKey: '',
  modelName: '',
  costPer1kInput: 0,
  costPer1kOutput: 0,
  costPerCall: 0,
  costPerSecond: 0,
  baseCost: 0,
  isFree: false,
  enabled: true,
  sortOrder: 0
})

async function loadConfig() {
  try {
    const res = await getBillingConfig()
    Object.assign(configForm, res)
  } catch (error) {
    console.error('加载计费配置失败:', error)
  }
}

async function saveConfig() {
  try {
    await updateBillingConfig({
      daily_free_quota: configForm.dailyFreeQuota.toString(),
      default_profit_margin: configForm.defaultProfitMargin.toString(),
      mixed_payment_default: configForm.mixedPaymentDefault.toString()
    })
    ElMessage.success('配置已保存')
  } catch (error) {
    ElMessage.error('保存失败')
  }
}

async function loadChannels() {
  loadingChannels.value = true
  try {
    channels.value = (await listChannels(channelTypeFilter.value, undefined)) ?? []
  } finally {
    loadingChannels.value = false
  }
}

function openChannelDialog(row?: ChannelDTO) {
  if (row) {
    Object.assign(channelForm, row)
  } else {
    Object.assign(channelForm, {
      id: undefined,
      channelCode: '',
      channelName: '',
      channelType: 'token',
      apiBaseUrl: '',
      apiKey: '',
      modelName: '',
      costPer1kInput: 0,
      costPer1kOutput: 0,
      costPerCall: 0,
      costPerSecond: 0,
      baseCost: 0,
      isFree: false,
      enabled: true,
      sortOrder: 0
    })
  }
  channelDialogVisible.value = true
}

async function submitChannel() {
  try {
    if (channelForm.id) {
      await updateChannel(channelForm.id, channelForm)
      ElMessage.success('渠道已更新')
    } else {
      await createChannel(channelForm)
      ElMessage.success('渠道已创建')
    }
    channelDialogVisible.value = false
    await loadChannels()
  } catch (error) {
    ElMessage.error('保存失败')
  }
}

async function handleEnable(id?: number) {
  if (!id) return
  try {
    await enableChannel(id)
    ElMessage.success('渠道已启用')
    await loadChannels()
  } catch (error) {
    ElMessage.error('操作失败')
  }
}

async function handleDisable(id?: number) {
  if (!id) return
  try {
    await disableChannel(id)
    ElMessage.success('渠道已禁用')
    await loadChannels()
  } catch (error) {
    ElMessage.error('操作失败')
  }
}

async function handleDeleteChannel(id?: number) {
  if (!id) return
  await ElMessageBox.confirm('确认删除该渠道吗？', '删除确认', { type: 'warning' })
  try {
    await deleteChannel(id)
    ElMessage.success('渠道已删除')
    await loadChannels()
  } catch (error) {
    ElMessage.error('删除失败')
  }
}

async function loadDailyStats() {
  try {
    const res = await getDailyReport()
    dailyStats.value = res || {}
  } catch (error) {
    console.error('加载今日成本统计失败:', error)
  }
}

function calculateProfit(): string {
  const revenue = dailyStats.value.revenue || 0
  const cost = (dailyStats.value.freeCost || 0) + (dailyStats.value.paidCost || 0)
  const profit = revenue - cost
  const rate = revenue > 0 ? ((profit / revenue) * 100).toFixed(0) : 0
  return `¥${profit.toFixed(2)} (${rate}%)`
}

onMounted(() => {
  loadConfig()
  loadChannels()
  loadDailyStats()
})
</script>

<style lang="scss" scoped>
.channel-ai-link-alert {
  margin-bottom: $space-md;
}

.header-actions {
  display: flex;
  gap: $space-md;
  align-items: center;
  flex-wrap: wrap;
}

.form-hint {
  margin-left: $space-sm;
  color: $text-muted;
  font-size: $font-size-sm;
}

.stat-card {
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(139, 92, 246, 0.1));
  border: 1px solid $border-subtle;
  border-radius: $radius-lg;
  padding: $space-lg;
  text-align: center;
  transition: all $transition-fast;

  &:hover {
    border-color: $border-accent;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(139, 92, 246, 0.15));
    transform: translateY(-2px);
    box-shadow: $glow-primary;
  }

  .stat-label {
    font-size: $font-size-sm;
    color: $text-muted;
    margin-bottom: $space-md;
    font-weight: 500;
  }

  .stat-value {
    font-size: $font-size-2xl;
    font-weight: 700;
    color: $text-primary;
    letter-spacing: -0.02em;

    &.profit {
      color: $success-color;
      text-shadow: $success-glow;
    }
  }
}
</style>
