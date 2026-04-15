import type { ApiCollectionResponse, ApiResourceResponse } from '@/types/api'
import type {
  CreateRunPayload,
  Run,
  RunAggregate,
  RunEvent,
  RunExportUrls,
  RunListFilters,
  RunSample,
} from '@/types/run'
import { api, buildApiUrl } from './api'

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

export function getRunExportUrls(runId: number): RunExportUrls {
  return {
    json: buildApiUrl(`/runs/${runId}/export?format=json`),
    csv: buildApiUrl(`/runs/${runId}/export?format=csv`),
  }
}
