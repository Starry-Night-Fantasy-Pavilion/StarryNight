/** 与 portal public-config 中 communityAutoPublishPosts 同步；默认 true（全自动发布） */
let communityAutoPublishPosts = true

export function setCommunityAutoPublishFromPortal(value: boolean | undefined) {
  if (typeof value === 'boolean') {
    communityAutoPublishPosts = value
  }
}

export function isCommunityAutoPublishPosts(): boolean {
  return communityAutoPublishPosts
}
