<?php

declare(strict_types=1);

require_once (__DIR__ . '/../utilities.php');

if( !defined('PROJECTS_PATH') ) {
    send_error(sprintf(
        "Path to projects directory is not defined in %s",
        realpath(__DIR__ . '/../config.php')
    ));
}

if( !file_exists(PROJECTS_PATH) ) {
    send_error(sprintf(
        "Defined project path name %s was not found",
        PROJECTS_PATH
    ));
}

if( !is_dir(PROJECTS_PATH) ) {
    send_error(sprintf(
        "Defined project path name %s is not a directory",
        PROJECTS_PATH
    ));
}

if( !$project_name = get_value_exists('project_name') ) {
    send_error("Please provide a project name");
}

use PD\ProjectRootDirectory;

$directory_iterator = new DirectoryIterator(PROJECTS_PATH);

$project_data = null;

foreach( $directory_iterator as $fileinfo ) {

    $file_name = $fileinfo->getFilename();
    $pathname = $fileinfo->getPathname();

    if(
        !$fileinfo->isDot()
        && $fileinfo->isDir()
        && !str_starts_with($file_name, '.')
        && $file_name === $project_name
        && file_exists(
            $pathname
            . DIRECTORY_SEPARATOR
            . ProjectRootDirectory::SYS_CONFIG_DIR_NAME
            . DIRECTORY_SEPARATOR
            . ProjectRootDirectory::SYS_CONFIG_FILE_NAME
        )
    ) {

        $project_data = [
            'title' => $fileinfo->getFilename(),
            'pathname' => $pathname,
            'url' => ProjectRootDirectory::getUrlAddressFromPathname(
                $pathname
            ),
        ];
    }
}

send_success($project_data);
