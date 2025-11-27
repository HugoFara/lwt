import js from "@eslint/js";
import globals from "globals";
import tseslint from "typescript-eslint";
import markdown from "@eslint/markdown";
import css from "@eslint/css";
import { defineConfig } from "eslint/config";

export default defineConfig([
  // Global ignores - these directories are completely excluded
  {
    ignores: [
      "node_modules/**",
      "vendor/**",
      "assets/**",
      "coverage/**",
      "coverage-report/**",
      "docs/generated/**",
      "src/frontend/js/third_party/**",
      // Ignore minified/bundled CSS files that have parsing issues
      "**/jquery-ui.css",
    ],
  },

  // JavaScript/TypeScript source files
  {
    files: ["src/frontend/js/**/*.{js,mjs,cjs,ts,mts,cts}"],
    plugins: { js },
    extends: ["js/recommended"],
    languageOptions: {
      globals: globals.browser,
    },
  },

  // TypeScript-specific configuration
  {
    files: ["src/frontend/js/**/*.{ts,mts,cts}"],
    extends: [tseslint.configs.recommended],
  },

  // Test files (Cypress, Vitest, etc.)
  {
    files: ["tests/**/*.{js,ts}", "cypress/**/*.{js,ts}"],
    plugins: { js },
    extends: ["js/recommended"],
    languageOptions: {
      globals: {
        ...globals.browser,
        ...globals.node,
      },
    },
  },

  // TypeScript test files
  {
    files: ["tests/**/*.ts", "cypress/**/*.ts"],
    extends: [tseslint.configs.recommended],
    rules: {
      // Allow namespace declarations for Cypress type augmentation
      "@typescript-eslint/no-namespace": "off",
    },
  },

  // Config files (vite.config.ts, eslint.config.ts, etc.)
  {
    files: ["*.config.{js,ts}", "scripts/**/*.js"],
    plugins: { js },
    extends: ["js/recommended"],
    languageOptions: {
      globals: globals.node,
    },
  },

  // TypeScript config files
  {
    files: ["*.config.ts"],
    extends: [tseslint.configs.recommended],
  },

  // Markdown files
  {
    files: ["*.md", "docs/**/*.md"],
    plugins: { markdown },
    language: "markdown/commonmark",
    extends: ["markdown/recommended"],
    rules: {
      // Disable rules that produce false positives for GitHub-flavored markdown
      // (GitHub alerts like [!NOTE], [!IMPORTANT], checkbox syntax, etc.)
      "markdown/no-missing-label-refs": "off",
      // Allow emphasis markers with spaces in specific cases
      "markdown/no-space-in-emphasis": "off",
    },
  },

  // CSS files
  {
    files: ["src/frontend/css/**/*.css", "src/frontend/themes/**/*.css"],
    plugins: { css },
    language: "css/css",
    extends: ["css/recommended"],
    rules: {
      // Allow !important - sometimes necessary for theme overrides
      "css/no-important": "off",
      // Allow newer CSS features that may not be in baseline yet
      "css/use-baseline": "off",
      // Some vendor-specific or newer properties may not be recognized
      "css/no-invalid-properties": "off",
      // Allow custom font stacks without generic fallback requirement
      "css/font-family-fallbacks": "off",
      // Allow vendor-prefixed at-rules like @-moz-document
      "css/no-invalid-at-rules": "off",
    },
  },
]);
