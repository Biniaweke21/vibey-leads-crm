<script setup lang="ts">
import { computed } from 'vue'
import { useToast } from 'vue-toastification'
import { useLeadsStore, leadStatuses, type Lead, type LeadStatus } from '@/stores/leads'
import { apiErrorMessage } from '@/services/apiError'
import KanbanColumn from './KanbanColumn.vue'

const props = defineProps<{ leads: Lead[] }>()
const leadsStore = useLeadsStore()
const toast = useToast()

const columns = computed(() =>
  leadStatuses.map((s) => ({
    ...s,
    leads: props.leads.filter((lead) => lead.status === s.value),
  })),
)

async function handleDrop(leadId: string, status: LeadStatus) {
  try {
    await leadsStore.updateStatus(leadId, status)
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not move this lead.'))
  }
}
</script>

<template>
  <div class="flex gap-4 overflow-x-auto pb-4">
    <KanbanColumn
      v-for="column in columns"
      :key="column.value"
      :label="column.label"
      :status="column.value"
      :leads="column.leads"
      @drop="handleDrop"
    />
  </div>
</template>
