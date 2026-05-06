export interface ResponseVO<T = any> {
  code: number
  message: string
  data: T
}

export interface PageVO<T = any> {
  total: number
  records: T[]
  current: number
  size: number
}

export type AuthPortalType = 'USER' | 'OPS'

export interface LoginDTO {
  username: string
  password: string
  /** USER 用户端（默认），OPS 运营端 */
  portal?: AuthPortalType
}

/** GET /api/auth/oauth/options 返回：已启用的第三方登录渠道 */
export interface OauthLoginOptionsVO {
  linuxdoEnabled: boolean
  githubEnabled: boolean
  googleEnabled: boolean
  wechatEnabled: boolean
  qqEnabled: boolean
  /** 知我云聚合总开关 */
  zevostEnabled: boolean
  /** key 为聚合 type（qq、wx、github…），见 https://u.zevost.com/doc.php */
  zevostTypes: Record<string, boolean>
}

export interface RegisterDTO {
  username: string
  password: string
  email?: string
  phone?: string
}

export interface AuthVO {
  accessToken: string
  refreshToken: string
  expiresIn: number
  authPortal?: AuthPortalType
  user: UserInfo
}

export interface UserInfo {
  id: number
  username: string
  email?: string
  phone?: string
  avatar?: string
  status: number
  isAdmin: number
  /** 运营端：admin_role.code */
  roleCode?: string
  roleName?: string
  /** 前台：实名核验是否已通过 */
  realNameVerified?: boolean
}

export interface SystemConfigItem {
  id?: number
  configKey: string
  configValue: string
  configType?: string
  configName: string
  configGroup?: string
  description?: string
  editable?: number
}

export interface AnnouncementItem {
  id?: number
  title: string
  content: string
  status: number
  publishTime?: string
}

/** 用户端社区帖子（列表/详情/发帖返回） */
export interface CommunityPostItem {
  id: number
  userId: number
  authorUsername?: string
  title?: string | null
  content: string
  contentType?: string
  topicId?: number | null
  likeCount?: number
  commentCount?: number
  viewCount?: number
  /** 当前用户是否已点赞（详情等接口，需登录） */
  likedByMe?: boolean
  /** 是否可评论/点赞（已审核且上架） */
  interactionEnabled?: boolean
  /** 0 待审 1 通过 2 驳回 */
  auditStatus?: number
  rejectReason?: string | null
  createTime?: string
}

export interface CommunityCommentItem {
  id: number
  postId: number
  userId: number
  authorUsername?: string
  parentId?: number | null
  content: string
  /** 0 待审 1 通过 2 驳回；未登录用户列表仅含已通过 */
  auditStatus?: number
  moderationNote?: string | null
  createTime?: string
}

export interface CommunityLikeResult {
  liked: boolean
  likeCount: number
}

/** 运营端社区帖子审核 */
export interface AdminCommunityPostItem {
  id: number
  userId: number
  authorUsername?: string
  title?: string | null
  content: string
  contentType?: string
  topicId?: number | null
  /** 0 待审 1 通过 2 驳回 */
  auditStatus: number
  rejectReason?: string | null
  likeCount: number
  commentCount: number
  viewCount: number
  /** 1 展示 0 运营下架 */
  onlineStatus: number
  createTime?: string
  updateTime?: string
}

/** 运营端社区评论 */
export interface AdminCommunityCommentItem {
  id: number
  postId: number
  postTitle?: string
  userId: number
  authorUsername?: string
  parentId?: number | null
  content: string
  /** 0 待审 1 通过 2 驳回 */
  auditStatus?: number
  moderationNote?: string | null
  createTime?: string
}

/** 运营端待处理工单（帖子待审 + 评论待审合并队列） */
export interface CommunityWorkOrderItem {
  kind: 'POST' | 'COMMENT'
  targetId: number
  postId: number
  commentId?: number | null
  userId: number
  username?: string
  titleSnippet?: string
  contentPreview?: string
  reasonNote?: string
  createTime?: string
}

/** 用户端发起举报 */
export interface CommunityReportCreateBody {
  kind: 'POST' | 'COMMENT'
  postId?: number
  commentId?: number
  reason: string
  detail?: string
}

/** 运营端举报工单 */
export interface AdminCommunityReportItem {
  id: number
  kind: 'POST' | 'COMMENT'
  postId: number
  commentId?: number | null
  targetUserId: number
  targetUsername?: string
  reporterUserId: number
  reporterUsername?: string
  reason: string
  detail?: string | null
  /** 0 待处理 1 已处理 2 已忽略 */
  status: number
  handleAction?: string | null
  handleNote?: string | null
  handledBy?: number | null
  handledTime?: string | null
  postTitle?: string
  contentPreview?: string
  createTime?: string
}

