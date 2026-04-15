import type { ApiCollectionResponse, ApiResourceResponse } from '@/types/api'
import type {
  CreateRunPayload,
  Run,
  RunAggregate,
  RunEvent,
  RunListFilters,
  RunSample,
} from '@/types/run'
import { api } from './api'

export async function fetchRuns(filters: RunListFilters = {}) {
  const { data } = await api.get<ApiCollectionResponse<Run>>('/runs', {
    params: filters,
  })

  return data
}

export async function fetchRun(runId: number) {
  const { data } = await api.get<ApiResourceResponse<Run>>(`/runs/${runId}`)
  return data
}

export async function createRun(payload: CreateRunPayload) {
  const { data } = await api.post<ApiResourceResponse<Run>>('/runs', payload)
  return data
}

export async function fetchRunAggregate(runId: number) {
  const { data } = await api.get<ApiResourceResponse<RunAggregate>>(`/runs/${runId}/aggregates`)
  return data
}

export async function fetchRunSamples(runId: number, success?: boolean) {
  const { data } = await api.get<ApiCollectionResponse<RunSample>>(`/runs/${runId}/samples`, {
    params: typeof success === 'boolean' ? { success: success ? 1 : 0 } : undefined,
  })

  return data
}

export async function fetchRunEvents(runId: number) {
  const { data } = await api.get<ApiCollectionResponse<RunEvent>>(`/runs/${runId}/events`)
  return data
}

export async function downloadRunExport(runId: number, format: 'json' | 'csv') {
  const response = await api.get(`/runs/${runId}/export?format=${format}`, {
    responseType: 'blob',
  })

  const contentType = response.headers['content-type'] ?? 'application/octet-stream'
  const blob = new Blob([response.data], { type: contentType })
  const objectUrl = URL.createObjectURL(blob)
  const anchor = document.createElement('a')

  anchor.href = objectUrl
  anchor.download = `run-${runId}.${format}`
  document.body.appendChild(anchor)
  anchor.click()
  document.body.removeChild(anchor)
  URL.revokeObjectURL(objectUrl)
}
