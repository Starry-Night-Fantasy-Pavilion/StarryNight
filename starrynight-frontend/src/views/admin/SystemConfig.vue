<template>
  <div class="admin-system-config page-container">
    <div class="page-header">
      <h1>系统设置</h1>
      <p class="page-header__hint">
        业务参数存于 MySQL <code>system_config</code>。<strong>Redis</strong> 与 <strong>RabbitMQ</strong>（启动时已启用集成时）保存后由后端热切换连接并重启监听器；若启动时未启用 Rabbit 集成，打开集成需<strong>重启后端</strong>。
      </p>
      <p class="page-header__actions">
        <el-button size="small" :loading="reloadRuntimeLoading" @click="reloadRuntimeFromDb">
          从数据库刷新运行时配置
        </el-button>
        <span class="page-header__actions-hint">直改库或执行 SQL 后点一次，等价于触发后端的 reload</span>
      </p>
    </div>

    <el-card>
      <el-tabs v-model="activeTab" class="config-tabs" @tab-change="onTabChange">
        <el-tab-pane label="注册与安全" name="register">
          <el-alert type="info" :closable="false" show-icon class="tab-alert">
            控制前台注册是否展示「邮箱」「手机号」及是否要求「实名」；邮箱/手机关闭后接口拒绝携带对应字段；实名开启后须填写姓名与 18 位身份证。
          </el-alert>
          <div v-loading="registerLoading" class="tab-body">
            <el-form label-width="160px" style="max-width: 520px">
              <el-form-item label="允许邮箱注册">
                <el-switch v-model="registerForm.emailEnabled" />
              </el-form-item>
              <el-form-item label="允许手机号注册">
                <el-switch v-model="registerForm.phoneEnabled" />
              </el-form-item>
              <el-form-item label="注册实名认证">
                <el-switch v-model="registerForm.realnameEnabled" />
              </el-form-item>
              <el-form-item>
                <el-button type="primary" :loading="registerSaving" @click="saveRegisterFlags">保存</el-button>
              </el-form-item>
            </el-form>
          </div>
        </el-tab-pane>

        <el-tab-pane label="Redis" name="redis">
          <el-alert type="info" :closable="false" show-icon class="tab-alert">
            对应 <code>spring.data.redis.*</code>，由后端从 <code>system_config</code> 构建 Redis 连接；保存后自动热切换，一般无需重启（数秒内旧连接会关闭）。
          </el-alert>
          <div v-loading="redisLoading" class="tab-body">
            <el-form label-width="140px" style="max-width: 560px">
              <el-form-item label="主机">
                <el-input v-model="redisForm.host" placeholder="localhost" />
              </el-form-item>
              <el-form-item label="端口">
                <el-input-number v-model="redisForm.port" :min="1" :max="65535" style="width: 100%" />
              </el-form-item>
              <el-form-item label="库索引">
                <el-input-number v-model="redisForm.database" :min="0" :max="255" style="width: 100%" />
              </el-form-item>
              <el-form-item label="密码">
                <el-input v-model="redisForm.password" type="password" show-password placeholder="无密码可留空" autocomplete="off" />
              </el-form-item>
              <el-form-item>
                <el-button type="primary" :loading="redisSaving" @click="saveRedis">保存</el-button>
              </el-form-item>
            </el-form>
          </div>
        </el-tab-pane>

        <el-tab-pane label="RabbitMQ" name="rabbit">
          <el-alert type="info" :closable="false" show-icon class="tab-alert">
            「启用 RabbitMQ 集成」与连接参数存于 <code>system_config</code>。保存后热切换连接并尝试重启监听器（数秒内完成）。若进程是以「未启用集成」启动的，首次打开集成仍须重启后端；关闭集成会停止消费，发消息侧也会跳过投递。
          </el-alert>
          <div v-loading="rabbitLoading" class="tab-body">
            <el-form label-width="180px" style="max-width: 560px">
              <el-form-item label="启用 RabbitMQ 集成">
                <el-switch v-model="rabbitForm.integrationEnabled" />
              </el-form-item>
              <el-form-item label="主机">
                <el-input v-model="rabbitForm.host" placeholder="localhost" />
              </el-form-item>
              <el-form-item label="端口">
                <el-input-number v-model="rabbitForm.port" :min="1" :max="65535" style="width: 100%" />
              </el-form-item>
              <el-form-item label="用户名">
                <el-input v-model="rabbitForm.username" />
              </el-form-item>
              <el-form-item label="密码">
                <el-input v-model="rabbitForm.password" type="password" show-password autocomplete="off" />
              </el-form-item>
              <el-form-item label="虚拟主机">
                <el-input v-model="rabbitForm.virtualHost" placeholder="/" />
              </el-form-item>
              <el-form-item>
                <el-button type="primary" :loading="rabbitSaving" @click="saveRabbit">保存</el-button>
              </el-form-item>
            </el-form>
          </div>
        </el-tab-pane>
      </el-tabs>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import { ElMessage } from 'element-plus'
