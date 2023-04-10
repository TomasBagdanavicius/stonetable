<?php

/**
 * A simple source file.
 *
 * A source file is basically a piece of code that produces capabilities that
 * one would like to demonstrate, play with, or run automated tests against. It
 * is a broad term that can encompass many things, whether it was just a basic
 * function or a big class.
 *
 * Each source file can be mapped to a demo file, where one would demonstrate
 * the capabilities of its souce file. Below there are 3 simple functions that
 * return text strings. Jump to the demo file for a demonstration (select
 * "Reveal Demo File" from the 3-dot options menu in the top right corner).
 */

declare(strict_types=1);

function helloWorld(): string {
    return "Hello World!";
}

function fooBar(): string {
    return "foobar";
}

function loremIpsum(
    ?string $text = null,
    ?string $param2 = null,
    bool $param3 = false,
    array $param4 = [],
    object $param5 = new stdClass
): string {
    return $text ?? "Lorem ipsum...";
}
