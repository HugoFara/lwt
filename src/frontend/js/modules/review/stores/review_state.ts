/**
 * Review State Module - Manages mutable review mode state.
 *
 * This module provides explicit getter/setter functions for review mode operations.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since 3.1.0
 */

/**
 * Current word ID being reviewed.
 */
let currentWordId = 0;

/**
 * The correct solution for the current review question.
 */
let reviewSolution = '';

/**
 * Whether the answer has been revealed.
 */
let answerOpened = false;

/**
 * Get the current word ID being reviewed.
 */
export function getCurrentWordId(): number {
  return currentWordId;
}

/**
 * Set the current word ID being reviewed.
 */
export function setCurrentWordId(wordId: number): void {
  currentWordId = wordId;
}

/**
 * Get the review solution.
 */
export function getReviewSolution(): string {
  return reviewSolution;
}

/**
 * Set the review solution.
 */
export function setReviewSolution(solution: string): void {
  reviewSolution = solution;
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
 * Reset all review state (for new session).
 */
export function resetReviewState(): void {
  currentWordId = 0;
  reviewSolution = '';
  answerOpened = false;
}
