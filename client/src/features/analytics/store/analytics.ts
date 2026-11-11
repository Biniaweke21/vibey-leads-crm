import { defineStore } from 'pinia'
import type { DateRangeKey } from '@/features/analytics/types'

export const useAnalyticsStore = defineStore('analytics', {
  state: () => ({
    dateRange: '30d' as DateRangeKey,
  }),
  actions: {
    setDateRange(range: DateRangeKey) {
      this.dateRange = range
    },
  },
})
