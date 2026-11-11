<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { FileText, FileSignature, Receipt } from '@lucide/vue'
import { Card, CardContent } from '@/components/ui/card'
import DocumentStatusBadge from '@/features/documents/components/DocumentStatusBadge.vue'
import { documentAmount, type CrmDocument } from '@/features/documents/types'
import { formatEtb } from '@/utils/currency'

const props = withDefaults(
  defineProps<{
    document: CrmDocument
    clientName: string
    projectName: string
    layout?: 'grid' | 'list'
  }>(),
  { layout: 'grid' },
)

const router = useRouter()

const categoryIcon = computed(() => {
  if (props.document.category === 'proposal') return FileText
  if (props.document.category === 'contract') return FileSignature
  return Receipt
})

const routePath = computed(
  () => `/dashboard/documents/${props.document.category}s/${props.document.id}`,
)

function formatDate(iso: string) {
  return new Date(iso).toLocaleDateString(undefined, {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
}

function open() {
  router.push(routePath.value)
}
</script>

<template>
  <Card
    class="hover:border-primary/40 cursor-pointer transition-colors"
    :class="layout === 'list' ? 'py-3' : ''"
    @click="open"
  >
    <CardContent v-if="layout === 'grid'" class="space-y-3">
      <div class="flex items-start justify-between gap-2">
        <div class="flex items-center gap-2">
          <component :is="categoryIcon" class="text-primary size-5 shrink-0" />
          <p class="line-clamp-1 font-semibold">{{ document.title }}</p>
        </div>
        <DocumentStatusBadge :category="document.category" :status="document.status" />
      </div>
      <p class="text-muted-foreground text-xs">{{ clientName }} · {{ projectName }}</p>
      <div class="grid grid-cols-2 gap-3 border-t pt-3 text-sm">
        <div>
          <p class="text-muted-foreground text-xs">Amount</p>
          <p class="font-medium">{{ formatEtb(documentAmount(document)) }}</p>
        </div>
        <div>
          <p class="text-muted-foreground text-xs">Version</p>
          <p class="font-medium">v{{ document.version }}</p>
        </div>
      </div>
      <p class="text-muted-foreground text-xs">
        Created {{ formatDate(document.createdAt) }} · Updated {{ formatDate(document.updatedAt) }}
      </p>
    </CardContent>

    <CardContent v-else class="flex flex-wrap items-center gap-4">
      <component :is="categoryIcon" class="text-primary size-5 shrink-0" />
      <div class="min-w-40 flex-1">
        <p class="line-clamp-1 font-semibold">{{ document.title }}</p>
        <p class="text-muted-foreground text-xs">{{ clientName }} · {{ projectName }}</p>
      </div>
      <DocumentStatusBadge :category="document.category" :status="document.status" />
      <p class="w-24 text-right text-sm font-medium">{{ formatEtb(documentAmount(document)) }}</p>
      <p class="text-muted-foreground w-16 text-xs">v{{ document.version }}</p>
      <p class="text-muted-foreground w-28 text-right text-xs">
        {{ formatDate(document.updatedAt) }}
      </p>
    </CardContent>
  </Card>
</template>
