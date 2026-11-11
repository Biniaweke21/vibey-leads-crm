<script setup lang="ts">
import { computed } from 'vue'
import { Badge } from '@/components/ui/badge'
import type { DocumentCategory, DocumentStatus } from '@/features/documents/types'
import {
  proposalStatusMeta,
  contractStatusMeta,
  invoiceStatusMeta,
} from '@/features/documents/data/statusMeta'

const props = defineProps<{ category: DocumentCategory; status: DocumentStatus }>()

const meta = computed(() => {
  if (props.category === 'proposal')
    return proposalStatusMeta[props.status as keyof typeof proposalStatusMeta]
  if (props.category === 'contract')
    return contractStatusMeta[props.status as keyof typeof contractStatusMeta]
  return invoiceStatusMeta[props.status as keyof typeof invoiceStatusMeta]
})
</script>

<template>
  <Badge :class="meta.class">{{ meta.label }}</Badge>
</template>
