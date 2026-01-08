# JavaScript-to-PHP Communication Modernization Plan

This document outlines a phased approach to modernize the JavaScript-to-PHP communication in LWT.

## Current State Analysis

### Communication Patterns Found

| Pattern | Count | Files |
|---------|-------|-------|
| jQuery `$.post()` | 7 | term_operations.ts, word_status_ajax.ts, ajax_utilities.ts |
| jQuery `$.getJSON()` | 6 | test_ajax.ts, text_display.ts, term_operations.ts |
| jQuery `$.get()` | 2 | youtube_import.ts, home_warnings.ts |
| `fetch()` API | 2 | app_data.ts |
| Frame navigation (`target="ro"`) | 15+ | overlib_interface.ts, text_keyboard.ts |
| `onclick` in HTML strings | 20+ | overlib_interface.ts |

### Legacy PHP Endpoints Called from JavaScript

| Endpoint | Purpose | Has REST Equivalent? |
|----------|---------|---------------------|
| `set_word_status.php` | Change word status (1-5, 98, 99) | Partial: `PUT /api/v1/terms/{id}/status/{status}` |
| `set_test_status.php` | Change status during testing | No |
| `delete_word.php` | Delete single word | No |
| `delete_mword.php` | Delete multi-word | No |
| `insert_word_wellknown.php` | Mark unknown as well-known (99) | No |
| `insert_word_ignore.php` | Mark unknown as ignored (98) | No |
| `show_word.php` | Display word details | Partial: `GET /api/v1/terms/{id}` |
| `edit_mword.php` | Edit multi-word expression | No |
| `edit_tword.php` | Edit term during test | No |
| `trans.php` | Translation popup | No |
| `inline_edit.php` | AJAX inline editing | No |
| `set_text_mode.php` | Toggle display settings | No |
| `all_words_wellknown.php` | Bulk mark words | No |
| `set_word_on_hover.php` | Quick word save | No |

### Existing REST API (api.php/v1/)

Located in `src/backend/Api/V1/`:

- `languages` - GET
- `media-files` - GET
- `phonetic-reading` - GET
- `review/next-word` - GET
- `review/tomorrow-count` - GET
- `sentences-with-term` - GET
- `similar-terms` - GET
- `settings` - POST
- `settings/theme-path` - GET
- `tags/term`, `tags/text` - GET
- `terms` - GET, POST (partial)
- `texts/statistics` - GET
- `feeds` - POST
- `version` - GET

---

## Migration Phases

### Phase 1: API Client Foundation

**Goal:** Create a centralized, type-safe API client to replace scattered jQuery calls.

**Duration estimate:** Foundation for all subsequent work

#### 1.1 Create API Client Module

Create `src/frontend/js/core/api_client.ts`:

```typescript
/**
 * Centralized API client for all backend communication.
 * Replaces jQuery AJAX with modern fetch API.
 */

export interface ApiResponse<T> {
  data?: T;
  error?: string;
}

export interface ApiClientConfig {
  baseUrl: string;
  defaultHeaders?: Record<string, string>;
}

const defaultConfig: ApiClientConfig = {
  baseUrl: '/api.php/v1',
  defaultHeaders: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
};

/**
 * Make a GET request to the API.
 */
export async function apiGet<T>(
  endpoint: string,
  params?: Record<string, string | number>
): Promise<ApiResponse<T>> {
  const url = new URL(defaultConfig.baseUrl + endpoint, window.location.origin);
  if (params) {
    Object.entries(params).forEach(([key, value]) => {
      url.searchParams.append(key, String(value));
    });
  }

  try {
    const response = await fetch(url.toString(), {
      method: 'GET',
      headers: defaultConfig.defaultHeaders
    });

    if (!response.ok) {
      return { error: `HTTP ${response.status}: ${response.statusText}` };
    }

    const data = await response.json();
    return { data };
  } catch (error) {
    return { error: String(error) };
  }
}

/**
 * Make a POST request to the API.
 */
export async function apiPost<T>(
  endpoint: string,
  body: Record<string, unknown>
): Promise<ApiResponse<T>> {
  try {
    const response = await fetch(defaultConfig.baseUrl + endpoint, {
      method: 'POST',
      headers: defaultConfig.defaultHeaders,
      body: JSON.stringify(body)
    });

    if (!response.ok) {
      return { error: `HTTP ${response.status}: ${response.statusText}` };
    }

    const data = await response.json();
    return { data };
  } catch (error) {
    return { error: String(error) };
  }
}

/**
 * Make a PUT request to the API.
 */
export async function apiPut<T>(
  endpoint: string,
  body: Record<string, unknown>
): Promise<ApiResponse<T>> {
  try {
    const response = await fetch(defaultConfig.baseUrl + endpoint, {
      method: 'PUT',
      headers: defaultConfig.defaultHeaders,
      body: JSON.stringify(body)
    });

    if (!response.ok) {
      return { error: `HTTP ${response.status}: ${response.statusText}` };
    }

    const data = await response.json();
    return { data };
  } catch (error) {
    return { error: String(error) };
  }
}

/**
 * Make a DELETE request to the API.
 */
export async function apiDelete<T>(endpoint: string): Promise<ApiResponse<T>> {
  try {
    const response = await fetch(defaultConfig.baseUrl + endpoint, {
      method: 'DELETE',
      headers: defaultConfig.defaultHeaders
    });

    if (!response.ok) {
      return { error: `HTTP ${response.status}: ${response.statusText}` };
    }

    const data = await response.json();
    return { data };
  } catch (error) {
    return { error: String(error) };
  }
}
```

