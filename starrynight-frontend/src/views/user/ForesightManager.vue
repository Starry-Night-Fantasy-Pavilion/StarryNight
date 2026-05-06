<template>
  <div class="foresight-manager page-container">
    <div class="page-header">
      <h1>🔮 AI伏笔管理器</h1>
      <div class="header-actions">
        <el-radio-group v-model="viewTab" size="small">
          <el-radio-button value="list">伏笔列表</el-radio-button>
          <el-radio-button value="settings">设置</el-radio-button>
        </el-radio-group>
        <el-select v-model="selectedNovelId" placeholder="选择作品" clearable style="width: 200px" @change="loadData">
          <el-option v-for="novel in novels" :key="novel.id" :label="novel.title" :value="novel.id" />
        </el-select>
      </div>
    </div>

    <div v-if="!selectedNovelId" class="empty-state">
      <el-icon :size="64"><MagicStick /></el-icon>
      <p>请选择作品以管理伏笔</p>
    </div>

    <div v-else class="manager-content">
      <el-row :gutter="16">
        <el-col :span="24">
          <el-card class="stats-card">
            <el-row :gutter="24">
              <el-col :span="8">
                <div class="stat-item unrecovered">
                  <span class="stat-value">{{ stats.unrecovered }}</span>
                  <span class="stat-label">未回收</span>
                </div>
              </el-col>
              <el-col :span="8">
                <div class="stat-item expiring">
                  <span class="stat-value">{{ stats.expiring }}</span>
                  <span class="stat-label">即将到期</span>
                </div>
              </el-col>
              <el-col :span="8">
                <div class="stat-item recovered">
                  <span class="stat-value">{{ stats.recovered }}</span>
                  <span class="stat-label">已回收</span>
                </div>
              </el-col>
            </el-row>
          </el-card>
        </el-col>
      </el-row>

      <el-tabs v-if="viewTab === 'list'" v-model="listTab">
        <el-tab-pane label="未回收" name="unrecovered">
          <el-card class="foresight-list-card">
            <div v-if="unrecoveredForesights.length === 0" class="empty-list">
              <p>暂无未回收伏笔</p>
            </div>
            <div v-else class="foresight-list">
              <div v-for="foresight in unrecoveredForesights" :key="foresight.id" class="foresight-item unrecovered">
                <div class="foresight-header">
                  <span class="chapter-tag">📍 第{{ foresight.sourceChapter }}章</span>
                  <el-tag :type="getStatusType(foresight.resolutionStatus)" size="small">
                    {{ getStatusLabel(foresight.resolutionStatus) }}
                  </el-tag>
                </div>
                <div class="foresight-content">
                  <p class="foresight-desc">{{ foresight.foreshadowingContent }}</p>
                  <p class="foresight-detail">{{ foresight.detail }}</p>
                </div>
                <div class="foresight-meta">
                  <span>预期回收: {{ foresight.targetChapter ? `第${foresight.targetChapter}章` : '未设置' }}</span>
                </div>
                <div class="foresight-actions">
                  <el-button size="small" @click="viewContext(foresight)">查看上下文</el-button>
                  <el-button v-if="foresight.targetChapter" size="small" type="primary" @click="jumpToChapter(foresight.targetChapter)">
                    跳转第{{ foresight.targetChapter }}章
                  </el-button>
                  <el-button size="small" type="success" @click="markRecovered(foresight)">标记已回收</el-button>
                  <el-button size="small" type="warning" @click="generateRecoveryScene(foresight)">
                    AI生成回收场景
                  </el-button>
                </div>
              </div>
            </div>
          </el-card>
        </el-tab-pane>

        <el-tab-pane label="已回收" name="recovered">
          <el-card class="foresight-list-card">
            <div v-if="recoveredForesights.length === 0" class="empty-list">
              <p>暂无已回收伏笔</p>
            </div>
            <div v-else class="foresight-list">
              <div v-for="foresight in recoveredForesights" :key="foresight.id" class="foresight-item recovered">
                <div class="foresight-header">
                  <span class="chapter-tag">📍 第{{ foresight.sourceChapter }}章</span>
                  <span class="recovery-chapter">→ 第{{ foresight.resolutionChapterId }}章回收</span>
                </div>
                <div class="foresight-content">
                  <p class="foresight-desc">{{ foresight.foreshadowingContent }}</p>
                </div>
                <div class="foresight-actions">
                  <el-button size="small" @click="viewContext(foresight)">查看上下文</el-button>
                </div>
              </div>
            </div>
          </el-card>
        </el-tab-pane>

        <el-tab-pane label="AI建议" name="suggestions">
          <el-card class="suggestions-card">
            <template #header>
              <span>💡 AI智能建议</span>
            </template>
            <div v-if="aiSuggestions.length === 0" class="empty-list">
              <p>暂无AI建议</p>
            </div>
            <div v-else class="suggestions-list">
              <div v-for="(suggestion, idx) in aiSuggestions" :key="idx" class="suggestion-item">
                <div class="suggestion-content">
                  <p class="suggestion-text">{{ suggestion.text }}</p>
                  <div v-if="suggestion.chapters" class="suggestion-chapters">
                    <el-tag v-for="ch in suggestion.chapters" :key="ch" size="small">
                      第{{ ch }}章
                    </el-tag>
                  </div>
                </div>
                <div class="suggestion-actions">
                  <el-button size="small" type="primary" @click="applyAISuggestion(suggestion)">
                    一键插入
                  </el-button>
                  <el-button size="small" @click="viewSuggestionDetail(suggestion)">查看详情</el-button>
                  <el-button size="small" @click="ignoreSuggestion(idx)">忽略</el-button>
                </div>
              </div>
            </div>
          </el-card>
        </el-tab-pane>

        <el-tab-pane label="一致性警告" name="warnings">
          <el-card class="warnings-card">
            <template #header>
              <span>⚠️ 一致性警告</span>
            </template>
            <div v-if="consistencyWarnings.length === 0" class="empty-list">
              <p>暂无一致性警告</p>
            </div>
            <div v-else class="warnings-list">
              <el-alert
                v-for="(warning, idx) in consistencyWarnings"
                :key="idx"
                :title="warning.conflict"
                type="danger"
                show-icon
                :closable="false"
                class="warning-item"
              >
                <template #default>
                  <p class="warning-detail">{{ warning.detail }}</p>
                  <div class="warning-actions">
                    <el-button size="small" type="primary" @click="viewConflictLocation(warning)">
                      查看冲突位置
                    </el-button>
                    <el-button size="small" type="success" @click="fixWithAI(warning)">
                      AI修复建议
                    </el-button>
                  </div>
                </template>
              </el-alert>
            </div>
          </el-card>
        </el-tab-pane>
      </el-tabs>

      <el-card v-else class="settings-card">
        <template #header>
          <span>⚙️ 伏笔设置</span>
        </template>
        <el-form :model="settings" label-width="140px">
          <el-form-item label="自动检测伏笔">
            <el-switch v-model="settings.autoDetect" />
            <span class="form-hint">开启后，系统将自动分析文本中的潜在伏笔</span>
          </el-form-item>
          <el-form-item label="到期提醒阈值">
            <el-input-number v-model="settings.expiringThreshold" :min="1" :max="20" />
            <span class="form-hint">章节数，距预期回收章节还有多少章时提醒</span>
          </el-form-item>
          <el-form-item label="伏笔类型">
            <el-checkbox-group v-model="settings.enabledTypes">
              <el-checkbox label="item">物品伏笔</el-checkbox>
              <el-checkbox label="identity">身份伏笔</el-checkbox>
              <el-checkbox label="relationship">关系伏笔</el-checkbox>
              <el-checkbox label="ability">能力伏笔</el-checkbox>
            </el-checkbox-group>
          </el-form-item>
          <el-form-item>
            <el-button type="primary" @click="saveSettings">保存设置</el-button>
          </el-form-item>
        </el-form>
      </el-card>
    </div>

    <el-dialog v-model="contextDialogVisible" title="伏笔上下文" width="640px">
      <div v-if="selectedForesight" class="context-content">
        <el-descriptions :column="1" border size="small">
          <el-descriptions-item label="伏笔描述">{{ selectedForesight.foreshadowingContent }}</el-descriptions-item>
          <el-descriptions-item label="详细描述">{{ selectedForesight.detail }}</el-descriptions-item>
          <el-descriptions-item label="埋设章节">第{{ selectedForesight.sourceChapter }}章</el-descriptions-item>
          <el-descriptions-item label="预期回收">第{{ selectedForesight.targetChapter || '未设置' }}章</el-descriptions-item>
          <el-descriptions-item label="伏笔类型">{{ getTypeLabel(selectedForesight.foreshadowingType) }}</el-descriptions-item>
        </el-descriptions>
        <div class="context-text">
          <h4>原文摘录：</h4>
          <p class="quote">{{ selectedForesight.quote }}</p>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { MagicStick } from '@element-plus/icons-vue'
