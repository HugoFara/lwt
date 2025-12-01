/**
 * Control the interactions for making an automated feed wizard.
 *
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @license Unlicense
 * @since   1.6.16-fork
 */

// Extend JQuery interface for get_adv_xpath
declare global {
  interface JQuery {
    get_adv_xpath(): void;
  }
}

/**
 * Execute an XPath expression and return matching elements as a jQuery object.
 * Supports pipe-separated multiple expressions (e.g., "//div | //span").
 *
 * @param expression - XPath expression to evaluate
 * @param context - Context node for evaluation (defaults to document)
 * @returns jQuery object containing matched elements
 */
function xpathQuery(expression: string, context: Node = document): JQuery<HTMLElement> {
  const results: HTMLElement[] = [];

  // Handle pipe-separated expressions (e.g., "//div[@id='x'] | //p[@class='y']")
  const expressions = expression.split(/\s*\|\s*/).filter(e => e.trim());

  for (const expr of expressions) {
    try {
      const xpathResult = document.evaluate(
        expr,
        context,
        null,
        XPathResult.ORDERED_NODE_SNAPSHOT_TYPE,
        null
      );
      for (let i = 0; i < xpathResult.snapshotLength; i++) {
        const node = xpathResult.snapshotItem(i);
        if (node instanceof HTMLElement && !results.includes(node)) {
          results.push(node);
        }
      }
    } catch {
      // Invalid XPath - skip this expression
    }
  }

  return $(results);
}

/**
 * Validate an XPath expression without executing it fully.
 *
 * @param expression - XPath expression to validate
 * @returns true if expression is valid, false otherwise
 */
function isValidXPath(expression: string): boolean {
  if (!expression || expression.trim() === '') {
    return false;
  }
  try {
    document.evaluate(expression, document, null, XPathResult.ANY_TYPE, null);
    return true;
  } catch {
    return false;
  }
}

// Export functions to window for use in PHP views
(window as any).xpathQuery = xpathQuery;
(window as any).isValidXPath = isValidXPath;

// Declare global filter_Array that may be set externally
declare const filter_Array: HTMLElement[];

// Type for lwt_form1
interface LwtForm1 extends HTMLFormElement {
  submit(): void;
}

declare const lwt_form1: LwtForm1;

/**
 * To be added to jQuery $.fn.get_adv_xpath, makes various unknown things.
 */
