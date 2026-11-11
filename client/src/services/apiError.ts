import axios from 'axios'

/** Extracts the standard API envelope's `message` field, falling back for network/unexpected errors. */
export function apiErrorMessage(error: unknown, fallback = 'Something went wrong.'): string {
  if (axios.isAxiosError(error)) {
    if (!error.response) return 'Network error — check your connection and try again.'
    return error.response.data?.message ?? fallback
  }
  if (error instanceof Error) return error.message
  return fallback
}

/** The 422 envelope's `errors` map (field -> messages), or null if this wasn't a validation error. */
export function apiValidationErrors(error: unknown): Record<string, string[]> | null {
  if (axios.isAxiosError(error) && error.response?.status === 422) {
    return error.response.data?.errors ?? null
  }
  return null
}
