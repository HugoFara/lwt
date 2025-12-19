/**
 * SortTable - Makes HTML tables sortable by clicking column headers
 *
 * A modern TypeScript replacement for sorttable.js v2 by Stuart Langridge.
 * Automatically finds tables with class="sortable" and makes them sortable.
 *
 * Features:
 * - Click column headers to sort
 * - Click again to reverse sort
 * - Automatic type detection (numeric, date, alphabetic)
 * - Custom sort keys via sorttable_customkey attribute
 * - Skip columns with sorttable_nosort class
 * - Force numeric sort with sorttable_numeric class
 *
 * @module ui/sorttable
 */

type SortFunction = (a: [string, HTMLTableRowElement], b: [string, HTMLTableRowElement]) => number;

interface SortableHeader extends HTMLTableCellElement {
  sorttable_sortfunction?: SortFunction;
  sorttable_columnindex?: number;
  sorttable_tbody?: HTMLTableSectionElement;
}

// Date regex: dd/mm/yyyy or mm/dd/yyyy with /, ., or - separators
const DATE_RE = /^(\d\d?)[/.\\-](\d\d?)[/.\\-]((\d\d)?\d\d)$/;

/**
 * Sort comparison for numeric values
 */
function sortNumeric(a: [string, HTMLTableRowElement], b: [string, HTMLTableRowElement]): number {
  const aa = parseFloat(a[0].replace(/[^0-9.-]/g, '')) || 0;
  const bb = parseFloat(b[0].replace(/[^0-9.-]/g, '')) || 0;
  return aa - bb;
}

/**
 * Sort comparison for alphabetic values
 */
function sortAlpha(a: [string, HTMLTableRowElement], b: [string, HTMLTableRowElement]): number {
  if (a[0] === b[0]) return 0;
  return a[0] < b[0] ? -1 : 1;
}

/**
 * Sort comparison for dates in dd/mm/yyyy format
 */
function sortDdMm(a: [string, HTMLTableRowElement], b: [string, HTMLTableRowElement]): number {
  const matchA = a[0].match(DATE_RE);
  const matchB = b[0].match(DATE_RE);

  if (!matchA || !matchB) return 0;

  const dt1 = matchA[3] + matchA[2].padStart(2, '0') + matchA[1].padStart(2, '0');
  const dt2 = matchB[3] + matchB[2].padStart(2, '0') + matchB[1].padStart(2, '0');

  if (dt1 === dt2) return 0;
  return dt1 < dt2 ? -1 : 1;
}

/**
 * Sort comparison for dates in mm/dd/yyyy format
 */
function sortMmDd(a: [string, HTMLTableRowElement], b: [string, HTMLTableRowElement]): number {
  const matchA = a[0].match(DATE_RE);
  const matchB = b[0].match(DATE_RE);

  if (!matchA || !matchB) return 0;

  const dt1 = matchA[3] + matchA[1].padStart(2, '0') + matchA[2].padStart(2, '0');
  const dt2 = matchB[3] + matchB[1].padStart(2, '0') + matchB[2].padStart(2, '0');

  if (dt1 === dt2) return 0;
  return dt1 < dt2 ? -1 : 1;
}

/**
 * Get the text content of a cell for sorting purposes
 * Supports custom sort keys via sorttable_customkey attribute
 */
function getInnerText(node: HTMLElement | null): string {
  if (!node) return '';

  // Check for custom sort key attribute
  const customKey = node.getAttribute('sorttable_customkey');
  if (customKey !== null) {
    return customKey;
  }

  // Check for input elements
  const inputs = node.getElementsByTagName('input');
  if (inputs.length > 0) {
    // Return the value of the first input
    return (inputs[0] as HTMLInputElement).value.trim();
  }

  // Return text content
  return (node.textContent || node.innerText || '').trim();
}

/**
 * Guess the type of a column based on its content
 */
