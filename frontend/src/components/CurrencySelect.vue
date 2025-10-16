<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: { type: String, required: true },
  codes: { type: Array, default: () => [] },
  label: { type: String, required: true }
})
const emit = defineEmits(['update:modelValue'])

const displayNames = new Intl.DisplayNames([navigator.language || 'en'], { type: 'currency' })
const options = computed(() => props.codes.map(c => ({ code: c, label: `${c} — ${displayNames.of(c) ?? c}` })))
</script>

<template>
  <label style="display:grid;gap:6px">
    <span>{{ label }}</span>
    <select
        :value="modelValue"
        @change="e => emit('update:modelValue', e.target.value)"
        style="padding:6px"
    >
      <option v-for="opt in options" :key="opt.code" :value="opt.code">{{ opt.label }}</option>
    </select>
  </label>
</template>
