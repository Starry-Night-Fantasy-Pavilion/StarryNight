<template>
  <div class="profile-page">
    <div class="page-toolbar">
      <div>
        <h2 class="toolbar-title">个人中心</h2>
        <p class="toolbar-sub">管理你的账户和偏好设置</p>
      </div>
    </div>

    <div class="profile-layout">
      <aside class="pf-side">
        <div class="pf-card">
          <div class="pf-avatar">
            <el-avatar :size="80" :src="profile.avatar">{{ profile.username?.charAt(0) }}</el-avatar>
            <h2>{{ profile.nickname || profile.username }}</h2>
            <p class="pf-at">@{{ profile.username }}</p>
            <span class="pf-level" :class="`lvl-${memberTagType}`">{{ memberText }}</span>
          </div>
          <div class="pf-stats">
            <div class="pf-stat" v-for="s in statItems" :key="s.key">
              <div class="pfs-icon" :class="`s-${s.color}`"><component :is="s.icon" :size="18" /></div>
              <span class="pfs-val">{{ s.value }}</span>
              <span class="pfs-lbl">{{ s.label }}</span>
            </div>
          </div>
        </div>
      </aside>

      <div class="pf-main">
        <el-alert
          v-if="showRealnameBanner"
          type="warning"
          :closable="false"
          show-icon
          class="pf-realname-alert"
        >
          <template #title>
            <span>未完成实名核验前无法导出作品内容。</span>
          </template>
          <template #default>
            <p v-if="faceModeNeedsIdentity" class="pf-rn-tip">
              请先在下方「实名信息」填写姓名与身份证号并保存，再发起人脸核验。
            </p>
            <p v-if="needsPayRealnameFee" class="pf-rn-tip">
              站点已开启实名认证费（{{ formatYuan(profile.realnameFeeAmountYuan) }} 元），需先通过易支付完成付款后再发起人脸核验。
            </p>
            <div v-if="showRealnameBanner" class="pf-rn-actions">
              <el-button
                v-if="needsPayRealnameFee"
                class="btn-primary mt-2"
                size="small"
                type="warning"
                :loading="realnameFeePaying"
                @click="payRealnameFee"
              >
                支付认证费
              </el-button>
              <el-button
                v-if="profile.realNameVerifyPending && !needsPayRealnameFee"
                class="btn-primary mt-2"
                size="small"
                :loading="realnameStarting"
                @click="startRealnameFace"
              >
                前往核验
              </el-button>
            </div>
          </template>
        </el-alert>

        <div class="pf-panel">
          <el-form ref="formRef" :model="editForm" :rules="formRules" label-position="top" size="large" @submit.prevent="save">
            <template v-if="showRealnameBanner">
              <h3>实名信息</h3>
              <template v-if="!profile.hasRealNameOnFile">
                <p class="pf-rn-tip">请填写与证件一致的姓名与 18 位身份证号，保存后与账号绑定。</p>
                <el-row :gutter="16">
                  <el-col :span="12">
                    <el-form-item label="真实姓名">
                      <el-input v-model="editForm.realName" maxlength="32" placeholder="真实姓名" />
                    </el-form-item>
                  </el-col>
                  <el-col :span="12">
                    <el-form-item label="身份证号">
                      <el-input v-model="editForm.idCardNo" maxlength="18" placeholder="18 位身份证号码" />
                    </el-form-item>
                  </el-col>
                </el-row>
              </template>
              <p v-else class="pf-rn-tip">
                证件信息已保存，请点击上方「前往核验」完成支付宝或喵雨欣核验流程。
              </p>
              <el-divider class="pf-rn-divider" />
            </template>

            <h3>编辑资料</h3>
            <el-row :gutter="16">
              <el-col :span="12">
                <el-form-item label="昵称" prop="nickname">
                  <el-input v-model="editForm.nickname" placeholder="设置昵称" maxlength="30" show-word-limit />
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item label="邮箱" prop="email">
                  <el-input v-model="editForm.email" placeholder="绑定邮箱" />
                </el-form-item>
              </el-col>
            </el-row>
            <el-form-item label="头像链接">
              <el-input v-model="editForm.avatar" placeholder="输入头像图片URL" />
              <el-avatar v-if="editForm.avatar" :size="48" :src="editForm.avatar" class="mt-3" />
            </el-form-item>
            <div class="pf-actions">
              <el-button class="btn-primary" :loading="saving" @click="save">保存</el-button>
              <el-button class="btn-ghost" @click="resetForm">重置</el-button>
            </div>
          </el-form>
        </div>

        <div class="pf-panel">
          <h3>安全设置</h3>
          <div class="sec-row">
            <div>
              <span class="sec-label">登录密码</span>
              <span class="sec-desc">定期更换密码提高安全性</span>
            </div>
            <el-button class="btn-ghost" @click="showPwd = true">修改</el-button>
          </div>
          <div class="sec-divider"></div>
          <div class="sec-row">
            <div>
              <span class="sec-label">账号注销</span>
              <span class="sec-desc sec-danger">注销后数据不可恢复</span>
            </div>
            <el-button class="btn-danger" @click="confirmDel">注销</el-button>
          </div>
        </div>
      </div>
    </div>

    <el-dialog v-model="showPwd" title="修改密码" width="400px">
      <el-form ref="pwdRef" :model="pwdForm" :rules="pwdRules" label-position="top" size="large">
        <el-form-item label="当前密码" prop="oldPwd">
          <el-input v-model="pwdForm.oldPwd" type="password" show-password />
        </el-form-item>
        <el-form-item label="新密码" prop="newPwd">
          <el-input v-model="pwdForm.newPwd" type="password" show-password />
        </el-form-item>
        <el-form-item label="确认密码" prop="cfmPwd">
          <el-input v-model="pwdForm.cfmPwd" type="password" show-password />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showPwd = false">取消</el-button>
        <el-button class="btn-primary" :loading="pwdSaving" @click="changePwd">确认</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import type { FormInstance, FormRules } from 'element-plus'
