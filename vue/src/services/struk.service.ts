import type {
  StrukItem,
  CariNomorPayload,
  CariTanggalPayload,
  CariKeywordPayload,
  ApiErrorResponse,
} from '../types/struk'

async function post<T>(url: string, body: unknown): Promise<T> {
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  })

  const data: unknown = await res.json()

  if (!res.ok) {
    const err = data as ApiErrorResponse
    throw new Error(err.message || 'Request gagal')
  }

  return data as T
}

/* ========= Public API ========= */

export function cariByNomor(
  payload: CariNomorPayload,
): Promise<StrukItem[]> {
  return post('/api/struk/by-nomor', payload)
}

export function cariByTanggal(
  payload: CariTanggalPayload,
): Promise<StrukItem[]> {
  return post('/api/struk/by-tanggal', payload)
}

export function cariByKeyword(
  payload: CariKeywordPayload,
): Promise<StrukItem[]> {
  return post('/api/struk/by-keyword', payload)
}
