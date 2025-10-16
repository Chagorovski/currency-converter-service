import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  server: {
    host: true,
    port: 5173,
    strictPort: true,
    watch: { usePolling: true },
    hmr: { clientPort: 5173 },
    proxy: {
      '/api': {
        target: 'http://nginx',
        changeOrigin: true
      },
    },
  },
})
