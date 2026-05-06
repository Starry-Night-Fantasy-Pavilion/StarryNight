import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type {
  KnowledgeItem,
  KnowledgeChunk,
  KnowledgeGraph,
  KnowledgeLink,
  KnowledgeTag,
  HybridSearchResult
} from '@/api/knowledge'
import {
  listKnowledge,
  getKnowledge,
  createKnowledge,
  updateKnowledge,
  deleteKnowledge,
  listChunks,
  searchChunks,
  searchAllChunks,
  searchKnowledge,
  hybridSearchKnowledge,
  semanticSearchKnowledge,
  getKnowledgeGraph,
  getRelatedKnowledge,
  getKnowledgeLinks,
  createKnowledgeLink,
  deleteKnowledgeLink,
  getKnowledgeTags,
  addKnowledgeTags,
  removeKnowledgeTag,
  getKnowledgeStats
} from '@/api/knowledge'
import type { PageVO, ResponseVO } from '@/types/api'

export const useKnowledgeStore = defineStore('knowledge', () => {
  const knowledgeList = ref<KnowledgeItem[]>([])
  const currentKnowledge = ref<KnowledgeItem | null>(null)
  const chunks = ref<KnowledgeChunk[]>([])
  const loading = ref(false)
  const totalCount = ref(0)
  const currentPage = ref(1)
  const pageSize = ref(10)
  const searchKeyword = ref('')
  const filterType = ref<string | null>(null)

  const knowledgeGraph = ref<KnowledgeGraph | null>(null)
  const knowledgeLinks = ref<KnowledgeLink[]>([])
  const knowledgeTags = ref<KnowledgeTag[]>([])
  const relatedKnowledge = ref<KnowledgeItem[]>([])
  const searchResults = ref<HybridSearchResult[]>([])
  const knowledgeStats = ref<{
    viewCount: number
    searchCount: number
    引用Count: number
    lastAccessedAt: string
  } | null>(null)

  const hasCurrentKnowledge = computed(() => currentKnowledge.value !== null)
  const filteredList = computed(() => {
    let result = knowledgeList.value
    if (searchKeyword.value) {
      const keyword = searchKeyword.value.toLowerCase()
      result = result.filter(k =>
        k.title?.toLowerCase().includes(keyword) ||
        k.description?.toLowerCase().includes(keyword)
      )
    }
    if (filterType.value) {
      result = result.filter(k => k.type === filterType.value)
    }
    return result
  })

  const totalChunks = computed(() =>
    knowledgeList.value.reduce((sum, k) => sum + (k.chunkCount || 0), 0)
  )

  async function fetchKnowledgeList(params?: {
    page?: number
    size?: number
    keyword?: string
    type?: string
  }) {
    loading.value = true
    try {
      const res = await listKnowledge({
        page: params?.page || currentPage.value,
        size: params?.size || pageSize.value,
        keyword: params?.keyword,
        type: params?.type
      })
      if (res.data) {
        knowledgeList.value = res.data.records || []
        totalCount.value = res.data.total || 0
      }
    } finally {
      loading.value = false
    }
  }

  async function fetchKnowledge(id: number) {
    loading.value = true
    try {
      const res = await getKnowledge(id)
      if (res.data) {
        currentKnowledge.value = res.data
      }
      return res.data
    } finally {
      loading.value = false
    }
  }

  async function createNewKnowledge(data: Partial<KnowledgeItem>) {
    const res = await createKnowledge(data)
    if (res.data) {
      knowledgeList.value.unshift(res.data)
      totalCount.value++
    }
    return res.data
  }

  async function updateCurrentKnowledge(id: number, data: Partial<KnowledgeItem>) {
    const res = await updateKnowledge(id, data)
    if (res.data) {
      const index = knowledgeList.value.findIndex(k => k.id === id)
      if (index !== -1) {
        knowledgeList.value[index] = res.data
      }
      if (currentKnowledge.value?.id === id) {
        currentKnowledge.value = res.data
      }
    }
    return res.data
  }

  async function removeKnowledge(id: number) {
    await deleteKnowledge(id)
    knowledgeList.value = knowledgeList.value.filter(k => k.id !== id)
    totalCount.value--
    if (currentKnowledge.value?.id === id) {
      currentKnowledge.value = null
    }
  }

  async function fetchChunks(knowledgeId: number, params?: { page?: number; size?: number }) {
    try {
      const res = await listChunks(knowledgeId, params)
      if (res.data?.data) {
        chunks.value = res.data.data.records || []
      }
    } catch (e) {
      console.error('Failed to fetch chunks', e)
    }
  }

  async function searchKnowledgeChunks(knowledgeId: number, keyword: string) {
    try {
      const res = await searchChunks(knowledgeId, keyword)
      if (res.data?.data) {
        return res.data.data.records || []
      }
      return []
    } catch (e) {
      console.error('Failed to search chunks', e)
      return []
    }
  }

  async function performHybridSearch(params: {
    query: string
    knowledgeIds?: number[]
    topK?: number
    rerank?: boolean
  }) {
    try {
      const res = await hybridSearchKnowledge(params)
      if (res.data?.data) {
        searchResults.value = res.data.data
      }
      return res.data?.data || []
    } catch (e) {
      console.error('Failed to perform hybrid search', e)
      return []
    }
  }

  async function performSemanticSearch(query: string, knowledgeId?: number) {
    try {
      const res = await semanticSearchKnowledge(query, knowledgeId)
      if (res.data?.data) {
        return res.data.data
      }
      return []
    } catch (e) {
      console.error('Failed to perform semantic search', e)
      return []
    }
  }

  async function fetchKnowledgeGraph(knowledgeId: number) {
    try {
      const res = await getKnowledgeGraph(knowledgeId)
      if (res.data?.data) {
        knowledgeGraph.value = res.data.data
      }
    } catch (e) {
      console.error('Failed to fetch knowledge graph', e)
    }
  }

  async function fetchRelatedKnowledge(knowledgeId: number, limit?: number) {
    try {
      const res = await getRelatedKnowledge(knowledgeId, limit)
      if (res.data?.data) {
        relatedKnowledge.value = res.data.data
      }
    } catch (e) {
      console.error('Failed to fetch related knowledge', e)
    }
  }

  async function fetchKnowledgeLinks(knowledgeId: number) {
    try {
      const res = await getKnowledgeLinks(knowledgeId)
      if (res.data?.data) {
        knowledgeLinks.value = res.data.data
      }
    } catch (e) {
      console.error('Failed to fetch knowledge links', e)
    }
  }

  async function createLink(sourceId: number, targetId: number, data: { linkType: string; description?: string; strength?: number }) {
    try {
      const res = await createKnowledgeLink(sourceId, targetId, data)
      if (res.data?.data) {
        knowledgeLinks.value.push(res.data.data)
      }
      return res.data
    } catch (e) {
      console.error('Failed to create knowledge link', e)
    }
  }

  async function removeLink(linkId: number) {
    try {
      await deleteKnowledgeLink(linkId)
      knowledgeLinks.value = knowledgeLinks.value.filter(l => l.id !== linkId)
    } catch (e) {
      console.error('Failed to remove knowledge link', e)
    }
  }

  async function fetchKnowledgeTags(knowledgeId?: number) {
    try {
      const res = await getKnowledgeTags(knowledgeId)
      if (res.data?.data) {
        knowledgeTags.value = res.data.data
      }
    } catch (e) {
      console.error('Failed to fetch knowledge tags', e)
    }
  }

  async function addTags(knowledgeId: number, tags: string[]) {
    try {
      await addKnowledgeTags(knowledgeId, tags)
      await fetchKnowledgeTags(knowledgeId)
    } catch (e) {
      console.error('Failed to add knowledge tags', e)
    }
  }

  async function removeTag(knowledgeId: number, tagId: number) {
    try {
      await removeKnowledgeTag(knowledgeId, tagId)
      await fetchKnowledgeTags(knowledgeId)
    } catch (e) {
      console.error('Failed to remove knowledge tag', e)
    }
  }

  async function fetchKnowledgeStats(knowledgeId: number) {
    try {
      const res = await getKnowledgeStats(knowledgeId)
      if (res.data?.data) {
        knowledgeStats.value = res.data.data
      }
    } catch (e) {
      console.error('Failed to fetch knowledge stats', e)
    }
  }

  function setSearchKeyword(keyword: string) {
    searchKeyword.value = keyword
  }

  function setFilterType(type: string | null) {
    filterType.value = type
  }

  function clearCurrentKnowledge() {
    currentKnowledge.value = null
    chunks.value = []
    knowledgeGraph.value = null
    knowledgeLinks.value = []
    relatedKnowledge.value = []
    knowledgeStats.value = null
  }

  function clearSearchResults() {
    searchResults.value = []
  }

  function clearAll() {
    knowledgeList.value = []
    currentKnowledge.value = null
    chunks.value = []
    totalCount.value = 0
    currentPage.value = 1
    searchKeyword.value = ''
    filterType.value = null
    knowledgeGraph.value = null
    knowledgeLinks.value = []
    knowledgeTags.value = []
    relatedKnowledge.value = []
    searchResults.value = []
    knowledgeStats.value = null
  }

  return {
    knowledgeList,
    currentKnowledge,
    chunks,
    loading,
    totalCount,
    currentPage,
    pageSize,
    searchKeyword,
    filterType,
    hasCurrentKnowledge,
    filteredList,
    totalChunks,
    knowledgeGraph,
    knowledgeLinks,
    knowledgeTags,
    relatedKnowledge,
    searchResults,
    knowledgeStats,
    fetchKnowledgeList,
    fetchKnowledge,
    createNewKnowledge,
    updateCurrentKnowledge,
    removeKnowledge,
    fetchChunks,
    searchKnowledgeChunks,
    performHybridSearch,
    performSemanticSearch,
    fetchKnowledgeGraph,
    fetchRelatedKnowledge,
    fetchKnowledgeLinks,
    createLink,
    removeLink,
    fetchKnowledgeTags,
    addTags,
    removeTag,
    fetchKnowledgeStats,
    setSearchKeyword,
    setFilterType,
    clearCurrentKnowledge,
    clearSearchResults,
    clearAll
  }
})
