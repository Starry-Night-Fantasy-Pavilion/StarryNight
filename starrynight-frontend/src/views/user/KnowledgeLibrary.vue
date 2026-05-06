<template>
  <div class="kl-page">
    <div class="page-toolbar">
      <div>
        <h2 class="toolbar-title">知识库</h2>
        <p class="toolbar-sub">构建专属世界观素材，AI 基于你的设定精准创作</p>
      </div>
      <div class="toolbar-right">
        <span class="cap-info">已用 {{ used }} / {{ total }}</span>
        <el-button class="btn-primary" @click="showUpload = true">
          <el-icon><Upload /></el-icon>上传文档
        </el-button>
      </div>
    </div>

    <div class="panel">
      <div class="panel-bar">
        <el-form :inline="true" size="default">
          <el-form-item label="搜索">
            <el-input v-model="qf.keyword" placeholder="搜索知识条目" clearable style="width:200px" @keyup.enter="search" />
          </el-form-item>
          <el-form-item label="分类">
            <el-select v-model="qf.type" placeholder="全部" clearable style="width:130px" @change="search">
              <el-option label="官方正史" value="canon" /><el-option label="参考资料" value="reference" />
              <el-option label="素材" value="material" /><el-option label="自定义" value="custom" />
            </el-select>
          </el-form-item>
          <el-form-item label="状态">
            <el-select v-model="qf.status" placeholder="全部" clearable style="width:120px" @change="search">
              <el-option label="处理中" value="PROCESSING" /><el-option label="就绪" value="READY" /><el-option label="失败" value="ERROR" />
            </el-select>
          </el-form-item>
          <el-form-item>
            <el-button class="btn-primary" @click="search">搜索</el-button>
            <el-button class="btn-ghost" @click="resetQ">重置</el-button>
          </el-form-item>
        </el-form>
      </div>

      <el-table :data="list" v-loading="loading" class="dark-table">
        <el-table-column label="文档名称" min-width="200">
          <template #default="{row}"><span class="doc-icon">{{ tyIcon(row.type) }}</span> {{ row.name }}</template>
        </el-table-column>
        <el-table-column label="分类" width="110">
          <template #default="{row}"><span class="kl-tag" :class="`kt-${tyTag(row.type)}`">{{ tyLabel(row.type) }}</span></template>
        </el-table-column>
        <el-table-column prop="documentCount" label="文档数" width="80" align="center" />
        <el-table-column prop="chunkCount" label="切片数" width="80" align="center" />
        <el-table-column label="状态" width="90">
          <template #default="{row}"><span class="kl-tag" :class="`kt-${stTag(row.status)}`">{{ stLabel(row.status) }}</span></template>
        </el-table-column>
        <el-table-column prop="updatedAt" label="更新时间" width="170" />
        <el-table-column label="操作" width="160" fixed="right">
          <template #default="{row}">
            <el-button link class="act" @click="viewItem(row)">查看</el-button>
            <el-button link class="act" @click="editItem(row)">编辑</el-button>
            <el-popconfirm title="确定删除？" @confirm="deleteItem(row)"><template #reference><el-button link class="act act-dang">删除</el-button></template></el-popconfirm>
          </template>
        </el-table-column>
      </el-table>

      <div class="pager" v-if="total>0">
        <el-pagination v-model:current-page="qf.page" v-model:page-size="qf.size" :total="total" :page-sizes="[10,20,50]" layout="total,sizes,prev,pager,next" @change="load" />
      </div>
      <el-empty v-if="!loading && !list.length" description="暂无知识库" />
    </div>

    <el-dialog v-model="showUpload" title="上传文档" width="520px">
      <el-form label-position="top" size="large">
        <el-form-item label="名称" required><el-input v-model="uf.name" placeholder="输入知识库名称" maxlength="50" /></el-form-item>
        <el-form-item label="分类" required>
          <el-select v-model="uf.type" style="width:100%">
            <el-option label="官方正史" value="canon" /><el-option label="参考资料" value="reference" />
            <el-option label="素材" value="material" /><el-option label="自定义" value="custom" />
          </el-select>
        </el-form-item>
        <el-form-item label="描述"><el-input v-model="uf.desc" type="textarea" :rows="3" /></el-form-item>
        <el-form-item label="文件">
          <el-upload :auto-upload="false" :limit="1" accept=".txt,.md,.pdf,.epub">
            <el-button class="btn-ghost">选择文件</el-button>
            <template #tip><span class="tip">支持 txt/md/pdf/epub</span></template>
          </el-upload>
        </el-form-item>
      </el-form>
      <template #footer><el-button @click="showUpload=false">取消</el-button><el-button class="btn-primary" @click="submitUp">上传解析</el-button></template>
    </el-dialog>

    <el-dialog v-model="showEdit" title="编辑知识库" width="480px">
      <el-form label-position="top" size="large">
        <el-form-item label="名称"><el-input v-model="ef.name" maxlength="50" /></el-form-item>
        <el-form-item label="描述"><el-input v-model="ef.desc" type="textarea" :rows="3" /></el-form-item>
      </el-form>
      <template #footer><el-button @click="showEdit=false">取消</el-button><el-button class="btn-primary" @click="submitEdit">保存</el-button></template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { Upload } from '@element-plus/icons-vue'
