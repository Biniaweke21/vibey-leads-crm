<script setup lang="ts">
import { ref } from 'vue'
import { useIntersectionObserver } from '@vueuse/core'

const props = withDefaults(defineProps<{ delay?: number }>(), { delay: 0 })

const target = ref<HTMLElement>()
const visible = ref(false)

useIntersectionObserver(
  target,
  ([entry]) => {
    if (entry?.isIntersecting) visible.value = true
  },
  { threshold: 0.15 },
)
</script>

<template>
  <div
    ref="target"
    class="transition-all duration-700 ease-out"
    :class="visible ? 'translate-y-0 opacity-100' : 'translate-y-6 opacity-0'"
    :style="{ transitionDelay: `${props.delay}ms` }"
  >
    <slot />
  </div>
</template>
