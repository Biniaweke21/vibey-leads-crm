import { defineStore } from 'pinia'
import api from '@/services/api'
import type { CrmDocument, Proposal, Contract, Invoice } from '@/features/documents/types'

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function mapActivity(a: any) {
  return { id: String(a.id), text: a.description, createdAt: a.created_at }
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function mapLineItem(li: any) {
  return {
    id: String(li.id),
    description: li.description,
    quantity: Number(li.quantity),
    unitPrice: Number(li.unit_price),
  }
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function mapProposal(d: any): Proposal {
  const body = d.body ?? {}
  return {
    id: String(d.id),
    category: 'proposal',
    title: d.title,
    clientId: String(d.client_id),
    projectId: d.project_id ? String(d.project_id) : '',
    createdAt: d.created_at,
    updatedAt: d.updated_at,
    version: d.version,
    activity: (d.activities ?? []).map(mapActivity),
    status: d.status,
    executiveSummary: body.executive_summary ?? '',
    scopeOfWork: body.scope_of_work ?? '',
    deliverables: body.deliverables ?? [],
    // Backend stores the timeline as `timeline.phases` (each `{phase, duration}`
    // with no id) — TimelineSection keys on `id`, so synthesize one per phase.
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    timelinePhases: (body.timeline?.phases ?? []).map((p: any, i: number) => ({
      id: String(i),
      phase: p.phase,
      duration: p.duration,
    })),
    pricing: (d.line_items ?? []).map(mapLineItem),
    techStack: body.tech_stack ?? {},
    quotationRangeLow: Number(body.quotation?.range_low ?? 0),
    quotationRangeHigh: Number(body.quotation?.range_high ?? 0),
    quotationReasoning: body.quotation?.reasoning ?? [],
    paymentTerms: body.payment_terms ?? [],
    assumptions: body.assumptions ?? [],
    callToAction: body.call_to_action ?? '',
    terms: body.terms ?? '',
  }
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function mapContract(d: any): Contract {
  const body = d.body ?? {}
  return {
    id: String(d.id),
    category: 'contract',
    title: d.title,
    clientId: String(d.client_id),
    projectId: d.project_id ? String(d.project_id) : '',
    createdAt: d.created_at,
    updatedAt: d.updated_at,
    version: d.version,
    activity: (d.activities ?? []).map(mapActivity),
    status: d.status,
    contractTerms: body.contract_terms ?? '',
    deliverables: body.deliverables ?? [],
    startDate: body.start_date ?? d.issue_date ?? d.created_at,
    endDate: body.end_date ?? d.due_date ?? d.created_at,
    signatureStatus: d.signature_status ?? 'unsigned',
    amount: d.total,
  }
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function mapInvoice(d: any): Invoice {
  return {
    id: String(d.id),
    category: 'invoice',
    title: d.title,
    clientId: String(d.client_id),
    projectId: d.project_id ? String(d.project_id) : '',
    createdAt: d.created_at,
    updatedAt: d.updated_at,
    version: d.version,
    activity: (d.activities ?? []).map(mapActivity),
    invoiceNumber: d.number ?? '',
    status: d.status,
    lineItems: (d.line_items ?? []).map(mapLineItem),
    taxRate: Number(d.tax_rate ?? 0),
    dueDate: d.due_date ?? d.created_at,
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    paymentHistory: (d.payments ?? []).map((p: any) => ({
      id: String(p.id),
      amount: Number(p.amount),
      date: p.paid_at ?? p.created_at,
      method: p.method ?? 'Bank Transfer',
      status: p.status,
      statusLabel: p.status_label,
      stage: p.stage ?? null,
      stageLabel: p.stage_label ?? null,
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      attachments: (p.attachments ?? []).map((a: any) => ({
        id: String(a.id),
        name: a.name,
        size: a.size ?? 0,
        type: a.mime_type ?? undefined,
        url: a.url ?? undefined,
      })),
    })),
  }
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function mapDocument(d: any): CrmDocument {
  if (d.type === 'proposal') return mapProposal(d)
  if (d.type === 'contract') return mapContract(d)
  return mapInvoice(d)
}

export const useDocumentsStore = defineStore('documents', {
  state: () => ({
    documents: [] as CrmDocument[],
    loading: false,
  }),
  actions: {
    async fetchDocuments(params: Record<string, unknown> = {}) {
      this.loading = true
      try {
        const { data } = await api.get('/documents', { params: { per_page: 100, ...params } })
        this.documents = data.data.map(mapDocument)
        return this.documents
      } finally {
        this.loading = false
      }
    },
    async fetchDocument(id: string) {
      const { data } = await api.get(`/documents/${id}`)
      const doc = mapDocument(data.data)
      this.replaceDocument(doc)
      return doc
    },
    /** Raw (un-mapped) document — lets an edit form patch specific `body` keys without dropping the rest, since PUT /documents replaces `body` wholesale rather than merging it. */
    async fetchRawDocument(id: string) {
      const { data } = await api.get(`/documents/${id}`)
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      return data.data as { title: string; body: Record<string, any> }
    },
    async updateDocument(
      id: string,
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      payload: { title?: string; body?: Record<string, any> },
    ) {
      const { data } = await api.put(`/documents/${id}`, payload)
      const doc = mapDocument(data.data)
      this.replaceDocument(doc)
      return doc
    },
    /** Creates a Contract or Invoice directly (Proposals are AI-generated only — see AiAnalysisPanel). Returns the new document's id. */
    async createDocument(payload: {
      type: 'contract' | 'invoice'
      clientId: string
      projectId?: string
      title: string
      dueDate?: string
      lineItems: { description: string; quantity: number; unitPrice: number }[]
    }): Promise<string> {
      const { data } = await api.post('/documents', {
        type: payload.type,
        client_id: payload.clientId,
        project_id: payload.projectId || undefined,
        title: payload.title,
        due_date: payload.dueDate || undefined,
      })
      const id = String(data.data.id)
      for (const item of payload.lineItems) {
        await api.post(`/documents/${id}/line-items`, {
          description: item.description,
          quantity: item.quantity,
          unit_price: item.unitPrice,
        })
      }
      const doc = await this.fetchDocument(id)
      return doc.id
    },
    async logActivity() {
      // Free-text activity entries aren't backed by the API — activities are recorded automatically.
      throw new Error('Activity entries are recorded automatically and can’t be added manually.')
    },
    async send(id: string, options?: { includeStamp?: boolean; includeSignature?: boolean }) {
      const { data } = await api.post(`/documents/${id}/send`, {
        include_stamp: options?.includeStamp,
        include_signature: options?.includeSignature,
      })
      this.replaceDocument(mapDocument(data.data))
    },
    /** Records a payment for the full remaining balance — mirrors the old mock's "mark paid" shortcut. */
    async markInvoicePaid(id: string) {
      const invoice = this.documents.find((d): d is Invoice => d.id === id && d.category === 'invoice')
      if (!invoice) return
      await this.fetchDocument(id) // ensure balance_due is fresh before computing it
      const { data: fresh } = await api.get(`/documents/${id}`)
      const remaining = Number(fresh.data.balance_due)
      if (remaining > 0) {
        await api.post(`/documents/${id}/payments`, { amount: remaining, method: 'Bank Transfer' })
      }
      await this.fetchDocument(id)
    },
    /** Confirms a client-submitted payment proof — flips it to Paid, which is when it starts counting toward revenue. */
    async approvePayment(documentId: string, paymentId: string) {
      await api.put(`/documents/${documentId}/payments/${paymentId}`, { status: 'paid' })
      await this.fetchDocument(documentId)
    },
    async duplicateProposal(id: string): Promise<string | undefined> {
      const { data } = await api.post(`/documents/${id}/duplicate`)
      const copy = mapDocument(data.data)
      this.documents.unshift(copy)
      return copy.id
    },
    /** Returns the id of the newly created Contract document. */
    async convertToContract(id: string): Promise<string> {
      const { data } = await api.post(`/documents/${id}/convert-to-contract`)
      const contract = mapDocument(data.data)
      this.documents.unshift(contract)
      return contract.id
    },
    async convertToInvoice(id: string): Promise<string> {
      const { data } = await api.post(`/documents/${id}/convert-to-invoice`)
      const invoice = mapDocument(data.data)
      this.documents.unshift(invoice)
      return invoice.id
    },
    async downloadPdf(id: string) {
      const response = await api.get(`/documents/${id}/pdf`, { responseType: 'blob' })
      const match = /filename="?([^";]+)"?/.exec(response.headers['content-disposition'] ?? '')
      const filename = match?.[1] ?? `document-${id}.pdf`
      const url = URL.createObjectURL(response.data as Blob)
      const a = document.createElement('a')
      a.href = url
      a.download = filename
      a.click()
      URL.revokeObjectURL(url)
    },
    async sendReminder(id: string) {
      await api.post(`/documents/${id}/send-reminder`)
    },
    replaceDocument(doc: CrmDocument) {
      const i = this.documents.findIndex((d) => d.id === doc.id)
      if (i >= 0) this.documents[i] = doc
      else this.documents.push(doc)
    },
  },
  getters: {
    proposals: (state): Proposal[] =>
      state.documents.filter((d): d is Proposal => d.category === 'proposal'),
    contracts: (state): Contract[] =>
      state.documents.filter((d): d is Contract => d.category === 'contract'),
    invoices: (state): Invoice[] =>
      state.documents.filter((d): d is Invoice => d.category === 'invoice'),
    getProposal: (state) => (id: string) =>
      state.documents.find((d): d is Proposal => d.category === 'proposal' && d.id === id),
    getContract: (state) => (id: string) =>
      state.documents.find((d): d is Contract => d.category === 'contract' && d.id === id),
    getInvoice: (state) => (id: string) =>
      state.documents.find((d): d is Invoice => d.category === 'invoice' && d.id === id),
  },
})
