export const EDITOR_PROMPT_KEY = 'starrynight_editor_prompt'

export interface EditorPrompt {
  type: 'prompt'
  promptId: number
  promptTitle: string
  promptContent: string
  variables?: Record<string, string>
  timestamp: number
}

export function setEditorPrompt(prompt: Omit<EditorPrompt, 'timestamp'>) {
  const data: EditorPrompt = {
    ...prompt,
    timestamp: Date.now()
  }
  sessionStorage.setItem(EDITOR_PROMPT_KEY, JSON.stringify(data))
  window.dispatchEvent(new CustomEvent('editor-prompt-update', { detail: data }))
}

export function getEditorPrompt(): EditorPrompt | null {
  const data = sessionStorage.getItem(EDITOR_PROMPT_KEY)
  if (!data) return null

  try {
    const prompt = JSON.parse(data) as EditorPrompt
    if (Date.now() - prompt.timestamp > 60000) {
      sessionStorage.removeItem(EDITOR_PROMPT_KEY)
      return null
    }
    return prompt
  } catch {
    return null
  }
}

export function clearEditorPrompt() {
  sessionStorage.removeItem(EDITOR_PROMPT_KEY)
}

export function listenEditorPrompt(callback: (prompt: EditorPrompt) => void) {
  const handler = (event: CustomEvent<EditorPrompt>) => {
    callback(event.detail)
  }

  window.addEventListener('editor-prompt-update', handler as EventListener)

  return () => {
    window.removeEventListener('editor-prompt-update', handler as EventListener)
  }
}
