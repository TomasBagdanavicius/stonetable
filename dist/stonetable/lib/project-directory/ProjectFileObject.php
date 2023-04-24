<?php

/**
 * Generic object for any project file.
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

/** Lowest abstraction level file object that extends into the SPL file info. */
class ProjectFileObject extends \SplFileInfo {

    /**
     * Sets up the file.
     *
     * @param string      $pathname      File path name.
     * @param string|null $root_pathname Base/Root path name.
     * @throws \UnexpectedValueException When given file path does not exist.
     * @throws \ValueError When given file path does not start with the given
     *                     root path name.
     */
    public function __construct(
        public readonly string $pathname,
        public readonly ?string $root_pathname = null,
        public readonly ?\Closure $on_description_data = null
    ) {

        if( !file_exists($pathname) ) {
            throw new \UnexpectedValueException(
                "File $pathname was not found"
            );
        }

        if( $root_pathname && !str_starts_with($pathname, $root_pathname) ) {
            throw new \ValueError(
                "Path name does not start with the given root path name"
            );
        }

        parent::__construct($pathname);
    }

    /** Gets data describing the file. */
    public function getDescriptionData(): array {

        $data = [
            'type' => filetype($this->pathname),
            'basename' => basename($this->pathname),
            'pathname' => $this->pathname,
            'dirname' => dirname($this->pathname),
            'relativePathname' => $this->getRelativePathname(),
            'url' => $this->getUrl(),
        ];

        if( $this->on_description_data ) {
            $data = ($this->on_description_data)($data, $this);
        }

        return $data;
    }

    /** Returns relative path name (relative to the base/root path name). */
    public function getRelativePathname(): ?string {

        if( !$this->root_pathname ) {
            return null;
        }

        return substr(
            $this->pathname,
            (strlen($this->root_pathname) + 1)
        );
    }

    /** Gets file's URL. */
    public function getUrl(): string {

        return ProjectRootDirectory::getUrlAddressFromPathname($this->pathname);
    }
}
