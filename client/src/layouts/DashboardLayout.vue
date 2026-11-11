<script setup lang="ts">
import { ref } from 'vue'
import { Sheet, SheetContent, SheetTitle } from '@/components/ui/sheet'
import DashboardSidebar from './DashboardSidebar.vue'
import DashboardHeader from './DashboardHeader.vue'

const collapsed = ref(false)
const mobileOpen = ref(false)
</script>

<template>
  <div class="flex min-h-svh">
    <aside
      class="border-border sticky top-0 hidden h-svh shrink-0 border-r transition-all duration-300 md:block"
      :class="collapsed ? 'w-18' : 'w-64'"
    >
      <DashboardSidebar
        :collapsed="collapsed"
        collapsible
        @toggle-collapsed="collapsed = !collapsed"
      />
    </aside>

    <Sheet v-model:open="mobileOpen">
      <SheetContent side="left" class="w-72 p-0">
        <SheetTitle class="sr-only">Navigation</SheetTitle>
        <DashboardSidebar />
      </SheetContent>
    </Sheet>

    <div class="flex min-w-0 flex-1 flex-col">
      <DashboardHeader @open-sidebar="mobileOpen = true" />
      <main class="flex-1 p-4 sm:p-6">
        <RouterView />
      </main>
    </div>
  </div>
</template>
