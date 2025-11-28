<?php

/**
 * Third-Party Voice API Help View
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

namespace Lwt\Views\Language;

?>
<h2>Third-Party Voice API</h2>
<p>
    You can customize the voice API using an external service.
    You have to use the following JSON format.
</p>
<pre
style="background-color: #f0f0f0; padding: 10px; border: 1px solid #ccc;"
><code lang="json"
>{
    "input": ...,
    "options": ...
}</code></pre>
<p>
    LWT will insert text in <code>lwt_term</code> (required),
    you can specify the language with <code>lwt_lang</code> (optional).<br />
    If you need help, suggestions or want to see some demo, please go to
    <a href="https://github.com/HugoFara/lwt/discussions/174">discussion 174</a>.
</p>
