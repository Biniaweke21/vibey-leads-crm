<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useToast } from 'vue-toastification'
import { Plus, List, CalendarDays, CalendarClock } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Card, CardContent } from '@/components/ui/card'
import EmptyState from '@/components/base/EmptyState.vue'
import MeetingCalendar from '@/features/meetings/components/MeetingCalendar.vue'
import MeetingListItem from '@/features/meetings/components/MeetingListItem.vue'
import CreateMeetingDialog from '@/features/meetings/components/CreateMeetingDialog.vue'
import { useMeetingsStore } from '@/features/meetings/store/meetings'
import { apiErrorMessage } from '@/services/apiError'

const meetingsStore = useMeetingsStore()
const toast = useToast()
const loading = ref(true)

onMounted(async () => {
  try {
    await meetingsStore.fetchMeetings()
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not load meetings.'))
  } finally {
    loading.value = false
  }
})

const view = ref<'list' | 'calendar'>('list')
const filter = ref<'upcoming' | 'past'>('upcoming')
const createOpen = ref(false)
const selectedDay = ref<Date | null>(new Date())

const filteredList = computed(() =>
  filter.value === 'upcoming' ? meetingsStore.upcoming : meetingsStore.past,
)

const meetingsOnSelectedDay = computed(() => {
  if (!selectedDay.value) return []
  return meetingsStore.meetings
    .filter((m) => new Date(m.starts_at).toDateString() === selectedDay.value!.toDateString())
    .sort((a, b) => a.starts_at.localeCompare(b.starts_at))
})

function formatDay(date: Date) {
  return date.toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric' })
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight">Meetings</h1>
        <p class="text-muted-foreground mt-1">
          Every call and meeting, linked back to the client or project it's for.
        </p>
      </div>
      <Button @click="createOpen = true"><Plus class="size-4" /> New Meeting</Button>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-4">
      <Tabs v-if="view === 'list'" v-model="filter">
        <TabsList>
          <TabsTrigger value="upcoming">Upcoming</TabsTrigger>
          <TabsTrigger value="past">Past</TabsTrigger>
        </TabsList>
      </Tabs>
      <div v-else />
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
          :variant="view === 'calendar' ? 'default' : 'ghost'"
          class="gap-1.5"
          @click="view = 'calendar'"
        >
          <CalendarDays class="size-4" /> Calendar
        </Button>
      </div>
    </div>

    <div v-if="view === 'list'" class="space-y-3">
      <p v-if="loading" class="text-muted-foreground py-10 text-center text-sm">
        Loading meetings…
      </p>
      <EmptyState
        v-else-if="filteredList.length === 0"
        :icon="CalendarClock"
        :title="`No ${filter} meetings`"
      />
      <MeetingListItem v-for="meeting in filteredList" :key="meeting.id" :meeting="meeting" />
    </div>

    <div v-else class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <Card class="lg:col-span-2">
        <CardContent>
          <MeetingCalendar
            :meetings="meetingsStore.meetings"
            @select="(date) => (selectedDay = date)"
          />
        </CardContent>
      </Card>
      <div class="space-y-3">
        <p class="text-muted-foreground text-sm font-medium">
          {{ selectedDay ? formatDay(selectedDay) : 'Select a day' }}
        </p>
        <EmptyState
          v-if="meetingsOnSelectedDay.length === 0"
          :icon="CalendarClock"
          title="No meetings this day"
        />
        <MeetingListItem
          v-for="meeting in meetingsOnSelectedDay"
          :key="meeting.id"
          :meeting="meeting"
        />
      </div>
    </div>

    <CreateMeetingDialog v-model:open="createOpen" />
  </div>
</template>
