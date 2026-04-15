export type ProtocolType = 'http' | 'mqtt'
export type RunStatus = 'queued' | 'running' | 'completed' | 'failed' | 'cancelled'

export interface RunAggregate {
  id: number
  run_id: number
  total_count: number
  success_count: number
  failure_count: number
  timeout_count: number
  connection_failure_count: number
  duplicate_count: number
  retry_count: number
  reconnect_count: number
  avg_latency_ms: number | null
  median_latency_ms: number | null
  min_latency_ms: number | null
  max_latency_ms: number | null
  p95_latency_ms: number | null
  p99_latency_ms: number | null
  throughput_per_sec: number | null
  success_rate: number
  created_at?: string
  updated_at?: string
}

export interface RunEvent {
  id: number
  run_id: number
  type: string
  level: string
  message: string
  context?: Record<string, unknown> | null
  occurred_at: string
}

export interface RunSample {
  id: number
  run_id: number
  sequence_no: number
  sent_at: string | null
  received_at: string | null
  latency_ms: number | null
  success: boolean
  status_code?: number | null
  error_code?: string | null
  metadata?: Record<string, unknown> | null
  created_at?: string
  updated_at?: string
}

export interface Run {
  id: number
  experiment_id: number
  protocol: ProtocolType
  status: RunStatus
  scenario?: string | null
  config: Record<string, unknown>
  environment_snapshot?: Record<string, unknown> | null
  error_message?: string | null
  started_at?: string | null
  finished_at?: string | null
  created_at?: string
  updated_at?: string
  aggregate?: RunAggregate | null
  events?: RunEvent[]
}

export interface RunListFilters {
  protocol?: ProtocolType
  status?: RunStatus
  experiment_id?: number
}

export interface CreateRunPayload {
  experiment_id: number
  protocol?: ProtocolType
  scenario?: string
  config?: Record<string, unknown>
}

export interface RunExportUrls {
  json: string
  csv: string
}
