<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useToast } from 'vue-toastification'
import { FileStack, Plus } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import EmptyState from '@/components/base/EmptyState.vue'
import DocumentFilters from '@/features/documents/components/DocumentFilters.vue'
import DocumentCard from '@/features/documents/components/DocumentCard.vue'
import CreateDocumentDialog from '@/features/documents/components/CreateDocumentDialog.vue'
import { useDocumentsStore } from '@/features/documents/store/documents'
import { useClientsStore } from '@/stores/clients'
import { useProjectsStore } from '@/stores/projects'
import { documentAmount, type DocumentCategory } from '@/features/documents/types'
import {
  proposalStatusMeta,
  contractStatusMeta,
  invoiceStatusMeta,
} from '@/features/documents/data/statusMeta'
import { apiErrorMessage } from '@/services/apiError'

const documentsStore = useDocumentsStore()
const clientsStore = useClientsStore()
const projectsStore = useProjectsStore()
const toast = useToast()

const loading = ref(true)
const createOpen = ref(false)
onMounted(async () => {
  try {
    await Promise.all([
      documentsStore.fetchDocuments(),
      clientsStore.fetchClients(),
      projectsStore.fetchProjects(),
    ])
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not load documents.'))
  } finally {
    loading.value = false
  }
})

const search = ref('')
const typeFilter = ref('all')
const statusFilter = ref('all')
const sortBy = ref('updatedAt')
const sortDir = ref<'asc' | 'desc'>('desc')
const layout = ref<'grid' | 'list'>('grid')
const page = ref(1)
const pageSize = 9

const sortOptions = [
  { value: 'updatedAt', label: 'Last Updated' },
  { value: 'createdAt', label: 'Created Date' },
  { value: 'amount', label: 'Amount' },
  { value: 'title', label: 'Title' },
]

const statusOptions = computed(() => {
  const metaMaps =
    typeFilter.value === 'proposal'
      ? [proposalStatusMeta]
      : typeFilter.value === 'contract'
        ? [contractStatusMeta]
        : typeFilter.value === 'invoice'
          ? [invoiceStatusMeta]
          : [proposalStatusMeta, contractStatusMeta, invoiceStatusMeta]

  const seen = new Map<string, string>()
  for (const meta of metaMaps) {
    for (const [value, { label }] of Object.entries(meta)) {
      if (!seen.has(value)) seen.set(value, label)
    }
  }
  return [...seen.entries()].map(([value, label]) => ({ value, label }))
})

function clientName(clientId: string) {
  return clientsStore.getClient(clientId)?.name ?? 'Unknown Client'
}

function projectName(projectId: string) {
  return projectsStore.getProject(projectId)?.name ?? 'Unknown Project'
}

const filteredDocuments = computed(() => {
  const query = search.value.trim().toLowerCase()
  let result = documentsStore.documents.filter((doc) => {
    const matchesSearch =
      !query ||
      doc.title.toLowerCase().includes(query) ||
      clientName(doc.clientId).toLowerCase().includes(query) ||
      projectName(doc.projectId).toLowerCase().includes(query)
    const matchesType =
      typeFilter.value === 'all' || doc.category === (typeFilter.value as DocumentCategory)
    const matchesStatus = statusFilter.value === 'all' || doc.status === statusFilter.value
    return matchesSearch && matchesType && matchesStatus
  })

  result = [...result].sort((a, b) => {
    const dir = sortDir.value === 'asc' ? 1 : -1
    if (sortBy.value === 'amount') return (documentAmount(a) - documentAmount(b)) * dir
    if (sortBy.value === 'title') return a.title.localeCompare(b.title) * dir
    if (sortBy.value === 'createdAt') return a.createdAt.localeCompare(b.createdAt) * dir
    return a.updatedAt.localeCompare(b.updatedAt) * dir
  })

  return result
})

watch([search, typeFilter, statusFilter], () => {
  page.value = 1
})

const totalPages = computed(() => Math.max(1, Math.ceil(filteredDocuments.value.length / pageSize)))
const paginatedDocuments = computed(() => {
  const start = (page.value - 1) * pageSize
  return filteredDocuments.value.slice(start, start + pageSize)
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-start justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold tracking-tight">Documents</h1>
        <p class="text-muted-foreground mt-1">Proposals, contracts, and invoices in one place.</p>
      </div>
      <Button @click="createOpen = true"><Plus class="size-4" /> New Document</Button>
    </div>

    <CreateDocumentDialog v-model:open="createOpen" />

    <DocumentFilters
      v-model:search="search"
      v-model:type="typeFilter"
      v-model:status="statusFilter"
      v-model:sort-by="sortBy"
      v-model:sort-dir="sortDir"
      v-model:layout="layout"
      :status-options="statusOptions"
      :sort-options="sortOptions"
    />

    <div v-if="loading" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <Skeleton v-for="i in 6" :key="i" class="h-40 rounded-lg" />
    </div>

    <EmptyState
      v-else-if="filteredDocuments.length === 0"
      :icon="FileStack"
      title="No documents match your filters"
      description="Try adjusting your search or filters."
    />

    <template v-else>
      <div
        :class="
          layout === 'grid' ? 'grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3' : 'space-y-2'
        "
      >
        <DocumentCard
          v-for="doc in paginatedDocuments"
          :key="doc.id"
          :document="doc"
          :client-name="clientName(doc.clientId)"
          :project-name="projectName(doc.projectId)"
          :layout="layout"
        />
      </div>

      <div class="flex items-center justify-between">
        <p class="text-muted-foreground text-sm">
          Page {{ page }} of {{ totalPages }} · {{ filteredDocuments.length }} documents
        </p>
        <div class="flex gap-2">
          <Button variant="outline" size="sm" :disabled="page === 1" @click="page--"
            >Previous</Button
          >
          <Button variant="outline" size="sm" :disabled="page === totalPages" @click="page++"
            >Next</Button
          >
        </div>
      </div>
    </template>
  </div>
</template>
