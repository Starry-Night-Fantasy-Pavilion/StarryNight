<template>
  <div class="branch-version page-container">
    <div class="page-header">
      <h1>📜 版本历史与分支创作</h1>
      <div class="header-actions">
        <el-select v-model="selectedNovelId" placeholder="选择作品" clearable style="width: 200px" @change="loadData">
          <el-option v-for="novel in novels" :key="novel.id" :label="novel.title" :value="novel.id" />
        </el-select>
      </div>
    </div>

    <div v-if="!selectedNovelId" class="empty-state">
      <el-icon :size="64"><Document /></el-icon>
      <p>请选择作品以查看版本历史</p>
    </div>

    <div v-else class="version-content">
      <el-row :gutter="16">
        <el-col :span="8">
          <el-card class="branch-tree-card">
            <template #header>
              <div class="card-header">
                <span>分支结构图</span>
                <el-button size="small" type="primary" @click="createBranch">新建分支</el-button>
              </div>
            </template>
            <div class="branch-tree">
              <div v-for="branch in branchTree" :key="branch.id" class="branch-node">
                <div
                  class="branch-item"
                  :class="{ active: currentBranch?.id === branch.id }"
                  @click="selectBranch(branch)"
                >
                  <span class="branch-icon">{{ branch.type === 'main' ? '●' : '🌿' }}</span>
                  <span class="branch-name">{{ branch.name }}</span>
                  <span class="branch-version">v{{ branch.version }}</span>
                </div>
                <div v-if="branch.children" class="branch-children">
                  <div
                    v-for="child in branch.children"
                    :key="child.id"
                    class="branch-item child"
                    :class="{ active: currentBranch?.id === child.id }"
                    @click="selectBranch(child)"
                  >
                    <span class="branch-icon">{{ child.type === 'main' ? '●' : '🌿' }}</span>
                    <span class="branch-name">{{ child.name }}</span>
                    <span class="branch-version">v{{ child.version }}</span>
                  </div>
                </div>
              </div>
            </div>
          </el-card>
        </el-col>

        <el-col :span="16">
          <el-card class="version-detail-card">
            <template #header>
              <div class="card-header">
                <span>当前: {{ currentBranch?.name || '未选择' }} {{ currentBranch ? `v${currentBranch.version}` : '' }}</span>
                <el-tag v-if="currentBranch?.type !== 'main'" type="success">{{ currentBranch?.type === '虐文' ? '虐文分支' : '爽文分支' }}</el-tag>
              </div>
            </template>

            <el-tabs v-model="detailTab">
              <el-tab-pane label="版本时间线" name="timeline">
                <el-timeline>
                  <el-timeline-item
                    v-for="(version, idx) in versionHistory"
                    :key="version.id"
                    :timestamp="version.timestamp"
                    :type="version.type"
                    :hollow="version.id !== currentVersionId"
                  >
                    <div class="timeline-content">
                      <div class="timeline-header">
                        <span class="version-name">{{ version.name }}</span>
                        <el-tag v-if="version.id === currentVersionId" size="small" type="primary">当前版本</el-tag>
                      </div>
                      <p class="version-desc">{{ version.description }}</p>
                      <div class="timeline-actions">
                        <el-button size="small" @click="viewVersion(version)">查看</el-button>
                        <el-button v-if="version.id !== currentVersionId" size="small" type="primary" @click="switchToVersion(version)">
                          切换至此
                        </el-button>
                        <el-button v-if="idx > 0" size="small" @click="createBranchFrom(version)">
                          从此版本创建分支
                        </el-button>
                      </div>
                    </div>
                  </el-timeline-item>
                </el-timeline>
              </el-tab-pane>

              <el-tab-pane label="内容对比" name="compare">
                <div class="compare-toolbar">
                  <el-select v-model="compareFrom" placeholder="选择版本" style="width: 180px">
                    <el-option v-for="v in versionHistory" :key="v.id" :label="v.name" :value="v.id" />
                  </el-select>
                  <span>VS</span>
                  <el-select v-model="compareTo" placeholder="选择版本" style="width: 180px">
                    <el-option v-for="v in versionHistory" :key="v.id" :label="v.name" :value="v.id" />
                  </el-select>
                  <el-button type="primary" @click="compareVersions">对比</el-button>
                </div>
                <div v-if="compareResult" class="compare-result">
                  <div class="compare-panel old">
                    <h4>旧版本 ({{ compareFromVersion?.name }})</h4>
                    <pre>{{ compareResult.old }}</pre>
                  </div>
                  <div class="compare-panel new">
                    <h4>新版本 ({{ compareToVersion?.name }})</h4>
                    <pre>{{ compareResult.new }}</pre>
                  </div>
                </div>
              </el-tab-pane>

              <el-tab-pane label="合并管理" name="merge">
                <div class="merge-content">
                  <el-alert title="分支合并" type="info" :closable="false">
                    将其他分支的更改合并到当前分支
                  </el-alert>
                  <div class="merge-list">
                    <div v-for="branch in mergeableBranches" :key="branch.id" class="merge-item">
                      <div class="merge-info">
                        <span class="merge-name">{{ branch.name }}</span>
                        <span class="merge-diff">{{ branch.diffCount }} 处更改</span>
                      </div>
                      <div class="merge-actions">
                        <el-button size="small" @click="previewMerge(branch)">预览</el-button>
                        <el-button size="small" type="primary" @click="executeMerge(branch)">合并</el-button>
                      </div>
                    </div>
                  </div>
                </div>
              </el-tab-pane>
            </el-tabs>
          </el-card>
        </el-col>
      </el-row>
    </div>

    <el-dialog v-model="branchDialogVisible" title="创建新分支" width="480px">
      <el-form :model="branchForm" label-width="100px">
        <el-form-item label="分支名称" required>
          <el-input v-model="branchForm.name" placeholder="如: 虐文结局分支" />
        </el-form-item>
        <el-form-item label="分支类型">
          <el-radio-group v-model="branchForm.type">
            <el-radio value="虐文">虐文分支</el-radio>
            <el-radio value="爽文">爽文分支</el-radio>
            <el-radio value="自定义">自定义</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="起始版本">
          <el-select v-model="branchForm.baseVersionId" placeholder="选择起始版本">
            <el-option v-for="v in versionHistory" :key="v.id" :label="v.name" :value="v.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="描述">
          <el-input v-model="branchForm.description" type="textarea" :rows="3" placeholder="描述分支目的..." />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="branchDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitBranch">创建</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Document } from '@element-plus/icons-vue'
