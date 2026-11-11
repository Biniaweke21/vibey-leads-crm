<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import {
  LayoutDashboard,
  Users,
  Building2,
  FolderKanban,
  FileStack,
  BarChart3,
  CalendarDays,
  Settings,
  ChevronLeft,
  ChevronRight,
} from '@lucide/vue'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip'
import { useAuthStore } from '@/stores/auth'

withDefaults(defineProps<{ collapsed?: boolean; collapsible?: boolean }>(), {
  collapsed: false,
  collapsible: false,
})
defineEmits<{ 'toggle-collapsed': [] }>()

const route = useRoute()
const authStore = useAuthStore()

const navItems = computed(() =>
  [
    { label: 'Dashboard', to: '/dashboard', icon: LayoutDashboard },
    { label: 'Leads', to: '/dashboard/leads', icon: Users },
    { label: 'Clients', to: '/dashboard/clients', icon: Building2 },
    { label: 'Projects', to: '/dashboard/projects', icon: FolderKanban },
    { label: 'Meetings', to: '/dashboard/meetings', icon: CalendarDays },
    { label: 'Documents', to: '/dashboard/documents', icon: FileStack },
    // Analytics carries revenue data — Admins only.
    { label: 'Analytics', to: '/dashboard/analytics', icon: BarChart3, adminOnly: true },
    { label: 'Settings', to: '/settings', icon: Settings },
  ].filter((item) => !item.adminOnly || authStore.isAdmin),
)

function isActive(to: string) {
  return to === '/dashboard' ? route.path === to : route.path.startsWith(to)
}
</script>

<template>
  <div class="flex h-full flex-col">
    <RouterLink to="/dashboard" class="border-border flex h-14 items-center gap-2 border-b px-4">
      <img src="/img/Vibeylogo.jpg" alt="Vibey World" class="h-8 w-auto shrink-0" />
      <span v-if="!collapsed" class="truncate font-bold">Vibey World</span>
    </RouterLink>
    <TooltipProvider :delay-duration="200">
      <nav class="flex-1 space-y-1 overflow-y-auto p-3">
        <template v-for="item in navItems" :key="item.to">
          <Tooltip v-if="collapsed">
            <TooltipTrigger as-child>
              <RouterLink
                :to="item.to"
                class="flex items-center justify-center rounded-lg px-3 py-2.5 text-sm font-medium transition-colors"
                :class="
                  isActive(item.to)
                    ? 'bg-primary/10 text-primary'
                    : 'text-foreground/80 hover:bg-accent'
                "
              >
                <component :is="item.icon" class="size-5 shrink-0" />
              </RouterLink>
            </TooltipTrigger>
            <TooltipContent side="right">{{ item.label }}</TooltipContent>
          </Tooltip>
          <RouterLink
            v-else
            :to="item.to"
            class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors"
            :class="
              isActive(item.to)
                ? 'bg-primary/10 text-primary'
                : 'text-foreground/80 hover:bg-accent'
            "
          >
            <component :is="item.icon" class="size-5 shrink-0" />
            <span>{{ item.label }}</span>
          </RouterLink>
        </template>
      </nav>
    </TooltipProvider>
    <button
      v-if="collapsible"
      type="button"
      class="border-border text-muted-foreground hover:bg-accent flex items-center justify-center gap-2 border-t p-3 text-sm transition-colors"
      @click="$emit('toggle-collapsed')"
    >
      <component :is="collapsed ? ChevronRight : ChevronLeft" class="size-4" />
      <span v-if="!collapsed">Collapse</span>
    </button>
  </div>
</template>
