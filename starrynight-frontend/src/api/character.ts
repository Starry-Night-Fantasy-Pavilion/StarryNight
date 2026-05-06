import { del, get, post, put } from '@/utils/request'
import type { PageVO, ResponseVO } from '@/types/api'

export interface CharacterRelationship {
  id?: number
  targetId: number
  targetName?: string
  type: string
  description?: string
  intimacy?: number
  trust?: number
  interactionFrequency?: number
  lastInteractionChapter?: number
  totalInteractions?: number
}

export interface RelationshipMetrics {
  intimacy: number
  trust: number
  interactionFrequency: number
  lastInteractionChapter: number
  totalInteractions: number
}

export interface CharacterInteraction {
  id: string
  chapterNo: number
  chapterTitle?: string
  interactionType: 'first_meeting' | 'battle' | 'conversation' | 'cooperation' | 'conflict' | 'emotional_support'
  description: string
  intimacyChange: number
  trustChange: number
  emotionTags: string[]
  createdAt: string
}

export interface KeyRelationshipEvent {
  id: string
  chapterNo: number
  eventType: 'first_meet' | 'oath' | 'betrayal' | 'reconciliation' | 'death' | 'rescue'
  description: string
  impactOnRelationship: 'positive' | 'negative' | 'neutral'
  intimacyChange?: number
  trustChange?: number
}

export interface CharacterStatus {
  characterId: number
  chapterNo: number
  lifeStatus: 'alive' | 'dead' | 'unknown'
  deathChapter?: number
  deathCause?: string
  health: {
    value: number
    injuries: Array<{
      id: string
      type: 'physical' | 'mental' | 'spiritual'
      severity: 'minor' | 'major' | 'critical'
      description: string
      healingChapter?: number
      isPersistent: boolean
    }>
    fatigue: number
    needsRecovery: boolean
  }
  emotional: {
    value: number
    emotion: string
    volatility: number
    mentalState: 'stable' | 'unstable' | 'breaking'
  }
  ability: {
    status: 'normal' | 'weakened' | 'sealed' | 'evolved'
    description?: string
  }
  location: {
    current: string
    previous?: string
  }
  statusHistory: Array<{
    chapterNo: number
    changeType: string
    description: string
    timestamp: string
  }>
  updatedAt: string
}

export interface CharacterPersonality {
  traits: string[]
}

export interface CharacterAbilities {
  level: string
  skills: string[]
}

export interface NovelCharacter {
  id?: number
  novelId: number
  name: string
  identity?: string
  gender?: string
  age?: string
  appearance?: string
  background?: string
  motivation?: string
  personality?: CharacterPersonality
  abilities?: CharacterAbilities
  relationships?: CharacterRelationship[]
  growthArc?: object
  createTime?: string
  updateTime?: string
}

export interface CharacterCreateDTO {
  novelId: number
  name: string
  identity?: string
  gender?: string
  age?: string
  appearance?: string
  background?: string
  motivation?: string
  personality?: CharacterPersonality
  abilities?: CharacterAbilities
  relationships?: CharacterRelationship[]
}

export interface CharacterUpdateDTO {
  name?: string
  identity?: string
  gender?: string
  age?: string
  appearance?: string
  background?: string
  motivation?: string
  personality?: CharacterPersonality
  abilities?: CharacterAbilities
  relationships?: CharacterRelationship[]
}

export interface CharacterGraph {
  nodes: Array<{
    id: number
    name: string
    identity: string
    gender: string
  }>
  edges: Array<{
    source: number
    target: number
    type: string
  }>
}

export function listCharacters(params: {
  keyword?: string
  novelId?: number
  page?: number
  size?: number
}) {
  return get<ResponseVO<PageVO<NovelCharacter>>>('/characters/list', { params })
}

export function getCharacter(id: number) {
  return get<ResponseVO<NovelCharacter>>(`/characters/${id}`)
}

export function createCharacter(data: CharacterCreateDTO) {
  return post<ResponseVO<NovelCharacter>>('/characters', data)
}

export function updateCharacter(id: number, data: CharacterUpdateDTO) {
  return put<ResponseVO<NovelCharacter>>(`/characters/${id}`, data)
}

export function deleteCharacter(id: number) {
  return del<ResponseVO<void>>(`/characters/${id}`)
}

