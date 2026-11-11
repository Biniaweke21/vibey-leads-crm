import type { DateRangeKey } from '@/features/analytics/types'

export const dateRangeOptions: { value: DateRangeKey; label: string }[] = [
  { value: '7d', label: 'Last 7 Days' },
  { value: '30d', label: 'Last 30 Days' },
  { value: '90d', label: 'Last 90 Days' },
  { value: 'ytd', label: 'This Year' },
]

export function rangeToCutoff(range: DateRangeKey): Date {
  const now = new Date()
  if (range === '7d') {
    const d = new Date(now)
    d.setDate(d.getDate() - 7)
    return d
  }
  if (range === '30d') {
    const d = new Date(now)
    d.setDate(d.getDate() - 30)
    return d
  }
  if (range === '90d') {
    const d = new Date(now)
    d.setDate(d.getDate() - 90)
    return d
  }
  return new Date(now.getFullYear(), 0, 1)
}
