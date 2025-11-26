/**
 * Media Selection - Media file selection and path handling
 *
 * @license unlicense
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @since   1.6.16-fork
 */

import $ from 'jquery';

interface MediaSelectResponse {
  error?: 'not_a_directory' | 'does_not_exist' | string;
  base_path?: string;
  paths?: string[];
  folders?: string[];
}

/**
 * Return an HTML group of options to add to a select field.
 *
 * @param paths     All paths (files and folders)
 * @param folders   Folders paths, should be a subset of paths
 * @param base_path Base path for LWT to append
 *
 * @returns List of options to append to the select.
 *
 * @since 2.9.1-fork Base path is no longer used
 */
export function select_media_path(
  paths: string[],
  folders: string[],
  base_path: string
): HTMLOptionElement[] {
  const options: HTMLOptionElement[] = [];
  let temp_option = document.createElement('option');
  temp_option.value = '';
  temp_option.text = '[Choose...]';
  options.push(temp_option);
  for (let i = 0; i < paths.length; i++) {
    temp_option = document.createElement('option');
    if (folders.includes(paths[i])) {
      temp_option.setAttribute('disabled', 'disabled');
      temp_option.text = '-- Directory: ' + paths[i] + '--';
    } else {
      temp_option.value = paths[i];
      temp_option.text = paths[i];
    }
    options.push(temp_option);
  }
  return options;
}

/**
 * Process the received data from media selection query
 *
 * @param data Received data as a JSON object
 */
export function media_select_receive_data(data: MediaSelectResponse): void {
  $('#mediaSelectLoadingImg').css('display', 'none');
  if (data.error !== undefined) {
    let msg: string;
    if (data.error === 'not_a_directory') {
      msg = '[Error: "../' + data.base_path + '/media" exists, but it is not a directory.]';
    } else if (data.error === 'does_not_exist') {
      msg = '[Directory "../' + data.base_path + '/media" does not yet exist.]';
    } else {
      msg = '[Unknown error!]';
    }
    $('#mediaSelectErrorMessage').text(msg);
    $('#mediaSelectErrorMessage').css('display', 'inherit');
  } else {
    const options = select_media_path(data.paths || [], data.folders || [], data.base_path || '');
    $('#mediaselect select').empty();
    for (let i = 0; i < options.length; i++) {
      $('#mediaselect select').append(options[i]);
    }
    $('#mediaselect select').css('display', 'inherit');
  }
}

/**
 * Perform an AJAX query to retrieve and display the media files path.
 */
export function do_ajax_update_media_select(): void {
  $('#mediaSelectErrorMessage').css('display', 'none');
  $('#mediaselect select').css('display', 'none');
  $('#mediaSelectLoadingImg').css('display', 'inherit');
  $.getJSON(
    'api.php/v1/media-files',
    {},
    media_select_receive_data
  );
}

// Expose globally for backward compatibility with PHP templates
if (typeof window !== 'undefined') {
  const w = window as unknown as Record<string, unknown>;
  w.select_media_path = select_media_path;
  w.media_select_receive_data = media_select_receive_data;
  w.do_ajax_update_media_select = do_ajax_update_media_select;
}