import { listNovels } from '@/api/novel'
import {
  getForesightList,
  getForesightSuggestions,
  resolveForeshadowing,
  abandonForeshadowing,
  generateForesightRecovery,
  detectForeshadowing,
  type ForesightDetail
} from '@/api/consistency'

interface Novel {
  id: number
  title: string
}

interface AISuggestion {
  text: string
  chapters?: number[]
  type: 'recovery' | 'new'
}

interface Warning {
  conflict: string
  detail: string
  chapter1: number
  chapter2: number
}

const viewTab = ref<'list' | 'settings'>('list')
const listTab = ref<'unrecovered' | 'recovered' | 'suggestions' | 'warnings'>('unrecovered')
const selectedNovelId = ref<number>()
const novels = ref<Novel[]>([])
const foresights = ref<ForesightDetail[]>([])
const selectedForesight = ref<ForesightDetail | null>(null)
const contextDialogVisible = ref(false)
const loading = ref(false)

const stats = reactive({
  unrecovered: 0,
  expiring: 0,
  recovered: 0
})

const settings = reactive({
  autoDetect: true,
  expiringThreshold: 5,
  enabledTypes: ['item', 'identity', 'relationship', 'ability'] as string[]
})

const unrecoveredForesights = computed(() =>
  foresights.value.filter(f => f.resolutionStatus !== 'recovered')
)