import { get, put, post } from '@/utils/request'
import { useUserSessionStore } from '@/stores/auth'
import type { UserProfile } from '@/types/api'
import { getUserBalance } from '@/api/billing'
import { createRealnameFeePay, startRealnameVerification } from '@/api/realname'
import { MagicStick, Coin, Document } from '@element-plus/icons-vue'

const auth = useUserSessionStore()
const route = useRoute()
const router = useRouter()
const formRef = ref<FormInstance>()
const pwdRef = ref<FormInstance>()
const saving = ref(false); const pwdSaving = ref(false); const showPwd = ref(false); const realnameStarting = ref(false); const realnameFeePaying = ref(false)

const profile = reactive<UserProfile>({
  userId: 0,
  username: '',
  nickname: '',
  email: '',
  phone: '',
  avatar: '',
  memberLevel: 0,
  points: 0,
  realNameVerified: 0,
  realNameVerifyPending: false,
  realNameGateEnabled: false,
  hasRealNameOnFile: false,
  realNameVerifyProvider: 'basic',
  realnameFeeEnabled: false,
  realnameFeeAmountYuan: undefined,
  realnameFeeCashPaid: undefined
})
const wallet = reactive({ freeQuota:0, platformCurrency:0 })

const editForm = reactive({ nickname: '', email: '', phone: '', avatar: '', realName: '', idCardNo: '' })

const ID18 = /^[1-9]\d{5}(18|19|20)\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])\d{3}[0-9Xx]$/

const isFaceProvider = computed(() => {
  const p = (profile.realNameVerifyProvider || 'basic').toLowerCase()
  return p === 'alipay' || p === 'ovooa'
})

const showRealnameBanner = computed(
  () => profile.realNameGateEnabled === true && profile.realNameVerified !== 1
)

