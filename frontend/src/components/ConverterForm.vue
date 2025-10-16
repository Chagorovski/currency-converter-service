<script setup>
import { ref, watchEffect } from 'vue'

const props = defineProps({
  codes: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false }
})
const emit = defineEmits(['submit'])

const amount = ref(30)
const from = ref('USD')
const to = ref('EUR')

watchEffect(() => {
  if (props.codes.length) {
    if (!props.codes.includes(from.value)) from.value = props.codes[0]
    if (!props.codes.includes(to.value)) to.value = props.codes[1] || props.codes[0]
  }
})

function onSwap() {
  const tmp = from.value
  from.value = to.value
  to.value = tmp
}

function onSubmit(e) {
  e.preventDefault()
  emit('submit', { amount: Number(amount.value), from: from.value, to: to.value })
}
</script>

<template>
  <form class="form" @submit="onSubmit">
    <!-- Amount -->
    <div>
      <label class="label">Amount</label>
      <input class="input" type="number" step="0.01" v-model.number="amount" />
    </div>

    <!-- From -->
    <div>
      <label class="label">From</label>
      <select class="select" v-model="from">
        <option v-for="c in codes" :key="c" :value="c">{{ c }}</option>
      </select>
    </div>

    <!-- Swap button -->
    <button type="button" class="swap" @click="onSwap" title="Swap">
      <svg viewBox="0 0 24 24"><path d="M7 7h11l-3-3 1.4-1.4L22.8 9l-6.4 6.4L15 14l3-3H7V7zm10 10H6l3 3-1.4 1.4L1.2 15l6.4-6.4L9 10l-3 3h11v4z"/></svg>
    </button>

    <!-- To -->
    <div>
      <label class="label">To</label>
      <select class="select" v-model="to">
        <option v-for="c in codes" :key="c" :value="c">{{ c }}</option>
      </select>
    </div>

    <!-- Submit -->
    <div style="grid-column: 1 / -1;display: flex;align-items: center;justify-content: center;">
      <button class="primary" :disabled="loading" type="submit">Convert</button>
    </div>
  </form>
</template>
