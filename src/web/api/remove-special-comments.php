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
use PD\TestFile;

$project_root_directory = get_project_root_object($project_pathname);
$project_file_object = $project_root_directory->find($path);

if( !$project_file_object ) {
    send_error("File with path $path was not found");
}

if(
    !($project_file_object instanceof SourceFile)
    && !($project_file_object instanceof TestFile)
) {
    send_error(
        "File $path does not represent a source, or a test file"
    );
}

$remove_result = $project_file_object->removeAllSpecialCommentLines();

if( $remove_result === false ) {
    send_error("Could not remove special comment lines");
} elseif( $remove_result === null ) {
    send_error("There was nothing to remove");
} else {
    send_success([]);
}
