import { defineConfig } from 'vite';
import legacy from '@vitejs/plugin-legacy';
import { resolve } from 'path';
import { fileURLToPath } from 'url';

const __dirname = fileURLToPath(new URL('.', import.meta.url));

export default defineConfig({
  root: resolve(__dirname, 'src/frontend'),
  publicDir: false,

  build: {
    outDir: resolve(__dirname, 'assets'),
    emptyOutDir: false,
    manifest: true,
    rollupOptions: {
      input: {
        main: resolve(__dirname, 'src/frontend/js/main.ts'),
      },
      output: {
        entryFileNames: 'js/vite/[name].[hash].js',
        chunkFileNames: 'js/vite/chunks/[name].[hash].js',
        assetFileNames: 'css/vite/[name].[hash][extname]',
      },
    }
  },

  plugins: [
    legacy({
      targets: ['defaults', 'not IE 11']
    })
  ],

  server: {
    port: 5173,
    // Proxy all non-asset requests to PHP server
    proxy: {
      '^/(?!@|src|node_modules).*': {
        target: 'http://localhost:8080',
        changeOrigin: true
      }
    }
  },

  resolve: {
    alias: {
      '@': resolve(__dirname, 'src/frontend/js'),
      '@css': resolve(__dirname, 'src/frontend/css'),
      // Map jQuery imports to our shims that use global jQuery
      'jquery': resolve(__dirname, 'src/frontend/js/shims/jquery-shim.ts'),
      'jquery-ui-dist/jquery-ui': resolve(__dirname, 'src/frontend/js/shims/jquery-ui-shim.ts'),
    }
  }
});
