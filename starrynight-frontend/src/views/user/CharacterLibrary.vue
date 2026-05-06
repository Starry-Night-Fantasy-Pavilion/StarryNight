<template>
  <div class="char-page">
    <div class="page-toolbar">
      <div>
        <h2 class="toolbar-title">角色库</h2>
        <p class="toolbar-sub">系统化管理作品角色</p>
      </div>
      <div class="toolbar-actions">
        <el-select v-model="selNovel" placeholder="选择作品" clearable style="width:180px" @change="load">
          <el-option v-for="n in novels" :key="n.id" :label="n.title" :value="n.id" />
        </el-select>
        <el-button class="btn-ghost" v-if="selNovel" @click="showRel = true">关系图</el-button>
        <el-button class="btn-ghost" v-if="selNovel" @click="importDlg = true">导入</el-button>
        <el-button class="btn-primary" @click="openCreate">新增角色</el-button>
      </div>
    </div>

    <div class="panel">
      <div class="panel-bar">
        <el-form :inline="true" size="default">
          <el-form-item label="搜索">
            <el-input v-model="kw" placeholder="搜索角色名" clearable style="width:200px" @keyup.enter="load" />
          </el-form-item>
          <el-form-item><el-button class="btn-primary" @click="load">搜索</el-button></el-form-item>
        </el-form>
      </div>

      <div v-if="chars.length" class="char-grid">
        <div v-for="c in chars" :key="c.id" class="char-card">
          <div class="cc-top">
            <el-avatar :size="48">{{ c.name?.charAt(0) }}</el-avatar>
            <div>
              <h3 class="cc-name">{{ c.name }}</h3>
              <span class="cc-id">{{ c.identity || '\u672a\u8bbe\u5b9a\u8eab\u4efd' }}</span>
            </div>
          </div>
          <div class="cc-div"></div>
          <div class="cc-mid">
            <div class="cc-row"><label>性格</label><span>{{ c.personality?.traits?.slice(0,3).join('、') || '未设定' }}</span></div>
            <div class="cc-row"><label>能力</label><span>{{ c.abilities?.level || '未设定' }}</span></div>
            <div class="cc-row"><label>关系</label><span>{{ c.relationships?.length ? `${c.relationships.length}个关联` : '无' }}</span></div>
          </div>
          <div class="cc-foot">
            <el-button link class="act" @click="viewChar(c)">详情</el-button>
            <el-button link class="act" @click="editChar(c)">编辑</el-button>
            <el-popconfirm title="确定删除？" @confirm="delChar(c)"><template #reference><el-button link class="act act-dang">删除</el-button></template></el-popconfirm>
          </div>
        </div>
      </div>
      <el-empty v-else description="暂无角色" />

      <div class="pager" v-if="total>0">
        <el-pagination v-model:current-page="pg" v-model:page-size="ps" :total="total" :page-sizes="[12,24,48]" layout="total,sizes,prev,pager,next" @change="load" />
      </div>
    </div>

    <el-dialog v-model="dlg" :title="isEdit?'编辑':'新增'" width="560px">
      <el-form label-position="top" size="large">
        <el-row :gutter="16">
          <el-col :span="12"><el-form-item label="名称" required><el-input v-model="cf.name" /></el-form-item></el-col>
          <el-col :span="12"><el-form-item label="身份"><el-input v-model="cf.identity" /></el-form-item></el-col>
        </el-row>
        <el-form-item label="外貌"><el-input v-model="cf.appearance" type="textarea" :rows="2" /></el-form-item>
        <el-form-item label="背景"><el-input v-model="cf.background" type="textarea" :rows="3" /></el-form-item>
      </el-form>
      <template #footer><el-button @click="dlg=false">取消</el-button><el-button class="btn-primary" @click="submitChar">保存</el-button></template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { get, post, put, del } from '@/utils/request'

