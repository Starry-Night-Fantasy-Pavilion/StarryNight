import { ref, computed } from 'vue'
import { getMemberStatus, getMemberBenefits, checkBenefit, type MemberBenefits } from '@/api/vip'

export function useVipMembership(userId: () => number | null) {
  const memberStatus = ref<any>(null)
  const benefits = ref<MemberBenefits | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  const isVip = computed(() => {
    return memberStatus.value?.memberLevel >= 2
  })

  const isSvip = computed(() => {
    return memberStatus.value?.memberLevel >= 3
  })

  const memberLevel = computed(() => {
    return memberStatus.value?.memberLevel || 1
  })

  const memberLevelName = computed(() => {
    return memberStatus.value?.memberLevelName || '普通用户'
  })

  const dailyFreeQuota = computed(() => {
    return memberStatus.value?.dailyFreeQuota || 10000
  })

  const isActive = computed(() => {
    return memberStatus.value?.isActive || false
  })

  async function loadStatus() {
    const id = userId()
    if (!id) return

    loading.value = true
    error.value = null

    try {
      const [statusRes, benefitsRes] = await Promise.all([
        getMemberStatus(id),
        getMemberBenefits(id)
      ])
      memberStatus.value = statusRes.data
      benefits.value = benefitsRes.data
    } catch (e) {
      error.value = 'Failed to load membership status'
      console.error(e)
    } finally {
      loading.value = false
    }
  }

  async function hasSpecificBenefit(benefitKey: string): Promise<boolean> {
    const id = userId()
    if (!id) return false

    try {
      const res = await checkBenefit(id, benefitKey)
      return res.data
    } catch (e) {
      console.error('Failed to check benefit:', e)
      return false
    }
  }

  function canUseFeature(featureKey: string): boolean {
    if (!benefits.value) return true
    const feature = benefits.value[featureKey]
    if (typeof feature === 'boolean') return feature
    if (typeof feature === 'object' && feature !== null) return !!feature.value
    return true
  }

  function getFeatureLimit(featureKey: string, defaultLimit: number): number {
    if (!benefits.value) return defaultLimit
    const feature = benefits.value[featureKey]
    if (typeof feature === 'object' && feature !== null) return feature.value || defaultLimit
    return defaultLimit
  }

  return {
    memberStatus,
    benefits,
    loading,
    error,
    isVip,
    isSvip,
    memberLevel,
    memberLevelName,
    dailyFreeQuota,
    isActive,
    loadStatus,
    hasSpecificBenefit,
    canUseFeature,
    getFeatureLimit
  }
}
