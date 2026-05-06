import { del, get, post, put } from '@/utils/request'
import type { ResponseVO } from '@/types/api'

export interface Worldline {
  id: number
  name: string
  source: string
  description?: string
  crossWorldRules: {
    canImportCharacters: boolean
    canImportItems: boolean
    conflictDetection: boolean
  }
  fusionRules?: {
    allowedWorldlines: number[]
    conflictResolution: 'first' | 'merge' | 'reject'
  }
  status: 'active' | 'archived' | 'if_branch'
  createdAt: string
}

export interface SettingEntry {
  id: number
  novelId: number
  worldlineId?: number
  name: string
  type: string
  content: string
  applicableWorldlines: number[]
  isCrossWorldValid: boolean
  conflictWorldlines: number[]
  source?: string
  canonStatus?: 'main' | 'movie' | 'spinoff' | 'novel' | 'stage'
}

export interface Device {
  id: number
  name: string
  type: 'belt' | 'buckel' | 'eyecon' | 'bottle' | 'core_idol' | string
  description?: string
  status: 'owned' | 'destroyed' | 'evolved' | 'lost'
  ownedBy?: number
  evolvedInto?: number
  evolutionCondition?: string
  imageUrl?: string
}

export interface Form {
  id: number
  name: string
  characterId: number
  parentFormId?: number
  childFormIds: number[]
  evolutionConditions: {
    emotionalTrigger?: string
    deviceRequired?: string
    externalCharge?: boolean
    battleCondition?: string
  }
  degenerationConditions: {
    energyDepletion: boolean
    transformationTimeout: boolean
    forcedByEnemy: boolean
  }
  abilityVector: {
    power: number
    speed: number
    specialAbilities: string[]
    weaknesses: string[]
  }
  enemyWeaknesses: Record<string, number>
  imageUrl?: string
  description?: string
}

export interface TokusatsuCharacter {
  id: number
  novelId: number
  baseCharacterId: number
  name: string
  formTree: Form[]
  ownedDevices: Device[]
  languageFingerprint: {
    transformationAnnounce: string[]
    catchphrases: string[]
    finisherAnnounce: string[]
  }
}

