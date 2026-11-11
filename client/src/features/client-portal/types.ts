import type { DocumentStatus } from '@/features/documents/types'

export interface PortalUserRef {
  id: number
  name: string
  email: string
  role: string
  role_label: string
}

export interface PortalMilestone {
  id: number
  title: string
  description: string | null
  due_date: string | null
  is_done: boolean
}

export interface PortalTask {
  id: number
  title: string
  description: string | null
  status: string
  status_label: string
  priority: string
  priority_label: string
  due_date: string | null
}

export interface PortalDesignSubmission {
  id: number
  name: string
  description: string | null
  version: number
  url: string | null
  status: string
  status_label: string
  note: string | null
  submitted_at: string | null
  decided_at: string | null
}

export interface PortalAttachment {
  id: number
  name: string
  mime_type: string | null
  size: number | null
  url: string | null
  uploaded_by_role: 'team' | 'client'
  uploader?: { id: number; name: string } | null
  created_at: string
}

export interface PortalProject {
  id: number
  name: string
  description: string | null
  status: string
  status_label: string
  progress: number
  budget: string
  start_date: string | null
  end_date: string | null
  members: PortalUserRef[]
  milestones: PortalMilestone[]
  tasks: PortalTask[]
  design_submissions: PortalDesignSubmission[]
  attachments: PortalAttachment[]
  created_at: string
  updated_at: string
}

export interface PortalLineItem {
  id: number
  description: string
  quantity: string
  unit_price: string
  tax_rate: string
  discount: string
  subtotal: number
  tax_amount: number
  total: number
}

export interface PortalPayment {
  id: number
  amount: string
  status: string
  status_label: string
  method: string
  stage: string | null
  stage_label: string | null
  reference_number: string | null
  paid_at: string | null
  attachments: PortalAttachment[]
}

export interface PortalDocument {
  id: number
  type: 'proposal' | 'contract' | 'invoice'
  type_label: string
  title: string
  number: string | null
  status: DocumentStatus
  status_label: string
  currency: string
  issue_date: string | null
  due_date: string | null
  signature_status: string | null
  signature_status_label: string | null
  body: Record<string, unknown> | unknown[]
  subtotal: number
  discount_total: number
  tax_total: number
  total: number
  amount_paid: number
  balance_due: number
  line_items: PortalLineItem[]
  payments: PortalPayment[]
  project_id: number | null
  project?: PortalProject | null
  created_at: string
  updated_at: string
}

export interface PortalMeetingAttendee {
  id: number
  name: string
  user_id: number | null
  user?: PortalUserRef | null
}

export interface PortalMeeting {
  id: number
  title: string
  description: string | null
  type: string
  type_label: string
  status: string
  status_label: string
  starts_at: string
  ends_at: string | null
  duration_minutes: number | null
  timezone: string | null
  location: string | null
  meeting_url: string | null
  client_response: 'available' | 'unavailable' | null
  client_response_label: string | null
  client_response_note: string | null
  organizer?: PortalUserRef | null
  attendees: PortalMeetingAttendee[]
  project_id: number | null
  project?: PortalProject | null
  created_at: string
  updated_at: string
}

export interface PortalCompany {
  id: number
  name: string
  industry: string | null
  city: string | null
  status: string
  status_label: string
}

export interface PortalActivity {
  id: number
  description: string
  user?: { id: number; name: string } | null
  created_at: string
}

export interface PortalDashboard {
  welcome_name: string
  company: PortalCompany
  documents_count: number
  active_projects: PortalProject[]
  recent_documents: PortalDocument[]
  outstanding_invoices: PortalDocument[]
  outstanding_total: number
  upcoming_meetings: PortalMeeting[]
  recent_activity: PortalActivity[]
}

export interface PortalTimelineEntry {
  module: string
  description: string
  date: string
}

export interface PortalContact {
  id: number
  name: string
  role: string | null
  email: string
  phone: string | null
  is_primary: boolean
}

export interface PortalNotificationPreferences {
  project_updates: boolean
  invoices: boolean
  meeting_reminders: boolean
  marketing: boolean
}

export interface PortalProfile {
  company: PortalCompany
  account: { id: number; name: string; email: string }
  primary_contact: PortalContact | null
  secondary_contacts: PortalContact[]
  notification_preferences: PortalNotificationPreferences
}
