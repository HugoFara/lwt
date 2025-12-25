/**
 * Test State Module - Manages mutable test/review mode state.
 *
 * This module provides explicit getter/setter functions for test mode operations.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since 3.1.0
 */

/**
 * Current word ID being tested.
 */
let currentWordId = 0;

/**
 * The correct solution for the current test question.
 */
let testSolution = '';

/**
 * Whether the answer has been revealed.
 */
let answerOpened = false;

/**
 * Get the current word ID being tested.
 */
export function getCurrentWordId(): number {
  return currentWordId;
}

/**
 * Set the current word ID being tested.
 */
export function setCurrentWordId(wordId: number): void {
  currentWordId = wordId;
}

/**
 * Get the test solution.
 */
export function getTestSolution(): string {
  return testSolution;
}

/**
 * Set the test solution.
 */
export function setTestSolution(solution: string): void {
  testSolution = solution;
}

/**
 * Check if the answer has been opened/revealed.
 */
export function isAnswerOpened(): boolean {
  return answerOpened;
}

/**
 * Set whether the answer has been opened.
 */
export function setAnswerOpened(opened: boolean): void {
  answerOpened = opened;
}

/**
 * Open/reveal the answer.
 */
export function openAnswer(): void {
  answerOpened = true;
}

/**
 * Reset the answer state (for new question).
 */
export function resetAnswer(): void {
  answerOpened = false;
}

/**
 * Reset all test state (for testing or new session).
 */
export function resetTestState(): void {
  currentWordId = 0;
  testSolution = '';
  answerOpened = false;
}
