<?php

declare(strict_types=1);

/**
 * Path name to the directory containing web projects.
 *
 * Trailing slash should be excluded. Please normalize using `realpath()` if
 * required.
 */
#define('PROJECTS_PATH', realpath(__DIR__ . '/../../test/projects'));
define('PROJECTS_PATH', '/usr/local/var/www/projects');

/**
 * Path name to the library directory.
 *
 * Trailing slash should be excluded. Please normalize using `realpath()` if
 * required.
 */
define('LIB_PATH', realpath(__DIR__ . '/../lib'));
