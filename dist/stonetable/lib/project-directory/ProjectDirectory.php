<?php

/**
 * An object that represents any directory in project.
 *
 * PHP version 8.1
 *
 * @package   Project Directory
 * @author    Tomas Bagdanavičius <tomas.bagdanavicius@lwis.net>
 * @license   MIT License
 * @copyright Copyright (c) 2023 LWIS Technologies <info@lwis.net>
 *            (https://www.lwis.net/)
 * @version   1.0.4
 * @since     1.0.0
 */

declare(strict_types=1);

namespace PD;

/** Project directory is also considered a project file object. */
class ProjectDirectory extends ProjectFileObject implements \IteratorAggregate {

    use FileTrait;

    /**
     * @param string        $dirname             Directory's path name.
     * @param self|null     $root_directory      Directory object of directory's
     *                                           base directory.
     * @param \Closure|null $on_description_data Func. that will be called each
     *                                           time description data is
     *                                           fetched.
     */
    public function __construct(
        public readonly string $dirname,
        public readonly ?self $root_directory = null,
        ?\Closure $on_description_data = null
    ) {

        parent::__construct(
            $dirname,
            $root_directory?->dirname,
            $on_description_data
        );
    }

    /** Gets data describing the directory. */
    public function getDescriptionData(): array {

        $data = [
            'IdeUri' => $this->getIdeUri(),
        ];

        return [
            ...parent::getDescriptionData(),
            ...$data,
        ];
    }

    /**
     * Searches for a file in this directory.
     *
     * @param string $relative_pathname File path relative to this directory.
     * @return ProjectFileObject|null Null when file is not found.
     */
    public function find(
        string $relative_pathname
    ): ?ProjectFileObject {

        $relative_pathname = ltrim($relative_pathname, '/\\');
        $find_pathname = ProjectRootDirectory::joinPath(
            $this->dirname,
            $relative_pathname
        );

        if( !file_exists($find_pathname) ) {
            return null;
        }

        return $this->root_directory->factory->fromPathname($find_pathname);
    }

    /**
     * Searches for a file in this directory by a given absolute path name.
     *
     * @param string $pathname Absolute path name.
     * @return ProjectFileObject|null Null when either absolute path name does
     *                                not start with the name of this directory,
     *                                or file does not exits.
     */
    public function findByAbsolutePathname(
        string $pathname
    ): ?ProjectFileObject {

        if( str_starts_with($this->dirname, $pathname) ) {
            return null;
        }

        $relative_pathname = substr($pathname, (strlen($this->dirname) + 1));

        return $this->find($relative_pathname);
    }

    /** Provides an instance of the tailored file iterator. */
    public function getIterator(): FileIterator {

        return new FileIterator(
            new \CallbackFilterIterator(
                new \FilesystemIterator($this->dirname),
                $this->getFilterHandler($this->dirname),
            ),
            project_root_directory: $this->root_directory
        );
    }

    /**
     * Provides an instance of iterator that has all file data sorted by the
     * custom sorting algorithm.
     */
    public function getSortedIterator(): \ArrayIterator {

        return $this->getSortedData(
            iterator_to_array($this->getIterator())
        );
    }

    /** Sorts given data array by the custom sorting algorithm. */
    public function getSortedData( array $data ): \ArrayIterator {

        $array_iterator = new \ArrayIterator($data);
        $array_iterator->uasort($this->getSortHandler());

        return $array_iterator;
    }

    /**
     * Provides an instance of the tailored recursive file iterator.
     *
     * @param int $mode File listing mode.
     */
    public function getRecursiveIterator(
        int $mode = \RecursiveIteratorIterator::SELF_FIRST
    ): \Traversable {

        return new RecursiveFileIterator(
            new \RecursiveCallbackFilterIterator(
                new \RecursiveDirectoryIterator(
                    $this->dirname,
                    flags: (
                        \FilesystemIterator::KEY_AS_PATHNAME
                        | \FilesystemIterator::CURRENT_AS_FILEINFO
                        | \FilesystemIterator::SKIP_DOTS
                    )
                ),
                $this->getFilterHandler($this->dirname),
            ),
            project_root_directory: $this->root_directory,
            mode: $mode
        );
    }


    /**
     * Provides an instance of recursive iterator that has all file data sorted
     * by the custom sorting algorithm.
     */
    public function getSortedRecursiveIterator(
        int $mode = \RecursiveIteratorIterator::SELF_FIRST
    ): \Traversable {

        return $this->getSortedData(
            iterator_to_array($this->getRecursiveIterator($mode))
        );
    }

    /** Gets a handler that will determine if file should be included or not. */
    public function getFilterHandler(
        string $dirname
    ): \Closure {

        $root_dirname_length = strlen($this->root_directory->dirname);

        return function(
            \SplFileInfo $file_info,
            string $filename,
            \Traversable $iterator
        ) use(
            $dirname,
            $root_dirname_length,
        ): bool {

            $file_name = $file_info->getFilename();

            if(
                // Ignore all files starting with a dot.
                str_starts_with($file_name, '.')
            ) {
                return false;
            }

            $relative_pathname = substr(
                $filename,
                ($root_dirname_length + 1)
            );

            if(
                in_array(
                    $relative_pathname,
                    $this->root_directory->config['hidden_files']
                )
            ) {
                return false;
            }

            return true;
        };
    }

    /** Gets a handler that will sort data by the custom sorting algorithm. */
    public static function getSortHandler(): \Closure {

        return function(
            \SplFileInfo $a,
            \SplFileInfo $b
        ): int {

            if( $a->isDir() && $b->isFile() ) {
                return -1;
            } elseif( $a->isFile() && $b->isDir() ) {
                return 1;
            }

            return strnatcasecmp($a->getPathname(), $b->getPathname());
        };
    }
}
