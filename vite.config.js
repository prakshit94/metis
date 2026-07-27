import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import legacy from '@vitejs/plugin-legacy';
import { resolve } from 'path';
import { fileURLToPath, URL } from 'node:url';

const __dirname = fileURLToPath(new URL('.', import.meta.url));

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/scss/main.scss', 'resources/js/main.js'],
      refresh: true,
    }),
    legacy({
      targets: ['> 1%', 'last 2 versions', 'not dead'],
    }),
  ],

  css: {
    devSourcemap: true,
    preprocessorOptions: {
      scss: {
        api: 'modern-compiler',
        silenceDeprecations: ['legacy-js-api', 'import', 'global-builtin', 'color-functions', 'if-function'],
      },
    },
  },

  resolve: {
    alias: {
      '@':                resolve(__dirname, 'resources/js'),
      '~bootstrap':       resolve(__dirname, 'node_modules/bootstrap'),
      '~bootstrap-icons': resolve(__dirname, 'node_modules/bootstrap-icons'),
    },
  },

  optimizeDeps: {
    include: ['bootstrap', 'alpinejs', 'apexcharts', 'sweetalert2', 'dayjs'],
    exclude: ['lucide'],
  },

  esbuild: {
    drop: process.env.NODE_ENV === 'production' ? ['console', 'debugger'] : [],
  },

  build: {
    emptyOutDir: false,
  },
});
