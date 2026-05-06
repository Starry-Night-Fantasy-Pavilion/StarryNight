<template>
  <div class="landing">
    <div class="landing-bg" aria-hidden="true">
      <div class="landing-bg__glow landing-bg__glow--1" />
      <div class="landing-bg__glow landing-bg__glow--2" />
      <div class="landing-bg__stars" />
    </div>

    <header class="landing-nav-outer">
      <div class="landing-nav">
      <router-link to="/" class="landing-nav__brand">
        <span v-if="portal.siteLogoUrl" class="landing-nav__logo-img">
          <img :src="portal.siteLogoUrl" alt="" width="40" height="40" referrerpolicy="no-referrer" />
        </span>
        <span v-else class="landing-nav__logo" aria-hidden="true">
          <svg viewBox="0 0 32 32" width="40" height="40" fill="none">
            <circle cx="16" cy="16" r="14" stroke="url(#lLogoGrad)" stroke-width="1.5" opacity="0.45" />
            <circle cx="16" cy="16" r="4" fill="url(#lLogoGrad)" />
            <circle cx="6" cy="8" r="1.2" fill="url(#lLogoGrad)" opacity="0.55" />
            <circle cx="26" cy="10" r="0.8" fill="url(#lLogoGrad)" opacity="0.35" />
            <circle cx="10" cy="24" r="0.8" fill="url(#lLogoGrad)" opacity="0.45" />
            <circle cx="24" cy="22" r="1" fill="url(#lLogoGrad)" opacity="0.35" />
            <defs>
              <linearGradient id="lLogoGrad" x1="0" y1="0" x2="32" y2="32">
                <stop offset="0%" stop-color="#a78bfa" />
                <stop offset="50%" stop-color="#818cf8" />
                <stop offset="100%" stop-color="#6366f1" />
              </linearGradient>
            </defs>
          </svg>
        </span>
        <span class="landing-nav__title">星夜</span>
        <span class="landing-nav__subtitle">{{ portal.siteName }}</span>
      </router-link>

      <nav class="landing-nav__links" aria-label="页面导航">
        <a href="#features" class="landing-nav__link">产品能力</a>
        <a href="#scenes" class="landing-nav__link">使用场景</a>
        <router-link to="/bookstore" class="landing-nav__link">星夜书库</router-link>
        <router-link to="/community" class="landing-nav__link">社区</router-link>
      </nav>

      <div class="landing-nav__actions">
        <router-link to="/auth/login">
          <el-button text class="landing-nav__btn-text">登录</el-button>
        </router-link>
        <router-link to="/auth/register">
          <el-button type="primary" class="landing-nav__btn-primary">免费注册</el-button>
        </router-link>
      </div>
      </div>
    </header>

    <main>
      <section class="hero">
        <p class="hero__eyebrow">AI 驱动的长篇小说创作工作台</p>
        <h1 class="hero__title">
          让每一缕灵感，<br /><span class="hero__title-accent">连成你的星夜</span>
        </h1>
        <p class="hero__lead">
          从大纲、章节到角色与世界观，星夜把结构化的创作流程与可控的 AI 辅助结合在一起，帮助你稳定产出、保持风格、管理长篇叙事。
        </p>
        <div class="hero__cta">
          <router-link to="/auth/register">
            <el-button type="primary" size="large" class="hero__btn-main">立即开始创作</el-button>
          </router-link>
          <router-link to="/bookstore">
            <el-button size="large" class="hero__btn-ghost">逛逛星夜书库</el-button>
          </router-link>
        </div>
        <ul class="hero__metrics" aria-label="产品亮点">
          <li v-for="m in metrics" :key="m.label">
            <span class="hero__metric-value">{{ m.value }}</span>
            <span class="hero__metric-label">{{ m.label }}</span>
          </li>
        </ul>
      </section>

      <section id="features" class="section features">
        <div class="section__head">
          <h2 class="section__title">为长篇创作而生的能力矩阵</h2>
          <p class="section__desc">覆盖「构思—结构—写作—沉淀」全链路，工具之间数据互通，减少反复复制粘贴与上下文丢失。</p>
        </div>
        <div class="features__grid">
          <article
            v-for="(card, i) in featureCards"
            :key="card.title"
            class="feature-card"
            :style="{ animationDelay: `${i * 0.05}s` }"
          >
            <div class="feature-card__icon" :class="`tone-${card.tone}`">
              <component :is="card.icon" :size="28" />
            </div>
            <h3 class="feature-card__title">{{ card.title }}</h3>
            <p class="feature-card__text">{{ card.text }}</p>
          </article>
        </div>
      </section>

      <section id="scenes" class="section scenes">
        <div class="section__head">
          <h2 class="section__title">三种典型路径，同一套星夜体验</h2>
          <p class="section__desc">无论你是网文作者、同人写手还是内容团队，都可以用同一平台完成协作与交付。</p>
        </div>
        <div class="scenes__row">
          <article v-for="s in scenes" :key="s.title" class="scene-card">
            <h3 class="scene-card__title">{{ s.title }}</h3>
            <p class="scene-card__text">{{ s.text }}</p>
            <ul class="scene-card__bullets">
              <li v-for="b in s.bullets" :key="b">{{ b }}</li>
            </ul>
          </article>
        </div>
      </section>

      <section class="section cta-band">
        <div class="cta-band__inner">
          <div>
            <h2 class="cta-band__title">准备好点亮你的第一颗星了吗？</h2>
            <p class="cta-band__text">注册即可使用创作中心、知识库与社区；会员体系支持按用量与权益灵活扩展。</p>
          </div>
          <div class="cta-band__actions">
            <router-link to="/auth/register">
              <el-button type="primary" size="large" class="cta-band__btn">创建账号</el-button>
            </router-link>
            <router-link to="/auth/login">
              <el-button size="large" plain class="cta-band__btn-secondary">已有账号登录</el-button>
            </router-link>
          </div>
        </div>
      </section>
    </main>

    <footer class="landing-footer">
      <div class="landing-footer__row">
        <span class="landing-footer__brand">星夜 · {{ portal.siteName }}</span>
        <div class="landing-footer__links">
          <router-link to="/bookstore">星夜书库</router-link>
          <router-link to="/community">社区</router-link>
          <router-link to="/auth/login">登录</router-link>
        </div>
      </div>
      <div v-if="showIcp" class="landing-footer__extra landing-footer__icp">
        <a v-if="icpHref" :href="icpHref" target="_blank" rel="noopener noreferrer">{{ icpDisplayText }}</a>
        <span v-else>{{ icpDisplayText }}</span>
      </div>
      <div v-if="contactLines.length" class="landing-footer__extra landing-footer__contact">
        <div v-for="(line, idx) in contactLines" :key="idx" class="landing-footer__contact-row">
          <span class="landing-footer__contact-label">{{ line.label }}</span>
          <a v-if="line.href" class="landing-footer__contact-val" :href="line.href" target="_blank" rel="noopener noreferrer">{{ line.text }}</a>
          <span v-else class="landing-footer__contact-val">{{ line.text }}</span>
        </div>
      </div>
      <div v-if="friendLinks.length" class="landing-footer__extra landing-footer__friends">
        <span class="landing-footer__friends-title">友情链接</span>
        <span v-for="(fl, idx) in friendLinks" :key="idx" class="landing-footer__friend-item">
          <a :href="fl.url" target="_blank" rel="noopener noreferrer">{{ fl.name }}</a>
          <span v-if="idx < friendLinks.length - 1" class="landing-footer__friend-sep">·</span>
        </span>
      </div>
      <div v-if="showSponsor" class="landing-footer__extra landing-footer__sponsor">
        <iframe
          v-if="sponsorHtmlUrl"
          class="landing-footer__sponsor-frame"
          :src="sponsorHtmlUrl"
          title="赞助鸣谢"
          loading="lazy"
          referrerpolicy="no-referrer-when-downgrade"
        />
        <pre v-else class="landing-footer__sponsor-pre">{{ sponsorText }}</pre>
      </div>
      <p class="landing-footer__copy">© {{ year }} StarryNight. 以技术与设计，陪伴认真讲故事的人。</p>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import {
  Document, MagicStick, Collection, UserFilled, Notebook,
  DataLine, ChatDotRound
} from '@element-plus/icons-vue'
import { usePortalPublicStore } from '@/stores/portalPublic'

