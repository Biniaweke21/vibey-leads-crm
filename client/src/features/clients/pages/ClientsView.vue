<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { Search, Building2, Mail, Briefcase } from '@lucide/vue'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import EmptyState from '@/components/base/EmptyState.vue'
import { useClientsStore, clientStatuses, type Client } from '@/stores/clients'
import { clientStatusBadgeClass } from '@/features/clients/data/statusMeta'
import { formatEtb } from '@/utils/currency'
import { apiErrorMessage } from '@/services/apiError'

const router = useRouter()
const toast = useToast()
const clientsStore = useClientsStore()
const loading = ref(true)

onMounted(async () => {
  try {
    await clientsStore.fetchClients()
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not load clients.'))
  } finally {
    loading.value = false
  }
})

const search = ref('')
const statusFilter = ref('all')

const industries = computed(() => [...new Set(clientsStore.clients.map((c) => c.industry))])
const industryFilter = ref('all')

const filteredClients = computed(() =>
  clientsStore.clients.filter((c) => {
    const query = search.value.trim().toLowerCase()
    const matchesSearch =
      !query ||
      c.name.toLowerCase().includes(query) ||
      c.contacts.some((contact) => contact.name.toLowerCase().includes(query))
    const matchesStatus = statusFilter.value === 'all' || c.status === statusFilter.value
    const matchesIndustry = industryFilter.value === 'all' || c.industry === industryFilter.value
    return matchesSearch && matchesStatus && matchesIndustry
  }),
)

function initials(name: string) {
  return name
    .split(' ')
    .map((part) => part[0])
    .slice(0, 2)
    .join('')
    .toUpperCase()
}

function statusLabel(status: Client['status']) {
  return clientStatuses.find((s) => s.value === status)?.label ?? status
}

function formatDate(iso: string) {
  return new Date(iso).toLocaleDateString(undefined, {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
}

function openClient(client: Client) {
  router.push(`/dashboard/clients/${client.id}`)
}
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold tracking-tight">Clients</h1>
      <p class="text-muted-foreground mt-1">Companies you've won and are actively serving.</p>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
      <div class="relative w-full sm:max-w-xs">
        <Search
          class="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2"
        />
        <Input v-model="search" placeholder="Search by company or contact…" class="pl-9" />
      </div>
      <Select v-model="statusFilter">
        <SelectTrigger class="w-full sm:w-40"><SelectValue placeholder="Status" /></SelectTrigger>
        <SelectContent>
          <SelectItem value="all">All statuses</SelectItem>
          <SelectItem v-for="s in clientStatuses" :key="s.value" :value="s.value">{{
            s.label
          }}</SelectItem>
        </SelectContent>
      </Select>
      <Select v-model="industryFilter">
        <SelectTrigger class="w-full sm:w-48"><SelectValue placeholder="Industry" /></SelectTrigger>
        <SelectContent>
          <SelectItem value="all">All industries</SelectItem>
          <SelectItem v-for="industry in industries" :key="industry" :value="industry">{{
            industry
          }}</SelectItem>
        </SelectContent>
      </Select>
    </div>

    <p v-if="loading" class="text-muted-foreground py-10 text-center text-sm">Loading clients…</p>

    <EmptyState
      v-else-if="filteredClients.length === 0"
      :icon="Building2"
      title="No clients match your filters"
      description="Try adjusting your search or filters."
    />

    <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <Card
        v-for="client in filteredClients"
        :key="client.id"
        class="hover:border-primary/40 cursor-pointer transition-colors"
        @click="openClient(client)"
      >
        <CardContent class="space-y-3">
          <div class="flex items-start justify-between gap-2">
            <div class="flex items-center gap-3">
              <Avatar class="size-10">
                <AvatarFallback>{{ initials(client.name) }}</AvatarFallback>
              </Avatar>
              <div>
                <p class="line-clamp-1 font-semibold">{{ client.name }}</p>
                <p class="text-muted-foreground text-xs">
                  {{ client.industry }} · {{ client.city }}
                </p>
              </div>
            </div>
            <Badge :class="clientStatusBadgeClass[client.status]">{{
              statusLabel(client.status)
            }}</Badge>
          </div>

          <div
            v-if="client.contacts[0]"
            class="text-muted-foreground flex items-center gap-2 text-xs"
          >
            <Mail class="size-3.5 shrink-0" />
            <span class="truncate"
              >{{ client.contacts[0].name }} · {{ client.contacts[0].email }}</span
            >
          </div>

          <div class="grid grid-cols-2 gap-3 border-t pt-3 text-sm">
            <div>
              <p class="text-muted-foreground flex items-center gap-1 text-xs">
                <Briefcase class="size-3.5" /> Active Projects
              </p>
              <p class="font-medium">{{ client.activeProjectsCount }}</p>
            </div>
            <div>
              <p class="text-muted-foreground text-xs">Total Revenue</p>
              <p class="font-medium">{{ formatEtb(clientsStore.totalRevenue(client)) }}</p>
            </div>
          </div>

          <p class="text-muted-foreground text-xs">
            Last interaction: {{ formatDate(client.lastInteraction) }}
          </p>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
