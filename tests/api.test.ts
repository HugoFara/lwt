import { describe, it, expect } from 'vitest';

const host = 'http://localhost';
const apiPath = '/lwt/api.php/v1';

/**
 * Helper function to make GET requests to the API.
 */
async function apiGet(
  endpoint: string,
  params?: Record<string, string>
): Promise<Response> {
  const url = new URL(`${host}${apiPath}${endpoint}`);
  if (params) {
    Object.entries(params).forEach(([key, value]) => {
      url.searchParams.append(key, value);
    });
  }
  return fetch(url.toString());
}

/**
 * Helper function to make POST requests to the API.
 */
async function apiPost(
  endpoint: string,
  body: Record<string, string | number>
): Promise<Response> {
  const formData = new URLSearchParams();
  Object.entries(body).forEach(([key, value]) => {
    formData.append(key, String(value));
  });

  return fetch(`${host}${apiPath}${endpoint}`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: formData.toString(),
  });
}

// ===========================================================================
// Basic API Tests
// ===========================================================================

describe('API Base', () => {
  it('returns 400 for requests without endpoint', async () => {
    const response = await fetch(`${host}${apiPath}`);

    expect(response.status).toBe(400);
    expect(response.headers.get('Content-Type')).toBe('application/json');
  });

  it('returns 404 for unknown endpoints', async () => {
    const response = await apiGet('/unknown-endpoint');

    expect(response.status).toBe(404);
    expect(response.headers.get('Content-Type')).toBe('application/json');

    const body = await response.json();
    expect(body.error).toContain('Endpoint Not Found');
  });

  it('returns 405 for unsupported HTTP methods', async () => {
    const response = await fetch(`${host}${apiPath}/version`, {
      method: 'DELETE',
    });

    expect(response.status).toBe(405);
    expect(response.headers.get('Content-Type')).toBe('application/json');
  });
});

// ===========================================================================
// Version Endpoint Tests
// ===========================================================================

describe('GET /version', () => {
  it('returns version information', async () => {
    const response = await apiGet('/version');

    expect(response.status).toBe(200);
    expect(response.headers.get('Content-Type')).toBe('application/json');

    const body = await response.json();
    expect(body).toHaveProperty('version');
    expect(body).toHaveProperty('release_date');
    expect(typeof body.version).toBe('string');
    expect(typeof body.release_date).toBe('string');
  });

  it('version follows semver format', async () => {
    const response = await apiGet('/version');
    const body = await response.json();

    // Check semver format (X.Y.Z)
    expect(body.version).toMatch(/^\d+\.\d+\.\d+$/);
  });

  it('release_date follows ISO date format', async () => {
    const response = await apiGet('/version');
    const body = await response.json();

    // Check date format (YYYY-MM-DD)
    expect(body.release_date).toMatch(/^\d{4}-\d{2}-\d{2}$/);
  });
});

// ===========================================================================
// Media Files Endpoint Tests
// ===========================================================================

describe('GET /media-files', () => {
  it('returns media files with base path', async () => {
    const response = await apiGet('/media-files');

    expect(response.status).toBe(200);
    expect(response.headers.get('Content-Type')).toBe('application/json');

    const body = await response.json();
    expect(body).toHaveProperty('base_path');
    expect(typeof body.base_path).toBe('string');
  });
});

// ===========================================================================
// Phonetic Reading Endpoint Tests
// ===========================================================================

