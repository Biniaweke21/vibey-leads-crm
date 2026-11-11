import type {
  ProposalStatus,
  ContractStatus,
  InvoiceStatus,
  SignatureState,
} from '@/features/documents/types'

export const proposalStatusMeta: Record<ProposalStatus, { label: string; class: string }> = {
  draft: { label: 'Draft', class: 'bg-muted text-muted-foreground border-border' },
  sent: { label: 'Sent', class: 'bg-sky-500/10 text-sky-600 border-sky-500/20' },
  approved: {
    label: 'Approved',
    class: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20',
  },
  rejected: { label: 'Rejected', class: 'bg-red-500/10 text-red-600 border-red-500/20' },
}

export const contractStatusMeta: Record<ContractStatus, { label: string; class: string }> = {
  draft: { label: 'Draft', class: 'bg-muted text-muted-foreground border-border' },
  sent: { label: 'Sent', class: 'bg-sky-500/10 text-sky-600 border-sky-500/20' },
  signed: { label: 'Signed', class: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' },
  expired: { label: 'Expired', class: 'bg-red-500/10 text-red-600 border-red-500/20' },
}

export const invoiceStatusMeta: Record<InvoiceStatus, { label: string; class: string }> = {
  draft: { label: 'Draft', class: 'bg-muted text-muted-foreground border-border' },
  sent: { label: 'Sent', class: 'bg-sky-500/10 text-sky-600 border-sky-500/20' },
  'partially-paid': {
    label: 'Partially Paid',
    class: 'bg-amber-500/10 text-amber-600 border-amber-500/20',
  },
  paid: { label: 'Paid', class: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' },
  overdue: { label: 'Overdue', class: 'bg-red-500/10 text-red-600 border-red-500/20' },
}

export const signatureStateMeta: Record<SignatureState, { label: string; class: string }> = {
  unsigned: { label: 'Unsigned', class: 'bg-muted text-muted-foreground border-border' },
  'client-signed': {
    label: 'Client Signed',
    class: 'bg-amber-500/10 text-amber-600 border-amber-500/20',
  },
  'fully-signed': {
    label: 'Fully Signed',
    class: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20',
  },
}

export const categoryMeta: Record<'proposal' | 'contract' | 'invoice', { label: string }> = {
  proposal: { label: 'Proposal' },
  contract: { label: 'Contract' },
  invoice: { label: 'Invoice' },
}