export function extend_adv_xpath(this: JQuery): void {
  $('#adv')
    .prepend(
      '<p style="text-align: left;">' +
        '<input style="vertical-align: middle; margin: 2px;" class="xpath" ' +
        'type="radio" name="xpath" value=\'\'>' +
          'custom: ' +
          '<input type="text" id="custom_xpath" name="custom_xpath" ' +
          'style="width:70%" ' +
          'onkeyup="var val=$(\'#custom_xpath\').val();var valid=isValidXPath(val)&&xpathQuery(val).length>0;if(!valid){$(this).parent().find(\'.xpath\').val(\'\');if($(this).parent().find(\':radio\').is(\':checked\'))$(\'#adv_get_button\').prop(\'disabled\', true);$(\'#custom_img\').attr(\'src\',\'icn/exclamation-red.png\');}else{$(this).parent().find(\'.xpath\').val(val);if($(this).parent().find(\':radio\').is(\':checked\'))$(\'#adv_get_button\').prop(\'disabled\', false);$(\'#custom_img\').attr(\'src\',\'icn/tick.png\');}return false;" onpaste="setTimeout(function(){var val=$(\'#custom_xpath\').val();var valid=isValidXPath(val)&&xpathQuery(val).length>0;if(!valid){$(\'#custom_xpath\').parent().find(\'.xpath\').val(\'\');if($(\'#custom_xpath\').parent().find(\':radio\').is(\':checked\'))$(\'#adv_get_button\').prop(\'disabled\', true);$(\'#custom_img\').attr(\'src\',\'icn/exclamation-red.png\');}else{$(\'#custom_xpath\').parent().find(\'.xpath\').val(val);if($(\'#custom_xpath\').parent().find(\':radio\').is(\':checked\'))$(\'#adv_get_button\').prop(\'disabled\', false);$(\'#custom_img\').attr(\'src\',\'icn/tick.png\');}}, 0);" value=\'\'>' +
          '</input>' +
        '<img id="custom_img" src="icn/exclamation-red.png" alt="-" />' +
        '</input>' +
      '</p>'
    );
  $('#adv').show();
  $('*').removeClass('lwt_marked_text');
  $('*[class=\'\']').removeAttr('class');
  const el = this[0];
  const selectedData = $('#mark_action :selected').data() as { tagName?: string } | undefined;
  const val1 = selectedData?.tagName?.toLowerCase() ?? '';
  let node_count = 0;
  let attr_v = '';
  let attr_p = '';
  let val_p = '';
  const attrs = el.attributes;
  for (let i = 0, l = attrs.length; i < l; i++) {
    if (attrs.item(i)!.nodeName === 'id') {
      const id_cont = attrs.item(i)!.nodeValue!.split(' ');
      for (let z = 0; z < id_cont.length; z++) {
        const val = '//*[@id[contains(concat(" ",normalize-space(.)," ")," ' + id_cont[z] + ' ")]]';
        $('#adv')
          .prepend(
            '<p style="text-align: left;">' +
              '<input style="vertical-align: middle; margin: 2px;" ' +
              'class="xpath" type="radio" name="xpath" value=\'' + val + '\'>' +
                'contains id: «' + id_cont[z] + '»' +
              '</input>' +
            '</p>'
          );
      }
    }
    if (attrs.item(i)!.nodeName === 'class') {
      const cl_cont = attrs.item(i)!.nodeValue!.split(' ');
      for (let z = 0; z < cl_cont.length; z++) {
        const val = '//*[@class[contains(concat(" ",normalize-space(.)," ")," ' + cl_cont[z] + ' ")]]';
        $('#adv')
          .prepend(
            '<p style="text-align: left;">' +
              '<input style="vertical-align: middle; margin: 2px;" ' +
              'class="xpath" type="radio" name="xpath" value=\'' + val + '\'>' +
                'contains class: «' + cl_cont[z] + '»' +
              '</input>' +
            '</p>'
          );
      }
    }
    if (i > 0) attr_v += ' and ';
    if (i === 0) attr_v += '[';
    attr_v += '@' + attrs.item(i)!.nodeName;
    attr_v += '="' + attrs.item(i)!.nodeValue + '"';
    if (i === (attrs.length - 1)) attr_v += ']';
  }
  this.parents().each(function () {
    const pa = $(this).get(0) as HTMLElement;
    const paAttrs = pa.attributes;
    for (let i = 0, l = paAttrs.length; i < l; i++) {
      if (node_count === 0) {
        if (paAttrs.item(i)!.nodeName === 'id') {
          const id_cont = paAttrs.item(i)!.nodeValue!.split(' ');
          for (let z = 0; z < id_cont.length; z++) {
            const val = '//*[@id[contains(concat(" ",normalize-space(.)," ")," ' + id_cont[z] + ' ")]]';
            $('#adv')
              .prepend(
                '<p style="text-align: left;">' +
                  '<input style="vertical-align: middle; margin: 2px;" ' +
                  'class="xpath" type="radio" name="xpath" value=\'' + val + '/' + val1 + '\'>' +
                    'parent contains id: «' + id_cont[z] + '»' +
                  '</input>' +
                '</p>'
              );
          }
        }
        if (paAttrs.item(i)!.nodeName === 'class') {
          const cl_cont = paAttrs.item(i)!.nodeValue!.split(' ');
          for (let z = 0; z < cl_cont.length; z++) {
            if (cl_cont[z] !== 'lwt_filtered_text') {
              const val = '//*[@class[contains(concat(" ",normalize-space(.)," ")," ' + cl_cont[z] + ' ")]]';
              $('#adv').prepend('<p style="text-align: left;"><input style="vertical-align: middle; margin: 2px;" class="xpath" type="radio" name="xpath" value=\'' + val + '/' + val1 + '\'>parent contains class: «' + cl_cont[z] + '»</input></p>');
            }
          }
        }
      }
      if (paAttrs.length > 1 || paAttrs.item(i)!.nodeValue !== 'lwt_filtered_text') {
        if (i > 0 && paAttrs.item(i)!.nodeValue !== 'lwt_filtered_text') attr_p += ' and ';
        if (i === 0) attr_p += '[';
        if (paAttrs.item(i)!.nodeValue !== 'lwt_filtered_text') attr_p += '@' + paAttrs.item(i)!.nodeName;
        if (paAttrs.item(i)!.nodeValue !== 'lwt_filtered_text') attr_p += '="' + paAttrs.item(i)!.nodeValue!.replace('lwt_filtered_text', '').trim() + '"';
        if (i === (paAttrs.length - 1)) attr_p += ']';
      }
    }
    val_p = pa.tagName.toLowerCase() + attr_p + '/' + val_p;
    attr_p = '';
    node_count++;
  });
  $('#adv').prepend('<p style="text-align: left;"><input style="vertical-align: middle; margin: 2px;" class="xpath" type="radio" name="xpath" value=\'/' + val_p + val1 + attr_v + '\'>all: « /' + val_p.replace('=""', '') + val1 + attr_v.replace('=""', '') + ' »</input></p>');
  $('#adv input[type="radio"]').each(function (z) {
    if (typeof z === 'undefined') z = 1;
    if (typeof $(this).attr('id') === 'undefined') {
      $(this).attr('id', 'rb_' + z++);
    }
    $(this).after('<label class="wrap_radio" for="' + $(this).attr('id') + '"><span></span></label>');
  });
}

