<?php

/**
 * Represents a source file.
 *
 * PHP version 8.1
 *
 * @package   Project Directory
 * @author    Tomas Bagdanavičius <tomas.bagdanavicius@lwis.net>
 * @license   MIT License
 * @copyright Copyright (c) 2023 LWIS Technologies <info@lwis.net>
 *            (https://www.lwis.net/)
 * @version   1.0.2
 * @since     1.0.0
 */

declare(strict_types=1);

namespace PD;

class SourceFile extends ProjectFile {

    public function __construct(
        string $filename,
        ProjectDirectory $root_directory
    ) {

        parent::__construct($filename, $root_directory);
    }

    /** Gets all data describing this source file. */
    public function getDescriptionData(): array {

        $my_data = [];

        if( $is_supported = $this->isSupportedFileType() ) {

            $my_data['useLine'] = $this->buildUseLine();
            $my_data['category'] = 'source';
            $my_data['group'] = 'source';
            $demo_file = $this->getDemoFileInstance();

            if( $demo_file ) {

                $my_data['demoFilePathname'] = $demo_file->filename;
                $my_data['demoFileIdeUri'] = $demo_file->getIdeUri();
                $my_data['demoFileRelativePathname']
                    = $demo_file->getRelativePathname();

                $playground_file = $demo_file->getPlaygroundFileInstance();

                if( $playground_file ) {

                    $playground_file_name = $playground_file->pathname;
                    $my_data['playgroundFilePathname'] = $playground_file_name;
                    $my_data['playgroundFileIdeUri']
                        = $playground_file->getIdeUri();
                }
            }
        }

        return [...parent::getDescriptionData(), ...$my_data];
    }


    public function setupSpecialComments(
        array $special_comment_storage
    ): array {

        $special_comment_storage['demo'] = new DemoSpecialComment($this);

        return parent::setupSpecialComments($special_comment_storage);
    }

    /** Gets path name relative to the source directory. */
    public function getSourceRelativePathname(): string {

        return substr(
            $this->pathname,
            (strlen($this->root_directory->source_dirname) + 1)
        );
    }

    /** Generates demo file equivalent file name. */
    public function buildDemoFilePathname(): string {

        return ProjectRootDirectory::joinPath(
            $this->root_directory->demo_static_dirname,
            $this->getSourceRelativePathname()
        );
    }

    /**
     * Gets a reference to an equivalent file name in demo files directory.
     *
     * @return string|null Null when file does not exist.
     */
    public function getDemoFilePathname(): ?string {

        $filename = $this->buildDemoFilePathname();

        if( !file_exists($filename) ) {
            return null;
        }

        return $filename;
    }

    /** Tells if demo file equivalent exists. */
    public function hasDemoFile(): bool {

        return boolval($this->getDemoFilePathname());
    }


    /**
     * Gets instance of the demo file equivalent.
     *
     * @return StaticFile|null Null when demo file does not exist.
     */
    public function getDemoFileInstance(): ?TestFile {

        $demo_file_pathname = $this->getDemoFilePathname();

        if( !$demo_file_pathname ) {
            return null;
        }

        return $this->root_directory->factory->fromPathname(
            $demo_file_pathname
        );
    }

    /** Generates demo file comment line. */
    public function buildDemoFileCommentLine(): ?string {

        return $this->getSpecialComment('demo')->getLine();
    }

    /** Tells if file contains demo file comment line. */
    public function hasDemoCommentLine(): bool {

        return boolval(
            $this->containsSpecialComment($this->getSpecialComment('demo'))
        );
    }

    /**
     * Rebuilds the demo file comment line.
     *
     * @return bool|null Null when either demo file does not exist or this file
     *                   is empty.
     */
    public function rebuildDemoFileCommentLine(): ?bool {

        if( !$this->hasDemoFile() ) {
            return null;
        }

        return $this->rebuildSpecialComment(
            $this->getSpecialComment('demo')
        );
    }

    /** Creates a demo file equivalent in its dedicated location. */
    public function createDemoFile(): TestFile|false {

        if( $demo_file_instance = $this->getDemoFileInstance() ) {
            return $demo_file_instance;
        }

        $demo_file_pathname = $this->buildDemoFilePathname();
        $demo_file_dirname = dirname($demo_file_pathname);

        if( !file_exists($demo_file_dirname) ) {

            $make_dir_result = mkdir(
                $demo_file_dirname,
                recursive: true
            );

            if( !$make_dir_result ) {
                return false;
            }
        }

        $template_filename = ProjectRootDirectory::joinPath(
            $this->root_directory->demo_static_dirname,
            '+new.php'
        );

        if( !file_exists($template_filename) ) {
            return false;
        }

        $template_contents = file_get_contents($template_filename);

        if( $template_contents === false ) {
            return false;
        }

        $bytes_written = file_put_contents(
            $demo_file_pathname,
            $template_contents
        );

        if( $bytes_written === false ) {
            return false;
        }

        return $this->root_directory->factory->fromPathname(
            $demo_file_pathname
        );
    }

    /**
     * Builds PHP namespace "use" declaration line that represents this file.
     */
    public function buildUseLine(): ?string {

        $result = 'use ';

        if( isset($this->root_directory->config['vendor_name']) ) {
            $result .= ($this->root_directory->config['vendor_name'] . '\\');
        }

        $relative_path = $this->getSourceRelativePathname();

        // Trim off extension.
        if( ($pos = strrpos($relative_path, '.')) !== false ) {
            $relative_path = substr($relative_path, 0, $pos);
        }

        $result .= (str_replace('/', '\\', $relative_path) . ';');

        return $result;
    }
}
