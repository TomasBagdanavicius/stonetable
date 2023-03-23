<?php

declare(strict_types=1);

require_once (__DIR__ . '/../utilities.php');

use PD\ProjectRootDirectory;

$base_url = ProjectRootDirectory::getUrlAddressFromPathname(__DIR__);

$data = [
    'directoryListing' => ($base_url . '/directory-listing.php'),
    'describeFile' => ($base_url . '/describe-file.php'),
    'projectsListing' => ($base_url . '/projects-listing.php'),
    'projectFind' => ($base_url . '/project-find.php'),
    'searchDirectory' => ($base_url . '/search-directory.php'),
    'createPlaygroundFile' => ($base_url . '/create-playground-file.php'),
    'createDemoFile' => ($base_url . '/create-demo-file.php'),
    'fileHandler' => ($base_url . '/file-handler.php'),
    'unitTests' => ($base_url . '/unit-tests.php'),
    'isLocalServer' => is_local_server(),
];

send_success($data);
