<script setup lang="ts">
import { useRoute } from 'vue-router'
import {
  LayoutDashboard,
  FolderKanban,
  FileStack,
  CalendarDays,
  History,
  User,
  ChevronLeft,
  ChevronRight,
} from '@lucide/vue'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip'

withDefaults(defineProps<{ collapsed?: boolean; collapsible?: boolean }>(), {
  collapsed: false,
  collapsible: false,
})
defineEmits<{ 'toggle-collapsed': [] }>()

const route = useRoute()

const navItems = [
  { label: 'Dashboard', to: '/client', icon: LayoutDashboard },
  { label: 'Projects', to: '/client/projects', icon: FolderKanban },
  { label: 'Documents', to: '/client/documents', icon: FileStack },
  { label: 'Meetings', to: '/client/meetings', icon: CalendarDays },
  { label: 'Timeline', to: '/client/timeline', icon: History },
  { label: 'Profile', to: '/client/profile', icon: User },
]

function isActive(to: string) {
  return to === '/client' ? route.path === to : route.path.startsWith(to)
}
</script>

<template>
  <div class="flex h-full flex-col">
    <RouterLink to="/client" class="border-border flex h-14 items-center gap-2 border-b px-4">
      <img src="/img/Vibeylogo.jpg" alt="Vibey World" class="h-8 w-auto shrink-0" />
      <span v-if="!collapsed" class="truncate font-bold">Client Portal</span>
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
