<?php

declare(strict_types=1);

# Change this to your path to "demo-page-init.php"
require_once '/Users/JohnDoe/web/stonetable/demo-page-init.php';

Demo\start();

/*** Demo Code ***/

# Code your test expression
$expression = true;

Demo\assert_true($expression, "This is my error message");