const faceModeNeedsIdentity = computed(
  () => showRealnameBanner.value && isFaceProvider.value && !profile.hasRealNameOnFile
)

const needsPayRealnameFee = computed(() => {
  if (!showRealnameBanner.value || !isFaceProvider.value) return false
  if (!profile.realnameFeeEnabled) return false
  const y = Number(profile.realnameFeeAmountYuan ?? 0)
  if (!y || y <= 0) return false
  return profile.realnameFeeCashPaid !== true
})

function formatYuan(v: unknown): string {
  const n = Number(v)
  if (!Number.isFinite(n) || n <= 0) return '—'
  return n.toFixed(2)
}
const pwdForm = reactive({ oldPwd:'',newPwd:'',cfmPwd:'' })

const formRules: FormRules = {
  email: [{ type:'email', message:'请输入正确邮箱', trigger:'blur' }]
}
const pwdRules: FormRules = {
  oldPwd: [{ required:true, message:'请输入当前密码', trigger:'blur' }],
  newPwd: [{ required:true, min:6, message:'至少6位', trigger:'blur' }],
  cfmPwd: [{ required:true, message:'请确认密码', trigger:'blur' },{ validator:(_r,v,cb)=>v!==pwdForm.newPwd?cb(new Error('两次不一致')):cb(), trigger:'blur' }]
}

const memberText = computed(() => { const m:Record<number,string>={0:'普通用户',1:'青铜',2:'VIP',3:'SVIP',4:'钻石'}; return m[profile.memberLevel]||'普通用户' })
const memberTagType = computed(() => { const m:Record<number,string>={0:'default',1:'bronze',2:'silver',3:'gold',4:'diamond'}; return m[profile.memberLevel]||'default' })

const statItems = computed(() => [
  { key:'quota', icon:MagicStick, label:'创作点', color:'purple', value:wallet.freeQuota.toLocaleString() },
  { key:'coin', icon:Coin, label:'星夜币', color:'amber', value:Number(wallet.platformCurrency).toLocaleString() },
  { key:'novel', icon:Document, label:'作品', color:'emerald', value:novelCount.value }
])
const novelCount = ref(0)

async function loadData() {
  try {
    const [r, b] = await Promise.all([get<UserProfile>('/user/profile'), getUserBalance(profile.userId||auth.userInfo?.id||0).catch(()=>null)])
    if (r) {
      Object.assign(profile, r)
      editForm.nickname = r.nickname || ''
      editForm.email = r.email || ''
      editForm.avatar = r.avatar || ''
      editForm.realName = ''
      editForm.idCardNo = ''
    }
    if (b) { wallet.freeQuota=b.freeQuota??0; wallet.platformCurrency=Number(b.platformCurrency??0) }
    novelCount.value = (await get<any>('/novel?page=1&size=1').catch(()=>null))?.total || 0
  } catch {}
}

async function save() {
  if (!formRef.value) return
  await formRef.value.validate(async (ok) => {
    if (!ok) return
    if (profile.realNameGateEnabled && profile.realNameVerified !== 1) {
      const rn = editForm.realName?.trim() ?? ''
      const idc = editForm.idCardNo?.trim() ?? ''
      if (rn || idc) {
        if (!rn || rn.length < 2) {
          ElMessage.warning('请填写真实姓名（2–32 个字符）')
          return
        }
        if (!idc || !ID18.test(idc)) {
          ElMessage.warning('请填写有效的 18 位身份证号码')
          return
        }
      }
    }
    saving.value = true
    try {
      const d: Record<string, string> = {}
      if (editForm.nickname) d.nickname = editForm.nickname
      if (editForm.email) d.email = editForm.email
      if (editForm.avatar) d.avatar = editForm.avatar
      if (profile.realNameGateEnabled && profile.realNameVerified !== 1) {
        const rn = editForm.realName?.trim() ?? ''
        const idc = editForm.idCardNo?.trim() ?? ''
        if (rn && idc) {
          d.realName = rn
          d.idCardNo = idc
        }
      }
      await put('/user/profile', d)
      ElMessage.success('已更新')
      if (auth.userInfo) {
        auth.userInfo.nickname = editForm.nickname
        auth.userInfo.avatar = editForm.avatar
      }
      await loadData()
      await auth.fetchProfile().catch(() => {})
    } catch {
      ElMessage.error('保存失败')
    } finally {
      saving.value = false
    }
  })
}

