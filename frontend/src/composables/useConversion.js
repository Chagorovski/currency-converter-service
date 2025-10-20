import { ref, watch } from 'vue'
import { convertGet, convertPost } from '../services/apiClient'
import { ApiError } from '../errors/ApiError'
import { useSession } from './useSession'

export function useConversion(getCsrf = null) {
    const output = ref(null)
    const error = ref(null)
    const loading = ref(false)
    const { forceLogout } = useSession()

    const { user, logout } = useSession()

    watch(user, (newUser) => {
        if (!newUser) {
            output.value = null
            error.value = null
        }
    })

    function normalizePayload({ amount, from, to }) {
        const amt = typeof amount === 'string' ? parseFloat(amount) : Number(amount)
        const src = String(from || '').trim().toUpperCase()
        const dst = String(to || '').trim().toUpperCase()
        if (!amt || amt <= 0 || !src || !dst) {
            throw new ApiError('Invalid input, you should insert correct amount', {
                details: 'amount must be > 0 and from/to are required',
            })
        }
        return { amount: amt, from: src, to: dst }
    }

    async function convert({ amount, from, to }) {
        loading.value = true
        output.value = null
        error.value = null

        try {
            const payload = normalizePayload({ amount, from, to })

            let res
            if (typeof getCsrf === 'function') {
                const token = await getCsrf()
                res = await convertPost(payload, token)
            } else {
                res = await convertGet(payload)
            }

            if (res?.error) throw new ApiError(res.error, { details: res.details })
            output.value = res
        } catch (e) {
            const status = (e instanceof ApiError && (e.status ?? e?.details?.status)) || null
            if (status === 401 || status === 403) {
                forceLogout()
                error.value = 'Your session expired. Please sign in again.'
                return
            }
            error.value = e.message || 'Conversion failed'
        } finally {
            loading.value = false
        }
    }

    return { output, error, loading, convert }
}
