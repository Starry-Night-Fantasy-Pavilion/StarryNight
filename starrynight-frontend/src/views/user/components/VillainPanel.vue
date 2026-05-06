<template>
  <div class="villain-panel">
    <div class="panel-header">
      <el-button type="primary" @click="showCreateDialog = true">
        <el-icon><Plus /></el-icon>
        新建敌役模板
      </el-button>
      <el-button @click="showTemplateDialog = true">
        <el-icon><DocumentCopy /></el-icon>
        从模板创建
      </el-button>
    </div>

    <el-row :gutter="16">
      <el-col :span="12">
        <el-card>
          <template #header>
            <span>敌役列表</span>
          </template>
          <div class="villain-list">
            <div
              v-for="villain in villains"
              :key="villain.id"
              class="villain-item"
              :class="{ active: selectedVillain?.id === villain.id }"
              @click="selectVillain(villain)"
            >
              <span class="villain-icon">{{ getCategoryIcon(villain.category) }}</span>
              <div class="villain-info">
                <span class="villain-name">{{ villain.name }}</span>
                <span class="villain-org" v-if="villain.organization">{{ villain.organization.name }}</span>
              </div>
              <div class="villain-power">
                <span class="power-label">战力:</span>
                <el-progress :percentage="villain.abilities.combatPower" :stroke-width="6" />
              </div>
            </div>
            <div v-if="villains.length === 0" class="empty-list">
              <p>暂无敌役模板</p>
            </div>
          </div>
        </el-card>
      </el-col>

      <el-col :span="12">
        <el-card v-if="selectedVillain">
          <template #header>
            <div class="card-header">
              <span>{{ selectedVillain.name }}</span>
              <div class="card-actions">
                <el-button size="small" @click="editVillain">编辑</el-button>
                <el-button size="small" type="danger" @click="deleteVillain(selectedVillain.id)">删除</el-button>
              </div>
            </div>
          </template>

          <el-descriptions :column="2" border size="small">
            <el-descriptions-item label="名称">{{ selectedVillain.name }}</el-descriptions-item>
            <el-descriptions-item label="分类">
              <el-tag size="small">{{ selectedVillain.category }}</el-tag>
            </el-descriptions-item>
            <el-descriptions-item label="所属组织" :span="2">
              {{ selectedVillain.organization?.name || '无' }}
            </el-descriptions-item>
            <el-descriptions-item label="战力">
              <el-rate v-model="selectedVillain.abilities.combatPower" disabled :max="100" />
            </el-descriptions-item>
          </el-descriptions>

          <div class="section">
            <h4>🗡️ 特殊技能</h4>
            <div class="abilities-list">
              <el-tag
                v-for="skill in selectedVillain.abilities.specialAttacks"
                :key="skill"
                size="small"
                type="danger"
              >
                {{ skill }}
              </el-tag>
            </div>
          </div>

          <div class="section">
            <h4>💀 弱点</h4>
            <div class="abilities-list">
              <el-tag
                v-for="weakness in selectedVillain.abilities.weaknesses"
                :key="weakness"
                size="small"
                type="warning"
              >
                {{ weakness }}
              </el-tag>
            </div>
          </div>

          <div class="section">
            <h4>⚔️ 克制关系</h4>
            <el-descriptions :column="1" border size="small">
              <el-descriptions-item
                v-for="(rel, target) in selectedVillain.rivalries"
                :key="target"
                :label="target"
              >
                <el-tag size="small" :type="getRivalryType(rel.type)">{{ rel.type }}</el-tag>
                <span v-if="rel.specificForm" class="form-hint">{{ rel.specificForm }}</span>
              </el-descriptions-item>
            </el-descriptions>
          </div>

          <div class="section">
            <h4>📋 状态历史</h4>
            <el-timeline size="small">
              <el-timeline-item
                v-for="(status, idx) in selectedVillain.statusHistory"
                :key="idx"
                :type="getStatusType(status.status)"
              >
                <div class="status-item">
                  <span class="status-name">{{ status.status }}</span>
                  <span v-if="status.deathChapter" class="death-chapter">第{{ status.deathChapter }}章死亡</span>
                  <span v-if="status.revivalCondition" class="revival-condition">
                    复活条件: {{ status.revivalCondition }}
                  </span>
                </div>
              </el-timeline-item>
            </el-timeline>
          </div>
        </el-card>
        <el-card v-else>
          <el-empty description="请选择要查看的敌役" />
        </el-card>
      </el-col>
    </el-row>

    <el-dialog v-model="showCreateDialog" title="创建敌役模板" width="600px">
      <el-form :model="villainForm" label-width="100px">
        <el-form-item label="名称" required>
          <el-input v-model="villainForm.name" placeholder="如: 暗影将军" />
        </el-form-item>
        <el-form-item label="分类">
          <el-select v-model="villainForm.category" style="width: 100%">
            <el-option value="monster" label="怪物" />
            <el-option value="rider" label="假面骑士" />
            <el-option value="kaijin" label="怪人" />
            <el-option value="ultraman" label="奥特曼" />
            <el-option value="boss" label="BOSS" />
          </el-select>
        </el-form-item>
        <el-form-item label="组织">
          <el-input v-model="villainForm.organization.name" placeholder="所属组织" />
        </el-form-item>
        <el-form-item label="战力">
          <el-slider v-model="villainForm.abilities.combatPower" :min="0" :max="100" show-input />
        </el-form-item>
        <el-form-item label="特殊技能">
          <el-select v-model="villainForm.abilities.specialAttacks" multiple placeholder="选择技能" style="width: 100%">
            <el-option label="暗影切割" value="shadow_slash" />
            <el-option label="火焰吐息" value="flame_breath" />
            <el-option label="瞬间移动" value="teleport" />
            <el-option label="复活" value="revival" />
            <el-option label="分身" value="clone" />
          </el-select>
        </el-form-item>
        <el-form-item label="弱点">
          <el-select v-model="villainForm.abilities.weaknesses" multiple placeholder="选择弱点" style="width: 100%">
            <el-option label="怕光" value="light" />
            <el-option label="怕水" value="water" />
            <el-option label="心脏" value="heart" />
            <el-option label="封印" value="seal" />
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showCreateDialog = false">取消</el-button>
        <el-button type="primary" @click="submitVillain">创建</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="showTemplateDialog" title="从模板创建" width="400px">
      <div class="template-list">
        <div
          v-for="tmpl in templates"
          :key="tmpl.id"
          class="template-item"
          @click="createFromTemplate(tmpl)"
        >
          <span class="template-icon">{{ getCategoryIcon(tmpl.category) }}</span>
          <span class="template-name">{{ tmpl.name }}</span>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Plus, DocumentCopy } from '@element-plus/icons-vue'