import { listNovels } from '@/api/novel'

interface Novel {
  id: number
  title: string
}

interface Branch {
  id: string
  name: string
  type: 'main' | '虐文' | '爽文' | '自定义'
  version: string
  children?: Branch[]
}

interface Version {
  id: string
  name: string
  description: string
  timestamp: string
  type?: 'primary' | 'success' | 'warning'
}

const selectedNovelId = ref<number>()
const novels = ref<Novel[]>([])
const branchTree = ref<Branch[]>([])
const currentBranch = ref<Branch | null>(null)
const currentVersionId = ref<string>('')
const versionHistory = ref<Version[]>([])
const detailTab = ref<'timeline' | 'compare' | 'merge'>('timeline')

const compareFrom = ref('')
const compareTo = ref('')
const compareResult = ref<{ old: string; new: string } | null>(null)

const branchDialogVisible = ref(false)
const branchForm = reactive({
  name: '',
  type: '自定义',
  baseVersionId: '',
  description: ''
})

const compareFromVersion = computed(() => versionHistory.value.find(v => v.id === compareFrom.value))
const compareToVersion = computed(() => versionHistory.value.find(v => v.id === compareTo.value))

const mergeableBranches = computed(() =>
  branchTree.value.filter(b => b.id !== currentBranch.value?.id && b.type !== 'main')
)

async function loadNovels() {
  try {
    const res = await listNovels({ page: 1, size: 100 })
    novels.value = res.data?.records || []
  } catch (e) {
    console.error('Failed to load novels', e)
  }
}