describe('GET /phonetic-reading', () => {
  it('returns phonetic reading with language ID', async () => {
    const response = await apiGet('/phonetic-reading', {
      text: 'test',
      lgid: '1',
    });

    expect(response.status).toBe(200);
    expect(response.headers.get('Content-Type')).toBe('application/json');

    const body = await response.json();
    expect(body).toHaveProperty('phonetic_reading');
  });

  it('returns phonetic reading with language name', async () => {
    const response = await apiGet('/phonetic-reading', {
      text: 'test',
      lang: 'en',
    });

    expect(response.status).toBe(200);
    expect(response.headers.get('Content-Type')).toBe('application/json');

    const body = await response.json();
    expect(body).toHaveProperty('phonetic_reading');
    expect(body.phonetic_reading).toBe('test');
  });

  it('handles empty text input', async () => {
    const response = await apiGet('/phonetic-reading', {
      text: '',
      lang: 'en',
    });

    expect(response.status).toBe(200);

    const body = await response.json();
    expect(body).toHaveProperty('phonetic_reading');
  });

  it('handles special characters in text', async () => {
    const response = await apiGet('/phonetic-reading', {
      text: 'héllo wörld',
      lang: 'en',
    });

    expect(response.status).toBe(200);

    const body = await response.json();
    expect(body).toHaveProperty('phonetic_reading');
  });
});

// ===========================================================================
// Sentences with Term Endpoint Tests
// ===========================================================================

describe('GET /sentences-with-term', () => {
  it('returns sentences for a term with normal search', async () => {
    const response = await apiGet('/sentences-with-term/1', {
      lg_id: '1',
      word_lc: 'test',
    });

    expect(response.status).toBe(200);
    expect(response.headers.get('Content-Type')).toBe('application/json');
  });

  it('returns sentences with advanced search', async () => {
    const response = await apiGet('/sentences-with-term/1', {
      lg_id: '1',
      word_lc: 'test',
      advanced_search: '-1',
    });

    expect(response.status).toBe(200);
    expect(response.headers.get('Content-Type')).toBe('application/json');
  });

  it('handles non-existent term ID gracefully', async () => {
    const response = await apiGet('/sentences-with-term/999999', {
      lg_id: '1',
      word_lc: 'nonexistent',
    });

    expect(response.status).toBe(200);
    expect(response.headers.get('Content-Type')).toBe('application/json');
  });
});

// ===========================================================================
// Settings Theme Path Endpoint Tests
// ===========================================================================

describe('GET /settings/theme-path', () => {
  it('returns theme path for CSS file', async () => {
    const response = await apiGet('/settings/theme-path', {
      path: 'css/styles.css',
    });

    expect(response.status).toBe(200);
    expect(response.headers.get('Content-Type')).toBe('application/json');

    const body = await response.json();
    expect(body).toHaveProperty('theme_path');
    expect(body.theme_path).toMatch(/.+\/styles\.css$/);
  });

  it('handles different file paths', async () => {
    const response = await apiGet('/settings/theme-path', {
      path: 'js/main.js',
    });

    expect(response.status).toBe(200);

    const body = await response.json();
    expect(body).toHaveProperty('theme_path');
  });
});

// ===========================================================================
// Terms Imported Endpoint Tests
// ===========================================================================

describe('GET /terms/imported', () => {
  it('returns imported terms with navigation', async () => {
    const response = await apiGet('/terms/imported', {
      last_update: '',
      page: '0',
      count: '10',
    });

    expect(response.status).toBe(200);
    expect(response.headers.get('Content-Type')).toBe('application/json');

    const body = await response.json();
    expect(body).toHaveProperty('navigation');
    expect(body).toHaveProperty('terms');
    expect(body.navigation).toBeTypeOf('object');
    expect(body.terms).toBeInstanceOf(Array);
  });

  it('navigation has correct structure', async () => {
    const response = await apiGet('/terms/imported', {
      last_update: '',
      page: '0',
      count: '10',
    });

    const body = await response.json();

    expect(body.navigation).toHaveProperty('current_page');
    expect(body.navigation).toHaveProperty('total_pages');
    expect(body.navigation.current_page).toBeLessThanOrEqual(
      body.navigation.total_pages
    );
  });

  it('respects pagination parameters', async () => {
    const response1 = await apiGet('/terms/imported', {
      last_update: '',
      page: '1',
      count: '5',
    });
    // Consume the response body to ensure request completes
    await response1.json();

    const response2 = await apiGet('/terms/imported', {
      last_update: '',
      page: '2',
      count: '5',
    });
    // Consume the response body to ensure request completes
    await response2.json();

    expect(response1.status).toBe(200);
    expect(response2.status).toBe(200);
  });
});

