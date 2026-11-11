import type { ClientStatus } from '@/stores/clients'

export const clientStatusBadgeClass: Record<ClientStatus, string> = {
  active: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20',
  inactive: 'bg-muted text-muted-foreground border-border',
  past: 'bg-red-500/10 text-red-600 border-red-500/20',
}
