export type DocumentCategory = 'proposal' | 'contract' | 'invoice'

export interface DocumentActivityEntry {
  id: string
  text: string
  createdAt: string
}

export type ProposalStatus = 'draft' | 'sent' | 'approved' | 'rejected'
export type ContractStatus = 'draft' | 'sent' | 'signed' | 'expired'
export type InvoiceStatus = 'draft' | 'sent' | 'partially-paid' | 'paid' | 'overdue'
export type DocumentStatus = ProposalStatus | ContractStatus | InvoiceStatus

export interface PricingLineItem {
  id: string
  description: string
  quantity: number
  unitPrice: number
}

export type SignatureState = 'unsigned' | 'client-signed' | 'fully-signed'

interface DocumentBase {
  id: string
  title: string
  clientId: string
  projectId: string
  createdAt: string
  updatedAt: string
  version: number
  activity: DocumentActivityEntry[]
}

export interface ProposalTechChoice {
  choice: string
  why: string
}

export interface Proposal extends DocumentBase {
  category: 'proposal'
  status: ProposalStatus
  executiveSummary: string
  scopeOfWork: string
  deliverables: string[]
  techStack: Record<string, ProposalTechChoice>
  timelinePhases: { id: string; phase: string; duration: string }[]
  pricing: PricingLineItem[]
  quotationRangeLow: number
  quotationRangeHigh: number
  quotationReasoning: string[]
  paymentTerms: { label: string; percentage: number }[]
  assumptions: string[]
  callToAction: string
  terms: string
}

export interface Contract extends DocumentBase {
  category: 'contract'
  status: ContractStatus
  contractTerms: string
  deliverables: string[]
  startDate: string
  endDate: string
  signatureStatus: SignatureState
  amount: number
}

export interface InvoicePayment {
  id: string
  amount: number
  date: string
  method: string
  status: 'pending' | 'paid'
  statusLabel: string
  stage: string | null
  stageLabel: string | null
  attachments: { id: string; name: string; size: number; type?: string; url?: string }[]
}

export interface Invoice extends DocumentBase {
  category: 'invoice'
  invoiceNumber: string
  status: InvoiceStatus
  lineItems: PricingLineItem[]
  taxRate: number
  dueDate: string
  paymentHistory: InvoicePayment[]
}

export type CrmDocument = Proposal | Contract | Invoice

export function lineItemsTotal(items: PricingLineItem[]): number {
  return items.reduce((sum, item) => sum + item.quantity * item.unitPrice, 0)
}

export function documentAmount(doc: CrmDocument): number {
  if (doc.category === 'proposal') return lineItemsTotal(doc.pricing)
  if (doc.category === 'contract') return doc.amount
  const subtotal = lineItemsTotal(doc.lineItems)
  return subtotal + subtotal * (doc.taxRate / 100)
}