function guessType(table: HTMLTableElement, columnIndex: number): SortFunction {
  const tbody = table.tBodies[0];
  if (!tbody) return sortAlpha;

  for (const row of Array.from(tbody.rows)) {
    const cell = row.cells[columnIndex];
    if (!cell) continue;

    const text = getInnerText(cell);
    if (text === '') continue;

    // Check for date first (before numeric, since dates with dots match numeric pattern)
    const dateMatch = text.match(DATE_RE);
    if (dateMatch) {
      const first = parseInt(dateMatch[1], 10);
      const second = parseInt(dateMatch[2], 10);

      if (first > 12) {
        return sortDdMm; // Definitely dd/mm
      } else if (second > 12) {
        return sortMmDd; // Definitely mm/dd
      } else {
        // Ambiguous, default to dd/mm
        return sortDdMm;
      }
    }

    // Check for numeric
    if (/^-?[$€£]?[\d,.]+%?$/.test(text)) {
      return sortNumeric;
    }
  }

  return sortAlpha;
}

/**
 * Reverse the rows in a tbody
 */
function reverseRows(tbody: HTMLTableSectionElement): void {
  const rows = Array.from(tbody.rows);
  for (let i = rows.length - 1; i >= 0; i--) {
    tbody.appendChild(rows[i]);
  }
}

/**
 * Handle click on a sortable column header
 */
function handleHeaderClick(this: SortableHeader): void {
  const tbody = this.sorttable_tbody;
  const columnIndex = this.sorttable_columnindex;
  const sortFunction = this.sorttable_sortfunction;

  if (!tbody || columnIndex === undefined || !sortFunction) return;

  // Check if already sorted by this column
  if (this.classList.contains('sorttable_sorted')) {
    // Reverse the table
    reverseRows(tbody);
    this.classList.remove('sorttable_sorted');
    this.classList.add('sorttable_sorted_reverse');

    // Update indicator
    const fwdInd = document.getElementById('sorttable_sortfwdind');
    if (fwdInd) fwdInd.remove();

    const revInd = document.createElement('span');
    revInd.id = 'sorttable_sortrevind';
    revInd.innerHTML = '&nbsp;&#x25B4;'; // Up triangle
    this.appendChild(revInd);
    return;
  }

  if (this.classList.contains('sorttable_sorted_reverse')) {
    // Reverse again
    reverseRows(tbody);
    this.classList.remove('sorttable_sorted_reverse');
    this.classList.add('sorttable_sorted');

    // Update indicator
    const revInd = document.getElementById('sorttable_sortrevind');
    if (revInd) revInd.remove();

    const fwdInd = document.createElement('span');
    fwdInd.id = 'sorttable_sortfwdind';
    fwdInd.innerHTML = '&nbsp;&#x25BE;'; // Down triangle
    this.appendChild(fwdInd);
    return;
  }

  // Remove sorted classes from all headers in this row
  const headerRow = this.parentNode as HTMLTableRowElement;
  if (headerRow) {
    for (const cell of Array.from(headerRow.cells)) {
      cell.classList.remove('sorttable_sorted', 'sorttable_sorted_reverse');
    }
  }

  // Remove existing indicators
  const existingFwd = document.getElementById('sorttable_sortfwdind');
  if (existingFwd) existingFwd.remove();
  const existingRev = document.getElementById('sorttable_sortrevind');
  if (existingRev) existingRev.remove();

  // Mark this column as sorted
  this.classList.add('sorttable_sorted');

  // Add sort indicator
  const sortInd = document.createElement('span');
  sortInd.id = 'sorttable_sortfwdind';
  sortInd.innerHTML = '&nbsp;&#x25BE;'; // Down triangle
  this.appendChild(sortInd);

  // Build array of [sortKey, row] pairs (Schwartzian transform)
  const rowArray: [string, HTMLTableRowElement][] = [];
  for (const row of Array.from(tbody.rows)) {
    const cell = row.cells[columnIndex];
    rowArray.push([getInnerText(cell), row]);
  }

  // Sort the array
  rowArray.sort(sortFunction);

  // Reorder the rows in the DOM
  for (const [, row] of rowArray) {
    tbody.appendChild(row);
  }
}

