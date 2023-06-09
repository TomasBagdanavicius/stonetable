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

use PD\ProjectRootDirectory;
use PD\ProjectDirectory;

/* FilesystemIterator suits better than DirectoryIterator, because the former is
a weird iterator, first of all, it implements SplFileInfo, and, instead of
returning new instances of SplFileInfo on current(), it returns itself. */
$file_iterator = new FilesystemIterator(PROJECTS_PATH);
$file_iterator_data = iterator_to_array($file_iterator);
$file_iterator = new \ArrayIterator($file_iterator_data);
$file_iterator->uasort(ProjectDirectory::getSortHandler());
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
        send_error("Page number cannot be larger than $max_page_number");
    }
}

$search_query = get_value_exists('project_search_query');
$has_search_query = ( $search_query !== null );
$charset = 'UTF-8';
$transliterator = 'Any-Latin; Latin-ASCII';

if( $has_search_query ) {

    $search_query_limit = 100;
    $has_transliterator = function_exists('transliterator_transliterate');
    $search_query_lc = mb_strtolower($search_query, $charset);
    $search_query = ( $has_transliterator )
        ? transliterator_transliterate($transliterator, $search_query_lc)
        : $search_query_lc;

    if( mb_strlen($search_query) > $search_query_limit ) {
        send_error(
            "Search query must not exceed $search_query_limit characters"
        );
    }
}

$data = [];
$index = 0;
$select_from = (($page_number - 1) * $entries_per_page + 1);
$select_to = ($select_from + $entries_per_page - 1);
$config_file_ending = (DIRECTORY_SEPARATOR
    . ProjectRootDirectory::SYS_CONFIG_DIR_NAME
    . DIRECTORY_SEPARATOR
    . ProjectRootDirectory::SYS_CONFIG_FILE_NAME);

foreach( $file_iterator as $fileinfo ) {

    $file_name = $fileinfo->getFilename();

    if(
        $fileinfo->isDir()
        && !str_starts_with($file_name, '.')
        && file_exists($fileinfo->getPathname() . $config_file_ending)
        && (
            !$has_search_query
            || mb_strpos(
                ( ( $has_transliterator )
                    ? transliterator_transliterate(
                        $transliterator,
                        mb_strtolower($file_name, $charset)
                    )
                    : mb_strtolower($file_name, $charset) ),
                $search_query,
                encoding: $charset
            ) !== false
        )
    ) {

        $index++;

        if( $index >= $select_from && $index <= $select_to ) {

            $pathname = $fileinfo->getPathname();

            $data[] = [
                'title' => $fileinfo->getFilename(),
                'pathname' => $pathname,
                'url' => ProjectRootDirectory::getUrlAddressFromPathname(
                    $pathname
                )
            ];
        }
    }
}

/* Demo: plenty of projects

$total = 200;
$index = 0;

for( $i = 1; $i <= $total; $i++ ) {

    $title = "Lorem ipsum " . $i;

    if(
        $search_query === null
        || strpos(strtolower($title), $search_query) !== false
    ) {

        $index++;

        if( $index >= $select_from && $index <= $select_to ) {

            $data[] = [
                'title' => $title,
                'pathname' => '/foo/bar',
                'url' => 'http://localhost/',
            ];
        }
    }
} */

/* Demo: no projects

send_success([], [
    'pageNumber' => 0,
    'totalCount' => 0,
    'maxPages' => 0,
    'perPage' => $entries_per_page,
]); */

send_success($data, [
    'pageNumber' => $page_number,
    'totalCount' => $index,
    'maxPages' => ceil($index / $entries_per_page),
    'perPage' => $entries_per_page,
]);
