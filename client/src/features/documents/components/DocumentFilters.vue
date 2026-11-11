<script setup lang="ts">
import { Search, ArrowUpDown, Grid3x3, LayoutList } from '@lucide/vue'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Button } from '@/components/ui/button'

defineProps<{
  statusOptions: { value: string; label: string }[]
  sortOptions: { value: string; label: string }[]
}>()

const search = defineModel<string>('search', { required: true })
const typeFilter = defineModel<string>('type', { required: true })
const statusFilter = defineModel<string>('status', { required: true })
const sortBy = defineModel<string>('sortBy', { required: true })
const sortDir = defineModel<'asc' | 'desc'>('sortDir', { required: true })
const layout = defineModel<'grid' | 'list'>('layout', { required: true })
</script>

<template>
  <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
    <div class="relative w-full sm:max-w-xs">
      <Search
        class="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2"
      />
      <Input v-model="search" placeholder="Search documents…" class="pl-9" />
    </div>

    <Select v-model="typeFilter">
      <SelectTrigger class="w-full sm:w-40"><SelectValue placeholder="Type" /></SelectTrigger>
      <SelectContent>
        <SelectItem value="all">All types</SelectItem>
        <SelectItem value="proposal">Proposal</SelectItem>
        <SelectItem value="contract">Contract</SelectItem>
        <SelectItem value="invoice">Invoice</SelectItem>
      </SelectContent>
    </Select>

    <Select v-model="statusFilter">
      <SelectTrigger class="w-full sm:w-40"><SelectValue placeholder="Status" /></SelectTrigger>
      <SelectContent>
        <SelectItem value="all">All statuses</SelectItem>
        <SelectItem v-for="s in statusOptions" :key="s.value" :value="s.value">{{
          s.label
        }}</SelectItem>
      </SelectContent>
    </Select>

    <div class="flex items-center gap-2">
      <Select v-model="sortBy">
        <SelectTrigger class="w-full sm:w-40"><SelectValue placeholder="Sort by" /></SelectTrigger>
        <SelectContent>
          <SelectItem v-for="s in sortOptions" :key="s.value" :value="s.value">{{
            s.label
          }}</SelectItem>
        </SelectContent>
      </Select>
      <Button
        variant="outline"
        size="icon"
        :aria-label="sortDir === 'asc' ? 'Sort ascending' : 'Sort descending'"
        @click="sortDir = sortDir === 'asc' ? 'desc' : 'asc'"
      >
        <ArrowUpDown class="size-4" />
      </Button>
    </div>

    <div class="bg-muted ml-auto flex items-center gap-1 rounded-lg p-1">
      <Button size="sm" :variant="layout === 'grid' ? 'default' : 'ghost'" @click="layout = 'grid'">
        <Grid3x3 class="size-4" />
      </Button>
      <Button size="sm" :variant="layout === 'list' ? 'default' : 'ghost'" @click="layout = 'list'">
        <LayoutList class="size-4" />
      </Button>
    </div>
  </div>
</template>
