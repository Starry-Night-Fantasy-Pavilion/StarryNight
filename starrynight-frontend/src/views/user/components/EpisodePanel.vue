<template>
  <div class="episode-panel">
    <div class="panel-header">
      <el-button type="primary" @click="showCreateDialog = true">
        <el-icon><Plus /></el-icon>
        新建单元剧
      </el-button>
      <el-button @click="generateOutline">
        <el-icon><MagicStick /></el-icon>
        AI生成大纲
      </el-button>
    </div>

    <div class="episode-timeline">
      <el-timeline>
        <el-timeline-item
          v-for="episode in episodes"
          :key="episode.id"
          :timestamp="`第${episode.episodeNo}集`"
          :type="getThreatType(episode.monsterEvent.episodeThreat)"
          placement="top"
        >
          <el-card class="episode-card">
            <template #header>
              <div class="card-header">
                <span>{{ episode.title || `第${episode.episodeNo}集` }}</span>
                <div class="card-actions">
                  <el-button size="small" text @click="editEpisode(episode)">
                    <el-icon><Edit /></el-icon>
                  </el-button>
                  <el-button size="small" text type="danger" @click="deleteEpisode(episode.id)">
                    <el-icon><Delete /></el-icon>
                  </el-button>
                </div>
              </div>
            </template>

            <div class="episode-content">
              <div class="track-section">
                <h4>🟥 主线剧情</h4>
                <div class="track-item">
                  <span class="track-label">怪物:</span>
                  <el-tag size="small">{{ episode.monsterEvent.mainMonster }}</el-tag>
                  <el-tag v-if="episode.monsterEvent.minions?.length" size="small" type="info">
                    +{{ episode.monsterEvent.minions.length }}小怪
                  </el-tag>
                  <el-tag size="small" :type="getThreatType(episode.monsterEvent.episodeThreat)">
                    {{ episode.monsterEvent.episodeThreat }}
                  </el-tag>
                </div>
                <div class="track-item">
                  <span class="track-label">战斗地点:</span>
                  <span>{{ episode.battleLocation }}</span>
                </div>
              </div>

              <div class="track-section">
                <h4>🟦 单元剧情</h4>
                <div class="track-item">
                  <span class="track-label">受害者:</span>
                  <el-tag size="small" type="warning">{{ episode.victimEvent.type }}</el-tag>
                  <span>{{ episode.victimEvent.description }}</span>
                </div>
                <div class="track-item">
                  <span class="track-label">收获:</span>
                  <span v-if="episode.gains.newForm">新形态: {{ episode.gains.newForm }}</span>
                  <span v-if="episode.gains.newDevice">新道具: {{ episode.gains.newDevice }}</span>
                  <span v-if="episode.gains.plotAdvance">剧情推进: {{ episode.gains.plotAdvance }}</span>
                </div>
              </div>

              <div v-if="episode.mainPlotConnection" class="plot-connection">
                <el-tag type="success" size="small">
                  📌 主线伏笔: {{ episode.mainPlotConnection.foreshadowingId }}
                  (+{{ episode.mainPlotConnection.advanceAmount }})
                </el-tag>
              </div>

              <div v-if="episode.summary" class="episode-summary">
                <p>{{ episode.summary }}</p>
              </div>
            </div>
          </el-card>
        </el-timeline-item>
      </el-timeline>

      <el-empty v-if="episodes.length === 0" description="暂无单元剧数据" />
    </div>

    <el-dialog v-model="showCreateDialog" title="创建单元剧" width="700px">
      <el-form :model="episodeForm" label-width="100px">
        <el-form-item label="集数" required>
          <el-input-number v-model="episodeForm.episodeNo" :min="1" />
        </el-form-item>
        <el-form-item label="标题">
          <el-input v-model="episodeForm.title" placeholder="如: 火焰中的邂逅" />
        </el-form-item>
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="主要怪物">
              <el-input v-model="episodeForm.monsterEvent.mainMonster" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="威胁等级">
              <el-select v-model="episodeForm.monsterEvent.episodeThreat" style="width: 100%">
                <el-option value="low" label="低" />
                <el-option value="medium" label="中" />
                <el-option value="high" label="高" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="小怪">
          <el-select v-model="episodeForm.monsterEvent.minions" multiple placeholder="选择小怪" style="width: 100%">
            <el-option label="杂兵A" value="minion_a" />
            <el-option label="杂兵B" value="minion_b" />
          </el-select>
        </el-form-item>
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="受害者类型">
              <el-select v-model="episodeForm.victimEvent.type" style="width: 100%">
                <el-option value="civilian" label="平民" />
                <el-option value="ally" label="同伴" />
                <el-option value="self" label="自身" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="战斗地点">
              <el-input v-model="episodeForm.battleLocation" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="受害者描述">
          <el-input v-model="episodeForm.victimEvent.description" />
        </el-form-item>
        <el-form-item label="新形态">
          <el-input v-model="episodeForm.gains.newForm" placeholder="获得的新形态" />
        </el-form-item>
        <el-form-item label="新道具">
          <el-input v-model="episodeForm.gains.newDevice" placeholder="获得的新道具" />
        </el-form-item>
        <el-form-item label="剧情推进">
          <el-input v-model="episodeForm.gains.plotAdvance" placeholder="主线剧情推进描述" />
        </el-form-item>
        <el-form-item label="剧情摘要">
          <el-input v-model="episodeForm.summary" type="textarea" :rows="3" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showCreateDialog = false">取消</el-button>
        <el-button type="primary" @click="submitEpisode">创建</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Plus, Edit, Delete, MagicStick } from '@element-plus/icons-vue'