#### 1.2 Create Domain-Specific API Modules

Create type-safe wrappers for each domain:

**`src/frontend/js/api/terms.ts`:**

```typescript
import { apiGet, apiPost, apiPut, apiDelete, ApiResponse } from '../core/api_client';

export interface Term {
  id: number;
  text: string;
  textLc: string;
  translation: string;
  romanization?: string;
  status: number;
  langId: number;
}

export interface TermStatusResponse {
  set?: number;
  error?: string;
}

export const TermsApi = {
  /**
   * Get term by ID
   */
  async get(termId: number): Promise<ApiResponse<Term>> {
    return apiGet<Term>(`/terms/${termId}`);
  },

  /**
   * Set term status
   */
  async setStatus(termId: number, status: number): Promise<ApiResponse<TermStatusResponse>> {
    return apiPost<TermStatusResponse>(`/terms/${termId}/status/${status}`, {});
  },

  /**
   * Increment or decrement term status
   */
  async incrementStatus(termId: number, direction: 'up' | 'down'): Promise<ApiResponse<TermStatusResponse>> {
    return apiPost<TermStatusResponse>(`/terms/${termId}/status/${direction}`, {});
  },

  /**
   * Delete a term
   */
  async delete(termId: number): Promise<ApiResponse<{ deleted: boolean }>> {
    return apiDelete(`/terms/${termId}`);
  },

  /**
   * Create a new term with status (wellknown/ignored)
   */
  async createQuick(textId: number, position: number, status: 98 | 99): Promise<ApiResponse<Term>> {
    return apiPost<Term>('/terms/quick', { textId, position, status });
  },

  /**
   * Update term translation
   */
  async updateTranslation(termId: number, translation: string): Promise<ApiResponse<{ update: string }>> {
    return apiPut(`/terms/${termId}/translations`, { translation });
  }
};
```

**Files to create:**

- `src/frontend/js/api/terms.ts` - Term/word operations
- `src/frontend/js/api/texts.ts` - Text operations
- `src/frontend/js/api/review.ts` - Test/review operations
- `src/frontend/js/api/settings.ts` - Settings operations

---

### Phase 2: Complete REST API Backend

**Goal:** Add missing REST API endpoints so all operations can use JSON API.

#### 2.1 New Endpoints Required

Add to `src/backend/Api/V1/Endpoints.php`:

```php
private const ROUTES = [
    // ... existing routes ...

    // New term endpoints
    'terms' => ['GET', 'POST', 'PUT', 'DELETE'],  // extend existing

    // New quick-action endpoints
    'terms/quick' => ['POST'],           // Quick create (wellknown/ignored)
    'terms/bulk-status' => ['PUT'],      // Bulk status update

    // Test mode endpoints
    'review/status' => ['PUT'],          // Update status during review

    // Text display endpoints
    'texts/display-mode' => ['PUT'],     // Toggle display settings
];
```

#### 2.2 Extend TermHandler

Add methods to `src/backend/Api/V1/Handlers/TermHandler.php`:

```php
/**
 * Delete a term by ID.
 *
 * @param int $termId Term ID to delete
 * @return array{deleted: bool, error?: string}
 */
public function deleteTerm(int $termId): array
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    $result = Connection::execute(
        "DELETE FROM {$tbpref}words WHERE WoID = $termId",
        ""
    );

    if ($result === 1) {
        return ['deleted' => true];
    }
    return ['deleted' => false, 'error' => 'Term not found'];
}

/**
 * Create a term quickly with wellknown (99) or ignored (98) status.
 *
 * @param int $textId   Text ID
 * @param int $position Word position in text
 * @param int $status   Status (98 or 99)
 * @return array{id?: int, error?: string}
 */
public function createQuickTerm(int $textId, int $position, int $status): array
{
    // Implementation mirrors insert_word_wellknown.php / insert_word_ignore.php
    // ...
}
```

#### 2.3 Create ReviewHandler Extensions

Add to `src/backend/Api/V1/Handlers/ReviewHandler.php`:

```php
/**
 * Update word status during review/test mode.
 *
 * @param int      $wordId   Word ID
 * @param int|null $status   Explicit status (1-5, 98, 99)
 * @param int|null $change   Status change amount (+1 or -1)
 * @return array Response with new status
 */
public function updateReviewStatus(int $wordId, ?int $status, ?int $change): array
{
    // Implementation mirrors set_test_status.php
    // ...
}
```

#### 2.4 Migration Mapping

| Legacy Endpoint | New REST Endpoint | Method |
|-----------------|-------------------|--------|
| `set_word_status.php` | `/terms/{id}/status/{status}` | PUT |
| `set_test_status.php` | `/review/status` | PUT |
| `delete_word.php` | `/terms/{id}` | DELETE |
| `delete_mword.php` | `/terms/{id}` | DELETE |
| `insert_word_wellknown.php` | `/terms/quick` | POST |
| `insert_word_ignore.php` | `/terms/quick` | POST |
| `inline_edit.php` | `/terms/{id}/translation` | PUT |
| `set_text_mode.php` | `/texts/{id}/display-mode` | PUT |
| `all_words_wellknown.php` | `/terms/bulk-status` | PUT |

---

### Phase 3: Migrate jQuery to Fetch

**Goal:** Replace all jQuery AJAX calls with the new API client.

#### 3.1 File-by-File Migration

**Priority 1 - Simple replacements:**

| File | Current | Migration |
|------|---------|-----------|
| `ajax_utilities.ts` | `$.post('api.php/v1/settings')` | `SettingsApi.save()` |
| `word_status_ajax.ts` | `$.post('api.php/v1/terms/{id}/status')` | `TermsApi.setStatus()` |
| `text_display.ts` | `$.getJSON('api.php/v1/texts/statistics')` | `TextsApi.getStatistics()` |

**Priority 2 - Moderate complexity:**

| File | Current | Migration |
|------|---------|-----------|
| `test_ajax.ts` | Multiple `$.getJSON` calls | `ReviewApi.*` methods |
| `term_operations.ts` | Mixed `$.post`/`$.getJSON` | `TermsApi.*` methods |
| `app_data.ts` | Already uses `fetch` | Migrate to `api_client.ts` for consistency |

**Priority 3 - Complex (requires Phase 4):**

| File | Current | Issue |
|------|---------|-------|
| `overlib_interface.ts` | `onclick` handlers with `target="ro"` | Requires frame removal |
| `text_keyboard.ts` | Frame-based navigation | Requires frame removal |

#### 3.2 Example Migration: ajax_utilities.ts

**Before:**

```typescript
import $ from 'jquery';

export function do_ajax_save_setting(k: string, v: string): void {
  $.post('api.php/v1/settings', { key: k, value: v });
}
```

**After:**

```typescript
import { SettingsApi } from '../api/settings';

export async function do_ajax_save_setting(k: string, v: string): Promise<void> {
  await SettingsApi.save(k, v);
}
```

#### 3.3 Example Migration: word_status_ajax.ts

**Before:**

```typescript
$.post(`api.php/v1/terms/${wid}/status/${status}`, {}, (data) => {
  // handle response
});
```

**After:**

```typescript
const response = await TermsApi.setStatus(wid, status);
if (response.error) {
  console.error(response.error);
  return;
}
// handle response.data
```