const loading=ref(false);const total=ref(0);const pg=ref(1);const ps=ref(12)
const kw=ref('');const selNovel=ref<number|null>(null)
const chars=ref<any[]>([]);const novels=ref<any[]>([])
const dlg=ref(false);const isEdit=ref(false);const showRel=ref(false);const importDlg=ref(false)

const cf=reactive({ name:'',identity:'',appearance:'',background:'' })

async function loadNovels() { try { const r=await get<any>('/novel/list?page=1&size=100'); novels.value=r?.records||[] } catch {} }
async function load() { loading.value=true
  try { const p:any={ page:pg.value,size:ps.value }; if(kw.value) p.keyword=kw.value; if(selNovel.value) p.novelId=selNovel.value
    const r=await get<any>('/character/list',{ params:p }); chars.value=r?.records||[]; total.value=r?.total||0
  } catch { chars.value=[] } finally { loading.value=false }
}
function openCreate() { isEdit.value=false; Object.assign(cf,{name:'',identity:'',appearance:'',background:''}); dlg.value=true }
function editChar(c:any) { isEdit.value=true; Object.assign(cf,c); dlg.value=true }
function viewChar(c:any) { /* detail */ }
async function delChar(c:any) { await del(`/character/${c.id}`); ElMessage.success('已删除'); load() }
async function submitChar() { dlg.value=false; ElMessage.success(isEdit.value?'已修改':'已创建'); load() }
onMounted(()=>{ loadNovels(); load() })
</script>

<style lang="scss" scoped>
.char-page { padding:$space-xl; max-width:1400px; margin:0 auto; }
.page-toolbar { display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:$space-lg;
  .toolbar-title{font-size:$font-size-xl;font-weight:700;color:$text-primary;}
  .toolbar-sub{font-size:$font-size-sm;color:$text-muted;margin-top:2px;}
  .toolbar-actions{display:flex;gap:$space-sm;align-items:center;}
}
.panel { background:$bg-surface;border:1px solid $border-color;border-radius:$border-radius-lg;overflow:hidden; }
.panel-bar { padding:$space-md $space-lg;border-bottom:1px solid $border-color; }
.char-grid { display:grid;grid-template-columns:repeat(4,1fr);gap:$space-md;padding:$space-lg; }
.char-card { background:$bg-elevated;border:1px solid $border-color;border-radius:$border-radius;padding:$space-md;transition:all $transition-normal;&:hover{border-color:rgba(99,102,241,0.25);transform:translateY(-2px);box-shadow:0 4px 20px rgba(99,102,241,0.08);} }
.cc-top { display:flex;align-items:center;gap:$space-sm;margin-bottom:$space-sm; }
.cc-name { font-size:$font-size-md;font-weight:600;color:$text-primary;overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }
.cc-id { font-size:$font-size-xs;color:$text-muted; }
.cc-div { height:1px;background:$border-color;margin-bottom:$space-sm; }
.cc-row { display:flex;justify-content:space-between;margin-bottom:4px;label{font-size:$font-size-xs;color:$text-muted;}span{font-size:$font-size-xs;color:$text-secondary;max-width:60%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;text-align:right;} }
.cc-foot { display:flex;justify-content:flex-end;gap:4px;margin-top:$space-sm;padding-top:$space-sm;border-top:1px solid $border-color; }
.btn-primary { background:linear-gradient(135deg,$primary-color,$primary-dark);border:none;color:#fff;font-weight:600;border-radius:8px;&:hover{background:linear-gradient(135deg,$primary-light,$primary-color);box-shadow:0 0 16px rgba(99,102,241,0.3);} }
.btn-ghost { background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08);color:$text-primary;border-radius:8px;&:hover{background:rgba(255,255,255,0.06);} }
.act { font-size:$font-size-sm;color:$primary-light;&.act-dang{color:#f87171;} }
.pager { padding:$space-md $space-lg;display:flex;justify-content:flex-end; }
@media(max-width:1400px){.char-grid{grid-template-columns:repeat(3,1fr)}}
@media(max-width:1024px){.char-grid{grid-template-columns:repeat(2,1fr)}}
</style>
