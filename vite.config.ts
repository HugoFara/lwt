import { defineConfig, type PluginOption, build } from 'vite';
import { PurgeCSS, type UserDefinedOptions } from 'purgecss';
import { resolve } from 'path';
import { fileURLToPath } from 'url';
import { copyFileSync, rmSync, existsSync } from 'fs';

const __dirname = fileURLToPath(new URL('.', import.meta.url));

/**
 * Plugin to clean stale Vite-generated files before each build.
 * We keep emptyOutDir false because dist/ also contains theme CSS
 * built by a separate script; this plugin only removes Vite's own
 * output subdirectories.
 */
function cleanViteOutput(): PluginOption {
  return {
    name: 'clean-vite-output',
    apply: 'build',
    buildStart() {
      const dirs = [
        resolve(__dirname, 'dist/js/vite'),
        resolve(__dirname, 'dist/css/vite'),
        resolve(__dirname, 'dist/.vite'),
      ];
      for (const dir of dirs) {
        if (existsSync(dir)) {
          rmSync(dir, { recursive: true });
        }
      }
    },
  };
}

/**
 * Local PurgeCSS plugin — inlined to avoid the unmaintained
 * vite-plugin-purgecss package, which pins an old vite@6.x with open
 * dev-server advisories. Runs PurgeCSS on each emitted CSS asset at
 * generateBundle time using the caller-supplied content globs.
 */
function purgeCssPlugin(options: Omit<UserDefinedOptions, 'css'>): PluginOption {
  return {
    name: 'lwt-purgecss',
    enforce: 'post',
    apply: 'build',
    async generateBundle(_opts, bundle) {
      for (const [key, asset] of Object.entries(bundle)) {
        if (!key.endsWith('.css') || asset.type !== 'asset') continue;
        const source = typeof asset.source === 'string'
          ? asset.source
          : Buffer.from(asset.source).toString('utf8');
        const [purged] = await new PurgeCSS().purge({
          ...options,
          css: [{ raw: source }],
        });
        asset.source = purged.css;
      }
    },
  };
}

/**
 * Plugin to build the service worker separately.
 * The SW must be served from the root for proper scope.
 */
function buildServiceWorker(): PluginOption {
  return {
    name: 'build-service-worker',
    apply: 'build',
    async closeBundle() {
      await build({
        configFile: false,
        build: {
          outDir: resolve(__dirname, 'sw-dist'),
          emptyOutDir: true,
          lib: {
            entry: resolve(__dirname, 'src/frontend/js/sw.ts'),
            formats: ['iife'],
            name: 'sw',
            fileName: () => 'sw.js',
          },
          minify: 'esbuild',
          target: 'es2022',
        },
        resolve: {
          alias: {
            '@': resolve(__dirname, 'src/frontend/js'),
            '@shared': resolve(__dirname, 'src/frontend/js/shared'),
            '@modules': resolve(__dirname, 'src/frontend/js/modules'),
          },
        },
      });
      // Move built SW to project root (required for service worker scope)
      const swDist = resolve(__dirname, 'sw-dist');
      copyFileSync(resolve(swDist, 'sw.js'), resolve(__dirname, 'sw.js'));
      rmSync(swDist, { recursive: true });
      console.log('Service worker built successfully');
    },
  };
}

export default defineConfig({
  root: resolve(__dirname, 'src/frontend'),
  publicDir: false,

  esbuild: {
    drop: ['console', 'debugger'],
  },

  build: {
    outDir: resolve(__dirname, 'dist'),
    emptyOutDir: false,
    manifest: true,
    target: 'es2022',
    rollupOptions: {
      input: {
        main: resolve(__dirname, 'src/frontend/js/main.ts'),
      },
      output: {
        entryFileNames: 'js/vite/[name].[hash].js',
        chunkFileNames: 'js/vite/chunks/[name].[hash].js',
        assetFileNames: 'css/vite/[name].[hash][extname]',
        manualChunks(id) {
          if (id.includes('@alpinejs/csp')) return 'alpine';
          if (id.includes('chart.js')) return 'chart';
          if (id.includes('@yaireo/tagify')) return 'tagify';
        },
      },
    },
    chunkSizeWarningLimit: 400,
  },

  plugins: [
    // Clean stale hashed bundles from previous builds
    cleanViteOutput(),
    // Build service worker for PWA support
    buildServiceWorker(),
    // PurgeCSS to remove unused CSS (especially from Bulma)
    purgeCssPlugin({
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
    }),
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
      // Use CSP-compliant Alpine.js build (no unsafe-eval needed)
      'alpinejs': '@alpinejs/csp',
    }
  }
});
