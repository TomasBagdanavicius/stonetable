<?php

declare(strict_types=1);

if( isset($argv) ) {

    if( !isset($argv[1]) ) {
        die("File pathname must be provided as the first argument\n");
    }

    $pathname = $argv[1];

} elseif( isset($_GET['pathname']) ) {

    $pathname = $_GET['pathname'];

} else {

    die("File pathname was not provided\n");
}

if( $pathname === '' ) {

    die("File pathname cannot be empty\n");
}

if( !file_exists($pathname) ) {

    die("File pathname does not point to an existing file\n");
}

require_once '/usr/local/var/www/projects/project-directory/src/Autoload.php';
require_once '/usr/local/var/www/projects/stonetable/src/web/utilities.php';

use PD\ProjectFile;
use PD\ProjectRootDirectory;

$project_pathname = realpath(__DIR__ . '/..');

$project_root_directory = new ProjectRootDirectory(
    $project_pathname,
    // Enable "App" special comment.
    on_special_comment_setup: on_special_comment_setup(...)
);

$project_file = $project_root_directory->findByAbsolutePathname($pathname);

if( !$project_file ) {

    die(sprintf(
        "File %s does not belong to project %s\n",
        $pathname,
        $project_pathname
    ));
}

$rebuild = $project_file->rebuildAllSpecialCommentLines();

if( !$rebuild ) {

    die(sprintf(
        "Could not rebuild special comments in file %s\n",
        $pathname
    ));

} else {

    die(sprintf(
        "Special comments successfully rebuilt in file %s\n",
        $pathname
    ));
}
