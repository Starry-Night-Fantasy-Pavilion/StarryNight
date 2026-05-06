<template>
  <div class="novel-branch page-container">
    <div class="page-header">
      <h2>🔀 分支版本</h2>
      <div class="header-actions">
        <el-select v-model="selectedNovelId" placeholder="选择作品" clearable style="width: 200px" @change="loadData">
          <el-option v-for="novel in novels" :key="novel.id" :label="novel.title" :value="novel.id" />
        </el-select>
        <el-button type="primary" :disabled="!selectedNovelId" @click="createBranch">
          <el-icon><Plus /></el-icon>
          新建分支
        </el-button>
      </div>
    </div>

    <div v-if="!selectedNovelId" class="empty-state">
      <el-icon :size="64"><FolderOpened /></el-icon>
      <p>请选择作品以管理分支版本</p>
    </div>

    <div v-else class="branch-content">
      <el-row :gutter="16">
        <el-col :span="8">
          <el-card class="branch-tree-card">
            <template #header>
              <span>分支列表</span>
            </template>
            <div class="branch-list">
              <div v-for="branch in branches" :key="branch.id" class="branch-item">
                <div
                  class="branch-row"
                  :class="{ active: currentBranch?.id === branch.id }"
                  @click="selectBranch(branch)"
                >
                  <span class="branch-icon">{{ branch.status === 'main' ? '●' : '🌿' }}</span>
                  <span class="branch-name">{{ branch.name }}</span>
                  <el-tag v-if="branch.status !== 'active'" size="small" type="info">{{ branch.status }}</el-tag>
                </div>
              </div>
              <div v-if="branches.length === 0" class="empty-list">
                <p>暂无分支</p>
              </div>
            </div>
          </el-card>
        </el-col>

        <el-col :span="16">
          <el-card v-if="currentBranch" class="branch-detail-card">
            <template #header>
              <div class="card-header">
                <span>{{ currentBranch.name }}</span>
                <el-button size="small" type="primary" @click="viewHistory">查看历史</el-button>
              </div>
            </template>
            <el-descriptions :column="2" border size="small">
              <el-descriptions-item label="分支名称">{{ currentBranch.name }}</el-descriptions-item>
              <el-descriptions-item label="状态">{{ currentBranch.status }}</el-descriptions-item>
              <el-descriptions-item label="描述" :span="2">{{ currentBranch.description || '无' }}</el-descriptions-item>
              <el-descriptions-item label="创建时间">{{ currentBranch.createdAt }}</el-descriptions-item>
              <el-descriptions-item label="合并时间">{{ currentBranch.mergedAt || '未合并' }}</el-descriptions-item>
            </el-descriptions>
            <div class="branch-actions">
              <el-button type="primary" @click="switchToBranch">切换至此分支</el-button>
              <el-button @click="mergeToMain">合并到主分支</el-button>
              <el-button type="danger" @click="deleteCurrentBranch">删除分支</el-button>
            </div>
          </el-card>
          <el-card v-else class="empty-detail">
            <el-empty description="请选择要查看的分支" />
          </el-card>
        </el-col>
      </el-row>
    </div>

    <el-dialog v-model="dialogVisible" title="创建新分支" width="480px">
      <el-form :model="branchForm" label-width="100px">
        <el-form-item label="分支名称" required>
          <el-input v-model="branchForm.name" placeholder="如: 虐文结局分支" />
        </el-form-item>
        <el-form-item label="分支类型">
          <el-radio-group v-model="branchForm.branchType">
            <el-radio value="虐文">虐文分支</el-radio>
            <el-radio value="爽文">爽文分支</el-radio>
            <el-radio value="自定义">自定义</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="描述">
          <el-input v-model="branchForm.description" type="textarea" :rows="3" placeholder="描述分支目的..." />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitBranch">创建</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Plus, FolderOpened } from '@element-plus/icons-vue'
import { useRoute, useRouter } from 'vue-router'
import { listNovels } from '@/api/novel'
import {
  listBranches,
  createBranch,
  deleteBranch,
  archiveBranch,
  type Branch,
  type BranchCreateDTO
} from '@/api/novelOutline'

const route = useRoute()
const router = useRouter()

interface Novel {
  id: number
  title: string
}

const selectedNovelId = ref<number | null>(null)
const novels = ref<Novel[]>([])
const branches = ref<Branch[]>([])
const currentBranch = ref<Branch | null>(null)
const dialogVisible = ref(false)

const branchForm = reactive<BranchCreateDTO>({
  novelId: 0,
  name: '',
  description: '',
  branchType: '自定义'
})

async function loadNovels() {
  try {
    const res = await listNovels({ page: 1, size: 100 })
    novels.value = res.data?.records || []
    if (route.params.id) {
      selectedNovelId.value = parseInt(route.params.id as string)
    }
  } catch (e) {
    console.error('Failed to load novels', e)
  }
}

async function loadBranches() {
  if (!selectedNovelId.value) return
  try {
    const res = await listBranches(selectedNovelId.value)
    branches.value = res.data || []
    if (branches.value.length > 0 && !currentBranch.value) {
      currentBranch.value = branches.value[0]
    }
  } catch (e) {
    console.error('Failed to load branches', e)
  }
}

async function loadData() {
  await loadBranches()
}

function selectBranch(branch: Branch) {
  currentBranch.value = branch
}

function createBranch() {
  branchForm.novelId = selectedNovelId.value || 0
  branchForm.name = ''
  branchForm.description = ''
  branchForm.branchType = '自定义'
  dialogVisible.value = true
}

async function submitBranch() {
  if (!branchForm.name.trim()) {
    ElMessage.warning('请输入分支名称')
    return
  }
  try {
    await createBranch(branchForm)
    ElMessage.success('分支创建成功')
    dialogVisible.value = false
    await loadBranches()
  } catch (e) {
    ElMessage.error('创建失败')
  }
}

function viewHistory() {
  if (currentBranch.value) {
    router.push(`/user/branch-version/${currentBranch.value.id}`)
  }
}

function switchToBranch() {
  if (currentBranch.value) {
    ElMessage.success(`已切换到分支: ${currentBranch.value.name}`)
  }
}

function mergeToMain() {
  if (currentBranch.value) {
    ElMessage.info(`合并 ${currentBranch.value.name} 到主分支`)
  }
}

async function deleteCurrentBranch() {
  if (!currentBranch.value) return
  try {
    await deleteBranch(currentBranch.value.id)
    ElMessage.success('分支已删除')
    await loadBranches()
    currentBranch.value = null
  } catch (e) {
    ElMessage.error('删除失败')
  }
}

onMounted(() => {
  loadNovels()
})
</script>

<style lang="scss" scoped>
.novel-branch {
  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;

    h2 {
      margin: 0;
      font-size: 20px;
    }

    .header-actions {
      display: flex;
      gap: 12px;
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

  .branch-content {
    .branch-tree-card {
      .branch-list {
        .branch-item {
          .branch-row {
            display: flex;
            align-items: center;
            gap: 8px;
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

            .branch-icon {
              font-size: 12px;
            }

            .branch-name {
              flex: 1;
              font-size: 14px;
            }
          }
        }

        .empty-list {
          text-align: center;
          padding: 20px;
          color: var(--el-text-color-secondary);
        }
      }
    }

    .branch-detail-card {
      .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
      }

      .branch-actions {
        margin-top: 16px;
        display: flex;
        gap: 8px;
      }
    }

    .empty-detail {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 300px;
    }
  }
}
</style>
