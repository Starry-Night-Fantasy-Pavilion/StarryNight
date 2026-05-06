<template>
  <el-drawer
    v-model="visible"
    title="AI创作助手"
    direction="rtl"
    size="480px"
    :before-close="handleClose"
    class="ai-chat-drawer"
  >
    <div class="chat-container">
      <div class="context-panel">
        <div class="context-header">
          <span>上下文引用</span>
          <el-button link type="primary" size="small" @click="showContextSettings = !showContextSettings">
            {{ showContextSettings ? '收起' : '展开' }}
          </el-button>
        </div>
        <el-collapse-transition>
          <div v-show="showContextSettings" class="context-settings">
            <el-checkbox v-model="contextOptions.includeCurrentNode" @change="updateContext">
              当前节点内容
            </el-checkbox>
            <el-checkbox v-model="contextOptions.includeOutline" @change="updateContext">
              大纲摘要
            </el-checkbox>
            <div class="context-item">
              <el-checkbox v-model="contextOptions.includeKnowledge" @change="updateContext">
                知识库:
              </el-checkbox>
              <el-select
                v-model="contextOptions.knowledgeIds"
                multiple
                placeholder="选择知识库"
                size="small"
                style="width: 200px"
                @change="updateContext"
              >
                <el-option
                  v-for="kb in knowledgeBases"
                  :key="kb.id"
                  :label="kb.name"
                  :value="kb.id"
                />
              </el-select>
            </div>
            <div class="context-item">
              <el-checkbox v-model="contextOptions.includeCharacters" @change="updateContext">
                角色库:
              </el-checkbox>
              <el-select
                v-model="contextOptions.characterIds"
                multiple
                placeholder="选择角色"
                size="small"
                style="width: 200px"
                @change="updateContext"
              >
                <el-option
                  v-for="char in characters"
                  :key="char.id"
                  :label="char.name"
                  :value="char.id"
                />
              </el-select>
            </div>
          </div>
        </el-collapse-transition>
      </div>

      <div class="chat-messages" ref="messagesContainer">
        <div v-if="messages.length === 0" class="empty-state">
          <el-icon :size="48"><ChatDotRound /></el-icon>
          <p>开始与AI对话，修改当前节点内容</p>
          <div class="quick-prompts">
            <el-tag
              v-for="prompt in quickPrompts"
              :key="prompt"
              class="quick-prompt"
              @click="sendQuickPrompt(prompt)"
            >
              {{ prompt }}
            </el-tag>
          </div>
        </div>

        <div v-for="(msg, index) in messages" :key="index" :class="['message', msg.role]">
          <div class="message-avatar">
            <el-icon v-if="msg.role === 'assistant'" :size="24"><MagicStick /></el-icon>
            <el-icon v-else :size="24"><User /></el-icon>
          </div>
          <div class="message-content">
            <div class="message-header">
              <span class="message-role">{{ msg.role === 'assistant' ? 'AI助手' : '你' }}</span>
              <span class="message-time">{{ formatTime(msg.timestamp) }}</span>
            </div>
            <div v-if="msg.role === 'assistant' && msg.isStreaming" class="streaming-indicator">
              <span class="streaming-dot"></span>
              AI正在创作中...
              <el-button size="small" type="danger" @click="interruptStream">中断</el-button>
            </div>
            <div class="message-text" v-html="renderMarkdown(msg.content)"></div>

            <div v-if="msg.role === 'assistant' && msg.suggestions && msg.suggestions.length > 0" class="message-suggestions">
              <span class="suggestions-label">快捷指令:</span>
              <el-button
                v-for="suggestion in msg.suggestions"
                :key="suggestion"
                size="small"
                @click="sendQuickPrompt(suggestion)"
              >
                {{ suggestion }}
              </el-button>
            </div>

            <div v-if="msg.modifications && msg.modifications.length > 0" class="modifications-panel">
              <div class="modifications-header">
                <span>修改建议</span>
                <el-tag size="small" type="warning">{{ msg.modifications.length }}项</el-tag>
              </div>
              <div
                v-for="(mod, modIdx) in msg.modifications"
                :key="modIdx"
                class="modification-item"
              >
                <div class="mod-diff">
                  <div class="diff-old">
                    <span class="diff-label">原内容:</span>
                    <div class="diff-content" v-html="renderMarkdown(mod.oldContent)"></div>
                  </div>
                  <div class="diff-arrow">
                    <el-icon><ArrowRight /></el-icon>
                  </div>
                  <div class="diff-new">
                    <span class="diff-label">修改后:</span>
                    <div class="diff-content" v-html="renderMarkdown(mod.newContent)"></div>
                  </div>
                </div>
                <div class="mod-actions">
                  <el-button size="small" type="success" @click="acceptModification(msg, modIdx)">
                    接受修改
                  </el-button>
                  <el-button size="small" type="danger" @click="rejectModification(msg, modIdx)">
                    拒绝
                  </el-button>
                  <el-button size="small" @click="continueAdjust(mod)">
                    继续调整
                  </el-button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="chat-input">
        <div v-if="replyingTo" class="replying-to">
          <span>回复于: {{ replyingTo.content.substring(0, 50) }}...</span>
          <el-button link type="danger" @click="cancelReply">取消</el-button>
        </div>
        <div class="input-row">
          <div class="mention-wrapper" style="position: relative; width: 100%;">
            <el-input
              v-model="inputText"
              type="textarea"
              :rows="3"
              placeholder="输入您的问题或指令... (Enter发送, Shift+Enter换行, @知识库 引用知识)"
              resize="none"
              @keydown.enter="handleEnterKey"
              @input="handleInputChange"
              ref="inputRef"
            />
            <div v-if="showMentionPopup" class="mention-popup" :style="mentionPopupStyle">
              <div class="mention-header">
                <span>引用知识库</span>
                <el-button link type="danger" size="small" @click="showMentionPopup = false">关闭</el-button>
              </div>
              <el-input
                v-model="mentionSearchKeyword"
                placeholder="搜索知识..."
                size="small"
                clearable
                @input="searchKnowledgeForMention"
              />
              <div v-if="mentionLoading" class="mention-loading">
                <el-icon class="is-loading"><Loading /></el-icon>
              </div>
              <div v-else-if="mentionResults.length > 0" class="mention-results">
                <div
                  v-for="item in mentionResults"
                  :key="item.id"
                  class="mention-item"
                  @click="selectMentionItem(item)"
                >
                  <span class="mention-icon">📖</span>
                  <span class="mention-content">{{ item.content?.slice(0, 80) || '无内容' }}</span>
                </div>
              </div>
              <div v-else-if="mentionSearchKeyword" class="mention-empty">
                未找到相关知识
              </div>
              <div v-else class="mention-hint">
                输入关键词搜索知识库
              </div>
            </div>
          </div>
        </div>
        <div class="input-actions">
          <div class="action-left">
            <el-tooltip content="清空对话">
              <el-button :icon="Delete" circle @click="clearMessages" />
            </el-tooltip>
            <el-tooltip content="对话历史">
              <el-button :icon="Clock" circle @click="showHistory = true" />
            </el-tooltip>
          </div>
          <div class="action-right">
            <el-button
              type="primary"
              :loading="sending"
              :disabled="!inputText.trim()"
              @click="sendMessage"
            >
              发送
            </el-button>
          </div>
        </div>
      </div>
    </div>

    <el-dialog v-model="showHistory" title="对话历史" width="600px">
      <div class="history-list">
        <el-timeline>
          <el-timeline-item
            v-for="(session, idx) in chatHistory"
            :key="session.id"
            :timestamp="formatDate(session.timestamp)"
            placement="top"
          >
            <el-card shadow="hover" class="history-item" @click="loadHistorySession(session)">
              <div class="history-preview">
                <span class="history-title">{{ session.nodeType }} - {{ session.nodeTitle }}</span>
                <span class="history-count">{{ session.messageCount }} 条消息</span>
              </div>
              <div class="history-first-msg">{{ session.firstMessage }}</div>
            </el-card>
          </el-timeline-item>
        </el-timeline>
        <el-empty v-if="chatHistory.length === 0" description="暂无对话历史" />
      </div>
    </el-dialog>
  </el-drawer>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch, nextTick } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Delete, Clock, ArrowRight, ChatDotRound, MagicStick, User, Loading } from '@element-plus/icons-vue'