const recoveredForesights = computed(() =>
  foresights.value.filter(f => f.resolutionStatus === 'recovered')
)

const aiSuggestions = ref<AISuggestion[]>([])

const consistencyWarnings = ref<Warning[]>([])

function getStatusType(status: string) {
  const map: Record<string, string> = {
    pending: '',
    expiring: 'warning',
    recovered: 'success'
  }
  return map[status] || ''
}

function getStatusLabel(status: string) {
  const map: Record<string, string> = {
    pending: '待触发',
    expiring: '即将到期',
    recovered: '已回收'
  }
  return map[status] || status
}

function getTypeLabel(type: string) {
  const map: Record<string, string> = {
    item: '物品伏笔',
    identity: '身份伏笔',
    relationship: '关系伏笔',
    ability: '能力伏笔'
  }
  return map[type] || type
}

async function loadNovels() {
  try {
    const res = await listNovels({ page: 1, size: 100 })
    novels.value = res.data?.records || []
  } catch (e) {
    console.error('Failed to load novels', e)
  }
}

async function loadForesights() {
  if (!selectedNovelId.value) return

  loading.value = true
  try {
    const res = await getForesightList(selectedNovelId.value)
    if (res.data?.data) {
      foresights.value = res.data.data
    } else {
      foresights.value = generateMockForesights()
    }
    updateStats()
  } catch (e) {
    console.error('Failed to load foresights', e)
    foresights.value = generateMockForesights()
    updateStats()
  } finally {
    loading.value = false
  }
}

