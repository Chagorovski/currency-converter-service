<script setup>
defineProps({ result: Object })
</script>

<template>
  <div v-if="result" style="margin-top:20px;padding:12px;border:1px solid #ddd;border-radius:8px">
    <div><strong>From:</strong> {{ result.from }} — <strong>To:</strong> {{ result.to }}</div>
    <div style="margin-top:6px"><strong>Rate:</strong> {{ result.rate }}</div>
    <div style="margin-top:6px"><strong>Converted:</strong> {{ result.converted }} ({{ result.formatted }})</div>
    <small style="display:block;margin-top:6px;color:#666">at {{ result.timestamp }}</small>
  </div>
</template>
