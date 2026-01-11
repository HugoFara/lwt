import { defineConfig } from 'vitest/config';
import { resolve } from 'path';
import { fileURLToPath } from 'url';

const __dirname = fileURLToPath(new URL('.', import.meta.url));

export default defineConfig({
  test: {
    globals: true,
    include: ['tests/**/*.test.ts', 'tests/**/*.spec.ts'],
    exclude: ['node_modules', 'vendor', 'tests/api.test.ts'],
    testTimeout: 10000,
    hookTimeout: 10000,
    setupFiles: ['./tests/setup.ts'],
    // Use different environments for different test types
    environmentMatchGlobs: [
      // Frontend tests use jsdom for DOM manipulation
      ['tests/frontend/**', 'jsdom'],
    ],
    // Default to jsdom for frontend tests
    environment: 'jsdom',
    coverage: {
      provider: 'v8',
      reporter: ['text', 'html', 'lcov'],
      reportsDirectory: './coverage',
      include: ['src/frontend/js/**/*.ts'],
      exclude: [
        'src/frontend/js/types/**',
        'src/frontend/js/main.ts',
      ],
    },
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
