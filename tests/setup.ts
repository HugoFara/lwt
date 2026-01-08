/**
 * Vitest global test setup
 * Sets up mocks for browser APIs not available in jsdom
 */
import { vi } from 'vitest';

// Suppress jsdom "Not implemented" warnings that are expected in test environment
// These warnings are normal for DOM methods jsdom hasn't fully implemented
// They are sent directly to stderr by jsdom, so we need to patch at that level
const originalStderrWrite = process.stderr.write.bind(process.stderr);
process.stderr.write = ((chunk: string | Uint8Array, ...args: unknown[]) => {
  const message = typeof chunk === 'string' ? chunk : chunk.toString();
  if (message.startsWith('Not implemented:')) {
    return true; // Suppress jsdom warnings
  }
  return originalStderrWrite(chunk, ...args);
}) as typeof process.stderr.write;

// Mock Web Speech API - not available in jsdom
const mockSpeechSynthesis = {
  speak: vi.fn(),
  cancel: vi.fn(),
  pause: vi.fn(),
  resume: vi.fn(),
  getVoices: vi.fn().mockReturnValue([]),
  pending: false,
  speaking: false,
  paused: false,
  onvoiceschanged: null,
  addEventListener: vi.fn(),
  removeEventListener: vi.fn(),
  dispatchEvent: vi.fn(),
};

// Define SpeechSynthesisUtterance if not defined
if (typeof SpeechSynthesisUtterance === 'undefined') {
  (global as Record<string, unknown>).SpeechSynthesisUtterance = class MockSpeechSynthesisUtterance {
    text = '';
    lang = '';
    voice: SpeechSynthesisVoice | null = null;
    volume = 1;
    rate = 1;
    pitch = 1;
    onstart: ((this: SpeechSynthesisUtterance, ev: SpeechSynthesisEvent) => void) | null = null;
    onend: ((this: SpeechSynthesisUtterance, ev: SpeechSynthesisEvent) => void) | null = null;
    onerror: ((this: SpeechSynthesisUtterance, ev: SpeechSynthesisErrorEvent) => void) | null = null;
    onpause: ((this: SpeechSynthesisUtterance, ev: SpeechSynthesisEvent) => void) | null = null;
    onresume: ((this: SpeechSynthesisUtterance, ev: SpeechSynthesisEvent) => void) | null = null;
    onmark: ((this: SpeechSynthesisUtterance, ev: SpeechSynthesisEvent) => void) | null = null;
    onboundary: ((this: SpeechSynthesisUtterance, ev: SpeechSynthesisEvent) => void) | null = null;
    addEventListener = vi.fn();
    removeEventListener = vi.fn();
    dispatchEvent = vi.fn();
  };
}

// Attach to window object for jsdom environment
Object.defineProperty(window, 'speechSynthesis', {
  value: mockSpeechSynthesis,
  writable: true,
  configurable: true,
});

// Mock methods not implemented in jsdom to suppress console warnings
// These are no-op implementations that prevent "Not implemented" messages
window.scrollTo = vi.fn();
// Note: window.alert is NOT mocked globally - tests that use it should mock it themselves
// to avoid interference with test spies

// Mock HTMLMediaElement methods
Object.defineProperty(HTMLMediaElement.prototype, 'load', {
  value: vi.fn(),
  writable: true,
});
Object.defineProperty(HTMLMediaElement.prototype, 'play', {
  value: vi.fn().mockResolvedValue(undefined),
  writable: true,
});
Object.defineProperty(HTMLMediaElement.prototype, 'pause', {
  value: vi.fn(),
  writable: true,
});

// Mock HTMLFormElement.requestSubmit (not in jsdom)
// Note: The real requestSubmit accepts an optional submitter parameter,
// but our mock doesn't need to use it - we just dispatch a submit event
Object.defineProperty(HTMLFormElement.prototype, 'requestSubmit', {
  value: vi.fn(function(this: HTMLFormElement) {
    // Simulate submitting the form by dispatching a submit event
    const event = new Event('submit', { bubbles: true, cancelable: true });
    this.dispatchEvent(event);
  }),
  writable: true,
});
