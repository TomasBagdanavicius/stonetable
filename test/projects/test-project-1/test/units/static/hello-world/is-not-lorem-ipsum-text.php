<?php

declare(strict_types=1);

require_once (__DIR__ . '/../../../../../../../src/web/demo-page-init.php');

Demo\start();

/*** Demo Code ***/

require_once (__DIR__ . '/../../../../src/hello-world.php');

Demo\assert_true(
    loremIpsum() !== "Lorem ipsum...",
    "Function loremIpsum() did not return anything, but \"Lorem ipsum...\""
);