/** 运营活动 */
export interface OpsCampaignItem {
  id?: number
  title: string
  summary?: string
  linkUrl?: string
  coverUrl?: string
  /** 0 草稿 1 已发布 2 已结束 */
  status: number
  startTime?: string | null
  endTime?: string | null
  sortOrder?: number
  createTime?: string
  updateTime?: string
}

/** 兑换码（后台） */
export interface RedeemCodeItem {
  id?: number
  code: string
  batchLabel?: string
  rewardType: string
  rewardPoints: number
  rewardCurrency: number
  maxTotalRedemptions?: number | null
  redemptionCount?: number
  maxPerUser: number
  validStart?: string | null
  validEnd?: string | null
  enabled: number
  campaignId?: number | null
  createTime?: string
  updateTime?: string
}

export interface RedeemGeneratePayload {
  batchLabel?: string
  count: number
  codeLength: number
  prefix?: string
  rewardType: string
  rewardPoints: number
  rewardCurrency: number
  maxTotalRedemptions?: number | null
  maxPerUser: number
  validStart?: string | null
  validEnd?: string | null
  enabled: number
  campaignId?: number | null
}

/** 成长任务配置（后台） */
export interface TaskConfigItem {
  id?: number
  taskCode: string
  taskName: string
  taskType: string
  description?: string
  triggerAction?: string
  rewardType: string
  rewardAmount: number
  conditionValue?: number | null
  conditionOperator?: string
  maxDailyTimes?: number | null
  sortOrder: number
  enabled: number
  createTime?: string
  updateTime?: string
}

export interface AdminRoleItem {
  id?: number
  name: string
  code: string
  description?: string
  status: number
  menuPermissions: string[]
  userCount?: number
}

export interface AdminUserItem {
  id: number
  username: string
  email?: string
  phone?: string
  status: number
  isAdmin: number
  memberLevel: number
  /** user_profile.member_expire_time */
  memberExpireTime?: string | null
  /** user_profile 遗留，计费以创作点/星夜币为准 */
  points?: number
  /** user_balance.free_quota */
  freeQuota?: number
  /** user_balance.platform_currency */
  platformCurrency?: number
  createTime: string
  /** auth_user.register_ip */
  registerIp?: string | null
  /** auth_user.last_login_time */
  lastLoginTime?: string | null
  /** auth_user.last_login_ip */
  lastLoginIp?: string | null
}

/** 运营端用户详情（含资料与计费字段） */
export interface AdminUserDetail extends AdminUserItem {
  nickname?: string
  avatar?: string
  totalWordCount?: number
  freeQuotaDate?: string | null
  enableMixedPayment?: boolean
  /** 是否已登记姓名与证件号 */
  hasIdentityOnFile?: boolean
  /** 脱敏姓名 */
  realNameMasked?: string | null
  /** 脱敏证件号 */
  idCardMasked?: string | null
  /** 人脸/三方核验：0 未通过 1 已通过 */
  realNameVerified?: number | null
  realNameVerifyOuterNo?: string | null
  /** 当前易支付实名认证费关联单号 */
  realnameFeePaidRecordNo?: string | null
  realnameFeePayStatus?: string | null
  realnameFeePayAmount?: number | null
  realnameFeePayTime?: string | null
  oauthProviders?: string[]
  novelCount?: number
  totalFreeUsed?: number
  totalPaidUsed?: number
  totalRecharged?: number
}

/** 运营端新建前台用户 */
export interface AdminUserCreatePayload {
  username: string
  password: string
  email?: string
  phone?: string
}

export interface OpsAccountItem {
  id?: number
  username: string
  email?: string
  roleId: number
  roleName?: string
  status: number
  createTime?: string
}

export interface UserProfile {
  userId: number
  username: string
  nickname?: string
  email?: string
  phone?: string
  avatar?: string
  memberLevel: number
  points: number
  /** 与 auth_user.real_name_verified 一致：0 未通过，1 已通过 */
  realNameVerified?: number
  /** 已填实名且站点开启人脸/三方核验但未完成时为 true */
  realNameVerifyPending?: boolean
  /** 站点是否开启实名（auth.realname.enabled） */
  realNameGateEnabled?: boolean
  /** 是否已在账号上登记姓名与证件号 */
  hasRealNameOnFile?: boolean
  /** 实名关闭时为 basic（占位）；开启时为 alipay | ovooa（喵雨欣） */
  realNameVerifyProvider?: string
  /** 是否启用实名认证费（易支付现金） */
  realnameFeeEnabled?: boolean
  /** 认证费金额（元） */
  realnameFeeAmountYuan?: number | null
  /** 认证费已付且与当前配置金额一致时为 true */
  realnameFeeCashPaid?: boolean | null
}

