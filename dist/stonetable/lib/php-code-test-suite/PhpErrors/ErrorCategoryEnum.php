<?php

/**
 * Enumerates PHP error types.
 *
 * PHP version 8.1
 *
 * @package   PHP Code Test Suite
 * @author    Tomas Bagdanavičius <tomas.bagdanavicius@lwis.net>
 * @license   MIT License
 * @copyright Copyright (c) 2023 LWIS Technologies <info@lwis.net>
 *            (https://www.lwis.net/)
 * @version   1.0.1
 * @since     1.0.0
 */

declare(strict_types=1);

namespace PCTS\PhpErrors;

enum ErrorCategoryEnum {

    case ERROR;
    case WARNING;
    case NOTICE;
    case DEPRECATED;

}