import { webLLMService } from '@/services/webllm'
import { listKnowledge, searchAllChunks } from '@/api/knowledge'
import { listCharacters } from '@/api/character'
import type { ChatCompletionMessageParam } from '@mlc-ai/web-llm'

interface ContextOptions {
  includeCurrentNode: boolean
  includeOutline: boolean
  includeKnowledge: boolean
  knowledgeIds: number[]
  includeCharacters: boolean
  characterIds: number[]
}

interface ChatMessage {
  role: 'user' | 'assistant'
  content: string
  timestamp: Date
  isStreaming?: boolean
  suggestions?: string[]
  modifications?: Modification[]
}

interface Modification {
  oldContent: string
  newContent: string
  reason?: string
}

interface ChatSession {
  id: string
  nodeType: string
  nodeTitle: string
  firstMessage: string
  messageCount: number
  timestamp: Date
  messages: ChatMessage[]
}

interface KnowledgeBase {
  id: number
  name: string
}

interface Character {
  id: number
  name: string
}

const props = defineProps<{
  modelValue: boolean
  currentNode?: {
    type: string
    title: string
    content: string
  }
  outlineSummary?: string
  novelId?: number
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'accept-modification': [content: string]
}>()

const visible = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
})

const inputText = ref('')
const sending = ref(false)
const messages = ref<ChatMessage[]>([])
const messagesContainer = ref<HTMLElement>()
const inputRef = ref<HTMLElement>()
const showContextSettings = ref(false)
const showMentionPopup = ref(false)
const mentionSearchKeyword = ref('')
const mentionResults = ref<any[]>([])
const mentionLoading = ref(false)
const mentionPopupStyle = ref({ top: '0px', left: '0px' })
let mentionSearchTimer: ReturnType<typeof setTimeout> | null = null
const showHistory = ref(false)
const replyingTo = ref<ChatMessage | null>(null)
const isStreaming = ref(false)
let abortController: AbortController | null = null