export interface EpisodeCard {
  id: number
  novelId: number
  episodeNo: number
  title?: string
  monsterEvent: {
    mainMonster: string
    minions?: string[]
    episodeThreat: 'low' | 'medium' | 'high'
  }
  victimEvent: {
    type: 'civilian' | 'ally' | 'self'
    description: string
  }
  gains: {
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
  content?: string
}

export interface VillainTemplate {
  id: number
  name: string
  category: 'monster' | 'rider' | 'kaijin' | 'ultraman' | 'boss'
  organization?: {
    id: string
    name: string
  }
  abilities: {
    combatPower: number
    specialAttacks: string[]
    weaknesses: string[]
    bodyParts?: string[]
  }
  statusHistory: Array<{
    status: 'alive' | 'dead' | 'revived' | 'cloned'
    deathChapter?: number
    revivalCondition?: string
  }>
  rivalries: Record<string, {
    type: 'weak_to' | 'strong_to' | 'equal'
    specificForm?: string
  }>
}

export interface TransformationValidationResult {
  valid: boolean
  error?: string
  type?: 'missing_device' | 'insufficient_energy' | 'emotional_mismatch'
  suggestion?: string
}

export interface ForeshadowingReminder {
  id: number
  description: string
  setupChapter: number
  currentChapter: number
  chaptersSinceSetup: number
  priority: 'high' | 'medium' | 'low'
  suggestion: string
}

export function listWorldlines(novelId: number) {
  return get<Worldline[]>(`/tokusatsu/${novelId}/worldlines`)
}

export function createWorldline(novelId: number, data: Omit<Worldline, 'id' | 'createdAt'>) {
  return post<Worldline>(`/tokusatsu/${novelId}/worldlines`, data)
}

export function updateWorldline(id: number, data: Partial<Worldline>) {
  return put<Worldline>(`/tokusatsu/worldlines/${id}`, data)
}

export function deleteWorldline(id: number) {
  return del<void>(`/tokusatsu/worldlines/${id}`)
}

export function getSettingEntries(novelId: number, worldlineId?: number) {
  return get<SettingEntry[]>(`/tokusatsu/${novelId}/settings`, {
    params: { worldlineId }
  })
}

export function importOfficialSettings(novelId: number, data: {
  source: string
  format: 'json' | 'markdown' | 'csv'
  content: string
}) {
  return post<SettingEntry[]>(`/tokusatsu/${novelId}/settings/import`, data)
}

export function createSettingEntry(novelId: number, data: Omit<SettingEntry, 'id'>) {
  return post<SettingEntry>(`/tokusatsu/${novelId}/settings`, data)
}

export function updateSettingEntry(id: number, data: Partial<SettingEntry>) {
  return put<SettingEntry>(`/tokusatsu/settings/${id}`, data)
}

export function getCharacterForms(characterId: number) {
  return get<Form[]>(`/tokusatsu/characters/${characterId}/forms`)
}

export function createForm(characterId: number, data: Omit<Form, 'id'>) {
  return post<Form>(`/tokusatsu/characters/${characterId}/forms`, data)
}

export function updateForm(id: number, data: Partial<Form>) {
  return put<Form>(`/tokusatsu/forms/${id}`, data)
}

export function deleteForm(id: number) {
  return del<void>(`/tokusatsu/forms/${id}`)
}

export function getOwnedDevices(characterId: number) {
  return get<Device[]>(`/tokusatsu/characters/${characterId}/devices`)
}

export function createDevice(characterId: number, data: Omit<Device, 'id'>) {
  return post<Device>(`/tokusatsu/characters/${characterId}/devices`, data)
}

export function updateDevice(id: number, data: Partial<Device>) {
  return put<Device>(`/tokusatsu/devices/${id}`, data)
}

export function validateTransformation(
  characterId: number,
  targetFormId: number,
  context: {
    currentEnergy?: number
    currentEmotion?: string
    currentEnemy?: string
  }
) {
  return post<TransformationValidationResult>(`/tokusatsu/characters/${characterId}/validate-transformation`, {
    targetFormId,
    ...context
  })
}

export function getEpisodeCards(novelId: number) {
  return get<EpisodeCard[]>(`/tokusatsu/${novelId}/episodes`)
}

export function createEpisodeCard(novelId: number, data: Omit<EpisodeCard, 'id'>) {
  return post<EpisodeCard>(`/tokusatsu/${novelId}/episodes`, data)
}

export function updateEpisodeCard(id: number, data: Partial<EpisodeCard>) {
  return put<EpisodeCard>(`/tokusatsu/episodes/${id}`, data)
}

export function deleteEpisodeCard(id: number) {
  return del<void>(`/tokusatsu/episodes/${id}`)
}

export function getVillainTemplates(novelId: number) {
  return get<VillainTemplate[]>(`/tokusatsu/${novelId}/villains`)
}

export function createVillainTemplate(novelId: number, data: Omit<VillainTemplate, 'id'>) {
  return post<VillainTemplate>(`/tokusatsu/${novelId}/villains`, data)
}

export function updateVillainTemplate(id: number, data: Partial<VillainTemplate>) {
  return put<VillainTemplate>(`/tokusatsu/villains/${id}`, data)
}

export function deleteVillainTemplate(id: number) {
  return del<void>(`/tokusatsu/villains/${id}`)
}

export function getForeshadowingReminders(novelId: number, currentChapter: number) {
  return get<ForeshadowingReminder[]>(`/tokusatsu/${novelId}/foreshadowing-reminders`, {
    params: { currentChapter }
  })
}

export function checkConsistencyForTokusatsu(
  novelId: number,
  chapterId: number,
  content: string
) {
  return post<ResponseVO<{
    issues: Array<{
      type: string
      severity: string
      description: string
      location?: string
      suggestion?: string
    }>
    transformationIssues: TransformationValidationResult[]
    missingAnnouncements: string[]
    villainStatusIssues: string[]
  }>>(`/tokusatsu/${novelId}/consistency-check`, {
    chapterId,
    content
  })
}

export function generateTokusatsuChapterContext(
  characterId: number,
  targetFormId: number,
  currentEnemy?: string
) {
  return get<ResponseVO<{
    availableForms: Form[]
    formLimits: string[]
    enemyWeaknesses: string[]
    transformationTips: string[]
  }>>(`/tokusatsu/characters/${characterId}/chapter-context`, {
    params: { targetFormId, currentEnemy }
  })
}