async function loadData() {
  if (!selectedNovelId.value) return

  branchTree.value = [
    {
      id: 'main',
      name: '主分支',
      type: 'main',
      version: '1.2',
      children: [
        {
          id: '虐文分支',
          name: '虐文分支',
          type: '虐文',
          version: '1.0',
          children: [
            { id: '虐文-20', name: '第20章 虐文版', type: '虐文', version: '1.0' }
          ]
        },
        {
          id: '爽文分支',
          name: '爽文分支',
          type: '爽文',
          version: '1.1'
        }
      ]
    }
  ]

  currentBranch.value = branchTree.value[0]
  currentVersionId.value = 'v1.2-1'

  versionHistory.value = [
    { id: 'v1.2-1', name: '主分支 v1.2', description: '完成第1-20章主体结构', timestamp: '2026-04-28 14:00', type: 'primary' },
    { id: 'v1.1-1', name: '主分支 v1.1', description: '完成第1-15章', timestamp: '2026-04-28 11:30', type: 'success' },
    { id: 'v1.0-1', name: '主分支 v1.0', description: '完成第1-10章', timestamp: '2026-04-28 10:00', type: '' },
    { id: '虐文-1.0', name: '虐文分支 v1.0', description: '从v1.0创建', timestamp: '2026-04-28 12:00', type: 'warning' }
  ]
}

function selectBranch(branch: Branch) {
  currentBranch.value = branch
}

function viewVersion(version: Version) {
  ElMessage.info(`查看版本: ${version.name}`)
}

function switchToVersion(version: Version) {
  currentVersionId.value = version.id
  ElMessage.success(`已切换到版本: ${version.name}`)
}

function createBranch() {
  branchForm.name = ''
  branchForm.type = '自定义'
  branchForm.baseVersionId = currentVersionId.value
  branchForm.description = ''
  branchDialogVisible.value = true
}

function submitBranch() {
  if (!branchForm.name.trim()) {
    ElMessage.warning('请输入分支名称')
    return
  }
  ElMessage.success(`分支 "${branchForm.name}" 创建成功`)
  branchDialogVisible.value = false
}

function createBranchFrom(version: Version) {
  branchForm.baseVersionId = version.id
  branchDialogVisible.value = true
}

function compareVersions() {
  if (!compareFrom.value || !compareTo.value) {
    ElMessage.warning('请选择两个版本进行对比')
    return
  }
  compareResult.value = {
    old: '这是第20章的原始内容...\n\n主角站在悬崖边，望着远方的云海。',
    new: '这是修改后的第20章内容...\n\n主角站在悬崖边，望着远方的云海，心中充满了决心。'
  }
}

function previewMerge(branch: Branch) {
  ElMessage.info(`预览合并: ${branch.name}`)
}

function executeMerge(branch: Branch) {
  ElMessage.success(`已从 ${branch.name} 合并`)
}

onMounted(() => {
  loadNovels()
})
</script>

<style lang="scss" scoped>
.branch-version {
  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 400px;
    color: var(--el-text-color-secondary);
  }

  .version-content {
    .branch-tree-card {
      .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
      }

      .branch-tree {
        .branch-node {
          .branch-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 12px;
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

            .branch-version {
              font-size: 12px;
              color: var(--el-text-color-muted);
            }

            &.child {
              margin-left: 24px;
              padding: 8px 12px;
            }
          }

          .branch-children {
            margin-left: 12px;
            border-left: 1px dashed var(--el-border-color);
          }
        }
      }
    }

    .version-detail-card {
      .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
      }

      .timeline-content {
        .timeline-header {
          display: flex;
          align-items: center;
          gap: 8px;
          margin-bottom: 4px;

          .version-name {
            font-weight: 600;
          }
        }

        .version-desc {
          font-size: 13px;
          color: var(--el-text-color-secondary);
          margin-bottom: 8px;
        }

        .timeline-actions {
          display: flex;
          gap: 8px;
        }
      }

      .compare-toolbar {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 16px;
      }

      .compare-result {
        display: flex;
        gap: 16px;

        .compare-panel {
          flex: 1;
          padding: 12px;
          background: var(--el-fill-color-light);
          border-radius: 6px;

          h4 {
            margin-bottom: 8px;
          }

          pre {
            white-space: pre-wrap;
            font-size: 13px;
            line-height: 1.6;
          }

          &.old {
            border-left: 3px solid var(--el-color-danger);
          }

          &.new {
            border-left: 3px solid var(--el-color-success);
          }
        }
      }

      .merge-content {
        .merge-list {
          margin-top: 16px;

          .merge-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid var(--el-border-color-lighter);

            &:last-child {
              border-bottom: none;
            }

            .merge-info {
              .merge-name {
                font-weight: 500;
                display: block;
              }

              .merge-diff {
                font-size: 12px;
                color: var(--el-text-color-muted);
              }
            }

            .merge-actions {
              display: flex;
              gap: 8px;
            }
          }
        }
      }
    }
  }
}
</style>
