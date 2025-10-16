// frontend/src/services/apiClient.js
import { ApiError } from '../errors/ApiError'
import { NetworkError } from '../errors/NetworkError'
import { ValidationError } from '../errors/ValidationError'

const API_BASE = import.meta?.env?.VITE_API_BASE ?? ''

function withTimeout(ms, controller = new AbortController()) {
    const id = setTimeout(() => controller.abort('timeout'), ms)
    return { controller, dispose: () => clearTimeout(id) }
}

function safeJson(text) {
    try { return JSON.parse(text) } catch { return null }
}

async function request(path, { method = 'GET', headers = {}, body, timeout = 6000 } = {}) {
    const url = `${API_BASE}${path}`
    const { controller, dispose } = withTimeout(timeout)
    try {
        const res = await fetch(url, {
            method,
            credentials: 'include', // carry session cookies
            headers: {
                'Accept': 'application/json',
                ...(body ? { 'Content-Type': 'application/json' } : {}),
                ...headers,
            },
            body,
            signal: controller.signal,
        })

        const raw = await res.text()
        const json = raw ? safeJson(raw) : null

        if (!res.ok) {
            if (res.status === 400) {
                throw new ValidationError(json?.error || 'Validation failed', json?.details)
            }
            const message = json?.message || json?.error || `HTTP ${res.status}`
            throw new ApiError(message, { status: res.status, details: json?.details })
        }

        return json
    } catch (e) {
        if (e.name === 'AbortError') throw new NetworkError('Request timed out')
        if (e instanceof ApiError || e instanceof ValidationError) throw e
        throw new NetworkError('Network failure', e)
    } finally {
        dispose()
    }
}

export async function fetchCsrfToken() {
    const j = await request('/api/csrf') // includes cookies
    if (!j?.token) throw new ApiError('Missing CSRF token from server')
    return j.token
}

export async function convertGet({ amount, from, to }) {
    const q = `/api/convert?amount=${encodeURIComponent(amount)}&from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}`
    return request(q)
}

/**
 * POST /api/convert with CSRF protection.
 * If csrfToken is omitted, it will be fetched automatically.
 * @param {{amount:number, from:string, to:string}} payload
 * @param {string=} csrfToken
 */
export async function convertPost(payload, csrfToken) {
    const token = csrfToken || await fetchCsrfToken()
    return request('/api/convert', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': token },
        body: JSON.stringify(payload),
    })
}