export interface UserProfileUpdatePayload {
  nickname?: string
  email?: string
  phone?: string
  avatar?: string
  realName?: string
  idCardNo?: string
}

export interface AdminOrderItem {
  id: number
  orderNo: string
  userId: number
  username: string
  productName: string
  amount: number
  status: number
  payTime?: string
  createTime: string
}

export interface AdminDashboardStats {
  totalUsers: number
  totalNovels: number
  totalOrders: number
  totalAnnouncements: number
  pendingOrders: number
  activeUsers: number
  userGrowthTrend?: Array<{ month: string; count: number }>
  orderTrend?: Array<{ month: string; count: number }>
  novelCategoryDistribution?: Array<{ category: string; count: number }>
  revenueTrend?: Array<{ month: string; revenue: number }>
}

export interface AiModelItem {
  id?: number
  modelCode: string
  modelName: string
  /** 保留字段，固定 default，不再作业务用途 */
  modelType?: string
  provider?: string
  /** 关联计费配置「渠道管理」中的渠道 */
  billingChannelId?: number
  channelCode?: string
  channelName?: string
  enabled: number
  sortOrder?: number
}

export interface AiSensitiveWordItem {
  id?: number
  word: string
  level: number
  enabled: number
}

export interface OperationLogItem {
  id: number
  userId?: number
  username?: string
  operation: string
  module?: string
  method?: string
  requestUrl?: string
  requestMethod?: string
  requestParams?: string
  responseData?: string
  ipAddress?: string
  userAgent?: string
  status: number
  errorMessage?: string
  executionTime?: number
  createTime: string
}

export interface Novel {
  id: number
  userId: number
  title: string
  subtitle?: string
  cover?: string
  categoryId?: number
  genre?: string
  style?: string
  synopsis?: string
  wordCount: number
  chapterCount: number
  status: number
  auditStatus: number
  isPublished: number
  createTime: string
  updateTime: string
}

export interface NovelVolume {
  id: number
  novelId: number
  title: string
  description?: string
  volumeOrder: number
  chapterCount: number
  wordCount: number
  status: number
}

export interface NovelChapter {
  id: number
  novelId: number
  volumeId?: number
  title: string
  content?: string
  outline?: string
  chapterOrder: number
  wordCount: number
  status: number
  version: number
}

export interface NovelOutline {
  id: number
  novelId: number
  volumeId?: number
  chapterId?: number
  type: 'outline' | 'volume_outline' | 'chapter_outline'
  title: string
  content?: string
  sortOrder: number
  parentId?: number
  version: number
}

export interface NovelOutlineItem {
  id?: number
  novelId: number
  volumeId?: number
  chapterId?: number
  type: string
  title: string
  content?: string
  sortOrder?: number
  parentId?: number
  version?: number
}

export interface ChapterWorkshopIntent {
  novelId: number
  coreEvent: string
  sceneLocation?: string
  atmosphere?: string
  emotionalTone?: string
  generationMode?: 'conservative' | 'balanced' | 'creative'
  rewriteStrength?: 'conservative' | 'stylized'
  candidateCount?: number
  sourceContent?: string
  presentCharacterIds?: string[]
  relatedOutlineNodes?: string[]
}

export interface ChapterWorkshopResult {
  cPrompt: string
  recalledContext: string[]
  generatedDraft?: string
  generatedDrafts?: string[]
  generatedDraftLabels?: string[]
  consistencyReport: {
    passed: boolean
    issues: Array<{
      category: string
      severity: string
      message: string
      suggestion?: string
    }>
  }
}

export interface ChapterDraftGeneratePayload {
  volumeId: number
  chapterCount: number
  targetWordCount?: number
  chapterNo?: number
  chapterType?: string
}

export interface ChapterDraftItem {
  chapterNo: number
  title: string
  coreEvent: string
  sceneSetting: {
    location: string
    time: string
    atmosphere: string
  }
  charactersPresent: Array<{
    name: string
    chapterGoal: string
    status: string
  }>
  plotPoints: Array<{
    order: number
    type: string
    description: string
    emotionalChange: string
  }>
  keyDialogues: Array<{
    speaker: string
    content: string
    purpose: string
  }>
  foreshadowing: Array<{
    setup: string
    type: string
  }>
  connectionNote: string
  status: string
  version: number
}

