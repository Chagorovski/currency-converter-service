import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default ({ command }) => {
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
