<?php

declare(strict_types=1);

require_once (__DIR__ . '/../utilities.php');

if( !$project_pathname = get_value_exists('project_path') ) {
    send_error("Please provide a project path");
}

$path = get_value_exists('path', DIRECTORY_SEPARATOR);
$project_root_directory = get_project_root_object($project_pathname);
$project_file = $project_root_directory->find($path);

if( !$project_file ) {
    send_error("File $path not found");
}

send_success($project_file->getDescriptionData());
