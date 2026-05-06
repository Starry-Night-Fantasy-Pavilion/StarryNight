import { post } from '@/utils/request'

export function uploadPortalSiteLogo(file: File) {
  const fd = new FormData()
  fd.append('file', file)
  return post<{ url: string }>('/admin/portal/site/logo', fd)
}

export function uploadPortalSponsorHtml(file: File) {
  const fd = new FormData()
  fd.append('file', file)
  return post<{ url: string }>('/admin/portal/site/sponsor-html', fd)
}
