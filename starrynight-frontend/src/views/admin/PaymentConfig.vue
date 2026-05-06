<template>
  <div class="payment-config" :class="{ 'payment-config--embedded': embedded }">
    <div v-if="!embedded" class="page-header">
      <h1>支付配置</h1>
      <p class="page-header__hint">
        标准易支付商户参数写入 <code>system_config</code>（<code>payment.epay.*</code>），供充值下单等业务读取。
      </p>
    </div>

    <el-card v-loading="loading">
      <el-form label-width="180px" style="max-width: 720px">
        <el-form-item label="启用易支付">
          <el-switch v-model="epayForm.enabled" />
        </el-form-item>
        <el-form-item label="网关 URL">
          <el-input v-model="epayForm.gateway" placeholder="https://你的易支付域名/submit.php" clearable />
        </el-form-item>
        <el-form-item label="商户 ID（PID）">
          <el-input v-model="epayForm.pid" placeholder="商户后台 PID" clearable />
        </el-form-item>
        <el-form-item label="商户密钥">
          <el-input v-model="epayForm.key" type="password" show-password autocomplete="off" clearable />
        </el-form-item>
        <el-form-item label="签名类型">
          <el-input v-model="epayForm.signType" placeholder="md5" clearable />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" :loading="saving" @click="save">保存</el-button>
        </el-form-item>
      </el-form>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import { ElMessage } from 'element-plus'
import type { SystemConfigItem } from '@/types/api'
import { listSystemConfigs, updateSystemConfig } from '@/api/systemConfig'

withDefaults(defineProps<{ embedded?: boolean }>(), { embedded: false })

const EPAY_KEYS = [
  'payment.epay.enabled',
  'payment.epay.gateway',
  'payment.epay.pid',
  'payment.epay.key',
  'payment.epay.sign-type'
] as const

const loading = ref(false)
const saving = ref(false)
const rows = ref<Record<string, SystemConfigItem>>({})
const epayForm = reactive({
  enabled: false,
  gateway: '',
  pid: '',
  key: '',
  signType: 'md5'
})

function indexByKey(list: SystemConfigItem[], keys: string[]) {
  const m: Record<string, SystemConfigItem> = {}
  for (const k of keys) {
    const row = list.find((c) => c.configKey === k)
    if (row) m[k] = row
  }
  return m
}

async function load() {
  loading.value = true
  try {
    const payList = await listSystemConfigs('payment')
    rows.value = indexByKey(payList, [...EPAY_KEYS])
    const E = rows.value
    epayForm.enabled = (E['payment.epay.enabled']?.configValue ?? 'false').toLowerCase() === 'true'
    epayForm.gateway = E['payment.epay.gateway']?.configValue ?? ''
    epayForm.pid = E['payment.epay.pid']?.configValue ?? ''
    epayForm.key = E['payment.epay.key']?.configValue ?? ''
    epayForm.signType = E['payment.epay.sign-type']?.configValue ?? 'md5'
  } finally {
    loading.value = false
  }
}

async function save() {
  const R = rows.value
  for (const key of EPAY_KEYS) {
    if (!R[key]?.id) {
      ElMessage.warning(`缺少配置 ${key}，请执行 patch_payment_epay_mail_templates.sql 或更新 seed`)
      return
    }
  }
  saving.value = true
  try {
    const patch: { key: (typeof EPAY_KEYS)[number]; val: string; type?: string }[] = [
      { key: 'payment.epay.enabled', val: epayForm.enabled ? 'true' : 'false', type: 'boolean' },
      { key: 'payment.epay.gateway', val: epayForm.gateway.trim(), type: 'string' },
      { key: 'payment.epay.pid', val: epayForm.pid.trim(), type: 'string' },
      { key: 'payment.epay.key', val: epayForm.key, type: 'string' },
      { key: 'payment.epay.sign-type', val: (epayForm.signType || 'md5').trim(), type: 'string' }
    ]
    for (const { key, val, type } of patch) {
      await updateSystemConfig({
        ...R[key]!,
        configValue: val,
        configType: type ?? 'string'
      })
    }
    ElMessage.success('易支付配置已保存')
    await load()
  } finally {
    saving.value = false
  }
}

void load()
</script>

<style scoped lang="scss">
.payment-config--embedded {
  :deep(.el-card) {
    box-shadow: none;
    border: 1px solid $border-color;
  }
}

.payment-config {
  .page-header__hint {
    margin: $space-sm 0 0;
    font-size: $font-size-sm;
    color: $text-muted;
    max-width: 720px;
    line-height: 1.55;

    code {
      font-size: $font-size-xs;
      padding: 2px 6px;
      border-radius: 4px;
      background: $bg-elevated;
    }
  }
}
</style>
