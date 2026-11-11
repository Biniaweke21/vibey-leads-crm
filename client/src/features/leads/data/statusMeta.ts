import type { LeadStatus, LeadPriority, AiAnalysisStatus } from '@/stores/leads'

export const statusBadgeClass: Record<LeadStatus, string> = {
  new: 'bg-sky-500/10 text-sky-600 border-sky-500/20',
  contacted: 'bg-indigo-500/10 text-indigo-600 border-indigo-500/20',
  'discovery-call': 'bg-violet-500/10 text-violet-600 border-violet-500/20',
  'proposal-sent': 'bg-amber-500/10 text-amber-600 border-amber-500/20',
  negotiation: 'bg-orange-500/10 text-orange-600 border-orange-500/20',
  won: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20',
  lost: 'bg-red-500/10 text-red-600 border-red-500/20',
  'in-development': 'bg-blue-500/10 text-blue-600 border-blue-500/20',
  delivered: 'bg-teal-500/10 text-teal-600 border-teal-500/20',
  maintenance: 'bg-purple-500/10 text-purple-600 border-purple-500/20',
}

export const priorityBadgeClass: Record<LeadPriority, string> = {
  hot: 'bg-red-500/10 text-red-600 border-red-500/20',
  warm: 'bg-amber-500/10 text-amber-600 border-amber-500/20',
  cold: 'bg-sky-500/10 text-sky-600 border-sky-500/20',
}

export const priorityDotClass: Record<LeadPriority, string> = {
  hot: 'bg-red-500',
  warm: 'bg-amber-500',
  cold: 'bg-sky-500',
}

export const priorityBorderClass: Record<LeadPriority, string> = {
  hot: 'border-l-red-500',
  warm: 'border-l-amber-500',
  cold: 'border-l-sky-500',
}

export const aiStatusBadgeClass: Record<AiAnalysisStatus, string> = {
  pending: 'bg-muted text-muted-foreground border-border',
  ready: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20',
}

export const complexityBadgeClass: Record<string, string> = {
  Low: 'bg-sky-500/10 text-sky-600 border-sky-500/20',
  Medium: 'bg-amber-500/10 text-amber-600 border-amber-500/20',
  High: 'bg-orange-500/10 text-orange-600 border-orange-500/20',
  Enterprise: 'bg-red-500/10 text-red-600 border-red-500/20',
}
