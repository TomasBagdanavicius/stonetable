<?php

declare(strict_types=1);

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

/* Source Files */

$source_file_iterator = $project_root_directory
    ->getSourceFileRecursiveIterator();
$rebuild_count = 0;
$rebuild_error_count = 0;

foreach( $source_file_iterator as $project_file_object ) {

    if( $project_file_object instanceof ProjectFile ) {

        $rebuild = $project_file_object->rebuildAllSpecialCommentLines();

        if( !$rebuild ) {

            echo sprintf(
                "Could not rebuild %s\n",
                $project_file_object->getRelativePathName()
            );

            $rebuild_error_count++;

        } else {

            $rebuild_count++;
        }

        $project_file_object->fileClose();
    }
}

echo sprintf(
    "Source files rebuilt: %d\n",
    $rebuild_count
);

echo sprintf(
    "Source file rebuild errors: %d\n",
    $rebuild_error_count
);

/* Demo Files */

$demo_file_iterator = $project_root_directory->getDemoFileRecursiveIterator();
$rebuild_count = 0;
$rebuild_error_count = 0;

foreach( $demo_file_iterator as $project_file_object ) {

    if( $project_file_object instanceof ProjectFile ) {

        $rebuild = $project_file_object->rebuildAllSpecialCommentLines();

        if( !$rebuild ) {

            echo sprintf(
                "Could not rebuild %s\n",
                $project_file_object->getRelativePathName()
            );

            $rebuild_error_count++;

        } else {

            $rebuild_count++;
        }

        $project_file_object->fileClose();
    }
}

echo sprintf(
    "Demo files rebuilt: %d\n",
    $rebuild_count
);

echo sprintf(
    "Demo file rebuild errors: %d\n",
    $rebuild_error_count
);

/* Unit Files */

$unit_file_iterator = $project_root_directory->getUnitsFileRecursiveIterator();
$rebuild_count = 0;
$rebuild_error_count = 0;

foreach( $unit_file_iterator as $project_file_object ) {

    if( $project_file_object instanceof ProjectFile ) {

        $rebuild = $project_file_object->rebuildAllSpecialCommentLines();

        if( !$rebuild ) {

            echo sprintf(
                "Could not rebuild %s\n",
                $project_file_object->getRelativePathName()
            );

            $rebuild_error_count++;

        } else {

            $rebuild_count++;
        }

        $project_file_object->fileClose();
    }
}

echo sprintf(
    "Unit files rebuilt: %d\n",
    $rebuild_count
);

echo sprintf(
    "Unit file rebuild errors: %d\n",
    $rebuild_error_count
);
