<script setup lang="ts">
withDefaults(defineProps<{ as?: 'h1' | 'h2' }>(), { as: 'h1' })
</script>

<template>
  <component :is="as" class="text-3xl font-semibold tracking-tight text-foreground">
    <slot />
  </component>
</template>
