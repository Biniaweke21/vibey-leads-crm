<script setup lang="ts">
import { useRouter } from 'vue-router'
import { CalendarDays, MapPin, Building2, FolderKanban } from '@lucide/vue'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'

interface MeetingListEntry {
  id: number | string
  title: string
  starts_at: string
  duration_minutes: number | null
  location: string | null
  status: string
  status_label: string
  client?: { name: string } | null
  project?: { name: string } | null
}

const props = withDefaults(
  defineProps<{ meeting: MeetingListEntry; basePath?: string; showClient?: boolean }>(),
  { basePath: '/dashboard/meetings', showClient: true },
)
const router = useRouter()

const statusClass: Record<string, string> = {
  upcoming: 'bg-sky-500/10 text-sky-600 border-sky-500/20',
  completed: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20',
  cancelled: 'bg-red-500/10 text-red-600 border-red-500/20',
}

function formatDate(iso: string) {
  return new Date(iso).toLocaleString(undefined, {
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}
</script>

<template>
  <Card
    class="hover:border-primary/40 cursor-pointer transition-colors"
    @click="router.push(`${basePath}/${meeting.id}`)"
  >
    <CardContent class="flex flex-wrap items-center gap-3">
      <div class="min-w-48 flex-1">
        <p class="font-medium">{{ meeting.title }}</p>
        <p class="text-muted-foreground flex items-center gap-1 text-xs">
          <CalendarDays class="size-3.5" /> {{ formatDate(meeting.starts_at) }}
          <template v-if="meeting.duration_minutes"> · {{ meeting.duration_minutes }} min</template>
        </p>
      </div>
      <p v-if="meeting.location" class="text-muted-foreground flex items-center gap-1 text-xs">
        <MapPin class="size-3.5" /> {{ meeting.location }}
      </p>
      <p
        v-if="showClient && meeting.client"
        class="text-muted-foreground flex items-center gap-1 text-xs"
      >
        <Building2 class="size-3.5" /> {{ meeting.client.name }}
      </p>
      <p v-if="meeting.project" class="text-muted-foreground flex items-center gap-1 text-xs">
        <FolderKanban class="size-3.5" /> {{ meeting.project.name }}
      </p>
      <Badge :class="statusClass[meeting.status]">{{ meeting.status_label }}</Badge>
    </CardContent>
  </Card>
</template>
