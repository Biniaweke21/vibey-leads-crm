import { defineStore } from 'pinia'
import api from '@/services/api'

export interface Reminder {
  id: string
  title: string
  dueAt: string
  done: boolean
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function mapReminder(r: any): Reminder {
  return { id: String(r.id), title: r.title, dueAt: r.due_at, done: r.completed_at !== null }
}

function isSameDay(a: Date, b: Date) {
  return a.toDateString() === b.toDateString()
}

export const useRemindersStore = defineStore('reminders', {
  state: () => ({
    reminders: [] as Reminder[],
  }),
  actions: {
    async fetchReminders() {
      const { data } = await api.get('/reminders')
      this.reminders = data.data.map(mapReminder)
      return this.reminders
    },
    async addReminderForLead(leadId: string, title: string, dueAt: string) {
      const { data } = await api.post(`/leads/${leadId}/reminders`, { title, due_at: dueAt })
      const reminder = mapReminder(data.data)
      this.reminders.push(reminder)
      return reminder
    },
    async toggleDone(id: string) {
      const reminder = this.reminders.find((r) => r.id === id)
      if (!reminder) return
      const { data } = await api.put(`/reminders/${id}`, { completed: !reminder.done })
      Object.assign(reminder, mapReminder(data.data))
    },
    async removeReminder(id: string) {
      await api.delete(`/reminders/${id}`)
      this.reminders = this.reminders.filter((r) => r.id !== id)
    },
  },
  getters: {
    overdue: (state) => {
      const now = new Date()
      return state.reminders
        .filter((r) => !r.done && new Date(r.dueAt) < now && !isSameDay(new Date(r.dueAt), now))
        .sort((a, b) => a.dueAt.localeCompare(b.dueAt))
    },
    dueToday: (state) => {
      const now = new Date()
      return state.reminders
        .filter((r) => !r.done && isSameDay(new Date(r.dueAt), now))
        .sort((a, b) => a.dueAt.localeCompare(b.dueAt))
    },
    upcoming: (state) => {
      const now = new Date()
      return state.reminders
        .filter((r) => !r.done && new Date(r.dueAt) > now && !isSameDay(new Date(r.dueAt), now))
        .sort((a, b) => a.dueAt.localeCompare(b.dueAt))
    },
  },
})
