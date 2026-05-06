<template>
  <div v-loading="loading" class="portal-site-tab">
    <el-alert type="info" :closable="false" show-icon class="tab-alert">
      以下配置写入 <code>system_config</code>（分组 <code>portal</code>）。备案、联系、友链、赞助需<strong>开启开关</strong>后用户端官网页脚才会展示。已配置 MinIO/OSS 时，Logo 与赞助 HTML
      会上传至对象存储；<strong>未配置</strong>时自动保存到服务器本地目录（默认 <code>data/portal-assets</code>，可由配置项
      <code>portal.local-assets.storage-dir</code> 覆盖），并通过 <code>/api/portal/public-asset/…</code> 匿名访问；亦可手动填写任意公网可访问的图片/HTML URL。
    </el-alert>

    <el-form label-width="150px" class="portal-site-form">
      <el-divider content-position="left">站点与资产文案</el-divider>
      <el-form-item label="网站名称">
        <el-input v-model="form.siteName" maxlength="64" show-word-limit placeholder="默认：星夜阁" />
      </el-form-item>
      <el-form-item label="网站 Logo">
        <div class="logo-row">
          <el-input v-model="form.logoUrl" placeholder="图片完整 URL" clearable class="logo-row__input" />
          <el-upload
            :show-file-list="false"
            accept="image/*"
            :http-request="onLogoUpload"
            :disabled="logoUploading"
          >
            <el-button type="primary" plain :loading="logoUploading">上传</el-button>
          </el-upload>
        </div>
        <div v-if="form.logoUrl" class="logo-preview">
          <img :src="form.logoUrl" alt="Logo 预览" referrerpolicy="no-referrer" />
        </div>
      </el-form-item>
      <el-form-item label="平台币名称">
        <el-input v-model="form.coinName" maxlength="32" show-word-limit placeholder="默认：星夜币" />
      </el-form-item>

      <el-divider content-position="left">备案信息</el-divider>
      <el-form-item label="展示备案区块">
        <el-switch v-model="form.icpEnabled" />
      </el-form-item>
      <template v-if="form.icpEnabled">
        <el-form-item label="备案号文案">
          <el-input v-model="form.icpRecord" maxlength="128" placeholder="如 粤ICP备xxxxxxxx号" />
        </el-form-item>
        <el-form-item label="备案查询链接">
          <el-input v-model="form.icpUrl" maxlength="512" placeholder="可选，如 https://beian.miit.gov.cn/" />
        </el-form-item>
      </template>

      <el-divider content-position="left">联系信息</el-divider>
      <el-form-item label="展示联系区块">
        <el-switch v-model="form.contactEnabled" />
      </el-form-item>
      <template v-if="form.contactEnabled">
        <div class="table-toolbar">
          <el-button size="small" type="primary" @click="addContactRow">添加一行</el-button>
        </div>
        <el-table :data="contactRows" border size="small" class="mini-table">
          <el-table-column label="标签" min-width="100">
            <template #default="{ row }">
              <el-input v-model="row.label" placeholder="如 邮箱" />
            </template>
          </el-table-column>
          <el-table-column label="展示文字" min-width="140">
            <template #default="{ row }">
              <el-input v-model="row.text" placeholder="如 hi@example.com" />
            </template>
          </el-table-column>
          <el-table-column label="链接（可选）" min-width="160">
            <template #default="{ row }">
              <el-input v-model="row.href" placeholder="mailto: 或 https://" />
            </template>
          </el-table-column>
          <el-table-column label="操作" width="72" align="center">
            <template #default="{ $index }">
              <el-button type="danger" link size="small" @click="removeContactRow($index)">删除</el-button>
            </template>
          </el-table-column>
        </el-table>
      </template>

      <el-divider content-position="left">友情链接</el-divider>
      <el-form-item label="展示友链区块">
        <el-switch v-model="form.friendLinksEnabled" />
      </el-form-item>
      <template v-if="form.friendLinksEnabled">
        <div class="table-toolbar">
          <el-button size="small" type="primary" @click="addFriendRow">添加一条</el-button>
        </div>
        <el-table :data="friendRows" border size="small" class="mini-table">
          <el-table-column label="名称" min-width="120">
            <template #default="{ row }">
              <el-input v-model="row.name" placeholder="站点名" />
            </template>
          </el-table-column>
          <el-table-column label="URL" min-width="220">
            <template #default="{ row }">
              <el-input v-model="row.url" placeholder="https://..." />
            </template>
          </el-table-column>
          <el-table-column label="操作" width="72" align="center">
            <template #default="{ $index }">
              <el-button type="danger" link size="small" @click="removeFriendRow($index)">删除</el-button>
            </template>
          </el-table-column>
        </el-table>
      </template>

      <el-divider content-position="left">赞助 / 鸣谢</el-divider>
      <el-form-item label="展示赞助区块">
        <el-switch v-model="form.sponsorEnabled" />
      </el-form-item>
      <template v-if="form.sponsorEnabled">
        <el-form-item label="鸣谢页 HTML">
          <div class="sponsor-html-row">
            <el-input
              v-model="form.sponsorHtmlUrl"
              placeholder="上传后自动填入（OSS 外链或本站 /api/portal/public-asset/…），也可粘贴任意可访问的 HTML 地址"
              clearable
              class="sponsor-html-row__input"
            />
            <el-upload
              :show-file-list="false"
              accept=".html,.htm,text/html"
              :http-request="onSponsorHtmlUpload"
              :disabled="sponsorHtmlUploading"
            >
              <el-button type="primary" plain :loading="sponsorHtmlUploading">上传 HTML</el-button>
            </el-upload>
            <el-button v-if="form.sponsorHtmlUrl" link type="primary" @click="openSponsorPreview">新窗口预览</el-button>
          </div>
          <p class="sponsor-html-hint">
            有对象存储时写入桶内 portal/sponsor；无对象存储时写入本地并由后端暴露静态路径。保存整页配置后，用户端官网页脚以 iframe 嵌入该 URL。若库内仍有旧版「纯文本」赞助文案且未填 HTML URL，仍按纯文本展示。
          </p>
        </el-form-item>
      </template>

      <el-form-item>
        <el-button type="primary" :loading="saving" @click="saveAll">保存</el-button>
      </el-form-item>
    </el-form>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import type { SystemConfigItem } from '@/types/api'
