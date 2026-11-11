<script setup lang="ts">
import type { Component } from 'vue'

defineProps<{
  icon?: Component
  title: string
  description?: string
}>()
</script>

<template>
  <div class="flex flex-col items-center justify-center gap-2 py-16 text-center">
    <component :is="icon" v-if="icon" class="text-muted-foreground mb-2 size-10" />
    <p class="text-foreground text-base font-medium">{{ title }}</p>
    <p v-if="description" class="text-muted-foreground text-sm">{{ description }}</p>
    <div v-if="$slots.action" class="mt-4">
      <slot name="action" />
    </div>
  </div>
</template>
