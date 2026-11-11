<script setup lang="ts">
import { computed } from 'vue'
import { CircleCheck, CircleDashed } from '@lucide/vue'
import { Badge } from '@/components/ui/badge'
import type { SignatureState } from '@/features/documents/types'
import { signatureStateMeta } from '@/features/documents/data/statusMeta'

const props = defineProps<{ status: SignatureState }>()

const clientSigned = computed(() => props.status !== 'unsigned')
const agencySigned = computed(() => props.status === 'fully-signed')
</script>

<template>
  <div class="space-y-3">
    <Badge :class="signatureStateMeta[status].class">{{ signatureStateMeta[status].label }}</Badge>
    <div class="space-y-2 text-sm">
      <div class="flex items-center gap-2">
        <CircleCheck v-if="clientSigned" class="size-4 shrink-0 text-emerald-500" />
        <CircleDashed v-else class="text-muted-foreground size-4 shrink-0" />
        <span>Client signature</span>
      </div>
      <div class="flex items-center gap-2">
        <CircleCheck v-if="agencySigned" class="size-4 shrink-0 text-emerald-500" />
        <CircleDashed v-else class="text-muted-foreground size-4 shrink-0" />
        <span>Vibey World signature</span>
      </div>
    </div>
  </div>
</template>
