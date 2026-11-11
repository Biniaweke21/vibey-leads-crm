const TEAM_TOKEN_KEY = 'vibey-api-token-team'
const CLIENT_TOKEN_KEY = 'vibey-api-token-client'

/**
 * The team dashboard and the client portal are independent sessions that
 * can legitimately be open in the same browser at once (e.g. an admin
 * testing the client experience in another tab) — they must never share a
 * token. Which one applies is inferred from the current path, since that's
 * always true at the moment a token is read, written, or cleared (login,
 * logout, and every request interceptor call happen from a page under
 * either `/client/*` or the team dashboard).
 */
function storageKey(): string {
  return window.location.pathname.startsWith('/client') ? CLIENT_TOKEN_KEY : TEAM_TOKEN_KEY
}

export function getToken(): string | null {
  return localStorage.getItem(storageKey())
}

export function setToken(token: string): void {
  localStorage.setItem(storageKey(), token)
}

export function clearToken(): void {
  localStorage.removeItem(storageKey())
}
