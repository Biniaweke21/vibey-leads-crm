export type DateRangeKey = '7d' | '30d' | '90d' | 'ytd'

export interface ChartPoint {
  label: string
  value: number
}

export interface TrendPoint {
  date: string
  label: string
  value: number
}

export interface TeamPerformanceRow {
  userId: number
  member: string
  role: string
  assignedLeads: number
  wonClients: number
  activeProjects: number
  revenueManaged: number
  completedTasks: number
}

export interface ActivityFeedItem {
  id: string
  module: string
  text: string
  date: string
}
