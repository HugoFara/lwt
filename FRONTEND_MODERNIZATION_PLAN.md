# Frontend Modernization Plan

**Project:** Learning with Texts (LWT)
**Document Version:** 6.0
**Last Updated:** November 30, 2025
**Status:** Phase 2.5 Complete - Centralized API Client, Comprehensive Test Coverage

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

This document outlines a comprehensive plan to modernize the Learning with Texts (LWT) frontend codebase. The current implementation relies on jQuery and outdated patterns from 2010-2015. This modernization will improve performance, maintainability, and developer experience while maintaining backward compatibility during the transition.

**Key Objectives:**

- ✅ Modernize build system (Vite) - **COMPLETE**
- ✅ Add TypeScript for type safety - **COMPLETE**
- ✅ Convert to ES6+ modules - **COMPLETE** (83 TypeScript files, ~17,500 lines)
- ✅ Extract backend-embedded JavaScript - **COMPLETE** (zero inline handlers)
- ✅ Centralized API client with type-safe wrappers - **COMPLETE**
- ✅ Comprehensive test suite - **COMPLETE** (72 test files, ~34,000 lines)
- 🔧 Replace jQuery with vanilla JS - **IN PROGRESS** (Phase 3)
- Keep jQuery 1.12.4 from npm (minimize breaking changes during transition)
- Improve CSS organization and theming
- Enhance code maintainability and testability

**Risk Level:** Low (phased approach proven successful)
**Expected ROI:** High (improved DX, performance, maintainability)

> **Note:** The original plan suggested removing jQuery. After discussion, the decision was made to **keep jQuery 1.12.4** from npm to minimize breaking changes during the initial modernization. jQuery removal can be considered in a future phase.

---

## Current State Analysis

### Architecture Overview

**JavaScript:**

- **Total Lines:** ~17,500 lines across 83 TypeScript files
- **Test Coverage:** 72 test files with ~34,000 lines of tests
- **Module System:** ES6 modules (TypeScript)
- **Framework:** jQuery 1.12.4 (from npm)
- **State Management:** Centralized `LWT_DATA` object with typed interface
- **API Client:** Centralized fetch-based client with type-safe wrappers
- **Build Process:** Vite with TypeScript

**Key Files:**

```text
src/frontend/js/
├── main.ts                       - Vite entry point
├── globals.ts                    - Global exports for inline PHP scripts (~560 lines)
├── api/                          - Centralized API client (NEW)
│   ├── terms.ts                  - Terms/vocabulary API
│   ├── texts.ts                  - Texts API
│   ├── review.ts                 - Review/testing API
│   └── settings.ts               - Settings API
├── core/
│   ├── api_client.ts             - Fetch-based API client (NEW)
│   ├── lwt_state.ts              - Centralized state management
│   ├── app_data.ts               - Application data utilities
│   ├── language_settings.ts      - Language filter utilities
│   ├── user_interactions.ts      - UI interactions
│   ├── ajax_utilities.ts         - AJAX helper functions
│   ├── ui_utilities.ts           - UI utility functions
│   ├── simple_interactions.ts    - Navigation, confirmation
│   ├── hover_intent.ts           - Native hover intent implementation
│   ├── cookies.ts                - Cookie management
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
│   └── set_mode_result.ts        - Display mode results
├── terms/
│   ├── translation_api.ts        - Translation APIs
│   ├── overlib_interface.ts      - Popup library interface (jQuery UI tooltips)
│   ├── dictionary.ts             - Dictionary link handling
│   ├── word_status.ts            - Word status utilities
│   ├── term_operations.ts        - Term CRUD operations
│   └── translation_page.ts       - Translation page
├── testing/
│   ├── test_mode.ts              - Test mode functionality
│   ├── test_header.ts            - Test header controls
│   ├── test_table.ts             - Test table display
│   ├── test_ajax.ts              - Test AJAX operations
│   └── elapsed_timer.ts          - Timer utility
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
│   ├── youtube_import.ts         - YouTube import
│   ├── text_check_display.ts     - Text check display
│   └── text_print.ts             - Print functionality
├── media/
│   ├── html5_audio_player.ts     - HTML5 audio player
│   └── media_selection.ts        - Media file selection
├── languages/
│   ├── language_wizard.ts        - Language setup wizard
│   └── language_form.ts          - Language form handling
├── admin/
│   ├── server_data.ts            - Server data utilities
│   ├── tts_settings.ts           - TTS configuration
│   ├── table_management.ts       - Database table management
│   └── settings_form.ts          - Settings form
├── home/
│   └── home_warnings.ts          - Home page warnings
├── tags/
│   └── tag_list.ts               - Tag list management
├── ui/
│   ├── modal.ts                  - Modal dialogs
│   ├── word_popup.ts             - Word popup (overlib replacement)
│   ├── inline_edit.ts            - Inline editing
│   ├── tagify_tags.ts            - Tagify integration
│   └── sorttable.ts              - Sortable tables
├── shims/
│   ├── jquery-shim.ts            - jQuery compatibility
│   └── jquery-ui-shim.ts         - jQuery UI compatibility
└── types/
    └── globals.d.ts              - TypeScript type declarations
```

