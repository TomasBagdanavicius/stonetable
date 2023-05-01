<?php

declare(strict_types=1);

require_once (__DIR__ . '/../utilities.php');

if( !$project_pathname = get_value_exists('project_path') ) {
    send_error("Please provide a project path");
}

$path = get_value_exists('path', DIRECTORY_SEPARATOR);
$path = preg_replace('#[/\\\]+#', DIRECTORY_SEPARATOR, $path);
$search_query = get_value_exists('file_search_query', null);

use PD\ProjectFile;

$project_root_directory = get_project_root_object($project_pathname);

$project_file_object
    = $main_project_file_object
    = $project_root_directory->find($path);

if( !$project_file_object ) {
    send_error("File with path $path was not found");
}

if( $project_file_object->isFile() ) {
    send_error("File {$project_file_object->pathname} is not a directory");
}

$entries_per_page = 25;

if( $page_number = get_value_exists('page_number', 1) ) {

    if( !is_numeric($page_number) ) {
        send_error("Page number must be numeric");
    }

    $page_number = (int)$page_number;
    $max_page_number = 99999;

    if( $page_number < 1 ) {
        send_error("Page number cannot be smaller than 1");
    } elseif( $page_number > $max_page_number ) {
        send_error("Page number cannot be larger than " . $max_page_number);
    }
}

$charset = 'UTF-8';
$transliterator = 'Any-Latin; Latin-ASCII';
$has_search_query = ( $search_query !== null );

if( !$has_search_query ) {

    $file_iterator = $project_file_object->getSortedIterator();

// Search query provided.
} else {

    $file_iterator = $project_file_object->getRecursiveIterator();

    $comparable_search_query = mb_strtolower($search_query, $charset);
    $comparable_search_query = transliterator_transliterate(
        $transliterator,
        $comparable_search_query
    );
}

$data = [];
$index = 0;
$select_from = (($page_number - 1) * $entries_per_page + 1);
$select_to = ($select_from + $entries_per_page - 1);

foreach( $file_iterator as $project_file_object ) {

    if( !$has_search_query ) {

        $index++;

        if( $index >= $select_from && $index <= $select_to ) {
            $data[] = $project_file_object->getDescriptionData();
        }

    // Search query provided.
    } else {

        $searchable_string = $project_file_object->getBasename();

        $comparable_searchable_str = mb_strtolower(
            $searchable_string,
            $charset
        );

        $comparable_searchable_str = transliterator_transliterate(
            $transliterator,
            $comparable_searchable_str
        );

        if(
            mb_strpos(
                $comparable_searchable_str,
                $comparable_search_query,
                encoding: $charset
            ) !== false
        ) {

            $index++;

            if( $index >= $select_from && $index <= $select_to ) {
                $data[] = $project_file_object->getDescriptionData();
            }
        }
    }

    // Since this is in a loop and might open many files, close finished ones.
    if( $project_file_object instanceof ProjectFile ) {
        $project_file_object->fileClose();
    }
}

$params = [
    'pageNumber' => $page_number,
    'totalCount' => $index,
    'maxPages' => ceil($index / $entries_per_page),
    'perPage' => $entries_per_page,
];

if( !$has_search_query ) {

    $parent_data = $main_project_file_object->getParentProjectDirectory()
        ?->getDescriptionData();

    if( $parent_data ) {
        $params['parentDir'] = $parent_data;
    }
}

send_success($data, $params);