export const lwt_feed_wiz_opt_inter = {
  clickHeader: function (event: JQuery.ClickEvent): boolean {
    if (!($(event.target).hasClass('lwt_selected_text'))) {
      if (!($(event.target).hasClass('lwt_filtered_text'))) {
        if ($(event.target).hasClass('lwt_marked_text')) {
          $('#mark_action').empty();
          $('*').removeClass('lwt_marked_text');
          $('*[class=\'\']').removeAttr('class');
          $('button[name="button"]').prop('disabled', true);
          $('<option/>').val('').text('[Click On Text]')
            .appendTo('#mark_action');
          return false;
        } else {
          $('*').removeClass('lwt_marked_text');
          $('#mark_action').empty();
          let filter_array: HTMLElement[] = [];
          $(event.target).parents(':not(html,body)').addBack()
            .each(function () {
              if (!($(this).hasClass('lwt_filtered_text'))) {
                filter_array = [];
                $(this).parents('.lwt_filtered_text').each(function () {
                  $(this).removeClass('lwt_filtered_text');
                  filter_array.push(this);
                });
                $('*[class=\'\']').removeAttr('class');
                const el = this as HTMLElement;
                const styleAttr = $(this).attr('style') as unknown as string | undefined;
                if (!styleAttr || styleAttr === '') $(this).removeAttr('style');
                const val1 = (el.tagName || '').toLowerCase();
                let attr = '';
                let attr_v = '';
                let attr_p = '';
                let attr_mode: number | string = '';
                let val_p = '';
                if ($('select[name="select_mode"]').val() !== '0') {
                  attr_mode = 5;
                } else if ($(this).attr('id')) {
                  attr_mode = 1;
                } else if ($(this).parent().attr('id')) {
                  attr_mode = 2;
                } else if ($(this).attr('class')) {
                  attr_mode = 3;
                } else if ($(this).parent().attr('class')) {
                  attr_mode = 4;
                } else {
                  attr_mode = 5;
                }
                const attrs = el.attributes;
                for (let i = 0, l = attrs.length; i < l; i++) {
                  if (attr_mode === 5 || (attrs.item(i)!.nodeName === 'class' && attr_mode !== 1) || (attrs.item(i)!.nodeName === 'id')) {
                    attr += attrs.item(i)!.nodeName;
                    attr += '="' + attrs.item(i)!.nodeValue + '" ';
                    if (i > 0) attr_v += ' and ';
                    attr_v += '@' + attrs.item(i)!.nodeName;
                    attr_v += '="' + attrs.item(i)!.nodeValue + '"';
                  }
                }
                attr = attr.replace('=""', '').trim();
                if (attr_v) attr_v = '[' + attr_v + ']';
                if (attr_mode !== 1 && attr_mode !== 3) {
                  const parentEl = $(this).parent().get(0) as HTMLElement;
                  const parentAttrs = parentEl.attributes;
                  for (let i = 0, l = parentAttrs.length; i < l; i++) {
                    if (attr_mode === 5 || (parentAttrs.item(i)!.nodeName === 'class' && attr_mode !== 2) || (parentAttrs.item(i)!.nodeName === 'id')) {
                      if (i > 0) attr_p += ' and ';
                      attr_p += '@' + parentAttrs.item(i)!.nodeName;
                      attr_p += '="' + parentAttrs.item(i)!.nodeValue + '"';
                    }
                  }
                  if (attr_p) attr_p = '[' + attr_p + ']';
                  val_p = ($(this).parent().get(0) as HTMLElement).tagName.toLowerCase() + attr_p + '§';
                }
                val_p = val_p.replace('body§', '');
                let attrsplit = attr.substr(0, 20);
                if (!(attrsplit === attr)) attrsplit = attrsplit + '... ';
                if (!(attrsplit === '')) attrsplit = ' ' + attrsplit;
                if (event.target === this) {
                  $('<option/>').val(
                    '//' + val_p.replace('=""', '')
                      .replace('[ and @', '[@') + val1 + attr_v.replace('=""', '')
                      .replace('[ and @', '[@')
                  ).text(
                    '<' + val1.replace('[ and @', '[@') +
                    attrsplit.replace('[ and @', '[@') + '>'
                  ).data(el)
                    .attr('selected', 'selected').prependTo('#mark_action');
                } else {
                  $('<option/>').val(
                    '//' + val_p.replace('=""', '')
                      .replace('[ and @', '[@') + val1 +
                    attr_v.replace('=""', '').replace('[ and @', '[@')
                  ).text(
                    '<' + val1.replace('[ and @', '[@') +
                    attrsplit.replace('[ and @', '[@') + '>'
                  ).data(el).prependTo('#mark_action');
                }
                for (const i in filter_array) {
                  $(filter_array[i]).addClass('lwt_filtered_text');
                }
              }
            });
          $('button[name="button"]').prop('disabled', false);
          let attr = $('#mark_action').val() as string;
          attr = attr.replace(/@/g, '').replace('//', '').replace(/ and /g, '][').replace('§', '>');
          filter_array = [];
          $(this).parents('.lwt_filtered_text').each(function () {
            $(this).removeClass('lwt_filtered_text');
            filter_array.push(this);
          });
          $(attr + ':not(.lwt_selected_text)').find('*:not(.lwt_selected_text)')
            .addBack().addClass('lwt_marked_text');
          for (const i in filter_array) {
            $(filter_array[i]).addClass('lwt_filtered_text');
          }
          return false;
        }
      } else {
        event.preventDefault();
      }
    } else {
      const selected_Array: HTMLElement[] = [];
      let filter_array: HTMLElement[] = [];
      $('.lwt_selected_text').each(function () {
        selected_Array.push(this);
      });
      $(event.target).parents('*').addBack().each(function () {
        if (!($(this).parent().hasClass('lwt_selected_text')) && $(this).hasClass('lwt_selected_text')) {
          if ($(this).hasClass('lwt_highlighted_text')) {
            $('*').removeClass('lwt_highlighted_text');
          } else {
            // eslint-disable-next-line @typescript-eslint/no-this-alias -- needed for closure scope
            const el = this;
            $('*').removeClass('lwt_selected_text');
            filter_array = [];
            $(this).parents('.lwt_filtered_text').each(function () {
              $(this).removeClass('lwt_filtered_text');
              filter_array.push(this);
            });
            $('*[class=\'\']').removeAttr('class');
            $('#lwt_sel li').each(function () {
              $('*').removeClass('lwt_highlighted_text');
              $(this).addClass('lwt_highlighted_text');
              xpathQuery($(this).text()).addClass('lwt_highlighted_text');
              if ($(el).hasClass('lwt_highlighted_text')) {
                return false;
              }
            });
            for (const i in selected_Array) {
              $(selected_Array[i]).addClass('lwt_selected_text');
            }
          }
        }
      });
      for (const i in filter_array) {
        $(filter_array[i]).addClass('lwt_filtered_text');
      }
      $('button[name="button"]').prop('disabled', true);
      $('#mark_action').empty();
      $('<option/>').val('').text('[Click On Text]').appendTo('#mark_action');
      return false;
    }
    return true;
  },

  highlightSelection: function (): string {
    let sel_array = '';
    $('#lwt_sel li').each(function () {
      if ($(this).hasClass('lwt_highlighted_text')) {
        xpathQuery($(this).text())
          .not($('#lwt_header').find('*').addBack())
          .addClass('lwt_highlighted_text').find('*').addBack()
          .addClass('lwt_selected_text');
      } else {
        sel_array += $(this).text() + ' | ';
      }
    });
    if (sel_array !== '') {
      xpathQuery(sel_array.replace(/ \| $/, '')).find('*')
        .addBack().not($('#lwt_header').find('*').addBack())
        .addClass('lwt_selected_text');
    }
    return sel_array;
  }
};

