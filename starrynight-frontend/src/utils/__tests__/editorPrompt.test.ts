import { describe, it, expect, beforeEach } from 'vitest'
import { setEditorPrompt, getEditorPrompt, clearEditorPrompt, EDITOR_PROMPT_KEY } from '@/utils/editorPrompt'

describe('editorPrompt utilities', () => {
  beforeEach(() => {
    sessionStorage.clear()
  })

  describe('setEditorPrompt', () => {
    it('should store prompt in session storage', () => {
      setEditorPrompt({
        type: 'prompt',
        promptId: 1,
        promptTitle: 'Test Prompt',
        promptContent: 'Test content'
      })

      const stored = sessionStorage.getItem(EDITOR_PROMPT_KEY)
      expect(stored).toBeTruthy()
    })

    it('should include timestamp', () => {
      const before = Date.now()
      setEditorPrompt({
        type: 'prompt',
        promptId: 1,
        promptTitle: 'Test Prompt',
        promptContent: 'Test content'
      })
      const after = Date.now()

      const stored = JSON.parse(sessionStorage.getItem(EDITOR_PROMPT_KEY) || '{}')
      expect(stored.timestamp).toBeGreaterThanOrEqual(before)
      expect(stored.timestamp).toBeLessThanOrEqual(after)
    })

    it('should include variables if provided', () => {
      const variables = { outline: 'test outline', style: '武侠' }
      setEditorPrompt({
        type: 'prompt',
        promptId: 1,
        promptTitle: 'Test',
        promptContent: 'Content',
        variables
      })

      const stored = JSON.parse(sessionStorage.getItem(EDITOR_PROMPT_KEY) || '{}')
      expect(stored.variables).toEqual(variables)
    })
  })

  describe('getEditorPrompt', () => {
    it('should return null when no prompt stored', () => {
      const result = getEditorPrompt()
      expect(result).toBeNull()
    })

    it('should return prompt when valid', () => {
      setEditorPrompt({
        type: 'prompt',
        promptId: 1,
        promptTitle: 'Test',
        promptContent: 'Content'
      })

      const result = getEditorPrompt()
      expect(result).toBeTruthy()
      expect(result?.promptId).toBe(1)
      expect(result?.promptTitle).toBe('Test')
    })

    it('should return null when prompt is older than 60 seconds', () => {
      const oldData = {
        type: 'prompt',
        promptId: 1,
        promptTitle: 'Test',
        promptContent: 'Content',
        timestamp: Date.now() - 70000
      }
      sessionStorage.setItem(EDITOR_PROMPT_KEY, JSON.stringify(oldData))

      const result = getEditorPrompt()
      expect(result).toBeNull()
    })
  })

  describe('clearEditorPrompt', () => {
    it('should remove prompt from session storage', () => {
      setEditorPrompt({
        type: 'prompt',
        promptId: 1,
        promptTitle: 'Test',
        promptContent: 'Content'
      })

      clearEditorPrompt()

      const stored = sessionStorage.getItem(EDITOR_PROMPT_KEY)
      expect(stored).toBeNull()
    })
  })
})
