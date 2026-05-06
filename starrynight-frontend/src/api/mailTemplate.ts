import { del, get, post } from '@/utils/request'

export interface MailTemplateHtmlStatus {
  hasFile: boolean
  sizeBytes: number
  lastModifiedMillis: number | null
}

export interface MailTemplateCatalogItem {
  key: string
  title: string
  category: string
  placeholderHint: string
  description: string
  /** 预览占位符示例（与后端一致） */
  previewSampleVariables?: Record<string, string>
}

export interface MailTemplatePreviewResult {
  subject: string
  htmlDocument: string
  bodySource: string
}

/** 内置模板目录（验证码 / 活动 / 营销等） */
export function getMailTemplateCatalog() {
  return get<MailTemplateCatalogItem[]>('/admin/mail-template/catalog')
}

export function getMailTemplateHtmlStatus(templateKey: string) {
  return get<MailTemplateHtmlStatus>(
    `/admin/mail-template/${encodeURIComponent(templateKey)}/status`
  )
}

export function uploadMailTemplateHtml(templateKey: string, file: File) {
  const fd = new FormData()
  fd.append('file', file)
  return post<void>(`/admin/mail-template/${encodeURIComponent(templateKey)}/html`, fd)
}

export function deleteMailTemplateHtml(templateKey: string) {
  return del<void>(`/admin/mail-template/${encodeURIComponent(templateKey)}/html`)
}

/**
 * 预览当前磁盘上的 HTML；占位符可用请求体覆盖。
 * 传入 `_subjectPreview` 时，预览标题以该字符串为准（与表单未保存的标题一致，并与实发使用相同替换规则），否则用库内已保存的标题配置。
 */
export function previewMailTemplate(
  templateKey: string,
  variableOverrides?: Record<string, string>
) {
  return post<MailTemplatePreviewResult>(
    `/admin/mail-template/${encodeURIComponent(templateKey)}/preview`,
    variableOverrides ?? {}
  )
}
