<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Menu, Search, Bell, ChevronDown, LogOut, User, Settings } from '@lucide/vue'
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
import { useRemindersStore } from '@/stores/reminders'
import { useAuthStore } from '@/stores/auth'

defineEmits<{ 'open-sidebar': [] }>()

const route = useRoute()
const router = useRouter()
const remindersStore = useRemindersStore()
const authStore = useAuthStore()

onMounted(() => {
  remindersStore.fetchReminders().catch(() => {})
})

const pageLabels: Record<string, string> = {
  dashboard: 'Dashboard',
  'dashboard-leads': 'Leads',
  'dashboard-lead-detail': 'Lead Details',
  'dashboard-clients': 'Clients',
  'dashboard-client-detail': 'Client Details',
  'dashboard-projects': 'Projects',
  'dashboard-project-detail': 'Project Details',
  'dashboard-documents': 'Documents',
  'dashboard-document-proposal': 'Proposal',
  'dashboard-document-contract': 'Contract',
  'dashboard-document-invoice': 'Invoice',
  'dashboard-analytics': 'Analytics',
  'dashboard-meetings': 'Meetings',
  'dashboard-meeting-detail': 'Meeting Details',
  'settings-company': 'Settings',
  'settings-team': 'Settings',
  'settings-notifications': 'Settings',
  'settings-security': 'Settings',
}

async function logout() {
  await authStore.logout()
  router.push('/login')
}

const currentLabel = computed(() => pageLabels[route.name as string] ?? '')

const notifications = computed(() => [
  ...remindersStore.overdue.map((r) => ({ id: r.id, text: `Overdue: ${r.title}`, urgent: true })),
  ...remindersStore.dueToday.map((r) => ({
    id: r.id,
    text: `Due today: ${r.title}`,
    urgent: false,
  })),
])
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
            <RouterLink to="/dashboard">Dashboard</RouterLink>
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
      <Input placeholder="Search leads, clients, projects…" class="pl-9" />
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
              (authStore.user?.name ?? 'VW').slice(0, 2).toUpperCase()
            }}</AvatarFallback>
          </Avatar>
          <ChevronDown class="text-muted-foreground mr-1 hidden size-4 sm:block" />
        </button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end">
        <DropdownMenuLabel>
          <p>{{ authStore.user?.name ?? 'My Account' }}</p>
          <p class="text-muted-foreground text-xs font-normal">
            {{ authStore.user?.roleLabel ?? 'Team Member' }}
          </p>
        </DropdownMenuLabel>
        <DropdownMenuSeparator />
        <DropdownMenuItem><User class="mr-2 size-4" /> Profile</DropdownMenuItem>
        <DropdownMenuItem @click="router.push('/settings')">
          <Settings class="mr-2 size-4" /> Settings
        </DropdownMenuItem>
        <DropdownMenuSeparator />
        <DropdownMenuItem @click="logout">
          <LogOut class="mr-2 size-4" /> Log out
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  </header>
</template>
