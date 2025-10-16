// frontend/vite.config.js
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default ({ command, mode }) => {
  const isBuild = command === 'build'

  return defineConfig({
    base: isBuild ? '/app/' : '/',

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
          changeOrigin: true,
          secure: false,
        },
      },
    },

    build: {
      outDir: 'dist',
      emptyOutDir: true,
    },
  })
}
