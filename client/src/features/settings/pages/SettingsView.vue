<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { Building2, Users, Bell, Shield } from '@lucide/vue'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const authStore = useAuthStore()

const allNavItems = [
  { label: 'Company', to: '/settings/company', icon: Building2, adminOnly: true },
  { label: 'Team', to: '/settings/team', icon: Users, adminOnly: true },
  { label: 'Notifications', to: '/settings/notifications', icon: Bell, adminOnly: false },
  { label: 'Security', to: '/settings/security', icon: Shield, adminOnly: false },
]

const navItems = computed(() =>
  allNavItems.filter((item) => !item.adminOnly || authStore.isAdmin),
)

function isActive(to: string) {
  return route.path === to
}
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold tracking-tight">Settings</h1>
      <p class="text-muted-foreground mt-1">Manage your workspace, team, and integrations.</p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
      <nav class="flex gap-1 overflow-x-auto lg:col-span-1 lg:flex-col lg:overflow-visible">
        <RouterLink
          v-for="item in navItems"
          :key="item.to"
          :to="item.to"
          class="flex shrink-0 items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-colors lg:shrink"
          :class="
            isActive(item.to)
              ? 'bg-accent text-foreground'
              : 'text-muted-foreground hover:bg-accent'
          "
        >
          <component :is="item.icon" class="size-4 shrink-0" />
          <span class="whitespace-nowrap">{{ item.label }}</span>
        </RouterLink>
      </nav>
      <div class="lg:col-span-3">
        <RouterView />
      </div>
    </div>
  </div>
</template>
