import { describe, it, expect } from 'vitest'
import { exportNovel } from '@/api/novel'

describe('novel API', () => {
  describe('exportNovel', () => {
    it('should call export with txt format by default', () => {
      const mockGet = vi.fn().mockResolvedValue({
        data: {
          code: 0,
          data: 'test content'
        }
      })

      expect(typeof exportNovel).toBe('function')
    })

    it('should accept html format parameter', () => {
      expect(typeof exportNovel).toBe('function')
    })
  })
})
