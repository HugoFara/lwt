/**
 * Tests for media_selection.ts - Media file selection and path handling
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  createMediaPathOptions,
  handleMediaSelectResponse,
  refreshMediaSelect
} from '../../../src/frontend/js/media/media_selection';

describe('media_selection.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // createMediaPathOptions Tests
  // ===========================================================================

  describe('createMediaPathOptions', () => {
    it('returns options array with Choose option first', () => {
      const options = createMediaPathOptions([], []);

      expect(options.length).toBe(1);
      expect(options[0].value).toBe('');
      expect(options[0].text).toBe('[Choose...]');
    });

    it('creates options for file paths', () => {
      const paths = ['audio1.mp3', 'audio2.mp3', 'video.mp4'];
      const folders: string[] = [];

      const options = createMediaPathOptions(paths, folders);

      expect(options.length).toBe(4); // 1 Choose + 3 files
      expect(options[1].value).toBe('audio1.mp3');
      expect(options[1].text).toBe('audio1.mp3');
      expect(options[2].value).toBe('audio2.mp3');
      expect(options[3].value).toBe('video.mp4');
    });

    it('marks folders as disabled with directory prefix', () => {
      const paths = ['folder1', 'file.mp3', 'folder2'];
      const folders = ['folder1', 'folder2'];

      const options = createMediaPathOptions(paths, folders);

      expect(options.length).toBe(4);

      // First path is folder
      expect(options[1].disabled).toBe(true);
      expect(options[1].text).toContain('Directory: folder1');

      // Second path is file
      expect(options[2].disabled).toBe(false);
      expect(options[2].value).toBe('file.mp3');

      // Third path is folder
      expect(options[3].disabled).toBe(true);
      expect(options[3].text).toContain('Directory: folder2');
    });

    it('handles mixed files and folders correctly', () => {
      const paths = [
        'subfolder',
        'subfolder/audio.mp3',
        'video.mp4',
        'another_folder'
      ];
      const folders = ['subfolder', 'another_folder'];

      const options = createMediaPathOptions(paths, folders);

      expect(options[1].disabled).toBe(true);  // subfolder
      expect(options[2].disabled).toBe(false); // subfolder/audio.mp3
      expect(options[2].value).toBe('subfolder/audio.mp3');
      expect(options[3].disabled).toBe(false); // video.mp4
      expect(options[4].disabled).toBe(true);  // another_folder
    });

    it('returns HTMLOptionElement instances', () => {
      const options = createMediaPathOptions(['test.mp3'], []);

      expect(options[0]).toBeInstanceOf(HTMLOptionElement);
      expect(options[1]).toBeInstanceOf(HTMLOptionElement);
    });
  });

  // ===========================================================================
  // handleMediaSelectResponse Tests
  // ===========================================================================

  describe('handleMediaSelectResponse', () => {
    beforeEach(() => {
      document.body.innerHTML = `
        <img id="mediaSelectLoadingImg" style="display: block" />
        <div id="mediaSelectErrorMessage" style="display: none"></div>
        <div id="mediaselect">
          <select style="display: none"></select>
        </div>
      `;
    });

    it('hides loading image on success', () => {
      handleMediaSelectResponse({
        paths: ['file.mp3'],
        folders: [],
        base_path: ''
      });

      const loadingImg = document.getElementById('mediaSelectLoadingImg')!;
      expect(loadingImg.style.display).toBe('none');
    });

    it('populates select with options on success', () => {
      handleMediaSelectResponse({
        paths: ['audio1.mp3', 'audio2.mp3'],
        folders: [],
        base_path: ''
      });

      const select = document.querySelector('#mediaselect select')!;
      expect(select.children.length).toBe(3); // Choose + 2 files
      expect(select.getAttribute('style')).toContain('display');
    });

    it('displays error for not_a_directory error', () => {
      handleMediaSelectResponse({
        error: 'not_a_directory',
        base_path: 'testpath'
      });

      const errorEl = document.getElementById('mediaSelectErrorMessage')!;
      expect(errorEl.style.display).not.toBe('none');
      expect(errorEl.textContent).toContain('not a directory');
      expect(errorEl.textContent).toContain('testpath');
    });

    it('displays error for does_not_exist error', () => {
      handleMediaSelectResponse({
        error: 'does_not_exist',
        base_path: 'mypath'
      });

      const errorEl = document.getElementById('mediaSelectErrorMessage')!;
      expect(errorEl.style.display).not.toBe('none');
      expect(errorEl.textContent).toContain('does not yet exist');
      expect(errorEl.textContent).toContain('mypath');
    });

    it('displays generic error for unknown error', () => {
      handleMediaSelectResponse({
        error: 'something_else'
      });

      const errorEl = document.getElementById('mediaSelectErrorMessage')!;
      expect(errorEl.textContent).toContain('Unknown error');
    });

    it('clears previous options before adding new ones', () => {
      // Add some initial options
      const select = document.querySelector('#mediaselect select')!;
      select.innerHTML = '<option>Old option</option>';

      handleMediaSelectResponse({
        paths: ['new.mp3'],
        folders: [],
        base_path: ''
      });

      expect(select.children.length).toBe(2); // Choose + 1 new file
      expect(select.querySelector('option[value=""]')).not.toBeNull(); // Choose option
    });

    it('handles empty paths array', () => {
      handleMediaSelectResponse({
        paths: [],
        folders: [],
        base_path: ''
      });

      const select = document.querySelector('#mediaselect select')!;
      expect(select.children.length).toBe(1); // Just Choose
    });

    it('handles undefined paths and folders', () => {
      handleMediaSelectResponse({
        base_path: ''
      });

      const select = document.querySelector('#mediaselect select')!;
      expect(select.children.length).toBe(1); // Just Choose
    });
  });

  // ===========================================================================
  // refreshMediaSelect Tests
  // ===========================================================================

  describe('refreshMediaSelect', () => {
    beforeEach(() => {
      document.body.innerHTML = `
        <img id="mediaSelectLoadingImg" style="display: none" />
        <div id="mediaSelectErrorMessage" style="display: block">Previous error</div>
        <div id="mediaselect">
          <select style="display: block">
            <option>Existing option</option>
          </select>
        </div>
      `;
    });

    it('hides error message and select, shows loading image', async () => {
      // Mock fetch
      const fetchSpy = vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        json: () => Promise.resolve({ paths: [], folders: [], base_path: '' })
      } as Response);

      refreshMediaSelect();

      const errorEl = document.getElementById('mediaSelectErrorMessage')!;
      const selectEl = document.querySelector('#mediaselect select') as HTMLSelectElement;
      const loadingImg = document.getElementById('mediaSelectLoadingImg')!;

      expect(errorEl.style.display).toBe('none');
      expect(selectEl.style.display).toBe('none');
      expect(loadingImg.style.display).not.toBe('none');

      fetchSpy.mockRestore();
    });

    it('makes fetch call to correct endpoint', async () => {
      const fetchSpy = vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        json: () => Promise.resolve({ paths: [], folders: [], base_path: '' })
      } as Response);

      refreshMediaSelect();

      expect(fetchSpy).toHaveBeenCalledWith('/api/v1/media-files');

      fetchSpy.mockRestore();
    });

    it('calls handleMediaSelectResponse with response data', async () => {
      const mockData = { paths: ['test.mp3'], folders: [], base_path: '' };
      const fetchSpy = vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        json: () => Promise.resolve(mockData)
      } as Response);

      refreshMediaSelect();

      // Wait for promises to resolve
      await new Promise(resolve => setTimeout(resolve, 0));

      const select = document.querySelector('#mediaselect select')!;
      // After receiving data, select should be populated
      expect(select.children.length).toBe(2); // Choose + test.mp3

      fetchSpy.mockRestore();
    });

    it('handles fetch error gracefully', async () => {
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
      const fetchSpy = vi.spyOn(globalThis, 'fetch').mockRejectedValue(new Error('Network error'));

      refreshMediaSelect();

      // Wait for promises to resolve
      await new Promise(resolve => setTimeout(resolve, 0));

      expect(consoleSpy).toHaveBeenCalledWith('Failed to fetch media files:', expect.any(Error));

      const errorEl = document.getElementById('mediaSelectErrorMessage')!;
      expect(errorEl.textContent).toContain('Unknown error');

      fetchSpy.mockRestore();
      consoleSpy.mockRestore();
    });
  });

  // ===========================================================================
  // Window Export Tests
  // ===========================================================================

  describe('window exports', () => {
    it('exports createMediaPathOptions to window', () => {
      expect((window as any).createMediaPathOptions).toBe(createMediaPathOptions);
    });

    it('exports handleMediaSelectResponse to window', () => {
      expect((window as any).handleMediaSelectResponse).toBe(handleMediaSelectResponse);
    });

    it('exports refreshMediaSelect to window', () => {
      expect((window as any).refreshMediaSelect).toBe(refreshMediaSelect);
    });
  });
});
