<template>
  <div class="mail-template-config" :class="{ 'mail-template-config--embedded': embedded }">
    <div v-if="!embedded" class="page-header">
      <h1>邮件模板</h1>
      <p class="page-header__hint">
        正文<strong>仅使用已上传的 HTML</strong>（磁盘 <code>{模板键}.html</code>）；邮件标题在下方配置。未上传 HTML 时对应场景无法发信。找回密码须上传「找回密码验证码」模板 HTML。
      </p>
    </div>

    <el-alert
      v-else
      type="info"
      :closable="false"
      show-icon
      class="mail-embedded-alert"
    >
      正文仅为上传的 HTML；请先在本页「邮箱配置」Tab 启用 SMTP。
    </el-alert>

    <div v-loading="pageLoading" class="mail-catalog">
      <template v-if="catalog.length">
        <section class="mail-section">
          <h2 class="mail-section__title">验证码与账号</h2>
          <div class="mail-section__grid">
            <el-card
              v-for="item in verifyItems"
              :key="item.key"
              class="tpl-card"
              shadow="never"
            >
              <template #header>
                <div class="tpl-card__head">
                  <span class="tpl-card__title">{{ item.title }}</span>
                  <el-tag size="small" type="info" effect="plain">{{ item.key }}.html</el-tag>
                </div>
              </template>
              <MailTemplateCardBody
                v-if="forms[item.key]"
                :item="item"
                :form="forms[item.key]"
                :status="statusMap[item.key]"
                :busy-upload="uploadingKey === item.key"
                :busy-save="savingKey === item.key"
                :busy-delete="deletingKey === item.key"
                :upload-nonce="uploadNonce[item.key] ?? 0"
                @upload="(file) => onUpload(item.key, file)"
                @delete-html="onDeleteHtml(item.key)"
                @save="onSaveText(item.key)"
                @refresh="refreshOneStatus(item.key)"
              />
            </el-card>
          </div>
        </section>

        <section class="mail-section">
          <h2 class="mail-section__title">活动与营销</h2>
          <div class="mail-section__grid">
            <el-card
              v-for="item in campaignItems"
              :key="item.key"
              class="tpl-card"
              shadow="never"
            >
              <template #header>
                <div class="tpl-card__head">
                  <span class="tpl-card__title">{{ item.title }}</span>
                  <el-tag size="small" type="info" effect="plain">{{ item.key }}.html</el-tag>
                </div>
              </template>
              <MailTemplateCardBody
                v-if="forms[item.key]"
                :item="item"
                :form="forms[item.key]"
                :status="statusMap[item.key]"
                :busy-upload="uploadingKey === item.key"
                :busy-save="savingKey === item.key"
                :busy-delete="deletingKey === item.key"
                :upload-nonce="uploadNonce[item.key] ?? 0"
                @upload="(file) => onUpload(item.key, file)"
                @delete-html="onDeleteHtml(item.key)"
                @save="onSaveText(item.key)"
                @refresh="refreshOneStatus(item.key)"
              />
            </el-card>
          </div>
        </section>
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import type { SystemConfigItem } from '@/types/api'
import { listSystemConfigs, updateSystemConfig } from '@/api/systemConfig'
import {
  deleteMailTemplateHtml,
  getMailTemplateCatalog,
  getMailTemplateHtmlStatus,
  uploadMailTemplateHtml,
  type MailTemplateCatalogItem,
  type MailTemplateHtmlStatus
} from '@/api/mailTemplate'
import MailTemplateCardBody from './MailTemplateCardBody.vue'

withDefaults(defineProps<{ embedded?: boolean }>(), { embedded: false })

const pageLoading = ref(false)
const catalog = ref<MailTemplateCatalogItem[]>([])
const configRows = ref<Record<string, SystemConfigItem>>({})
const forms = reactive<Record<string, { subject: string }>>({})
const statusMap = reactive<Record<string, MailTemplateHtmlStatus | undefined>>({})

const uploadingKey = ref<string | null>(null)
const savingKey = ref<string | null>(null)
const deletingKey = ref<string | null>(null)
/** 上传成功后递增，子卡片清空「待上传」文件状态 */
const uploadNonce = reactive<Record<string, number>>({})

