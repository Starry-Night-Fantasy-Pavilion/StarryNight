<template>
  <div class="tpl-body">
    <p class="tpl-body__desc">{{ item.description }}</p>
    <p class="tpl-body__ph">
      占位符：<code>{{ item.placeholderHint }}</code>
    </p>

    <div v-if="status" class="tpl-body__status">
      <el-tag :type="status.hasFile ? 'success' : 'info'" effect="plain" size="small">
        {{ status.hasFile ? '已上传 HTML' : '未上传 HTML' }}
      </el-tag>
      <span v-if="status.hasFile" class="tpl-body__meta">
        {{ formatBytes(status.sizeBytes) }}
        <template v-if="status.lastModifiedMillis"> · {{ formatTime(status.lastModifiedMillis) }}</template>
      </span>
    </div>

    <div class="tpl-preview-bar">
      <el-button type="primary" plain size="small" :loading="previewServerLoading" @click="openServerPreview">
        预览已保存模板
      </el-button>
      <span class="tpl-preview-bar__hint">预览时用示例占位符替换（站点名等取自系统配置）；内置模板首次启动会写入磁盘</span>
    </div>

    <el-upload
      class="tpl-upload"
      drag
      :show-file-list="false"
      accept=".html,.htm"
      :auto-upload="false"
      :disabled="busyUpload"
      @change="onFileChange"
    >
      <el-icon class="tpl-upload__icon"><UploadFilled /></el-icon>
      <div class="el-upload__text">拖放或 <em>点击选择</em> HTML（≤500KB），选择后可预览本地再上传</div>
    </el-upload>

    <div v-if="pendingName" class="tpl-pending">
      <span class="tpl-pending__name">已选择：<strong>{{ pendingName }}</strong></span>
      <div class="tpl-pending__actions">
        <el-button size="small" :disabled="!pendingRaw" @click="openLocalPreview">预览本地文件</el-button>
        <el-button type="primary" size="small" :loading="busyUpload" :disabled="!pendingRaw" @click="submitUpload">
          上传到服务器
        </el-button>
        <el-button size="small" text type="info" @click="clearPending">清除</el-button>
      </div>
    </div>

    <div class="tpl-body__actions">
      <el-button size="small" @click="emit('refresh')">刷新状态</el-button>
      <el-button
        size="small"
        type="danger"
        plain
        :disabled="!status?.hasFile"
        :loading="busyDelete"
        @click="emit('delete-html')"
      >
        删除 HTML
      </el-button>
    </div>

    <el-divider content-position="left">邮件标题</el-divider>
    <el-form label-width="100px" class="tpl-form">
      <el-form-item label="标题">
        <el-input v-model="form.subject" placeholder="SMTP 邮件标题（可与占位符配合）" clearable />
      </el-form-item>
      <el-form-item>
        <el-button type="primary" size="small" :loading="busySave" @click="emit('save')">保存标题</el-button>
      </el-form-item>
    </el-form>

    <el-dialog
      v-model="previewVisible"
      :title="`预览 · ${item.title}`"
      width="min(960px, 98vw)"
      class="tpl-preview-dialog"
      append-to-body
      top="5vh"
      destroy-on-close
      @closed="onPreviewDialogClosed"
    >
      <div class="tpl-preview-meta">
        <div class="tpl-preview-meta__row">
          <span class="tpl-preview-meta__label">标题</span>
          <span class="tpl-preview-meta__value">{{ previewSubjectDisplay }}</span>
        </div>
        <el-tag v-if="previewBodySource" class="tpl-preview-meta__tag" size="small" effect="plain" type="info">
          {{ previewBodyLabel }}
        </el-tag>
      </div>
      <p class="tpl-preview-hint">
        标题与上方输入框一致（与 SMTP 实发使用相同占位符替换）；正文与磁盘上当前 HTML 一致。外链/图片受 iframe 沙箱限制可能与部分客户端略有差异。
      </p>
      <div class="tpl-preview-device">
        <div class="tpl-preview-device__chrome">
          <span class="tpl-preview-device__dot" aria-hidden="true" />
          <span class="tpl-preview-device__dot" aria-hidden="true" />
          <span class="tpl-preview-device__dot" aria-hidden="true" />
          <span class="tpl-preview-device__title">邮件正文</span>
        </div>
        <div class="tpl-preview-frame-wrap">
          <iframe
            v-if="previewIframeSrcdoc"
            :key="previewIframeKey"
            class="tpl-preview-iframe"
            title="邮件 HTML 预览"
            sandbox="allow-scripts"
            referrerpolicy="no-referrer"
            :srcdoc="previewIframeSrcdoc"
          />
        </div>
      </div>
      <template #footer>
        <el-button type="primary" @click="previewVisible = false">关闭</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { UploadFilled } from '@element-plus/icons-vue'
