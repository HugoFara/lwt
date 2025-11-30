<?php

/**
 * Mobile Index View - Main mobile interface page
 *
 * Variables expected:
 * - $languages: Array of language records
 * - $version: Application version string
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 *
 * @psalm-suppress UndefinedVariable - Variables are set by the including controller
 */

namespace Lwt\Views\Mobile;

/** @var array $languages */
/** @var string $version */

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<meta http-equiv="content-language" content="en" />
<title>Mobile LWT</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
<link rel="apple-touch-icon" href="/assets/images/apple-touch-icon-57x57.png" />
<link rel="apple-touch-icon" sizes="72x72" href="/assets/images/apple-touch-icon-72x72.png" />
<link rel="apple-touch-icon" sizes="114x114" href="/assets/images/apple-touch-icon-114x114.png" />
<link rel="apple-touch-startup-image" href="/assets/images/apple-touch-startup.png">
<meta name="apple-touch-fullscreen" content="YES" />
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="apple-mobile-web-app-status-bar-style" content="black" />
<link rel="stylesheet" type="text/css" href="/assets/vendor/iui/iui.css" media="screen" />
<link rel="stylesheet" type="text/css" href="/assets/css/mobile.css" media="screen" />
<script type="text/javascript" src="/assets/vendor/iui/iui.js" charset="utf-8"></script>
</head>
<body>

<div class="toolbar">
    <h1 id="pageTitle"></h1>
    <a id="backButton" class="button" href="#"></a>
    <a class="button" href="/mobile" target="_self">Home</a>
</div>

<ul id="home" title="Mobile LWT" selected="true">
    <li class="group">Languages</li>
    <?php foreach ($languages as $language): ?>
    <li><a href="/mobile?action=2&amp;lang=<?php echo $language['id']; ?>"><?php echo tohtml($language['name']); ?></a></li>
    <?php endforeach; ?>
    <li class="group">Other</li>
    <li><a href="#about">About</a></li>
    <li><a href="/" target="_self">LWT Standard Version</a></li>
</ul>

<div id="about" title="About">
    <p style="text-align:center; margin-top:50px;">
This is "Learning With Texts" (LWT) for Mobile Devices<br />Version <?php echo $version; ?><br /><br />"Learning with Texts" (LWT) is released into the Public Domain. This applies worldwide. In case this is not legally possible, any entity is granted the right to use this work for any purpose, without any conditions, unless such conditions are required by law.<br /><br /> Developed with the <a href="http://iui-js.org" target="_self">iUI Framework</a>.<br /><br /><b>Back to<br/><a href="/" target="_self">LWT Standard Version</a></b>
    </p>
</div>

<div id="notyetimpl" title="Sorry...">
    <p style="text-align:center; margin-top:50px;">Not yet implemented!</p>
</div>

</body>
</html>
