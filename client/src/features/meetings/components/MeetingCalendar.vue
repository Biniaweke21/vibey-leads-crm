<script setup lang="ts">
import { computed, ref } from 'vue'
import { ChevronLeft, ChevronRight } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import type { Meeting } from '@/features/meetings/types'

const props = defineProps<{ meetings: Meeting[] }>()
const emit = defineEmits<{ select: [date: Date] }>()

const viewDate = ref(new Date())
const selectedDate = ref<Date | null>(new Date())

const monthLabel = computed(() =>
  viewDate.value.toLocaleDateString(undefined, { month: 'long', year: 'numeric' }),
)

function isSameDay(a: Date, b: Date) {
  return a.toDateString() === b.toDateString()
}

function meetingsOn(date: Date) {
  return props.meetings.filter((m) => isSameDay(new Date(m.starts_at), date))
}

function isToday(date: Date) {
  return isSameDay(date, new Date())
}

const weeks = computed(() => {
  const year = viewDate.value.getFullYear()
  const month = viewDate.value.getMonth()
  const firstDay = new Date(year, month, 1)
  const startOffset = firstDay.getDay()
  const daysInMonth = new Date(year, month + 1, 0).getDate()

  const cells: (Date | null)[] = []
  for (let i = 0; i < startOffset; i++) cells.push(null)
  for (let d = 1; d <= daysInMonth; d++) cells.push(new Date(year, month, d))
  while (cells.length % 7 !== 0) cells.push(null)

  const result: (Date | null)[][] = []
  for (let i = 0; i < cells.length; i += 7) result.push(cells.slice(i, i + 7))
  return result
})

function prevMonth() {
  viewDate.value = new Date(viewDate.value.getFullYear(), viewDate.value.getMonth() - 1, 1)
}
function nextMonth() {
  viewDate.value = new Date(viewDate.value.getFullYear(), viewDate.value.getMonth() + 1, 1)
}

function selectDay(date: Date) {
  selectedDate.value = date
  emit('select', date)
}
</script>

<template>
  <div>
    <div class="mb-4 flex items-center justify-between">
      <p class="font-semibold">{{ monthLabel }}</p>
      <div class="flex gap-1">
        <Button variant="outline" size="icon-sm" @click="prevMonth"
          ><ChevronLeft class="size-4"
        /></Button>
        <Button variant="outline" size="icon-sm" @click="nextMonth"
          ><ChevronRight class="size-4"
        /></Button>
      </div>
    </div>
    <div class="text-muted-foreground mb-1 grid grid-cols-7 gap-1 text-center text-xs">
      <span v-for="d in ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa']" :key="d">{{ d }}</span>
    </div>
    <div class="grid grid-cols-7 gap-1">
      <template v-for="(week, wi) in weeks" :key="wi">
        <button
          v-for="(day, di) in week"
          :key="di"
          type="button"
          :disabled="!day"
          class="flex aspect-square flex-col items-center justify-center gap-0.5 rounded-lg text-sm transition-colors"
          :class="[
            !day ? 'invisible' : 'hover:bg-accent',
            day && isToday(day) ? 'text-primary font-bold' : '',
            day && selectedDate && isSameDay(day, selectedDate)
              ? 'bg-primary/10 border-primary/30 border'
              : '',
          ]"
          @click="day && selectDay(day)"
        >
          <span>{{ day?.getDate() }}</span>
          <span v-if="day && meetingsOn(day).length > 0" class="bg-primary size-1.5 rounded-full" />
        </button>
      </template>
    </div>
  </div>
</template>