const portal = usePortalPublicStore()
const year = new Date().getFullYear()

const showIcp = computed(
  () =>
    portal.config.footerIcpEnabled &&
    Boolean((portal.config.footerIcpRecord || '').trim() || (portal.config.footerIcpUrl || '').trim())
)
const icpHref = computed(() => (portal.config.footerIcpUrl || '').trim())
const icpDisplayText = computed(() => {
  const r = (portal.config.footerIcpRecord || '').trim()
  if (r) return r
  if (icpHref.value) return '备案查询'
  return ''
})

const contactLines = computed(() => {
  if (!portal.config.footerContactEnabled) return []
  const raw = portal.config.footerContactLines || []
  return raw.filter((l) => (l.label || '').trim() && (l.text || '').trim())
})

const friendLinks = computed(() => {
  if (!portal.config.footerFriendLinksEnabled) return []
  const raw = portal.config.footerFriendLinks || []
  return raw.filter((l) => (l.name || '').trim() && (l.url || '').trim())
})

const sponsorHtmlUrl = computed(() => (portal.config.footerSponsorHtmlUrl || '').trim())
const sponsorText = computed(() => (portal.config.footerSponsorText || '').trim())
const showSponsor = computed(
  () => portal.config.footerSponsorEnabled && Boolean(sponsorHtmlUrl.value || sponsorText.value)
)

