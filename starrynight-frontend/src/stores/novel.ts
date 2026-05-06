import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Novel, NovelVolume, NovelChapter, NovelOutline } from '@/types/api'
import { getNovel, listNovels, createNovel, updateNovel, deleteNovel, listVolumes, createVolume, updateVolume, deleteVolume, listChapters, createChapter, updateChapter, deleteChapter, generateOutline, generateVolumes } from '@/api/novel'
import type { PageVO, ResponseVO } from '@/types/api'

export const useNovelStore = defineStore('novel', () => {
  const novelList = ref<Novel[]>([])
  const currentNovel = ref<Novel | null>(null)
  const volumes = ref<NovelVolume[]>([])
  const currentVolume = ref<NovelVolume | null>(null)
  const chapters = ref<NovelChapter[]>([])
  const currentChapter = ref<NovelChapter | null>(null)
  const outline = ref<NovelOutline | null>(null)
  const loading = ref(false)
  const totalCount = ref(0)
  const currentPage = ref(1)
  const pageSize = ref(10)

  const hasCurrentNovel = computed(() => currentNovel.value !== null)
  const novelId = computed(() => currentNovel.value?.id)

  async function fetchNovelList(params?: { page?: number; size?: number; keyword?: string; status?: number }) {
    loading.value = true
    try {
      const res = await listNovels(params)
      if (res.data) {
        novelList.value = res.data.records || []
        totalCount.value = res.data.total || 0
        if (params?.page) currentPage.value = params.page
        if (params?.size) pageSize.value = params.size
      }
    } finally {
      loading.value = false
    }
  }

  async function fetchNovel(id: number) {
    loading.value = true
    try {
      const res = await getNovel(id)
      if (res.data) {
        currentNovel.value = res.data
      }
      return res.data
    } finally {
      loading.value = false
    }
  }

  async function createNewNovel(data: Partial<Novel>) {
    const res = await createNovel(data)
    if (res.data) {
      novelList.value.unshift(res.data)
      totalCount.value++
    }
    return res.data
  }

  async function updateCurrentNovel(data: Partial<Novel>) {
    if (!currentNovel.value?.id) return null
    const res = await updateNovel(currentNovel.value.id, data)
    if (res.data) {
      const index = novelList.value.findIndex(n => n.id === currentNovel.value?.id)
      if (index !== -1) {
        novelList.value[index] = res.data
      }
      currentNovel.value = res.data
    }
    return res.data
  }

  async function removeNovel(id: number) {
    await deleteNovel(id)
    novelList.value = novelList.value.filter(n => n.id !== id)
    totalCount.value--
    if (currentNovel.value?.id === id) {
      currentNovel.value = null
    }
  }

  async function fetchVolumes(novelId: number) {
    const res = await listVolumes(novelId)
    volumes.value = res || []
  }

  async function fetchChapters(novelId: number, volumeId?: number) {
    const res = await listChapters(novelId, volumeId)
    chapters.value = res || []
  }

  function setCurrentVolume(volume: NovelVolume | null) {
    currentVolume.value = volume
  }

  function setCurrentChapter(chapter: NovelChapter | null) {
    currentChapter.value = chapter
  }

  function clearCurrentNovel() {
    currentNovel.value = null
    volumes.value = []
    currentVolume.value = null
    chapters.value = []
    currentChapter.value = null
    outline.value = null
  }

  return {
    novelList,
    currentNovel,
    volumes,
    currentVolume,
    chapters,
    currentChapter,
    outline,
    loading,
    totalCount,
    currentPage,
    pageSize,
    hasCurrentNovel,
    novelId,
    fetchNovelList,
    fetchNovel,
    createNewNovel,
    updateCurrentNovel,
    removeNovel,
    fetchVolumes,
    fetchChapters,
    setCurrentVolume,
    setCurrentChapter,
    clearCurrentNovel
  }
})