function resetForm() {
  editForm.nickname = profile.nickname || ''
  editForm.email = profile.email || ''
  editForm.avatar = profile.avatar || ''
  editForm.realName = ''
  editForm.idCardNo = ''
}

async function changePwd() {
  if (!pwdRef.value) return
  await pwdRef.value.validate(async (ok)=>{ if (!ok) return
    pwdSaving.value = true
    try {
      await post('/user/change-password', { oldPassword:pwdForm.oldPwd, newPassword:pwdForm.newPwd })
      ElMessage.success('密码已修改')
      showPwd.value = false
      pwdForm.oldPwd=''; pwdForm.newPwd=''; pwdForm.cfmPwd=''
    } catch { ElMessage.error('修改失败') } finally { pwdSaving.value = false }
  })
}

async function confirmDel() {
  try {
    await ElMessageBox.confirm('确定注销？不可恢复！','确认',{ type:'warning' })
    await post('/user/delete-account')
    ElMessage.success('已注销'); auth.logout()
  } catch {}
}

async function payRealnameFee() {
  const token = auth.accessToken
  if (!token) {
    ElMessage.warning('请先登录')
    return
  }
  realnameFeePaying.value = true
  try {
    const vo = await createRealnameFeePay(token, { payType: 'alipay' })
    const url = vo.payUrl?.trim()
    if (!url) {
      ElMessage.error('未返回支付地址')
      return
    }
    window.location.href = url
  } catch (e) {
    ElMessage.error(e instanceof Error ? e.message : '创建支付失败')
  } finally {
    realnameFeePaying.value = false
  }
}

async function startRealnameFace() {
  const token = auth.accessToken
  if (!token) {
    ElMessage.warning('请先登录')
    return
  }
  if (isFaceProvider.value && !profile.hasRealNameOnFile) {
    ElMessage.warning('请先在下方填写真实姓名与身份证号并保存')
    return
  }
  realnameStarting.value = true
  try {
    const vo = await startRealnameVerification(token)
    const url = vo.redirectUrl?.trim()
    if (!url) {
      ElMessage.error('未返回核验地址')
      return
    }
    window.location.href = url
  } catch (e) {
    ElMessage.error(e instanceof Error ? e.message : '发起核验失败')
  } finally {
    realnameStarting.value = false
  }
}

onMounted(() => {
  if (route.query.realnameFeePaid === '1') {
    ElMessage.success('若支付成功，请发起人脸核验')
    router.replace({ path: route.path, query: {} })
  }
  void loadData()
})
</script>

<style lang="scss" scoped>
.profile-page { padding:$space-xl; max-width:1100px; margin:0 auto; }
.page-toolbar { margin-bottom:$space-lg; .toolbar-title{font-size:$font-size-xl;font-weight:700;color:$text-primary;} .toolbar-sub{font-size:$font-size-sm;color:$text-muted;margin-top:2px;} }
.profile-layout { display:grid; grid-template-columns: 280px 1fr; gap:$space-lg; align-items:start; }

.pf-realname-alert {
  margin-bottom: $space-lg;
}
.mt-2 {
  margin-top: $space-sm;
}

.pf-rn-actions {
  display: flex;
  flex-wrap: wrap;
  gap: $space-sm;
  align-items: center;
}