export const lwt_feed_wizard = {
  prepareInteractions: function (): void {
    if (
      $('#lwt_sel').html() === '' &&
      parseInt($('input[name=\'step\']').val() as string, 10) === 2
    ) {
      $('#next').prop('disabled', true);
    } else {
      $('#next').prop('disabled', false);
    }
    $('#lwt_last').css('margin-top', $('#lwt_header').height()!);
    $('#lwt_header').nextAll().on('click', lwt_feed_wiz_opt_inter.clickHeader);
    $('*').removeClass('lwt_filtered_text');
    $('*[class=\'\']').removeAttr('class');
    lwt_feed_wiz_opt_inter.highlightSelection();
    for (const i in filter_Array) {
      $(filter_Array[i]).addClass('lwt_filtered_text');
    }
    $('*[style=\'\']').removeAttr('style');
    $('#lwt_header select').wrap('<label class=\'wrap_select\'></label>');
    document.addEventListener('mouseup', () => {
      // Blur form elements on mouseup
      const selectors = [
        'select:not(:active)', 'button', 'input[type=button]',
        '.wrap_radio span', '.wrap_checkbox span'
      ];
      document.querySelectorAll<HTMLElement>(selectors.join(',')).forEach((el) => {
        el.blur();
      });
    });
  },

  deleteSelection: function (this: HTMLElement): boolean {
    $('*').removeClass('lwt_selected_text').removeClass('lwt_marked_text');
    $('*').removeClass('lwt_filtered_text');
    $('#lwt_header').nextAll().find('*').addBack().removeClass('lwt_highlighted_text');
    $(this).parent().remove();
    let sel_array = '';
    $('#lwt_sel li').each(function () {
      if ($(this).hasClass('lwt_highlighted_text')) {
        xpathQuery($(this).text()).not($('#lwt_header').find('*')
          .addBack()).addClass('lwt_highlighted_text').find('*').addBack()
          .addClass('lwt_selected_text');
      } else {
        sel_array += $(this).text() + ' | ';
      }
    });
    if (sel_array !== '') {
      xpathQuery(sel_array.replace(/ \| $/, '')).find('*')
        .addBack().not($('#lwt_header').find('*').addBack())
        .addClass('lwt_selected_text');
    }
    for (const i in filter_Array) {
      $(filter_Array[i]).addClass('lwt_filtered_text');
    }
    $('*[class=\'\']').removeAttr('class');
    $('*[style=\'\']').removeAttr('style');
    $('#lwt_last').css('margin-top', $('#lwt_header').height()!);
    if (
      $('#lwt_sel').html() === '' &&
      parseInt($('input[name=\'step\']').val() as string, 10) === 2
    ) {
      $('#next').prop('disabled', true);
    }
    return false;
  },

  changeXPath: function (this: HTMLElement): boolean {
    $('#adv_get_button').prop('disabled', false);
    $(this).parent().find('img').each(function () {
      const srcAttr = $(this).attr('src') as unknown as string | undefined;
      if (srcAttr === 'icn/exclamation-red.png') {
        $('#adv_get_button').prop('disabled', true);
      }
    });
    return false;
  },

  clickAdvGetButton: function (): boolean {
    $('*').removeClass('lwt_filtered_text');
    $('*[class=\'\']').removeAttr('class');
    if (typeof $('#adv :radio:checked').val() !== 'undefined') {
      $('#lwt_sel').append(
        '<li style=\'text-align: left\'>' +
        '<img class=\'delete_selection\' src=\'icn/cross.png\' ' +
        'title=\'Delete Selection\' alt=\'\' /> ' +
        $('#adv :radio:checked').val() +
        '</li>'
      );
      xpathQuery($('#adv :radio:checked').val() as string).find('*')
        .addBack().not($('#lwt_header').find('*').addBack())
        .addClass('lwt_selected_text');
      $('#next').prop('disabled', false);
    }
    $('#adv').hide();
    $('#lwt_last').css('margin-top', $('#lwt_header').height()!);
    for (const i in filter_Array) {
      $(filter_Array[i]).addClass('lwt_filtered_text');
    }
    return false;
  },

  clickSelectLi: function (this: HTMLElement): boolean {
    if ($(this).hasClass('lwt_highlighted_text')) {
      $('*').removeClass('lwt_highlighted_text');
    } else {
      const selected_Array: HTMLElement[] = [];
      $('.lwt_selected_text').each(function () {
        $(this).removeClass('lwt_selected_text');
        selected_Array.push(this);
      });
      $('*').removeClass('lwt_filtered_text');
      $('*').removeClass('lwt_highlighted_text');
      $('*[class=\'\']').removeAttr('class');
      $(this).addClass('lwt_highlighted_text');

      xpathQuery($(this).text()).not($('#lwt_header').find('*').addBack())
        .addClass('lwt_highlighted_text').find('*').addBack()
        .addClass('lwt_selected_text');

      for (const i in filter_Array) {
        $(filter_Array[i]).addClass('lwt_filtered_text');
      }
      for (const i in selected_Array) {
        $(selected_Array[i]).addClass('lwt_selected_text');
      }
    }
    return false;
  },

  changeMarkAction: function (): boolean {
    $('*').removeClass('lwt_marked_text');
    $('*[class=\'\']').removeAttr('class');
    let attr = $('#mark_action').val() as string;
    attr = attr.replace(/@/g, '').replace('//', '').replace(/ and /g, '][')
      .replace('§', '>');
    $('*').removeClass('lwt_filtered_text');
    $(attr).find('*:not(.lwt_selected_text)').addBack().addClass('lwt_marked_text');
    for (const i in filter_Array) {
      $(filter_Array[i]).addClass('lwt_filtered_text');
    }
    return false;
  },

  clickGetOrFilter: function (this: HTMLElement): boolean {
    $('*').removeClass('lwt_marked_text');
    if ($('select[name=\'select_mode\']').val() === 'adv') {
      $('#adv p').remove();
      $('*[style=\'\']').removeAttr('style');
      $('#adv_get_button').prop('disabled', true);
      const selectedElement = $('#mark_action :selected').data() as { get_adv_xpath?: () => void } | undefined;
      if (selectedElement?.get_adv_xpath) {
        selectedElement.get_adv_xpath();
      }
    } else {
      $('#next').prop('disabled', false);
      let attr = $('#mark_action').val() as string;
      attr = attr.replace(/@/g, '').replace('//', '').replace(/ and /g, '][')
        .replace('§', '>');
      const local_filter_Array: HTMLElement[] = [];
      $('.lwt_filtered_text').each(function () {
        $(this).removeClass('lwt_filtered_text');
        local_filter_Array.push(this);
      });
      $('*').removeClass('lwt_filtered_text');
      $(attr).find('*').addBack().addClass('lwt_selected_text');
      for (const i in local_filter_Array) {
        $(local_filter_Array[i]).addClass('lwt_filtered_text');
      }
      $('#lwt_sel').append(
        '<li style=\'text-align: left\'>' +
        '<img class=\'delete_selection\' src=\'icn/cross.png\' ' +
        'title=\'Delete Selection\' alt=\'' +
        $('#mark_action').val() + '\' /> ' +
        ($('#mark_action').val() as string).replace('§', '/') +
        '</li>'
      );
    }
    $(this).prop('disabled', true);
    $('#mark_action').empty();
    $('<option/>').val('').text('[Click On Text]').appendTo('#mark_action');
    $('#lwt_last').css('margin-top', $('#lwt_header').height()!);
    return false;
  },

  clickNextButton: function (): boolean {
    $('#article_tags,#filter_tags').val($('#lwt_sel').html()!)
      .prop('disabled', false);
    const html = $('#lwt_sel li').map(function () {
      return $(this).text();
    }).get().join(' | ');
    $('input[name=\'html\']').val(html);
    let val = parseInt($('input[name=\'step\']').val() as string, 10);
    if (val === 2) {
      $('input[name=\'html\']').attr('name', 'article_selector');
      $('select[name=\'NfArticleSection\'] option').each(function () {
        const art_sec = $('#lwt_sel li').map(function () {
          return $(this).text();
        }).get().join(' | ');
        $(this).val(art_sec);
      });
    }
    $('input[name=\'step\']').val(++val);
    lwt_form1.submit();
    return false;
  },

  changeHostStatus: function (this: HTMLElement): boolean {
    const host_status = $(this).val() as string;
    const current_host = $('input[name=\'host_name\']').val() as string;
    $('select[name=\'selected_feed\'] option').each(function () {
      const opt_str = $(this).text();
      const host_name = opt_str.replace(/[▸-][0-9\s]*[★☆-][\s]*host:/, '');
      if (host_name.trim() === current_host.trim()) {
        $(this).text(
          opt_str.replace(
            /([▸-][0-9\s]*?)\s[★☆-]\s(.*)/,
            '$1 ' + host_status.trim() + ' $2'
          )
        );
      }
    });
    return false;
  }
};

