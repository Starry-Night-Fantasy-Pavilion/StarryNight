import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export interface AIMessage {
  id: string
  role: 'user' | 'assistant' | 'system'
  content: string
  timestamp: Date
  isStreaming?: boolean
  metadata?: Record<string, any>
}

export interface AIContext {
  includeCurrentNode: boolean
  includeOutline: boolean
  includeKnowledge: boolean
  knowledgeIds: number[]
  includeCharacters: boolean
  characterIds: number[]
  includeMaterials: boolean
  materialIds: number[]
}

export interface AIGenerationProgress {
  stage: 'intent' | 'context' | 'generation' | 'consistency' | 'done'
  progress: number
  message: string
  isComplete: boolean
}

export const useAIStore = defineStore('ai', () => {
  const messages = ref<AIMessage[]>([])
  const isStreaming = ref(false)
  const isGenerating = ref(false)
  const generationProgress = ref<AIGenerationProgress | null>(null)
  const context = ref<AIContext>({
    includeCurrentNode: true,
    includeOutline: true,
    includeKnowledge: false,
    knowledgeIds: [],
    includeCharacters: false,
    characterIds: [],
    includeMaterials: false,
    materialIds: []
  })
  const showContextSettings = ref(false)
  const currentStreamingMessageId = ref<string | null>(null)
  const conversationId = ref<string | null>(null)
  const quickPrompts = ref<string[]>([
    '帮我完善这个情节点',
    '添加更多细节描写',
    '调整情感节奏',
    '生成一些对白'
  ])

  const hasMessages = computed(() => messages.value.length > 0)
  const canSend = computed(() => !isStreaming.value && !isGenerating.value)

  function addMessage(message: Omit<AIMessage, 'id' | 'timestamp'>) {
    const newMessage: AIMessage = {
      ...message,
      id: `msg_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
      timestamp: new Date()
    }
    messages.value.push(newMessage)
    return newMessage.id
  }

  function updateMessage(messageId: string, updates: Partial<AIMessage>) {
    const index = messages.value.findIndex(m => m.id === messageId)
    if (index !== -1) {
      messages.value[index] = { ...messages.value[index], ...updates }
    }
  }

  function removeMessage(messageId: string) {
    messages.value = messages.value.filter(m => m.id !== messageId)
  }

  function clearMessages() {
    messages.value = []
    conversationId.value = null
  }

  function startStreaming(messageId: string) {
    currentStreamingMessageId.value = messageId
    isStreaming.value = true
  }

  function stopStreaming() {
    isStreaming.value = false
    currentStreamingMessageId.value = null
  }

  function setGenerationProgress(progress: AIGenerationProgress | null) {
    generationProgress.value = progress
    if (progress) {
      isGenerating.value = !progress.isComplete
    } else {
      isGenerating.value = false
    }
  }

  function updateContext(updates: Partial<AIContext>) {
    context.value = { ...context.value, ...updates }
  }

  function toggleContextSetting<K extends keyof AIContext>(key: K) {
    context.value[key] = !context.value[key] as any
  }

  function resetContext() {
    context.value = {
      includeCurrentNode: true,
      includeOutline: true,
      includeKnowledge: false,
      knowledgeIds: [],
      includeCharacters: false,
      characterIds: [],
      includeMaterials: false,
      materialIds: []
    }
  }

  function setConversationId(id: string | null) {
    conversationId.value = id
  }

  function appendToStreamingMessage(messageId: string, content: string) {
    const message = messages.value.find(m => m.id === messageId)
    if (message) {
      message.content += content
    }
  }

  function setQuickPrompts(prompts: string[]) {
    quickPrompts.value = prompts
  }

  function addQuickPrompt(prompt: string) {
    if (!quickPrompts.value.includes(prompt)) {
      quickPrompts.value.unshift(prompt)
      if (quickPrompts.value.length > 10) {
        quickPrompts.value.pop()
      }
    }
  }

  function removeQuickPrompt(prompt: string) {
    quickPrompts.value = quickPrompts.value.filter(p => p !== prompt)
  }

  return {
    messages,
    isStreaming,
    isGenerating,
    generationProgress,
    context,
    showContextSettings,
    currentStreamingMessageId,
    conversationId,
    quickPrompts,
    hasMessages,
    canSend,
    addMessage,
    updateMessage,
    removeMessage,
    clearMessages,
    startStreaming,
    stopStreaming,
    setGenerationProgress,
    updateContext,
    toggleContextSetting,
    resetContext,
    setConversationId,
    appendToStreamingMessage,
    setQuickPrompts,
    addQuickPrompt,
    removeQuickPrompt
  }
})
