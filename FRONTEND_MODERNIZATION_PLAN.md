# Frontend Modernization Plan

**Project:** Learning with Texts (LWT)
**Document Version:** 8.0
**Last Updated:** December 23, 2025
**Status:** Phase 4 Complete - Full Alpine.js Component Architecture

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [Current State Analysis](#current-state-analysis)
3. [Modernization Goals](#modernization-goals)
4. [Technology Stack Decisions](#technology-stack-decisions)
5. [Migration Phases](#migration-phases)
6. [Risk Management](#risk-management)
7. [Success Metrics](#success-metrics)
8. [Timeline & Milestones](#timeline--milestones)
9. [Resources & References](#resources--references)

---

## Executive Summary

This document outlines the comprehensive plan to modernize the Learning with Texts (LWT) frontend codebase. The original implementation relied on jQuery and outdated patterns from 2010-2015. This modernization has dramatically improved performance, maintainability, and developer experience.

**Key Objectives:**

- ✅ Modernize build system (Vite) - **COMPLETE**
- ✅ Add TypeScript for type safety - **COMPLETE**
- ✅ Convert to ES6+ modules - **COMPLETE** (104 TypeScript files, ~25,000 lines)
- ✅ Extract backend-embedded JavaScript - **COMPLETE** (zero inline handlers)
- ✅ Centralized API client with type-safe wrappers - **COMPLETE**
- ✅ Comprehensive test suite - **COMPLETE** (96 test files, 3000+ tests)
- ✅ Remove jQuery entirely - **COMPLETE**
- ✅ Adopt Alpine.js for reactive components - **COMPLETE**
- ✅ Adopt Bulma CSS framework - **COMPLETE**
- ✅ Component architecture refinement - **COMPLETE** (Phase 4)
- 🎯 Performance optimization - **PLANNED** (Phase 5)

**Risk Level:** Low (phased approach proven successful)
**Expected ROI:** High (improved DX, performance, maintainability)

---

## Current State Analysis

### Architecture Overview

**JavaScript:**

- **Total Lines:** ~25,000 lines across 104 TypeScript files
- **Test Coverage:** 96 test files with 3000+ tests
- **Module System:** ES6 modules (TypeScript)
- **UI Framework:** Alpine.js 3.x (reactive components)
- **CSS Framework:** Bulma 1.x (modern CSS)
- **State Management:** Centralized `LWT_DATA` object with typed interface + Alpine stores
- **API Client:** Centralized fetch-based client with type-safe wrappers
- **Build Process:** Vite with TypeScript

**Key Files:**

```text
src/frontend/js/
├── main.ts                       - Vite entry point
├── globals.ts                    - Global exports for inline PHP scripts
├── api/                          - Centralized API client
│   ├── terms.ts                  - Terms/vocabulary API
│   ├── texts.ts                  - Texts API
│   ├── review.ts                 - Review/testing API
│   └── settings.ts               - Settings API
├── core/
│   ├── api_client.ts             - Fetch-based API client
│   ├── lwt_state.ts              - Centralized state management
│   ├── app_data.ts               - Application data utilities
│   ├── language_settings.ts      - Language filter utilities
│   ├── ajax_utilities.ts         - AJAX helper functions
│   ├── ui_utilities.ts           - UI utility functions
│   ├── simple_interactions.ts    - Navigation, confirmation
│   ├── hover_intent.ts           - Native hover intent implementation
│   ├── cookies.ts                - Cookie management
│   ├── tts_storage.ts            - TTS settings storage
│   └── html_utils.ts             - HTML utility functions
├── feeds/
│   ├── jq_feedwizard.ts          - Feed wizard functionality
│   ├── feed_browse.ts            - Feed browse page
│   ├── feed_loader.ts            - Feed loading AJAX
│   ├── feed_multi_load.ts        - Multi-feed load page
│   ├── feed_index.ts             - Feed management page
│   ├── feed_form.ts              - Feed form handling
│   ├── feed_text_edit.ts         - Feed text editing
│   ├── feed_wizard_common.ts     - Shared wizard utilities
│   ├── feed_wizard_step2.ts      - Wizard step 2
│   ├── feed_wizard_step3.ts      - Wizard step 3
│   └── feed_wizard_step4.ts      - Wizard step 4
├── forms/
│   ├── word_form_auto.ts         - Word form auto-translate/romanize
│   ├── unloadformcheck.ts        - Form change tracking
│   ├── form_validation.ts        - Form validation utilities
│   ├── form_initialization.ts    - Form setup
│   └── bulk_actions.ts           - Bulk action handling
├── reading/
│   ├── text_events.ts            - Text reading interactions
│   ├── audio_controller.ts       - Audio playback controller
│   ├── text_display.ts           - Text display utilities
│   ├── text_reading_init.ts      - Reading page initialization
│   ├── text_keyboard.ts          - Keyboard shortcuts
│   ├── text_multiword_selection.ts - Multi-word selection
│   ├── text_annotations.ts       - Annotation handling
│   ├── annotation_toggle.ts      - Annotation visibility
│   ├── annotation_interactions.ts - Annotation interactions
│   ├── frame_management.ts       - Frame management
│   ├── text_renderer.ts          - Text rendering
│   ├── word_actions.ts           - Word action handlers
│   ├── set_mode_result.ts        - Display mode results
│   ├── components/               - Alpine components
│   │   ├── text_reader.ts        - Text reader component
│   │   ├── word_modal.ts         - Word modal component
│   │   └── word_edit_form.ts     - Word edit form component
│   └── stores/                   - Alpine stores
│       ├── word_store.ts         - Word state store
│       └── word_form_store.ts    - Word form state store
├── terms/
│   ├── translation_api.ts        - Translation APIs
│   ├── overlib_interface.ts      - Legacy popup interface (deprecated)
│   ├── dictionary.ts             - Dictionary link handling
│   ├── word_status.ts            - Word status utilities
│   ├── term_operations.ts        - Term CRUD operations
│   └── translation_page.ts       - Translation page
├── testing/
│   ├── test_mode.ts              - Test mode functionality
│   ├── test_header.ts            - Test header controls
│   ├── test_table.ts             - Test table display
│   ├── test_ajax.ts              - Test AJAX operations
│   ├── elapsed_timer.ts          - Timer utility
│   ├── components/               - Alpine components
│   │   └── test_view.ts          - Test view component
│   └── stores/                   - Alpine stores
│       └── test_store.ts         - Test state store
├── words/
│   ├── word_list_filter.ts       - Word list filtering
│   ├── word_list_table.ts        - Word list table
│   ├── word_dom_updates.ts       - DOM update utilities
│   ├── word_status_ajax.ts       - Status change AJAX
│   ├── word_upload.ts            - Word import/upload
│   ├── bulk_translate.ts         - Bulk translation
│   ├── expression_interactable.ts - Expression interactions
│   └── word_result_init.ts       - Result page initialization
├── texts/
│   ├── text_list.ts              - Text list page
│   ├── texts_grouped_app.ts      - Grouped texts Alpine app
│   ├── archived_texts_grouped_app.ts - Archived texts Alpine app
│   ├── text_status_chart.ts      - Text status chart
│   ├── youtube_import.ts         - YouTube import
│   ├── text_check_display.ts     - Text check display
│   └── text_print.ts             - Print functionality
├── media/
│   ├── html5_audio_player.ts     - HTML5 audio player
│   ├── audio_player_alpine.ts    - Alpine audio player component
│   └── media_selection.ts        - Media file selection
├── languages/
│   ├── language_wizard.ts        - Language setup wizard
│   ├── language_form.ts          - Language form handling
│   └── language_list.ts          - Language list page
├── admin/
│   ├── server_data.ts            - Server data utilities
│   ├── tts_settings.ts           - TTS configuration
│   ├── table_management.ts       - Database table management
│   ├── settings_form.ts          - Settings form
│   └── statistics_charts.ts      - Statistics charts
├── home/
│   └── home_app.ts               - Home page Alpine app
├── tags/
│   └── tag_list.ts               - Tag list management
├── ui/
│   ├── modal.ts                  - Modal dialogs
│   ├── word_popup.ts             - Word popup (Alpine-based)
│   ├── inline_edit.ts            - Inline editing (native)
│   ├── tagify_tags.ts            - Tagify integration
│   ├── sorttable.ts              - Sortable tables
│   ├── navbar.ts                 - Navigation bar
│   ├── footer.ts                 - Footer component
│   ├── result_panel.ts           - Result panel
│   ├── native_tooltip.ts         - Native tooltip implementation
│   ├── lucide_icons.ts           - Lucide icon integration
│   └── icons.ts                  - Icon utilities
└── types/
    └── globals.d.ts              - TypeScript type declarations
```

**CSS:**

```text
src/frontend/css/
├── base/
│   ├── styles.css                - Main stylesheet (Bulma-based)
│   ├── css_charts.css            - Chart visualizations
│   ├── html5_audio_player.css    - HTML5 audio player
│   ├── gallery.css               - Gallery styles
│   ├── mobile.css                - Mobile styles
│   └── standalone.css            - Standalone page styles
└── themes/
    ├── chaosarium_light/
    ├── Default_Mod/
    ├── Lingocracy/
    ├── Lingocracy_Dark/
    ├── Night_Mode/
    └── White_Night/
```

**Dependencies (from npm):**

- Alpine.js 3.x (~15KB minified) - Reactive UI components
- Bulma 1.x (~25KB minified) - CSS framework
- Chart.js 4.x - Data visualization
- Lucide - SVG icons
- Tagify - Tag input widget

**Removed Dependencies:**

- ~~jQuery 1.12.4~~ - **REMOVED** - replaced with vanilla JS + Alpine.js
- ~~jQuery UI 1.12.1~~ - **REMOVED** - replaced with native elements + Bulma
- ~~jPlayer~~ - **REMOVED** - replaced with HTML5 `<audio>`
- ~~Overlib~~ - **REMOVED** - replaced with native tooltips
- ~~jquery.xpath~~ - **REMOVED** - replaced with native `document.evaluate()`
- ~~jQuery plugins~~ - **REMOVED** - replaced with native implementations

### JavaScript Library Inventory (December 2025)

#### Current Libraries

| Library | Size | Purpose | Status |
|---------|------|---------|--------|
| **Alpine.js** | ~15KB | Reactive components, state management | ✅ Active |
| **Bulma** | ~25KB | CSS framework, UI components | ✅ Active |
| **Chart.js** | ~65KB | Data visualization | ✅ Active |
| **Lucide** | ~5KB | SVG icons | ✅ Active |
| **Tagify** | ~30KB | Tag input widget | ✅ Active |

#### Removed Libraries

| Library | Was | Replacement |
|---------|-----|-------------|
| jQuery | 97KB | Vanilla JS + Alpine.js |
| jQuery UI | 240KB | Native elements + Bulma CSS |
| jQuery scrollTo | 2KB | Native `scrollTo()` |
| jQuery jeditable | 8KB | Native inline edit |
| jQuery hoverIntent | 2KB | Native `hoverIntent()` in `hover_intent.ts` |
| jQuery jPlayer | 61KB | HTML5 `<audio>` |
| jQuery XPath | 80KB | Native `document.evaluate()` |
| tag-it | 10KB | Tagify |
| overlib | 75KB | Native tooltips |

**Current JS bundle:** ~995KB (unminified, includes all dependencies)
**Previous JS bundle:** ~600KB (with jQuery ecosystem)

> Note: Bundle size increased due to Alpine.js ecosystem and Chart.js, but provides significantly better functionality and developer experience.

### Issues Resolved

#### ✅ 1. Global Namespace Pollution - RESOLVED

All JavaScript is now organized into TypeScript modules with explicit exports. Global functions are exposed through `globals.ts` for backward compatibility with inline scripts.

#### ✅ 2. Inline Event Handlers - RESOLVED

Zero inline `onclick`, `onchange`, `onsubmit` handlers remain in Views. All event handling uses Alpine.js directives (`@click`, `x-on:`) or data attributes with event delegation.

#### ✅ 3. Backend-Embedded JavaScript - RESOLVED

All inline `<script>` blocks have been migrated to TypeScript modules. PHP Views use JSON config pattern for passing data to JavaScript.

#### ✅ 4. No Centralized API Client - RESOLVED

New `src/frontend/js/api/` directory with type-safe API wrappers:

- `api_client.ts` - Fetch-based client with `apiGet`, `apiPost`, `apiPut`, `apiDelete`
- `terms.ts` - `TermsApi` with methods for term CRUD operations
- `texts.ts` - `TextsApi` with methods for text operations
- `review.ts` - `ReviewApi` with methods for test/review operations
- `settings.ts` - `SettingsApi` with methods for settings

#### ✅ 5. Heavy jQuery Dependency - RESOLVED

jQuery has been completely removed from the codebase. Replacements:

- DOM manipulation → Vanilla JS (`querySelector`, `addEventListener`)
- AJAX → Fetch API in `api_client.ts`
- UI widgets → Alpine.js components + Bulma CSS
- Animations → CSS transitions

#### ✅ 6. Poor Separation of Concerns - RESOLVED

Clear module boundaries established:

- `api/` - API communication
- `core/` - Core utilities
- `ui/` - UI components
- `forms/` - Form handling
- `reading/` - Text reading interface (with Alpine components and stores)
- `testing/` - Test mode (with Alpine components and stores)
- etc.

### Remaining Work

#### 1. Backend-Embedded CSS

One file (`Views/Text/read_text.php`) contains inline CSS for dynamic annotation styling. This is acceptable as it generates CSS based on PHP configuration.

| File | Lines | Description | Status |
|------|-------|-------------|--------|
| `Views/Text/read_text.php` | 80-120 | Dynamic annotation styling (::after, ::before), ruby text | Acceptable - dynamic based on config |

#### 2. Alpine.js Migration Completion ✅

All major pages now use Alpine.js components:

- ✅ Feed wizard pages (already migrated with `feed_wizard_store.ts` and step components)
- ✅ Admin pages (`ttsSettingsApp`, `settingsFormApp`, `statisticsChartsApp`, etc.)
- ✅ Word list pages (`wordListFilterApp`, `wordListTableApp`, `wordUploadFormApp`, `bulkTranslateApp`)

### Technical Metrics

| Metric | Phase 0 | Phase 2.5 | Current | Notes |
|--------|---------|-----------|---------|-------|
| TypeScript Files | 0 | 83 | 104 | +25% growth |
| Test Files | 0 | 72 | 96 | +33% growth |
| Tests | 0 | ~2,500 | 3,051 | Comprehensive |
| Bundle Size (JS) | ~600KB | ~286KB | ~995KB | Includes Alpine + Chart.js |
| Inline Handlers | 50+ | 0 | 0 | ✅ Complete |
| API Endpoints Typed | 0 | 15+ | All | ✅ Complete |
| jQuery Usage | 100% | ~50% | 0% | ✅ Complete |
| Alpine Components | 0 | ~10 | 25+ | ✅ Complete |

---

## Modernization Goals

### Primary Goals

1. **Performance Improvement**
   - ✅ Remove jQuery ecosystem (~400KB saved)
   - ✅ Implement code splitting (Chart.js, Tagify in separate chunks)
   - Improve runtime performance (faster interactions with Alpine.js)

2. **Code Quality**
   - ✅ Establish clear module boundaries (104 TypeScript files)
   - ✅ Implement component-based architecture (Alpine.js components)
   - ✅ Achieve comprehensive test coverage (84 test files)
   - ✅ Reduce code duplication

3. **Developer Experience**
   - ✅ Hot Module Replacement (instant feedback)
   - ✅ Modern IDE support (autocomplete, refactoring)
   - ✅ Type safety (TypeScript)
   - ✅ Clear project structure
   - ✅ Reactive UI patterns (Alpine.js)

4. **Maintainability**
   - ✅ Remove deprecated dependencies (jQuery, overlib, jPlayer, etc.)
   - ✅ Document component APIs
   - ✅ Establish coding standards
   - ✅ Create reusable component library (Alpine components)

5. **User Experience**
   - Faster page interactions
   - Better mobile support (Bulma responsive)
   - Improved accessibility (WCAG 2.1 AA)
   - Modern UI patterns (Bulma components)

### Non-Goals (Out of Scope)

- ❌ Complete UI redesign (visual changes minimal)
- ❌ Backend refactoring (PHP code unchanged unless necessary)
- ❌ Database schema changes
- ❌ Breaking existing functionality
- ❌ Major feature additions during migration

---

## Technology Stack Decisions

### Build System: **Vite** ✅

**Why Vite:**

- Lightning-fast HMR (<100ms updates)
- Simple configuration
- Excellent ES modules support
- Built-in optimizations
- Active development and community

### JavaScript: **TypeScript with ES6+ Modules** ✅

**Standards:**

- TypeScript for type safety
- ES6+ syntax (const/let, arrow functions, classes)
- Native modules (import/export)
- Modern APIs (fetch, async/await)
- No transpilation unless needed (target modern browsers)

**Browser Support:**

- Chrome/Edge 90+
- Firefox 88+
- Safari 14+
- No IE11 support

### UI Framework: **Alpine.js** ✅

**Why Alpine.js:**

- Minimal footprint (~15KB)
- Progressive enhancement (works with existing HTML)
- Declarative syntax (`x-data`, `x-on:`, `x-bind:`)
- No build step required (but integrates well with Vite)
- Easy migration from jQuery patterns

**Usage Patterns:**

```typescript
// src/frontend/js/reading/stores/word_store.ts
Alpine.store('word', {
  selectedWord: null,
  translation: '',
  setWord(word: Word) { ... }
});

// src/frontend/js/reading/components/word_modal.ts
Alpine.data('wordModal', () => ({
  isOpen: false,
  open() { this.isOpen = true; },
  close() { this.isOpen = false; }
}));
```

### CSS Framework: **Bulma** ✅

**Why Bulma:**

- Modern flexbox-based CSS
- No JavaScript required
- Modular (import only what you need)
- Great documentation
- Responsive by default

### API Client: **Fetch-based with Type-Safe Wrappers** ✅

```typescript
// src/frontend/js/core/api_client.ts
export async function apiGet<T>(endpoint: string, params?): Promise<ApiResponse<T>>
export async function apiPost<T>(endpoint: string, body): Promise<ApiResponse<T>>
export async function apiPut<T>(endpoint: string, body): Promise<ApiResponse<T>>
export async function apiDelete<T>(endpoint: string): Promise<ApiResponse<T>>

// src/frontend/js/api/terms.ts
export const TermsApi = {
  async get(termId: number): Promise<ApiResponse<Term>>,
  async setStatus(termId: number, status: number): Promise<ApiResponse<TermStatusResponse>>,
  async updateTranslation(termId: number, translation: string): Promise<ApiResponse<TermTranslationResponse>>,
  // ...
};
```

### Testing: **Vitest + Testing Library** ✅

**Tools:**

- Vitest (Vite-native test runner)
- @testing-library/dom (DOM testing utilities)
- jsdom (DOM environment)
- Cypress (E2E tests)

---

## Migration Phases

### Phase 0: Foundation Setup ✅ **COMPLETE**

**Completed Tasks:**

1. ✅ Install Node.js dependencies (Vite, TypeScript)
2. ✅ Set up Vite configuration with legacy browser support
3. ✅ Create TypeScript configuration
4. ✅ Create type declarations for PHP-injected globals
5. ✅ Create PHP helper for Vite asset loading
6. ✅ Modify View Helpers for conditional asset loading

---

### Phase 1: TypeScript Migration ✅ **COMPLETE**

**Completed Tasks:**

1. ✅ Convert all JavaScript files to TypeScript
2. ✅ Fix all TypeScript errors
3. ✅ Import all modules in `main.ts` entry point
4. ✅ Remove old `.js` source files
5. ✅ Update `tsconfig.json` to disable `allowJs`
6. ✅ Verify build produces working bundles

---

### Phase 2: Build Pipeline Integration ✅ **COMPLETE**

**Completed Tasks:**

1. ✅ Created Node.js theme builder (`scripts/build-themes.js`)
2. ✅ Removed PHP minification entirely
3. ✅ Updated npm scripts
4. ✅ Updated documentation

**Build Commands:**

```bash
npm run build                    # Build Vite JS/CSS bundles
npm run build:themes             # Build theme CSS files
npm run build:all                # Build everything (Vite + themes)
npm run dev                      # Start Vite dev server with HMR
npm run typecheck                # Run TypeScript type checking
composer build                   # Alias for npm run build:all
```

---

### Phase 2.5: Backend JavaScript Extraction ✅ **COMPLETE**

**Completed Tasks:**

1. ✅ All inline event handlers replaced with data attributes
2. ✅ All `<script>` blocks moved to TypeScript modules
3. ✅ PHP functions return data instead of generating JS
4. ✅ New TypeScript modules created and tested
5. ✅ Centralized API client with type-safe wrappers
6. ✅ Comprehensive test suite (96 test files)

---

### Phase 3: jQuery Removal ✅ **COMPLETE**

**Completed Tasks:**

1. ✅ Replace jQuery DOM manipulation with vanilla JS
2. ✅ Replace jQuery AJAX with Fetch API
3. ✅ Remove jQuery from dependencies
4. ✅ Remove jQuery UI widgets
5. ✅ Adopt Alpine.js for reactive components
6. ✅ Adopt Bulma for CSS framework

**jQuery Replacement Summary:**

| jQuery Feature | Replacement |
|---------------|-------------|
| `$(selector)` | `document.querySelector()` |
| `.on()` | `addEventListener()` or Alpine `@click` |
| `.ajax()` | Fetch API via `api_client.ts` |
| `.animate()` | CSS transitions |
| UI Dialog | Bulma modal + Alpine |
| UI Tooltip | Native tooltip (`native_tooltip.ts`) |
| UI Resizable | CSS `resize` property |
| UI Draggable | Native Drag and Drop API |

---

### Phase 4: Component Architecture ✅ **COMPLETE**

**Goals:**

- Expand Alpine.js component library
- Create reusable UI components
- Improve state management with Alpine stores
- Migrate remaining vanilla JS to Alpine patterns

**Completed:**

- ✅ Reading page components (`text_reader.ts`, `word_modal.ts`, `word_edit_form.ts`)
- ✅ Reading stores (`word_store.ts`, `word_form_store.ts`)
- ✅ Testing components (`test_view.ts`)
- ✅ Testing stores (`test_store.ts`)
- ✅ Text list apps (`texts_grouped_app.ts`, `archived_texts_grouped_app.ts`)
- ✅ Home app (`home_app.ts`)
- ✅ Audio player component (`audio_player_alpine.ts`)
- ✅ UI components (`navbar.ts`, `footer.ts`, `word_popup.ts`)
- ✅ Feed wizard components (`feed_wizard_store.ts`, step components)
- ✅ Admin components (`ttsSettingsApp`, `settingsFormApp`, `statisticsChartsApp`, `backupManagerApp`, `tableManagementApp`)
- ✅ Word list components (`wordListFilterApp`, `wordListTableApp`, `wordUploadFormApp`, `wordUploadResultApp`, `bulkTranslateApp`)

---

### Phase 5: Polish & Optimization 🎯 **PLANNED**

**Goals:**

- CSS modernization (full Bulma adoption)
- Performance optimization (Lighthouse 90+)
- Accessibility improvements (WCAG 2.1 AA)
- Code splitting optimization
- Bundle size optimization

---

## Risk Management

### Risks Mitigated

| Risk | Mitigation | Status |
|------|------------|--------|
| Breaking changes | Incremental migration, comprehensive tests | ✅ Managed |
| Type errors | Gradual TypeScript adoption | ✅ Resolved |
| jQuery removal complexity | Phased approach, Alpine.js adoption | ✅ Complete |
| Test coverage | 84 test files added | ✅ Strong coverage |

### Current Risks

| Risk | Level | Mitigation |
|------|-------|------------|
| Bundle size growth | Low | Code splitting, lazy loading |
| Browser compatibility | Low | Modern browser targets defined |
| Performance regression | Low | Lighthouse monitoring |

---

## Success Metrics

### Completed Metrics

| Metric | Target | Achieved | Notes |
|--------|--------|----------|-------|
| TypeScript Migration | 100% | ✅ 100% | 104 files |
| Inline Handlers | 0 | ✅ 0 | All removed |
| Test Files | 70+ | ✅ 96 | Comprehensive |
| jQuery Removal | 100% | ✅ 100% | Fully removed |
| API Type Safety | All endpoints | ✅ All | Complete coverage |
| Alpine Components | All pages | ✅ 25+ | Reading, testing, texts, admin, feeds, words |

### Pending Metrics

| Metric | Target | Current | Status |
|--------|--------|---------|--------|
| Lighthouse Performance | 90+ | TBD | Phase 5 |
| Accessibility Score | 95+ | TBD | Phase 5 |
| Alpine Migration | 100% | ✅ 100% | Complete |

---

## Timeline & Milestones

### Completed Milestones

| Phase | Milestone | Completed |
|-------|-----------|-----------|
| 0 | Vite + TypeScript setup | ✅ Nov 2025 |
| 1 | TypeScript migration | ✅ Nov 2025 |
| 2 | Build pipeline | ✅ Nov 2025 |
| 2.5 | API client + tests | ✅ Nov 2025 |
| 3 | jQuery removal + Alpine/Bulma adoption | ✅ Dec 2025 |
| 4 | Full Alpine component architecture | ✅ Dec 2025 |

### Upcoming Milestones

| Phase | Milestone | Target |
|-------|-----------|--------|
| 5 | Performance optimization | TBD |

---

## Resources & References

### Documentation

- [Vite Documentation](https://vitejs.dev/)
- [TypeScript Handbook](https://www.typescriptlang.org/docs/)
- [Alpine.js Documentation](https://alpinejs.dev/)
- [Bulma Documentation](https://bulma.io/documentation/)
- [Modern JavaScript Tutorial](https://javascript.info/)
- [Web.dev Performance](https://web.dev/performance/)

### Project Files

- `CLAUDE.md` - Developer guide
- `package.json` - npm configuration
- `tsconfig.json` - TypeScript configuration
- `vite.config.ts` - Vite build configuration

### Testing

```bash
npm test                         # Run Vitest tests
npm run test:coverage           # Coverage report
npm run e2e                     # Cypress E2E tests
npm run typecheck               # TypeScript checking
```

---

## Appendix: File Organization

### Test File Structure

```text
tests/frontend/
├── admin/                       # Admin tests (5 files)
│   ├── server_data.test.ts
│   ├── settings_form.test.ts
│   ├── statistics_charts.test.ts
│   ├── table_management.test.ts
│   └── tts_settings.test.ts
├── api/                         # API tests (4 files)
│   ├── review.test.ts
│   ├── settings.test.ts
│   ├── terms.test.ts
│   └── texts.test.ts
├── core/                        # Core tests (13 files)
│   ├── ajax_utilities.test.ts
│   ├── api_client.test.ts
│   ├── app_data.test.ts
│   ├── globals.test.ts
│   ├── hover_intent.test.ts
│   ├── language_settings.test.ts
│   ├── lwt_state.test.ts
│   ├── simple_interactions.test.ts
│   ├── tts_storage.test.ts
│   ├── ui_utilities.test.ts
│   ├── user_interactions.test.ts
│   └── utilities.test.ts
├── feeds/                       # Feed tests (11 files)
│   ├── feed_browse.test.ts
│   ├── feed_form.test.ts
│   ├── feed_index.test.ts
│   ├── feed_loader.test.ts
│   ├── feed_multi_load.test.ts
│   ├── feed_text_edit.test.ts
│   ├── feed_wizard_common.test.ts
│   ├── feed_wizard_step2.test.ts
│   ├── feed_wizard_step3.test.ts
│   ├── feed_wizard_step4.test.ts
│   └── jq_feedwizard.test.ts
├── forms/                       # Form tests (5 files)
│   ├── bulk_actions.test.ts
│   ├── form_initialization.test.ts
│   ├── form_validation.test.ts
│   ├── unloadformcheck.test.ts
│   └── word_form_auto.test.ts
├── home/                        # Home tests (1 file)
│   └── home_app.test.ts
├── languages/                   # Language tests (2 files)
│   ├── language_form.test.ts
│   └── language_wizard.test.ts
├── media/                       # Media tests (2 files)
│   ├── html5_audio_player.test.ts
│   └── media_selection.test.ts
├── reading/                     # Reading tests (13 files)
│   ├── annotation_interactions.test.ts
│   ├── annotation_toggle.test.ts
│   ├── frame_management.test.ts
│   ├── set_mode_result.test.ts
│   ├── text_annotations.test.ts
│   ├── text_display.test.ts
│   ├── text_events.test.ts
│   ├── text_keyboard.test.ts
│   ├── text_multiword_selection.test.ts
│   ├── text_reading_init.test.ts
│   └── word_actions.test.ts
├── tags/                        # Tag tests (1 file)
│   └── tag_list.test.ts
├── terms/                       # Term tests (4 files)
│   ├── dictionary.test.ts
│   ├── overlib_interface.test.ts
│   ├── term_operations.test.ts
│   └── translation_api.test.ts
├── testing/                     # Testing tests (5 files)
│   ├── elapsed_timer.test.ts
│   ├── test_ajax.test.ts
│   ├── test_header.test.ts
│   ├── test_mode.test.ts
│   └── test_table.test.ts
├── texts/                       # Text tests (4 files)
│   ├── text_check_display.test.ts
│   ├── text_list.test.ts
│   ├── text_print.test.ts
│   └── youtube_import.test.ts
├── ui/                          # UI tests (8 files)
│   ├── inline_edit.test.ts
│   ├── lucide_icons.test.ts
│   ├── modal.test.ts
│   ├── native_tooltip.test.ts
│   ├── result_panel.test.ts
│   ├── sorttable.test.ts
│   ├── tagify_tags.test.ts
│   └── word_popup.test.ts
└── words/                       # Word tests (8 files)
    ├── bulk_translate.test.ts
    ├── expression_interactable.test.ts
    ├── word_dom_updates.test.ts
    ├── word_list_filter.test.ts
    ├── word_list_table.test.ts
    ├── word_result_init.test.ts
    ├── word_status_ajax.test.ts
    └── word_upload.test.ts
```

### Alpine.js Components Structure

```text
src/frontend/js/
├── admin/
│   ├── tts_settings.ts           - TTS settings component (ttsSettingsApp)
│   ├── settings_form.ts          - Settings form component (settingsFormApp)
│   ├── statistics_charts.ts      - Statistics charts component
│   ├── backup_manager.ts         - Backup manager component (backupManagerApp)
│   └── table_management.ts       - Table management component (tableManagementApp)
├── feeds/
│   ├── components/
│   │   ├── feed_wizard_step1.ts  - Wizard step 1 component
│   │   ├── feed_wizard_step2.ts  - Wizard step 2 component
│   │   ├── feed_wizard_step3.ts  - Wizard step 3 component
│   │   └── feed_wizard_step4.ts  - Wizard step 4 component
│   └── stores/
│       └── feed_wizard_store.ts  - Feed wizard state management
├── reading/
│   ├── components/
│   │   ├── text_reader.ts        - Main text reading component
│   │   ├── word_modal.ts         - Word editing modal
│   │   └── word_edit_form.ts     - Word form component
│   └── stores/
│       ├── word_store.ts         - Selected word state
│       └── word_form_store.ts    - Form state management
├── testing/
│   ├── components/
│   │   └── test_view.ts          - Test view component
│   └── stores/
│       └── test_store.ts         - Test state management
├── texts/
│   ├── texts_grouped_app.ts      - Texts list Alpine app
│   └── archived_texts_grouped_app.ts - Archived texts Alpine app
├── words/
│   ├── word_list_filter.ts       - Word list filter component (wordListFilterApp)
│   ├── word_list_table.ts        - Word list table component (wordListTableApp)
│   ├── word_upload.ts            - Word upload components (wordUploadFormApp, wordUploadResultApp)
│   └── bulk_translate.ts         - Bulk translate component (bulkTranslateApp)
├── media/
│   └── audio_player_alpine.ts    - Audio player component
├── home/
│   └── home_app.ts               - Home page Alpine app
└── ui/
    ├── navbar.ts                 - Navigation component
    ├── footer.ts                 - Footer component
    └── word_popup.ts             - Word popup component
```
