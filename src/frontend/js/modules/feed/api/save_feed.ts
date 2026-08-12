/**
 * Feed Save - Send a feed form's fields to /api/v1/feeds.
 *
 * The three feed forms outside the manager SPA (the wizard's last step, the
 * manual "add a feed" form and the edit form) each posted themselves to a
 * server route. They save through the API now, so they work against a
 * configurable API base URL rather than the page origin (#262).
 *
 * The API path is also the only one that checks the submitted NfLgID belongs
 * to the caller — FeedEditController passed it straight to the facade.
 *
 * @license unlicense
 * @since   3.4.2
 */

import { createFeed, updateFeed, type FeedData } from './feeds_api';

/**
 * How a feed save ended up.
 */
export interface FeedSaveOutcome {
  feedId: number | null;
  error: string;
}

/**
 * Create or update a feed.
 *
 * @param data   Feed fields
 * @param feedId Feed ID to update, or null to create
 * @returns The saved feed's id, or a message to show
 */
export async function saveFeed(
  data: FeedData,
  feedId: number | null
): Promise<FeedSaveOutcome> {
  if (!data.langId) {
    return { feedId: null, error: 'Please choose a language.' };
  }
  if (!data.name.trim()) {
    return { feedId: null, error: 'Please give the feed a name.' };
  }
  if (!data.sourceUri.trim()) {
    return { feedId: null, error: 'Please give the feed a URL.' };
  }

  const response =
    feedId !== null ? await updateFeed(feedId, data) : await createFeed(data);

  const payload = response.data;
  if (response.error || !payload || payload.success !== true) {
    return {
      feedId: null,
      error: response.error || payload?.error || 'Could not save the feed.'
    };
  }

  return { feedId: payload.feed?.id ?? feedId, error: '' };
}

/**
 * Read a feed form's fields into an API payload.
 *
 * The forms keep their NfName / NfSourceURI field names, so the tag and
 * option helpers that write into inputs by name are unaffected.
 *
 * @param form    The feed form
 * @param options Serialized options string, built by the form's component
 * @returns The payload the feeds API expects
 */
export function readFeedForm(form: HTMLFormElement, options: string): FeedData {
  const value = (name: string): string => {
    const el = form.querySelector<HTMLInputElement | HTMLSelectElement>(`[name="${name}"]`);
    return el?.value ?? '';
  };

  return {
    langId: Number(value('NfLgID')) || 0,
    name: value('NfName'),
    sourceUri: value('NfSourceURI'),
    articleSectionTags: value('NfArticleSectionTags'),
    filterTags: value('NfFilterTags'),
    options
  };
}
