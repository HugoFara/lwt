/**
 * Text Styles - Dynamic CSS generation for text reading view.
 *
 * Generates and injects dynamic CSS based on user settings for:
 * - Annotation display (translations, romanization)
 * - Ruby-style layout (above/below text)
 * - Status-specific translation display
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import type { TextReadingConfig } from '../api/texts';

/**
 * Style element ID for dynamic text styles.
 */
const STYLE_ELEMENT_ID = 'text-dynamic-styles';

/**
 * Check if a status should display translations based on displayStatTrans setting.
 *
 * @param status Status value (1-5, 98, 99)
 * @param displayStatTrans Setting value for which statuses show translations
 * @returns Whether translation should be shown for this status
 */
function shouldShowTransForStatus(status: number, displayStatTrans: number): boolean {
  // displayStatTrans is a bitmap: 1=status1, 2=status2, 4=status3, 8=status4, 16=status5, 32=ignored, 64=wellknown
  switch (status) {
    case 1: return (displayStatTrans & 1) !== 0;
    case 2: return (displayStatTrans & 2) !== 0;
    case 3: return (displayStatTrans & 4) !== 0;
    case 4: return (displayStatTrans & 8) !== 0;
    case 5: return (displayStatTrans & 16) !== 0;
    case 98: return (displayStatTrans & 32) !== 0;
    case 99: return (displayStatTrans & 64) !== 0;
    default: return false;
  }
}

/**
 * Generate dynamic CSS for text annotation display.
 *
 * @param config Text reading configuration
 * @returns CSS string
 */
export function generateTextStyles(config: TextReadingConfig): string {
  const { modeTrans, annTextSize } = config;

  const isRuby = modeTrans === 2 || modeTrans === 4;

  const cssRules: string[] = [];

  // Ruby-style layout rules
  if (isRuby) {
    const marginRule = modeTrans === 4 ? 'margin-top: 0.2em;' : 'margin-bottom: 0.2em;';
    const verticalAlign = modeTrans === 2 ? 'vertical-align: top;' : '';

    cssRules.push(`
.wsty {
  ${marginRule}
  text-align: center;
  display: inline-block;
  ${verticalAlign}
}`);

    const annMargin = modeTrans === 2 ? 'margin-top: -0.05em;' : 'margin-bottom: -0.15em;';
    cssRules.push(`
.wsty .word-ann {
  display: block !important;
  ${annMargin}
}`);
  }

  // General annotation styling
  const textAlign = isRuby ? 'text-align: center;' : '';
  const marginLeft = modeTrans === 1 ? 'margin-left: 0.2em;' : '';
  const marginRight = modeTrans === 3 ? 'margin-right: 0.2em;' : '';

  cssRules.push(`
.word-ann {
  ${textAlign}
  font-size: ${annTextSize}%;
  ${marginLeft}
  ${marginRight}
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
  display: inline-block;
  vertical-align: -25%;
  color: #006699;
  max-width: 15em;
}`);

  // Allow rich text formatting within annotations
  cssRules.push(`
.word-ann strong, .word-ann b {
  font-weight: bold;
}
.word-ann em, .word-ann i {
  font-style: italic;
}
.word-ann del {
  text-decoration: line-through;
}
.word-ann a {
  color: #006699;
  text-decoration: underline;
}`);

  // Hide class
  cssRules.push('.hide{display:none !important;}');

  return cssRules.join('\n');
}

/**
 * Inject or update dynamic text styles in the document.
 *
 * @param config Text reading configuration
 */
export function injectTextStyles(config: TextReadingConfig): void {
  // Remove existing style element if present
  const existingStyle = document.getElementById(STYLE_ELEMENT_ID);
  if (existingStyle) {
    existingStyle.remove();
  }

  // Create new style element
  const styleEl = document.createElement('style');
  styleEl.id = STYLE_ELEMENT_ID;
  styleEl.textContent = generateTextStyles(config);

  // Inject into head
  document.head.appendChild(styleEl);
}

/**
 * Generate inline styles for the text paragraph element.
 *
 * @param config Text reading configuration
 * @returns Style string for the paragraph element
 */
export function generateParagraphStyles(config: TextReadingConfig): string {
  const { textSize, removeSpaces, modeTrans } = config;
  const isRuby = modeTrans === 2 || modeTrans === 4;
  const lineHeight = isRuby ? '1' : '1.4';
  const wordBreak = removeSpaces ? 'word-break:break-all;' : '';

  return `margin-bottom: 10px; ${wordBreak} font-size: ${textSize}%; line-height: ${lineHeight};`;
}

/**
 * Remove dynamic text styles from the document.
 */
export function removeTextStyles(): void {
  const styleEl = document.getElementById(STYLE_ELEMENT_ID);
  if (styleEl) {
    styleEl.remove();
  }
}