import type { FooterContactLine, FooterFriendLink } from '@/api/portalPublic'
import { listSystemConfigs, updateSystemConfig } from '@/api/systemConfig'
import { uploadPortalSiteLogo, uploadPortalSponsorHtml } from '@/api/adminPortalSite'
import { extractApiErrorMessage } from '@/utils/request'

const PORTAL_KEYS = [
  'portal.site.name',
  'portal.site.logo-url',
  'portal.wallet.coin-display-name',
  'portal.footer.icp.enabled',
  'portal.footer.icp.record',
  'portal.footer.icp.url',
  'portal.footer.contact.enabled',
  'portal.footer.contact.lines-json',
  'portal.footer.friend-links.enabled',
  'portal.footer.friend-links.json',
  'portal.footer.sponsor.enabled',
  'portal.footer.sponsor.html-url'
] as const

const loading = ref(false)
const saving = ref(false)
const logoUploading = ref(false)
const sponsorHtmlUploading = ref(false)
const rows = ref<Record<string, SystemConfigItem>>({})

const form = reactive({
  siteName: '星夜阁',
  logoUrl: '',
  coinName: '星夜币',
  icpEnabled: false,
  icpRecord: '',
  icpUrl: '',
  contactEnabled: false,
  friendLinksEnabled: false,
  sponsorEnabled: false,
  sponsorHtmlUrl: ''
})

const contactRows = ref<FooterContactLine[]>([])
const friendRows = ref<FooterFriendLink[]>([])

function indexByKey(list: SystemConfigItem[], keys: readonly string[]) {
  const m: Record<string, SystemConfigItem> = {}
  for (const row of list) {
    if (keys.includes(row.configKey)) m[row.configKey] = row
  }
  return m
}

function parseBool(v: string | undefined) {
  return String(v).toLowerCase() === 'true'
}

function safeJsonArray<T>(raw: string | undefined, fallback: T[]): T[] {
  if (!raw || !raw.trim()) return [...fallback]
  try {
    const a = JSON.parse(raw) as unknown
    return Array.isArray(a) ? (a as T[]) : [...fallback]
  } catch {
    return [...fallback]
  }
}

async function load() {
  loading.value = true
  try {
    const list = await listSystemConfigs('portal')
    rows.value = indexByKey(list, PORTAL_KEYS)
    const g = (k: string) => rows.value[k]?.configValue ?? ''

    form.siteName = g('portal.site.name') || '星夜阁'
    form.logoUrl = g('portal.site.logo-url')
    form.coinName = g('portal.wallet.coin-display-name') || '星夜币'
    form.icpEnabled = parseBool(g('portal.footer.icp.enabled'))
    form.icpRecord = g('portal.footer.icp.record')
    form.icpUrl = g('portal.footer.icp.url')
    form.contactEnabled = parseBool(g('portal.footer.contact.enabled'))
    contactRows.value = safeJsonArray<FooterContactLine>(g('portal.footer.contact.lines-json'), [])
    if (form.contactEnabled && contactRows.value.length === 0) {
      contactRows.value.push({ label: '', text: '', href: '' })
    }
    form.friendLinksEnabled = parseBool(g('portal.footer.friend-links.enabled'))
    friendRows.value = safeJsonArray<FooterFriendLink>(g('portal.footer.friend-links.json'), [])
    if (form.friendLinksEnabled && friendRows.value.length === 0) {
      friendRows.value.push({ name: '', url: '' })
    }
    form.sponsorEnabled = parseBool(g('portal.footer.sponsor.enabled'))
    form.sponsorHtmlUrl = g('portal.footer.sponsor.html-url')
  } finally {
    loading.value = false
  }
}

function addContactRow() {
  contactRows.value.push({ label: '', text: '', href: '' })
}

