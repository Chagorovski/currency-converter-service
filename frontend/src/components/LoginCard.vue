<script setup>
import { ref } from 'vue'
const props = defineProps({ loading: Boolean, error: [String, Object] })
const emit = defineEmits(['login'])
const name = ref('')

function submit() {
  if (!name.value.trim()) return
  emit('login', name.value.trim())
}
</script>

<template>
  <div class="card section">
    <h3 style="margin:0 0 10px;">Sign in</h3>
    <p class="small" style="margin:0 0 8px;">Demo session + CSRF (no password required).</p>
    <div class="form" style="grid-template-columns: 1fr 140px;">
      <div>
        <label class="label">Your name</label>
        <input class="input" :disabled="loading" v-model="name" placeholder="e.g. admin" @keyup.enter="submit"/>
      </div>
      <div style="align-self:end;">
        <button class="login-btn" :disabled="loading" @click="submit">Login</button>
      </div>
    </div>
    <div v-if="error" class="small" style="margin-top:8px;color:#a0181e;">
      {{ error.message || error }}
    </div>
  </div>
</template>