import {
  getVillainTemplates,
  createVillainTemplate,
  updateVillainTemplate,
  deleteVillainTemplate,
  type VillainTemplate
} from '@/api/tokusatsu'

interface Props {
  novelId: number
}

const props = defineProps<Props>()

const villains = ref<VillainTemplate[]>([])
const selectedVillain = ref<VillainTemplate | null>(null)
const showCreateDialog = ref(false)
const showTemplateDialog = ref(false)

const templates = ref<VillainTemplate[]>([
  {
    id: 101,
    name: '标准怪物模板',
    category: 'monster',
    abilities: {
      combatPower: 50,
      specialAttacks: ['普通攻击', '怒吼'],
      weaknesses: ['怕光']
    },
    statusHistory: [{ status: 'alive' }],
    rivalries: {}
  },
  {
    id: 102,
    name: '干部模板',
    category: 'boss',
    abilities: {
      combatPower: 80,
      specialAttacks: ['暗影切割', '瞬间移动'],
      weaknesses: ['心脏']
    },
    statusHistory: [{ status: 'alive' }],
    rivalries: {}
  }
])

const villainForm = reactive<{
  name: string
  category: 'monster' | 'rider' | 'kaijin' | 'ultraman' | 'boss'
  organization: { id: string; name: string }
  abilities: {
    combatPower: number
    specialAttacks: string[]
    weaknesses: string[]
  }
}>({
  name: '',
  category: 'monster',
  organization: { id: '', name: '' },
  abilities: {
    combatPower: 50,
    specialAttacks: [],
    weaknesses: []
  }
})

function getCategoryIcon(category: string) {
  const icons: Record<string, string> = {
    monster: '👹',
    rider: '🤖',
    kaijin: '👤',
    ultraman: '🛸',
    boss: '👑'
  }
  return icons[category] || '👹'
}

function getRivalryType(type: string) {
  const types: Record<string, string> = {
    weak_to: 'success',
    strong_to: 'danger',
    equal: 'warning'
  }
  return types[type] || ''
}

