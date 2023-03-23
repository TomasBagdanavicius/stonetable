<?php

declare(strict_types=1);

require_once (__DIR__ . '/../../../../../../../src/web/demo-page-init.php');

Demo\start();

/*** Demo Code ***/

require_once (__DIR__ . '/../../../../src/Rectangle.php');

$rectangle = new Rectangle(3,14);

Demo\assert_true(
    $rectangle->calcArea() === 42,
    "Rectangle area is not equal to 42"
);
