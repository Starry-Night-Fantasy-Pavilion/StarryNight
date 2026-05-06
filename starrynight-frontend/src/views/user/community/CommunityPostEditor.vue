<template>
  <div class="community-editor page-container">
    <el-page-header @back="goBack" :title="isEdit ? '编辑帖子' : '发布帖子'" />

    <el-form ref="formRef" :model="form" :rules="rules" label-width="72px" class="editor-form">
      <el-form-item label="标题" prop="title">
        <el-input v-model="form.title" maxlength="200" show-word-limit placeholder="可选" clearable />
      </el-form-item>
      <el-form-item label="正文" prop="content">
        <el-input v-model="form.content" type="textarea" :rows="14" maxlength="20000" show-word-limit placeholder="分享你的想法…" />
      </el-form-item>
      <el-form-item>
        <el-button type="primary" :loading="submitting" @click="submit">{{ isEdit ? '保存' : '提交审核' }}</el-button>
        <el-button v-if="isEdit" type="danger" plain :loading="deleting" @click="onDelete">删除</el-button>
      </el-form-item>
    </el-form>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import type { FormInstance, FormRules } from 'element-plus'
import {
  createCommunityPost,
  deleteCommunityPost,
  getCommunityPostForAuthor,
  updateCommunityPost
} from '@/api/community'

const route = useRoute()
const router = useRouter()

const formRef = ref<FormInstance>()
const submitting = ref(false)
const deleting = ref(false)

const editId = computed(() => {
  const raw = route.params.id
  const n = typeof raw === 'string' ? Number(raw) : Number(Array.isArray(raw) ? raw[0] : raw)
  return Number.isFinite(n) && n > 0 ? n : null
})

const isEdit = computed(() => editId.value != null)

const form = reactive({
  title: '',
  content: ''
})

const rules: FormRules = {
  content: [{ required: true, message: '请输入正文', trigger: 'blur' }]
}

function goBack() {
  if (isEdit.value && editId.value) {
    router.push(`/community/post/${editId.value}`)
  } else {
    router.push('/community')
  }
}

onMounted(async () => {
  if (!isEdit.value) return
  try {
    const p = await getCommunityPostForAuthor(editId.value!)
    if (p.auditStatus === 1) {
      ElMessage.warning('已通过审核的帖子不能在此编辑')
      router.replace(`/community/post/${editId.value}`)
      return
    }
    form.title = p.title || ''
    form.content = p.content || ''
  } catch {
    router.replace('/community')
  }
})

async function submit() {
  await formRef.value?.validate().catch(() => Promise.reject())
  submitting.value = true
  try {
    if (isEdit.value && editId.value) {
      await updateCommunityPost(editId.value, {
        title: form.title.trim() || undefined,
        content: form.content.trim()
      })
      ElMessage.success('已保存，重新进入审核队列')
      router.push(`/community/post/${editId.value}`)
    } else {
      const created = await createCommunityPost({
        title: form.title.trim() || undefined,
        content: form.content.trim()
      })
      ElMessage.success('已提交，请等待审核')
      if (created?.id) {
        router.push(`/community/post/${created.id}`)
      } else {
        router.push('/community')
      }
    }
  } finally {
    submitting.value = false
  }
}

async function onDelete() {
  if (!editId.value) return
  try {
    await ElMessageBox.confirm('确定删除该帖？删除后不可恢复。', '删除', { type: 'warning' })
  } catch {
    return
  }
  deleting.value = true
  try {
    await deleteCommunityPost(editId.value)
    ElMessage.success('已删除')
    router.push('/community')
  } finally {
    deleting.value = false
  }
}
</script>

<style scoped lang="scss">
.community-editor {
  max-width: 720px;
  margin: 0 auto;
  padding: $space-lg;
}

.editor-form {
  margin-top: $space-lg;
}
</style>
