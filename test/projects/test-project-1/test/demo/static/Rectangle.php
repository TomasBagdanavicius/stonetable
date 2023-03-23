<?php

declare(strict_types=1);

require_once (__DIR__ . '/../../../../../../src/web/demo-page-init.php');

Demo\start();

/*** Demo Code ***/

require_once (__DIR__ . '/../../../src/Rectangle.php');

echo "Constructing Rectangle(5,10)...", PHP_EOL;
$rectangleA = new Rectangle(5,10);

echo "Calculating area...", PHP_EOL;
echo '>>> ';
var_dump($rectangleA->calcArea());

echo "Constructing Rectangle(5,\"10\")...", PHP_EOL;
$rectangleB = new Rectangle(5,"10");
