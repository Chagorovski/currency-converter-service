// frontend/src/composables/useSession.js
import { ref } from 'vue'

const user = ref(null)
const loading = ref(false)
const error = ref(null)

/**
 * Safely parses JSON or throws a human-readable error
 */
async function parseJsonSafe(res) {
    const ct = res.headers.get('content-type') || ''
    if (!ct.includes('application/json')) {
        const text = await res.text().catch(() => '')
        throw new Error(text || `${res.status} ${res.statusText}`)
    }
    try {
        return await res.json()
    } catch (e) {
        const text = await res.text().catch(() => '')
        throw new Error(text || 'Invalid JSON response')
    }
}

/**
 * Generic API helper that handles credentials and JSON parsing safely
 */
async function API(path, opts = {}) {
    const res = await fetch(`${import.meta.env.VITE_API_BASE || ''}${path}`, {
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json',
            ...(opts.headers || {}),
        },
        ...opts,
    })

    if (!res.ok) {
        const errData = await parseJsonSafe(res).catch(e => ({ message: e.message }))
        throw new Error(errData.message || `${res.status} ${res.statusText}`)
    }

    return parseJsonSafe(res)
}

export function useSession() {
    function setUser(u) {
        user.value = u
        if (u) localStorage.setItem('ccs.user', u)
        else localStorage.removeItem('ccs.user')
    }

    async function refresh() {
        loading.value = true
        error.value = null
        try {
            const res = await fetch('/api/session', { credentials: 'include' })
            const data = await parseJsonSafe(res)
            if (data.authenticated && data.user) {
                setUser(data.user)
            } else {
                const cached = localStorage.getItem('ccs.user')
                if (cached) setUser(cached)
            }
        } catch (e) {
            error.value = e
        } finally {
            loading.value = false
        }
    }

    async function getCsrf() {
        const { token } = await API('/api/csrf')
        return token
    }

    async function login(username) {
        loading.value = true
        error.value = null
        try {
            const token = await getCsrf()
            const res = await API('/api/session/login', {
                method: 'POST',
                headers: { 'X-CSRF-Token': token },
                body: JSON.stringify({ username }),
            })
            if (res.error) throw res
            user.value = res.user || username
        } catch (e) {
            error.value = e
        } finally {
            loading.value = false
        }
    }

    async function logout() {
        loading.value = true
        error.value = null
        try {
            const token = await getCsrf()
            await API('/api/session/logout', {
                method: 'POST',
                headers: { 'X-CSRF-Token': token },
            })
            user.value = null
        } catch (e) {
            error.value = e
        } finally {
            loading.value = false
        }
    }

    return { user, loading, error, refresh, login, logout, getCsrf }
}
