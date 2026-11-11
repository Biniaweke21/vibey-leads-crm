<script setup lang="ts">
import { ref } from 'vue'
import type { Lead, LeadStatus } from '@/stores/leads'
import KanbanCard from './KanbanCard.vue'
import { draggedLeadId } from './dragState'

const props = defineProps<{ label: string; status: LeadStatus; leads: Lead[] }>()
const emit = defineEmits<{ drop: [leadId: string, status: LeadStatus] }>()

const isDragOver = ref(false)

function onDrop() {
  isDragOver.value = false
  if (draggedLeadId.value) emit('drop', draggedLeadId.value, props.status)
  draggedLeadId.value = null
}
</script>

<template>
  <div
    class="bg-muted/40 flex w-72 shrink-0 flex-col rounded-lg transition-colors"
    :class="isDragOver ? 'bg-primary/10 ring-primary/30 ring-2' : ''"
    @dragover.prevent
    @dragenter.prevent="isDragOver = true"
    @dragleave="isDragOver = false"
    @drop="onDrop"
  >
    <div class="flex items-center justify-between px-3 py-2.5">
      <p class="text-sm font-semibold">{{ label }}</p>
      <span class="text-muted-foreground text-xs">{{ leads.length }}</span>
    </div>
    <div class="flex-1 space-y-2 overflow-y-auto px-2 pb-2">
      <KanbanCard v-for="lead in leads" :key="lead.id" :lead="lead" />
    </div>
  </div>
</template>
