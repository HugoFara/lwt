<?php

/**
 * External Parser Allowlist Configuration.
 *
 * SECURITY: This file defines which external programs can be executed for text parsing.
 * Only server administrators should modify this file. Never allow user input to determine
 * parser paths or arguments.
 *
 * PHP version 8.1
 *
 * @category Configuration
 * @package  Lwt\Config
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

declare(strict_types=1);

/**
 * External parser configurations.
 *
 * Each parser entry is keyed by a unique type identifier and contains:
 *
 * - 'name' (string, required): Human-readable name displayed in the UI
 * - 'binary' (string, required): Path to executable. Can be:
 *     - Absolute path: '/usr/bin/python3'
 *     - Command name: 'python3' (uses system PATH)
 * - 'args' (array, optional): Command-line arguments passed to the binary
 * - 'input_mode' (string, optional): How text is passed to the parser:
 *     - 'stdin' (default): Text is piped to stdin
 *     - 'file': Text is written to a temp file, path appended as last argument
 * - 'output_format' (string, optional): How parser output is interpreted:
 *     - 'line' (default): One token per line
 *     - 'wakati': Space-separated tokens (like MeCab wakati mode)
 *
 * Built-in parsers (regex, character, mecab) are always available and do not
 * need to be configured here. This file is for adding additional external parsers.
 *
 * Example configurations:
 *
 * return [
 *     'jieba' => [
 *         'name' => 'Jieba (Chinese)',
 *         'binary' => '/usr/bin/python3',
 *         'args' => ['/opt/lwt/parsers/jieba_tokenize.py'],
 *         'input_mode' => 'stdin',
 *         'output_format' => 'line',
 *     ],
 *
 *     'sudachi' => [
 *         'name' => 'Sudachi (Japanese)',
 *         'binary' => 'sudachipy',
 *         'args' => ['-m', 'C', '-a'],
 *         'input_mode' => 'stdin',
 *         'output_format' => 'wakati',
 *     ],
 *
 *     'custom_tokenizer' => [
 *         'name' => 'Custom Tokenizer',
 *         'binary' => '/opt/lwt/bin/tokenize',
 *         'args' => ['--format=simple'],
 *         'input_mode' => 'file',
 *         'output_format' => 'line',
 *     ],
 * ];
 */
return [
    // Add your external parser configurations here.
    // See the docblock above for configuration options.
];
