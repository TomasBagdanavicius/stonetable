<?php

declare(strict_types=1);

require_once (__DIR__ . '/../../../../../../src/web/demo-page-init.php');

Demo\start();

/*** Demo Code ***/

$class_pathname = (Demo\SRC_PATH . DIRECTORY_SEPARATOR . 'Rectangle.php');

echo "Clickable namespace: ";

trigger_error(
    "VendorName\Rectangle",
    E_USER_WARNING
);

echo "Namespace not backed up by a file: ";

trigger_error(
    "VendorName\Unexisting",
    E_USER_WARNING
);

echo "File path: ";

trigger_error(
    "$class_pathname",
    E_USER_WARNING
);

echo "File path with line number: ";

trigger_error(
    "$class_pathname on line 10",
    E_USER_WARNING
);

echo "Function namespace name: ";

trigger_error("VendorName\\func_name()", E_USER_WARNING);

echo "Handle closure the same way as function: ";

trigger_error("VendorName\\{closure}", E_USER_WARNING);

echo "Unexisting file path: ";

trigger_error(sprintf(
    "File %s does not exist",
    realpath(__DIR__ . '/../../..') . DIRECTORY_SEPARATOR . 'xyz.txt'
), E_USER_WARNING);

echo "Anonymous class with file path, line number, and variable: ";

trigger_error(
    "VendorName\Rectangle@anonymous$class_pathname:10\$a",
    E_USER_WARNING
);
