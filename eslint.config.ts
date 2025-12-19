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
    rules: {
      // Allow unused variables that start with underscore (intentionally unused params)
      "@typescript-eslint/no-unused-vars": [
        "error",
        {
          argsIgnorePattern: "^_",
          varsIgnorePattern: "^_",
          caughtErrorsIgnorePattern: "^_",
        },
      ],
    },
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
      // Allow unused variables that start with underscore
      "@typescript-eslint/no-unused-vars": [
        "error",
        {
          argsIgnorePattern: "^_",
          varsIgnorePattern: "^_",
          caughtErrorsIgnorePattern: "^_",
        },
      ],
      // Allow explicit any in tests (common for mocking)
      "@typescript-eslint/no-explicit-any": "off",
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
    rules: {
      // Allow unused variables that start with underscore
      "@typescript-eslint/no-unused-vars": [
        "error",
        {
          argsIgnorePattern: "^_",
          varsIgnorePattern: "^_",
          caughtErrorsIgnorePattern: "^_",
        },
      ],
    },
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

  // CSS files (base styles and themes)
  {
    files: ["src/frontend/css/**/*.css"],
    plugins: { css },
    language: "css/css",
    extends: ["css/recommended"],
    rules: {
      // Allow !important - this codebase legitimately uses !important for:
      // - Alpine.js [x-cloak] pattern (required by Alpine)
      // - Overriding Bulma framework defaults
      // - State/utility classes (.is-loading, .lwt_selected_text, etc.)
      // Refactoring to avoid !important would require major CSS architecture changes
      "css/no-important": "off",
      // The flagged properties (user-select, resize, accent-color) are well-supported
      // in all modern browsers since 2021-2022. Disabling to avoid false positives.
      "css/use-baseline": "off",
    },
  },
]);
