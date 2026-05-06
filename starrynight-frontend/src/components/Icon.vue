<template>
  <component :is="iconComponent" v-bind="$attrs" />
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { SvgIcon } from './index'

interface Props {
  name: string
  size?: number | string
  color?: string
  spin?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  size: 24,
  color: 'currentColor',
  spin: false
})

const iconComponent = computed(() => {
  return SvgIcon
})
</script>

<style>
@keyframes icon-spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>