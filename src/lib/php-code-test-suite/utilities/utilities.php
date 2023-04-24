<?php

/**
 * A collection of helper functions that can be used while debugging code.
 *
 * PHP version 8.1
 *
 * @package   PHP Code Test Suite
 * @author    Tomas Bagdanavičius <tomas.bagdanavicius@lwis.net>
 * @license   MIT License
 * @copyright Copyright (c) 2023 LWIS Technologies <info@lwis.net>
 *            (https://www.lwis.net/)
 * @version   1.0.4
 * @since     1.0.0
 */

declare(strict_types=1);

/** Alias of "print_r()". */
function pr( mixed $value ): void {

    print_r($value);
}

/** Runs "print_r()" with the given value and exits immediatelly afterwards. */
function pre( mixed $value ): never {

    print_r($value);
    exit;
}

/**
 * Runs "var_dump()" with the given value(s) and exits immediatelly afterwards.
 */
function vare(): never {

    if( !func_num_args() ) {
        throw new \ArgumentCountError(
            "vare() expects at least 1 argument, 0 given"
        );
    }

    var_dump(...func_get_args());
    exit;
}

/** Prints the given value with a line break at the end. */
function prl( string|int|float $value ): void {

    echo ($value . PHP_EOL);
}

/** Echos given object's class name. */
function eo( object $value ): never {

    die($value::class);
}

/** Gets given object's ID number. */
function oid( object $value ): void {

    var_dump(spl_object_id($value));
}

/** Exits with an "OK" status message. */
function ok(): never {

    die("OK");
}

/** Prints a plain text horizontal ruler. */
function hr( int $length = 30 ): void {

    prl(PHP_EOL . str_repeat('-', $length) . PHP_EOL);
}

/**
 * Creates a CTLD constant that will store line break solution based on the
 * "content-type" HTTP header.
 */
function define_content_type_line_break(): void {

    $headers_list = headers_list();
    $content_type = null;

    foreach( $headers_list as $header ):

        if( str_starts_with(strtolower($header), 'content-type:') ) {
            [$content_type] = explode(';', trim(substr($header, 13)));
            break;
        }

    endforeach;

    // Content type aware line break.
    define(
        'CTLB',
        ( $content_type === 'text/plain' )
            ? "\n"
            :'<br>'
    );
}