const contextOptions = reactive<ContextOptions>({
  includeCurrentNode: true,
  includeOutline: true,
  includeKnowledge: false,
  knowledgeIds: [],
  includeCharacters: false,
  characterIds: []
})

const knowledgeBases = ref<KnowledgeBase[]>([])
const characters = ref<Character[]>([])
const chatHistory = ref<ChatSession[]>([])

const quickPrompts = [
  '帮我修改这个情节点',
  '增加更多细节描写',
  '调整情感节奏',
  '生成对白建议',
  '情节建议'
]

async function loadKnowledgeAndCharacters() {
  if (!props.novelId) return
  try {
    const [kbRes, charRes] = await Promise.all([
      listKnowledge({ page: 1, size: 100, status: 'READY' }),
      listCharacters({ page: 1, size: 100, novelId: props.novelId })
    ])
    knowledgeBases.value = kbRes.data?.records || []
    characters.value = charRes.data?.records || []
  } catch (e) {
    console.error('Failed to load context data', e)
  }
}

function updateContext() {}

function buildSystemPrompt(): string {
  let context = '你是星夜阁的AI创作助手，专注于帮助作者优化小说内容。\n\n'

  if (contextOptions.includeCurrentNode && props.currentNode) {
    context += `【当前节点】\n类型: ${props.currentNode.type}\n标题: ${props.currentNode.title}\n内容: ${props.currentNode.content}\n\n`
  }

  if (contextOptions.includeOutline && props.outlineSummary) {
    context += `【大纲摘要】\n${props.outlineSummary}\n\n`
  }

  if (contextOptions.includeKnowledge && contextOptions.knowledgeIds.length > 0) {
    const selectedKBs = knowledgeBases.value
      .filter(kb => contextOptions.knowledgeIds.includes(kb.id))
      .map(kb => kb.name)
    context += `【引用知识库】: ${selectedKBs.join(', ')}\n\n`
  }

  if (contextOptions.includeCharacters && contextOptions.characterIds.length > 0) {
    const selectedChars = characters.value
      .filter(c => contextOptions.characterIds.includes(c.id))
      .map(c => c.name)
    context += `【出场角色】: ${selectedChars.join(', ')}\n\n`
  }

  context += '请根据上下文，帮助作者优化当前内容。'
  return context
}