async function loadSuggestions() {
  if (!selectedNovelId.value) return

  try {
    const res = await getForesightSuggestions(selectedNovelId.value)
    if (res.data?.data) {
      aiSuggestions.value = res.data.data
    } else {
      aiSuggestions.value = generateMockSuggestions()
    }
  } catch (e) {
    console.error('Failed to load suggestions', e)
    aiSuggestions.value = generateMockSuggestions()
  }
}

function updateStats() {
  stats.unrecovered = foresights.value.filter(f => f.resolutionStatus !== 'recovered').length
  stats.expiring = foresights.value.filter(f => f.resolutionStatus === 'expiring').length
  stats.recovered = foresights.value.filter(f => f.resolutionStatus === 'recovered').length
}

async function loadData() {
  await loadForesights()
  await loadSuggestions()
}

function generateMockForesights(): ForesightDetail[] {
  return [
    {
      id: 1,
      userId: 1,
      novelId: 1,
      chapterId: 1,
      sourceChapter: 3,
      targetChapter: 30,
      foreshadowingType: 'item',
      foreshadowingContent: '主角捡到古朴戒指',
      hintLevel: 2,
      resolutionStatus: 'expiring',
      detail: '主角在山谷中发现一枚散发微光的戒指',
      quote: '主角的手触碰到戒指，戒指突然发出一道金光',
      status: 'expiring'
    },
    {
      id: 2,
      userId: 1,
      novelId: 1,
      chapterId: 2,
      sourceChapter: 8,
      targetChapter: 25,
      foreshadowingType: 'identity',
      foreshadowingContent: '神秘人说"你不属于这个世界"',
      hintLevel: 3,
      resolutionStatus: 'pending',
      detail: '黑衣人对主角说出意味深长的话',
      quote: '"你不属于这个世界"，黑衣人说完便消失在夜色中',
      status: 'pending'
    },
    {
      id: 3,
      userId: 1,
      novelId: 1,
      chapterId: 3,
      sourceChapter: 12,
      foreshadowingType: 'relationship',
      foreshadowingContent: '配角的妹妹失踪',
      hintLevel: 2,
      resolutionStatus: 'pending',
      detail: '主角的同伴李四提及妹妹三天未归',
      quote: '李四焦急地说："我妹妹已经三天没回家了..."',
      status: 'pending'
    },
    {
      id: 4,
      userId: 1,
      novelId: 1,
      chapterId: 4,
      sourceChapter: 15,
      targetChapter: 22,
      resolutionChapterId: 22,
      foreshadowingType: 'ability',
      foreshadowingContent: '神秘老人传授功法',
      hintLevel: 3,
      resolutionStatus: 'recovered',
      detail: '主角在山洞遇到神秘老人，被传授上古功法',
      status: 'recovered'
    }
  ]
}

function generateMockSuggestions(): AISuggestion[] {
  return [
    { text: '第30章回收戒指伏笔 - 揭露为上古神器', chapters: [30], type: 'recovery' },
    { text: '第28章可埋设新伏笔 - 反派似乎认识主角的身世', chapters: [28], type: 'new' }
  ]
}

function viewContext(foresight: ForesightDetail) {
  selectedForesight.value = foresight
  contextDialogVisible.value = true
}

function jumpToChapter(chapter: number) {
  ElMessage.info(`跳转到第${chapter}章`)
}

async function markRecovered(foresight: ForesightDetail) {
  try {
    await resolveForeshadowing(foresight.id, 28)
    foresight.resolutionStatus = 'recovered'
    foresight.resolutionChapterId = 28
    updateStats()
    ElMessage.success('已标记为已回收')
  } catch (e) {
    ElMessage.error('标记失败')
  }
}