const metrics = [
  { value: '全流程', label: '大纲 · 章节 · 角色 · 时间线' },
  { value: '可控生成', label: '知识库与提示词协同' },
  { value: '读写一体', label: '创作端与书城打通' }
]

const featureCards = [
  { icon: Document, title: '智能大纲与卷章结构', text: '用结构化视图管理卷、章与情节节奏，长文不迷路。', tone: 'violet' },
  { icon: MagicStick, title: '章节级 AI 辅助', text: '续写、扩写与润色在同一作品语境下完成，减少风格漂移。', tone: 'amber' },
  { icon: Collection, title: '世界观知识库', text: '沉淀设定与资料，创作时按需引用，保持设定一致。', tone: 'cyan' },
  { icon: UserFilled, title: '角色与关系', text: '集中维护角色档案与关系，支撑多线叙事。', tone: 'rose' },
  { icon: DataLine, title: '叙事节奏与时间线', text: '从时间轴与节奏面板把控伏笔、高潮与信息释放。', tone: 'indigo' },
  { icon: Notebook, title: '提示词与工具箱', text: '沉淀可复用提示词模板，搭配实用小工具提升效率。', tone: 'emerald' },
  { icon: ChatDotRound, title: '星夜社区', text: '与同好交流创作心得，发现灵感与反馈。', tone: 'violet' }
]

const scenes = [
  {
    title: '网文与连载作者',
    text: '稳定日更、管理长线伏笔与多角色视角。',
    bullets: ['卷章结构清晰', '版本与分支辅助试错', '会员与订单中心透明']
  },
  {
    title: '世界观向与同人创作',
    text: '把设定写进知识库，让每一次生成都有据可依。',
    bullets: ['资料结构化', '风格拓展与润色', '社区分享与讨论']
  },
  {
    title: '内容团队与工作室',
    text: '统一平台管理素材、提示词与作品资产。',
    bullets: ['多模块一站式', '通知与成长体系', '书城分发与阅读数据']
  }
]
</script>

<style lang="scss" scoped>
.landing {
  position: relative;
  width: 100%;
  min-width: 0;
  min-height: 100vh;
  color: $text-primary;
  background: $bg-canvas;
}

.landing-bg {
  position: fixed;
  inset: 0;
  pointer-events: none;
  z-index: 0;
  overflow: hidden;
}

.landing-bg__glow {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  opacity: 0.55;
}
.landing-bg__glow--1 {
  width: min(52vw, 520px);
  height: min(52vw, 520px);
  top: -12%;
  right: -8%;
  background: radial-gradient(circle, rgba(99, 102, 241, 0.35), transparent 70%);
}
.landing-bg__glow--2 {
  width: min(48vw, 480px);
  height: min(48vw, 480px);
  bottom: -18%;
  left: -10%;
  background: radial-gradient(circle, rgba(139, 92, 246, 0.22), transparent 72%);
}

