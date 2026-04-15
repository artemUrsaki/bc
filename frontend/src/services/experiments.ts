import type { ApiCollectionResponse, ApiResourceResponse } from '@/types/api'
import type { CreateExperimentPayload, Experiment } from '@/types/experiment'
import { api } from './api'

export async function fetchExperiments() {
  const { data } = await api.get<ApiCollectionResponse<Experiment>>('/experiments')
  return data
}

export async function fetchExperiment(experimentId: number) {
  const { data } = await api.get<ApiResourceResponse<Experiment>>(`/experiments/${experimentId}`)
  return data
}

export async function createExperiment(payload: CreateExperimentPayload) {
  const { data } = await api.post<ApiResourceResponse<Experiment>>('/experiments', payload)
  return data
}