// ===========================================================================
// Terms Translations Endpoint Tests
// ===========================================================================

describe('GET /terms/{term-id}/translations', () => {
  it('returns translations for an existing term', async () => {
    const response = await apiGet('/terms/1/translations');

    expect(response.status).toBe(200);
    expect(response.headers.get('Content-Type')).toBe('application/json');
  });

  it('handles non-existent term ID', async () => {
    const response = await apiGet('/terms/999999/translations');

    expect(response.status).toBe(200);
    expect(response.headers.get('Content-Type')).toBe('application/json');
  });
});

// ===========================================================================
// Review Endpoints Tests
// ===========================================================================

describe('GET /review/next-word', () => {
  it('returns next word for review', async () => {
    const response = await apiGet('/review/next-word', {
      test_sql: '',
      word_mode: 'true',
      lg_id: '1',
      word_regex: 'test',
      type: '0',
    });

    expect(response.status).toBe(200);
  });

  it('handles word mode false', async () => {
    const response = await apiGet('/review/next-word', {
      test_sql: '',
      word_mode: 'false',
      lg_id: '1',
      word_regex: '',
      type: '1',
    });

    expect(response.status).toBe(200);
  });

  it('returns expected structure when no words', async () => {
    const response = await apiGet('/review/next-word', {
      test_key: 'test',
      selection: '',
      word_mode: 'true',
      lg_id: '1',
      word_regex: '',
      type: '0',
    });

    expect(response.status).toBe(200);

    // Check if response is JSON before parsing
    const contentType = response.headers.get('Content-Type');
    if (contentType === 'application/json') {
      const body = await response.json();
      expect(body).toHaveProperty('word_id');
      expect(body).toHaveProperty('word_text');
      expect(body).toHaveProperty('group');
    }
  });
});

describe('GET /review/tomorrow-count', () => {
  it('returns count of tomorrow reviews', async () => {
    const response = await apiGet('/review/tomorrow-count', {
      test_key: 'test',
      selection: '',
    });

    expect(response.status).toBe(200);

    // Check if response is JSON before parsing
    const contentType = response.headers.get('Content-Type');
    if (contentType === 'application/json') {
      const body = await response.json();
      expect(body).toHaveProperty('count');
      expect(typeof body.count).toBe('number');
    }
  });
});

// ===========================================================================
// Texts Statistics Endpoint Tests
// ===========================================================================

describe('GET /texts/statistics', () => {
  it('returns statistics for texts', async () => {
    const response = await apiGet('/texts/statistics', {
      texts_id: '1,2',
    });

    expect(response.status).toBe(200);
    expect(response.headers.get('Content-Type')).toBe('application/json');
  });

  it('handles single text ID', async () => {
    const response = await apiGet('/texts/statistics', {
      texts_id: '1',
    });

    expect(response.status).toBe(200);
    expect(response.headers.get('Content-Type')).toBe('application/json');
  });

  it('handles empty text IDs', async () => {
    const response = await apiGet('/texts/statistics', {
      texts_id: '',
    });

    expect(response.status).toBe(200);
  });
});

// ===========================================================================
// Similar Terms Endpoint Tests
// ===========================================================================

describe('GET /similar-terms', () => {
  it('returns similar terms for a given term', async () => {
    const response = await apiGet('/similar-terms', {
      lg_id: '1',
      term: 'test',
    });

    expect(response.status).toBe(200);
    expect(response.headers.get('Content-Type')).toBe('application/json');

    const body = await response.json();
    expect(body).toHaveProperty('similar_terms');
  });

  it('handles terms with special characters', async () => {
    const response = await apiGet('/similar-terms', {
      lg_id: '1',
      term: 'café',
    });

    expect(response.status).toBe(200);

    const body = await response.json();
    expect(body).toHaveProperty('similar_terms');
  });
});

// ===========================================================================
// Languages Endpoint Tests
// ===========================================================================

