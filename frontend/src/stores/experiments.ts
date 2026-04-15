import { defineStore } from 'pinia'
import type { CreateExperimentPayload, Experiment } from '@/types/experiment'
import {
  createExperiment as createExperimentRequest,
  fetchExperiment as fetchExperimentRequest,
  fetchExperiments as fetchExperimentsRequest,
} from '@/services/experiments'

interface ExperimentsState {
  items: Experiment[]
  current: Experiment | null
  loading: boolean
  error: string | null
}

export const useExperimentsStore = defineStore('experiments', {
  state: (): ExperimentsState => ({
    items: [],
    current: null,
    loading: false,
    error: null,
  }),
  actions: {
    async fetchAll() {
      this.loading = true
      this.error = null

      try {
        const response = await fetchExperimentsRequest()
        this.items = response.data
        return this.items
      } catch (error) {
        this.error = error instanceof Error ? error.message : 'Failed to load experiments.'
        throw error
      } finally {
        this.loading = false
      }
    },
    async fetchOne(experimentId: number) {
      this.loading = true
      this.error = null

      try {
        const response = await fetchExperimentRequest(experimentId)
        this.current = response.data
        return this.current
      } catch (error) {
        this.error = error instanceof Error ? error.message : 'Failed to load experiment.'
        throw error
      } finally {
        this.loading = false
      }
    },
    async create(payload: CreateExperimentPayload) {
      this.loading = true
      this.error = null

      try {
        const response = await createExperimentRequest(payload)
        this.items.unshift(response.data)
        this.current = response.data
        return response.data
      } catch (error) {
        this.error = error instanceof Error ? error.message : 'Failed to create experiment.'
        throw error
      } finally {
        this.loading = false
      }
    },
  },
})
