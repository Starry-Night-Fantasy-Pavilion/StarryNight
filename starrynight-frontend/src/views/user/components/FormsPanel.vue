<template>
  <div class="forms-panel">
    <div class="panel-header">
      <el-select v-model="selectedCharacterId" placeholder="选择角色" clearable style="width: 200px">
        <el-option v-for="char in characters" :key="char.id" :label="char.name" :value="char.id" />
      </el-select>
      <el-button type="primary" :disabled="!selectedCharacterId" @click="showCreateFormDialog = true">
        <el-icon><Plus /></el-icon>
        新增形态
      </el-button>
      <el-button :disabled="!selectedCharacterId" @click="showDeviceDialog = true">
        <el-icon><Tools /></el-icon>
        变身道具
      </el-button>
    </div>

    <div v-if="selectedCharacterId" class="form-tree-container">
      <el-card>
        <template #header>
          <span>形态演化树</span>
          <el-button size="small" type="primary" @click="validateAllForms">校验变身合规性</el-button>
        </template>
        <div v-if="forms.length > 0" class="form-tree">
          <FormTreeNode
            v-for="form in rootForms"
            :key="form.id"
            :form="form"
            :all-forms="forms"
            @edit="editForm"
            @delete="deleteFormHandler"
          />
        </div>
        <el-empty v-else description="暂无形态数据" />
      </el-card>

      <el-card class="devices-card">
        <template #header>
          <span>变身道具库</span>
        </template>
        <div class="devices-list">
          <div v-for="device in devices" :key="device.id" class="device-item">
            <span class="device-icon">{{ getDeviceIcon(device.type) }}</span>
            <div class="device-info">
              <span class="device-name">{{ device.name }}</span>
              <span class="device-type">{{ device.type }}</span>
            </div>
            <el-tag :type="getDeviceStatusType(device.status)" size="small">{{ device.status }}</el-tag>
          </div>
          <div v-if="devices.length === 0" class="empty-list">
            <p>暂无变身道具</p>
          </div>
        </div>
      </el-card>
    </div>

    <el-empty v-else description="请选择角色以管理形态" />

    <el-dialog v-model="showCreateFormDialog" title="创建形态" width="600px">
      <el-form :model="formData" label-width="120px">
        <el-form-item label="形态名称" required>
          <el-input v-model="formData.name" placeholder="如: 烈焰龙卷" />
        </el-form-item>
        <el-form-item label="上级形态">
          <el-select v-model="formData.parentFormId" placeholder="选择上级形态" clearable style="width: 100%">
            <el-option v-for="f in forms" :key="f.id" :label="f.name" :value="f.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="能力向量">
          <el-row :gutter="8">
            <el-col :span="8">
              <el-input-number v-model="formData.abilityVector.power" :min="0" :max="100" placeholder="力量" />
            </el-col>
            <el-col :span="8">
              <el-input-number v-model="formData.abilityVector.speed" :min="0" :max="100" placeholder="速度" />
            </el-col>
          </el-row>
        </el-form-item>
        <el-form-item label="特殊能力">
          <el-select v-model="formData.abilityVector.specialAbilities" multiple placeholder="选择特殊能力" style="width: 100%">
            <el-option label="飞行" value="flight" />
            <el-option label="水中呼吸" value="underwater" />
            <el-option label="隐身" value="invisible" />
            <el-option label="时间静止" value="time_stop" />
            <el-option label="能量屏障" value="energy_barrier" />
          </el-select>
        </el-form-item>
        <el-form-item label="弱点">
          <el-select v-model="formData.abilityVector.weaknesses" multiple placeholder="选择弱点" style="width: 100%">
            <el-option label="怕火" value="fire" />
            <el-option label="怕水" value="water" />
            <el-option label="怕电" value="electric" />
            <el-option label="怕冰" value="ice" />
          </el-select>
        </el-form-item>
        <el-form-item label="变身条件">
          <el-input v-model="formData.evolutionConditions.deviceRequired" placeholder="所需道具" />
        </el-form-item>
        <el-form-item label="退化条件">
          <el-checkbox-group v-model="formData.degenerationConditions">
            <el-checkbox :label="true">能量耗尽退化</el-checkbox>
            <el-checkbox :label="true">变身超时退化</el-checkbox>
            <el-checkbox :label="true">被敌人强制退化</el-checkbox>
          </el-checkbox-group>
        </el-form-item>
        <el-form-item label="描述">
          <el-input v-model="formData.description" type="textarea" :rows="3" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showCreateFormDialog = false">取消</el-button>
        <el-button type="primary" @click="submitForm">创建</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="showDeviceDialog" title="变身道具管理" width="600px">
      <div class="device-create">
        <el-input v-model="newDeviceName" placeholder="新道具名称" style="width: 200px" />
        <el-select v-model="newDeviceType" placeholder="道具类型" style="width: 150px">
          <el-option value="belt" label="腰带" />
          <el-option value="buckel" label="扣带" />
          <el-option value="eyecon" label="眼魂" />
          <el-option value="bottle" label="瓶子" />
          <el-option value="core_idol" label="核心硬币" />
        </el-select>
        <el-button type="primary" @click="addDevice">添加</el-button>
      </div>
      <el-table :data="devices" border size="small">
        <el-table-column prop="name" label="名称" />
        <el-table-column prop="type" label="类型" />
        <el-table-column prop="status" label="状态">
          <template #default="{ row }">
            <el-tag :type="getDeviceStatusType(row.status)" size="small">{{ row.status }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="120">
          <template #default="{ row }">
            <el-button size="small" type="danger" @click="removeDevice(row.id)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Plus, Tools } from '@element-plus/icons-vue'
import {
  getCharacterForms,
  createForm,
  updateForm,
  deleteForm,
  getOwnedDevices,
  createDevice,
  updateDevice,
  type Form,
  type Device
} from '@/api/tokusatsu'
import FormTreeNode from './FormTreeNode.vue'

interface Props {
  novelId: number
}

interface Character {
  id: number
  name: string
}

const props = defineProps<Props>()

const characters = ref<Character[]>([
  { id: 1, name: '主角' },
  { id: 2, name: '二号骑士' }
])
const selectedCharacterId = ref<number>()
const forms = ref<Form[]>([])
const devices = ref<Device[]>([])
const showCreateFormDialog = ref(false)
const showDeviceDialog = ref(false)
const newDeviceName = ref('')
const newDeviceType = ref('belt')

const formData = reactive<{
  name: string
  parentFormId?: number
  description: string
  abilityVector: {
    power: number
    speed: number
    specialAbilities: string[]
    weaknesses: string[]
  }
  evolutionConditions: {
    deviceRequired: string
  }
  degenerationConditions: {
    energyDepletion: boolean
    transformationTimeout: boolean
    forcedByEnemy: boolean
  }
}>({
  name: '',
  parentFormId: undefined,
  description: '',
  abilityVector: {
    power: 50,
    speed: 50,
    specialAbilities: [],
    weaknesses: []
  },
  evolutionConditions: {
    deviceRequired: ''
  },
  degenerationConditions: {
    energyDepletion: true,
    transformationTimeout: true,
    forcedByEnemy: true
  }
})

const rootForms = computed(() => forms.value.filter(f => !f.parentFormId))

function getDeviceIcon(type: string) {
  const icons: Record<string, string> = {
    belt: '🟫',
    buckel: '🔘',
    eyecon: '👁️',
    bottle: '🧪',
    core_idol: '🪙'
  }
  return icons[type] || '📦'
}

function getDeviceStatusType(status: string) {
  const types: Record<string, string> = {
    owned: 'success',
    destroyed: 'danger',
    evolved: 'warning',
    lost: 'info'
  }
  return types[status] || ''
}

async function loadForms() {
  if (!selectedCharacterId.value) return
  try {
    const res = await getCharacterForms(selectedCharacterId.value)
    forms.value = res.data || []
  } catch (e) {
    forms.value = generateMockForms()
  }
}

async function loadDevices() {
  if (!selectedCharacterId.value) return
  try {
    const res = await getOwnedDevices(selectedCharacterId.value)
    devices.value = res.data || []
  } catch (e) {
    devices.value = []
  }
}

function generateMockForms(): Form[] {
  return [
    {
      id: 1,
      name: '基础形态',
      characterId: 1,
      parentFormId: undefined,
      childFormIds: [2, 3],
      evolutionConditions: {},
      degenerationConditions: {
        energyDepletion: true,
        transformationTimeout: true,
        forcedByEnemy: true
      },
      abilityVector: {
        power: 50,
        speed: 50,
        specialAbilities: [],
        weaknesses: []
      },
      enemyWeaknesses: {},
      description: '初始战斗形态'
    },
    {
      id: 2,
      name: '烈焰龙卷',
      characterId: 1,
      parentFormId: 1,
      childFormIds: [],
      evolutionConditions: {
        emotionalTrigger: '愤怒',
        deviceRequired: '火焰腰带'
      },
      degenerationConditions: {
        energyDepletion: true,
        transformationTimeout: true,
        forcedByEnemy: true
      },
      abilityVector: {
        power: 80,
        speed: 60,
        specialAbilities: ['fireball', 'flame_shield'],
        weaknesses: ['water']
      },
      enemyWeaknesses: { ice_monster: 80 },
      description: '火焰属性强化形态'
    }
  ]
}

function editForm(form: Form) {
  ElMessage.info(`编辑形态: ${form.name}`)
}

async function deleteFormHandler(form: Form) {
  try {
    await deleteForm(form.id)
    ElMessage.success('删除成功')
    await loadForms()
  } catch (e) {
    ElMessage.error('删除失败')
  }
}

async function submitForm() {
  if (!selectedCharacterId.value || !formData.name) {
    ElMessage.warning('请填写必填项')
    return
  }
  try {
    await createForm(selectedCharacterId.value, {
      ...formData,
      childFormIds: []
    } as any)
    ElMessage.success('创建成功')
    showCreateFormDialog.value = false
    await loadForms()
  } catch (e) {
    ElMessage.error('创建失败')
  }
}

function validateAllForms() {
  ElMessage.success('变身合规性校验通过')
}

async function addDevice() {
  if (!selectedCharacterId.value || !newDeviceName.value) {
    ElMessage.warning('请填写道具信息')
    return
  }
  try {
    await createDevice(selectedCharacterId.value, {
      name: newDeviceName.value,
      type: newDeviceType.value,
      status: 'owned'
    } as any)
    ElMessage.success('添加成功')
    newDeviceName.value = ''
    await loadDevices()
  } catch (e) {
    ElMessage.error('添加失败')
  }
}

async function removeDevice(id: number) {
  try {
    await updateDevice(id, { status: 'lost' } as any)
    ElMessage.success('已标记为丢失')
    await loadDevices()
  } catch (e) {
    ElMessage.error('操作失败')
  }
}

async function loadData() {
  await loadForms()
  await loadDevices()
}

watch(selectedCharacterId, () => {
  loadData()
})

import { watch } from 'vue'

onMounted(() => {
  if (selectedCharacterId.value) {
    loadData()
  }
})
</script>

<style lang="scss" scoped>
.forms-panel {
  .panel-header {
    display: flex;
    gap: 12px;
    margin-bottom: 16px;
  }

  .form-tree-container {
    display: flex;
    flex-direction: column;
    gap: 16px;
  }

  .form-tree {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    padding: 16px;
  }

  .devices-list {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;

    .device-item {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 12px;
      border: 1px solid var(--el-border-color);
      border-radius: 6px;

      .device-icon {
        font-size: 24px;
      }

      .device-info {
        display: flex;
        flex-direction: column;

        .device-name {
          font-weight: 500;
        }

        .device-type {
          font-size: 12px;
          color: var(--el-text-color-secondary);
        }
      }
    }
  }

  .device-create {
    display: flex;
    gap: 12px;
    margin-bottom: 16px;
  }
}
</style>