function buildMessages(): ChatCompletionMessageParam[] {
  const msgs: ChatCompletionMessageParam[] = [
    { role: 'system', content: buildSystemPrompt() }
  ]

  for (const msg of messages.value) {
    msgs.push({
      role: msg.role,
      content: msg.content
    })
  }

  if (inputText.value.trim()) {
    msgs.push({
      role: 'user',
      content: inputText.value.trim()
    })
  }

  return msgs
}

async function sendMessage() {
  if (!inputText.value.trim() || sending.value) return

  const userMsg: ChatMessage = {
    role: 'user',
    content: inputText.value.trim(),
    timestamp: new Date(),
    ...(replyingTo.value && { replyTo: replyingTo.value })
  }

  messages.value.push(userMsg)
  const userInput = inputText.value
  inputText.value = ''
  replyingTo.value = null
  sending.value = true
  isStreaming.value = true
  scrollToBottom()

  try {
    const aiMsg: ChatMessage = {
      role: 'assistant',
      content: '',
      timestamp: new Date(),
      isStreaming: true
    }
    messages.value.push(aiMsg)

    const msgs = buildMessages()
    abortController = new AbortController()

    const fullContent = await streamResponse(msgs, (chunk) => {
      aiMsg.content += chunk
      scrollToBottom()
    })

    aiMsg.content = fullContent
    aiMsg.isStreaming = false

    if (fullContent.includes('【修改建议】') || fullContent.includes('建议修改为')) {
      aiMsg.modifications = extractModifications(fullContent)
    }

    aiMsg.suggestions = extractSuggestions(fullContent)

    saveToHistory()
  } catch (error: any) {
    if (error.name === 'AbortError') {
      ElMessage.info('已中断生成')
    } else {
      ElMessage.error('生成失败: ' + error.message)
    }
  } finally {
    sending.value = false
    isStreaming.value = false
    abortController = null
  }
}

async function streamResponse(
  messages: ChatCompletionMessageParam[],
  onChunk: (chunk: string) => void
): Promise<string> {
  if (!webLLMService.isReady()) {
    return await webLLMService.chatCompletion({ messages }) as unknown as string
  }

  let fullContent = ''
  try {
    for await (const chunk of webLLMService.chatCompletionStream({ messages })) {
      if (abortController?.signal.aborted) {
        throw new DOMException('Aborted', 'AbortError')
      }
      const content = chunk.choices[0]?.delta?.content || ''
      if (content) {
        fullContent += content
        onChunk(content)
      }
    }
  } catch (e: any) {
    if (e.name === 'AbortError') {
      throw e
    }
    console.error('Stream error, falling back to non-stream', e)
    const result = await webLLMService.chatCompletion({ messages: messages.filter(m => m.role !== 'user').concat({ role: 'user', content: inputText.value }) })
    fullContent = (result as any).choices?.[0]?.message?.content || ''
  }
  return fullContent
}

function interruptStream() {
  if (abortController) {
    abortController.abort()
    isStreaming.value = false
  }
}

function extractModifications(content: string): Modification[] {
  const mods: Modification[] = []
  const modPattern = /原内容[:：]\s*([\s\S]*?)\n修改后[:：]\s*([\s\S]*?)(?=\n\n|$)/g
  let match
  while ((match = modPattern.exec(content)) !== null) {
    mods.push({
      oldContent: match[1].trim(),
      newContent: match[2].trim()
    })
  }
  return mods
}