---

### Phase 4: Remove Frame Architecture

**Goal:** Replace iframe-based results with in-page DOM updates.

This is the most significant change, affecting the reading interface.

#### 4.1 Current Frame Architecture

The reading interface uses frames:

- Main frame: Text display (`do_text.php`)
- Right frame (`target="ro"`): Word details, status changes

When a user clicks a word:

1. `overlib_interface.ts` generates HTML with `onclick` handlers
2. Handlers navigate the right frame to PHP endpoints
3. PHP returns full HTML page
4. Frame displays the result

#### 4.2 Target Architecture

Replace frames with:

1. **Event delegation** on word elements
2. **API calls** returning JSON
3. **DOM updates** to show results in-place or in a panel

#### 4.3 Migration Steps

**Step 1: Create Word Actions Module**

```typescript
// src/frontend/js/reading/word_actions.ts

import { TermsApi } from '../api/terms';

export interface WordActionContext {
  textId: number;
  wordId?: number;
  position: number;
  text: string;
}

/**
 * Handle word status change via API instead of frame navigation.
 */
export async function changeWordStatus(
  context: WordActionContext,
  newStatus: number
): Promise<void> {
  if (!context.wordId) {
    console.error('No word ID for status change');
    return;
  }

  const response = await TermsApi.setStatus(context.wordId, newStatus);
  if (response.error) {
    showError(response.error);
    return;
  }

  // Update DOM to reflect new status
  updateWordDisplay(context.position, newStatus);
  updateResultPanel(response.data);
}

/**
 * Mark unknown word as well-known.
 */
export async function markWellKnown(context: WordActionContext): Promise<void> {
  const response = await TermsApi.createQuick(context.textId, context.position, 99);
  if (response.error) {
    showError(response.error);
    return;
  }

  updateWordDisplay(context.position, 99);
}

/**
 * Update word element in the text to reflect new status.
 */
function updateWordDisplay(position: number, status: number): void {
  const wordEl = document.querySelector(`[data-pos="${position}"]`);
  if (wordEl) {
    wordEl.className = `word status-${status}`;
  }
}
```

**Step 2: Replace onclick Handlers with Event Delegation**

```typescript
// src/frontend/js/reading/text_events.ts

import { WordActionContext, changeWordStatus, markWellKnown } from './word_actions';

/**
 * Initialize event delegation for word interactions.
 */
export function initWordEvents(textContainer: HTMLElement): void {
  textContainer.addEventListener('click', handleWordClick);
}

function handleWordClick(event: Event): void {
  const target = event.target as HTMLElement;
  const wordEl = target.closest('[data-word-id]');

  if (!wordEl) return;

  const context: WordActionContext = {
    textId: parseInt(wordEl.dataset.textId || '0'),
    wordId: parseInt(wordEl.dataset.wordId || '0') || undefined,
    position: parseInt(wordEl.dataset.pos || '0'),
    text: wordEl.textContent || ''
  };

  // Show popup with actions bound to API calls
  showWordPopup(wordEl, context);
}
```

**Step 3: Refactor overlib_interface.ts**

Instead of generating HTML strings with `onclick` handlers:

```typescript
// Before
export function make_overlib_link_change_status(...): string {
  return ' <a href="set_word_status.php?..." target="ro" onclick="showRightFrames();">...</a>';
}

// After
export function createStatusChangeButton(
  context: WordActionContext,
  newStatus: number
): HTMLButtonElement {
  const btn = document.createElement('button');
  btn.className = 'status-btn';
  btn.textContent = getStatusAbbr(newStatus);
  btn.addEventListener('click', () => changeWordStatus(context, newStatus));
  return btn;
}
```

#### 4.4 Result Panel Component

Create a panel component to replace frame content:

```typescript
// src/frontend/js/components/result_panel.ts

export class ResultPanel {
  private container: HTMLElement;

  constructor(containerId: string) {
    this.container = document.getElementById(containerId) || this.createContainer();
  }

  private createContainer(): HTMLElement {
    const panel = document.createElement('div');
    panel.id = 'result-panel';
    panel.className = 'result-panel';
    document.body.appendChild(panel);
    return panel;
  }

  show(content: string | HTMLElement): void {
    this.container.innerHTML = '';
    if (typeof content === 'string') {
      this.container.innerHTML = content;
    } else {
      this.container.appendChild(content);
    }
    this.container.classList.add('visible');
  }

  hide(): void {
    this.container.classList.remove('visible');
  }
}
```

