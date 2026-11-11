<script setup lang="ts">
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { dateRangeOptions } from '@/features/analytics/data/dateRanges'
import type { DateRangeKey } from '@/features/analytics/types'

const model = defineModel<DateRangeKey>({ required: true })
</script>

<template>
  <Select :model-value="model" @update:model-value="(v) => (model = v as DateRangeKey)">
    <SelectTrigger class="w-full sm:w-44"><SelectValue /></SelectTrigger>
    <SelectContent>
      <SelectItem v-for="option in dateRangeOptions" :key="option.value" :value="option.value">
        {{ option.label }}
      </SelectItem>
    </SelectContent>
  </Select>
</template>