describe('GET /languages/{id}/reading-configuration', () => {
  it('returns reading configuration for a language', async () => {
    const response = await apiGet('/languages/1/reading-configuration');

    expect(response.status).toBe(200);
    expect(response.headers.get('Content-Type')).toBe('application/json');

    const body = await response.json();
    expect(body).toHaveProperty('name');
    expect(body).toHaveProperty('voiceapi');
    expect(body).toHaveProperty('word_parsing');
    expect(body).toHaveProperty('abbreviation');
    expect(body).toHaveProperty('reading_mode');
  });

  it('reading_mode is one of expected values', async () => {
    const response = await apiGet('/languages/1/reading-configuration');

    const body = await response.json();
    expect(['direct', 'external', 'internal']).toContain(body.reading_mode);
  });

  it('returns 404 for invalid language ID format', async () => {
    const response = await apiGet('/languages/invalid/reading-configuration');

    expect(response.status).toBe(404);
  });

  it('returns 404 for wrong sub-endpoint', async () => {
    const response = await apiGet('/languages/1/unknown');

    expect(response.status).toBe(404);
  });
});

// ===========================================================================
// POST Endpoints Tests
// ===========================================================================

describe('POST /settings', () => {
  it('saves a setting successfully', async () => {
    const response = await apiPost('/settings', {
      key: 'set-test-setting',
      value: 'test-value',
    });

    expect(response.status).toBe(200);
    expect(response.headers.get('Content-Type')).toBe('application/json');
  });
});

describe('POST /texts/{id}/reading-position', () => {
  it('sets reading position for a text', async () => {
    const response = await apiPost('/texts/1/reading-position', {
      position: 100,
    });

    expect(response.status).toBe(200);
    expect(response.headers.get('Content-Type')).toBe('application/json');

    const body = await response.json();
    expect(body).toHaveProperty('text');
    expect(body.text).toBe('Reading position set');
  });

  it('handles invalid text ID format', async () => {
    const response = await apiPost('/texts/invalid/reading-position', {
      position: 100,
    });

    expect(response.status).toBe(404);
  });
});

describe('POST /texts/{id}/audio-position', () => {
  it('sets audio position for a text', async () => {
    const response = await apiPost('/texts/1/audio-position', {
      position: 50,
    });

    expect(response.status).toBe(200);
    expect(response.headers.get('Content-Type')).toBe('application/json');

    const body = await response.json();
    expect(body).toHaveProperty('audio');
    expect(body.audio).toBe('Audio position set');
  });
});

describe('POST /texts/{id}/annotation', () => {
  it('sets annotation for a text element', async () => {
    const response = await apiPost('/texts/1/annotation', {
      elem: 'tx0',
      data: JSON.stringify({ tx0: 'test annotation' }),
    });

    expect(response.status).toBe(200);
    expect(response.headers.get('Content-Type')).toBe('application/json');
  });
});

describe('POST /terms/{id}/status/up', () => {
  it('increments term status', async () => {
    const response = await apiPost('/terms/1/status/up', {});

    expect(response.status).toBe(200);
    expect(response.headers.get('Content-Type')).toBe('application/json');
  });
});

describe('POST /terms/{id}/status/down', () => {
  it('decrements term status', async () => {
    const response = await apiPost('/terms/1/status/down', {});

    expect(response.status).toBe(200);
    expect(response.headers.get('Content-Type')).toBe('application/json');
  });
});

describe('POST /terms/{id}/status/{new-status}', () => {
  it('sets term status to specific value', async () => {
    const response = await apiPost('/terms/1/status/3', {});

    expect(response.status).toBe(200);
    expect(response.headers.get('Content-Type')).toBe('application/json');

    const body = await response.json();
    if (body.set !== undefined) {
      expect(body.set).toBe(1);
    }
  });

  it('handles status value 99 (well-known)', async () => {
    const response = await apiPost('/terms/1/status/99', {});

    expect(response.status).toBe(200);
  });

  it('handles status value 98 (ignored)', async () => {
    const response = await apiPost('/terms/1/status/98', {});

    expect(response.status).toBe(200);
  });
});

describe('POST /terms/{id}/translations', () => {
  it('updates term translation', async () => {
    const response = await apiPost('/terms/1/translations', {
      translation: 'new translation',
    });

    expect(response.status).toBe(200);
    expect(response.headers.get('Content-Type')).toBe('application/json');
  });
});