function extractSuggestions(content: string): string[] {
  const suggestions: string[] = []
  const patterns = [
    /快捷指令[:：]\s*([^\n]+)/,
    /建议[:：]\s*([^\n]+)/,
    /你可以[:：]\s*([^\n]+)/
  ]
  for (const pattern of patterns) {
    const match = content.match(pattern)
    if (match) {
      suggestions.push(...match[1].split(/[,，]/).map(s => s.trim()).filter(Boolean))
    }
  }
  return suggestions.slice(0, 4)
}

function acceptModification(msg: ChatMessage, modIdx: number) {
  const mod = msg.modifications?.[modIdx]
  if (mod) {
    emit('accept-modification', mod.newContent)
    msg.modifications = msg.modifications?.filter((_, i) => i !== modIdx)
    ElMessage.success('已接受修改')
  }
}

function rejectModification(msg: ChatMessage, modIdx: number) {
  msg.modifications = msg.modifications?.filter((_, i) => i !== modIdx)
  ElMessage.info('已拒绝')
}

function continueAdjust(mod: Modification) {
  inputText.value = `继续调整这个修改：${mod.newContent}`
}

function sendQuickPrompt(prompt: string) {
  inputText.value = prompt
  sendMessage()
}

function handleEnterKey(e: KeyboardEvent) {
  if (!e.shiftKey) {
    e.preventDefault()
    sendMessage()
  }
}

function clearMessages() {
  messages.value = []
  ElMessage.success('对话已清空')
}

function cancelReply() {
  replyingTo.value = null
}

function handleInputChange() {
  const text = inputText.value
  const atIndex = text.lastIndexOf('@知识库')

  if (atIndex !== -1) {
    const afterAt = text.slice(atIndex)
    if (!afterAt.includes(' ') && !afterAt.includes('\n')) {
      showMentionPopup.value = true
      mentionSearchKeyword.value = ''
      mentionResults.value = []
      mentionPopupStyle.value = { top: '-200px', left: '0px' }
    } else if (showMentionPopup.value && mentionSearchKeyword.value) {
      searchKnowledgeForMention()
    }
  } else {
    showMentionPopup.value = false
  }
}

function searchKnowledgeForMention() {
  if (mentionSearchTimer) {
    clearTimeout(mentionSearchTimer)
  }

  if (!mentionSearchKeyword.value.trim()) {
    mentionResults.value = []
    return
  }

  mentionSearchTimer = setTimeout(async () => {
    mentionLoading.value = true
    try {
      const res = await searchAllChunks(mentionSearchKeyword.value, { page: 1, size: 10 })
      if (res.data?.records) {
        mentionResults.value = res.data.records
      } else if (res.data) {
        mentionResults.value = Array.isArray(res.data) ? res.data : []
      }
    } catch (error) {
      console.error('Search failed:', error)
      mentionResults.value = []
    } finally {
      mentionLoading.value = false
    }
  }, 300)
}

function selectMentionItem(item: any) {
  const atIndex = inputText.value.lastIndexOf('@知识库')
  const beforeAt = inputText.value.slice(0, atIndex)
  const reference = `【引用知识库:${item.content?.slice(0, 100) || ''}】`

  if (beforeAt.trim() === '') {
    inputText.value = reference + '\n'
  } else {
    inputText.value = beforeAt + reference + '\n'
  }

  showMentionPopup.value = false
  mentionSearchKeyword.value = ''
  mentionResults.value = []
  inputRef.value?.focus()
}

function scrollToBottom() {
  nextTick(() => {
    if (messagesContainer.value) {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
    }
  })
}

function formatTime(date: Date): string {
  return date.toLocaleTimeString('zh-CN', { hour: '2-digit', minute: '2-digit' })
}

