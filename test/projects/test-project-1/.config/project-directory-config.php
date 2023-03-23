<?php

declare(strict_types=1);

$config = [
    'vendor_name' => 'VendorName',
    'ide_uri_format' => 'vscode://file/{file}[:{line}][:{column}]',
    'import_vendors' => [
        'test-project-2'
    ],
    'hidden_files' => [
        'test/demo/static/+new.php',
    ],
    'special_comments' => [
        'src' => [
            'ignore_files' => [
            ],
        ],
        'test' => [
            'ignore_files' => [
                'demo/static/+new.php',
            ],
        ],
    ],
];
