import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type {
  NovelCharacter,
  CharacterRelationship,
  CharacterInteraction,
  CharacterStatus,
  RelationshipMetrics,
  KeyRelationshipEvent
} from '@/api/character'
import {
  listCharacters,
  getCharacter,
  createCharacter,
  updateCharacter,
  deleteCharacter,
  getCharacterInteractions,
  recordCharacterInteraction,
  getCharacterStatus,
  updateCharacterStatus,
  getCharacterRelationshipMetrics,
  getKeyRelationshipEvents,
  analyzeRelationshipEvolution,
  getCharacterRelationshipContext
} from '@/api/character'
import type { PageVO, ResponseVO } from '@/types/api'

export const useCharacterStore = defineStore('character', () => {
  const characterList = ref<NovelCharacter[]>([])
  const currentCharacter = ref<NovelCharacter | null>(null)
  const loading = ref(false)
  const totalCount = ref(0)
  const currentPage = ref(1)
  const pageSize = ref(10)
  const searchKeyword = ref('')
  const selectedNovelId = ref<number | null>(null)

  const characterInteractions = ref<CharacterInteraction[]>([])
  const characterStatus = ref<CharacterStatus | null>(null)
  const relationshipMetrics = ref<Record<number, RelationshipMetrics>>({})
  const relationshipEvents = ref<KeyRelationshipEvent[]>([])

  const hasCurrentCharacter = computed(() => currentCharacter.value !== null)
  const filteredList = computed(() => {
    if (!searchKeyword.value) return characterList.value
    const keyword = searchKeyword.value.toLowerCase()
    return characterList.value.filter(c =>
      c.name?.toLowerCase().includes(keyword) ||
      c.identity?.toLowerCase().includes(keyword) ||
      c.background?.toLowerCase().includes(keyword)
    )
  })

  const aliveCharacters = computed(() =>
    characterList.value.filter(c => {
      const status = characterStatus.value
      return status?.characterId === c.id ? status.lifeStatus === 'alive' : true
    })
  )

  const deadCharacters = computed(() =>
    characterList.value.filter(c => {
      const status = characterStatus.value
      return status?.characterId === c.id ? status.lifeStatus === 'dead' : false
    })
  )

  async function fetchCharacterList(params?: {
    page?: number
    size?: number
    novelId?: number
    keyword?: string
  }) {
    loading.value = true
    try {
      const res = await listCharacters({
        page: params?.page || currentPage.value,
        size: params?.size || pageSize.value,
        novelId: params?.novelId || selectedNovelId.value || undefined,
        keyword: params?.keyword
      })
      if (res.data) {
        characterList.value = res.data.records || []
        totalCount.value = res.data.total || 0
      }
    } finally {
      loading.value = false
    }
  }

  async function fetchCharacter(id: number) {
    loading.value = true
    try {
      const res = await getCharacter(id)
      if (res.data) {
        currentCharacter.value = res.data
      }
      return res.data
    } finally {
      loading.value = false
    }
  }

  async function createNewCharacter(data: Partial<NovelCharacter>) {
    const res = await createCharacter(data)
    if (res.data) {
      characterList.value.unshift(res.data)
      totalCount.value++
    }
    return res.data
  }

  async function updateCurrentCharacter(id: number, data: Partial<NovelCharacter>) {
    const res = await updateCharacter(id, data)
    if (res.data) {
      const index = characterList.value.findIndex(c => c.id === id)
      if (index !== -1) {
        characterList.value[index] = res.data
      }
      if (currentCharacter.value?.id === id) {
        currentCharacter.value = res.data
      }
    }
    return res.data
  }

  async function removeCharacter(id: number) {
    await deleteCharacter(id)
    characterList.value = characterList.value.filter(c => c.id !== id)
    totalCount.value--
    if (currentCharacter.value?.id === id) {
      currentCharacter.value = null
    }
  }

  async function fetchCharacterInteractions(characterId: number, novelId: number) {
    try {
      const res = await getCharacterInteractions(characterId, novelId)
      if (res.data?.data) {
        characterInteractions.value = res.data.data
      }
    } catch (e) {
      console.error('Failed to fetch interactions', e)
    }
  }

  async function recordInteraction(
    characterId: number,
    data: Omit<CharacterInteraction, 'id' | 'createdAt'>
  ) {
    const res = await recordCharacterInteraction(characterId, data)
    if (res.data?.data) {
      characterInteractions.value.unshift(res.data.data)
    }
    return res.data
  }

  async function fetchCharacterStatus(characterId: number, novelId: number) {
    try {
      const res = await getCharacterStatus(characterId, novelId)
      if (res.data?.data) {
        characterStatus.value = res.data.data
      }
    } catch (e) {
      console.error('Failed to fetch character status', e)
    }
  }

  async function updateCharacterCurrentStatus(characterId: number, data: Partial<CharacterStatus>) {
    const res = await updateCharacterStatus(characterId, data)
    if (res.data?.data) {
      characterStatus.value = res.data.data
    }
    return res.data
  }

  async function fetchRelationshipMetrics(characterId: number) {
    try {
      const res = await getCharacterRelationshipMetrics(characterId)
      if (res.data?.data) {
        relationshipMetrics.value[characterId] = res.data.data
      }
    } catch (e) {
      console.error('Failed to fetch relationship metrics', e)
    }
  }

  async function fetchRelationshipEvents(novelId: number) {
    try {
      const res = await getKeyRelationshipEvents(novelId)
      if (res.data?.data) {
        relationshipEvents.value = res.data.data
      }
    } catch (e) {
      console.error('Failed to fetch relationship events', e)
    }
  }

  async function fetchRelationshipEvolution(
    novelId: number,
    characterAId: number,
    characterBId: number
  ) {
    try {
      const res = await analyzeRelationshipEvolution(novelId, characterAId, characterBId)
      return res.data?.data
    } catch (e) {
      console.error('Failed to fetch relationship evolution', e)
    }
  }

  async function fetchRelationshipContext(
    characterId: number,
    targetCharacterId: number,
    chapterNo: number
  ) {
    try {
      const res = await getCharacterRelationshipContext(characterId, targetCharacterId, chapterNo)
      return res.data?.data
    } catch (e) {
      console.error('Failed to fetch relationship context', e)
    }
  }

  function getRelationshipTrend(characterId: number): 'improving' | 'declining' | 'stable' | null {
    const metrics = relationshipMetrics.value[characterId]
    if (!metrics) return null
    if (metrics.intimacy > 70) return 'improving'
    if (metrics.intimacy < 30) return 'declining'
    return 'stable'
  }

  function setSearchKeyword(keyword: string) {
    searchKeyword.value = keyword
  }

  function setSelectedNovel(novelId: number | null) {
    selectedNovelId.value = novelId
  }

  function clearCurrentCharacter() {
    currentCharacter.value = null
    characterInteractions.value = []
    characterStatus.value = null
  }

  function clearAll() {
    characterList.value = []
    currentCharacter.value = null
    totalCount.value = 0
    currentPage.value = 1
    searchKeyword.value = ''
    selectedNovelId.value = null
    characterInteractions.value = []
    characterStatus.value = null
    relationshipMetrics.value = {}
    relationshipEvents.value = []
  }

  return {
    characterList,
    currentCharacter,
    loading,
    totalCount,
    currentPage,
    pageSize,
    searchKeyword,
    selectedNovelId,
    hasCurrentCharacter,
    filteredList,
    aliveCharacters,
    deadCharacters,
    characterInteractions,
    characterStatus,
    relationshipMetrics,
    relationshipEvents,
    fetchCharacterList,
    fetchCharacter,
    createNewCharacter,
    updateCurrentCharacter,
    removeCharacter,
    fetchCharacterInteractions,
    recordInteraction,
    fetchCharacterStatus,
    updateCharacterCurrentStatus,
    fetchRelationshipMetrics,
    fetchRelationshipEvents,
    fetchRelationshipEvolution,
    fetchRelationshipContext,
    getRelationshipTrend,
    setSearchKeyword,
    setSelectedNovel,
    clearCurrentCharacter,
    clearAll
  }
})
