<script setup lang="ts">
import type { Component } from 'vue'
import { Card, CardContent } from '@/components/ui/card'

defineProps<{
  icon?: Component
  title: string
  description?: string
}>()
</script>

<template>
  <Card
    class="border-border/60 from-card to-muted/40 hover:border-primary/30 h-full bg-gradient-to-b transition-all duration-300 hover:-translate-y-1 hover:shadow-xl"
  >
    <CardContent class="flex h-full flex-col">
      <div
        v-if="icon"
        class="bg-primary/10 text-primary mb-4 flex size-12 items-center justify-center rounded-xl"
      >
        <component :is="icon" class="size-6" />
      </div>
      <p class="text-base font-semibold">{{ title }}</p>
      <p v-if="description" class="mt-1.5 text-sm text-muted-foreground">{{ description }}</p>
    </CardContent>
  </Card>
</template>
