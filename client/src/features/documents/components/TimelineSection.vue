<script setup lang="ts">
import { Milestone } from '@lucide/vue'
import EmptyState from '@/components/base/EmptyState.vue'

defineProps<{ phases: { id: string; phase: string; duration: string }[] }>()
</script>

<template>
  <EmptyState v-if="phases.length === 0" :icon="Milestone" title="No timeline defined" />
  <ol v-else class="space-y-3">
    <li v-for="(phase, i) in phases" :key="phase.id" class="flex items-center gap-3">
      <span
        class="bg-primary/10 text-primary flex size-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold"
      >
        {{ i + 1 }}
      </span>
      <div class="flex flex-1 items-center justify-between border-b pb-2">
        <p class="text-sm font-medium">{{ phase.phase }}</p>
        <p class="text-muted-foreground text-xs">{{ phase.duration }}</p>
      </div>
    </li>
  </ol>
</template>
