import axios from 'axios'

const TOKEN_STORAGE_KEY = 'benchmark_token'

const baseURL = import.meta.env.VITE_API_BASE_URL || 'http://localhost/api/v1'

export const api = axios.create({
  baseURL,
  headers: {
    Accept: 'application/json',
  },
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem(TOKEN_STORAGE_KEY)

  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  return config
})

export function setApiToken(token: string | null) {
  if (token) {
    localStorage.setItem(TOKEN_STORAGE_KEY, token)
    return
  }

  localStorage.removeItem(TOKEN_STORAGE_KEY)
}

export function getApiToken(): string | null {
  return localStorage.getItem(TOKEN_STORAGE_KEY)
}

export function buildApiUrl(path: string): string {
  const normalizedPath = path.startsWith('/') ? path.slice(1) : path
  return `${baseURL.replace(/\/$/, '')}/${normalizedPath}`
}
