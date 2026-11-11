<script setup lang="ts">
import { computed, ref } from 'vue'
import { Plus } from '@lucide/vue'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'

export interface ActivityLike {
  id: string
  text: string
  createdAt: string
}

const props = withDefaults(defineProps<{ title?: string; activity: ActivityLike[] }>(), {
  title: 'Timeline & Activity',
})
const emit = defineEmits<{ log: [text: string, at?: string] }>()

const logText = ref('')
const logDate = ref('')

const sortedActivity = computed(() =>
  [...props.activity].sort((a, b) => b.createdAt.localeCompare(a.createdAt)),
)

function formatDate(iso: string) {
  return new Date(iso).toLocaleString(undefined, {
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}

function logEntry() {
  if (!logText.value.trim()) return
  const at = logDate.value ? new Date(logDate.value).toISOString() : undefined
  emit('log', logText.value.trim(), at)
  logText.value = ''
  logDate.value = ''
}
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>{{ title }}</CardTitle>
    </CardHeader>
    <CardContent class="space-y-4">
      <div class="space-y-2">
        <Input v-model="logText" placeholder="Log a call, event, or note…" class="w-full" />
        <div class="flex gap-2">
          <Input v-model="logDate" type="date" class="flex-1" />
          <Button size="sm" :disabled="!logText.trim()" @click="logEntry">
            <Plus class="size-4" /> Log
          </Button>
        </div>
      </div>

      <ol class="space-y-4">
        <li v-for="entry in sortedActivity" :key="entry.id" class="flex gap-3">
          <div class="flex flex-col items-center">
            <span class="bg-primary mt-1.5 size-2 shrink-0 rounded-full" />
            <span class="bg-border mt-1 w-px flex-1" />
          </div>
          <div class="pb-1">
            <p class="text-sm">{{ entry.text }}</p>
            <p class="text-muted-foreground text-xs">{{ formatDate(entry.createdAt) }}</p>
          </div>
        </li>
      </ol>
    </CardContent>
  </Card>
</template>