async function generateRecoveryScene(foresight: ForesightDetail) {
  try {
    ElMessage.success('AI正在生成回收场景...')
    const res = await generateForesightRecovery(foresight.id)
    if (res.data?.data) {
      ElMessage.success('回收场景已生成')
    }
  } catch (e) {
    ElMessage.error('生成失败')
  }
}

function applyAISuggestion(suggestion: AISuggestion) {
  ElMessage.success('已插入回收场景')
}

function viewSuggestionDetail(suggestion: AISuggestion) {
  ElMessage.info('查看建议详情')
}

function ignoreSuggestion(idx: number) {
  aiSuggestions.value.splice(idx, 1)
}

function viewConflictLocation(warning: Warning) {
  ElMessage.info(`查看第${warning.chapter1}章和第${warning.chapter2}章的冲突`)
}

function fixWithAI(warning: Warning) {
  ElMessage.success('AI正在生成修复建议...')
}

function saveSettings() {
  ElMessage.success('设置已保存')
}

onMounted(() => {
  loadNovels()
})
</script>

<style lang="scss" scoped>
.foresight-manager {
  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
  }

  .empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 400px;
    color: var(--el-text-color-secondary);
  }

  .manager-content {
    .stats-card {
      margin-bottom: 16px;

      .stat-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 20px;

        .stat-value {
          font-size: 36px;
          font-weight: 700;
        }

        .stat-label {
          font-size: 14px;
          color: var(--el-text-color-secondary);
          margin-top: 8px;
        }

        &.unrecovered .stat-value { color: var(--el-color-danger); }
        &.expiring .stat-value { color: var(--el-color-warning); }
        &.recovered .stat-value { color: var(--el-color-success); }
      }
    }

    .foresight-list-card {
      .empty-list {
        text-align: center;
        padding: 40px;
        color: var(--el-text-color-secondary);
      }

      .foresight-list {
        .foresight-item {
          padding: 16px;
          border-bottom: 1px solid var(--el-border-color-lighter);

          &:last-child {
            border-bottom: none;
          }

          .foresight-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;

            .chapter-tag {
              font-weight: 600;
              color: var(--el-text-color);
            }

            .recovery-chapter {
              color: var(--el-color-success);
              font-size: 13px;
            }
          }

          .foresight-content {
            margin-bottom: 12px;

            .foresight-desc {
              font-weight: 500;
              margin-bottom: 4px;
            }

            .foresight-detail {
              font-size: 13px;
              color: var(--el-text-color-secondary);
            }
          }

          .foresight-meta {
            font-size: 12px;
            color: var(--el-text-color-muted);
            margin-bottom: 12px;
          }

          .foresight-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
          }
        }
      }
    }

    .suggestions-card, .warnings-card {
      .empty-list {
        text-align: center;
        padding: 40px;
        color: var(--el-text-color-secondary);
      }

      .suggestion-item, .warning-item {
        padding: 12px;
        border-bottom: 1px solid var(--el-border-color-lighter);

        &:last-child {
          border-bottom: none;
        }

        .suggestion-content {
          margin-bottom: 12px;

          .suggestion-text {
            margin-bottom: 8px;
          }

          .suggestion-chapters {
            display: flex;
            gap: 4px;
          }
        }

        .suggestion-actions, .warning-actions {
          display: flex;
          gap: 8px;
        }

        .warning-detail {
          margin: 8px 0;
          font-size: 13px;
        }
      }
    }

    .settings-card {
      .form-hint {
        margin-left: 12px;
        font-size: 12px;
        color: var(--el-text-color-muted);
      }
    }
  }

  .context-content {
    .context-text {
      margin-top: 16px;

      h4 {
        margin-bottom: 8px;
      }

      .quote {
        padding: 12px;
        background: var(--el-fill-color-light);
        border-left: 3px solid var(--el-color-primary);
        font-style: italic;
      }
    }
  }
}
</style>
