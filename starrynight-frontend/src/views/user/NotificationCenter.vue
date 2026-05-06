<template>
  <div class="notification-center page-container">
    <div class="page-header">
      <h1>🔔 通知中心</h1>
    </div>

    <el-row :gutter="20">
      <el-col :span="16">
        <el-card>
          <template #header>
            <div class="card-header">
              <span>我的通知</span>
              <el-button link type="primary" @click="handleMarkAllRead" v-if="unreadCount > 0">
                全部标为已读
              </el-button>
            </div>
          </template>

          <el-tabs v-model="activeTab" @tab-change="handleTabChange">
            <el-tab-pane label="全部" name="all" />
            <el-tab-pane label="系统通知" name="SYSTEM" />
            <el-tab-pane label="账号通知" name="ACCOUNT" />
            <el-tab-pane label="订单通知" name="ORDER" />
            <el-tab-pane label="活动通知" name="ACTIVITY" />
          </el-tabs>

          <div class="notification-list" v-loading="loading">
            <div v-if="notifications.length === 0" class="empty-state">
              <span class="empty-icon">📭</span>
              <p>暂无通知</p>
            </div>

            <div
              v-for="notification in notifications"
              :key="notification.id"
              class="notification-item"
              :class="{ unread: notification.isRead === 0 }"
              @click="handleClick(notification)"
            >
              <div class="notification-icon">
                {{ getTypeIcon(notification.notificationType) }}
              </div>
              <div class="notification-content">
                <div class="notification-header">
                  <span class="notification-title">{{ notification.title }}</span>
                  <span class="notification-time">{{ formatTime(notification.createTime) }}</span>
                </div>
                <div class="notification-body">
                  {{ notification.content }}
                </div>
                <div class="notification-meta">
                  <el-tag size="small" :type="getTypeTagType(notification.notificationType)">
                    {{ getTypeLabel(notification.notificationType) }}
                  </el-tag>
                  <el-tag v-if="notification.priority === 'HIGH' || notification.priority === 'URGENT'"
                          size="small" type="danger">
                    {{ notification.priority === 'URGENT' ? '紧急' : '重要' }}
                  </el-tag>
                </div>
              </div>
              <div class="notification-actions">
                <el-button link type="danger" @click.stop="handleDelete(notification.id)">
                  删除
                </el-button>
              </div>
            </div>
          </div>

          <div class="pagination-wrapper" v-if="total > pageSize">
            <el-pagination
              v-model:current-page="currentPage"
              :page-size="pageSize"
              :total="total"
              layout="prev, pager, next"
              @current-change="loadNotifications"
            />
          </div>
        </el-card>
      </el-col>

      <el-col :span="8">
        <el-card class="settings-card">
          <template #header>
            <span>通知设置</span>
          </template>

          <div class="settings-list">
            <div v-for="type in notificationTypes" :key="type.value" class="setting-item">
              <div class="setting-info">
                <span class="setting-icon">{{ type.icon }}</span>
                <span class="setting-name">{{ type.label }}</span>
              </div>
              <div class="setting-controls">
                <el-switch
                  :model-value="getPushEnabled(type.value)"
                  @change="(val: boolean) => handlePushChange(type.value, val)"
                  active-text="推送"
                />
              </div>
            </div>
          </div>
        </el-card>

        <el-card class="tips-card" style="margin-top: 20px">
          <template #header>
            <span>通知说明</span>
          </template>
          <div class="tips-content">
            <ul>
              <li>系统会在重要事件发生时向您发送通知</li>
              <li>您可以设置各类型通知的推送开关</li>
              <li>超过30天的通知将自动清理</li>
              <li>如有疑问请联系客服</li>
            </ul>
          </div>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  getNotifications,
  getNotificationsByType,
  getUnreadCount,
  markAsRead,
  markAllAsRead,
  deleteNotification,
  getUserSettings,
  updateSetting,
  notificationTypeLabels,
  notificationTypeIcons,
  type NotificationMessage,
  type NotificationSetting
} from '@/api/notification'

const props = defineProps<{
  userId: number
}>()

const notifications = ref<NotificationMessage[]>([])
const loading = ref(false)
const activeTab = ref('all')
const currentPage = ref(1)
const pageSize = ref(20)
const total = ref(0)
const unreadCount = ref(0)
const settings = ref<Record<string, NotificationSetting>>({})

const notificationTypes = [
  { value: 'SYSTEM', label: '系统通知', icon: '📢' },
  { value: 'ACCOUNT', label: '账号通知', icon: '👤' },
  { value: 'ORDER', label: '订单通知', icon: '💰' },
  { value: 'ACTIVITY', label: '活动通知', icon: '🎁' },
  { value: 'INTERACTION', label: '互动通知', icon: '💬' },
  { value: 'AI创作', label: 'AI创作通知', icon: '🤖' }
]

function getTypeIcon(type: string): string {
  return notificationTypeIcons[type] || '📌'
}

function getTypeLabel(type: string): string {
  return notificationTypeLabels[type] || type
}

function getTypeTagType(type: string): string {
  const typeMap: Record<string, string> = {
    'SYSTEM': 'primary',
    'ACCOUNT': 'success',
    'ORDER': 'warning',
    'ACTIVITY': 'info',
    'INTERACTION': 'info',
    'AI创作': 'danger'
  }
  return typeMap[type] || 'info'
}