export interface ChapterDraftConnectionCheckPayload {
  volumeId: number
  drafts: ChapterDraftItem[]
}

export interface ChapterDraftConnectionIssue {
  chapterNo?: number
  level: 'error' | 'warn' | 'info'
  message: string
  suggestion?: string
}

export interface ContentExpandRequest {
  chapterOutlineId: number
  expandRatio?: number
  styleSample?: string
  optimizeConnections?: boolean
  postProcessEnabled?: boolean
}

export interface ContentExpandResult {
  content: string
  wordCount: number
  styleFingerprint?: {
    avgSentenceLength?: number
    dialogueRatio?: number
    descriptionDensity?: number
    pacingType?: string
  }
  generationPlan: Array<{
    type: string
    sentenceCount: number
    strategy: string
  }>
  segments: Array<{
    type: string
    text: string
  }>
}

export interface ContentVersionItem {
  id: number
  version: number
  title: string
  sourceType?: 'draft' | 'expanded'
  wordCount: number
  createTime: string
  content: string
}

export interface ContinueWritingRequest {
  sourceContent: string
  expandRatio?: number
  styleSample?: string
  optimizeConnections?: boolean
  postProcessEnabled?: boolean
  /** 传入时后端会按作品隔离注入向量记忆 */
  novelId?: number
}

export interface ContinueWritingResult {
  content: string
  wordCount: number
  styleFingerprint?: {
    avgSentenceLength?: number
    dialogueRatio?: number
    descriptionDensity?: number
    pacingType?: string
  }
  generationPlan: Array<{
    type: string
    sentenceCount: number
    strategy: string
  }>
  segments: Array<{
    type: string
    text: string
  }>
}

export interface ConsistencyIssue {
  category: string
  severity: string
  message: string
  suggestion?: string
}

export interface ConsistencyReport {
  passed: boolean
  issues: ConsistencyIssue[]
}

export interface PlotSuggestionResult {
  suggestions: string[]
}

export interface TokusatsuWorldline {
  id: number
  novelId: number
  name: string
  source: string
  description?: string
  crossWorldRules?: {
    canImportCharacters: boolean
    canImportItems: boolean
    conflictDetection: boolean
  }
  fusionRules?: {
    allowedWorldlines: number[]
    conflictResolution: string
  }
  status: string
  createdAt: string
}

export interface TokusatsuForm {
  id: number
  characterId: number
  name: string
  parentFormId?: number
  childFormIds: number[]
  evolutionConditions?: {
    emotionalTrigger?: string
    deviceRequired?: string
    externalCharge?: boolean
    battleCondition?: string
  }
  degenerationConditions?: {
    energyDepletion: boolean
    transformationTimeout: boolean
    forcedByEnemy: boolean
  }
  abilityVector?: {
    power: number
    speed: number
    specialAbilities: string[]
    weaknesses: string[]
  }
  enemyWeaknesses?: Record<string, number>
  description?: string
  imageUrl?: string
}

export interface TokusatsuDevice {
  id: number
  name: string
  type: string
  description?: string
  status: string
  ownedBy?: number
  evolvedInto?: number
  evolutionCondition?: string
  imageUrl?: string
}

export interface TokusatsuEpisode {
  id: number
  novelId: number
  episodeNo: number
  title?: string
  monsterEvent?: {
    mainMonster: string
    minions?: string[]
    episodeThreat: string
  }
  victimEvent?: {
    type: string
    description: string
  }
  gains?: {
    newForm?: string
    newDevice?: string
    plotAdvance?: string
  }
  mainPlotConnection?: {
    foreshadowingId: number
    advanceAmount: number
  }
  battleLocation: string
  summary?: string
}

export interface TokusatsuVillain {
  id: number
  name: string
  category: string
  organization?: {
    id: string
    name: string
  }
  abilities?: {
    combatPower: number
    specialAttacks: string[]
    weaknesses: string[]
  }
  statusHistory: Array<{
    status: string
    deathChapter?: number
    revivalCondition?: string
  }>
  rivalries: Record<string, {
    type: string
    specificForm?: string
  }>
}

export interface TenantInfo {
  id: number
  name: string
  code: string
  type: string
  status: string
  logo?: string
  description?: string
  contactEmail?: string
  createdAt: string
  expiredAt?: string
}