---

### Phase 5: Testing and Validation

#### 5.1 Unit Tests ✓ DONE

API client tests exist at `tests/frontend/core/api_client.test.ts` covering:
- GET/POST/PUT/DELETE requests
- Error handling
- Parameter serialization
- Form data posting

#### 5.2 Integration Tests ✓ DONE

E2E tests for reading interface exist at `cypress/e2e/09-reading.cy.ts` covering:
- Reading page load
- Word display with status classes
- Multi-word selection
- Modal interactions

#### 5.3 Legacy File Cleanup ✓ DONE

All legacy PHP endpoint files have been removed. No deprecation warnings needed as:
- All functionality migrated to controllers and REST API
- No legacy `.php` files remain in the codebase
- Frame-based operations replaced with API mode

---

## Implementation Order

```
Phase 1.1  Create api_client.ts                    [Foundation] ✓ DONE
Phase 1.2  Create domain API modules               [Foundation] ✓ DONE
    │
    ├── Phase 2.1  Add missing REST endpoints      [Backend] ✓ DONE
    │   Phase 2.2  Extend TermHandler              [Backend] ✓ DONE
    │   Phase 2.3  Extend ReviewHandler            [Backend] ✓ DONE
    │
    └── Phase 3.1  Migrate ajax_utilities.ts       [Quick win] ✓ DONE
        Phase 3.2  Migrate word_status_ajax.ts     [Quick win] ✓ DONE
        Phase 3.3  Migrate text_display.ts         [Quick win] ✓ DONE
        Phase 3.4  Migrate test_ajax.ts            [Moderate] ✓ DONE
        Phase 3.5  Migrate term_operations.ts      [Moderate] ✓ DONE
            │
            └── Phase 4.1  Create word_actions.ts  [Major] ✓ DONE
                Phase 4.2  Result panel            [Major] ✓ DONE
                Phase 4.3  Refactor overlib        [Major] ✓ DONE
                Phase 4.4  Event delegation        [Major] ✓ DONE
                Phase 4.5  API mode as default     [Major] ✓ DONE
                    │
                    └── Phase 5  Testing           [Validation] ✓ DONE
```

**Status: Migration Complete** - All phases have been implemented. The modernization from jQuery/frame-based communication to fetch API/in-page updates is complete.

---

## API Mode Configuration

Since v3.0.0, the frontend uses **API mode by default** for word operations. This provides:
- Faster status changes (no iframe reload)
- Better mobile experience
- Reduced server load
- In-page result panels instead of frame navigation

### Opt-out to Legacy Frame Mode

If you need to use the legacy frame-based mode for backward compatibility:

**JavaScript:**
```javascript
// Disable API mode globally
window.setUseApiMode(false);

// Or set in LWT_DATA before page initialization
LWT_DATA.settings.use_frame_mode = true;
```

**PHP (in template):**
```php
<script>
LWT_DATA.settings.use_frame_mode = true;
</script>
```

### Frame Mode Controller Methods

The following controller methods exist to support legacy frame mode. They render HTML for iframe display and are kept for backward compatibility:

| Controller Method | REST API Alternative |
|-------------------|---------------------|
| `VocabularyController::delete()` | `DELETE /api/v1/terms/{id}` |
| `VocabularyController::deleteMulti()` | `DELETE /api/v1/terms/{id}` |
| `VocabularyController::insertWellknown()` | `POST /api/v1/terms/quick` (status=99) |
| `VocabularyController::insertIgnore()` | `POST /api/v1/terms/quick` (status=98) |
| `VocabularyController::setStatus()` | `PUT /api/v1/terms/{id}/status/{status}` |
| `TestController::setStatus()` | `PUT /api/v1/review/status` |

These methods are still used when `use_frame_mode = true` is set.

---

## Benefits After Migration

| Aspect | Before | After |
|--------|--------|-------|
| **Bundle size** | jQuery dependency (~87KB) | Native fetch (0KB) |
| **Type safety** | None | Full TypeScript types |
| **Error handling** | Inconsistent | Centralized |
| **Testability** | Difficult (DOM + frames) | Easy (mock API) |
| **Performance** | Frame reloads | Partial DOM updates |
| **Maintainability** | Scattered PHP endpoints | Unified REST API |
| **Mobile UX** | Poor (frames) | Modern (SPA-like) |