function getPushEnabled(type: string): boolean {
  const setting = settings.value[type]
  return setting ? setting.pushEnabled === 1 : true
}

function formatTime(timeStr: string): string {
  if (!timeStr) return ''
  const date = new Date(timeStr)
  const now = new Date()
  const diff = now.getTime() - date.getTime()

  if (diff < 60000) return '刚刚'
  if (diff < 3600000) return `${Math.floor(diff / 60000)}分钟前`
  if (diff < 86400000) return `${Math.floor(diff / 3600000)}小时前`
  if (diff < 604800000) return `${Math.floor(diff / 86400000)}天前`

  return date.toLocaleDateString('zh-CN', { month: '2-digit', day: '2-digit' })
}

async function loadNotifications() {
  loading.value = true
  try {
    let res
    if (activeTab.value === 'all') {
      res = await getNotifications(props.userId, pageSize.value)
      notifications.value = res.data
    } else {
      res = await getNotificationsByType(props.userId, activeTab.value)
      notifications.value = res.data
    }
    total.value = notifications.value.length
  } catch (error) {
    console.error('Failed to load notifications:', error)
  } finally {
    loading.value = false
  }
}

async function loadUnreadCount() {
  try {
    const res = await getUnreadCount(props.userId)
    unreadCount.value = res.data
  } catch (error) {
    console.error('Failed to load unread count:', error)
  }
}

async function loadSettings() {
  try {
    const res = await getUserSettings(props.userId)
    settings.value = res.data
  } catch (error) {
    console.error('Failed to load settings:', error)
  }
}

function handleTabChange() {
  currentPage.value = 1
  loadNotifications()
}

async function handleClick(notification: NotificationMessage) {
  if (notification.isRead === 0) {
    await markAsRead(notification.id)
    notification.isRead = 1
    unreadCount.value = Math.max(0, unreadCount.value - 1)
  }

  if (notification.linkUrl) {
    window.location.href = notification.linkUrl
  }
}

async function handleMarkAllRead() {
  try {
    await markAllAsRead(props.userId)
    unreadCount.value = 0
    notifications.value.forEach(n => n.isRead = 1)
    ElMessage.success('已全部标为已读')
  } catch (error) {
    ElMessage.error('操作失败')
  }
}

async function handleDelete(id: number) {
  await ElMessageBox.confirm('确认删除该通知吗？', '删除确认', { type: 'warning' })
  try {
    await deleteNotification(id)
    notifications.value = notifications.value.filter(n => n.id !== id)
    ElMessage.success('删除成功')
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('删除失败')
    }
  }
}

async function handlePushChange(type: string, enabled: boolean) {
  try {
    await updateSetting(props.userId, type, enabled, undefined)
    if (settings.value[type]) {
      settings.value[type].pushEnabled = enabled ? 1 : 0
    } else {
      settings.value[type] = { id: 0, userId: props.userId, notificationType: type, pushEnabled: enabled ? 1 : 0, emailEnabled: 0 }
    }
    ElMessage.success('设置已更新')
  } catch (error) {
    ElMessage.error('设置失败')
  }
}

onMounted(() => {
  loadNotifications()
  loadUnreadCount()
  loadSettings()
})
</script>

<style lang="scss" scoped>
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.notification-list {
  min-height: 400px;
}

.empty-state {
  text-align: center;
  padding: 60px 0;
  color: #999;

  .empty-icon {
    font-size: 48px;
    display: block;
    margin-bottom: 16px;
  }
}

.notification-item {
  display: flex;
  align-items: flex-start;
  padding: 16px;
  border-bottom: 1px solid #eee;
  cursor: pointer;
  transition: background 0.2s;

  &:hover {
    background: #f8f8ff;
  }

  &.unread {
    background: #f0f7ff;

    .notification-title {
      font-weight: 600;
    }
  }

  &:last-child {
    border-bottom: none;
  }
}

.notification-icon {
  font-size: 24px;
  margin-right: 12px;
  flex-shrink: 0;
}

.notification-content {
  flex: 1;
  min-width: 0;
}

.notification-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.notification-title {
  font-size: 14px;
  color: #333;
}

.notification-time {
  font-size: 12px;
  color: #999;
  flex-shrink: 0;
}

.notification-body {
  font-size: 13px;
  color: #666;
  line-height: 1.5;
  margin-bottom: 8px;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.notification-meta {
  display: flex;
  gap: 8px;
  align-items: center;
}

.notification-actions {
  flex-shrink: 0;
  margin-left: 12px;
}

.pagination-wrapper {
  margin-top: 20px;
  display: flex;
  justify-content: center;
}

.settings-list {
  .setting-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #eee;

    &:last-child {
      border-bottom: none;
    }
  }

  .setting-info {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .setting-icon {
    font-size: 18px;
  }

  .setting-name {
    font-size: 14px;
    color: #333;
  }
}

.tips-content {
  font-size: 13px;
  color: #666;
  line-height: 1.8;

  ul {
    padding-left: 20px;
    margin: 0;
  }

  li {
    margin-bottom: 4px;
  }
}
</style>
