import type { ResponseVO } from '@/types/api'

export interface FooterContactLine {
  label?: string
  text?: string
  href?: string
}

export interface FooterFriendLink {
  name?: string
  url?: string
}

export interface PortalPublicConfig {
  apiPublicOrigin?: string
  siteName?: string
  siteLogoUrl?: string
  platformCoinName?: string
  footerIcpEnabled?: boolean
  footerIcpRecord?: string
  footerIcpUrl?: string
  footerContactEnabled?: boolean
  footerContactLines?: FooterContactLine[]
  footerFriendLinksEnabled?: boolean
  footerFriendLinks?: FooterFriendLink[]
  footerSponsorEnabled?: boolean
  footerSponsorText?: string
  /** 上传至对象存储的鸣谢页 HTML URL */
  footerSponsorHtmlUrl?: string
  /** 社区发帖自动通过上架 */
  communityAutoPublishPosts?: boolean
}

export async function fetchPortalPublicConfig(): Promise<PortalPublicConfig> {
  const res = await fetch('/api/portal/public-config', {
    headers: { Accept: 'application/json' },
    credentials: 'same-origin'
  })
  if (!res.ok) return {}
  const json = (await res.json()) as ResponseVO<PortalPublicConfig>
  if (json && json.code === 200 && json.data != null) return json.data
  return {}
}