function removeContactRow(i: number) {
  contactRows.value.splice(i, 1)
}

function addFriendRow() {
  friendRows.value.push({ name: '', url: '' })
}

function removeFriendRow(i: number) {
  friendRows.value.splice(i, 1)
}

async function onSponsorHtmlUpload(opt: { file: File }) {
  sponsorHtmlUploading.value = true
  try {
    const data = await uploadPortalSponsorHtml(opt.file)
    if (data?.url) {
      form.sponsorHtmlUrl = data.url
      ElMessage.success('上传成功，请点击页面底部保存')
    }
  } catch (e: unknown) {
    ElMessage.error(extractApiErrorMessage(e) || '上传失败')
  } finally {
    sponsorHtmlUploading.value = false
  }
}

function openSponsorPreview() {
  const u = form.sponsorHtmlUrl.trim()
  if (u) window.open(u, '_blank', 'noopener,noreferrer')
}

async function onLogoUpload(opt: { file: File }) {
  logoUploading.value = true
  try {
    const data = await uploadPortalSiteLogo(opt.file)
    if (data?.url) {
      form.logoUrl = data.url
      ElMessage.success('上传成功，请记得点击保存')
    }
  } catch (e: unknown) {
    ElMessage.error(extractApiErrorMessage(e) || '上传失败')
  } finally {
    logoUploading.value = false
  }
}

async function persist(key: string, value: string, configType?: string) {
  const row = rows.value[key]
  if (row?.id == null) {
    throw new Error(`缺少配置项 ${key}，请执行数据库补丁 patch_system_config_portal_site.sql`)
  }
  const type = configType ?? row.configType ?? 'string'
  await updateSystemConfig({ ...row, configValue: value, configType: type })
}

function buildContactJson(): string {
  const cleaned = contactRows.value
    .map((r) => ({
      label: (r.label || '').trim(),
      text: (r.text || '').trim(),
      href: (r.href || '').trim() || undefined
    }))
    .filter((r) => r.label && r.text)
  return JSON.stringify(cleaned)
}

function buildFriendJson(): string {
  const cleaned = friendRows.value
    .map((r) => ({
      name: (r.name || '').trim(),
      url: (r.url || '').trim()
    }))
    .filter((r) => r.name && r.url)
  return JSON.stringify(cleaned)
}

async function saveAll() {
  if (form.icpEnabled && !form.icpRecord.trim() && !form.icpUrl.trim()) {
    ElMessage.warning('已开启备案展示时，请至少填写备案号或查询链接之一')
    return
  }
  saving.value = true
  try {
    await persist('portal.site.name', form.siteName.trim() || '星夜阁')
    await persist('portal.site.logo-url', form.logoUrl.trim())
    await persist('portal.wallet.coin-display-name', form.coinName.trim() || '星夜币')
    await persist('portal.footer.icp.enabled', form.icpEnabled ? 'true' : 'false', 'boolean')
    await persist('portal.footer.icp.record', form.icpRecord.trim())
    await persist('portal.footer.icp.url', form.icpUrl.trim())
    await persist('portal.footer.contact.enabled', form.contactEnabled ? 'true' : 'false', 'boolean')
    await persist('portal.footer.contact.lines-json', buildContactJson(), 'json')
    await persist('portal.footer.friend-links.enabled', form.friendLinksEnabled ? 'true' : 'false', 'boolean')
    await persist('portal.footer.friend-links.json', buildFriendJson(), 'json')
    await persist('portal.footer.sponsor.enabled', form.sponsorEnabled ? 'true' : 'false', 'boolean')
    await persist('portal.footer.sponsor.html-url', form.sponsorHtmlUrl.trim())
    ElMessage.success('已保存')
    await load()
  } catch (e: unknown) {
    const msg = e instanceof Error ? e.message : '保存失败'
    ElMessage.error(msg)
  } finally {
    saving.value = false
  }
}

onMounted(() => void load())
</script>

<style scoped lang="scss">
.portal-site-tab {
  max-width: 880px;
}
.tab-alert {
  margin-bottom: $space-lg;
}
.portal-site-form {
  padding-top: $space-sm;
}
.logo-row {
  display: flex;
  gap: $space-sm;
  width: 100%;
  align-items: center;
}
.logo-row__input {
  flex: 1;
  min-width: 0;
}
.logo-preview {
  margin-top: $space-sm;
  img {
    max-height: 48px;
    max-width: 200px;
    object-fit: contain;
    border-radius: $border-radius-sm;
    border: 1px solid $border-color;
    padding: 4px;
    background: $bg-surface;
  }
}
.table-toolbar {
  margin-bottom: $space-sm;
}
.mini-table {
  margin-bottom: $space-md;
}

.sponsor-html-row {
  display: flex;
  flex-wrap: wrap;
  gap: $space-sm;
  align-items: center;
  width: 100%;
}
.sponsor-html-row__input {
  flex: 1;
  min-width: 200px;
}
.sponsor-html-hint {
  margin: $space-sm 0 0;
  font-size: $font-size-xs;
  color: $text-muted;
  line-height: 1.55;
}
</style>
