<?php

declare(strict_types=1);

require_once (__DIR__ . '/../utilities.php');

if( !$project_pathname = get_value_exists('project_path') ) {
    send_error("Please provide a project path");
}

$path = get_value_exists('path', DIRECTORY_SEPARATOR);

use PD\StaticFile;

$project_root_directory = get_project_root_object($project_pathname);
$static_file = $project_root_directory->find($path);

if( !$static_file ) {
    send_error("File $path not found");
}

if( !($static_file instanceof StaticFile) ) {
    send_error("File $path is not a static category file");
}

$playground_file = $static_file->getPlaygroundFileInstance();

if( !$playground_file ) {

    $playground_file = $static_file->createPlaygroundFile();
}

send_success($playground_file->getDescriptionData());
