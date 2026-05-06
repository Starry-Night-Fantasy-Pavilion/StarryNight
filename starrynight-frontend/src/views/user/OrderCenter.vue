<template>
  <div class="order-page">
    <div class="page-toolbar">
      <div>
        <h2 class="toolbar-title">订单中心</h2>
        <p class="toolbar-sub">查看和管理你的订单记录</p>
      </div>
    </div>

    <div class="stats-row">
      <div class="st-card" v-for="s in orderStats" :key="s.key">
        <span class="stv" :class="s.color">{{ s.value }}</span>
        <span class="stl">{{ s.label }}</span>
      </div>
    </div>

    <div class="panel">
      <div class="panel-bar">
        <el-form :inline="true" size="default">
          <el-form-item label="状态">
            <el-select v-model="qf.status" placeholder="全部" clearable style="width:130px">
              <el-option label="待支付" :value="0" /><el-option label="已支付" :value="1" />
              <el-option label="已完成" :value="2" /><el-option label="已取消" :value="3" /><el-option label="已退款" :value="4" />
            </el-select>
          </el-form-item>
          <el-form-item label="搜索">
            <el-input v-model="qf.keyword" placeholder="订单号/商品名" clearable style="width:180px" />
          </el-form-item>
          <el-form-item>
            <el-button class="btn-primary" @click="search">搜索</el-button>
            <el-button class="btn-ghost" @click="resetQ">重置</el-button>
          </el-form-item>
        </el-form>
      </div>
      <el-table :data="orders" v-loading="loading" class="dark-table">
        <el-table-column prop="orderNo" label="订单号" min-width="180" />
        <el-table-column prop="productName" label="商品" min-width="140" />
        <el-table-column prop="amount" label="金额" width="100"><template #default="{row}"><span class="money">¥{{ row.amount?.toFixed(2) }}</span></template></el-table-column>
        <el-table-column prop="status" label="状态" width="90">
          <template #default="{row}"><span class="os-tag" :class="`os-${sty(row.status)}`">{{ stx(row.status) }}</span></template>
        </el-table-column>
        <el-table-column prop="payTime" label="支付时间" width="170"><template #default="{row}">{{ row.payTime || '-' }}</template></el-table-column>
        <el-table-column prop="createTime" label="创建时间" width="170" />
        <el-table-column label="操作" width="140" fixed="right">
          <template #default="{row}">
            <el-button link class="act" @click="detail(row)">详情</el-button>
            <el-button v-if="row.status===0" link class="act act-warn" @click="goPay(row)">支付</el-button>
          </template>
        </el-table-column>
      </el-table>
      <div class="pager" v-if="total>0">
        <el-pagination v-model:current-page="qf.page" v-model:page-size="qf.size" :total="total" :page-sizes="[10,20,50]" layout="total,sizes,prev,pager,next" @change="load" />
      </div>
      <el-empty v-if="!loading && !orders.length" description="暂无订单" />
    </div>

    <el-dialog v-model="detailV" title="订单详情" width="500px">
      <el-descriptions v-if="cur" :column="2" border size="small">
        <el-descriptions-item label="订单号" :span="2">{{ cur.orderNo }}</el-descriptions-item>
        <el-descriptions-item label="商品">{{ cur.productName }}</el-descriptions-item>
        <el-descriptions-item label="金额"><span class="money">¥{{ cur.amount?.toFixed(2) }}</span></el-descriptions-item>
        <el-descriptions-item label="状态"><span class="os-tag" :class="`os-${sty(cur.status)}`">{{ stx(cur.status) }}</span></el-descriptions-item>
        <el-descriptions-item label="支付时间">{{ cur.payTime||'-' }}</el-descriptions-item>
        <el-descriptions-item label="创建时间">{{ cur.createTime }}</el-descriptions-item>
      </el-descriptions>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { ElMessage } from 'element-plus'
import { get } from '@/utils/request'
import type { AdminOrderItem, PageVO } from '@/types/api'

const loading=ref(false); const total=ref(0); const orders=ref<AdminOrderItem[]>([])
const detailV=ref(false); const cur=ref<AdminOrderItem|null>(null)

const qf=reactive({ page:1, size:10, status:undefined as number|undefined, keyword:'' })