function formatDate(date: Date): string {
  return date.toLocaleDateString('zh-CN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function renderMarkdown(text: string): string {
  if (!text) return ''
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
    .replace(/\*(.+?)\*/g, '<em>$1</em>')
    .replace(/`(.+?)`/g, '<code>$1</code>')
    .replace(/\n/g, '<br>')
}

function saveToHistory() {
  if (messages.value.length < 2) return

  const session: ChatSession = {
    id: Date.now().toString(),
    nodeType: props.currentNode?.type || 'unknown',
    nodeTitle: props.currentNode?.title || '未知节点',
    firstMessage: messages.value[0]?.content?.substring(0, 50) || '',
    messageCount: messages.value.length,
    timestamp: new Date(),
    messages: [...messages.value]
  }

  const existing = chatHistory.value.findIndex(s => s.nodeTitle === session.nodeTitle)
  if (existing >= 0) {
    chatHistory.value[existing] = session
  } else {
    chatHistory.value.unshift(session)
    if (chatHistory.value.length > 20) {
      chatHistory.value = chatHistory.value.slice(0, 20)
    }
  }

  try {
    localStorage.setItem('ai_chat_history', JSON.stringify(chatHistory.value))
  } catch (e) {
    console.error('Failed to save chat history', e)
  }
}

function loadHistory() {
  try {
    const saved = localStorage.getItem('ai_chat_history')
    if (saved) {
      chatHistory.value = JSON.parse(saved).map((s: any) => ({
        ...s,
        timestamp: new Date(s.timestamp)
      }))
    }
  } catch (e) {
    console.error('Failed to load chat history', e)
  }
}

function loadHistorySession(session: ChatSession) {
  messages.value = [...session.messages]
  showHistory.value = false
  ElMessage.success('已加载历史对话')
  scrollToBottom()
}

function handleClose(done: () => void) {
  if (messages.value.length > 0) {
    ElMessageBox.confirm('关闭将清空当前对话，确定关闭吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    }).then(() => {
      messages.value = []
      done()
    }).catch(() => {})
  } else {
    done()
  }
}

watch(() => props.modelValue, (val) => {
  if (val) {
    loadKnowledgeAndCharacters()
    loadHistory()
  }
})

watch(() => props.currentNode, (node) => {
  if (node && visible.value) {
    updateContext()
  }
})
</script>

<style lang="scss" scoped>
.ai-chat-drawer {
  :deep(.el-drawer__header) {
    margin-bottom: 0;
    padding: 16px 20px;
    border-bottom: 1px solid var(--el-border-color-lighter);
  }

  :deep(.el-drawer__body) {
    padding: 0;
    height: 100%;
  }
}

.chat-container {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.context-panel {
  padding: 12px 16px;
  background: var(--el-fill-color-light);
  border-bottom: 1px solid var(--el-border-color-lighter);

  .context-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
    color: var(--el-text-color-secondary);
  }

  .context-settings {
    margin-top: 12px;
    display: flex;
    flex-direction: column;
    gap: 8px;

    .context-item {
      display: flex;
      align-items: center;
      gap: 8px;
    }
  }
}

.chat-messages {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 16px;

  &::-webkit-scrollbar {
    width: 6px;
  }

  &::-webkit-scrollbar-thumb {
    background: var(--el-border-color);
    border-radius: 3px;
  }
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: var(--el-text-color-secondary);
  text-align: center;

  p {
    margin: 16px 0;
  }

  .quick-prompts {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: center;

    .quick-prompt {
      cursor: pointer;

      &:hover {
        opacity: 0.8;
      }
    }
  }
}

.message {
  display: flex;
  gap: 12px;
  max-width: 100%;

  &.user {
    flex-direction: row-reverse;

    .message-content {
      align-items: flex-end;
    }

    .message-text {
      background: var(--el-color-primary-light-8);
      border-radius: 16px 16px 4px 16px;
    }
  }

  &.assistant {
    .message-text {
      background: var(--el-fill-color-light);
      border-radius: 16px 16px 16px 4px;
    }
  }

  .message-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: var(--el-fill-color);
  }

  .message-content {
    display: flex;
    flex-direction: column;
    gap: 4px;
    max-width: 85%;

    .message-header {
      display: flex;
      gap: 8px;
      font-size: 12px;
      color: var(--el-text-color-secondary);

      .message-role {
        font-weight: 500;
      }
    }

    .message-text {
      padding: 10px 14px;
      line-height: 1.6;
      word-break: break-word;
    }

    .streaming-indicator {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 12px;
      color: var(--el-color-primary);
      padding: 4px 0;

      .streaming-dot {
        width: 8px;
        height: 8px;
        background: var(--el-color-primary);
        border-radius: 50%;
        animation: pulse 1s infinite;
      }

      @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
      }
    }

    .message-suggestions {
      display: flex;
      flex-wrap: wrap;
      gap: 4px;
      align-items: center;
      margin-top: 4px;

      .suggestions-label {
        font-size: 12px;
        color: var(--el-text-color-secondary);
      }
    }
  }
}