.landing-bg__stars {
  position: absolute;
  inset: 0;
  opacity: 0.35;
  background-image:
    radial-gradient(1px 1px at 10% 20%, rgba(255, 255, 255, 0.5), transparent),
    radial-gradient(1px 1px at 30% 70%, rgba(255, 255, 255, 0.35), transparent),
    radial-gradient(1px 1px at 78% 35%, rgba(255, 255, 255, 0.45), transparent),
    radial-gradient(1px 1px at 55% 88%, rgba(255, 255, 255, 0.3), transparent),
    radial-gradient(1.5px 1.5px at 88% 12%, rgba(165, 180, 252, 0.5), transparent),
    radial-gradient(1px 1px at 22% 45%, rgba(255, 255, 255, 0.25), transparent);
  background-size: 100% 100%;
}

.landing-nav-outer {
  position: sticky;
  top: 0;
  z-index: 20;
  width: 100%;
  border-bottom: 1px solid $border-subtle;
  background: color-mix(in srgb, var(--color-bg-canvas) 82%, transparent);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
}

.landing-nav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: $space-md;
  width: 100%;
  max-width: 1400px;
  margin: 0 auto;
  padding: $space-md clamp($space-lg, 4vw, $space-3xl);
  box-sizing: border-box;
}

.landing-nav__actions :deep(.el-button) {
  min-height: 42px;
  padding: 0 20px;
  font-size: $font-size-md;
  font-weight: 600;
}

.landing-nav__brand {
  display: flex;
  align-items: center;
  gap: 8px;
  text-decoration: none;
  color: inherit;
  flex-shrink: 0;
}

.landing-nav__title {
  font-size: $font-size-xl;
  font-weight: 700;
  letter-spacing: 0.12em;
  background: linear-gradient(135deg, #a78bfa, #818cf8);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.landing-nav__subtitle {
  font-size: $font-size-sm;
  color: $text-muted;
  font-weight: 500;
  padding-left: 10px;
  border-left: 1px solid $border-default;
}

.landing-nav__links {
  display: none;
  align-items: center;
  gap: $space-lg;
}
@media (min-width: 840px) {
  .landing-nav__links {
    display: flex;
  }
}

.landing-nav__link {
  font-size: $font-size-md;
  font-weight: 500;
  color: $text-secondary;
  text-decoration: none;
  transition: color $transition-fast;
  &:hover {
    color: $primary-light;
  }
}

.landing-nav__actions {
  display: flex;
  align-items: center;
  gap: $space-sm;
  flex-shrink: 0;
}

.landing-nav__btn-text {
  color: $text-secondary;
}
.landing-nav__btn-primary {
  font-weight: 600;
  border-radius: $radius-md;
  background: linear-gradient(135deg, $primary-light, $primary-dark);
  border: none;
}

main {
  position: relative;
  z-index: 1;
  width: 100%;
  max-width: 1320px;
  margin: 0 auto;
  padding: clamp($space-xl, 4vw, $space-3xl) clamp($space-lg, 4vw, $space-3xl) $space-3xl;
  box-sizing: border-box;
}

.hero {
  padding: clamp($space-2xl, 6vw, $space-3xl) 0 clamp($space-3xl, 7vw, 5rem);
  text-align: center;
}

.hero__eyebrow {
  font-size: $font-size-md;
  font-weight: 600;
  color: $primary-light;
  letter-spacing: 0.08em;
  text-transform: none;
  margin-bottom: $space-md;
}

.hero__title {
  font-size: clamp(2rem, 5.2vw, 3.35rem);
  font-weight: 800;
  line-height: 1.18;
  letter-spacing: -0.02em;
  margin-bottom: $space-lg;
}

.hero__title-accent {
  background: linear-gradient(120deg, #c4b5fd, #818cf8, #38bdf8);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.hero__lead {
  max-width: 42rem;
  margin: 0 auto;
  font-size: clamp($font-size-md, 1.35vw, 1.125rem);
  line-height: 1.8;
  color: $text-secondary;
}

.hero__cta {
  display: flex;
  flex-wrap: wrap;
  gap: $space-md;
  justify-content: center;
  margin-top: $space-xl;
}

.hero__btn-main {
  font-weight: 600;
  padding: 14px 32px;
  min-height: 48px;
  height: auto;
  border-radius: $radius-md;
  font-size: $font-size-md;
  background: linear-gradient(135deg, $primary-color, $primary-dark);
  border: none;
  &:hover {
    background: linear-gradient(135deg, $primary-light, $primary-color);
    box-shadow: 0 0 28px rgba(99, 102, 241, 0.35);
  }
}

.hero__btn-ghost {
  font-weight: 600;
  padding: 14px 28px;
  min-height: 48px;
  height: auto;
  border-radius: $radius-md;
  font-size: $font-size-md;
}

.hero__metrics {
  list-style: none;
  margin: $space-2xl auto 0;
  padding: 0;
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: clamp($space-lg, 4vw, $space-2xl);
  max-width: 52rem;
}

.hero__metrics li {
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 10.5rem;
}

.hero__metric-value {
  font-size: $font-size-lg;
  font-weight: 700;
  color: $text-primary;
}
.hero__metric-label {
  font-size: $font-size-sm;
  color: $text-muted;
  line-height: 1.45;
}

.section {
  margin-bottom: $space-3xl;
}

.section__head {
  text-align: center;
  max-width: 44rem;
  margin: 0 auto $space-2xl;
}

.section__title {
  font-size: clamp(1.5rem, 3.2vw, 2.125rem);
  font-weight: 800;
  margin-bottom: $space-md;
  letter-spacing: -0.02em;
}

.section__desc {
  font-size: $font-size-md;
  color: $text-secondary;
  line-height: 1.75;
}

.features__grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(288px, 1fr));
  gap: $space-lg;
}