**CSS:**

```text
src/frontend/css/
├── base/
│   ├── styles.css                - Main stylesheet
│   ├── css_charts.css            - Chart visualizations
│   ├── jquery-ui.css             - jQuery UI widgets
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

- jQuery 1.12.4 (~85KB minified)
- jQuery UI 1.12.1 (~250KB with CSS)
- Tagify (tag input - replacement for tag-it)
- ~~jPlayer~~ (removed - replaced with HTML5 `<audio>`)
- ~~Overlib~~ (removed - replaced with jQuery UI tooltips)
- ~~jquery.xpath~~ (removed - replaced with native `document.evaluate()`)
- ~~jQuery plugins: jeditable, scrollTo, hoverIntent~~ (removed - replaced with native implementations)

### JavaScript Library Inventory (November 2025)

#### External Libraries (in `assets/js/`)

| Library | File | Size | Purpose | Status |
|---------|------|------|---------|--------|
| **jQuery** | `jquery.js` | 97KB | DOM manipulation, AJAX | ✅ Kept (from npm) |
| **jQuery UI** | `jquery-ui.min.js` | 240KB | UI widgets (dialogs, tooltips, draggable) | ✅ Kept (provides tooltip, dialog, resizable) |
| ~~**jQuery scrollTo**~~ | ~~`jquery.scrollTo.min.js`~~ | ~~2KB~~ | ~~Smooth scrolling~~ | ✅ **REMOVED** - replaced with native `scrollTo()` in `hover_intent.ts` |
| ~~**jQuery jeditable**~~ | ~~`jquery.jeditable.mini.js`~~ | ~~8KB~~ | ~~In-place editing~~ | ✅ **REMOVED** - was unused |
| ~~**jQuery hoverIntent**~~ | ~~`jquery.hoverIntent.js`~~ | ~~2KB~~ | ~~Delayed hover events~~ | ✅ **REMOVED** - replaced with native `hoverIntent()` in `hover_intent.ts` |
| ~~**jQuery jPlayer**~~ | ~~`jquery.jplayer.min.js`~~ | ~~61KB~~ | ~~Audio/video player~~ | ✅ **REMOVED** - replaced with HTML5 `<audio>` |
| ~~**jQuery XPath**~~ | ~~`jquery.xpath.min.js`~~ | ~~80KB~~ | ~~XPath selector (feed wizard)~~ | ✅ **REMOVED** - replaced with native `document.evaluate()` |
| ~~**tag-it**~~ | ~~`tag-it.js`~~ | ~~10KB~~ | ~~Tag input widget~~ | ✅ **REMOVED** - replaced with Tagify |
| ~~**overlib**~~ | ~~`overlib/overlib_mini.js` + plugins~~ | ~~75KB~~ | ~~Popup/tooltip library~~ | ✅ **REMOVED** - replaced with jQuery UI tooltips |

**Current JS size:** ~286KB (main bundle, uncompressed) - reduced from ~575KB

#### Priority Removal Order (Future)

1. ~~**overlib** (75KB)~~ - ✅ **REMOVED** - replaced with jQuery UI tooltips
2. ~~**jPlayer** (61KB)~~ - ✅ **REMOVED** - replaced with HTML5 `<audio>`
3. ~~**jquery.xpath** (80KB)~~ - ✅ **REMOVED** - replaced with native `document.evaluate()`
4. ~~**jquery.hoverIntent** (2KB)~~ - ✅ **REMOVED** - replaced with native `hoverIntent()` in `hover_intent.ts`
5. ~~**jquery.scrollTo** (2KB)~~ - ✅ **REMOVED** - replaced with native `scrollTo()` in `hover_intent.ts`
6. ~~**jquery.jeditable** (8KB)~~ - ✅ **REMOVED** - was unused
7. ~~**tag-it** (10KB)~~ - ✅ **REMOVED** - replaced with Tagify
8. **jQuery + jQuery UI** (337KB) - Last, requires significant refactoring

### Issues Resolved

#### ✅ 1. Global Namespace Pollution - RESOLVED

All JavaScript is now organized into TypeScript modules with explicit exports. Global functions are exposed through `globals.ts` for backward compatibility with inline scripts.

#### ✅ 2. Inline Event Handlers - RESOLVED

Zero inline `onclick`, `onchange`, `onsubmit` handlers remain in Views. All event handling uses data attributes and event delegation.

#### ✅ 3. Backend-Embedded JavaScript - RESOLVED

All inline `<script>` blocks have been migrated to TypeScript modules. PHP Views use JSON config pattern for passing data to JavaScript.

#### ✅ 4. No Centralized API Client - RESOLVED

New `src/frontend/js/api/` directory with type-safe API wrappers:
- `api_client.ts` - Fetch-based client with `apiGet`, `apiPost`, `apiPut`, `apiDelete`
- `terms.ts` - `TermsApi` with methods for term CRUD operations
- `texts.ts` - `TextsApi` with methods for text operations
- `review.ts` - `ReviewApi` with methods for test/review operations
- `settings.ts` - `SettingsApi` with methods for settings

#### 🔧 5. Heavy jQuery Dependency - IN PROGRESS

jQuery is still used but migration utilities are in place. Native replacements exist for:
- XPath selection → `document.evaluate()`
- Scroll → `Element.scrollIntoView()`
- Hover intent → Native implementation in `hover_intent.ts`
- AJAX → Fetch API in `api_client.ts`

#### ✅ 6. Poor Separation of Concerns - RESOLVED

Clear module boundaries established:
- `api/` - API communication
- `core/` - Core utilities
- `ui/` - UI components
- `forms/` - Form handling
- `reading/` - Text reading interface
- etc.

### Remaining Issues

#### 1. Backend-Embedded CSS

One file (`Views/Text/read_text.php`) contains inline CSS for dynamic annotation styling. This is acceptable as it generates CSS based on PHP configuration.

| File | Lines | Description | Status |
|------|-------|-------------|--------|
| `Views/Text/read_text.php` | 80-120 | Dynamic annotation styling (::after, ::before), ruby text | Acceptable - dynamic based on config |

#### 2. jQuery Usage

jQuery is still used for:
- jQuery UI widgets (tooltips, dialogs, resizable)
- Some DOM manipulation in legacy code
- Animation effects

### Technical Metrics

| Metric | Phase 0 | Current | Target | Notes |
|--------|---------|---------|--------|-------|
| TypeScript Files | 0 | 83 | 83 | ✅ Complete |
| Test Files | 0 | 72 | 83 | 87% coverage |
| Test Lines | 0 | ~34,000 | - | Comprehensive |
| Bundle Size (JS) | ~600KB | ~286KB | <200KB | 52% reduction |
| Inline Handlers | 50+ | 0 | 0 | ✅ Complete |
| API Endpoints Typed | 0 | 15+ | All | Good progress |

---

## Modernization Goals

### Primary Goals

1. **Performance Improvement**
   - ✅ Reduce bundle size by 52% (from ~600KB to ~286KB)
   - Target: <200KB with jQuery removal
   - Implement code splitting and lazy loading
   - Improve runtime performance (faster interactions)

2. **Code Quality**
   - ✅ Establish clear module boundaries (83 TypeScript files)
   - ✅ Implement component-based architecture
   - ✅ Achieve comprehensive test coverage (72 test files)
   - ✅ Reduce code duplication

3. **Developer Experience**
   - ✅ Hot Module Replacement (instant feedback)
   - ✅ Modern IDE support (autocomplete, refactoring)
   - ✅ Type safety (TypeScript)
   - ✅ Clear project structure

4. **Maintainability**
   - ✅ Remove deprecated dependencies (overlib, jPlayer, etc.)
   - ✅ Document component APIs
   - ✅ Establish coding standards
   - Create reusable component library

5. **User Experience**
   - Faster page interactions
   - Better mobile support
   - Improved accessibility (WCAG 2.1 AA)
   - Modern UI patterns

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

1. ✅ Install Node.js dependencies (Vite, TypeScript, jQuery from npm)
2. ✅ Set up Vite configuration with legacy browser support
3. ✅ Create TypeScript configuration
4. ✅ Create type declarations for PHP-injected globals
5. ✅ Create PHP helper for Vite asset loading
6. ✅ Modify View Helpers for conditional asset loading

---

### Phase 1: TypeScript Migration ✅ **COMPLETE**

**Completed Tasks:**

1. ✅ Convert all JavaScript files to TypeScript (83 files)
2. ✅ Fix all TypeScript errors (type safety issues with jQuery, etc.)
3. ✅ Import all modules in `main.ts` entry point
4. ✅ Remove old `.js` source files
5. ✅ Update `tsconfig.json` to disable `allowJs`
6. ✅ Verify build produces working bundles

**Build Output:**

- Main JS bundle: `main.[hash].js` (~286 KB)
- Main CSS: `main.[hash].css` (~37 KB)
- All functions exported to global scope for backward compatibility

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
6. ✅ Comprehensive test suite (72 test files)

**API Client Implementation:**

```typescript
// Centralized API client
src/frontend/js/core/api_client.ts  // Base fetch wrapper
src/frontend/js/api/terms.ts        // TermsApi
src/frontend/js/api/texts.ts        // TextsApi
src/frontend/js/api/review.ts       // ReviewApi
src/frontend/js/api/settings.ts     // SettingsApi
```

**Migration Checklist (All Complete):**

- [x] `Views/Feed/browse.php` - Replace all onclick/onchange handlers
- [x] `Views/Word/form_edit_new.php` - Extract auto-translate logic
- [x] `Services/FeedService.php` - Refactor `load_feeds()` to return data
- [x] `Views/Text/edit_form.php` - Extract language switching logic
- [x] `Core/Word/dictionary_links.php` - Refactor dictionary link generation
- [x] `Views/Feed/index.php` - Replace inline handlers
- [x] `Views/Feed/multi_load.php` - Extract feed loading logic
- [x] All Views - Zero inline handlers remaining

**Success Criteria (All Met):**

- [x] Zero inline `onclick`/`onchange` attributes in Views
- [x] Zero PHP functions that `echo` JavaScript
- [x] All extracted JS has TypeScript types
- [x] Existing functionality preserved (E2E tests pass)
- [x] Comprehensive test coverage

---

### Phase 3: jQuery Removal 🔧 **IN PROGRESS**

**Goals:**

- Replace jQuery DOM manipulation with vanilla JS
- Replace jQuery AJAX with Fetch API (✅ done via api_client.ts)
- Remove jQuery dependencies
- Maintain functionality

#### Task 3.1: jQuery Replacement Utilities

Already implemented:
- `src/frontend/js/core/api_client.ts` - Fetch-based API client
- `src/frontend/js/core/hover_intent.ts` - Native hover intent
- Native `scrollTo()` replacement

#### Task 3.2: Migrate Core Functions

**Priority Migration Order:**

1. Simple DOM queries → `document.querySelector/querySelectorAll`
2. Event handling → `addEventListener` with delegation
3. AJAX calls → Already migrated to `apiGet/apiPost` etc.
4. Animations → CSS transitions or Web Animations API
5. jQuery UI widgets → Last (most complex)

#### Task 3.3: jQuery UI Replacement Strategy

| Widget | Replacement | Priority |
|--------|-------------|----------|
| Tooltips | Native `title` + CSS or Tippy.js | Medium |
| Dialogs | Native `<dialog>` element | High |
| Resizable | CSS `resize` or custom | Low |
| Draggable | Native Drag and Drop API | Low |

---

### Phase 4: Component Architecture 🎯 **PLANNED**

**Goals:**

- Consider Alpine.js or vanilla component patterns
- Create reusable components
- Establish state management
- Improve code organization

#### Task 4.1: Evaluate Framework Options

| Framework | Pros | Cons | Recommendation |
|-----------|------|------|----------------|
| **Alpine.js** | Minimal, progressive, easy migration | Less powerful for complex UIs | Consider |
| **Vanilla JS** | No dependencies, full control | More boilerplate | Current approach |
| **Web Components** | Framework-agnostic, native | More complex setup | Future consideration |

---

### Phase 5: Polish & Optimization 🎯 **PLANNED**

**Goals:**

- CSS modernization
- Performance optimization
- Accessibility improvements
- Code splitting

---

## Risk Management

### Risks Mitigated

| Risk | Mitigation | Status |
|------|------------|--------|
| Breaking changes | Incremental migration, comprehensive tests | ✅ Managed |
| Type errors | Gradual TypeScript adoption | ✅ Resolved |
| Bundle size | Removed legacy libraries | ✅ 52% reduction |
| Test coverage | 72 test files added | ✅ Strong coverage |

### Current Risks

| Risk | Level | Mitigation |
|------|-------|------------|
| jQuery UI replacement | Medium | Evaluate alternatives carefully |
| Browser compatibility | Low | Modern browser targets defined |
| Performance regression | Low | Lighthouse monitoring |

---

## Success Metrics

### Completed Metrics

| Metric | Target | Achieved | Notes |
|--------|--------|----------|-------|
| TypeScript Migration | 100% | ✅ 100% | 83 files |
| Inline Handlers | 0 | ✅ 0 | All removed |
| Test Files | 70+ | ✅ 72 | Comprehensive |
| Bundle Reduction | 50% | ✅ 52% | 600KB → 286KB |
| API Type Safety | All endpoints | ✅ 15+ | Good coverage |

### Pending Metrics

| Metric | Target | Current | Status |
|--------|--------|---------|--------|
| Bundle Size | <200KB | 286KB | In progress |
| jQuery Removal | 100% | ~50% | Phase 3 |
| Lighthouse Performance | 90+ | TBD | Planned |
| Accessibility Score | 95+ | TBD | Planned |

---

## Timeline & Milestones

### Completed Milestones

| Phase | Milestone | Completed |
|-------|-----------|-----------|
| 0 | Vite + TypeScript setup | ✅ Nov 2025 |
| 1 | TypeScript migration | ✅ Nov 2025 |
| 2 | Build pipeline | ✅ Nov 2025 |
| 2.5 | API client + tests | ✅ Nov 2025 |

### Upcoming Milestones

| Phase | Milestone | Target |
|-------|-----------|--------|
| 3 | jQuery removal | TBD |
| 4 | Component architecture | TBD |
| 5 | Performance optimization | TBD |

---

## Resources & References

### Documentation

- [Vite Documentation](https://vitejs.dev/)
- [TypeScript Handbook](https://www.typescriptlang.org/docs/)
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
├── admin/                       # Admin tests
│   ├── server_data.test.ts
│   ├── settings_form.test.ts
│   ├── table_management.test.ts
│   └── tts_settings.test.ts
├── core/                        # Core tests
│   ├── ajax_utilities.test.ts
│   ├── app_data.test.ts
│   ├── globals.test.ts
│   ├── hover_intent.test.ts
│   ├── lwt_state.test.ts
│   ├── simple_interactions.test.ts
│   ├── ui_utilities.test.ts
│   ├── user_interactions.test.ts
│   └── utilities.test.ts
├── feeds/                       # Feed tests
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
├── forms/                       # Form tests
│   ├── bulk_actions.test.ts
│   ├── form_initialization.test.ts
│   ├── form_validation.test.ts
│   ├── unloadformcheck.test.ts
│   └── word_form_auto.test.ts
├── home/                        # Home tests
│   └── home_warnings.test.ts
├── languages/                   # Language tests
│   ├── language_form.test.ts
│   └── language_wizard.test.ts
├── media/                       # Media tests
│   ├── html5_audio_player.test.ts
│   └── media_selection.test.ts
├── reading/                     # Reading tests
│   ├── annotation_interactions.test.ts
│   ├── annotation_toggle.test.ts
│   ├── audio_controller.test.ts
│   ├── frame_management.test.ts
│   ├── set_mode_result.test.ts
│   ├── text_annotations.test.ts
│   ├── text_display.test.ts
│   ├── text_events.test.ts
│   ├── text_keyboard.test.ts
│   ├── text_multiword_selection.test.ts
│   └── text_reading_init.test.ts
├── tags/                        # Tag tests
│   └── tag_list.test.ts
├── terms/                       # Term tests
│   ├── overlib_interface.test.ts
│   ├── term_operations.test.ts
│   ├── translation_api.test.ts
│   └── translation_page.test.ts
├── testing/                     # Testing tests
│   ├── elapsed_timer.test.ts
│   ├── test_ajax.test.ts
│   ├── test_header.test.ts
│   ├── test_mode.test.ts
│   └── test_table.test.ts
├── texts/                       # Text tests
│   ├── text_check_display.test.ts
│   ├── text_list.test.ts
│   ├── text_print.test.ts
│   └── youtube_import.test.ts
├── ui/                          # UI tests
│   ├── inline_edit.test.ts
│   ├── modal.test.ts
│   ├── sorttable.test.ts
│   ├── tagify_tags.test.ts
│   └── word_popup.test.ts
└── words/                       # Word tests
    ├── bulk_translate.test.ts
    ├── expression_interactable.test.ts
    ├── word_dom_updates.test.ts
    ├── word_list_filter.test.ts
    ├── word_list_table.test.ts
    ├── word_result_init.test.ts
    ├── word_status_ajax.test.ts
    └── word_upload.test.ts
```
