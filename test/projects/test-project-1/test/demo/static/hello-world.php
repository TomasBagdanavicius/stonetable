<?php

declare(strict_types=1);

require_once (__DIR__ . '/../../../../../../src/web/demo-page-init.php');

Demo\start();

/*** Demo Code ***/

require_once (__DIR__ . '/../../../src/hello-world.php');

echo helloWorld(), PHP_EOL;

// Emulates warning.
echo $hello;

echo fooBar(), PHP_EOL;

// Emulates error due to incorrect parameter type.
echo loremIpsum(100), PHP_EOL;
