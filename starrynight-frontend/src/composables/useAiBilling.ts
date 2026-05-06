import { ref, computed } from 'vue'
import { estimateAiCost, type EstimateResult } from '@/api/ai'
import { getUserBalance, type UserBalanceDTO } from '@/api/billing'

export function useAiBilling() {
  const userId = ref<number | null>(null)
  const balance = ref<UserBalanceDTO | null>(null)
  const estimateResult = ref<EstimateResult | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  const canGenerate = computed(() => {
    if (!estimateResult.value) return true
    return estimateResult.value.scenario !== 'INSUFFICIENT'
  })

  const balanceDisplay = computed(() => {
    if (!balance.value) return { freeQuota: 0, platformCurrency: 0 }
    return {
      freeQuota: balance.value.freeQuota,
      platformCurrency: balance.value.platformCurrency,
      platformCurrencyInPoints: balance.value.platformCurrencyInPoints
    }
  })

  async function loadBalance(id: number) {
    userId.value = id
    loading.value = true
    error.value = null
    try {
      balance.value = await getUserBalance(id)
    } catch (e) {
      error.value = 'Failed to load balance'
      console.error(e)
    } finally {
      loading.value = false
    }
  }

  async function checkEstimate(contentType: string, inputTokens = 500, outputTokens = 1000) {
    if (!userId.value) {
      error.value = 'User not logged in'
      return null
    }

    loading.value = true
    error.value = null
    try {
      const res = await estimateAiCost(userId.value, contentType, inputTokens, outputTokens)
      estimateResult.value = res.data
      return res.data
    } catch (e) {
      error.value = 'Failed to estimate cost'
      console.error(e)
      return null
    } finally {
      loading.value = false
    }
  }

  function getEstimateMessage(): string {
    if (!estimateResult.value) return ''
    return estimateResult.value.message
  }

  function getEstimateScenario(): string {
    if (!estimateResult.value) return ''
    return estimateResult.value.scenario
  }

  function isBalanceSufficient(): boolean {
    if (!estimateResult.value) return true
    return estimateResult.value.scenario !== 'INSUFFICIENT'
  }

  function isMixedPayment(): boolean {
    if (!estimateResult.value) return false
    return estimateResult.value.scenario === 'MIXED_PAYMENT'
  }

  return {
    userId,
    balance,
    estimateResult,
    loading,
    error,
    canGenerate,
    balanceDisplay,
    loadBalance,
    checkEstimate,
    getEstimateMessage,
    getEstimateScenario,
    isBalanceSufficient,
    isMixedPayment
  }
}
