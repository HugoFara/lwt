/**
 * Tests for sorttable.ts - Table sorting component
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { makeSortable, initSortable } from '../../../src/frontend/js/ui/sorttable';

/**
 * Helper to create a sortable table for testing
 */
function createTable(options: {
  headers: string[];
  rows: string[][];
  headerClasses?: string[];
  cellAttributes?: Record<number, Record<number, Record<string, string>>>;
  hasCustomKeys?: Record<number, Record<number, string>>;
}): HTMLTableElement {
  const table = document.createElement('table');
  table.className = 'sortable';

  // Create thead
  const thead = document.createElement('thead');
  const headerRow = document.createElement('tr');
  options.headers.forEach((text, i) => {
    const th = document.createElement('th');
    th.textContent = text;
    if (options.headerClasses && options.headerClasses[i]) {
      th.className = options.headerClasses[i];
    }
    headerRow.appendChild(th);
  });
  thead.appendChild(headerRow);
  table.appendChild(thead);

  // Create tbody
  const tbody = document.createElement('tbody');
  options.rows.forEach((rowData, rowIndex) => {
    const tr = document.createElement('tr');
    rowData.forEach((cellText, cellIndex) => {
      const td = document.createElement('td');
      td.textContent = cellText;

      // Add custom key if specified
      if (options.hasCustomKeys?.[rowIndex]?.[cellIndex]) {
        td.setAttribute('sorttable_customkey', options.hasCustomKeys[rowIndex][cellIndex]);
      }

      // Add any other attributes
      if (options.cellAttributes?.[rowIndex]?.[cellIndex]) {
        Object.entries(options.cellAttributes[rowIndex][cellIndex]).forEach(([attr, val]) => {
          td.setAttribute(attr, val);
        });
      }

      tr.appendChild(td);
    });
    tbody.appendChild(tr);
  });
  table.appendChild(tbody);

  return table;
}

/**
 * Get text content of all rows in a column
 */
function getColumnValues(table: HTMLTableElement, columnIndex: number): string[] {
  const tbody = table.tBodies[0];
  return Array.from(tbody.rows).map(row => row.cells[columnIndex]?.textContent || '');
}

/**
 * Click a header to trigger sorting
 */
function clickHeader(table: HTMLTableElement, columnIndex: number): void {
  const header = table.tHead?.rows[0].cells[columnIndex];
  if (header) {
    header.click();
  }
}