export function getCharacterGraph(novelId: number) {
  return get<ResponseVO<CharacterGraph>>('/characters/graph', { params: { novelId } })
}

export function exportCharacters(novelId: number) {
  return get<ResponseVO<NovelCharacter[]>>('/characters/export', { params: { novelId } })
}

export function importCharacters(novelId: number, characters: CharacterCreateDTO[]) {
  return post<ResponseVO<void>>('/characters/import', { novelId, characters })
}

export interface CharacterAIGenerateRequest {
  prompt: string
  count?: number
  novelId: number
  knowledgeIds?: number[]
}

export interface CharacterAIGenerateResult {
  name: string
  gender?: string
  age?: string
  identity?: string
  appearance?: string
  background?: string
  motivation?: string
  personality?: {
    traits: string[]
  }
  abilities?: {
    level: string
    skills: string[]
  }
}

export function generateCharacterByAI(data: CharacterAIGenerateRequest): Promise<{ data: CharacterAIGenerateResult[] }> {
  return post('/characters/ai-generate', data)
}

export interface ConsistencyCheckRequest {
  novelId: number
  chapterStart?: number
  chapterEnd?: number
  content?: string
}

export interface ConsistencyIssue {
  character: string
  type: string
  description: string
  evidence: string
  suggestion: string
  severity: 'error' | 'warning'
}

export function checkCharacterConsistency(data: ConsistencyCheckRequest): Promise<{ data: ConsistencyIssue[] }> {
  return post('/characters/consistency-check', data)
}

export function createCharacterRelationship(
  characterId: number,
  data: CharacterRelationship
): Promise<ResponseVO<CharacterRelationship>> {
  return post(`/characters/${characterId}/relationships`, data)
}

export function updateCharacterRelationship(
  characterId: number,
  relationshipId: number,
  data: CharacterRelationship
): Promise<ResponseVO<CharacterRelationship>> {
  return put(`/characters/${characterId}/relationships/${relationshipId}`, data)
}

export function deleteCharacterRelationship(
  characterId: number,
  relationshipId: number
): Promise<ResponseVO<void>> {
  return del(`/characters/${characterId}/relationships/${relationshipId}`)
}

export function getCharacterInteractions(characterId: number, novelId: number): Promise<ResponseVO<CharacterInteraction[]>> {
  return get(`/characters/${characterId}/interactions`, { params: { novelId } })
}

export function recordCharacterInteraction(
  characterId: number,
  data: Omit<CharacterInteraction, 'id' | 'createdAt'>
): Promise<ResponseVO<CharacterInteraction>> {
  return post(`/characters/${characterId}/interactions`, data)
}

export function getCharacterRelationshipMetrics(characterId: number): Promise<ResponseVO<RelationshipMetrics>> {
  return get(`/characters/${characterId}/relationship-metrics`)
}

export function updateCharacterRelationshipMetrics(
  characterId: number,
  relationshipId: number,
  data: Partial<RelationshipMetrics>
): Promise<ResponseVO<RelationshipMetrics>> {
  return put(`/characters/${characterId}/relationships/${relationshipId}/metrics`, data)
}

export function getCharacterStatus(characterId: number, novelId: number): Promise<ResponseVO<CharacterStatus>> {
  return get(`/characters/${characterId}/status`, { params: { novelId } })
}

export function updateCharacterStatus(
  characterId: number,
  data: Partial<CharacterStatus>
): Promise<ResponseVO<CharacterStatus>> {
  return post(`/characters/${characterId}/status`, data)
}

export function getCharacterStatusHistory(
  characterId: number,
  novelId: number
): Promise<ResponseVO<CharacterStatus['statusHistory']>> {
  return get(`/characters/${characterId}/status/history`, { params: { novelId } })
}

export function getKeyRelationshipEvents(
  novelId: number
): Promise<ResponseVO<KeyRelationshipEvent[]>> {
  return get(`/characters/relationship-events`, { params: { novelId } })
}

export function analyzeRelationshipEvolution(
  novelId: number,
  characterAId: number,
  characterBId: number
): Promise<ResponseVO<{
  events: KeyRelationshipEvent[]
  currentMetrics: RelationshipMetrics
  trend: 'improving' | 'declining' | 'stable'
}>> {
  return get(`/characters/relationship-evolution`, {
    params: { novelId, characterAId, characterBId }
  })
}

