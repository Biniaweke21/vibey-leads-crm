import type { ProjectStatus } from '@/stores/projects'

export const projectStatusBadgeClass: Record<ProjectStatus, string> = {
  planning: 'bg-sky-500/10 text-sky-600 border-sky-500/20',
  'in-progress': 'bg-amber-500/10 text-amber-600 border-amber-500/20',
  'on-hold': 'bg-orange-500/10 text-orange-600 border-orange-500/20',
  completed: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20',
  maintenance: 'bg-purple-500/10 text-purple-600 border-purple-500/20',
}

export const taskStatusBadgeClass: Record<'todo' | 'in-progress' | 'done', string> = {
  todo: 'bg-muted text-muted-foreground border-border',
  'in-progress': 'bg-amber-500/10 text-amber-600 border-amber-500/20',
  done: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20',
}
