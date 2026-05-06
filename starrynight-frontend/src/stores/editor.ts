import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export type EditorMode = 'outline' | 'volume' | 'chapter-outline' | 'content'

export interface EditorNode {
  id: string
  type: 'outline' | 'volume' | 'chapter' | 'section'
  title: string
  parentId?: string
  children?: EditorNode[]
  content?: string
  expanded?: boolean
}

export interface EditorDraft {
  id?: number
  type: 'outline' | 'volume' | 'chapter' | 'content'
  title: string
  content: string
  version: number
  createTime?: string
}

export const useEditorStore = defineStore('editor', () => {
  const mode = ref<EditorMode>('outline')
  const currentNode = ref<EditorNode | null>(null)
  const treeData = ref<EditorNode[]>([])
  const content = ref('')
  const isDirty = ref(false)
  const isSaving = ref(false)
  const lastSaved = ref<Date | null>(null)
  const autoSaveEnabled = ref(true)
  const showKnowledgeRef = ref(false)
  const showContentConsistencyCheck = ref(false)
  const showAiChat = ref(false)
  const drafts = ref<EditorDraft[]>([])
  const currentDraft = ref<EditorDraft | null>(null)

  const hasContent = computed(() => content.value.trim().length > 0)
  const isEditing = computed(() => currentNode.value !== null)

  function setMode(newMode: EditorMode) {
    mode.value = newMode
  }

  function setCurrentNode(node: EditorNode | null) {
    currentNode.value = node
    if (node?.content !== undefined) {
      content.value = node.content
    }
  }

  function setTreeData(data: EditorNode[]) {
    treeData.value = data
  }

  function updateContent(newContent: string) {
    content.value = newContent
    isDirty.value = true
  }

  function updateNodeContent(nodeId: string, newContent: string) {
    const updateRecursive = (nodes: EditorNode[]): boolean => {
      for (const node of nodes) {
        if (node.id === nodeId) {
          node.content = newContent
          return true
        }
        if (node.children && updateRecursive(node.children)) {
          return true
        }
      }
      return false
    }
    updateRecursive(treeData.value)
    if (currentNode.value?.id === nodeId) {
      content.value = newContent
    }
    isDirty.value = true
  }

  function toggleNodeExpanded(nodeId: string) {
    const findAndToggle = (nodes: EditorNode[]): boolean => {
      for (const node of nodes) {
        if (node.id === nodeId) {
          node.expanded = !node.expanded
          return true
        }
        if (node.children && findAndToggle(node.children)) {
          return true
        }
      }
      return false
    }
    findAndToggle(treeData.value)
  }

  function addNode(parentId: string | null, node: EditorNode) {
    if (parentId === null) {
      treeData.value.push(node)
    } else {
      const addToParent = (nodes: EditorNode[]): boolean => {
        for (const n of nodes) {
          if (n.id === parentId) {
            if (!n.children) n.children = []
            n.children.push(node)
            return true
          }
          if (n.children && addToParent(n.children)) {
            return true
          }
        }
        return false
      }
      addToParent(treeData.value)
    }
  }

  function removeNode(nodeId: string) {
    const removeRecursive = (nodes: EditorNode[], parentList: EditorNode[]): boolean => {
      for (let i = 0; i < nodes.length; i++) {
        if (nodes[i].id === nodeId) {
          parentList.splice(i, 1)
          return true
        }
        if (nodes[i].children && removeRecursive(nodes[i].children!, nodes)) {
          return true
        }
      }
      return false
    }
    removeRecursive(treeData.value, treeData.value)
    if (currentNode.value?.id === nodeId) {
      currentNode.value = null
    }
  }

  function reorderNodes(parentId: string | null, nodeIds: string[]) {
    const getNodeById = (nodes: EditorNode[], id: string): EditorNode | null => {
      for (const node of nodes) {
        if (node.id === id) return node
        if (node.children) {
          const found = getNodeById(node.children, id)
          if (found) return found
        }
      }
      return null
    }

    const newOrder: EditorNode[] = []
    for (const id of nodeIds) {
      const node = getNodeById(treeData.value, id)
      if (node) newOrder.push(node)
    }

    if (parentId === null) {
      treeData.value = newOrder
    } else {
      const setOrder = (nodes: EditorNode[]): boolean => {
        for (const n of nodes) {
          if (n.id === parentId) {
            n.children = newOrder
            return true
          }
          if (n.children && setOrder(n.children)) {
            return true
          }
        }
        return false
      }
      setOrder(treeData.value)
    }
  }

  async function save() {
    if (!currentNode.value) return
    isSaving.value = true
    try {
      lastSaved.value = new Date()
      isDirty.value = false
    } finally {
      isSaving.value = false
    }
  }

  function markSaved() {
    lastSaved.value = new Date()
    isDirty.value = false
  }

  function reset() {
    mode.value = 'outline'
    currentNode.value = null
    treeData.value = []
    content.value = ''
    isDirty.value = false
    isSaving.value = false
    lastSaved.value = null
    drafts.value = []
    currentDraft.value = null
  }

  return {
    mode,
    currentNode,
    treeData,
    content,
    isDirty,
    isSaving,
    lastSaved,
    autoSaveEnabled,
    showKnowledgeRef,
    showContentConsistencyCheck,
    showAiChat,
    drafts,
    currentDraft,
    hasContent,
    isEditing,
    setMode,
    setCurrentNode,
    setTreeData,
    updateContent,
    updateNodeContent,
    toggleNodeExpanded,
    addNode,
    removeNode,
    reorderNodes,
    save,
    markSaved,
    reset
  }
})
