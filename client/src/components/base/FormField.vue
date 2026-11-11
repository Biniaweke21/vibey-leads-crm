<script setup lang="ts">
withDefaults(defineProps<{ label: string; required?: boolean; hint?: string }>(), {
  required: false,
})
</script>

<template>
  <label class="flex flex-col gap-1.5">
    <span class="text-sm font-medium">
      {{ label }}
      <span v-if="required" class="text-destructive">*</span>
    </span>
    <slot />
    <span v-if="hint" class="text-muted-foreground text-xs">{{ hint }}</span>
  </label>
</template>
