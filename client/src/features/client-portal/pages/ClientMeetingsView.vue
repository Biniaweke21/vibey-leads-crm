<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useToast } from 'vue-toastification'
import { CalendarClock, Plus } from '@lucide/vue'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Button } from '@/components/ui/button'
import EmptyState from '@/components/base/EmptyState.vue'
import MeetingListItem from '@/features/meetings/components/MeetingListItem.vue'
import BookMeetingDialog from '@/features/client-portal/components/BookMeetingDialog.vue'
import { useClientPortalStore } from '@/features/client-portal/store/clientPortal'
import { apiErrorMessage } from '@/services/apiError'

const toast = useToast()
const portalStore = useClientPortalStore()
const filter = ref<'upcoming' | 'past'>('upcoming')
const loading = ref(true)
const bookOpen = ref(false)

async function load() {
  loading.value = true
  try {
    await portalStore.fetchMeetings({ when: filter.value })
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not load your meetings.'))
  } finally {
    loading.value = false
  }
}

onMounted(load)
watch(filter, load)
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-start justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold tracking-tight">Meetings</h1>
        <p class="text-muted-foreground mt-1">Every call scheduled with Vibey World.</p>
      </div>
      <Button @click="bookOpen = true"><Plus class="size-4" /> Book a Meeting</Button>
    </div>

    <BookMeetingDialog v-model:open="bookOpen" @booked="load" />

    <Tabs v-model="filter">
      <TabsList>
        <TabsTrigger value="upcoming">Upcoming</TabsTrigger>
        <TabsTrigger value="past">Past</TabsTrigger>
      </TabsList>
    </Tabs>

    <div class="space-y-3">
      <EmptyState
        v-if="!loading && portalStore.meetings.length === 0"
        :icon="CalendarClock"
        :title="`No ${filter} meetings`"
      />
      <MeetingListItem
        v-for="meeting in portalStore.meetings"
        :key="meeting.id"
        :meeting="meeting"
        base-path="/client/meetings"
        :show-client="false"
      />
    </div>
  </div>
</template>
