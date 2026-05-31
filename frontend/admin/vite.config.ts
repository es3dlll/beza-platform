import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig(({ mode }) => ({
  plugins: [
    react(),
  ],
  build: {
    target: 'es2020',
    minify: 'esbuild',
    cssMinify: true,
    sourcemap: mode === 'production' ? false : true,
    rollupOptions: {
      output: {
        manualChunks: {
          vendor: ['react', 'react-dom', 'react-router-dom'],
          state: ['zustand'],
          http: ['axios'],
        },
      },
    },
    chunkSizeWarningLimit: 300,
  },
  esbuild: {
    pure: mode === 'production' ? ['console.log', 'console.debug'] : [],
    drop: mode === 'production' ? ['debugger'] : [],
  },
  test: {
    environment: 'jsdom',
    setupFiles: './src/test-setup.ts',
    globals: true,
  },
}))