// Set up event handlers using vanilla JS event delegation
document.addEventListener('click', (e) => {
  const target = e.target as HTMLElement;

  // .delete_selection click
  if (target.classList.contains('delete_selection')) {
    lwt_feed_wizard.deleteSelection.call(target);
    return;
  }

  // #adv_get_button click
  if (target.id === 'adv_get_button') {
    lwt_feed_wizard.clickAdvGetButton();
    return;
  }

  // #lwt_sel li click
  const liTarget = target.closest('#lwt_sel li') as HTMLElement | null;
  if (liTarget) {
    lwt_feed_wizard.clickSelectLi.call(liTarget);
    return;
  }

  // #get_button or #filter_button click
  if (target.id === 'get_button' || target.id === 'filter_button') {
    lwt_feed_wizard.clickGetOrFilter.call(target);
    return;
  }

  // #next button click
  if (target.id === 'next') {
    lwt_feed_wizard.clickNextButton();
    return;
  }
});

document.addEventListener('change', (e) => {
  const target = e.target as HTMLElement;

  // .xpath change
  if (target.classList.contains('xpath')) {
    lwt_feed_wizard.changeXPath.call(target);
    return;
  }

  // #mark_action change
  if (target.id === 'mark_action') {
    lwt_feed_wizard.changeMarkAction();
    return;
  }

  // #host_status change
  if (target.id === 'host_status') {
    lwt_feed_wizard.changeHostStatus.call(target);
    return;
  }
});
