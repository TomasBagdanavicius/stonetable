<?php

/**
 * Produces project file objects by a pattern match.
 *
 * PHP version 8.1
 *
 * @package   Project Directory
 * @author    Tomas Bagdanavičius <tomas.bagdanavicius@lwis.net>
 * @license   MIT License
 * @copyright Copyright (c) 2023 LWIS Technologies <info@lwis.net>
 *            (https://www.lwis.net/)
 * @version   1.0.7
 * @since     1.0.0
 */

declare(strict_types=1);

namespace PD;

class ProjectFileObjectFactory {

    /**
     * Two level array containing ['pattern', 'class_name', 'type',
     * 'dependencies'] sets.
     */
    private array $pattern_map = [];

    public function __construct(
        public readonly ProjectRootDirectory $project_root_directory,
    ) {

    }

    /**
     * Adds a new pattern.
     *
     * @param string $pattern      Pattern syntax.
     * @param string $class_name   Class name that should be used when a file
     *                             matches the pattern.
     * @param string $type         File type - "dir" or "file"
     * @param array  $dependencies A list of file names that should be run in
     *                             the factory and added to the construct
     *                             parameter of the class name
     */
    public function add(
        string $pattern,
        string $class_name,
        string $type,
        array $dependencies = [],
    ): void {

        if( isset($this->pattern_map[$pattern]) ) {
            throw new \RuntimeException(
                "Pattern $pattern already exists"
            );
        }

        $this->pattern_map[] = [
            'pattern' => $pattern,
            'class_name' => $class_name,
            'type' => $type,
            'dependencies' => $dependencies,
        ];
    }

    /**
     * Matches a pattern agains a given path name.
     *
     * @param string $pathname Path name.
     * @param string $pattern  Pattern to match.
     */
    public function matchPattern( string $pathname, string $pattern ): bool {

        $pathname_parts = explode(DIRECTORY_SEPARATOR, $pathname);
        $pattern_parts = explode(DIRECTORY_SEPARATOR, $pattern);

        foreach( $pattern_parts as $index => $pattern_part ) {

            if(
                // Pathname shorter than pattern.
                !isset($pathname_parts[$index])
                || (
                    $pathname_parts[$index] !== $pattern_part
                    && $pattern_part !== '*'
                )
            ) {
                return false;
            }
        }

        $pathname_parts_count = count($pathname_parts);
        $is_last_any = ($pattern_parts[array_key_last($pattern_parts)] === '*');

        if(
            !$is_last_any
            && $pathname_parts_count > ($index + 1)
        ) {
            return false;
        }

        return true;
    }

    /**
     * Produces a project file object from a given file name.
     *
     * @param string $pathname File name.
     * @return ProjectFileObject|null Null when given file name does not exist.
     */
    public function fromPathname(
        string $pathname
    ): ?ProjectFileObject {

        if( !file_exists($pathname) ) {
            return null;
        }

        $filetype = filetype($pathname);

        foreach( $this->pattern_map as $data ) {

            if(
                isset($data['type'])
                && $filetype !== $data['type']
            ) {
                continue;
            }

            $match = $this->matchPattern($pathname, $data['pattern']);

            if( $match ) {

                $params = [
                    $pathname,
                    $this->project_root_directory,
                ];

                if( isset($data['dependencies']) ) {

                    foreach( $data['dependencies'] as $dependency_pathname ) {

                        $dependency_object = $this->fromPathname(
                            $dependency_pathname
                        );

                        $params[] = $dependency_object;
                    }
                }

                return new ($data['class_name'])(...$params);
            }
        }

        if( is_file($pathname) ) {

            return new ProjectFile(
                $pathname,
                $this->project_root_directory,
            );

        } elseif( is_dir($pathname) ) {

            return new ProjectDirectory(
                $pathname,
                $this->project_root_directory,
            );
        }
    }
}
