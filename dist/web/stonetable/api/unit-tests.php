<?php

declare(strict_types=1);

require_once (__DIR__ . '/../utilities.php');

if( !$project_pathname = get_value_exists('project_path') ) {
    send_error("Please provide a project path");
}

$path = get_value_exists('path', '/');

$project_root_directory = get_project_root_object($project_pathname);
$iterator = $project_root_directory->getUnitsFileRecursiveIterator();
$base_pathname = $project_root_directory->units_static_dirname;
$base_pathname_len = (strlen($base_pathname) + 1);

$data = [
    'meta' => [
        'basename' => "Unit Tests",
        'handlerName' => 'unit-tests',
    ],
    'parts' => [],
];
$total = 0;
$category_map = [];

foreach( $iterator as $file ):

    if( $file->isFile() ) {

        $category_name = substr($file->getPath(), $base_pathname_len);
        $description_data = $file->getDescriptionData();
        $category_id = array_search($category_name, $category_map);

        if( $category_id === false ) {

            $category_id = ( !$category_map )
                ? 1
                : (array_key_last($category_map) + 1);

            $category_map[$category_id] = $category_name;

            $data['parts'][$category_id] = [
                'name' => ($category_name ?: '/'),
                'files' => [
                    1 => $description_data,
                ],
            ];

        } else {

            $last_key = array_key_last($data['parts'][$category_id]['files']);
            $file_id = ($last_key + 1);

            $data['parts'][$category_id]['files'][$file_id] = $description_data;
        }

        $total++;
    }

endforeach;

$data['meta']['total'] = $total;

send_success($data);
