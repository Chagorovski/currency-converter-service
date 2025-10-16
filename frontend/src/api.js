export async function getCsrf() {
  const r = await fetch('/api/csrf')
  if (!r.ok) throw new Error('CSRF fetch failed')
  const j = await r.json()
  return j.token
}

export async function convert({ amount, from, to }) {
  const url = `/api/convert?amount=${encodeURIComponent(amount)}&from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}`
  const r = await fetch(url, { headers: { 'Accept-Language': navigator.language || 'en-US' } })
  return r.json()
}
