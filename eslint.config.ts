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
  },

  // CSS files
  {
    files: ["src/frontend/css/**/*.css", "src/frontend/themes/**/*.css"],
    plugins: { css },
    language: "css/css",
    extends: ["css/recommended"],
  },
]);
