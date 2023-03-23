<?php

/**
 * Stonetable individual project configuration file.
 */

declare(strict_types=1);

$config = [
    /* Vendor name that represents this project. Must be in camelcase, eg.
    VendorName. */
    'vendor_name' => '',
    /* URI format to use when building links to open files in external IDE code
    editor. Use placeholders wrapped into curly brackets to denote 3 variables:
    1. File path name as {file};
    2. Line number as {line};
    3. Column number as {column}. Additionally, use standard bracket wrappers to
       create a conditional output, eg. if [Hello!{line}] is used, string
       "Hello" will be outputted, only if line number is available. Examples for
       you convenience:
    - Visual Studio Code: vscode://file/{file}[:{line}][:{column}]
    - PHPStorm: phpstorm://open?file={file}[&line={line}][&column={column}]
    - Sublime Text: subl://open?url=file://{file}[&line={line}]
    - Atom: atom://core/open/file?filename={file}[&line={line}]
    - NetBeans: netbeans://open?file={file}[&line={line}]
    - Eclipse PDT: eclipse-pdt://open?file={file}[&line={line}]
    - CodeLobster:
      codelobster://open?url=file://{file}[&line={line}][&column={column}]
    - Komodo IDE: komodo://open?url=file://{file}[&line={line}]
    - Zend Studio: zendstudio://open?file={file}[&line={line}]
    - PHPDesigner: phpdesigner://open?url=file://{file}[&line={line}] */
    'ide_uri_format' => 'vscode://file/{file}[:{line}][:{column}]',
    // A custom source directory name (default is "src")
    #'source_dirname' => '',
    // A custom tests directory name (default is "test")
    #'tests_dirname' => '',
    /* A list of project names that you want to import, eg. "project-name". */
    'import_vendors' => [
    ],
    /* A list of relative path names to files that should be hidden in file
    listing. */
    'hidden_files' => [
        'test/demo/static/+new.php',
    ],
    /* A list of relative path names (in either source or test directory) that
    you want to exclude from receiving special comments. */
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
