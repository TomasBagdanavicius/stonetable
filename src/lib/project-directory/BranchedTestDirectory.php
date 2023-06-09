<?php

/**
 * Represents a dir that can be branched into static and playground directories.
 *
 * Brached test directories are the "demo" and "units" directories.
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

class BranchedTestDirectory extends ProjectDirectory {

    /** Defines name for the directory containing static files. */
    public const STATIC_DIR_NAME = 'static';

    /** Defines name for the directory containing playground files. */
    public const PLAYGROUND_DIR_NAME = 'playground';

    /** Directory path to the static files. */
    public readonly string $static_dirname;

    /** Directory path to the playground files. */
    public readonly string $playground_dirname;

    public function __construct(
        string $dirname,
        ProjectDirectory $root_directory
    ) {

        parent::__construct($dirname, $root_directory);

        $this->static_dirname = ProjectRootDirectory::joinPath(
            $dirname,
            self::STATIC_DIR_NAME
        );
        $this->playground_dirname = ProjectRootDirectory::joinPath(
            $dirname,
            self::PLAYGROUND_DIR_NAME
        );
    }

    /** Tells if the static files directory exists. */
    public function hasStaticDirectory(): bool {

        return boolval($this->find(self::STATIC_DIR_NAME));
    }

    /** Tells if the playground files directory exists. */
    public function hasPlaygroundDirectory(): bool {

        return boolval(
            $this->find(self::PLAYGROUND_DIR_NAME)
        );
    }

    /**
     * Builds a path name to a file in the static files directory.
     *
     * @param string $relative_pathname Relative path that will be appended.
     */
    public function buildStaticFilePathname(
        string $relative_pathname
    ): string {

        return ProjectRootDirectory::joinPath(
            $this->static_dirname,
            $relative_pathname
        );
    }

    /**
     * Builds a path name to a file in the playground files directory.
     *
     * @param string $relative_pathname Relative path that will be appended.
     */
    public function buildPlaygroundFilePathname(
        string $relative_pathname
    ): string {

        return ProjectRootDirectory::joinPath(
            $this->playground_dirname,
            $relative_pathname
        );
    }

    /**
     * Returns a static file object.
     *
     * @param string $relative_pathname Path name relative to the static files
     *                                  directory.
     * @return StaticFile|null Static file object or null, when file is not
     *                         found.
     */
    public function findStaticFile(
        string $relative_pathname
    ): ?StaticFile {

        return $this->root_directory->factory->fromPathname(
            $this->buildStaticFilePathname($relative_pathname)
        );
    }

    /**
     * Returns a playground file object.
     *
     * @param string $relative_pathname Path name relative to the playground
     *                                  files directory.
     * @return PlaygroundFile|null PlaygroundFile file object or null, when file
     *                             is not found.
     */
    public function findPlaygroundFile(
        string $relative_pathname
    ): ?PlaygroundFile {

        return $this->root_directory->factory->fromPathname(
            $this->buildPlaygroundFilePathname($relative_pathname)
        );
    }
}
