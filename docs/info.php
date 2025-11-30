<?php
/**
 * \file
 * \brief LWT Information / Help
 *
 * PHP version 8.1
 *
 * @category Documentation
 * @package Lwt_Documentation
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/html/info_8php.html
 * @since   1.0.3
 */

require __DIR__ . '/../src/backend/Core/version.php';
require_once __DIR__ . '/../src/tools/markdown_converter.php';

use function Lwt\Core\get_version;

?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />

		<meta http-equiv="content-language" content="en-US" />
		<meta http-equiv="cache-control" content="no-cache" />
		<meta http-equiv="pragma" content="no-cache" />
		<meta http-equiv="expires" content="0" />
		<meta name="keywords" content="Language Learning Texts LWT Software Freeware LingQ Alternative AJATT Khatzumoto MCD MCDs Massive Context Cloze Deletion Cards Tool Stephen Krashen Second Language Acquisition Steve Kaufmann" />
		<meta name="description" content="Learning with Texts (LWT) is a tool for Language Learning, inspired by Stephen Krashen's principles in Second Language Acquisition, Steve Kaufmann's LingQ System and ideas (e. g. Massive-Context Cloze Deletion Cards = MCDs) from Khatzumoto, published at AJATT - All Japanese All The Time. It is an Alternative to LingQ, 100 % free, Open Source, and in the Public Domain." />
		<meta name="revisit-after" content="2 days" />
		<meta name="viewport" content="width=1280, user-scalable=yes" />
		<link rel="apple-touch-icon" href="../assets/images/apple-touch-icon-57x57.png" />
		<link rel="apple-touch-icon" sizes="72x72" href="../assets/images/apple-touch-icon-72x72.png" />
		<link rel="apple-touch-icon" sizes="114x114" href="../assets/images/apple-touch-icon-114x114.png" />
		<link rel="apple-touch-startup-image" href="../assets/images/apple-touch-startup.png" />
		<style type="text/css">
			@import url(../assets/css/styles.css);
			.hidden {display:none;}
		</style>
		<script type="text/javascript" src="../assets/js/jquery.js"></script>
 		<script type="text/javascript">
			/**
			 * Perform an AJAX query to get the current theme style sheet.
			 * 
			 * @since 2.9.0 Function is modified to use JSON with the REST API.
			 */
			function ajaxGetTheme () {
				$.getJSON(
					'../api.php/v1/settings/theme-path',
					{
						path: '../css/styles.css' 
					},
					function (data) {
						if ("error" in data)
							return;
						const path = data["theme_path"].trim();
						if (path.endsWith("styles.css")) {
							$('style').html(
								"@import url(../" + path + ");"
							);
						}
					}
				)
				.always(function () {
					$('html').show();
				});
			}

			/**
			 * Jump to a specific element
			 */
			function topicJump (qm) { 
				const val = qm.options[qm.selectedIndex].value;
				if (val != '') {
					location.href = '#' + val;
				}
				qm.selectedIndex = 0;
			}

			$('html').addClass('hidden');

			$(document).ready(ajaxGetTheme);

		</script>
		<title>
			Learning with Texts :: Help/Information
		</title>
	</head>

	<body>
		<div id="floatdiv">
			<a href="#">↑ TOP ↑</a>
			<div>&nbsp;</div>
			<a href="#getting-started">Getting Started</a>
			<a href="#current">Curr. Version </a>
			<a href="#install">Installation</a>
			<a href="#postinstall">Post-<wbr>Installation</a>
			<a href="#features">Features</a>
			<a href="#newfeatures">New Features</a>
			<a href="#screencasts">Screencasts</a>
			<a href="#links">Links</a>
			<a href="#restrictions">Restrictions</a>
			<a href="#UNLICENSE">License</a>
			<a href="#thirdpartylicenses">Third Party</a>
			<a href="#learn">How to learn</a>
			<a href="#howto">How to use</a>
			<a href="#faq">Q & A</a>
			<div>&nbsp;</div>
			<a href="#ipad">Setup Tablets</a> 
			<a href="#langsetup">Lang. Setup</a>
			<a href="#termscores">Term Scores</a>
			<a href="#keybind">Key Bindings</a>
			<a href="#export">Export Template</a>
			<div>&nbsp;</div>
			<a href="#contribute">Contribute</a>
			<a href="#wordpress">WordPress Integration</a>
			<a href="#api">Public API</a>
			<a href="#database">Database</a>
			<a href="#CHANGELOG">Changelog</a>
		</div>	

		<div style="margin-right:100px;">

			<h4>
				<a href="../index.php" target="_top">
					<img src="../assets/images/lwt_icon_big.png" class="lwtlogoright" alt="Logo" />
					Learning with Texts
				</a>
				<br /><br />
				<span class="bigger">Help/Information</span>
			</h4>

			<p class="inline">
				Jump to topic:
				<select id="topicjump" onchange="topicJump(this);">
					<option value="" selected="selected">
						[Select...]
					</option>
					<option value="getting-started">
						Getting Started
					</option>
					<option value="current">
						Current Version
					</option>
					<option value="install">
						Installation
					</option>
					<option value="postinstall">
						Post-Installation Steps
					</option>
					<option value="features">
						Features
					</option>
					<option value="newfeatures">
						New in this Version
					</option>
					<option value="screencasts">
						Screencasts
					</option>
					<option value="links">
						Links
					</option>
					<option value="restrictions">
						Restrictions
					</option>
					<option value="UNLICENSE">
						License
					</option>
					<option value="learn">
						How to learn
					</option>
					<option value="howto">
						How to use
					</option>
					<option value="faq">
						Questions and Answers
					</option>
					<option value="ipad">
						Setup for Tablets
					</option>
					<option value="langsetup">
						Language Setup
					</option>
					<option value="termscores">
						Term Scores
					</option>
					<option value="keybind">
						Key Bindings
					</option>
					<option value="export">
						Export Template
					</option>
					<option value="contribute">
						Contribute
					</option>
					<option value="wordpress">
						WordPress Integration
					</option>
					<option value="api">
						Public API
					</option>
					<option value="database">
						Database Structure
					</option>
					<option value="CHANGELOG">
						Changelog
					</option>
				</select>
			</p>

			<?php markdown_integration(__DIR__ . "/user/getting-started.md"); ?>

			<h2 name="current" id="current">
				▶ Current Version - <a href="#">[↑]</a>
			</h2>

			<p>
				The current version is <?php echo get_version();  ?>.
				<br>
				<a href="#CHANGELOG">View the Changelog.</a>
			</p>

			<?php markdown_integration(__DIR__ . "/user/installation.md"); ?>

			<?php markdown_integration(__DIR__ . "/user/post-installation.md"); ?>

			<?php markdown_integration(__DIR__ . "/reference/features.md"); ?>

			<?php markdown_integration(__DIR__ . "/reference/new-features.md"); ?>
			
			<h2 name="screencasts" id="screencasts">
				▶ Screencasts/Videos - <a href="#">[↑]</a>
			</h2>
		
			<p>
				<a target="_blank" href="https://www.youtube.com/watch?v=QSLPOATWAU4">Learning With Texts</a> 
				by <a target="_blank" href="https://www.youtube.com/@AnthonyLauder">Anthony Lauder (FluentCzech)</a>:
				<br /><br />
				<iframe width="640" height="360" src="https://www.youtube.com/embed/QSLPOATWAU4" frameborder="0" allowfullscreen></iframe>
				<br /><br />
			</p>

			<p>
				<a target="_blank" href="https://www.youtube.com/watch?v=QnGG-_urLKk">
					Learning With Texts: iPad, computer & mobile FREE language reading interface
				</a> from <a target="_blank" href="https://www.youtube.com/@irishpolyglot">Benny the Irish polyglot</a>:
				<br /><br />
				<iframe width="640" height="360" src="https://www.youtube.com/embed/QnGG-_urLKk" frameborder="0" allowfullscreen></iframe>
				<br /><br />
				<a href="http://www.fluentin3months.com/learning-with-texts/" target="_blank">Fluent In 3 Months: Introducing LWT</a>.<br />
			</p>

			<?php markdown_integration(__DIR__ . "/legal/links.md"); ?>

			<?php markdown_integration(__DIR__ . "/reference/restrictions.md"); ?>

			<?php markdown_integration(__DIR__ . "/../UNLICENSE.md" ) ?>

			<?php markdown_integration(__DIR__ . "/legal/third-party-licenses.md" ) ?>

			<?php markdown_integration(__DIR__ . "/user/how-to-learn.md"); ?>

			<?php markdown_integration(__DIR__ . "/user/how-to-use.md"); ?>

			<?php markdown_integration(__DIR__ . "/user/faq.md"); ?>

			<?php markdown_integration(__DIR__ . "/user/troubleshooting/ipad.md"); ?>

			<?php markdown_integration(__DIR__ . '/user/language-setup.md'); ?>

			<?php markdown_integration(__DIR__ . "/reference/term-scores.md"); ?>

			<?php markdown_integration(__DIR__ . "/user/keyboard-shortcuts.md"); ?>

			<?php markdown_integration(__DIR__ . "/reference/export-templates.md"); ?>

			<?php markdown_integration(__DIR__ . "/../CONTRIBUTING.md"); ?>

			<?php markdown_integration(__DIR__ . "/user/troubleshooting/wordpress.md"); ?>

			<?php markdown_integration(__DIR__ . "/developer/api.md"); ?>

			<?php markdown_integration(__DIR__ . "/reference/database-schema.md"); ?>

			<?php markdown_integration(__DIR__ . "/../CHANGELOG.md"); ?>

			<footer>
				<p class="smallgray">
					<a target="_blank" href="http://en.wikipedia.org/wiki/Public_domain_software">
						<img class="lwtlogo" src="../assets/images/public_domain.png" alt="Public Domain" />
					</a>
					<a href="http://sourceforge.net/projects/learning-with-texts/" target="_blank">
						"Learning with Texts" (LWT)
					</a> is released into the Public Domain. This applies worldwide.
					<wbr />
					In case this is not legally possible, any entity is granted the right to use this 
					work for any purpose, without any conditions, unless such conditions are 
					required by law.
				</p>
			</footer>
        </div>
    </body>
</html>