---

## Risks and Mitigations (Resolved)

| Risk | Resolution |
|------|------------|
| Breaking existing functionality | Mitigated via `use_frame_mode` toggle for backward compatibility |
| jQuery removal affects other code | jQuery still available for legacy code; new code uses fetch API |
| Frame removal affects layout | Result panels and modals replace frame content |
| Backward compatibility | Frame mode controller methods retained alongside REST API |

---

## Files Summary (Current Paths)

### API Layer

- `src/frontend/js/shared/api/client.ts` - Centralized API client ✓
- `src/frontend/js/modules/vocabulary/api/terms_api.ts` - Term/word operations ✓
- `src/frontend/js/modules/vocabulary/api/words_api.ts` - Word-specific operations ✓
- `src/frontend/js/modules/text/api/texts_api.ts` - Text operations ✓
- `src/frontend/js/modules/review/api/review_api.ts` - Review/test operations ✓
- `src/frontend/js/modules/admin/api/settings_api.ts` - Settings operations ✓
- `src/frontend/js/modules/language/api/languages_api.ts` - Language operations ✓
- `src/frontend/js/modules/feed/api/feeds_api.ts` - Feed operations ✓

### Service Layer

- `src/frontend/js/modules/vocabulary/services/word_actions.ts` - Word action handlers ✓
- `src/frontend/js/modules/vocabulary/services/word_status_ajax.ts` - Status change logic ✓
- `src/frontend/js/modules/vocabulary/services/term_operations.ts` - Term CRUD operations ✓
- `src/frontend/js/modules/vocabulary/services/overlib_interface.ts` - Popup interface ✓

### UI Components

- `src/frontend/js/modules/vocabulary/components/result_panel.ts` - Result display panel ✓

### Page Modules

- `src/frontend/js/modules/text/pages/reading/text_display.ts` - Text display logic ✓
- `src/frontend/js/modules/text/pages/reading/text_events.ts` - Event handling ✓
- `src/frontend/js/modules/text/pages/reading/text_keyboard.ts` - Keyboard shortcuts (partial)
- `src/frontend/js/modules/review/pages/test_ajax.ts` - Test mode AJAX ✓

### Shared Utilities

- `src/frontend/js/shared/utils/ajax_utilities.ts` - AJAX helpers ✓

### Backend

- `src/backend/Api/V1/Endpoints.php` - REST API routes ✓
- `src/backend/Api/V1/Handlers/TermHandler.php` - Term endpoint handlers ✓
- `src/backend/Api/V1/Handlers/ReviewHandler.php` - Review endpoint handlers ✓

### All Files Complete

All frontend files have been migrated from frame-based to direct module imports:

- `src/frontend/js/modules/text/pages/reading/text_keyboard.ts` - Migrated to direct `lwt_audio_controller` import ✓
- `src/frontend/js/modules/text/pages/reading/text_events.ts` - Migrated to direct `lwt_audio_controller` import ✓

### Legacy PHP Files (Removed)

All legacy PHP endpoint files have been removed. The functionality has been migrated to:

| Former File | Replacement |
|-------------|-------------|
| `set_word_status.php` | `VocabularyController::setStatus()` + `PUT /api/v1/terms/{id}/status/{status}` |
| `set_test_status.php` | `ReviewController` + `PUT /api/v1/review/status` |
| `delete_word.php` | `VocabularyController::delete()` + `DELETE /api/v1/terms/{id}` |
| `delete_mword.php` | `VocabularyController::deleteMulti()` + `DELETE /api/v1/terms/{id}` |
| `insert_word_wellknown.php` | `VocabularyController::insertWellknown()` + `POST /api/v1/terms/quick` |
| `insert_word_ignore.php` | `VocabularyController::insertIgnore()` + `POST /api/v1/terms/quick` |
| `inline_edit.php` | `VocabularyController::inlineEdit()` at `/word/inline-edit` |
| `set_text_mode.php` | `TextController` + `PUT /api/v1/texts/{id}/display-mode` |
| `trans.php` | Direct dictionary URLs (no proxy needed) |
| `set_word_on_hover.php` | API mode hover handlers |