function getStatusType(status: string) {
  const types: Record<string, string> = {
    alive: 'success',
    dead: 'danger',
    revived: 'warning',
    cloned: 'info'
  }
  return types[status] || ''
}

async function loadVillains() {
  try {
    const res = await getVillainTemplates(props.novelId)
    villains.value = res.data || []
  } catch (e) {
    villains.value = generateMockVillains()
  }
}

function generateMockVillains(): VillainTemplate[] {
  return [
    {
      id: 1,
      name: '暗影将军',
      category: 'boss',
      organization: { id: '1', name: '暗影帝国' },
      abilities: {
        combatPower: 95,
        specialAttacks: ['暗影剑气', '黑暗领域', '影分身'],
        weaknesses: ['心脏', '光属性']
      },
      statusHistory: [
        { status: 'alive' },
        { status: 'dead', deathChapter: 50 },
        { status: 'revived', revivalCondition: '收集七颗暗影宝石' }
      ],
      rivalries: {
        '烈焰骑士': { type: 'weak_to', specificForm: '烈焰形态' },
        '雷电使者': { type: 'strong_to' }
      }
    },
    {
      id: 2,
      name: '熔岩巨兽',
      category: 'monster',
      abilities: {
        combatPower: 70,
        specialAttacks: ['熔岩吐息', '地震'],
        weaknesses: ['水属性', '冰属性']
      },
      statusHistory: [{ status: 'alive' }],
      rivalries: {}
    }
  ]
}

function selectVillain(villain: VillainTemplate) {
  selectedVillain.value = villain
}

function editVillain() {
  ElMessage.info('编辑功能开发中')
}

async function submitVillain() {
  if (!villainForm.name) {
    ElMessage.warning('请填写名称')
    return
  }
  try {
    await createVillainTemplate(props.novelId, {
      ...villainForm,
      statusHistory: [{ status: 'alive' }],
      rivalries: {}
    } as any)
    ElMessage.success('创建成功')
    showCreateDialog.value = false
    await loadVillains()
  } catch (e) {
    ElMessage.error('创建失败')
  }
}

async function deleteVillain(id: number) {
  try {
    await deleteVillainTemplate(id)
    ElMessage.success('删除成功')
    selectedVillain.value = null
    await loadVillains()
  } catch (e) {
    ElMessage.error('删除失败')
  }
}

function createFromTemplate(tmpl: VillainTemplate) {
  villainForm.name = ''
  villainForm.category = tmpl.category as any
  villainForm.abilities = { ...tmpl.abilities }
  villainForm.organization = { ...tmpl.organization }
  showTemplateDialog.value = false
  showCreateDialog.value = true
}

onMounted(() => {
  loadVillains()
})
</script>

<style lang="scss" scoped>
.villain-panel {
  .panel-header {
    display: flex;
    gap: 12px;
    margin-bottom: 16px;
  }

  .villain-list {
    .villain-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px;
      cursor: pointer;
      border-radius: 6px;
      transition: all 0.2s;

      &:hover {
        background: var(--el-fill-color-light);
      }

      &.active {
        background: var(--el-color-primary-light-9);
        border-left: 3px solid var(--el-color-primary);
      }

      .villain-icon {
        font-size: 32px;
      }

      .villain-info {
        flex: 1;
        display: flex;
        flex-direction: column;

        .villain-name {
          font-weight: 600;
        }

        .villain-org {
          font-size: 12px;
          color: var(--el-text-color-secondary);
        }
      }

      .villain-power {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 4px;
        width: 80px;

        .power-label {
          font-size: 12px;
          color: var(--el-text-color-secondary);
        }
      }
    }

    .empty-list {
      text-align: center;
      padding: 20px;
      color: var(--el-text-color-secondary);
    }
  }

  .section {
    margin-top: 16px;

    h4 {
      margin: 0 0 8px 0;
      font-size: 14px;
    }

    .abilities-list {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
    }

    .status-item {
      display: flex;
      flex-direction: column;
      gap: 4px;

      .status-name {
        text-transform: capitalize;
      }

      .death-chapter,
      .revival-condition {
        font-size: 12px;
        color: var(--el-text-color-secondary);
      }
    }
  }

  .template-list {
    .template-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px;
      cursor: pointer;
      border-radius: 6px;
      transition: all 0.2s;

      &:hover {
        background: var(--el-fill-color-light);
      }

      .template-icon {
        font-size: 24px;
      }

      .template-name {
        font-weight: 500;
      }
    }
  }
}
</style>