import type { SystemConfigItem } from '@/types/api'
import { listSystemConfigs, reloadRuntimeSystemConfig, updateSystemConfig } from '@/api/systemConfig'

const activeTab = ref<'register' | 'redis' | 'rabbit'>('register')

const registerLoading = ref(false)
const registerSaving = ref(false)
const registerRows = reactive<{ email?: SystemConfigItem; phone?: SystemConfigItem; realname?: SystemConfigItem }>({})
const registerForm = reactive({ emailEnabled: true, phoneEnabled: true, realnameEnabled: false })

const redisLoading = ref(false)
const redisSaving = ref(false)
const redisRows = ref<Record<string, SystemConfigItem>>({})
const redisForm = reactive({ host: '', port: 6379, database: 0, password: '' })

const rabbitLoading = ref(false)
const rabbitSaving = ref(false)
const rabbitRows = ref<Record<string, SystemConfigItem>>({})
const rabbitForm = reactive({
  integrationEnabled: true,
  host: '',
  port: 5672,
  username: 'guest',
  password: '',
  virtualHost: '/'
})

const reloadRuntimeLoading = ref(false)

async function reloadRuntimeFromDb() {
  reloadRuntimeLoading.value = true
  try {
    await reloadRuntimeSystemConfig()
    ElMessage.success('已从数据库重载运行时配置（含 Redis/Rabbit 热切换钩子）')
    if (activeTab.value === 'register') await loadRegisterFlags()
    if (activeTab.value === 'redis') await loadRedisForm()
    if (activeTab.value === 'rabbit') await loadRabbitForm()
  } finally {
    reloadRuntimeLoading.value = false
  }
}

function onTabChange(name: string | number) {
  if (name === 'register') loadRegisterFlags()
  if (name === 'redis') loadRedisForm()
  if (name === 'rabbit') loadRabbitForm()
}

async function loadRegisterFlags() {
  registerLoading.value = true
  try {
    const list = await listSystemConfigs('auth')
    const email = list.find((c) => c.configKey === 'auth.register.email.enabled')
    const phone = list.find((c) => c.configKey === 'auth.register.phone.enabled')
    const realname = list.find((c) => c.configKey === 'auth.realname.enabled')
    registerRows.email = email
    registerRows.phone = phone
    registerRows.realname = realname
    registerForm.emailEnabled = (email?.configValue ?? 'true').toLowerCase() === 'true'
    registerForm.phoneEnabled = (phone?.configValue ?? 'true').toLowerCase() === 'true'
    registerForm.realnameEnabled = (realname?.configValue ?? 'false').toLowerCase() === 'true'
  } finally {
    registerLoading.value = false
  }
}

async function saveRegisterFlags() {
  if (!registerRows.email?.id || !registerRows.phone?.id || !registerRows.realname?.id) {
    ElMessage.warning('缺少注册/实名开关配置项，请确认已执行最新 seed.sql 或 patch_system_auth_rabbit.sql')
    return
  }
  registerSaving.value = true
  try {
    await updateSystemConfig({
      ...registerRows.email,
      configValue: registerForm.emailEnabled ? 'true' : 'false',
      configType: 'boolean'
    })
    await updateSystemConfig({
      ...registerRows.phone,
      configValue: registerForm.phoneEnabled ? 'true' : 'false',
      configType: 'boolean'
    })
    await updateSystemConfig({
      ...registerRows.realname,
      configValue: registerForm.realnameEnabled ? 'true' : 'false',
      configType: 'boolean'
    })
    ElMessage.success('已保存（前台注册接口即时生效）')
    await loadRegisterFlags()
  } finally {
    registerSaving.value = false
  }
}

function indexByKey(list: SystemConfigItem[], keys: string[]) {
  const m: Record<string, SystemConfigItem> = {}
  for (const k of keys) {
    const row = list.find((c) => c.configKey === k)
    if (row) m[k] = row
  }
  return m
}

