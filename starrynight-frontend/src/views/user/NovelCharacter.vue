<template>
  <div class="novel-character page-container">
    <div class="page-header">
      <div class="header-left">
        <h2>角色库</h2>
        <el-tag type="info">{{ characters.length }} 个角色</el-tag>
      </div>
      <div class="header-actions">
        <el-button @click="showRelationshipGraph = true">
          <el-icon><Connection /></el-icon>
          关系图谱
        </el-button>
        <el-button type="primary" @click="showCreateDialog = true">
          <el-icon><Plus /></el-icon>
          新增角色
        </el-button>
      </div>
    </div>

    <div class="page-content">
      <div v-if="characters.length" class="character-grid">
        <CharacterCard
          v-for="char in characters"
          :key="char.id"
          :character="char"
          @click="viewCharacter(char)"
          @edit="editCharacter(char)"
          @command="(cmd) => handleCommand(cmd, char)"
        />
      </div>

      <el-empty v-else description="暂无角色">
        <el-button type="primary" @click="showCreateDialog = true">创建第一个角色</el-button>
      </el-empty>
    </div>

    <el-dialog v-model="showDialog" :title="isEdit ? '编辑角色' : '新增角色'" width="700px" destroy-on-close>
      <el-form :model="charForm" label-width="100px">
        <el-form-item label="角色名称" required>
          <el-input v-model="charForm.name" placeholder="请输入角色名称" />
        </el-form-item>
        <el-form-item label="身份/定位">
          <el-input v-model="charForm.identity" placeholder="如：主角、反派、导师等" />
        </el-form-item>
        <el-form-item label="性别">
          <el-radio-group v-model="charForm.gender">
            <el-radio label="男">男</el-radio>
            <el-radio label="女">女</el-radio>
            <el-radio label="其他">其他</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="年龄">
          <el-input v-model="charForm.age" placeholder="如：25岁、青年等" />
        </el-form-item>
        <el-form-item label="外貌特征">
          <el-input v-model="charForm.appearance" type="textarea" :rows="2" placeholder="描述角色的外貌特征" />
        </el-form-item>
        <el-form-item label="性格特点">
          <el-input v-model="charForm.personality" type="textarea" :rows="2" placeholder="描述角色的性格" />
        </el-form-item>
        <el-form-item label="背景故事">
          <el-input v-model="charForm.background" type="textarea" :rows="3" placeholder="角色的背景故事" />
        </el-form-item>
        <el-form-item label="能力/特长">
          <el-input v-model="charForm.abilities" placeholder="角色拥有的特殊能力" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showDialog = false">取消</el-button>
        <el-button type="primary" @click="handleSave">保存</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="showRelationshipGraph" title="角色关系图谱" width="900px" destroy-on-close>
      <RelationshipGraph
        v-if="showRelationshipGraph"
        :data="graphData"
        @node-click="viewCharacter"
      />
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import { Plus, Connection } from '@element-plus/icons-vue'
import type { NovelCharacter } from '@/api/character'
import { listCharacters, createCharacter, updateCharacter, deleteCharacter } from '@/api/character'
import CharacterCard from './components/CharacterCard.vue'
import RelationshipGraph from './components/RelationshipGraph.vue'

const route = useRoute()
const novelId = computed(() => route.params.id as string)

const characters = ref<NovelCharacter[]>([])
const showDialog = ref(false)
const showRelationshipGraph = ref(false)
const isEdit = ref(false)
const editingId = ref<number | null>(null)

const charForm = reactive({
  name: '',
  identity: '',
  gender: '',
  age: '',
  appearance: '',
  personality: '',
  background: '',
  abilities: ''
})

const graphData = computed(() => ({
  nodes: characters.value.map(c => ({
    id: c.id!,
    name: c.name,
    identity: c.identity,
    personality: c.personality,
    relationships: c.relationships || []
  })),
  links: characters.value.flatMap(c =>
    (c.relationships || []).map(r => ({
      source: c.id!,
      target: r.targetId,
      label: r.type,
      type: r.type as 'friendly' | 'hostile' | 'neutral'
    }))
  )
}))

async function loadCharacters() {
  try {
    const res = await listCharacters({ novelId: Number(novelId.value) })
    characters.value = res?.records || []
  } catch (error) {
    console.error('Load characters failed:', error)
  }
}

function viewCharacter(character: NovelCharacter) {
  Object.assign(charForm, {
    name: character.name,
    identity: character.identity,
    gender: character.gender,
    age: character.age,
    appearance: character.appearance,
    personality: character.personality?.traits?.join(','),
    background: character.background,
    abilities: character.abilities?.level
  })
  editingId.value = character.id!
  isEdit.value = true
  showDialog.value = true
}

function editCharacter(character: NovelCharacter) {
  viewCharacter(character)
}

async function handleSave() {
  try {
    const data = {
      novelId: Number(novelId.value),
      name: charForm.name,
      identity: charForm.identity,
      gender: charForm.gender,
      age: charForm.age,
      appearance: charForm.appearance,
      personality: { traits: charForm.personality.split(',').filter(Boolean) },
      background: charForm.background,
      abilities: { level: charForm.abilities, skills: [] }
    }

    if (isEdit.value && editingId.value) {
      await updateCharacter(editingId.value, data)
      ElMessage.success('角色更新成功')
    } else {
      await createCharacter(data)
      ElMessage.success('角色创建成功')
    }

    showDialog.value = false
    loadCharacters()
  } catch (error) {
    ElMessage.error('保存失败')
  }
}

async function handleCommand(command: string, character: NovelCharacter) {
  if (command === 'delete') {
    if (character.id) {
      await deleteCharacter(character.id)
      ElMessage.success('删除成功')
      loadCharacters()
    }
  }
}

onMounted(() => {
  loadCharacters()
})
</script>

<style lang="scss" scoped>
.novel-character {
  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;

    .header-left {
      display: flex;
      align-items: center;
      gap: 12px;

      h2 {
        margin: 0;
        font-size: 20px;
      }
    }

    .header-actions {
      display: flex;
      gap: 12px;
    }
  }

  .character-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;
  }
}
</style>