const verifyItems = computed(() => catalog.value.filter((c) => c.category === 'VERIFY'))
const campaignItems = computed(() => catalog.value.filter((c) => c.category === 'CAMPAIGN'))

function indexByKey(list: SystemConfigItem[], keys: string[]) {
  const m: Record<string, SystemConfigItem> = {}
  for (const k of keys) {
    const row = list.find((c) => c.configKey === k)
    if (row) m[k] = row
  }
  return m
}

function subjectKey(k: string) {
  return `mail.template.${k}.subject`
}

async function refreshOneStatus(key: string) {
  statusMap[key] = await getMailTemplateHtmlStatus(key)
}

async function loadAll() {
  pageLoading.value = true
  try {
    /** 并行请求，且须在赋值 catalog 之前写好 forms，否则 await 间隙会先渲染出「有 catalog、无 forms」导致子组件报错 */
    const [cat, mailList] = await Promise.all([
      getMailTemplateCatalog(),
      listSystemConfigs('mail')
    ])
    const keys: string[] = []
    for (const c of cat) {
      keys.push(subjectKey(c.key))
    }
    configRows.value = indexByKey(mailList, keys)
    for (const c of cat) {
      const sk = subjectKey(c.key)
      forms[c.key] = {
        subject: configRows.value[sk]?.configValue ?? ''
      }
    }
    catalog.value = cat
    await Promise.all(cat.map((c) => refreshOneStatus(c.key)))
  } finally {
    pageLoading.value = false
  }
}

async function onUpload(key: string, file: File) {
  uploadingKey.value = key
  try {
    await uploadMailTemplateHtml(key, file)
    ElMessage.success('HTML 已上传')
    uploadNonce[key] = (uploadNonce[key] ?? 0) + 1
    await refreshOneStatus(key)
  } finally {
    uploadingKey.value = null
  }
}

async function onDeleteHtml(key: string) {
  await ElMessageBox.confirm('删除后该场景将无法发送邮件（直至重新上传 HTML），确定删除？', '删除 HTML 模板', {
    type: 'warning',
    confirmButtonText: '删除',
    cancelButtonText: '取消'
  })
  deletingKey.value = key
  try {
    await deleteMailTemplateHtml(key)
    ElMessage.success('已删除')
    await refreshOneStatus(key)
  } finally {
    deletingKey.value = null
  }
}

async function onSaveText(key: string) {
  const sk = subjectKey(key)
  const rs = configRows.value[sk]
  if (!rs?.id) {
    ElMessage.warning('缺少配置项，请执行 patch_mail_templates_extended.sql 或更新 seed')
    return
  }
  const f = forms[key]
  if (!f) return
  savingKey.value = key
  try {
    await updateSystemConfig({ ...rs, configValue: f.subject.trim(), configType: 'string' })
    ElMessage.success('邮件标题已保存')
    const mailList = await listSystemConfigs('mail')
    const keys: string[] = []
    for (const c of catalog.value) {
      keys.push(subjectKey(c.key))
    }
    configRows.value = indexByKey(mailList, keys)
  } finally {
    savingKey.value = null
  }
}

void loadAll()
</script>

<style scoped lang="scss">
.mail-template-config--embedded {
  :deep(.tpl-card) {
    box-shadow: none;
    border: 1px solid $border-color;
  }
}

.mail-embedded-alert {
  margin-bottom: $space-md;
}

.mail-template-config {
  .page-header__hint {
    margin: $space-sm 0 0;
    font-size: $font-size-sm;
    color: $text-muted;
    max-width: 900px;
    line-height: 1.55;

    code {
      font-size: $font-size-xs;
      padding: 2px 6px;
      border-radius: 4px;
      background: $bg-elevated;
    }
  }
}

.mail-catalog {
  min-height: 120px;
}

.mail-section {
  margin-bottom: $space-xl;

  &__title {
    margin: 0 0 $space-md;
    font-size: $font-size-lg;
    font-weight: 600;
    color: $text-primary;
  }

  &__grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: $space-lg;
  }
}

:deep(.tpl-card) {
  height: 100%;
}

:deep(.tpl-card__head) {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: $space-sm;
  flex-wrap: wrap;
}

:deep(.tpl-card__title) {
  font-weight: 600;
}
</style>