import {
  getEpisodeCards,
  createEpisodeCard,
  updateEpisodeCard,
  deleteEpisodeCard,
  type EpisodeCard
} from '@/api/tokusatsu'

interface Props {
  novelId: number
}

const props = defineProps<Props>()

const episodes = ref<EpisodeCard[]>([])
const showCreateDialog = ref(false)
const editingEpisodeId = ref<number>()

const episodeForm = reactive<{
  episodeNo: number
  title: string
  monsterEvent: {
    mainMonster: string
    minions: string[]
    episodeThreat: 'low' | 'medium' | 'high'
  }
  victimEvent: {
    type: 'civilian' | 'ally' | 'self'
    description: string
  }
  gains: {
    newForm?: string
    newDevice?: string
    plotAdvance?: string
  }
  battleLocation: string
  summary?: string
}>({
  episodeNo: 1,
  title: '',
  monsterEvent: {
    mainMonster: '',
    minions: [],
    episodeThreat: 'medium'
  },
  victimEvent: {
    type: 'civilian',
    description: ''
  },
  gains: {},
  battleLocation: '',
  summary: ''
})

function getThreatType(threat: string) {
  const types: Record<string, string> = {
    low: 'success',
    medium: 'warning',
    high: 'danger'
  }
  return types[threat] || 'info'
}

async function loadEpisodes() {
  try {
    const res = await getEpisodeCards(props.novelId)
    episodes.value = res.data || []
  } catch (e) {
    episodes.value = generateMockEpisodes()
  }
}

function generateMockEpisodes(): EpisodeCard[] {
  return [
    {
      id: 1,
      novelId: props.novelId,
      episodeNo: 1,
      title: '火焰中的邂逅',
      monsterEvent: {
        mainMonster: '熔岩巨人',
        minions: ['火焰精灵'],
        episodeThreat: 'high'
      },
      victimEvent: {
        type: 'civilian',
        description: '小镇居民被困在着火的大楼中'
      },
      gains: {
        newForm: '烈焰形态'
      },
      battleLocation: '城市商业区',
      summary: '主角首次变身，击退熔岩巨人'
    },
    {
      id: 2,
      novelId: props.novelId,
      episodeNo: 2,
      title: '暗影来袭',
      monsterEvent: {
        mainMonster: '暗影兽',
        episodeThreat: 'medium'
      },
      victimEvent: {
        type: 'ally',
        description: '女主角被暗影兽抓走'
      },
      gains: {
        plotAdvance: '揭示反派组织的存在'
      },
      battleLocation: '废弃工厂',
      summary: '主角深入敌巢，救回女主角'
    }
  ]
}

function editEpisode(episode: EpisodeCard) {
  editingEpisodeId.value = episode.id
  Object.assign(episodeForm, {
    episodeNo: episode.episodeNo,
    title: episode.title || '',
    monsterEvent: { ...episode.monsterEvent },
    victimEvent: { ...episode.victimEvent },
    gains: { ...episode.gains },
    battleLocation: episode.battleLocation,
    summary: episode.summary || ''
  })
  showCreateDialog.value = true
}

async function submitEpisode() {
  try {
    if (editingEpisodeId.value) {
      await updateEpisodeCard(editingEpisodeId.value, episodeForm as any)
      ElMessage.success('更新成功')
    } else {
      await createEpisodeCard(props.novelId, episodeForm as any)
      ElMessage.success('创建成功')
    }
    showCreateDialog.value = false
    editingEpisodeId.value = undefined
    await loadEpisodes()
  } catch (e) {
    ElMessage.error('操作失败')
  }
}

async function deleteEpisode(id: number) {
  try {
    await deleteEpisodeCard(id)
    ElMessage.success('删除成功')
    await loadEpisodes()
  } catch (e) {
    ElMessage.error('删除失败')
  }
}

function generateOutline() {
  ElMessage.success('AI正在生成双轨大纲...')
}

onMounted(() => {
  loadEpisodes()
})
</script>

<style lang="scss" scoped>
.episode-panel {
  .panel-header {
    display: flex;
    gap: 12px;
    margin-bottom: 16px;
  }

  .episode-timeline {
    .episode-card {
      .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
      }

      .card-actions {
        display: flex;
        gap: 4px;
      }

      .episode-content {
        .track-section {
          margin-bottom: 12px;

          h4 {
            margin: 0 0 8px 0;
            font-size: 14px;
          }

          .track-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
            font-size: 13px;

            .track-label {
              color: var(--el-text-color-secondary);
              min-width: 60px;
            }
          }
        }

        .plot-connection {
          margin-top: 8px;
        }

        .episode-summary {
          margin-top: 8px;
          padding: 8px;
          background: var(--el-fill-color-light);
          border-radius: 4px;
          font-size: 13px;
          color: var(--el-text-color-secondary);
        }
      }
    }
  }
}
</style>
