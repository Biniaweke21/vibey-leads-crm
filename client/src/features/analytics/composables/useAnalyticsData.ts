import { defineStore } from 'pinia'
import api from '@/services/api'
import { leadStatuses, type LeadStatus } from '@/stores/leads'
import type {
  ChartPoint,
  TrendPoint,
  TeamPerformanceRow,
  ActivityFeedItem,
} from '@/features/analytics/types'

interface PipelinePoint {
  label: string
  value: number
  status: LeadStatus
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function mapChart(rows: any[]): ChartPoint[] {
  return rows.map((r) => ({ label: r.label, value: r.value }))
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function mapTrend(rows: any[]): TrendPoint[] {
  return rows.map((r) => ({ date: r.label, label: r.label, value: r.value }))
}

export const useAnalyticsDataStore = defineStore('analyticsData', {
  state: () => ({
    totalLeads: 0,
    activeClients: 0,
    activeProjects: 0,
    revenue: 0,
    proposalConversionRate: 0,
    invoiceCollectionRate: 0,
    averageProjectValue: 0,
    averageLeadValue: 0,

    revenueTrend: [] as TrendPoint[],
    leadsTrend: [] as TrendPoint[],
    pipeline: [] as PipelinePoint[],
    serviceDistribution: [] as ChartPoint[],
    projectStatusCounts: [] as ChartPoint[],
    revenueByClient: [] as ChartPoint[],
    proposalSuccess: [] as ChartPoint[],
    invoiceStatusCounts: [] as ChartPoint[],
    teamPerformance: [] as TeamPerformanceRow[],
    recentActivity: [] as ActivityFeedItem[],

    loading: false,
  }),
  actions: {
    /** Lighter fetch for dashboard-home widgets that only need revenue + the activity feed. */
    async fetchSummary(range = '30d') {
      const params = { range }
      const [dashboard, activity] = await Promise.all([
        api.get('/analytics/dashboard', { params }),
        api.get('/analytics/activity', { params }),
      ])
      this.revenue = dashboard.data.data.total_revenue
      this.activeProjects = dashboard.data.data.active_projects
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      this.recentActivity = activity.data.data.map((a: any, i: number) => ({
        id: `activity-${i}`,
        module: a.module,
        text: a.description,
        date: a.date,
      }))
    },
    async fetchAll(range: string) {
      this.loading = true
      try {
        const params = { range }
        const [
          dashboard,
          revenueTrend,
          leadsTrend,
          pipeline,
          services,
          projectStatus,
          revenueByClient,
          proposals,
          invoices,
          teamPerformance,
          activity,
        ] = await Promise.all([
          api.get('/analytics/dashboard', { params }),
          api.get('/analytics/revenue-trend', { params }),
          api.get('/analytics/leads-trend', { params }),
          api.get('/analytics/pipeline', { params }),
          api.get('/analytics/services', { params }),
          api.get('/analytics/project-status', { params }),
          api.get('/analytics/revenue-by-client', { params }),
          api.get('/analytics/proposals', { params }),
          api.get('/analytics/invoices', { params }),
          api.get('/analytics/team-performance', { params }),
          api.get('/analytics/activity', { params }),
        ])

        const d = dashboard.data.data
        this.totalLeads = d.total_leads
        this.activeClients = d.active_clients
        this.activeProjects = d.active_projects
        this.revenue = d.total_revenue
        this.proposalConversionRate = d.proposal_conversion_rate
        this.invoiceCollectionRate = d.invoice_collection_rate
        this.averageProjectValue = d.average_project_value
        this.averageLeadValue = d.average_lead_value

        this.revenueTrend = mapTrend(revenueTrend.data.data)
        this.leadsTrend = mapTrend(leadsTrend.data.data)
        // Backend returns pipeline stages in LeadStatus::cases() order — same order as leadStatuses.
        this.pipeline = mapChart(pipeline.data.data).map((point, i) => ({
          ...point,
          status: leadStatuses[i]?.value ?? 'new',
        }))
        this.serviceDistribution = mapChart(services.data.data)
        this.projectStatusCounts = mapChart(projectStatus.data.data)
        this.revenueByClient = mapChart(revenueByClient.data.data)
        this.proposalSuccess = mapChart(proposals.data.data)
        this.invoiceStatusCounts = mapChart(invoices.data.data)

        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        this.teamPerformance = teamPerformance.data.data.map((r: any) => ({
          userId: r.user_id,
          member: r.name,
          role: r.role,
          assignedLeads: r.assigned_leads,
          wonClients: r.won_clients,
          activeProjects: r.active_projects,
          revenueManaged: r.revenue_managed,
          completedTasks: r.completed_tasks,
        }))

        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        this.recentActivity = activity.data.data.map((a: any, i: number) => ({
          id: `activity-${i}`,
          module: a.module,
          text: a.description,
          date: a.date,
        }))
      } finally {
        this.loading = false
      }
    },
  },
})