.pf-rn-tip {
  font-size: $font-size-sm;
  color: $text-secondary;
  margin: 0 0 $space-sm;
  line-height: 1.5;
}

.pf-rn-divider {
  margin: $space-lg 0;
}

.pf-card {
  background:$bg-surface; border:1px solid $border-color; border-radius:$border-radius-lg; padding:$space-xl; text-align:center;
}
.pf-avatar {
  margin-bottom:$space-lg;
  h2 { font-size:$font-size-xl;font-weight:700;color:$text-primary;margin-top:$space-md; }
  .pf-at { font-size:$font-size-sm;color:$text-muted; }
}
.pf-level {
  display:inline-block; margin-top:$space-sm; font-size:11px;font-weight:600; padding:4px 14px;border-radius:12px;
  &.lvl-default{background:rgba(148,163,184,0.12);color:#94a3b8;}
  &.lvl-bronze{background:rgba(180,83,9,0.15);color:#d97706;}
  &.lvl-silver{background:rgba(148,163,184,0.15);color:#94a3b8;}
  &.lvl-gold{background:rgba(251,191,36,0.15);color:#f59e0b;}
  &.lvl-diamond{background:linear-gradient(135deg,rgba(6,182,212,0.15),rgba(99,102,241,0.15));color:#22d3ee;}
}

.pf-stats { display:flex;justify-content:center;gap:$space-lg;border-top:1px solid $border-color;padding-top:$space-lg; }
.pf-stat { display:flex;flex-direction:column;align-items:center;gap:6px; }
.pfs-icon { width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;
  &.s-purple{background:linear-gradient(135deg,#6366f1,#818cf8);color:#fff;}
  &.s-amber{background:linear-gradient(135deg,#f59e0b,#fbbf24);color:#fff;}
  &.s-emerald{background:linear-gradient(135deg,#10b981,#34d399);color:#fff;}
}
.pfs-val { font-size:$font-size-lg;font-weight:700;color:$text-primary; }
.pfs-lbl { font-size:$font-size-xs;color:$text-muted; }

.pf-main { display:flex;flex-direction:column;gap:$space-lg; }
.pf-panel {
  background:$bg-surface; border:1px solid $border-color; border-radius:$border-radius-lg; padding:$space-xl;
  h3 { font-size:$font-size-md;font-weight:600;color:$text-primary;margin-bottom:$space-lg; }
}

:deep(.el-input__wrapper){ background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08);border-radius:10px;box-shadow:none; }
:deep(.el-input__wrapper:hover){ border-color:rgba(255,255,255,0.12); }
:deep(.el-input__wrapper.is-focus){ border-color:$primary-color;box-shadow:0 0 0 3px rgba(99,102,241,0.08); }

.pf-actions { display:flex;gap:$space-sm; }
.btn-primary {
  background:linear-gradient(135deg,$primary-color,$primary-dark); border:none; color:#fff; font-weight:600; border-radius:10px; padding:10px 24px;
  &:hover{background:linear-gradient(135deg,$primary-light,$primary-color);box-shadow:0 0 20px rgba(99,102,241,0.35);transform:translateY(-1px);}
}
.btn-ghost {
  background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); color:$text-primary; border-radius:10px;
  &:hover{background:rgba(255,255,255,0.06);border-color:rgba(255,255,255,0.12);}
}
.btn-danger { border:1px solid rgba(239,68,68,0.3);color:#ef4444;border-radius:10px;&:hover{background:rgba(239,68,68,0.08);border-color:rgba(239,68,68,0.5);} }
.sec-row { display:flex;justify-content:space-between;align-items:center;padding:$space-md 0; }
.sec-label { font-size:$font-size-sm;font-weight:500;color:$text-primary;display:block; }
.sec-desc { font-size:$font-size-xs;color:$text-muted;display:block; }
.sec-danger { color:#ef4444; }
.sec-divider { height:1px;background:$border-color; }
.mt-3 { margin-top:8px; }
</style>
