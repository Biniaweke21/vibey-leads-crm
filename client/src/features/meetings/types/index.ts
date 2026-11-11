export type MeetingStatus = 'upcoming' | 'completed' | 'cancelled'

export interface MeetingNote {
  id: string
  text: string
  createdAt: string
}

export interface MeetingAttendee {
  id: string
  name: string
  userId: number | null
}

export interface MeetingRef {
  id: string
  name: string
}

/** Field names mirror the backend Meeting/ClientMeeting resources directly — MeetingListItem.vue reads these as-is. */
export interface Meeting {
  id: string
  title: string
  description: string
  type: string
  type_label: string
  status: MeetingStatus
  status_label: string
  starts_at: string
  ends_at: string | null
  duration_minutes: number | null
  location: string
  meetingUrl: string | null
  client_response: 'available' | 'unavailable' | null
  client_response_label: string | null
  client_response_note: string | null
  attendees: MeetingAttendee[]
  notes: MeetingNote[]
  client: MeetingRef | null
  project: MeetingRef | null
  lead: MeetingRef | null
  createdAt: string
}
