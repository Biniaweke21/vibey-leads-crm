<script setup lang="ts">
import type { Component } from 'vue'
import StatCard from '@/components/base/StatCard.vue'

withDefaults(
  defineProps<{
    label: string
    value: string | number
    icon?: Component
    suffix?: string
  }>(),
  { suffix: '' },
)
</script>

<template>
  <StatCard :label="label" :value="`${value}${suffix}`" :icon="icon" />
</template>
