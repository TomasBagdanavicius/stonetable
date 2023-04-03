<?php

/**
 * Autoloader for the PHP Code Test Suite.
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

namespace PCTS;

spl_autoload_extensions('.php');

spl_autoload_register(function( string $namespace_name ): void {

    $namespace_parts = explode('\\', $namespace_name, 2);

    if( $namespace_parts[0] === __NAMESPACE__ ) {

        $filename = (__DIR__
            . DIRECTORY_SEPARATOR
            . str_replace('\\', DIRECTORY_SEPARATOR, $namespace_parts[1])
            . '.php');

        if( !file_exists($filename) ) {
            throw new \UnexpectedValueException(
                "Filename $filename was not found"
            );
        }

        require $filename;
    }

});
