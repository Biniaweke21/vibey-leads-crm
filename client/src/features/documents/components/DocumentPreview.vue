<script setup lang="ts">
defineProps<{ title: string; subtitle?: string }>()
</script>

<template>
  <div class="bg-card rounded-lg border p-6 shadow-sm sm:p-8">
    <header class="mb-6 flex items-start justify-between gap-4 border-b pb-4">
      <div>
        <p class="text-muted-foreground text-xs font-semibold tracking-wide uppercase">
          Vibey World
        </p>
        <h2 class="text-xl font-bold">{{ title }}</h2>
        <p v-if="subtitle" class="text-muted-foreground text-sm">{{ subtitle }}</p>
      </div>
      <img src="/img/Vibeylogo.jpg" alt="Vibey World" class="h-10 w-auto shrink-0" />
    </header>
    <div class="space-y-6">
      <slot />
    </div>
  </div>
</template>
