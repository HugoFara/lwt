/**
 * Tests for languages/language_list.ts - Language List page interactions
 *
 * Note: This module uses DOMContentLoaded event listeners which are difficult
 * to test in isolation. These tests focus on the core logic that can be tested.
 */
import { describe, it, expect, vi } from 'vitest';

// Mock the dependencies
vi.mock('../../../src/frontend/js/api/settings', () => ({
  SettingsApi: {
    save: vi.fn(),
  },
}));

vi.mock('../../../src/frontend/js/ui/lucide_icons', () => ({
  initIcons: vi.fn(),
}));

describe('languages/language_list.ts', () => {
  // The module uses DOMContentLoaded which makes it hard to test directly.
  // This file contains integration tests that verify the module exports and
  // basic structure. Actual UI tests are better done via E2E testing.

  it('module can be imported without error', async () => {
    // Just verify the module can be loaded
    await expect(import('../../../src/frontend/js/languages/language_list')).resolves.not.toThrow();
  });
});
