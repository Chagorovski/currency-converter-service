import { ref, computed } from 'vue'
import { ISO_4217 } from '../isoCurrencies'

const codes = ref([...ISO_4217])
const dn = new Intl.DisplayNames([navigator.language || 'en'], { type: 'currency' })
const options = computed(() => codes.value.map(c => ({ code: c, label: `${c} — ${dn.of(c) ?? c}` })))

export function useCurrencies() {
    return { codes, options }
}