.feature-card {
  padding: $space-xl;
  background: $bg-surface;
  border: 1px solid $border-color;
  border-radius: $border-radius-xl;
  box-shadow: $shadow-sm;
  transition:
    border-color $transition-fast,
    transform $transition-fast,
    box-shadow $transition-fast;
  animation: fadeInUp 0.5s ease both;

  &:hover {
    border-color: rgba(99, 102, 241, 0.22);
    transform: translateY(-2px);
    box-shadow: $shadow-md;
  }
}

.feature-card__icon {
  width: 52px;
  height: 52px;
  border-radius: $radius-md;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: $space-md;

  &.tone-violet {
    background: rgba(99, 102, 241, 0.12);
    color: #a5b4fc;
  }
  &.tone-amber {
    background: rgba(251, 191, 36, 0.12);
    color: #fbbf24;
  }
  &.tone-cyan {
    background: rgba(6, 182, 212, 0.12);
    color: #22d3ee;
  }
  &.tone-rose {
    background: rgba(244, 114, 182, 0.12);
    color: #f472b6;
  }
  &.tone-indigo {
    background: rgba(99, 102, 241, 0.12);
    color: #a5b4fc;
  }
  &.tone-emerald {
    background: rgba(16, 185, 129, 0.12);
    color: #34d399;
  }
}

.feature-card__title {
  font-size: $font-size-lg;
  font-weight: 700;
  margin-bottom: $space-sm;
}

.feature-card__text {
  font-size: $font-size-md;
  color: $text-secondary;
  line-height: 1.7;
  margin: 0;
}

.scenes__row {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: $space-md;
}

@media (max-width: 900px) {
  .scenes__row {
    grid-template-columns: 1fr;
  }
}

.scene-card {
  padding: clamp($space-xl, 3vw, $space-2xl);
  border-radius: $border-radius-xl;
  background: linear-gradient(160deg, rgba(99, 102, 241, 0.08), transparent 55%), $bg-surface;
  border: 1px solid $border-color;
}

.scene-card__title {
  font-size: $font-size-lg;
  font-weight: 700;
  margin-bottom: $space-md;
}

.scene-card__text {
  font-size: $font-size-md;
  color: $text-secondary;
  line-height: 1.7;
  margin-bottom: $space-md;
}

.scene-card__bullets {
  margin: 0;
  padding-left: 1.15em;
  font-size: $font-size-md;
  color: $text-muted;
  line-height: 1.75;
}

