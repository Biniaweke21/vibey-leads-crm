<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import {
  Search,
  ArrowUp,
  ArrowDown,
  ChevronsUpDown,
  Users,
  List,
  KanbanSquare,
  Sparkles,
} from '@lucide/vue'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Table,
  TableHeader,
  TableBody,
  TableRow,
  TableHead,
  TableCell,
} from '@/components/ui/table'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import EmptyState from '@/components/base/EmptyState.vue'
import KanbanBoard from '@/features/leads/components/kanban/KanbanBoard.vue'
import { useLeadsStore, leadStatuses, leadPriorities, type Lead } from '@/stores/leads'
import {
  statusBadgeClass,
  priorityBadgeClass,
  aiStatusBadgeClass,
} from '@/features/leads/data/statusMeta'
import { apiErrorMessage } from '@/services/apiError'

const router = useRouter()
const toast = useToast()
const leadsStore = useLeadsStore()
const loading = ref(true)

onMounted(async () => {
  try {
    await leadsStore.fetchLeads()
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not load leads.'))
  } finally {
    loading.value = false
  }
})

const view = ref<'list' | 'kanban'>('list')

const search = ref('')
const statusFilter = ref('all')
const priorityFilter = ref('all')

type SortKey = 'fullName' | 'businessName' | 'budget' | 'createdAt'
const sortKey = ref<SortKey>('createdAt')
const sortDir = ref<'asc' | 'desc'>('desc')

const page = ref(1)
const pageSize = 8

function toggleSort(key: SortKey) {
  if (sortKey.value === key) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortDir.value = 'asc'
  }
}

const filteredLeads = computed(() => {
  const query = search.value.trim().toLowerCase()
  let result = leadsStore.leads.filter((lead) => {
    const matchesSearch =
      !query ||
      lead.fullName.toLowerCase().includes(query) ||
      lead.businessName.toLowerCase().includes(query)
    const matchesStatus = statusFilter.value === 'all' || lead.status === statusFilter.value
    const matchesPriority = priorityFilter.value === 'all' || lead.priority === priorityFilter.value
    return matchesSearch && matchesStatus && matchesPriority
  })

  result = [...result].sort((a, b) => {
    const dir = sortDir.value === 'asc' ? 1 : -1
    return a[sortKey.value].localeCompare(b[sortKey.value]) * dir
  })

  return result
})

const totalPages = computed(() => Math.max(1, Math.ceil(filteredLeads.value.length / pageSize)))

const paginatedLeads = computed(() => {
  const start = (page.value - 1) * pageSize
  return filteredLeads.value.slice(start, start + pageSize)
})

watch([search, statusFilter, priorityFilter], () => {
  page.value = 1
})

