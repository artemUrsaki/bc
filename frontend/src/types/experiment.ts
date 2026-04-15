export interface Experiment {
  id: number
  name: string
  description: string | null
  hypothesis: string | null
  default_protocol: 'http' | 'mqtt'
  default_config: Record<string, unknown>
  created_at?: string
  updated_at?: string
}

export interface CreateExperimentPayload {
  name: string
  description?: string | null
  hypothesis?: string | null
  default_protocol: 'http' | 'mqtt'
  default_config?: Record<string, unknown>
}