import type { MailTemplateCatalogItem, MailTemplateHtmlStatus } from '@/api/mailTemplate'
import { previewMailTemplate } from '@/api/mailTemplate'
import type { UploadFile, UploadFiles } from 'element-plus'

const emit = defineEmits<{
  upload: [file: File]
  'delete-html': []
  save: []
  refresh: []
}>()

const props = withDefaults(
  defineProps<{
    item: MailTemplateCatalogItem
    form: { subject: string }
    status: MailTemplateHtmlStatus | undefined
    busyUpload: boolean
    busySave: boolean
    busyDelete: boolean
    /** 父组件在上传成功后递增，用于清空待上传文件选择 */
    uploadNonce?: number
  }>(),
  { uploadNonce: 0 }
)

const pendingRaw = ref<File | null>(null)
const pendingName = ref('')

const previewVisible = ref(false)
const previewIframeSrcdoc = ref('')
const previewIframeKey = ref(0)
const previewSubjectDisplay = ref('')
const previewBodySource = ref('')
const previewServerLoading = ref(false)

function onPreviewDialogClosed() {
  previewIframeSrcdoc.value = ''
}

watch(
  () => props.uploadNonce,
  () => {
    pendingRaw.value = null
    pendingName.value = ''
  }
)

const previewBodyLabel = computed(() => {
  if (previewBodySource.value === 'HTML_FILE') return '来源：已上传 HTML'
  if (previewBodySource.value === 'LOCAL_FILE') return '来源：本地所选文件（示例占位符）'
  return ''
})

function onFileChange(uploadFile: UploadFile, _uploadFiles: UploadFiles) {
  const raw = uploadFile.raw
  if (!raw) return
  if (raw.size > 512000) {
    ElMessage.warning('文件不能超过 500KB')
    return
  }
  pendingRaw.value = raw
  pendingName.value = raw.name
}

function clearPending() {
  pendingRaw.value = null
  pendingName.value = ''
}

function submitUpload() {
  if (!pendingRaw.value) {
    ElMessage.warning('请先选择 HTML 文件')
    return
  }
  emit('upload', pendingRaw.value)
}

function sampleVars(): Record<string, string> {
  return { ...(props.item.previewSampleVariables ?? {}) }
}

function substitute(text: string, vars: Record<string, string>) {
  let s = text
  for (const [k, v] of Object.entries(vars)) {
    s = s.split(`{${k}}`).join(v ?? '')
  }
  return s
}

function ensureHtmlDocument(html: string): string {
  const t = html.trimStart()
  const lower = t.slice(0, 32).toLowerCase()
  if (lower.startsWith('<!doctype') || lower.startsWith('<html')) {
    return html
  }
  return (
    '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8">' +
    '<meta name="viewport" content="width=device-width,initial-scale=1"><title>预览</title>' +
    '<style>' +
    'body{margin:0;background:#f4f4f5;font-family:system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;}' +
    '.sn-mail-preview__shell{max-width:640px;margin:0 auto;padding:28px 16px;}' +
    '.sn-mail-preview__card{background:#fff;border-radius:12px;box-shadow:0 2px 12px rgba(15,23,42,.08);' +
    'overflow:hidden;padding:28px;color:#18181b;font-size:15px;line-height:1.65;}' +
    '</style></head><body>' +
    `<div class="sn-mail-preview__shell"><div class="sn-mail-preview__card">${html}</div></div></body></html>`
  )
}

async function readFileText(file: File): Promise<string> {
  return new Promise((resolve, reject) => {
    const r = new FileReader()
    r.onload = () => resolve(String(r.result ?? ''))
    r.onerror = () => reject(r.error)
    r.readAsText(file, 'UTF-8')
  })
}

async function openServerPreview() {
  previewServerLoading.value = true
  try {
    const res = await previewMailTemplate(props.item.key, {
      _subjectPreview: props.form.subject ?? ''
    })
    previewIframeKey.value += 1
    previewSubjectDisplay.value = res.subject
    previewIframeSrcdoc.value = res.htmlDocument
    previewBodySource.value = res.bodySource
    previewVisible.value = true
  } catch {
    /* 全局已提示 */
  } finally {
    previewServerLoading.value = false
  }
}

async function openLocalPreview() {
  if (!pendingRaw.value) {
    ElMessage.warning('请先选择 HTML 文件')
    return
  }
  try {
    const rawHtml = await readFileText(pendingRaw.value)
    const vars = sampleVars()
    const merged = substitute(rawHtml, vars)
    previewIframeKey.value += 1
    previewSubjectDisplay.value = substitute(props.form.subject || '', vars)
    previewIframeSrcdoc.value = ensureHtmlDocument(merged)
    previewBodySource.value = 'LOCAL_FILE'
    previewVisible.value = true
  } catch (e) {
    ElMessage.error(e instanceof Error ? e.message : '读取文件失败')
  }
}

