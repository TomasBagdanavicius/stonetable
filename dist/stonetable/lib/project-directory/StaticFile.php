<?php

/**
 * Represents demo or unit static file.
 *
 * PHP version 8.1
 *
 * @package   Project Directory
 * @author    Tomas Bagdanavičius <tomas.bagdanavicius@lwis.net>
 * @license   MIT License
 * @copyright Copyright (c) 2023 LWIS Technologies <info@lwis.net>
 *            (https://www.lwis.net/)
 * @version   1.0.5
 * @since     1.0.0
 */

declare(strict_types=1);

namespace PD;

/** Static file is a more specific object of a test file. */
class StaticFile extends TestFile {

    public function __construct(
        string $filename,
        ProjectDirectory $root_directory,
        public readonly BranchedTestDirectory $branched_test_directory
    ) {

        parent::__construct($filename, $root_directory);
    }

    /** Gets all data describing this static file. */
    public function getDescriptionData(): array {

        $data = parent::getDescriptionData();
        $data['group'] = ($data['category'] . '-static');
        $source_file = $this->getSourceFileInstance();

        if( $source_file ) {

            $data['sourceFilePathname'] = $source_file->filename;
            $data['sourceFileIdeUri'] = $source_file->getIdeUri();
            $data['sourceFileRelativePathname']
                = $source_file->getRelativePathname();
        }

        $playground_file = $this->getPlaygroundFileInstance();

        if( $playground_file ) {

            $data['playgroundFilePathname'] = $playground_file->filename;
            $data['playgroundFileRelativePathname']
                = $playground_file->getRelativePathname();
            $data['playgroundFileIdeUri'] = $playground_file->getIdeUri();
        }

        return $data;
    }

    /** Adds in special comments required by this file. */
    public function setupSpecialComments(
        array $special_comment_storage
    ): array {

        $special_comment_storage['source'] = new SourceSpecialComment($this);

        return parent::setupSpecialComments($special_comment_storage);
    }

    /** Generates the source comment line. */
    public function buildSourceCommentLine(): ?string {

        return $this->getSpecialComment('source')->getLine();
    }

    /** Tells if file contains the source comment line. */
    public function hasSourceCommentLine(): bool {

        return boolval(
            $this->containsSpecialComment($this->getSpecialComment('source'))
        );
    }

    /** Rebuilds the source comment line. */
    public function rebuildSourceCommentLine(): ?bool {

        return $this->rebuildSpecialComment(
            $this->getSpecialComment('source')
        );
    }

    /** Gets path name relative to the static files directory. */
    public function getStaticRelativePathname(): string {

        return substr(
            $this->pathname,
            (strlen($this->branched_test_directory->static_dirname) + 1)
        );
    }

    /** Generates source file equivalent file name. */
    public function buildSourceFilename(): string {

        return ProjectRootDirectory::joinPath(
            $this->root_directory->source_dirname,
            $this->getStaticRelativePathname()
        );
    }

    /**
     * Gets a reference to an equivalent file name in source files directory.
     *
     * @return string|null Null when file does not exist.
     */
    public function getSourceFilePathname(): ?string {

        $filename = $this->buildSourceFilename();

        if( !file_exists($filename) ) {
            return null;
        }

        return $filename;
    }

    /** Tells if source file equivalent exists. */
    public function hasSourceFile(): bool {

        return boolval($this->getSourceFilePathname());
    }

    /**
     * Gets instance of the source file equivalent.
     *
     * @return SourceFile|null Null when demo file does not exist.
     */
    public function getSourceFileInstance(): ?SourceFile {

        $source_file_pathname = $this->getSourceFilePathname();

        if( !$source_file_pathname ) {
            return null;
        }

        return $this->root_directory->factory->fromPathname(
            $source_file_pathname
        );
    }

    /** Generates playground file equivalent file name. */
    public function buildPlaygroundFilename(): string {

        return $this->branched_test_directory->buildPlaygroundFilePathname(
            $this->getStaticRelativePathname()
        );
    }

    /**
     * Gets a reference to an equivalent file name in playground files
     * directory.
     *
     * @return string|null Null when file does not exist.
     */
    public function getPlaygroundFilename(): ?string {

        $playground_filename = $this->buildPlaygroundFilename();

        if( !file_exists($playground_filename) ) {
            return null;
        }

        return $playground_filename;
    }

    /** Tells if playground file equivalent exists. */
    public function hasPlaygroundFile(): bool {

        return boolval($this->getPlaygroundFilename());
    }

    /**
     * Gets instance of the playground file equivalent.
     *
     * @return PlaygroundFile|null Null when playground file does not exist.
     */
    public function getPlaygroundFileInstance(): ?PlaygroundFile {

        return $this->branched_test_directory->findPlaygroundFile(
            $this->getStaticRelativePathname()
        );
    }

    /** Creates a playground file equivalent in its dedicated location. */
    public function createPlaygroundFile(): PlaygroundFile|false {

        $filename = ProjectRootDirectory::joinPath(
            $this->branched_test_directory->playground_dirname,
            $this->getStaticRelativePathname()
        );
        $filename_dirname = dirname($filename);

        if( !file_exists($filename_dirname) ) {

            $make_dir_result = mkdir(
                $filename_dirname,
                recursive: true
            );

            if( !$make_dir_result ) {
                return false;
            }
        }

        $contents = $this->getContents();

        if( $contents === false ) {
            return false;
        }

        $written = file_put_contents($filename, $contents);

        if( $written === false ) {
            return false;
        }

        $playground_file = new PlaygroundFile(
            $filename,
            $this->root_directory,
            $this->branched_test_directory
        );

        $playground_file->rebuildAllSpecialCommentLines();

        return $playground_file;
    }
}
