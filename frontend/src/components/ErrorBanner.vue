<script setup>
defineProps({ error: Object })
</script>

<template>
  <div v-if="error" style="margin-top:16px;padding:12px;border:1px solid #f5c2c7;background:#f8d7da;color:#842029">
    <strong>Oops:</strong>
    <span>
      {{ error.message }}
      <template v-if="error.details"> — {{ typeof error.details === 'string' ? error.details : JSON.stringify(error.details) }}</template>
    </span>
  </div>
</template>
