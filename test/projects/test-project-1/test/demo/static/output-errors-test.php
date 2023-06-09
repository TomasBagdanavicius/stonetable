<?php

declare(strict_types=1);

require_once (__DIR__ . '/../../../../../../src/web/demo-page-init.php');

Demo\start();

/*** Demo Code ***/

echo "Normal output 1";
trigger_error("Deprecated", E_USER_DEPRECATED);
echo "Normal output 2";
trigger_error("Notice", E_USER_NOTICE);
echo "Normal output 3";
trigger_error("Warning", E_USER_WARNING);
echo "Normal output 4";
trigger_error("Error", E_USER_ERROR);
echo "Normal output 5";