const orderStats = computed(() => [
  { key:'all', value:total.value, label:'全部', color:'' },
  { key:'pending', value:orders.value.filter(o=>o.status===0).length, label:'待支付', color:'warn' },
  { key:'done', value:orders.value.filter(o=>o.status===2).length, label:'已完成', color:'done' },
  { key:'ref', value:orders.value.filter(o=>o.status===4).length, label:'已退款', color:'ref' }
])

function sty(s:number):string { const m:Record<number,string>={0:'warn',1:'purple',2:'done',3:'slate',4:'ref'}; return m[s]||'slate' }
function stx(s:number):string { const m:Record<number,string>={0:'待支付',1:'已支付',2:'已完成',3:'已取消',4:'已退款'}; return m[s]||'未知' }

async function load() { loading.value=true
  try { const p:any={ page:qf.page, size:qf.size }; if(qf.status!==undefined) p.status=qf.status; if(qf.keyword.trim()) p.keyword=qf.keyword.trim()
    const r=await get<PageVO<AdminOrderItem>>('/orders/list',{ params:p }); orders.value=r?.records||[]; total.value=r?.total||0
  } catch { orders.value=[]; total.value=0 } finally { loading.value=false }
}
function search() { qf.page=1; load() }
function resetQ() { qf.status=undefined; qf.keyword=''; search() }
function detail(r:AdminOrderItem) { cur.value=r; detailV.value=true }
function goPay(r:AdminOrderItem) { ElMessage.info('跳转支付') }
onMounted(load)
</script>

<style lang="scss" scoped>
.order-page { padding:$space-xl; max-width:1300px; margin:0 auto; }
.page-toolbar { margin-bottom:$space-lg; .toolbar-title{font-size:$font-size-xl;font-weight:700;color:$text-primary;} .toolbar-sub{font-size:$font-size-sm;color:$text-muted;margin-top:2px;} }
.stats-row { display:grid;grid-template-columns:repeat(4,1fr);gap:$space-md;margin-bottom:$space-lg; }
.st-card { padding:$space-lg;background:$bg-surface;border:1px solid $border-color;border-radius:$border-radius;text-align:center;
  .stv{font-size:$font-size-2xl;font-weight:700;color:$text-primary;display:block;margin-bottom:4px;}
  .stl{font-size:$font-size-xs;color:$text-muted;}
  .warn{color:#fbbf24;}.done{color:#34d399;}.ref{color:#f87171;}
}
.panel { background:$bg-surface;border:1px solid $border-color;border-radius:$border-radius-lg;overflow:hidden; }
.panel-bar { padding:$space-md $space-lg;border-bottom:1px solid $border-color; }
.dark-table {
  --el-table-bg-color:transparent;--el-table-tr-bg-color:transparent;
  --el-table-header-bg-color:rgba(255,255,255,0.02);
  --el-table-border-color:rgba(255,255,255,0.06);
  --el-table-text-color:#f1f5f9;--el-table-header-text-color:#94a3b8;
  --el-table-row-hover-bg-color:rgba(255,255,255,0.03);
}
.btn-primary { background:linear-gradient(135deg,$primary-color,$primary-dark);border:none;color:#fff;font-weight:600;border-radius:8px;&:hover{background:linear-gradient(135deg,$primary-light,$primary-color);box-shadow:0 0 16px rgba(99,102,241,0.3);} }
.btn-ghost { background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08);color:$text-primary;border-radius:8px;&:hover{background:rgba(255,255,255,0.06);} }
.money { color:$primary-light;font-weight:600; }
.os-tag { font-size:10px;font-weight:500;padding:2px 8px;border-radius:4px;
  &.os-warn{background:rgba(251,191,36,0.12);color:#fbbf24;}
  &.os-purple{background:rgba(99,102,241,0.12);color:#a5b4fc;}
  &.os-done{background:rgba(16,185,129,0.12);color:#34d399;}
  &.os-ref{background:rgba(239,68,68,0.12);color:#f87171;}
  &.os-slate{background:rgba(148,163,184,0.12);color:#94a3b8;}
}
.act { font-size:$font-size-sm;color:$primary-light;&.act-warn{color:#fbbf24;} }
.pager { padding:$space-md $space-lg;display:flex;justify-content:flex-end; }
</style>
