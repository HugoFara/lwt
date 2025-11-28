<?php

/**
 * Feed Edit Text Form Footer View
 *
 * Renders the submit button and JavaScript for the edit text form.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Views\Feed;

?>
   <input id="markaction" type="submit" value="Save" />
   <input type="button" value="Cancel" onclick="location.href='/feeds';" />
   <input type="hidden" name="checked_feeds_save" value="1" />
   </form>

   <script type="text/javascript">
    $(document).ready(function() {
        const firstTable = document.querySelector('table');
        if (firstTable) {
            firstTable.scrollIntoView({ behavior: 'instant', block: 'start' });
        }

        // Initialize Tagify on feed tag inputs
        document.querySelectorAll('ul[name^="feed"]').forEach(function(ulElement) {
            const fieldName = ulElement.getAttribute('name');

            // Extract existing tags from LI elements
            const existingTags = [];
            ulElement.querySelectorAll('li').forEach(function(li) {
                const text = li.textContent?.trim();
                if (text) {
                    existingTags.push(text);
                }
            });

            // Create input element to replace the UL
            const input = document.createElement('input');
            input.type = 'text';
            input.name = fieldName;
            input.className = 'tagify-feed-input';
            input.value = existingTags.join(', ');
            input.dataset.feedIndex = fieldName.match(/feed\[(\d+)\]/)?.[1] || '';

            // Replace UL with input
            ulElement.replaceWith(input);

            // Initialize Tagify
            const tagify = new Tagify(input, {
                whitelist: TEXTTAGS || [],
                dropdown: {
                    enabled: 1,
                    maxItems: 20,
                    closeOnSelect: true,
                    highlightFirst: true
                },
                duplicates: false
            });

            // Add existing tags
            if (existingTags.length > 0) {
                tagify.addTags(existingTags);
            }

            // Store tagify instance on the input for later access
            input._tagify = tagify;
        });

        // Handle checkbox changes for enabling/disabling feed forms
        $('input[type="checkbox"]').change(function(){
            const feedIndex = $(this).val();
            const feed = '[name^=feed\\['+ feedIndex +'\\]';
            const tagifyInput = document.querySelector('.tagify-feed-input[data-feed-index="' + feedIndex + '"]');
            const tagify = tagifyInput?._tagify;

            if(this.checked){
                $(feed+']').prop('disabled', false);
                $(feed+'\\[TxTitle\\]],'+feed+'\\[TxText\\]]').addClass("notempty");
                if (tagify) {
                    tagify.setDisabled(false);
                }
            } else {
                $(feed+']').prop('disabled', true).removeClass("notempty");
                if (tagify) {
                    tagify.setDisabled(true);
                }
            }
        });
    });
   </script>
