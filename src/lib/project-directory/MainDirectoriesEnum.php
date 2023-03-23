<?php

/**
 * Enumerates the names of the main directories in a project repository.
 *
 * Requires PHP 8.1 or higher.
 *
 * @package Project Directory
 * @author Tomas Bagdanavičius <tomas.bagdanavicius@lwis.net>
 * @license MIT License
 * @copyright Copyright (c) 2023 LWIS Technologies <info@lwis.net>
 *            (https://www.lwis.net/)
 * @version 1.0.0
 * @since 1.0.0
 */

declare(strict_types=1);

namespace PD;

enum MainDirectoriesEnum: string {

    case SOURCE = 'src';
    case TESTS = 'test';

}
