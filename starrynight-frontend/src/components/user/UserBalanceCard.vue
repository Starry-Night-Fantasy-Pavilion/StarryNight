<template>
  <div class="user-balance-card">
    <div class="balance-header">
      <span class="balance-title">💰 我的资产</span>
    </div>

    <div class="balance-content">
      <div class="balance-item">
        <div class="balance-label">📅 今日免费额度</div>
        <div class="balance-value">
          <span class="value-number">{{ formatNumber(balance?.freeQuota || 0) }}</span>
          <span class="value-unit">创作点</span>
        </div>
        <div class="balance-hint">⏰ 明日0点重置</div>
      </div>

      <el-divider direction="vertical" class="balance-divider" />

      <div class="balance-item">
        <div class="balance-label">💎 {{ coinName }}余额</div>
        <div class="balance-value">
          <span class="value-number">{{ formatDecimal(balance?.platformCurrency || 0) }}</span>
          <span class="value-unit">（约 {{ formatNumber(balance?.platformCurrencyInPoints || 0) }} 创作点）</span>
        </div>
        <div class="balance-action">
          <el-button type="primary" size="small" @click="showRechargeDialog = true">
            💳 充值
          </el-button>
        </div>
      </div>
    </div>

    <div class="balance-footer">
      <div class="mixed-payment">
        <span>🔄 混合支付:</span>
        <el-switch
          v-model="mixedPaymentEnabled"
          active-text="已开启"
          inactive-text="已关闭"
          @change="handleMixedPaymentChange"
        />
      </div>
    </div>

    <el-dialog v-model="showRechargeDialog" :title="`💎 充值${coinName}`" width="500px">
      <div class="recharge-current">
        当前余额: <strong>{{ formatDecimal(balance?.platformCurrency || 0) }}</strong> {{ coinName }}
      </div>

      <div class="recharge-packages">
        <div class="package-title">推荐套餐</div>
        <div class="package-grid">
          <div
            v-for="pkg in rechargePackages"
            :key="pkg.amount"
            class="package-item"
            :class="{ selected: selectedPackage === pkg.amount }"
            @click="selectedPackage = pkg.amount"
          >
            <div class="package-icon">{{ pkg.icon }}</div>
            <div class="package-amount">{{ pkg.amount }}元</div>
            <div class="package-currency">{{ pkg.currency }} {{ coinName }}</div>
            <div v-if="pkg.bonus > 0" class="package-bonus">+{{ pkg.bonus }}赠送</div>
          </div>
        </div>
      </div>

      <div class="custom-amount">
        <span>自定义金额:</span>
        <el-input-number v-model="customAmount" :min="1" :max="10000" />
        <span>元</span>
      </div>

      <div class="recharge-summary">
        将获得: <strong>{{ calculateCurrency() }}</strong> {{ coinName }}
      </div>

      <template #footer>
        <el-button @click="showRechargeDialog = false">取消</el-button>
        <el-button type="primary" @click="handleRecharge">立即充值</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getUserBalance, setMixedPayment, createRecharge, type UserBalanceDTO } from '@/api/billing'
import { usePortalPublicStore } from '@/stores/portalPublic'

const props = defineProps<{
  userId: number
}>()

const portalPublic = usePortalPublicStore()
const coinName = computed(() => portalPublic.platformCoinName)

const balance = ref<UserBalanceDTO | null>(null)
const mixedPaymentEnabled = ref(true)
const showRechargeDialog = ref(false)
const selectedPackage = ref<number | null>(30)
const customAmount = ref(0)

const rechargePackages = [
  { amount: 30, icon: '💎', currency: 315, bonus: 15 },
  { amount: 68, icon: '💎', currency: 748, bonus: 68 },
  { amount: 128, icon: '💎', currency: 1456, bonus: 128 },
  { amount: 328, icon: '💎', currency: 3800, bonus: 328 },
  { amount: 648, icon: '💎', currency: 7800, bonus: 648 },
]

