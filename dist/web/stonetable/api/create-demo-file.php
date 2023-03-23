<?php

declare(strict_types=1);

require_once (__DIR__ . '/../utilities.php');

if( !$project_pathname = get_value_exists('project_path') ) {
    send_error("Please provide a project path");
}

if( !$path = get_value_exists('path') ) {
    send_error("Please provide a path");
}

use PD\SourceFile;

$project_root_directory = get_project_root_object($project_pathname);
$source_file = $project_root_directory->find($path);

if( !$source_file ) {
    send_error("File $path not found");
}

if( !($source_file instanceof SourceFile) ) {
    send_error("File $path is not a source file");
}

$demo_file = $source_file->getDemoFileInstance();

if( !$demo_file ) {

    $demo_file = $source_file->createDemoFile();

    if( $demo_file === false ) {
        send_error("Could not create demo file");
    }
}

send_success($demo_file->getDescriptionData());