function formatDate(iso: string) {
  return new Date(iso).toLocaleDateString(undefined, {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
}

function statusLabel(status: Lead['status']) {
  return leadStatuses.find((s) => s.value === status)?.label ?? status
}

function priorityLabel(priority: Lead['priority']) {
  return leadPriorities.find((p) => p.value === priority)?.label ?? priority
}

function openLead(lead: Lead) {
  router.push(`/dashboard/leads/${lead.id}`)
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight">Leads</h1>
        <p class="text-muted-foreground mt-1">Every lead that's come through, in one place.</p>
      </div>
      <div class="bg-muted flex items-center gap-1 rounded-lg p-1">
        <Button
          size="sm"
          :variant="view === 'list' ? 'default' : 'ghost'"
          class="gap-1.5"
          @click="view = 'list'"
        >
          <List class="size-4" /> List
        </Button>
        <Button
          size="sm"
          :variant="view === 'kanban' ? 'default' : 'ghost'"
          class="gap-1.5"
          @click="view = 'kanban'"
        >
          <KanbanSquare class="size-4" /> Kanban
        </Button>
      </div>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
      <div class="relative w-full sm:max-w-xs">
        <Search
          class="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2"
        />
        <Input v-model="search" placeholder="Search by name or business…" class="pl-9" />
      </div>
      <Select v-if="view === 'list'" v-model="statusFilter">
        <SelectTrigger class="w-full sm:w-56"><SelectValue placeholder="Stage" /></SelectTrigger>
        <SelectContent>
          <SelectItem value="all">All stages</SelectItem>
          <SelectItem v-for="s in leadStatuses" :key="s.value" :value="s.value">{{
            s.label
          }}</SelectItem>
        </SelectContent>
      </Select>
      <Select v-model="priorityFilter">
        <SelectTrigger class="w-full sm:w-40"><SelectValue placeholder="Priority" /></SelectTrigger>
        <SelectContent>
          <SelectItem value="all">All priorities</SelectItem>
          <SelectItem v-for="p in leadPriorities" :key="p.value" :value="p.value">{{
            p.label
          }}</SelectItem>
        </SelectContent>
      </Select>
    </div>

    <p v-if="loading" class="text-muted-foreground py-10 text-center text-sm">Loading leads…</p>

    <EmptyState
      v-else-if="filteredLeads.length === 0"
      :icon="Users"
      title="No leads match your filters"
      description="Try adjusting your search or filters."
    />

    <KanbanBoard v-else-if="view === 'kanban'" :leads="filteredLeads" />

    <template v-else>
      <div class="overflow-x-auto rounded-lg border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>
                <button
                  class="flex items-center gap-1 hover:text-foreground"
                  @click="toggleSort('fullName')"
                >
                  Client & Company
                  <ArrowUp v-if="sortKey === 'fullName' && sortDir === 'asc'" class="size-3.5" />
                  <ArrowDown
                    v-else-if="sortKey === 'fullName' && sortDir === 'desc'"
                    class="size-3.5"
                  />
                  <ChevronsUpDown v-else class="size-3.5 opacity-40" />
                </button>
              </TableHead>
              <TableHead>Services Requested</TableHead>
              <TableHead>
                <button
                  class="flex items-center gap-1 hover:text-foreground"
                  @click="toggleSort('budget')"
                >
                  Budget
                  <ArrowUp v-if="sortKey === 'budget' && sortDir === 'asc'" class="size-3.5" />
                  <ArrowDown
                    v-else-if="sortKey === 'budget' && sortDir === 'desc'"
                    class="size-3.5"
                  />
                  <ChevronsUpDown v-else class="size-3.5 opacity-40" />
                </button>
              </TableHead>
              <TableHead>Timeline</TableHead>
              <TableHead>
                <button
                  class="flex items-center gap-1 hover:text-foreground"
                  @click="toggleSort('createdAt')"
                >
                  Submitted
                  <ArrowUp v-if="sortKey === 'createdAt' && sortDir === 'asc'" class="size-3.5" />
                  <ArrowDown
                    v-else-if="sortKey === 'createdAt' && sortDir === 'desc'"
                    class="size-3.5"
                  />
                  <ChevronsUpDown v-else class="size-3.5 opacity-40" />
                </button>
              </TableHead>
              <TableHead>Stage</TableHead>
              <TableHead>Priority</TableHead>
              <TableHead>AI Analysis</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow
              v-for="lead in paginatedLeads"
              :key="lead.id"
              class="hover:bg-muted/50 cursor-pointer"
              @click="openLead(lead)"
            >
              <TableCell>
                <p class="font-medium">{{ lead.fullName }}</p>
                <p class="text-muted-foreground text-xs">{{ lead.businessName }}</p>
              </TableCell>
              <TableCell>
                <div class="flex flex-wrap gap-1">
                  <Badge
                    v-for="service in lead.services"
                    :key="service"
                    variant="secondary"
                    class="text-[11px]"
                  >
                    {{ service }}
                  </Badge>
                </div>
              </TableCell>
              <TableCell>{{ lead.budget }}</TableCell>
              <TableCell>{{ lead.timeline }}</TableCell>
              <TableCell class="text-muted-foreground">{{ formatDate(lead.createdAt) }}</TableCell>
              <TableCell>
                <Badge :class="statusBadgeClass[lead.status]">{{ statusLabel(lead.status) }}</Badge>
              </TableCell>
              <TableCell>
                <Badge :class="priorityBadgeClass[lead.priority]">{{
                  priorityLabel(lead.priority)
                }}</Badge>
              </TableCell>
              <TableCell>
                <Badge :class="aiStatusBadgeClass[lead.aiAnalysisStatus]">
                  <Sparkles v-if="lead.aiAnalysisStatus === 'ready'" class="size-3" />
                  {{ lead.aiAnalysisStatus === 'ready' ? 'Ready' : 'Pending' }}
                </Badge>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>

      <div class="flex items-center justify-between">
        <p class="text-muted-foreground text-sm">
          Page {{ page }} of {{ totalPages }} · {{ filteredLeads.length }} leads
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