const finalAmount = computed(() => selectedPackage.value || customAmount.value)

function calculateCurrency() {
  const amount = finalAmount.value
  if (amount >= 648) return Math.floor(amount * 10 * 1.1)
  if (amount >= 328) return Math.floor(amount * 10 * 1.1)
  if (amount >= 128) return Math.floor(amount * 10 * 1.1)
  if (amount >= 68) return Math.floor(amount * 10 * 1.1)
  if (amount >= 30) return Math.floor(amount * 10 * 1.05)
  return Math.floor(amount * 10)
}

function formatNumber(num: number): string {
  return num.toLocaleString()
}

function formatDecimal(num: number): string {
  return num.toFixed(2)
}

async function loadBalance() {
  try {
    const data = await getUserBalance(props.userId)
    balance.value = data
    mixedPaymentEnabled.value = Boolean(data.enableMixedPayment)
  } catch (error) {
    console.error('Failed to load balance:', error)
  }
}

async function handleMixedPaymentChange(enabled: boolean) {
  try {
    await setMixedPayment(props.userId, enabled)
    ElMessage.success(enabled ? '混合支付已开启' : '混合支付已关闭')
  } catch (error) {
    mixedPaymentEnabled.value = !enabled
    ElMessage.error('设置失败')
  }
}

async function handleRecharge() {
  if (finalAmount.value <= 0) {
    ElMessage.warning('请选择或输入充值金额')
    return
  }

  try {
    await createRecharge({
      userId: props.userId,
      amount: finalAmount.value,
      payMethod: 'alipay'
    })
    ElMessage.success('充值订单已创建（支付功能开发中）')
    showRechargeDialog.value = false
    loadBalance()
  } catch (error) {
    ElMessage.error('创建充值订单失败')
  }
}

onMounted(() => {
  loadBalance()
})
</script>

<style lang="scss" scoped>
.user-balance-card {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 12px;
  padding: 20px;
  color: white;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.balance-header {
  margin-bottom: 16px;
}

.balance-title {
  font-size: 18px;
  font-weight: 600;
}

.balance-content {
  display: flex;
  align-items: center;
  gap: 24px;
}

.balance-item {
  flex: 1;
}

.balance-label {
  font-size: 14px;
  opacity: 0.9;
  margin-bottom: 8px;
}

.balance-value {
  display: flex;
  align-items: baseline;
  gap: 4px;
}

.value-number {
  font-size: 28px;
  font-weight: 700;
}

.value-unit {
  font-size: 14px;
  opacity: 0.8;
}

.balance-hint {
  font-size: 12px;
  opacity: 0.7;
  margin-top: 4px;
}

.balance-action {
  margin-top: 8px;
}

.balance-divider {
  height: 60px;
  background: rgba(255, 255, 255, 0.3);
}

.balance-footer {
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid rgba(255, 255, 255, 0.2);
}

.mixed-payment {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 14px;
}

.recharge-current {
  margin-bottom: 20px;
  font-size: 16px;
}

.package-title {
  font-size: 14px;
  color: #666;
  margin-bottom: 12px;
}

.package-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  margin-bottom: 20px;
}

.package-item {
  border: 2px solid #eee;
  border-radius: 8px;
  padding: 16px;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s;

  &:hover {
    border-color: #667eea;
  }

  &.selected {
    border-color: #667eea;
    background: #f0f0ff;
  }
}

.package-icon {
  font-size: 24px;
  margin-bottom: 8px;
}

.package-amount {
  font-size: 16px;
  font-weight: 600;
  color: #333;
}

.package-currency {
  font-size: 14px;
  color: #666;
  margin-top: 4px;
}

.package-bonus {
  font-size: 12px;
  color: #ff6b6b;
  margin-top: 4px;
}

.custom-amount {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
}

.recharge-summary {
  font-size: 16px;
  text-align: center;
  padding: 16px;
  background: #f5f5f5;
  border-radius: 8px;
}
</style>
