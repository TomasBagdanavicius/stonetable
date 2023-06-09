<?php

declare(strict_types=1);

/**
 * Path name to the directory containing PHP projects.
 *
 * Trailing slash should be excluded. Please normalize using `realpath()` if
 * required.
 */
define('PROJECTS_PATH', realpath(__DIR__ . '/../../test/projects'));

/**
 * Path name to the library directory.
 *
 * Trailing slash should be excluded. Please normalize using `realpath()` if
 * required.
 */
define('LIB_PATH', realpath(__DIR__ . '/../lib'));