/**
 * Make a table sortable
 */
function makeSortable(table: HTMLTableElement): void {
  // Ensure the table has a thead
  if (table.tHead === null) {
    const thead = table.getElementsByTagName('thead')[0];
    if (thead) {
      table.tHead = thead;
    } else if (table.rows.length > 0) {
      // Create thead from first row
      const newThead = document.createElement('thead');
      newThead.appendChild(table.rows[0]);
      table.insertBefore(newThead, table.firstChild);
      table.tHead = newThead;
    }
  }

  if (!table.tHead || table.tHead.rows.length === 0) return;

  // Move "sortbottom" rows to tfoot for backwards compatibility
  const sortBottomRows: HTMLTableRowElement[] = [];
  for (const row of Array.from(table.rows)) {
    if (row.classList.contains('sortbottom')) {
      sortBottomRows.push(row);
    }
  }

  if (sortBottomRows.length > 0) {
    let tfoot = table.tFoot;
    if (!tfoot) {
      tfoot = document.createElement('tfoot');
      table.appendChild(tfoot);
    }
    for (const row of sortBottomRows) {
      tfoot.appendChild(row);
    }
  }

  // Process header cells
  const headerRow = table.tHead.rows[0];
  const tbody = table.tBodies[0];

  if (!tbody) return;

  for (let i = 0; i < headerRow.cells.length; i++) {
    const header = headerRow.cells[i] as SortableHeader;

    // Skip columns marked as nosort
    if (header.classList.contains('sorttable_nosort')) continue;

    // Determine sort function
    if (header.classList.contains('sorttable_numeric')) {
      header.sorttable_sortfunction = sortNumeric;
    } else {
      // Check for custom sort type class (sorttable_ddmm, sorttable_mmdd, etc.)
      const classMatch = header.className.match(/\bsorttable_([a-z0-9]+)\b/);
      if (classMatch) {
        const sortType = classMatch[1];
        if (sortType === 'numeric') {
          header.sorttable_sortfunction = sortNumeric;
        } else if (sortType === 'ddmm') {
          header.sorttable_sortfunction = sortDdMm;
        } else if (sortType === 'mmdd') {
          header.sorttable_sortfunction = sortMmDd;
        } else if (sortType === 'alpha') {
          header.sorttable_sortfunction = sortAlpha;
        } else {
          header.sorttable_sortfunction = guessType(table, i);
        }
      } else {
        header.sorttable_sortfunction = guessType(table, i);
      }
    }

    header.sorttable_columnindex = i;
    header.sorttable_tbody = tbody;

    // Make header clickable
    header.style.cursor = 'pointer';
    header.addEventListener('click', handleHeaderClick);
  }

  // Mark table as processed
  table.classList.add('sorttable_processed');
}

/**
 * Initialize all sortable tables on the page
 */
function initSortable(): void {
  const tables = document.querySelectorAll<HTMLTableElement>('table.sortable:not(.sorttable_processed)');
  tables.forEach(makeSortable);
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initSortable);
} else {
  initSortable();
}

// Re-initialize when new content is added (for AJAX-loaded tables)
// Use MutationObserver to watch for new tables
const observer = new MutationObserver((mutations) => {
  for (const mutation of mutations) {
    if (mutation.type === 'childList') {
      for (const node of Array.from(mutation.addedNodes)) {
        if (node instanceof HTMLElement) {
          // Check if the added node is a sortable table
          if (node.matches('table.sortable:not(.sorttable_processed)')) {
            makeSortable(node as HTMLTableElement);
          }
          // Check for sortable tables within the added node
          const tables = node.querySelectorAll<HTMLTableElement>('table.sortable:not(.sorttable_processed)');
          tables.forEach(makeSortable);
        }
      }
    }
  }
});

// Start observing once DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    observer.observe(document.body, { childList: true, subtree: true });
  });
} else {
  observer.observe(document.body, { childList: true, subtree: true });
}

// Export for manual use if needed
export { makeSortable, initSortable };