import { get, post, put, del } from '@/utils/request'

const router = useRouter()
interface KItem { id:number;name:string;type:string;description?:string;tags?:string[];documentCount:number;chunkCount:number;status:string;fileSize?:number;createdAt:string;updatedAt:string }

const loading=ref(false);const total=ref(0);const list=ref<KItem[]>([])
const showUpload=ref(false);const showEdit=ref(false)
const used=ref('0 MB');const totalCap=ref('1 GB')

const qf=reactive({ page:1,size:10,keyword:'',type:'',status:'' })
const uf=reactive({ name:'',type:'',desc:'' })
const ef=reactive({ id:0,name:'',desc:'' })

function tyIcon(t:string):string { const m:Record<string,string>={canon:'📜',reference:'📚',material:'📦',custom:'📁'}; return m[t]||'📄' }
function tyLabel(t:string):string { const m:Record<string,string>={canon:'官方正史',reference:'参考资料',material:'素材',custom:'自定义'}; return m[t]||t }
function tyTag(t:string):string { const m:Record<string,string>={canon:'pu',reference:'gr',material:'am',custom:'sl'}; return m[t]||'sl' }
function stLabel(s:string):string { const m:Record<string,string>={PROCESSING:'处理中',READY:'就绪',ERROR:'失败'}; return m[s]||s }
function stTag(s:string):string { const m:Record<string,string>={PROCESSING:'am',READY:'gr',ERROR:'dg'}; return m[s]||'sl' }

async function load() { loading.value=true
  try { const r=await get<any>('/knowledge/list',{ params:qf }); list.value=r?.records||[]; total.value=r?.total||0 } catch { list.value=[]; total.value=0 } finally { loading.value=false }
}
function search() { qf.page=1; load() }
function resetQ() { qf.keyword=''; qf.type=''; qf.status=''; search() }
function viewItem(r:KItem) { router.push(`/knowledge/${r.id}`) }
function editItem(r:KItem) { ef.id=r.id; ef.name=r.name; ef.desc=r.description||''; showEdit.value=true }
async function deleteItem(r:KItem) { await del(`/knowledge/${r.id}`); ElMessage.success('已删除'); load() }
async function submitUp() { showUpload.value=false; ElMessage.success('上传成功'); load() }
async function submitEdit() { showEdit.value=false; ElMessage.success('已更新'); load() }
onMounted(load)
</script>

<style lang="scss" scoped>
.kl-page { padding:$space-xl; max-width:1300px; margin:0 auto; }
.page-toolbar { display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:$space-lg;
  .toolbar-title{font-size:$font-size-xl;font-weight:700;color:$text-primary;}
  .toolbar-sub{font-size:$font-size-sm;color:$text-muted;margin-top:2px;}
  .toolbar-right{display:flex;align-items:center;gap:$space-md;}
}
.cap-info { font-size:$font-size-sm;color:$text-muted; }
.panel { background:$bg-surface;border:1px solid $border-color;border-radius:$border-radius-lg;overflow:hidden; }
.panel-bar { padding:$space-md $space-lg;border-bottom:1px solid $border-color; }
.dark-table { --el-table-bg-color:transparent;--el-table-tr-bg-color:transparent;--el-table-header-bg-color:rgba(255,255,255,0.02);--el-table-border-color:rgba(255,255,255,0.06);--el-table-text-color:#f1f5f9;--el-table-header-text-color:#94a3b8;--el-table-row-hover-bg-color:rgba(255,255,255,0.03); }
.doc-icon { margin-right:6px;font-size:15px; }
.kl-tag { font-size:10px;font-weight:500;padding:2px 8px;border-radius:4px;
  &.kt-pu{background:rgba(99,102,241,0.12);color:#a5b4fc;}&.kt-gr{background:rgba(16,185,129,0.12);color:#34d399;}
  &.kt-am{background:rgba(251,191,36,0.12);color:#fbbf24;}&.kt-sl{background:rgba(148,163,184,0.12);color:#94a3b8;}
  &.kt-dg{background:rgba(239,68,68,0.12);color:#f87171;}
}
.btn-primary { background:linear-gradient(135deg,$primary-color,$primary-dark);border:none;color:#fff;font-weight:600;border-radius:8px;&:hover{background:linear-gradient(135deg,$primary-light,$primary-color);box-shadow:0 0 16px rgba(99,102,241,0.3);} }
.btn-ghost { background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08);color:$text-primary;border-radius:8px;&:hover{background:rgba(255,255,255,0.06);} }
.act { font-size:$font-size-sm;color:$primary-light;&.act-dang{color:#f87171;} }
.pager { padding:$space-md $space-lg;display:flex;justify-content:flex-end; }
.tip { font-size:$font-size-xs;color:$text-muted; }
</style>
