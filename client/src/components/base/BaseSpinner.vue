<script setup lang="ts">
import { computed } from 'vue'
import { Loader2 } from '@lucide/vue'

const props = withDefaults(defineProps<{ size?: 'sm' | 'md' | 'lg' }>(), { size: 'md' })

const sizeClasses: Record<string, string> = {
  sm: 'size-4',
  md: 'size-6',
  lg: 'size-8',
}

const classes = computed(() => sizeClasses[props.size])
</script>

<template>
  <Loader2 :class="classes" class="text-primary animate-spin" />
</template>