function formatBytes(n: number) {
  if (n < 1024) return `${n} B`
  if (n < 1024 * 1024) return `${(n / 1024).toFixed(1)} KB`
  return `${(n / (1024 * 1024)).toFixed(1)} MB`
}

function formatTime(ms: number) {
  try {
    return new Date(ms).toLocaleString()
  } catch {
    return ''
  }
}
</script>

<style scoped lang="scss">
.tpl-body__desc {
  margin: 0 0 $space-sm;
  font-size: $font-size-sm;
  color: $text-secondary;
  line-height: 1.55;
}

.tpl-body__ph {
  margin: 0 0 $space-md;
  font-size: $font-size-xs;
  color: $text-muted;

  code {
    font-size: $font-size-xs;
    padding: 1px 5px;
    border-radius: 4px;
    background: $bg-elevated;
  }
}

:deep(.tpl-preview-dialog.el-dialog) {
  border-radius: 14px;
  /* 勿设 overflow:hidden，易与入场动画 transform 叠加导致首帧宽度异常 */

  .el-dialog__header {
    padding-bottom: 8px;
    margin-right: 0;
  }

  .el-dialog__body {
    padding-top: 6px;
    padding-bottom: 8px;
  }
}

.tpl-preview-bar {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: $space-sm;
  margin-bottom: $space-md;

  &__hint {
    font-size: $font-size-xs;
    color: $text-muted;
    line-height: 1.45;
  }
}

.tpl-body__status {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: $space-sm;
  margin-bottom: $space-md;
}

.tpl-body__meta {
  font-size: $font-size-xs;
  color: $text-muted;
}

.tpl-upload {
  width: 100%;

  .tpl-upload__icon {
    font-size: 36px;
    color: var(--el-text-color-secondary);
    margin-bottom: $space-xs;
  }
}

.tpl-pending {
  margin-top: $space-md;
  padding: $space-sm $space-md;
  border-radius: 8px;
  background: $bg-elevated;
  border: 1px dashed $border-color;

  &__name {
    display: block;
    font-size: $font-size-sm;
    margin-bottom: $space-sm;
    color: $text-secondary;
  }

  &__actions {
    display: flex;
    flex-wrap: wrap;
    gap: $space-sm;
  }
}

.tpl-body__actions {
  margin-top: $space-md;
  display: flex;
  gap: $space-sm;
  flex-wrap: wrap;
}

.tpl-form {
  margin-top: $space-sm;

  :deep(.el-form-item:last-child) {
    margin-bottom: 0;
  }
}

.tpl-preview-meta {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: $space-xs;
  margin-bottom: $space-sm;
  font-size: $font-size-sm;

  &__row {
    display: flex;
    align-items: baseline;
    flex-wrap: wrap;
    gap: $space-sm;
    width: 100%;
  }

  &__label {
    color: $text-muted;
    flex-shrink: 0;
  }

  &__value {
    font-weight: 500;
    word-break: break-word;
    flex: 1;
    min-width: 0;
  }

  &__tag {
    align-self: flex-start;
  }
}

.tpl-preview-hint {
  margin: 0 0 $space-md;
  font-size: $font-size-xs;
  color: $text-muted;
  line-height: 1.5;
}

.tpl-preview-device {
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid $border-color;
  background: linear-gradient(165deg, #fafafa 0%, #f4f4f5 48%, #e8e8ec 100%);
  box-shadow:
    0 1px 0 rgba(255, 255, 255, 0.9) inset,
    0 12px 40px rgba(15, 23, 42, 0.08);
}

.tpl-preview-device__chrome {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 10px 14px;
  /* 避免 backdrop-filter 与全屏遮罩叠层时触发部分浏览器合成层/重绘异常 */
  background: rgba(250, 250, 250, 0.92);
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}

.tpl-preview-device__dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #d4d4d8;
  box-shadow: inset 0 1px 1px rgba(255, 255, 255, 0.7);

  &:nth-child(1) {
    background: #fb7185;
  }
  &:nth-child(2) {
    background: #fbbf24;
  }
  &:nth-child(3) {
    background: #34d399;
  }
}

.tpl-preview-device__title {
  margin-left: auto;
  font-size: 11px;
  letter-spacing: 0.02em;
  color: $text-muted;
  font-weight: 500;
}

.tpl-preview-frame-wrap {
  padding: 16px;
  background: transparent;
}

.tpl-preview-iframe {
  display: block;
  width: 100%;
  min-height: min(520px, 62vh);
  height: min(560px, 68vh);
  border: none;
  border-radius: 8px;
  background: #fff;
  box-shadow:
    0 0 0 1px rgba(15, 23, 42, 0.06),
    0 16px 48px rgba(15, 23, 42, 0.12);
}
</style>
