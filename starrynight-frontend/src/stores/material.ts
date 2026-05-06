import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { MaterialItem, MaterialCategory, MaterialTag } from '@/api/material'
import {
  listMaterials,
  getMaterial,
  createMaterial,
  updateMaterial,
  deleteMaterial,
  batchDeleteMaterials,
  recordMaterialUsage,
  recommendMaterials,
  getMaterialCategories,
  getMaterialTags,
  toggleFavorite,
  getRelatedMaterials,
  searchMaterials,
  type MaterialCreateDTO,
  type MaterialUpdateDTO
} from '@/api/material'
import type { PageVO, ResponseVO } from '@/types/api'

export const useMaterialStore = defineStore('material', () => {
  const materialList = ref<MaterialItem[]>([])
  const currentMaterial = ref<MaterialItem | null>(null)
  const categories = ref<MaterialCategory[]>([])
  const tags = ref<MaterialTag[]>([])
  const recommendedMaterials = ref<MaterialItem[]>([])
  const relatedMaterials = ref<MaterialItem[]>([])
  const loading = ref(false)
  const totalCount = ref(0)
  const currentPage = ref(1)
  const pageSize = ref(10)
  const searchKeyword = ref('')
  const filterType = ref<string | null>(null)
  const selectedTags = ref<string[]>([])
  const favorites = ref<Set<number>>(new Set())

  const hasCurrentMaterial = computed(() => currentMaterial.value !== null)
  const filteredList = computed(() => {
    let result = materialList.value
    if (searchKeyword.value) {
      const keyword = searchKeyword.value.toLowerCase()
      result = result.filter(m =>
        m.title?.toLowerCase().includes(keyword) ||
        m.description?.toLowerCase().includes(keyword) ||
        m.content?.toLowerCase().includes(keyword)
      )
    }
    if (filterType.value) {
      result = result.filter(m => m.type === filterType.value)
    }
    if (selectedTags.value.length > 0) {
      result = result.filter(m =>
        m.tags?.some(tag => selectedTags.value.includes(tag))
      )
    }
    return result
  })

  const allTags = computed(() => {
    const tagSet = new Set<string>()
    materialList.value.forEach(m => {
      m.tags?.forEach(tag => tagSet.add(tag))
    })
    return Array.from(tagSet).sort()
  })

  const favoriteMaterials = computed(() =>
    materialList.value.filter(m => favorites.value.has(m.id || 0))
  )

  async function fetchMaterialList(params?: {
    page?: number
    size?: number
    keyword?: string
    type?: string
    tags?: string[]
    favorite?: boolean
  }) {
    loading.value = true
    try {
      const res = await listMaterials({
        page: params?.page || currentPage.value,
        size: params?.size || pageSize.value,
        keyword: params?.keyword,
        type: params?.type,
        tags: params?.tags,
        favorite: params?.favorite
      })
      if (res.data) {
        materialList.value = res.data.records || []
        totalCount.value = res.data.total || 0
      }
    } finally {
      loading.value = false
    }
  }

  async function fetchMaterial(id: number) {
    loading.value = true
    try {
      const res = await getMaterial(id)
      if (res.data) {
        currentMaterial.value = res.data
      }
      return res.data
    } finally {
      loading.value = false
    }
  }

  async function createNewMaterial(data: MaterialCreateDTO) {
    const res = await createMaterial(data)
    if (res.data) {
      materialList.value.unshift(res.data)
      totalCount.value++
    }
    return res.data
  }

  async function updateCurrentMaterial(id: number, data: MaterialUpdateDTO) {
    const res = await updateMaterial(id, data)
    if (res.data) {
      const index = materialList.value.findIndex(m => m.id === id)
      if (index !== -1) {
        materialList.value[index] = res.data
      }
      if (currentMaterial.value?.id === id) {
        currentMaterial.value = res.data
      }
    }
    return res.data
  }

  async function removeMaterial(id: number) {
    await deleteMaterial(id)
    materialList.value = materialList.value.filter(m => m.id !== id)
    totalCount.value--
    if (currentMaterial.value?.id === id) {
      currentMaterial.value = null
    }
  }

  async function batchDelete(ids: number[]) {
    await batchDeleteMaterials(ids)
    materialList.value = materialList.value.filter(m => !ids.includes(m.id || 0))
    totalCount.value -= ids.length
  }

  async function fetchCategories() {
    try {
      const res = await getMaterialCategories()
      if (res.data?.data) {
        categories.value = res.data.data
      }
    } catch (e) {
      console.error('Failed to fetch categories', e)
    }
  }

  async function fetchTags(params?: { type?: string }) {
    try {
      const res = await getMaterialTags(params)
      if (res.data?.data) {
        tags.value = res.data.data
      }
    } catch (e) {
      console.error('Failed to fetch tags', e)
    }
  }

  async function fetchRecommended(params?: { novelId?: number; context?: string; type?: string; limit?: number }) {
    try {
      const res = await recommendMaterials(params || {})
      if (res.data?.data) {
        recommendedMaterials.value = res.data.data
      }
    } catch (e) {
      console.error('Failed to fetch recommended materials', e)
    }
  }

  async function fetchRelated(id: number, limit?: number) {
    try {
      const res = await getRelatedMaterials(id, limit)
      if (res.data?.data) {
        relatedMaterials.value = res.data.data
      }
    } catch (e) {
      console.error('Failed to fetch related materials', e)
    }
  }

  async function search(keyword: string, params?: { type?: string; tags?: string[]; page?: number; size?: number }) {
    try {
      const res = await searchMaterials({ query: keyword, ...params })
      if (res.data?.data) {
        materialList.value = res.data.data.records || []
        totalCount.value = res.data.data.total || 0
      }
    } catch (e) {
      console.error('Failed to search materials', e)
    }
  }

  async function toggleFavoriteMaterial(id: number) {
    try {
      const res = await toggleFavorite(id)
      if (res.data?.data) {
        if (favorites.value.has(id)) {
          favorites.value.delete(id)
        } else {
          favorites.value.add(id)
        }
      }
    } catch (e) {
      console.error('Failed to toggle favorite', e)
    }
  }

  async function recordUsage(id: number) {
    try {
      await recordMaterialUsage(id)
      const material = materialList.value.find(m => m.id === id)
      if (material) {
        material.usageCount = (material.usageCount || 0) + 1
      }
    } catch (e) {
      console.error('Failed to record usage', e)
    }
  }

  function setSearchKeyword(keyword: string) {
    searchKeyword.value = keyword
  }

  function setFilterType(type: string | null) {
    filterType.value = type
  }

  function setSelectedTags(tags: string[]) {
    selectedTags.value = tags
  }

  function toggleTag(tag: string) {
    const index = selectedTags.value.indexOf(tag)
    if (index === -1) {
      selectedTags.value.push(tag)
    } else {
      selectedTags.value.splice(index, 1)
    }
  }

  function clearFilters() {
    searchKeyword.value = ''
    filterType.value = null
    selectedTags.value = []
  }

  function clearCurrentMaterial() {
    currentMaterial.value = null
    relatedMaterials.value = []
  }

  return {
    materialList,
    currentMaterial,
    categories,
    tags,
    recommendedMaterials,
    relatedMaterials,
    loading,
    totalCount,
    currentPage,
    pageSize,
    searchKeyword,
    filterType,
    selectedTags,
    favorites,
    hasCurrentMaterial,
    filteredList,
    allTags,
    favoriteMaterials,
    fetchMaterialList,
    fetchMaterial,
    createNewMaterial,
    updateCurrentMaterial,
    removeMaterial,
    batchDelete,
    fetchCategories,
    fetchTags,
    fetchRecommended,
    fetchRelated,
    search,
    toggleFavoriteMaterial,
    recordUsage,
    setSearchKeyword,
    setFilterType,
    setSelectedTags,
    toggleTag,
    clearFilters,
    clearCurrentMaterial
  }
})
