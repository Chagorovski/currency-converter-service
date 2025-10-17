import {defineConfig, loadEnv} from 'vite'
import vue from '@vitejs/plugin-vue'
import fs from 'node:fs'

export default ({ command, mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  const isBuild = command === 'build'

  const inDocker = fs.existsSync('/.dockerenv')

  const apiTarget =
      env.VITE_API_ORIGIN ||
      (inDocker ? 'http://nginx' : 'http://localhost:8080')

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
          target: apiTarget,
          changeOrigin: true,
          secure: false,
          cookieDomainRewrite: 'localhost',
        },
      },
    },
    build: {
      outDir: 'dist',
      emptyOutDir: true,
    },
  })
}
