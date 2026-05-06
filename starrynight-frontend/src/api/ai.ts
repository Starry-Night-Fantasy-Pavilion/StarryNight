import request from '@/utils/request'

export interface EstimateResult {
  estimatedPoints: number
  message: string
  scenario: 'SUFFICIENT' | 'MIXED_PAYMENT' | 'FREE_INSUFFICIENT' | 'PAID_INSUFFICIENT' | 'INSUFFICIENT'
}

export interface AiMessage {
  role: 'system' | 'user' | 'assistant'
  content: string
}

export interface AiGenerationRequest {
  userId: number
  contentType: string
  channelId?: number
  model?: string
  messages: AiMessage[]
  temperature?: number
  maxTokens?: number
  inputTokens?: number
  outputTokens?: number
}

export function estimateAiCost(
  userId: number,
  contentType: string,
  inputTokens: number = 500,
  outputTokens: number = 1000
): Promise<{ data: EstimateResult }> {
  return request.get<EstimateResult>('/api/ai/estimate', {
    params: { userId, contentType, inputTokens, outputTokens }
  })
}

export function generateWithStream(
  data: AiGenerationRequest,
  onChunk: (content: string) => void,
  onDone: () => void,
  onError: (error: Error) => void
): () => void {
  let closed = false

  fetch('/api/ai/generate/stream', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(data)
  })
    .then(response => {
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`)
      }

      const reader = response.body?.getReader()
      if (!reader) {
        throw new Error('No response body')
      }

      const decoder = new TextDecoder()
      let buffer = ''

      function read() {
        if (closed) return

        reader.read().then(({ done, value }) => {
          if (done || closed) {
            if (!buffer.includes('[DONE]')) {
              onDone()
            }
            return
          }

          buffer += decoder.decode(value, { stream: true })
          const lines = buffer.split('\n')
          buffer = lines.pop() || ''

          for (const line of lines) {
            if (line.startsWith('data: ')) {
              const data = line.slice(6).trim()
              if (data && data !== '[DONE]') {
                try {
                  const parsed = JSON.parse(data)
                  if (parsed.content) {
                    onChunk(parsed.content)
                  }
                  if (parsed.finish === 'stop') {
                    onDone()
                    closed = true
                    return
                  }
                } catch (e) {
                  // ignore parse errors
                }
              }
            }
          }

          read()
        }).catch(err => {
          if (!closed) {
            onError(err)
          }
        })
      }

      read()
    })
    .catch(err => {
      if (!closed) {
        onError(err)
      }
    })

  return () => {
    closed = true
  }
}

export function generateWithoutStream(data: AiGenerationRequest): Promise<{ data: any }> {
  return request.post('/api/ai/generate', data)
}
