export interface ApiResourceResponse<T> {
  data: T
}

export interface ApiCollectionResponse<T> {
  data: T[]
  meta?: Record<string, unknown>
}

export interface ValidationErrorResponse {
  message: string
  errors?: Record<string, string[]>
}
