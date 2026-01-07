import { defineConfig, type PluginOption } from 'vite';
import legacy from '@vitejs/plugin-legacy';
import purgecss from 'vite-plugin-purgecss';
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
        manualChunks: {
          // Vendor chunks - loaded on demand
          'alpine': ['alpinejs'],
          'chart': ['chart.js'],
          'tagify': ['@yaireo/tagify'],
          // Note: lucide icons are tree-shaken (only ~90 icons imported)
          // so no separate chunk needed
        },
      },
    },
    // Enable minification for better compression
    minify: 'terser',
    terserOptions: {
      compress: {
        drop_console: true,
        drop_debugger: true,
      },
    },
    chunkSizeWarningLimit: 400,
  },

  plugins: [
    legacy({
      targets: ['defaults', 'not IE 11']
    }),
    // PurgeCSS to remove unused CSS (especially from Bulma)
    // Cast needed due to vite-plugin-purgecss type issues with 'enforce' property
    purgecss({
      content: [
        // PHP views and templates
        resolve(__dirname, 'src/**/*.php'),
        resolve(__dirname, 'index.php'),
        // TypeScript files (for dynamic class names)
        resolve(__dirname, 'src/frontend/js/**/*.ts'),
        // CSS files (for @apply directives)
        resolve(__dirname, 'src/frontend/css/**/*.css'),
      ],
      // Safelist patterns that are dynamically generated
      safelist: {
        standard: [
          // Word status classes (s1, s2, s3, s4, s5, s98, s99)
          /^s\d+$/,
          /^status\d+$/,
          /^status-\d+$/,
          // Bulma modals and dropdowns (may be opened dynamically)
          'is-active',
          'is-hidden',
          'is-loading',
          'is-disabled',
          // Alpine.js visibility
          /^\[x-cloak\]$/,
          // Chart.js canvas
          'chartjs-render-monitor',
          // Tagify
          /^tagify/,
          // Dynamic color classes
          /^has-background-/,
          /^has-text-/,
        ],
        // Keep all Bulma responsive helpers
        greedy: [
          /^is-hidden-/,
          /^is-invisible-/,
          /^is-block-/,
          /^is-flex-/,
          /^is-inline-/,
          // Column sizes
          /^is-\d+-/,
          /^is-offset-/,
        ],
      },
      // Skip purging these files
      rejected: true,
    }) as PluginOption,
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
      '@shared': resolve(__dirname, 'src/frontend/js/shared'),
      '@modules': resolve(__dirname, 'src/frontend/js/modules'),
      '@css': resolve(__dirname, 'src/frontend/css'),
    }
  }
});
