<script setup>
import { onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCurrencies } from './composables/useCurrencies'
import { useConversion } from './composables/useConversion'
import { useSession } from './composables/useSession'
import ConverterForm from './components/ConverterForm.vue'
import LoginCard from './components/LoginCard.vue' // ← add this

const { t } = useI18n()
const { codes } = useCurrencies()

// Session
const { user, loading: loadingSession, error: sessionError, refresh, login, logout, getCsrf } = useSession()
onMounted(refresh)

// Conversion
const { output, error: convertError, loading: converting, convert } = useConversion(getCsrf)

// Called when LoginCard emits "login" with the name
function handleLogin(name) {
  if (!name) return
  login(name)
}

function handleLogout() {
  logout()
}

function onSubmit(payload) {
  convert(payload)
}
</script>

<template>
  <!-- Hero -->
  <header class="hero">
    <h1>Currency Converter</h1>
    <p v-if="user">Welcome, {{ user }}!</p>
    <p v-else>Check live foreign exchange rates</p>
  </header>

  <!-- Card -->
  <section class="container">
    <div class="card">
      <LoginCard
          v-if="!user"
          :loading="loadingSession"
          :error="sessionError"
          @login="handleLogin"
      />
      <template v-else>
        <ConverterForm :codes="codes" :loading="converting" @submit="onSubmit" />

        <div style="margin-top:10px;text-align:right;">
          <button class="link" :disabled="loadingSession" @click="handleLogout">Logout</button>
        </div>

        <div v-if="output" class="section">
          <h3>Result</h3>
          <div class="small">
            {{ output.amount }} {{ output.from }} → {{ output.to }} at rate {{ output.rate }}
          </div>
          <div style="margin-top:8px;font-size:22px;font-weight:700">
            {{ output.formatted }}
          </div>
          <div class="small" style="margin-top:6px">
            Source: {{ output.source }} · {{ output.timestamp }}
          </div>
        </div>

        <div v-if="convertError" class="section" style="border-color:#f5c2c7;background:#fff7f7">
          <h3 style="color:#a0181e;margin-bottom:4px">Error</h3>
          <div style="color:#a0181e;">
            {{ convertError.message || convertError }}
          </div>
        </div>
      </template>
    </div>
  </section>
</template>