export interface UniverseSetting {
  id: number
  novelId: number
  name: string
  type: 'world' | 'power' | 'organization' | 'race' | 'item' | 'location'
  description: string
  rules: string[]
  compatibleWith: number[]
  conflictsWith: number[]
  source?: string
  isCrossUniverse: boolean
}

export interface UniverseConsistencyResult {
  valid: boolean
  issues: Array<{
    type: string
    severity: 'high' | 'medium' | 'low'
    description: string
    location?: string
    suggestion?: string
  }>
  warnings: string[]
}

export function getUniverseSettings(novelId: number) {
  return get<ResponseVO<UniverseSetting[]>>(`/characters/universe-settings`, { params: { novelId } })
}

export function createUniverseSetting(novelId: number, data: Omit<UniverseSetting, 'id'>) {
  return post<ResponseVO<UniverseSetting>>(`/characters/universe-settings`, data)
}

export function updateUniverseSetting(id: number, data: Partial<UniverseSetting>) {
  return put<ResponseVO<UniverseSetting>>(`/characters/universe-settings/${id}`, data)
}

export function deleteUniverseSetting(id: number) {
  return del<ResponseVO<void>>(`/characters/universe-settings/${id}`)
}

export function validateUniverseConsistency(
  novelId: number,
  content: string,
  chapterId?: number
): Promise<ResponseVO<UniverseConsistencyResult>> {
  return post(`/characters/universe-consistency`, { novelId, content, chapterId })
}

export function checkCrossUniverseRules(
  novelId: number,
  characterId: number,
  targetWorldlineId?: number
): Promise<ResponseVO<{
  canImport: boolean
  conflicts: Array<{
    settingId: number
    settingName: string
    conflictReason: string
  }>
  suggestions: string[]
}>> {
  return get(`/characters/${characterId}/cross-universe-check`, {
    params: { novelId, targetWorldlineId }
  })
}

export interface DialogueContext {
  id: string
  characterId: number
  chapterNo: number
  situation: {
    location: string
    timeOfDay: 'dawn' | 'morning' | 'noon' | 'afternoon' | 'evening' | 'night'
    weather: string
    emotionalTone: 'positive' | 'neutral' | 'negative' | 'tense'
    urgencyLevel: 'low' | 'medium' | 'high' | 'critical'
  }
  participants: Array<{
    characterId: number
    role: 'speaker' | 'listener' | 'bystander'
    currentEmotion: string
    relationshipToMain: 'friendly' | 'neutral' | 'hostile'
  }>
  topic?: string
  conversationGoal?: string
  relevantBackground: string[]
  suggestedResponses?: string[]
}

export interface DialogueSuggestion {
  speakerId: number
  suggestedContent: string
  emotion: string
  reasoning: string
  relationshipFactors: Array<{
    factor: string
    impact: 'positive' | 'negative' | 'neutral'
  }>
  contextMatches: string[]
}

export function getDialogueContext(
  characterId: number,
  chapterNo: number
): Promise<ResponseVO<DialogueContext>> {
  return get(`/characters/${characterId}/dialogue-context`, {
    params: { chapterNo }
  })
}

export function getDialogueSuggestions(
  characterId: number,
  context: DialogueContext
): Promise<ResponseVO<DialogueSuggestion[]>> {
  return post(`/characters/${characterId}/dialogue-suggestions`, context)
}

export function validateDialogueConsistency(
  novelId: number,
  chapterId: number,
  dialogues: Array<{
    speakerId: number
    content: string
  }>
): Promise<ResponseVO<{
  valid: boolean
  issues: Array<{
    speakerId: number
    type: string
    description: string
    suggestion: string
  }>
}>> {
  return post(`/characters/dialogue-consistency`, {
    novelId,
    chapterId,
    dialogues
  })
}

export function getCharacterRelationshipContext(
  characterId: number,
  targetCharacterId: number,
  chapterNo: number
): Promise<ResponseVO<{
  currentMetrics: RelationshipMetrics
  recentInteractions: CharacterInteraction[]
  emotionalState: CharacterStatus['emotional']
  suggestedTopics: string[]
  taboos: string[]
}>> {
  return get(`/characters/${characterId}/relationship-context`, {
    params: { targetCharacterId, chapterNo }
  })
}
