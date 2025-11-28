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
        // Use global jQuery instead of bundling it
        globals: {
          jquery: 'jQuery',
          'jquery-ui-dist/jquery-ui': 'jQuery'
        }
      },
      // Externalize jQuery - it's loaded separately for inline script compatibility
      external: ['jquery', 'jquery-ui-dist/jquery-ui']
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
    }
  }
});