.cta-band {
  margin-bottom: $space-2xl;
}

.cta-band__inner {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: $space-xl;
  padding: clamp($space-xl, 4vw, $space-3xl);
  border-radius: $border-radius-xl;
  background: linear-gradient(135deg, rgba(79, 70, 229, 0.92), rgba(99, 102, 241, 0.85));
  color: #fff;
  box-shadow: 0 16px 48px rgba(79, 70, 229, 0.28);
}

.cta-band__title {
  font-size: clamp(1.25rem, 2.5vw, 1.75rem);
  font-weight: 800;
  margin-bottom: $space-md;
}

.cta-band__text {
  font-size: $font-size-md;
  opacity: 0.92;
  line-height: 1.7;
  max-width: 32rem;
  margin: 0;
}

.cta-band__actions {
  display: flex;
  flex-wrap: wrap;
  gap: $space-md;
}

.cta-band__btn {
  font-weight: 600;
  min-height: 48px;
  padding: 0 28px;
  font-size: $font-size-md;
  border: none;
  background: #fff;
  color: #4338ca;
  &:hover {
    background: #eef2ff;
    color: #3730a3;
  }
}

.cta-band__btn-secondary {
  font-weight: 600;
  min-height: 48px;
  padding: 0 28px;
  font-size: $font-size-md;
  color: #fff !important;
  border-color: rgba(255, 255, 255, 0.55) !important;
  background: transparent !important;
  &:hover {
    background: rgba(255, 255, 255, 0.12) !important;
    border-color: #fff !important;
  }
}

.landing-footer {
  position: relative;
  z-index: 1;
  width: 100%;
  max-width: 1320px;
  margin: 0 auto;
  padding: $space-2xl clamp($space-lg, 4vw, $space-3xl) $space-3xl;
  box-sizing: border-box;
  border-top: 1px solid $border-subtle;
}

.landing-footer__row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: $space-md;
  margin-bottom: $space-md;
}

.landing-footer__brand {
  font-size: $font-size-md;
  font-weight: 600;
  color: $text-secondary;
}

.landing-footer__links {
  display: flex;
  gap: $space-lg;
  a {
    font-size: $font-size-md;
    color: $text-muted;
    text-decoration: none;
    &:hover {
      color: $primary-light;
    }
  }
}

.landing-nav__logo-img {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  img {
    border-radius: 6px;
    object-fit: contain;
  }
}

.landing-footer__extra {
  margin-top: $space-md;
  font-size: $font-size-sm;
  color: $text-secondary;
  line-height: 1.65;
}

.landing-footer__icp a {
  color: $text-muted;
  text-decoration: none;
  &:hover {
    color: $primary-light;
  }
}

.landing-footer__contact-row {
  display: flex;
  flex-wrap: wrap;
  gap: $space-sm;
  align-items: baseline;
  margin-bottom: 4px;
}
.landing-footer__contact-label {
  color: $text-muted;
  min-width: 3em;
}
.landing-footer__contact-val {
  color: $text-secondary;
}
.landing-footer__contact-val[href] {
  color: $primary-light;
  text-decoration: none;
  &:hover {
    text-decoration: underline;
  }
}

.landing-footer__friends {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: $space-sm;
}
.landing-footer__friends-title {
  color: $text-muted;
  margin-right: $space-sm;
}
.landing-footer__friend-item a {
  color: $primary-light;
  text-decoration: none;
  &:hover {
    text-decoration: underline;
  }
}
.landing-footer__friend-sep {
  color: $text-muted;
  margin: 0 4px;
}

.landing-footer__sponsor-frame {
  display: block;
  width: 100%;
  min-height: 120px;
  max-height: 480px;
  border: 1px solid $border-subtle;
  border-radius: $radius-md;
  background: $bg-surface;
}

.landing-footer__sponsor-pre {
  margin: 0;
  white-space: pre-wrap;
  font-family: inherit;
  font-size: $font-size-sm;
  color: $text-secondary;
}

.landing-footer__copy {
  font-size: $font-size-xs;
  color: $text-muted;
  margin: 0;
  margin-top: $space-md;
}
</style>
