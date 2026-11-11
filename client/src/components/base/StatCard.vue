<script setup lang="ts">
import type { Component } from 'vue'
import { Card, CardContent } from '@/components/ui/card'

defineProps<{
  label: string
  value: string | number
  icon?: Component
  trend?: string
}>()
</script>

<template>
  <Card>
    <CardContent class="flex items-start justify-between">
      <div>
        <p class="text-sm text-muted-foreground">{{ label }}</p>
        <p class="mt-1 text-2xl font-semibold">{{ value }}</p>
        <p v-if="trend" class="mt-1 text-xs text-muted-foreground">{{ trend }}</p>
      </div>
      <component :is="icon" v-if="icon" class="text-primary size-8" />
    </CardContent>
  </Card>
</template>