describe('sorttable.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // makeSortable Tests
  // ===========================================================================

  describe('makeSortable', () => {
    it('adds sorttable_processed class to table', () => {
      const table = createTable({
        headers: ['Name', 'Age'],
        rows: [['Alice', '30'], ['Bob', '25']]
      });
      document.body.appendChild(table);

      makeSortable(table);

      expect(table.classList.contains('sorttable_processed')).toBe(true);
    });

    it('makes headers clickable with cursor pointer', () => {
      const table = createTable({
        headers: ['Name', 'Age'],
        rows: [['Alice', '30'], ['Bob', '25']]
      });
      document.body.appendChild(table);

      makeSortable(table);

      const header = table.tHead?.rows[0].cells[0];
      expect(header?.style.cursor).toBe('pointer');
    });

    it('skips columns with sorttable_nosort class', () => {
      const table = createTable({
        headers: ['Name', 'Actions'],
        rows: [['Alice', 'Edit'], ['Bob', 'Delete']],
        headerClasses: ['', 'sorttable_nosort']
      });
      document.body.appendChild(table);

      makeSortable(table);

      const actionHeader = table.tHead?.rows[0].cells[1];
      expect(actionHeader?.style.cursor).not.toBe('pointer');
    });

    it('creates thead from first row if missing', () => {
      const table = document.createElement('table');
      table.className = 'sortable';
      const tbody = document.createElement('tbody');
      const headerRow = document.createElement('tr');
      headerRow.innerHTML = '<td>Name</td><td>Age</td>';
      tbody.appendChild(headerRow);
      const dataRow = document.createElement('tr');
      dataRow.innerHTML = '<td>Alice</td><td>30</td>';
      tbody.appendChild(dataRow);
      table.appendChild(tbody);
      document.body.appendChild(table);

      makeSortable(table);

      expect(table.tHead).not.toBeNull();
    });

    it('handles table with no tbody gracefully', () => {
      const table = document.createElement('table');
      table.className = 'sortable';
      const thead = document.createElement('thead');
      thead.innerHTML = '<tr><th>Name</th></tr>';
      table.appendChild(thead);
      document.body.appendChild(table);

      expect(() => makeSortable(table)).not.toThrow();
    });
  });

  // ===========================================================================
  // Alphabetic Sorting Tests
  // ===========================================================================

  describe('Alphabetic sorting', () => {
    it('sorts text alphabetically in ascending order on first click', () => {
      const table = createTable({
        headers: ['Name'],
        rows: [['Charlie'], ['Alice'], ['Bob']]
      });
      document.body.appendChild(table);
      makeSortable(table);

      clickHeader(table, 0);

      expect(getColumnValues(table, 0)).toEqual(['Alice', 'Bob', 'Charlie']);
    });

    it('reverses sort on second click', () => {
      const table = createTable({
        headers: ['Name'],
        rows: [['Charlie'], ['Alice'], ['Bob']]
      });
      document.body.appendChild(table);
      makeSortable(table);

      clickHeader(table, 0);
      clickHeader(table, 0);

      expect(getColumnValues(table, 0)).toEqual(['Charlie', 'Bob', 'Alice']);
    });

    it('restores ascending order on third click', () => {
      const table = createTable({
        headers: ['Name'],
        rows: [['Charlie'], ['Alice'], ['Bob']]
      });
      document.body.appendChild(table);
      makeSortable(table);

      clickHeader(table, 0);
      clickHeader(table, 0);
      clickHeader(table, 0);

      expect(getColumnValues(table, 0)).toEqual(['Alice', 'Bob', 'Charlie']);
    });

    it('handles empty strings', () => {
      const table = createTable({
        headers: ['Name'],
        rows: [['Bob'], [''], ['Alice']]
      });
      document.body.appendChild(table);
      makeSortable(table);

      clickHeader(table, 0);

      expect(getColumnValues(table, 0)).toEqual(['', 'Alice', 'Bob']);
    });

    it('handles case-sensitive sorting', () => {
      const table = createTable({
        headers: ['Name'],
        rows: [['bob'], ['Alice'], ['Bob']]
      });
      document.body.appendChild(table);
      makeSortable(table);

      clickHeader(table, 0);

      // Uppercase comes before lowercase in ASCII
      expect(getColumnValues(table, 0)).toEqual(['Alice', 'Bob', 'bob']);
    });
  });

  // ===========================================================================
  // Numeric Sorting Tests
  // ===========================================================================

  describe('Numeric sorting', () => {
    it('auto-detects numeric columns and sorts numerically', () => {
      const table = createTable({
        headers: ['Score'],
        rows: [['100'], ['20'], ['3']]
      });
      document.body.appendChild(table);
      makeSortable(table);

      clickHeader(table, 0);

      expect(getColumnValues(table, 0)).toEqual(['3', '20', '100']);
    });

    it('handles numbers with currency symbols', () => {
      const table = createTable({
        headers: ['Price'],
        rows: [['$100'], ['$20'], ['$3']]
      });
      document.body.appendChild(table);
      makeSortable(table);

      clickHeader(table, 0);

      expect(getColumnValues(table, 0)).toEqual(['$3', '$20', '$100']);
    });

    it('handles numbers with commas', () => {
      const table = createTable({
        headers: ['Amount'],
        rows: [['1,000'], ['100'], ['10,000']]
      });
      document.body.appendChild(table);
      makeSortable(table);

      clickHeader(table, 0);

      expect(getColumnValues(table, 0)).toEqual(['100', '1,000', '10,000']);
    });

    it('handles negative numbers', () => {
      const table = createTable({
        headers: ['Value'],
        rows: [['10'], ['-5'], ['0']]
      });
      document.body.appendChild(table);
      makeSortable(table);

      clickHeader(table, 0);

      expect(getColumnValues(table, 0)).toEqual(['-5', '0', '10']);
    });

    it('handles decimal numbers', () => {
      const table = createTable({
        headers: ['Rating'],
        rows: [['3.5'], ['3.14'], ['3.9']]
      });
      document.body.appendChild(table);
      makeSortable(table);

      clickHeader(table, 0);

      expect(getColumnValues(table, 0)).toEqual(['3.14', '3.5', '3.9']);
    });

    it('handles percentages', () => {
      const table = createTable({
        headers: ['Progress'],
        rows: [['50%'], ['5%'], ['100%']]
      });
      document.body.appendChild(table);
      makeSortable(table);

      clickHeader(table, 0);

      expect(getColumnValues(table, 0)).toEqual(['5%', '50%', '100%']);
    });

    it('treats non-numeric values as 0', () => {
      const table = createTable({
        headers: ['Score'],
        rows: [['10'], ['N/A'], ['5']],
        headerClasses: ['sorttable_numeric']
      });
      document.body.appendChild(table);
      makeSortable(table);

      clickHeader(table, 0);

      expect(getColumnValues(table, 0)).toEqual(['N/A', '5', '10']);
    });

    it('respects sorttable_numeric class override', () => {
      const table = createTable({
        headers: ['ID'],
        rows: [['10'], ['2'], ['1']],
        headerClasses: ['sorttable_numeric']
      });
      document.body.appendChild(table);
      makeSortable(table);

      clickHeader(table, 0);

      expect(getColumnValues(table, 0)).toEqual(['1', '2', '10']);
    });
  });

  // ===========================================================================
  // Date Sorting Tests
  // ===========================================================================

  describe('Date sorting', () => {
    it('sorts dd/mm/yyyy dates correctly', () => {
      const table = createTable({
        headers: ['Date'],
        rows: [['25/12/2023'], ['01/01/2024'], ['15/06/2023']]
      });
      document.body.appendChild(table);
      makeSortable(table);

      clickHeader(table, 0);

      expect(getColumnValues(table, 0)).toEqual(['15/06/2023', '25/12/2023', '01/01/2024']);
    });

    it('sorts dates with dot separator', () => {
      const table = createTable({
        headers: ['Date'],
        rows: [['25.12.2023'], ['01.01.2024'], ['15.06.2023']]
      });
      document.body.appendChild(table);
      makeSortable(table);

      clickHeader(table, 0);

      expect(getColumnValues(table, 0)).toEqual(['15.06.2023', '25.12.2023', '01.01.2024']);
    });

    it('sorts dates with dash separator', () => {
      const table = createTable({
        headers: ['Date'],
        rows: [['25-12-2023'], ['01-01-2024'], ['15-06-2023']]
      });
      document.body.appendChild(table);
      makeSortable(table);

      clickHeader(table, 0);

      expect(getColumnValues(table, 0)).toEqual(['15-06-2023', '25-12-2023', '01-01-2024']);
    });

    it('detects mm/dd format when day > 12', () => {
      const table = createTable({
        headers: ['Date'],
        rows: [['01/25/2023'], ['06/15/2023'], ['12/01/2023']]
      });
      document.body.appendChild(table);
      makeSortable(table);

      clickHeader(table, 0);

      // mm/dd format: Jan 25, Jun 15, Dec 1
      expect(getColumnValues(table, 0)).toEqual(['01/25/2023', '06/15/2023', '12/01/2023']);
    });

    it('handles two-digit years', () => {
      const table = createTable({
        headers: ['Date'],
        rows: [['25/12/23'], ['01/01/24'], ['15/06/23']]
      });
      document.body.appendChild(table);
      makeSortable(table);

      clickHeader(table, 0);

      expect(getColumnValues(table, 0)).toEqual(['15/06/23', '25/12/23', '01/01/24']);
    });
  });

  // ===========================================================================
  // Custom Sort Key Tests
  // ===========================================================================

  describe('Custom sort key (sorttable_customkey)', () => {
    it('uses sorttable_customkey attribute for sorting', () => {
      const table = createTable({
        headers: ['Status'],
        rows: [['Active'], ['Pending'], ['Done']],
        hasCustomKeys: {
          0: { 0: '2' },  // Active = 2
          1: { 0: '1' },  // Pending = 1
          2: { 0: '3' }   // Done = 3
        }
      });
      document.body.appendChild(table);
      makeSortable(table);

      clickHeader(table, 0);

      expect(getColumnValues(table, 0)).toEqual(['Pending', 'Active', 'Done']);
    });

    it('uses custom key even when display text differs', () => {
      const table = createTable({
        headers: ['Last Update'],
        rows: [['5 minutes ago'], ['2 hours ago'], ['Just now']],
        hasCustomKeys: {
          0: { 0: '300' },   // 5 min = 300 seconds
          1: { 0: '7200' },  // 2 hours = 7200 seconds
          2: { 0: '0' }      // Just now = 0 seconds
        }
      });
      document.body.appendChild(table);
      makeSortable(table);

      clickHeader(table, 0);

      expect(getColumnValues(table, 0)).toEqual(['Just now', '5 minutes ago', '2 hours ago']);
    });
  });

  // ===========================================================================
  // Sort Indicator Tests
  // ===========================================================================

  describe('Sort indicators', () => {
    it('adds sorttable_sorted class on first click', () => {
      const table = createTable({
        headers: ['Name'],
        rows: [['Alice'], ['Bob']]
      });
      document.body.appendChild(table);
      makeSortable(table);

      clickHeader(table, 0);

      const header = table.tHead?.rows[0].cells[0];
      expect(header?.classList.contains('sorttable_sorted')).toBe(true);
    });

    it('changes to sorttable_sorted_reverse on second click', () => {
      const table = createTable({
        headers: ['Name'],
        rows: [['Alice'], ['Bob']]
      });
      document.body.appendChild(table);
      makeSortable(table);

      clickHeader(table, 0);
      clickHeader(table, 0);

      const header = table.tHead?.rows[0].cells[0];
      expect(header?.classList.contains('sorttable_sorted_reverse')).toBe(true);
      expect(header?.classList.contains('sorttable_sorted')).toBe(false);
    });

    it('adds sort indicator span on sort', () => {
      const table = createTable({
        headers: ['Name'],
        rows: [['Alice'], ['Bob']]
      });
      document.body.appendChild(table);
      makeSortable(table);

      clickHeader(table, 0);

      const indicator = document.getElementById('sorttable_sortfwdind');
      expect(indicator).not.toBeNull();
    });

    it('shows down arrow for ascending sort', () => {
      const table = createTable({
        headers: ['Name'],
        rows: [['Alice'], ['Bob']]
      });
      document.body.appendChild(table);
      makeSortable(table);

      clickHeader(table, 0);

      const indicator = document.getElementById('sorttable_sortfwdind');
      expect(indicator?.innerHTML).toContain('▾'); // Down triangle
    });

    it('shows up arrow for descending sort', () => {
      const table = createTable({
        headers: ['Name'],
        rows: [['Alice'], ['Bob']]
      });
      document.body.appendChild(table);
      makeSortable(table);

      clickHeader(table, 0);
      clickHeader(table, 0);

      const indicator = document.getElementById('sorttable_sortrevind');
      expect(indicator?.innerHTML).toContain('▴'); // Up triangle
    });

    it('removes indicator from previous column when sorting different column', () => {
      const table = createTable({
        headers: ['Name', 'Age'],
        rows: [['Alice', '30'], ['Bob', '25']]
      });
      document.body.appendChild(table);
      makeSortable(table);

      clickHeader(table, 0);
      clickHeader(table, 1);

      expect(document.getElementById('sorttable_sortfwdind')?.parentElement).toBe(
        table.tHead?.rows[0].cells[1]
      );
    });

    it('removes sorted class from previous column', () => {
      const table = createTable({
        headers: ['Name', 'Age'],
        rows: [['Alice', '30'], ['Bob', '25']]
      });
      document.body.appendChild(table);
      makeSortable(table);

      clickHeader(table, 0);
      clickHeader(table, 1);

      const nameHeader = table.tHead?.rows[0].cells[0];
      const ageHeader = table.tHead?.rows[0].cells[1];
      expect(nameHeader?.classList.contains('sorttable_sorted')).toBe(false);
      expect(ageHeader?.classList.contains('sorttable_sorted')).toBe(true);
    });
  });

  // ===========================================================================
  // Multi-column Sorting Tests
  // ===========================================================================

  describe('Multi-column sorting', () => {
    it('maintains row integrity when sorting', () => {
      const table = createTable({
        headers: ['Name', 'Age', 'City'],
        rows: [
          ['Charlie', '30', 'NYC'],
          ['Alice', '25', 'LA'],
          ['Bob', '35', 'Chicago']
        ]
      });
      document.body.appendChild(table);
      makeSortable(table);

      clickHeader(table, 0);

      // After sorting by name, verify entire rows are intact
      expect(getColumnValues(table, 0)).toEqual(['Alice', 'Bob', 'Charlie']);
      expect(getColumnValues(table, 1)).toEqual(['25', '35', '30']);
      expect(getColumnValues(table, 2)).toEqual(['LA', 'Chicago', 'NYC']);
    });

    it('can sort by different columns sequentially', () => {
      const table = createTable({
        headers: ['Name', 'Score'],
        rows: [
          ['Alice', '85'],
          ['Bob', '90'],
          ['Charlie', '80']
        ]
      });
      document.body.appendChild(table);
      makeSortable(table);

      // Sort by score
      clickHeader(table, 1);
      expect(getColumnValues(table, 1)).toEqual(['80', '85', '90']);

      // Sort by name
      clickHeader(table, 0);
      expect(getColumnValues(table, 0)).toEqual(['Alice', 'Bob', 'Charlie']);
    });
  });

  // ===========================================================================
  // Input Field Tests
  // ===========================================================================

  describe('Input field handling', () => {
    it('sorts by input value instead of text content', () => {
      const table = document.createElement('table');
      table.className = 'sortable';
      table.innerHTML = `
        <thead><tr><th>Value</th></tr></thead>
        <tbody>
          <tr><td><input type="text" value="Charlie" /></td></tr>
          <tr><td><input type="text" value="Alice" /></td></tr>
          <tr><td><input type="text" value="Bob" /></td></tr>
        </tbody>
      `;
      document.body.appendChild(table);
      makeSortable(table);

      clickHeader(table, 0);

      const values = Array.from(table.tBodies[0].rows).map(
        row => (row.cells[0].querySelector('input') as HTMLInputElement).value
      );
      expect(values).toEqual(['Alice', 'Bob', 'Charlie']);
    });
  });

  // ===========================================================================
  // initSortable Tests
  // ===========================================================================

  describe('initSortable', () => {
    it('initializes all sortable tables on the page', () => {
      const table1 = createTable({
        headers: ['A'],
        rows: [['1'], ['2']]
      });
      const table2 = createTable({
        headers: ['B'],
        rows: [['3'], ['4']]
      });
      document.body.appendChild(table1);
      document.body.appendChild(table2);

      initSortable();

      expect(table1.classList.contains('sorttable_processed')).toBe(true);
      expect(table2.classList.contains('sorttable_processed')).toBe(true);
    });

    it('does not re-process already processed tables', () => {
      const table = createTable({
        headers: ['Name'],
        rows: [['Alice'], ['Bob']]
      });
      document.body.appendChild(table);

      initSortable();
      const _firstHeader = table.tHead?.rows[0].cells[0];

      // Run again
      initSortable();

      // Should still have only one click handler (verify by sorting behavior)
      clickHeader(table, 0);
      expect(getColumnValues(table, 0)).toEqual(['Alice', 'Bob']);
    });

    it('ignores tables without sortable class', () => {
      const table = document.createElement('table');
      table.innerHTML = `
        <thead><tr><th>Name</th></tr></thead>
        <tbody><tr><td>Alice</td></tr></tbody>
      `;
      document.body.appendChild(table);

      initSortable();

      expect(table.classList.contains('sorttable_processed')).toBe(false);
    });
  });

  // ===========================================================================
  // Edge Cases
  // ===========================================================================

  describe('Edge cases', () => {
    it('handles empty table body', () => {
      const table = createTable({
        headers: ['Name'],
        rows: []
      });
      document.body.appendChild(table);

      expect(() => makeSortable(table)).not.toThrow();
      expect(() => clickHeader(table, 0)).not.toThrow();
    });

    it('handles single row', () => {
      const table = createTable({
        headers: ['Name'],
        rows: [['Alice']]
      });
      document.body.appendChild(table);
      makeSortable(table);

      clickHeader(table, 0);

      expect(getColumnValues(table, 0)).toEqual(['Alice']);
    });

    it('handles cells with only whitespace', () => {
      const table = createTable({
        headers: ['Name'],
        rows: [['Bob'], ['   '], ['Alice']]
      });
      document.body.appendChild(table);
      makeSortable(table);

      clickHeader(table, 0);

      // Whitespace-only cells should be trimmed to empty string
      expect(getColumnValues(table, 0)).toEqual(['   ', 'Alice', 'Bob']);
    });

    it('handles mixed content types in column', () => {
      const table = createTable({
        headers: ['Value'],
        rows: [['100'], ['text'], ['50']]
      });
      document.body.appendChild(table);
      makeSortable(table);

      clickHeader(table, 0);

      // First non-empty row is numeric, so should use numeric sort
      // "text" becomes 0 (NaN -> 0)
      expect(getColumnValues(table, 0)).toEqual(['text', '50', '100']);
    });

    it('handles null/undefined cell gracefully', () => {
      const table = createTable({
        headers: ['Name', 'Value'],
        rows: [['Alice', '10'], ['Bob', '20']]
      });
      // Remove a cell to create mismatch
      table.tBodies[0].rows[0].deleteCell(1);
      document.body.appendChild(table);

      expect(() => makeSortable(table)).not.toThrow();
      expect(() => clickHeader(table, 1)).not.toThrow();
    });
  });

  // ===========================================================================
  // Sort Bottom Rows Tests
  // ===========================================================================

  describe('sortbottom rows', () => {
    it('moves sortbottom rows to tfoot', () => {
      const table = createTable({
        headers: ['Name', 'Value'],
        rows: [['Alice', '10'], ['Total', '100'], ['Bob', '20']]
      });
      table.tBodies[0].rows[1].className = 'sortbottom';
      document.body.appendChild(table);

      makeSortable(table);

      expect(table.tFoot).not.toBeNull();
      expect(table.tFoot?.rows.length).toBe(1);
      expect(table.tFoot?.rows[0].cells[0].textContent).toBe('Total');
    });

    it('creates tfoot if not present', () => {
      const table = createTable({
        headers: ['Name'],
        rows: [['Alice'], ['Total']]
      });
      table.tBodies[0].rows[1].className = 'sortbottom';
      document.body.appendChild(table);

      expect(table.tFoot).toBeNull();

      makeSortable(table);

      expect(table.tFoot).not.toBeNull();
    });

    it('sortbottom rows stay at bottom during sort', () => {
      const table = createTable({
        headers: ['Name'],
        rows: [['Charlie'], ['Total'], ['Alice'], ['Bob']]
      });
      table.tBodies[0].rows[1].className = 'sortbottom';
      document.body.appendChild(table);
      makeSortable(table);

      clickHeader(table, 0);

      // Body should be sorted, tfoot should remain
      expect(getColumnValues(table, 0)).toEqual(['Alice', 'Bob', 'Charlie']);
      expect(table.tFoot?.rows[0].cells[0].textContent).toBe('Total');
    });
  });
});
