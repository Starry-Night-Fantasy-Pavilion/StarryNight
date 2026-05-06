<template>
  <div class="checkin-card">
    <div class="checkin-header">
      <div class="checkin-title">
        <span class="icon">📅</span>
        <span>每日签到</span>
      </div>
      <div class="continuous-days" v-if="status">
        <span class="flame">🔥</span>
        <span>连续 {{ status.continuousDays }} 天</span>
      </div>
    </div>

    <div class="checkin-calendar">
      <div class="weekday-header">
        <span v-for="day in weekdays" :key="day">{{ day }}</span>
      </div>
      <div class="calendar-grid">
        <div
          v-for="day in calendarDays"
          :key="day.date"
          class="calendar-day"
          :class="{
            'checked': day.checked,
            'today': day.isToday,
            'future': day.isFuture
          }"
        >
          <span class="day-number">{{ day.day }}</span>
          <span v-if="day.checked" class="check-icon">✓</span>
        </div>
      </div>
    </div>

    <div class="checkin-reward" v-if="status">
      <div class="reward-info">
        <span class="label">今日签到奖励:</span>
        <span class="value">{{ status.todayReward }} 创作点</span>
      </div>
      <el-button
        type="primary"
        :disabled="status.checkedIn"
        :loading="checking"
        @click="handleCheckin"
        class="checkin-btn"
      >
        {{ status.checkedIn ? '已签到' : '立即签到' }}
      </el-button>
    </div>

    <div class="checkin-stats" v-if="status">
      <div class="stat-item">
        <span class="stat-value">{{ status.totalCheckins }}</span>
        <span class="stat-label">累计签到</span>
      </div>
      <div class="stat-item">
        <span class="stat-value">{{ status.maxContinuousDays }}</span>
        <span class="stat-label">最高连续</span>
      </div>
    </div>

    <div class="streak-rewards">
      <div class="streak-title">连续签到奖励</div>
      <div class="streak-items">
        <div class="streak-item" :class="{ achieved: status && status.continuousDays >= 7 }">
          <span class="streak-days">7天</span>
          <span class="streak-reward">+100</span>
        </div>
        <div class="streak-item" :class="{ achieved: status && status.continuousDays >= 15 }">
          <span class="streak-days">15天</span>
          <span class="streak-reward">+300</span>
        </div>
        <div class="streak-item" :class="{ achieved: status && status.continuousDays >= 30 }">
          <span class="streak-days">30天</span>
          <span class="streak-reward">+500</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getCheckinStatus, doCheckin, type CheckinStatus, type CheckinResult } from '@/api/growth'

const props = defineProps<{
  userId: number
}>()

const weekdays = ['日', '一', '二', '三', '四', '五', '六']
const status = ref<CheckinStatus | null>(null)
const checking = ref(false)

const calendarDays = computed(() => {
  if (!status.value) return []

  const today = new Date()
  const year = today.getFullYear()
  const month = today.getMonth()
  const firstDay = new Date(year, month, 1).getDay()
  const daysInMonth = new Date(year, month + 1, 0).getDate()
  const todayDate = today.getDate()

  const days = []

  for (let i = 0; i < firstDay; i++) {
    days.push({ date: '', day: '', checked: false, isToday: false, isFuture: false })
  }

  const checkedSet = new Set(status.value.checkedDates || [])

  for (let d = 1; d <= daysInMonth; d++) {
    const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`
    days.push({
      date: dateStr,
      day: d,
      checked: checkedSet.has(dateStr),
      isToday: d === todayDate,
      isFuture: false
    })
  }

  return days
})

async function loadStatus() {
  try {
    const res = await getCheckinStatus(props.userId)
    status.value = res.data
  } catch (error) {
    console.error('Failed to load checkin status:', error)
  }
}

async function handleCheckin() {
  checking.value = true
  try {
    const res = await doCheckin(props.userId)
    if (res.data.success) {
      ElMessage.success(res.data.message)
      await loadStatus()
    } else {
      ElMessage.info(res.data.message)
    }
  } catch (error) {
    ElMessage.error('签到失败')
  } finally {
    checking.value = false
  }
}

onMounted(() => {
  loadStatus()
})
</script>

<style lang="scss" scoped>
.checkin-card {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 16px;
  padding: 20px;
  color: white;
}

.checkin-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.checkin-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 18px;
  font-weight: 600;

  .icon {
    font-size: 24px;
  }
}

.continuous-days {
  display: flex;
  align-items: center;
  gap: 4px;
  background: rgba(255, 255, 255, 0.2);
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 14px;

  .flame {
    font-size: 16px;
  }
}

.checkin-calendar {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 16px;
}

.weekday-header {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  text-align: center;
  font-size: 12px;
  opacity: 0.8;
  margin-bottom: 8px;
}

.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 4px;
}

.calendar-day {
  aspect-ratio: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  font-size: 14px;
  position: relative;

  &.checked {
    background: rgba(74, 222, 128, 0.3);
    .check-icon {
      display: block;
    }
  }

  &.today {
    border: 2px solid #fff;
  }

  &.future {
    opacity: 0.3;
  }

  .check-icon {
    display: none;
    font-size: 10px;
    color: #4ade80;
  }
}

.checkin-reward {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.reward-info {
  .label {
    font-size: 14px;
    opacity: 0.9;
  }

  .value {
    font-size: 20px;
    font-weight: 700;
    margin-left: 8px;
  }
}

.checkin-btn {
  background: #fff;
  color: #667eea;
  border: none;
  font-weight: 600;

  &:hover {
    background: #f0f0ff;
  }

  &:disabled {
    background: rgba(255, 255, 255, 0.3);
    color: #fff;
  }
}

.checkin-stats {
  display: flex;
  justify-content: space-around;
  padding: 12px 0;
  border-top: 1px solid rgba(255, 255, 255, 0.2);
  margin-bottom: 16px;
}

.stat-item {
  text-align: center;

  .stat-value {
    font-size: 24px;
    font-weight: 700;
    display: block;
  }

  .stat-label {
    font-size: 12px;
    opacity: 0.8;
  }
}

.streak-rewards {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  padding: 16px;
}

.streak-title {
  font-size: 14px;
  margin-bottom: 12px;
  opacity: 0.9;
}

.streak-items {
  display: flex;
  justify-content: space-between;
}

.streak-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 12px 16px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  min-width: 80px;
  opacity: 0.5;

  &.achieved {
    opacity: 1;
    background: rgba(74, 222, 128, 0.3);
  }

  .streak-days {
    font-size: 14px;
    font-weight: 600;
  }

  .streak-reward {
    font-size: 12px;
    color: #4ade80;
    margin-top: 4px;
  }
}
</style>
