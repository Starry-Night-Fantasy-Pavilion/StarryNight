<template>
  <div class="tokusatsu-manager page-container">
    <div class="page-header">
      <h1>📺 特摄创作增强模块</h1>
      <div class="header-actions">
        <el-select v-model="selectedNovelId" placeholder="选择作品" clearable style="width: 200px" @change="loadData">
          <el-option v-for="novel in novels" :key="novel.id" :label="novel.title" :value="novel.id" />
        </el-select>
      </div>
    </div>

    <div v-if="!selectedNovelId" class="empty-state">
      <el-icon :size="64"><VideoCamera /></el-icon>
      <p>请选择作品以使用特摄创作增强功能</p>
    </div>

    <div v-else class="manager-content">
      <el-tabs v-model="activeTab">
        <el-tab-pane label="🌌 世界线管理" name="worldline">
          <WorldlinePanel :novel-id="selectedNovelId" />
        </el-tab-pane>
        <el-tab-pane label="🔄 形态演化树" name="forms">
          <FormsPanel :novel-id="selectedNovelId" />
        </el-tab-pane>
        <el-tab-pane label="🎭 单元剧大纲" name="episodes">
          <EpisodePanel :novel-id="selectedNovelId" />
        </el-tab-pane>
        <el-tab-pane label="😈 敌役模板" name="villains">
          <VillainPanel :novel-id="selectedNovelId" />
        </el-tab-pane>
        <el-tab-pane label="⚖️ 一致性检查" name="consistency">
          <ConsistencyPanel :novel-id="selectedNovelId" />
        </el-tab-pane>
      </el-tabs>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { VideoCamera } from '@element-plus/icons-vue'
import { listNovels } from '@/api/novel'
import WorldlinePanel from './components/WorldlinePanel.vue'
import FormsPanel from './components/FormsPanel.vue'
import EpisodePanel from './components/EpisodePanel.vue'
import VillainPanel from './components/VillainPanel.vue'
import ConsistencyPanel from './components/ConsistencyPanel.vue'

interface Novel {
  id: number
  title: string
}

const selectedNovelId = ref<number>()
const novels = ref<Novel[]>([])
const activeTab = ref('worldline')

async function loadNovels() {
  try {
    const res = await listNovels({ page: 1, size: 100 })
    novels.value = res.data?.records || []
  } catch (e) {
    console.error('Failed to load novels', e)
  }
}

function loadData() {
  activeTab.value = 'worldline'
}

onMounted(() => {
  loadNovels()
})
</script>

<style lang="scss" scoped>
.tokusatsu-manager {
  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;

    h1 {
      margin: 0;
      font-size: 24px;
    }
  }

  .empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 400px;
    color: var(--el-text-color-secondary);
  }
}
</style>