export interface TenantQuotaInfo {
  tenantId: number
  plan: string
  novelsLimit: number
  chaptersLimit: number
  storageLimit: number
  apiCallsLimit: number
  teamMembersLimit: number
  currentNovels: number
  currentChapters: number
  currentStorage: number
  currentApiCalls: number
  currentTeamMembers: number
  resetAt: string
}

export interface CharacterStatusInfo {
  characterId: number
  chapterNo: number
  lifeStatus: string
  deathChapter?: number
  deathCause?: string
  health?: {
    value: number
    injuries: Array<{
      id: string
      type: string
      severity: string
      description: string
      healingChapter?: number
      isPersistent: boolean
    }>
    fatigue: number
    needsRecovery: boolean
  }
  emotional?: {
    value: number
    emotion: string
    volatility: number
    mentalState: string
  }
  ability?: {
    status: string
    description?: string
  }
  location?: {
    current: string
    previous?: string
  }
  updatedAt: string
}

export interface CharacterInteractionInfo {
  id: string
  chapterNo: number
  chapterTitle?: string
  interactionType: string
  description: string
  intimacyChange: number
  trustChange: number
  emotionTags: string[]
  createdAt: string
}

export interface RelationshipMetricsInfo {
  intimacy: number
  trust: number
  interactionFrequency: number
  lastInteractionChapter: number
  totalInteractions: number
}

export interface KeyRelationshipEventInfo {
  id: string
  chapterNo: number
  eventType: string
  description: string
  impactOnRelationship: string
  intimacyChange?: number
  trustChange?: number
}

export interface MaterialItemInfo {
  id?: number
  title: string
  type: string
  subtype?: string
  description?: string
  content?: string
  tags?: string[]
  usageCount?: number
  favoriteCount?: number
  viewCount?: number
  source?: string
  sourceUrl?: string
  author?: string
  createTime?: string
  updateTime?: string
}

export interface MaterialCategoryInfo {
  id: string
  name: string
  type: string
  icon?: string
  count: number
  children?: MaterialCategoryInfo[]
}

export interface KnowledgeGraphInfo {
  nodes: Array<{
    id: string
    label: string
    type: string
    properties: Record<string, any>
  }>
  edges: Array<{
    source: string
    target: string
    label: string
    weight?: number
  }>
}

export interface KnowledgeLinkInfo {
  id: number
  sourceId: number
  targetId: number
  linkType: string
  description?: string
  strength: number
  createTime: string
}

export interface KnowledgeShareInfo {
  id: number
  knowledgeId: number
  sharedWithUserId?: number
  sharedWithTeamId?: number
  permission: string
  expiresAt?: string
  createTime: string
}

export interface VectorCollectionInfo {
  id: number
  name: string
  description?: string
  vectorDimension: number
  recordCount: number
  metadata: Record<string, any>
  createdAt: string
  updatedAt: string
}

export interface VectorSnapshotInfo {
  id: string
  collectionId: number
  name: string
  size: number
  recordCount: number
  status: string
  createdAt: string
}

export interface ForeshadowingInfo {
  id: number
  novelId: number
  chapterId: number
  sourceChapter: number
  targetChapter?: number
  resolutionChapterId?: number
  foreshadowingType: string
  foreshadowingContent: string
  hintLevel?: number
  resolutionStatus: string
  detail?: string
  quote?: string
  status?: string
}

export interface RhythmAnalysisInfo {
  chapterNo: number
  anticipationScore?: number
  tensionScore?: number
  warmthScore?: number
  sadnessScore?: number
  pacingScore?: number
}

export interface BranchInfo {
  id: number
  novelId: number
  name: string
  description?: string
  baseVersionId?: string
  rootCommitId?: string
  headCommitId?: string
  parentBranchId?: number
  status: string
  createdAt: string
  mergedAt?: string
}

export interface CommitInfo {
  id: string
  branchId: number
  parentIds: string[]
  nodeType: string
  nodeId: string
  changeType: string
  contentBefore?: string
  contentAfter?: string
  message: string
  author: string
  aiConversationId?: string
  createdAt: string
}

export interface PromptTemplateInfo {
  id?: number
  title: string
  content: string
  category: string
  variables?: Array<{
    name: string
    description: string
    required: boolean
    defaultValue?: string
    type?: string
    options?: string[]
  }>
  description?: string
  tags?: string[]
  usageCount?: number
  favoriteCount?: number
  viewCount?: number
  isPublic?: boolean
  authorId?: number
  authorName?: string
  createTime?: string
  updateTime?: string
}
