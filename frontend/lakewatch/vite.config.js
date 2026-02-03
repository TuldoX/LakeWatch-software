import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
// import vueDevTools from 'vite-plugin-vue-devtools'

export default defineConfig({
  plugins: [
    vue(),
    // vueDevTools(),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    proxy: {
      // Proxy /bff to app.lakewatch.com/bff
      '/bff': {
        target: 'http://app.lakewatch.com',
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/bff/, '/bff'),
        secure: true,
      },
    },
  },
})
