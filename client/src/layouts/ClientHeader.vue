<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Menu, Search, Bell, ChevronDown, LogOut, User } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from '@/components/ui/breadcrumb'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { useClientPortalStore } from '@/features/client-portal/store/clientPortal'
import { useClientPortalAuthStore } from '@/features/client-portal/store/clientPortalAuth'

defineEmits<{ 'open-sidebar': [] }>()

const route = useRoute()
const router = useRouter()
const portalStore = useClientPortalStore()
const authStore = useClientPortalAuthStore()

onMounted(() => {
  if (!portalStore.dashboard) {
    portalStore.fetchDashboard().catch(() => {})
  }
})

const pageLabels: Record<string, string> = {
  'client-dashboard': 'Dashboard',
  'client-projects': 'Projects',
  'client-project-detail': 'Project Details',
  'client-documents': 'Documents',
  'client-meetings': 'Meetings',
  'client-meeting-detail': 'Meeting Details',
  'client-timeline': 'Timeline',
  'client-profile': 'Profile',
}

const currentLabel = computed(() => pageLabels[route.name as string] ?? '')

function initials(name: string) {
  return name
    .split(' ')
    .map((part) => part[0])
    .slice(0, 2)
    .join('')
    .toUpperCase()
}

const notifications = computed(() => {
  const dashboard = portalStore.dashboard
  if (!dashboard) return []

  const overdueInvoices = dashboard.outstanding_invoices
    .filter((i) => i.status === 'overdue')
    .map((i) => ({ id: i.id, text: `Invoice overdue: ${i.title}`, urgent: true }))

  const soonThreshold = Date.now() + 2 * 86_400_000
  const upcomingMeetings = dashboard.upcoming_meetings
    .filter((m) => new Date(m.starts_at).getTime() <= soonThreshold)
    .map((m) => ({ id: m.id, text: `Meeting soon: ${m.title}`, urgent: false }))

  return [...overdueInvoices, ...upcomingMeetings]
})

async function logout() {
  await authStore.logout()
  router.push('/client/login')
}
</script>

<template>
  <header
    class="bg-background/80 border-border sticky top-0 z-30 flex h-14 items-center gap-3 border-b px-4 backdrop-blur sm:px-6"
  >
    <Button variant="ghost" size="icon" class="md:hidden" @click="$emit('open-sidebar')">
      <Menu class="size-5" />
    </Button>

    <Breadcrumb class="hidden sm:flex">
      <BreadcrumbList>
        <BreadcrumbItem>
          <BreadcrumbLink as-child>
            <RouterLink to="/client">Dashboard</RouterLink>
          </BreadcrumbLink>
        </BreadcrumbItem>
        <template v-if="currentLabel && currentLabel !== 'Dashboard'">
          <BreadcrumbSeparator />
          <BreadcrumbItem>
            <BreadcrumbPage>{{ currentLabel }}</BreadcrumbPage>
          </BreadcrumbItem>
        </template>
      </BreadcrumbList>
    </Breadcrumb>

    <div class="flex-1"></div>

    <div class="relative hidden w-full max-w-xs sm:block">
      <Search
        class="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2"
      />
      <Input placeholder="Search projects, documents…" class="pl-9" />
    </div>

    <DropdownMenu>
      <DropdownMenuTrigger as-child>
        <Button variant="ghost" size="icon" class="relative">
          <Bell class="size-5" />
          <span
            v-if="notifications.length > 0"
            class="bg-destructive absolute top-1.5 right-1.5 size-2 rounded-full"
          />
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" class="w-80">
        <DropdownMenuLabel>Notifications</DropdownMenuLabel>
        <DropdownMenuSeparator />
        <p v-if="notifications.length === 0" class="text-muted-foreground px-2 py-3 text-sm">
          You're all caught up.
        </p>
        <DropdownMenuItem
          v-for="n in notifications"
          :key="n.id"
          :class="n.urgent ? 'text-destructive' : ''"
        >
          <p class="text-sm">{{ n.text }}</p>
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>

    <DropdownMenu>
      <DropdownMenuTrigger as-child>
        <button
          class="hover:bg-accent flex items-center gap-2 rounded-full py-1 pr-1 pl-1 transition-colors"
        >
          <Avatar class="size-8">
            <AvatarFallback>{{
              portalStore.dashboard ? initials(portalStore.dashboard.company.name) : '?'
            }}</AvatarFallback>
          </Avatar>
          <ChevronDown class="text-muted-foreground mr-1 hidden size-4 sm:block" />
        </button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end">
        <DropdownMenuLabel>{{ portalStore.dashboard?.company.name ?? 'Account' }}</DropdownMenuLabel>
        <DropdownMenuSeparator />
        <DropdownMenuItem @click="router.push('/client/profile')">
          <User class="mr-2 size-4" /> Profile
        </DropdownMenuItem>
        <DropdownMenuSeparator />
        <DropdownMenuItem @click="logout">
          <LogOut class="mr-2 size-4" /> Sign Out
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  </header>
</template>
