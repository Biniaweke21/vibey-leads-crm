<script setup lang="ts">
import { ref } from 'vue'
import { Pencil, Trash2, StickyNote } from '@lucide/vue'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Textarea } from '@/components/ui/textarea'
import { Button } from '@/components/ui/button'
import EmptyState from '@/components/base/EmptyState.vue'

export interface NoteLike {
  id: string
  text: string
  createdAt: string
}

withDefaults(defineProps<{ title?: string; notes: NoteLike[] }>(), { title: 'Notes' })
const emit = defineEmits<{
  add: [text: string]
  update: [id: string, text: string]
  remove: [id: string]
}>()

const draft = ref('')
const editingId = ref<string | null>(null)
const editDraft = ref('')

function addNote() {
  if (!draft.value.trim()) return
  emit('add', draft.value.trim())
  draft.value = ''
}

function startEdit(noteId: string, text: string) {
  editingId.value = noteId
  editDraft.value = text
}

function saveEdit(noteId: string) {
  if (!editDraft.value.trim()) return
  emit('update', noteId, editDraft.value.trim())
  editingId.value = null
}

function formatDate(iso: string) {
  return new Date(iso).toLocaleString(undefined, {
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>{{ title }}</CardTitle>
    </CardHeader>
    <CardContent class="space-y-4">
      <div class="space-y-2">
        <Textarea v-model="draft" placeholder="Add a note…" rows="3" />
        <Button size="sm" :disabled="!draft.trim()" @click="addNote">Add Note</Button>
      </div>

      <EmptyState v-if="notes.length === 0" :icon="StickyNote" title="No notes yet" />

      <div v-else class="space-y-3">
        <div v-for="note in notes" :key="note.id" class="rounded-lg border p-3">
          <template v-if="editingId === note.id">
            <Textarea v-model="editDraft" rows="3" />
            <div class="mt-2 flex gap-2">
              <Button size="sm" @click="saveEdit(note.id)">Save</Button>
              <Button size="sm" variant="ghost" @click="editingId = null">Cancel</Button>
            </div>
          </template>
          <template v-else>
            <p class="text-sm whitespace-pre-wrap">{{ note.text }}</p>
            <div class="mt-2 flex items-center justify-between">
              <p class="text-muted-foreground text-xs">{{ formatDate(note.createdAt) }}</p>
              <div class="flex gap-1">
                <Button variant="ghost" size="icon-sm" @click="startEdit(note.id, note.text)">
                  <Pencil class="size-3.5" />
                </Button>
                <Button variant="ghost" size="icon-sm" @click="emit('remove', note.id)">
                  <Trash2 class="size-3.5" />
                </Button>
              </div>
            </div>
          </template>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
