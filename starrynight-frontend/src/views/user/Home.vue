<template>
  <div class="home-page">
    <section class="welcome-banner">
      <div class="banner-glow"></div>
      <div class="banner-content">
        <h1 class="banner-title">
          欢迎回到<span class="gradient-text">星夜</span>
        </h1>
        <p class="banner-desc">AI 驱动的智能小说创作平台，让每一个创意都成为精彩的故事</p>
        <div class="banner-actions">
          <router-link to="/author">
            <el-button class="btn-primary" size="large">
              <el-icon><EditPen /></el-icon>开始创作            </el-button>
          </router-link>
        </div>
      </div>
      <div class="banner-stats">
        <div class="stat-card" v-for="s in stats" :key="s.label">
          <div class="stat-value">{{ s.value }}</div>
          <div class="stat-label">{{ s.label }}</div>
        </div>
      </div>
    </section>

    <section class="features-section">
      <div class="section-title-row">
        <h2>创作工具箱</h2>
        <p>覆盖从灵感到成品的每一个环节</p>
      </div>
      <div class="features-grid">
        <div
          v-for="(f, i) in features"
          :key="i"
          class="feature-card"
          :style="{ animationDelay: `${i * 0.06}s` }"
        >
          <div class="feature-icon" :class="`icon-${f.color}`">
            <component :is="f.icon" :size="24" />
          </div>
          <div class="feature-info">
            <h3>{{ f.title }}</h3>
            <p>{{ f.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <section class="workflow-section">
      <div class="section-title-row">
        <h2>创作流程</h2>
      </div>
      <div class="workflow-row">
        <div class="workflow-step" v-for="(step, i) in workflow" :key="i">
          <div class="step-number">{{ String(i + 1).padStart(2, '0') }}</div>
          <div class="step-card">
            <component :is="step.icon" :size="32" class="step-icon" />
            <h3>{{ step.title }}</h3>
            <p>{{ step.desc }}</p>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import {
  EditPen, Document, MagicStick, Collection, UserFilled,
  Notebook, Medal, OfficeBuilding, Files, Reading
} from '@element-plus/icons-vue'

const stats = [
  { value: '10,000+', label: '创作者' },
  { value: '50万+', label: 'AI生成字数' },
  { value: '99.9%', label: '服务可用性' },
  { value: '8', label: '创作工具' }
]

const features = [
  { icon: Document, title: 'AI 大纲生成', desc: '根据创意自动生成完整故事大纲，支持多卷多线', color: 'purple' },
  { icon: MagicStick, title: '智能章节创作', desc: 'AI 辅助扩写和续写，保持风格一致', color: 'amber' },
  { icon: Collection, title: '知识库辅助', desc: '构建专属世界观知识库，精准创作', color: 'cyan' },
  { icon: UserFilled, title: '角色库管理', desc: '系统化管理角色，支持关系图谱', color: 'rose' },
  { icon: Notebook, title: '提示词库', desc: '积累优质提示词模板', color: 'emerald' },
  { icon: Medal, title: '工具箱', desc: '金手指、书名生成等实用工具', color: 'indigo' }
]

const workflow = [
  { icon: EditPen, title: '创建作品', desc: '输入基本信息，选择题材风格' },
  { icon: Document, title: '生成大纲', desc: 'AI 智能生成完整故事结构' },
  { icon: MagicStick, title: '创作正文', desc: 'AI 辅助扩写，风格优化' }
]
</script>

<style lang="scss" scoped>
.home-page {
  padding: $space-xl;
  max-width: 1200px;
  margin: 0 auto;
}

.welcome-banner {
  position: relative;
  overflow: hidden;
  padding: $space-2xl;
  border-radius: $border-radius-xl;
  background: linear-gradient(135deg, rgba(99,102,241,0.1), rgba(139,92,246,0.05));
  border: 1px solid rgba(99,102,241,0.12);
  margin-bottom: $space-2xl;
}

.banner-glow {
  position: absolute;
  top: -80px;
  right: -80px;
  width: 300px;
  height: 300px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(99,102,241,0.15), transparent 70%);
  pointer-events: none;
}

.banner-content {
  position: relative;
  margin-bottom: $space-xl;
}

.banner-title {
  font-size: $font-size-2xl;
  font-weight: 800;
  color: $text-primary;
  margin-bottom: $space-sm;
}

.banner-desc {
  font-size: $font-size-md;
  color: $text-secondary;
  max-width: 500px;
}

.banner-actions {
  margin-top: $space-lg;
}

.btn-primary {
  background: linear-gradient(135deg, $primary-color, $primary-dark);
  border: none;
  color: #fff;
  font-weight: 600;
  font-size: 15px;
  padding: 12px 28px;
  border-radius: 12px;
  height: auto;
  &:hover {
    background: linear-gradient(135deg, $primary-light, $primary-color);
    box-shadow: 0 0 28px rgba(99,102,241,0.35);
    transform: translateY(-1px);
  }
}

.banner-stats {
  display: flex;
  gap: $space-xl;
  position: relative;
}

.stat-card {
  display: flex;
  flex-direction: column;

  .stat-value {
    font-size: $font-size-xl;
    font-weight: 700;
    color: $text-primary;
  }
  .stat-label {
    font-size: $font-size-xs;
    color: $text-muted;
  }
}

.features-section {
  margin-bottom: $space-2xl;
}

.section-title-row {
  margin-bottom: $space-lg;

  h2 {
    font-size: $font-size-xl;
    font-weight: 700;
    color: $text-primary;
    margin-bottom: 4px;
  }
  p {
    font-size: $font-size-sm;
    color: $text-muted;
  }
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: $space-md;
}

.feature-card {
  padding: $space-lg;
  background: $bg-surface;
  border: 1px solid $border-color;
  border-radius: $border-radius-lg;
  display: flex;
  flex-direction: column;
  gap: $space-md;
  transition: all $transition-normal;
  animation: fadeInUp 0.5s ease both;

  &:hover {
    border-color: rgba(99,102,241,0.2);
    background: $bg-elevated;
    transform: translateY(-2px);
  }
}

.feature-icon {
  width: 44px;
  height: 44px;
  border-radius: $border-radius-sm;
  display: flex;
  align-items: center;
  justify-content: center;

  &.icon-purple  { background: rgba(99,102,241,0.12); color: #a5b4fc; }
  &.icon-amber   { background: rgba(251,191,36,0.12); color: #fbbf24; }
  &.icon-cyan    { background: rgba(6,182,212,0.12); color: #22d3ee; }
  &.icon-rose    { background: rgba(244,114,182,0.12); color: #f472b6; }
  &.icon-emerald { background: rgba(16,185,129,0.12); color: #34d399; }
  &.icon-indigo  { background: rgba(99,102,241,0.12); color: #a5b4fc; }
}

.feature-info {
  h3 { font-size: $font-size-md; font-weight: 600; color: $text-primary; margin-bottom: 6px; }
  p  { font-size: $font-size-sm; color: $text-secondary; line-height: 1.6; }
}

.workflow-row {
  display: flex;
  gap: $space-lg;
}

.workflow-step {
  flex: 1;
  text-align: center;
  position: relative;
}

.step-number {
  font-size: 60px;
  font-weight: 800;
  color: rgba(99,102,241,0.08);
  letter-spacing: -2px;
  margin-bottom: -28px;
}

.step-card {
  padding: $space-xl;
  background: $bg-surface;
  border: 1px solid $border-color;
  border-radius: $border-radius-lg;
  position: relative;
  z-index: 1;

  .step-icon { color: $primary-light; margin-bottom: $space-sm; }
  h3 { font-size: $font-size-md; font-weight: 600; color: $text-primary; margin-bottom: 8px; }
  p  { font-size: $font-size-sm; color: $text-secondary; line-height: 1.6; }
}

@media (max-width: 900px) {
  .features-grid { grid-template-columns: repeat(2, 1fr); }
  .workflow-row { flex-direction: column; }
}

@media (max-width: 600px) {
  .features-grid { grid-template-columns: 1fr; }
}
</style>
