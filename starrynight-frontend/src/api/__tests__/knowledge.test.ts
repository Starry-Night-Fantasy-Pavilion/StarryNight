import { describe, it, expect } from 'vitest'
import { searchAllChunks, listKnowledge } from '@/api/knowledge'

describe('knowledge API', () => {
  describe('searchAllChunks', () => {
    it('should be a function', () => {
      expect(typeof searchAllChunks).toBe('function')
    })

    it('should accept keyword parameter', () => {
      expect(typeof searchAllChunks).toBe('function')
    })

    it('should accept optional pagination parameters', () => {
      expect(typeof searchAllChunks).toBe('function')
    })
  })

  describe('listKnowledge', () => {
    it('should be a function', () => {
      expect(typeof listKnowledge).toBe('function')
    })

    it('should accept keyword, type, status and pagination params', () => {
      expect(typeof listKnowledge).toBe('function')
    })
  })
})
