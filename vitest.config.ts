import { defineConfig } from 'vitest/config';
import { resolve } from 'path';
import { fileURLToPath } from 'url';

const __dirname = fileURLToPath(new URL('.', import.meta.url));

export default defineConfig({
  test: {
    globals: true,
    include: ['tests/**/*.test.ts', 'tests/**/*.spec.ts'],
    exclude: ['node_modules', 'vendor'],
    testTimeout: 10000,
    hookTimeout: 10000,
    // Use different environments for different test types
    environmentMatchGlobs: [
      // Frontend tests use jsdom for DOM manipulation
      ['tests/frontend/**', 'jsdom'],
      // API tests use node environment
      ['tests/api.test.ts', 'node'],
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
        'src/frontend/js/third_party/**',
        'src/frontend/js/main.ts',
      ],
    },
  },

  resolve: {
    alias: {
      '@': resolve(__dirname, 'src/frontend/js'),
      '@css': resolve(__dirname, 'src/frontend/css'),
    }
  }
});