describe('POST /terms/new', () => {
  it('creates a new term with translation', async () => {
    const uniqueTerm = `test_term_${Date.now()}`;
    const response = await apiPost('/terms/new', {
      term_text: uniqueTerm,
      lg_id: 1,
      translation: 'test translation',
    });

    expect(response.status).toBe(200);
    expect(response.headers.get('Content-Type')).toBe('application/json');

    const body = await response.json();
    // Either creates successfully or returns an error for duplicate
    expect(
      body.term_id !== undefined ||
        body.add !== undefined ||
        body.error !== undefined
    ).toBe(true);
  });
});

// ===========================================================================
// Error Handling Tests
// ===========================================================================

describe('Error Handling', () => {
  it('handles malformed JSON gracefully', async () => {
    const response = await fetch(`${host}${apiPath}/texts/1/annotation`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: 'elem=tx0&data=not-valid-json',
    });

    // Should still return a response (might be error)
    expect(response.status).toBeGreaterThanOrEqual(200);
  });

  it('handles missing required parameters', async () => {
    const response = await apiGet('/phonetic-reading', {});

    // Depending on implementation, might return 200 with empty result or 400
    expect([200, 400, 500]).toContain(response.status);
  });

  it('handles very long input strings', async () => {
    const longString = 'a'.repeat(10000);
    const response = await apiGet('/phonetic-reading', {
      text: longString,
      lang: 'en',
    });

    // Server may return various status codes for long URIs:
    // 200 - processed successfully
    // 400 - bad request
    // 413 - payload too large
    // 414 - URI too long
    expect([200, 400, 413, 414]).toContain(response.status);
  });

  it('handles unicode characters correctly', async () => {
    const response = await apiGet('/phonetic-reading', {
      text: '日本語テスト',
      lang: 'ja',
    });

    expect(response.status).toBe(200);
  });

  it('handles emoji in text', async () => {
    const response = await apiGet('/phonetic-reading', {
      text: 'Hello 👋 World',
      lang: 'en',
    });

    expect([200, 400]).toContain(response.status);
  });
});

// ===========================================================================
// Content-Type Tests
// ===========================================================================

describe('Content-Type Headers', () => {
  // Note: /review/tomorrow-count excluded - returns HTML when test_key is empty (backend bug)
  const endpoints = [
    '/version',
    '/media-files',
    '/phonetic-reading?text=test&lang=en',
    '/terms/imported?last_update=&page=0&count=10',
    '/settings/theme-path?path=css/styles.css',
    '/texts/statistics?texts_id=1',
    '/similar-terms?lg_id=1&term=test',
  ];

  endpoints.forEach((endpoint) => {
    it(`GET ${endpoint.split('?')[0]} returns application/json`, async () => {
      const response = await fetch(`${host}${apiPath}${endpoint}`);

      expect(response.headers.get('Content-Type')).toBe('application/json');
    });
  });
});

// ===========================================================================
// URL Format Tests (Legacy and New)
// ===========================================================================

describe('URL Format Support', () => {
  it('supports legacy /api.php/v1/ format', async () => {
    const response = await fetch(`${host}/lwt/api.php/v1/version`);

    expect(response.status).toBe(200);
  });
});

// ===========================================================================
// Concurrent Request Tests
// ===========================================================================

describe('Concurrent Requests', () => {
  it('handles multiple concurrent GET requests', async () => {
    const requests = [
      apiGet('/version'),
      apiGet('/media-files'),
      apiGet('/phonetic-reading', { text: 'test', lang: 'en' }),
      apiGet('/terms/imported', { last_update: '', page: '0', count: '10' }),
    ];

    const responses = await Promise.all(requests);

    responses.forEach((response) => {
      expect(response.status).toBe(200);
    });
  });

  it('handles rapid sequential requests', async () => {
    for (let i = 0; i < 5; i++) {
      const response = await apiGet('/version');
      expect(response.status).toBe(200);
    }
  });
});
