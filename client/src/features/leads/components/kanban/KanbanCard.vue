<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { Banknote, Clock3, Sparkles } from '@lucide/vue'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import type { Lead } from '@/stores/leads'
import { priorityBorderClass } from '@/features/leads/data/statusMeta'
import { draggedLeadId } from './dragState'

const props = defineProps<{ lead: Lead }>()

const router = useRouter()

function onDragStart(event: DragEvent) {
  draggedLeadId.value = props.lead.id
  event.dataTransfer?.setData('text/plain', props.lead.id)
}

const daysInStage = computed(() =>
  Math.max(
    0,
    Math.floor((Date.now() - new Date(props.lead.stageEnteredAt).getTime()) / 86_400_000),
  ),
)

function initials(name: string) {
  return name
    .split(' ')
    .map((part) => part[0])
    .slice(0, 2)
    .join('')
    .toUpperCase()
}
</script>

<template>
  <Card
    draggable="true"
    class="hover:border-primary/40 cursor-grab gap-0 border-l-4 py-3 shadow-sm transition-colors active:cursor-grabbing"
    :class="priorityBorderClass[lead.priority]"
    @dragstart="onDragStart"
    @click="router.push(`/dashboard/leads/${lead.id}`)"
  >
    <CardContent class="space-y-2 px-3">
      <div class="flex items-start justify-between gap-2">
        <p class="line-clamp-1 text-sm font-semibold">{{ lead.fullName }}</p>
        <Sparkles
          v-if="lead.aiAnalysisStatus === 'ready'"
          class="mt-0.5 size-3.5 shrink-0 text-emerald-500"
        />
      </div>
      <p class="text-muted-foreground line-clamp-1 text-xs">{{ lead.businessName }}</p>
      <Badge variant="secondary" class="text-[11px]">{{ lead.services[0] ?? 'General' }}</Badge>
      <div class="text-muted-foreground flex items-center gap-1 text-xs">
        <Banknote class="size-3.5 shrink-0" />
        <span class="line-clamp-1">{{ lead.budget }}</span>
      </div>
      <div class="text-muted-foreground flex items-center gap-1 text-xs">
        <Clock3 class="size-3.5 shrink-0" />
        {{ daysInStage }} {{ daysInStage === 1 ? 'day' : 'days' }} in stage
      </div>
      <div class="flex items-center justify-end pt-1">
        <Avatar class="size-6">
          <AvatarFallback class="text-[10px]">{{ initials(lead.assignedTo) }}</AvatarFallback>
        </Avatar>
      </div>
    </CardContent>
  </Card>
</template>