.modifications-panel {
  margin-top: 12px;
  padding: 12px;
  background: var(--el-color-warning-light-9);
  border-radius: 8px;

  .modifications-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    font-weight: 500;
  }

  .modification-item {
    padding: 12px;
    background: white;
    border-radius: 6px;
    margin-bottom: 8px;

    &:last-child {
      margin-bottom: 0;
    }

    .mod-diff {
      display: flex;
      gap: 8px;
      margin-bottom: 8px;

      > div {
        flex: 1;
      }

      .diff-label {
        font-size: 12px;
        color: var(--el-text-color-secondary);
        display: block;
        margin-bottom: 4px;
      }

      .diff-content {
        padding: 8px;
        background: var(--el-fill-color-light);
        border-radius: 4px;
        font-size: 13px;
        max-height: 100px;
        overflow-y: auto;
      }

      .diff-arrow {
        display: flex;
        align-items: center;
        color: var(--el-color-primary);
      }
    }

    .mod-actions {
      display: flex;
      gap: 8px;
    }
  }
}

.chat-input {
  padding: 12px 16px;
  border-top: 1px solid var(--el-border-color-lighter);
  background: white;

  .replying-to {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 12px;
    background: var(--el-fill-color-light);
    border-radius: 6px;
    margin-bottom: 8px;
    font-size: 13px;
    color: var(--el-text-color-secondary);
  }

  .input-row {
    :deep(.el-textarea__inner) {
      border-radius: 8px;
    }
  }

  .mention-popup {
    position: absolute;
    top: -200px;
    left: 0;
    width: 100%;
    background: white;
    border: 1px solid var(--el-border-color-lighter);
    border-radius: 8px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
    z-index: 100;
    max-height: 200px;
    overflow: hidden;
    display: flex;
    flex-direction: column;

    .mention-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 8px 12px;
      border-bottom: 1px solid var(--el-border-color-lighter);
      font-weight: 500;
      font-size: 13px;
    }

    .mention-loading,
    .mention-empty,
    .mention-hint {
      padding: 16px;
      text-align: center;
      color: var(--el-text-color-secondary);
      font-size: 12px;
    }

    .mention-results {
      flex: 1;
      overflow-y: auto;

      .mention-item {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        padding: 8px 12px;
        cursor: pointer;
        transition: background 0.2s;

        &:hover {
          background: var(--el-fill-color-light);
        }

        .mention-icon {
          font-size: 14px;
          flex-shrink: 0;
        }

        .mention-content {
          font-size: 12px;
          color: var(--el-text-color-regular);
          overflow: hidden;
          text-overflow: ellipsis;
          white-space: nowrap;
        }
      }
    }
  }

  .input-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 8px;

    .action-left {
      display: flex;
      gap: 4px;
    }
  }
}

.history-list {
  max-height: 500px;
  overflow-y: auto;

  .history-item {
    cursor: pointer;
    transition: all 0.3s;

    &:hover {
      transform: translateY(-2px);
    }

    .history-preview {
      display: flex;
      justify-content: space-between;
      margin-bottom: 8px;

      .history-title {
        font-weight: 500;
      }

      .history-count {
        font-size: 12px;
        color: var(--el-text-color-secondary);
      }
    }

    .history-first-msg {
      font-size: 13px;
      color: var(--el-text-color-secondary);
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
  }
}
</style>
