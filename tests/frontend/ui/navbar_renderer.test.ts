/**
 * Tests for the global navbar renderer (shared/components/navbar_renderer.ts),
 * which replaces the server-rendered PageLayoutHelper::buildNavbar() with a
 * client-built HTML string sourced from GET /api/v1/navbar. The same renderer
 * feeds the PHP web app and the bundled Lukaisu client.
 */
import { describe, it, expect } from 'vitest';
import { renderNavbar, type NavbarData } from '@shared/components/navbar_renderer';

function data(overrides: Partial<NavbarData> = {}): NavbarData {
  return {
    basePath: '',
    logoUrl: '/assets/images/lwt_icon_48.png',
    languages: [
      { id: 1, name: 'Spanish' },
      { id: 2, name: 'French' },
    ],
    currentLanguageId: 2,
    isMultiUser: false,
    showAdminItems: true,
    theme: { mode: 'light', counterpart: 'dist/themes/Dark/', current: '', auto: true },
    ...overrides,
  };
}

describe('renderNavbar', () => {
  it('renders a navbar nav element with the Alpine component', () => {
    const html = renderNavbar(data());
    expect(html).toContain('<nav class="navbar is-light"');
    expect(html).toContain('role="navigation"');
    expect(html).toContain('x-data="navbar()"');
  });

  it('renders the three primary sections with their add buttons', () => {
    const html = renderNavbar(data());
    expect(html).toContain('href="/texts"');
    expect(html).toContain('href="/texts/new"');
    expect(html).toContain('href="/words"');
    expect(html).toContain('href="/words/new"');
    expect(html).toContain('href="/languages"');
    expect(html).toContain('href="/languages/new"');
  });

  it('highlights the active section from currentPage', () => {
    expect(renderNavbar(data(), 'texts')).toMatch(
      /class="button is-small is-active" href="\/texts"/
    );
    expect(renderNavbar(data(), 'term-tags')).toMatch(
      /class="button is-small is-active" href="\/words"/
    );
    expect(renderNavbar(data(), 'language-edit')).toMatch(
      /class="button is-small is-active" href="\/languages"/
    );
  });

  it('lists languages and marks the current one selected', () => {
    const html = renderNavbar(data());
    expect(html).toContain('<option value="1">Spanish</option>');
    expect(html).toContain('<option value="2" selected>French</option>');
    expect(html).toContain('switchLanguage($event)');
  });

  it('omits the language selector when there are no languages', () => {
    const html = renderNavbar(data({ languages: [] }));
    expect(html).not.toContain('switchLanguage($event)');
  });

  it('shows a moon toggle in light mode and a sun toggle in dark mode', () => {
    expect(renderNavbar(data())).toContain('data-lucide="moon"');
    expect(renderNavbar(data())).toContain('data-theme-mode="light"');
    const dark = renderNavbar(data({ theme: { mode: 'dark', counterpart: '', current: 'd', auto: false } }));
    expect(dark).toContain('data-lucide="sun"');
    expect(dark).toContain('data-theme-mode="dark"');
    expect(dark).toContain('data-auto-theme="false"');
  });

  it('hides profile/logout and shows admin items for a single-user install', () => {
    const html = renderNavbar(data({ isMultiUser: false, showAdminItems: true }));
    expect(html).not.toContain('href="/logout"');
    expect(html).not.toContain('href="/profile"');
    expect(html).toContain('href="/admin/backup"');
    expect(html).toContain('href="/admin/settings"');
  });

  it('shows profile/logout for a multi-user install and hides admin items for non-admins', () => {
    const html = renderNavbar(data({ isMultiUser: true, showAdminItems: false }));
    expect(html).toContain('href="/logout"');
    expect(html).toContain('@click.prevent="logout()"');
    expect(html).toContain('href="/profile"');
    expect(html).not.toContain('href="/admin/backup"');
  });

  it('marks the user dropdown active on preferences/admin pages', () => {
    expect(renderNavbar(data(), 'preferences')).toContain('has-dropdown is-active');
    expect(renderNavbar(data(), 'settings')).toContain('has-dropdown is-active');
    expect(renderNavbar(data(), 'texts')).not.toContain('has-dropdown is-active');
  });

  it('prefixes hrefs with the install base path', () => {
    const html = renderNavbar(data({ basePath: '/lwt' }));
    expect(html).toContain('href="/lwt/texts"');
    expect(html).toContain('href="/lwt/words"');
    expect(html).toContain('href="/lwt/"');
  });

  it('escapes language names to prevent HTML injection', () => {
    const html = renderNavbar(data({ languages: [{ id: 9, name: '<b>x</b>' }], currentLanguageId: 9 }));
    expect(html).not.toContain('<b>x</b>');
    expect(html).toContain('&lt;b&gt;x&lt;/b&gt;');
  });
});
