<?php

/**
 * Enumerates PHP error classes (not to be confused with standard classes).
 *
 * PHP version 8.1
 *
 * @package   PHP Code Test Suite
 * @author    Tomas Bagdanavičius <tomas.bagdanavicius@lwis.net>
 * @license   MIT License
 * @copyright Copyright (c) 2023 LWIS Technologies <info@lwis.net>
 *            (https://www.lwis.net/)
 * @version   1.0.2
 * @since     1.0.0
 */

declare(strict_types=1);

namespace PCTS\PhpErrors;

enum ErrorClassEnum {

    case ERROR;
    case EXCEPTION;

}
