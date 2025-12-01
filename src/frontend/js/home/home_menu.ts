/**
 * Home page collapsible menu functionality.
 *
 * Handles expanding and collapsing menu sections on the home page.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

const STORAGE_KEY = 'lwt_collapsed_menus';

/**
 * Get the list of collapsed menu IDs from localStorage.
 */
function getCollapsedMenus(): string[] {
  try {
    const stored = localStorage.getItem(STORAGE_KEY);
    return stored ? JSON.parse(stored) : [];
  } catch {
    return [];
  }
}

/**
 * Save the list of collapsed menu IDs to localStorage.
 */
function saveCollapsedMenus(menuIds: string[]): void {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(menuIds));
  } catch {
    // localStorage not available
  }
}

/**
 * Toggle a menu's collapsed state.
 */
function toggleMenu(menu: HTMLElement): void {
  const menuId = menu.dataset.menuId;
  if (!menuId) return;

  const isCollapsed = menu.classList.toggle('collapsed');

  const collapsedMenus = getCollapsedMenus();
  if (isCollapsed) {
    if (!collapsedMenus.includes(menuId)) {
      collapsedMenus.push(menuId);
    }
  } else {
    const index = collapsedMenus.indexOf(menuId);
    if (index > -1) {
      collapsedMenus.splice(index, 1);
    }
  }
  saveCollapsedMenus(collapsedMenus);
}

/**
 * Initialize collapsible menus on the home page.
 *
 * By default, all menus except Languages are collapsed.
 */
export function initCollapsibleMenus(): void {
  const menus = document.querySelectorAll<HTMLElement>('.home-menu-container .menu[data-menu-id]');

  if (menus.length === 0) return;

  // Check if this is the first visit (no stored preference)
  const storedMenus = localStorage.getItem(STORAGE_KEY);
  const isFirstVisit = storedMenus === null;

  if (isFirstVisit) {
    // First visit: collapse all except Languages
    const defaultCollapsed: string[] = [];
    menus.forEach(menu => {
      const menuId = menu.dataset.menuId;
      if (menuId && menuId !== 'languages') {
        defaultCollapsed.push(menuId);
        menu.classList.add('collapsed');
      }
    });
    saveCollapsedMenus(defaultCollapsed);
  } else {
    // Restore collapsed state from localStorage
    const collapsedMenus = getCollapsedMenus();
    menus.forEach(menu => {
      const menuId = menu.dataset.menuId;
      if (menuId && collapsedMenus.includes(menuId)) {
        menu.classList.add('collapsed');
      }
    });
  }

  // Add click handlers to menu headers
  menus.forEach(menu => {
    const header = menu.querySelector('.menu-header');
    if (header) {
      header.addEventListener('click', () => toggleMenu(menu));
    }
  });
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  initCollapsibleMenus();
});