async function loadRedisForm() {
  redisLoading.value = true
  try {
    const list = await listSystemConfigs('redis')
    const keys = ['spring.data.redis.host', 'spring.data.redis.port', 'spring.data.redis.database', 'spring.data.redis.password']
    redisRows.value = indexByKey(list, keys)
    redisForm.host = redisRows.value['spring.data.redis.host']?.configValue ?? 'localhost'
    redisForm.port = Number(redisRows.value['spring.data.redis.port']?.configValue ?? 6379)
    redisForm.database = Number(redisRows.value['spring.data.redis.database']?.configValue ?? 0)
    redisForm.password = redisRows.value['spring.data.redis.password']?.configValue ?? ''
  } finally {
    redisLoading.value = false
  }
}

async function saveRedis() {
  const rows = redisRows.value
  const patch = [
    { key: 'spring.data.redis.host', val: redisForm.host },
    { key: 'spring.data.redis.port', val: String(redisForm.port) },
    { key: 'spring.data.redis.database', val: String(redisForm.database) },
    { key: 'spring.data.redis.password', val: redisForm.password ?? '' }
  ] as const
  for (const { key, val } of patch) {
    if (!rows[key]?.id) {
      ElMessage.warning(`缺少配置 ${key}，请检查种子数据`)
      return
    }
  }
  redisSaving.value = true
  try {
    for (const { key, val } of patch) {
      const row = rows[key]!
      await updateSystemConfig({ ...row, configValue: val })
    }
    ElMessage.success('Redis 配置已保存，连接将自动热切换')
  } finally {
    redisSaving.value = false
  }
}

async function loadRabbitForm() {
  rabbitLoading.value = true
  try {
    const list = await listSystemConfigs('rabbitmq')
    const keys = [
      'rabbitmq.integration.enabled',
      'spring.rabbitmq.host',
      'spring.rabbitmq.port',
      'spring.rabbitmq.username',
      'spring.rabbitmq.password',
      'spring.rabbitmq.virtual-host'
    ]
    rabbitRows.value = indexByKey(list, keys)
    rabbitForm.integrationEnabled =
      (rabbitRows.value['rabbitmq.integration.enabled']?.configValue ?? 'true').toLowerCase() === 'true'
    rabbitForm.host = rabbitRows.value['spring.rabbitmq.host']?.configValue ?? 'localhost'
    rabbitForm.port = Number(rabbitRows.value['spring.rabbitmq.port']?.configValue ?? 5672)
    rabbitForm.username = rabbitRows.value['spring.rabbitmq.username']?.configValue ?? 'guest'
    rabbitForm.password = rabbitRows.value['spring.rabbitmq.password']?.configValue ?? ''
    rabbitForm.virtualHost = rabbitRows.value['spring.rabbitmq.virtual-host']?.configValue ?? '/'
  } finally {
    rabbitLoading.value = false
  }
}

async function saveRabbit() {
  const rows = rabbitRows.value
  const patch = [
    { key: 'rabbitmq.integration.enabled', val: rabbitForm.integrationEnabled ? 'true' : 'false', type: 'boolean' as const },
    { key: 'spring.rabbitmq.host', val: rabbitForm.host, type: undefined },
    { key: 'spring.rabbitmq.port', val: String(rabbitForm.port), type: undefined },
    { key: 'spring.rabbitmq.username', val: rabbitForm.username, type: undefined },
    { key: 'spring.rabbitmq.password', val: rabbitForm.password ?? '', type: undefined },
    { key: 'spring.rabbitmq.virtual-host', val: rabbitForm.virtualHost || '/', type: undefined }
  ] as const
  for (const { key } of patch) {
    if (!rows[key]?.id) {
      ElMessage.warning(`缺少配置 ${key}，请执行最新 seed.sql 或 patch_system_auth_rabbit.sql`)
      return
    }
  }
  rabbitSaving.value = true
  try {
    for (const { key, val, type } of patch) {
      const row = rows[key]!
      await updateSystemConfig({
        ...row,
        configValue: val,
        ...(type ? { configType: type } : {})
      })
    }
    ElMessage.success('RabbitMQ 配置已保存，连接与监听器将自动热切换')
  } finally {
    rabbitSaving.value = false
  }
}

loadRegisterFlags()
</script>

<style scoped lang="scss">
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

.page-header__actions {
  margin-top: $space-sm;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: $space-sm;
}

.page-header__actions-hint {
  font-size: $font-size-xs;
  color: $text-muted;
}

.config-tabs {
  :deep(.el-tabs__content) {
    padding-top: $space-md;
  }
}

.tab-alert {
  margin-bottom: $space-md;
}

.tab-body {
  min-height: 120px;
}
</style>
