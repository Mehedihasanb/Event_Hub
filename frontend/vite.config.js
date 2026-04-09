import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

// In Docker, VITE_API_TARGET=http://nginx:80 (see docker-compose.yml). On your machine, localhost is fine.
const apiTarget = process.env.VITE_API_TARGET || 'http://localhost:80';

export default defineConfig({
  plugins: [vue()],
  server: {
    host: true,
    port: 5173,
    proxy: {
      '/api': {
        target: apiTarget,
        changeOrigin: true,
      },
    },
  },
});
