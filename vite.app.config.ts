import { defineConfig, type PluginOption } from 'vite';
import { resolve } from 'path';
import { fileURLToPath } from 'url';
// @ts-expect-error - plain .mjs build helper, no types
import { prerenderPhpView } from './build/php-view-prerender.mjs';

const __dirname = fileURLToPath(new URL('.', import.meta.url));

/**
 * Bundled-client ("Model B") build.
 *
 * Emits standalone HTML pages — connect, library, reader — that boot the
 * existing LWT frontend bundle (`src/frontend/js/main.ts`) against a remote
 * `/api/v1` with no PHP server in the loop, for packaging into the Lukaisu
 * Capacitor/F-Droid app (../lukaisu). Each page's body is prerendered from the
 * real PHP view at build time (see build/php-view-prerender.mjs), so the Alpine
 * scaffolds never drift from the server templates.
 *
 * Output: dist-app/ (consumed by ../lukaisu as its Capacitor webDir).
 *
 * Run: `npm run build:app` (from lwt/).
 */

/**
 * Replace `<!--LWT_VIEW:relative/path/to/view.php-->` markers in the page
 * wrappers with the prerendered static HTML of that PHP view.
 */
function injectPrerenderedViews(): PluginOption {
  return {
    name: 'lwt-inject-prerendered-views',
    transformIndexHtml: {
      order: 'pre' as const,
      handler(html: string) {
        return html.replace(
          /<!--\s*LWT_VIEW:([\w.\-/]+)\s*-->/g,
          (_m, rel: string) => {
            const abs = resolve(__dirname, rel);
            return prerenderPhpView(abs, (msg: string) =>
              // eslint-disable-next-line no-console
              console.warn(`[prerender] ${rel}: ${msg}`)
            );
          }
        );
      }
    }
  };
}

export default defineConfig({
  root: resolve(__dirname, 'src/frontend/app'),
  // Relative asset URLs so pages work when served from the bundle root inside
  // the WebView (capacitor://localhost / https://localhost).
  base: './',
  publicDir: false,

  resolve: {
    alias: {
      '@': resolve(__dirname, 'src/frontend/js'),
      '@shared': resolve(__dirname, 'src/frontend/js/shared'),
      '@modules': resolve(__dirname, 'src/frontend/js/modules'),
      '@css': resolve(__dirname, 'src/frontend/css'),
      // CSP-compliant Alpine build (matches vite.config.ts; the CSP eval
      // restriction is enforced the same way in the bundled client).
      'alpinejs': '@alpinejs/csp'
    }
  },

  plugins: [injectPrerenderedViews()],

  build: {
    outDir: resolve(__dirname, 'dist-app'),
    emptyOutDir: true,
    target: 'es2020',
    rollupOptions: {
      input: {
        index: resolve(__dirname, 'src/frontend/app/index.html'),
        library: resolve(__dirname, 'src/frontend/app/library.html'),
        read: resolve(__dirname, 'src/frontend/app/read.html')
      }
    }
  }
});
