import api from '@/services/api'

/** A file attachment carried by leads, projects, clients, and design reviews. */
export interface Attachment {
  id?: string
  name: string
  size: number
  type?: string
  /** A real, openable/downloadable URL (Cloudinary) once uploaded. */
  url?: string
  uploadedBy?: 'team' | 'client'
  createdAt?: string
}

/** What POST /api/uploads returns — passed straight into a module's attachment-create endpoint. */
export interface UploadedFileMeta {
  name: string
  mime_type: string | null
  size: number
  url: string
  secure_url: string
  cloudinary_public_id: string
}

/** Uploads picked files to Cloudinary (via the backend) and returns their metadata. */
export async function uploadFiles(files: File[]): Promise<UploadedFileMeta[]> {
  return Promise.all(
    files.map(async (file) => {
      const form = new FormData()
      form.append('file', file)
      const { data } = await api.post('/uploads', form, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      return data.data as UploadedFileMeta
    }),
  )
}

export function isImage(att: Pick<Attachment, 'name' | 'type'>): boolean {
  return !!att.type?.startsWith('image/') || /\.(png|jpe?g|gif|webp|svg|avif)$/i.test(att.name)
}

export function formatSize(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

/** Opens an attachment's real URL in a new tab. */
export function openAttachment(att: Attachment) {
  if (att.url) window.open(att.url, '_blank', 'noopener')
}

/** Triggers a browser download of the attachment. */
export function downloadAttachment(att: Attachment) {
  if (!att.url) return
  const a = document.createElement('a')
  a.href = att.url
  a.download = att.name
  a.target = '_blank'
  a.rel = 'noopener'
  a.click()
}
