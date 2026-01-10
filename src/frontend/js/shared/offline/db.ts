/**
 * Offline Database Schema for LWT PWA.
 *
 * Uses Dexie.js for IndexedDB management. Stores texts, words,
 * and language data for offline reading support.
 *
 * @author  HugoFara <Hugo.Farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import Dexie, { type Table } from 'dexie';
import type { TextReadingConfig, TextWord, DictLinks } from '@modules/text/api/texts_api';

// =============================================================================
// DATABASE SCHEMA TYPES
// =============================================================================

/**
 * Offline text record - stores text content for offline reading.
 */
export interface OfflineText {
  /** Text ID (primary key) */
  id: number;
  /** Language ID for grouping */
  langId: number;
  /** Text title */
  title: string;
  /** Audio URI if available */
  audioUri: string | null;
  /** Source URI */
  sourceUri: string | null;
  /** Reading configuration */
  config: TextReadingConfig;
  /** When this text was downloaded */
  downloadedAt: Date;
  /** Last time this text was accessed offline */
  lastAccessedAt: Date;
  /** Size in bytes (approximate) */
  sizeBytes: number;
}

/**
 * Offline words for a text - stored separately for efficient querying.
 */
export interface OfflineTextWords {
  /** Composite key: textId */
  textId: number;
  /** All words for this text */
  words: TextWord[];
  /** When this was last synced */
  syncedAt: Date;
}

/**
 * Offline language configuration.
 */
export interface OfflineLanguage {
  /** Language ID (primary key) */
  id: number;
  /** Language name */
  name: string;
  /** Two-letter code */
  abbreviation: string;
  /** Right-to-left script */
  rightToLeft: boolean;
  /** Remove spaces between words */
  removeSpaces: boolean;
  /** Dictionary links */
  dictLinks: DictLinks;
  /** Font size setting */
  textSize: number;
  /** When downloaded */
  downloadedAt: Date;
}

/**
 * Sync metadata - tracks download status.
 */
export interface SyncMetadata {
  /** Key (e.g., 'lastSync', 'version') */
  key: string;
  /** Value (JSON serializable) */
  value: unknown;
  /** Last updated */
  updatedAt: Date;
}

/**
 * Pending offline operations - for future Phase 3.
 */
export interface PendingOperation {
  /** Auto-increment ID */
  id?: number;
  /** Operation type */
  type: 'word_status' | 'word_create' | 'reading_position';
  /** Target entity ID */
  entityId: number;
  /** Operation data */
  data: Record<string, unknown>;
  /** When created */
  createdAt: Date;
  /** Retry count */
  retries: number;
}

// =============================================================================
// DATABASE CLASS
// =============================================================================

/**
 * LWT Offline Database using Dexie.js.
 *
 * Schema version history:
 * - v1: Initial schema with texts, words, languages, metadata, pending ops
 */
export class LwtOfflineDatabase extends Dexie {
  // Table declarations for TypeScript
  texts!: Table<OfflineText, number>;
  textWords!: Table<OfflineTextWords, number>;
  languages!: Table<OfflineLanguage, number>;
  metadata!: Table<SyncMetadata, string>;
  pendingOps!: Table<PendingOperation, number>;

  constructor() {
    super('LwtOfflineDB');

    // Schema version 1
    this.version(1).stores({
      // Primary key is 'id', indexed by langId for filtering
      texts: 'id, langId, downloadedAt',
      // Primary key is textId
      textWords: 'textId',
      // Primary key is 'id'
      languages: 'id',
      // Primary key is 'key'
      metadata: 'key',
      // Auto-increment id, indexed by type for batch processing
      pendingOps: '++id, type, createdAt',
    });
  }
}

// =============================================================================
// DATABASE INSTANCE
// =============================================================================

/**
 * Singleton database instance.
 */
export const offlineDb = new LwtOfflineDatabase();

// =============================================================================
// DATABASE UTILITIES
// =============================================================================

/**
 * Check if IndexedDB is available.
 */
export function isIndexedDBAvailable(): boolean {
  try {
    return typeof indexedDB !== 'undefined' && indexedDB !== null;
  } catch {
    return false;
  }
}

/**
 * Get the total size of offline data in bytes.
 */
export async function getOfflineStorageSize(): Promise<number> {
  const texts = await offlineDb.texts.toArray();
  return texts.reduce((sum, text) => sum + text.sizeBytes, 0);
}

/**
 * Get count of offline texts.
 */
export async function getOfflineTextCount(): Promise<number> {
  return offlineDb.texts.count();
}

/**
 * Clear all offline data.
 */
export async function clearOfflineData(): Promise<void> {
  await offlineDb.transaction('rw', [
    offlineDb.texts,
    offlineDb.textWords,
    offlineDb.languages,
    offlineDb.metadata,
  ], async () => {
    await offlineDb.texts.clear();
    await offlineDb.textWords.clear();
    await offlineDb.languages.clear();
    await offlineDb.metadata.clear();
  });
}

/**
 * Get metadata value.
 */
export async function getMetadata<T>(key: string): Promise<T | undefined> {
  const record = await offlineDb.metadata.get(key);
  return record?.value as T | undefined;
}

/**
 * Set metadata value.
 */
export async function setMetadata(key: string, value: unknown): Promise<void> {
  await offlineDb.metadata.put({
    key,
    value,
    updatedAt: new Date(),
  });
}

// =============================================================================
// EXPORT TYPES FOR USE ELSEWHERE
// =============================================================================

export type { Table };
