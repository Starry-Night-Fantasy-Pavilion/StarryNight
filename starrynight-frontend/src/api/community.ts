import { del, get, post, put } from '@/utils/request'
import type {
  CommunityCommentItem,
  CommunityLikeResult,
  CommunityPostItem,
  CommunityReportCreateBody,
  PageVO
} from '@/types/api'

export function listCommunityPosts(page = 1, size = 10) {
  return get<PageVO<CommunityPostItem>>('/community/post/list', { params: { page, size } })
}

export function getCommunityPost(id: number) {
  return get<CommunityPostItem>(`/community/post/${id}`)
}

/** 登录用户查看自己的帖子（任意审核状态，含驳回原因） */
export function getCommunityPostForAuthor(id: number) {
  return get<CommunityPostItem>(`/community/author/post/${id}`)
}

export function createCommunityPost(body: {
  title?: string
  content: string
  contentType?: string
  topicId?: number
}) {
  return post<CommunityPostItem>('/community/post', body)
}

export function updateCommunityPost(
  id: number,
  body: { title?: string; content: string; contentType?: string; topicId?: number }
) {
  return put<CommunityPostItem>(`/community/post/${id}`, body)
}

export function deleteCommunityPost(id: number) {
  return del<void>(`/community/post/${id}`)
}

export function listCommunityComments(postId: number, page = 1, size = 20) {
  return get<PageVO<CommunityCommentItem>>(`/community/post/${postId}/comments`, {
    params: { page, size }
  })
}

export function createCommunityComment(body: {
  postId: number
  parentId?: number | null
  content: string
}) {
  return post<CommunityCommentItem>('/community/comment', body)
}

export function deleteCommunityComment(id: number) {
  return del<void>(`/community/comment/${id}`)
}

export function toggleCommunityPostLike(postId: number) {
  return post<CommunityLikeResult>(`/community/post/${postId}/like`)
}

export function createCommunityReport(body: CommunityReportCreateBody) {
  return post<void>('/community/report', body)
}
