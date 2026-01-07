const API_BASE = import.meta.env.VITE_API_BASE ?? '/api'

export interface ApiError {
  status: number
  message: string
}

export async function apiFetch<T>(
  path: string,
  options: RequestInit = {},
): Promise<T> {
  const url = `${API_BASE}${path}`

  // =====================
  // REQUEST LOG
  // =====================
  console.log('[API FETCH]', {
    url,
    method: options.method ?? 'GET',
    body: options.body,
  })

  const response = await fetch(url, {
    headers: {
      'Content-Type': 'application/json',
      ...(options.headers ?? {}),
    },
    ...options,
  })

  // =====================
  // RESPONSE LOG
  // =====================
  console.log('[API RESPONSE]', response.status, response.statusText)

  // =====================
  // ERROR HANDLING
  // =====================
  if (!response.ok) {
    let message = 'Request failed'

    try {
      const err = await response.json()
      message = err?.message ?? message
    } catch {
      message = response.statusText
    }

    console.error('[API ERROR]', {
      status: response.status,
      message,
    })

    throw {
      status: response.status,
      message,
    } as ApiError
  }
  // =====================
// SUCCESS RAW RESPONSE
// =====================
const text = await response.text()

console.log('[API RAW RESPONSE]')
console.log(text)

try {
  const data = JSON.parse(text)
  console.log('[API DATA]', data)
  return data as T
} catch (e) {
  console.error('[API JSON PARSE ERROR]', e)
  throw {
    status: response.status,
    message: 'Response bukan JSON valid',
  } as ApiError
}

  // =====================
  // SUCCESS JSON
  // =====================
  const data = (await response.json()) as T

  console.log('[API DATA]', data)

  return data
}
