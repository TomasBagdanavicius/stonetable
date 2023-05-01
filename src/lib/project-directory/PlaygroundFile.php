<?php

/**
 * Represents a playground file.
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

/** Playground file is a more specific version of a test file. */
class PlaygroundFile extends TestFile {

    public function __construct(
        string $filename,
        ProjectDirectory $root_directory,
        public readonly BranchedTestDirectory $branched_test_directory
    ) {

        parent::__construct($filename, $root_directory);
    }

    /** Gets all data describing this playground file. */
    public function getDescriptionData(): array {

        $data = parent::getDescriptionData();
        $data['group'] = ($data['category'] . '-playground');
        $static_file = $this->getStaticFileInstance();

        if( $static_file ) {

            $data['staticFilePathname'] = $static_file->filename;
            $data['staticFileIdeUri'] = $static_file->getIdeUri();
            $data['staticFileRelativePathname']
                = $static_file->getRelativePathname();

            $source_file = $static_file->getSourceFileInstance();

            if( $source_file ) {

                $data['sourceFilePathname'] = $source_file->filename;
                $data['sourceFileIdeUri'] = $source_file->getIdeUri();
                $data['sourceFileRelativePathname']
                    = $source_file->getRelativePathname();
            }
        }

        return $data;
    }

    /** Adds in special comments required by this file. */
    public function setupSpecialComments(
        array $special_comment_storage
    ): array {

        $special_comment_storage['static'] = new StaticSpecialComment($this);

        return parent::setupSpecialComments($special_comment_storage);
    }

    /** Gets path name relative to the playground files directory. */
    public function getPlaygroundRelativePathname(): string {

        return substr(
            $this->pathname,
            (strlen($this->branched_test_directory->playground_dirname) + 1)
        );
    }

    /**
     * Builds a file name that would be an equivalent file in a static files
     * directory.
     */
    public function buildStaticFilePathname(): string {

        return $this->branched_test_directory->buildStaticFilePathname(
            $this->getPlaygroundRelativePathname()
        );
    }

    /**
     * Gets a reference to an equivalent file name in a static files directory.
     *
     * @return string|null Null when file does not exist.
     */
    public function getStaticFilePathname(): ?string {

        $static_filename = $this->buildStaticFilePathname();

        if( !file_exists($static_filename) ) {
            return null;
        }

        return $static_filename;
    }

    /** Tells if static file equivalent exists. */
    public function hasStaticFile(): bool {

        return boolval($this->getStaticFilePathname());
    }

    /**
     * Gets instance of the static file equivalent.
     *
     * @return StaticFile|null Null when static file does not exist.
     */
    public function getStaticFileInstance(): ?StaticFile {

        return $this->branched_test_directory->findStaticFile(
            $this->getPlaygroundRelativePathname()
        );
    }

    /**
     * Resets contents of this file by replacing it with the original contents
     * from the static file equivalent.
     */
    public function resetContents(): bool {

        $static_file_instance = $this->getStaticFileInstance();

        [
            'comparable_contents' => $static_comparable_contents
        ] = self::getComparableContents(
            $static_file_instance->file
        );

        [
            'header_size' => $playground_header_size
        ] = self::getComparableContents(
            $this->file
        );

        $coords = [$playground_header_size, $this->file->getSize()];

        return $this->replaceByCoords($coords, $static_comparable_contents);
    }

    /**
     * Tells if this playground file has been modified in comparison to the
     * contents in the static file equivalent.
     *
     * @return bool|null Null when file is empty, otherwise boolean.
     */
    public function isModified(): ?bool {

        $static_file_instance = $this->getStaticFileInstance();

        if( $static_file_instance === null ) {
            return null;
        }

        [
            'comparable_contents' => $static_comparable_contents
        ] = self::getComparableContents(
            $static_file_instance->file
        );

        [
            'comparable_contents' => $playground_comparable_contents
        ] = self::getComparableContents(
            $this->file
        );

        return (
            $static_comparable_contents !== $playground_comparable_contents
        );
    }

    /** Deletes this playground file. */
    public function delete(): bool {

        return unlink($this->filename);
    }

    /**
     * Retrieves contents that does not include the PHP opening tag and
     * succeeded special comments.
     *
     * @param \SplFileObject $file_object A file to retrieve contents from.
     * @return array ['header_size', 'comparable_contents']
     */
    public static function getComparableContents(
        \SplFileObject $file_object
    ): array {

        $file_object->rewind();

        $open_tag = $found = false;
        $header_size = 0;
        $comparable_contents = '';

        foreach( $file_object as $line ) {

            $trimmed_line = trim($line);

            if( !$open_tag && $trimmed_line === '<?php' ) {

                $open_tag = true;

            } elseif( !$found && $trimmed_line !== '' ) {

                preg_match('#^\/\/\s.+?:\s#', $line, $matches);

                if( !$matches ) {
                    $found = true;
                }
            }

            if( $found ) {
                $comparable_contents .= $line;
            } else {
                $header_size += strlen($line);
            }
        }

        return [
            'header_size' => $header_size,
            'comparable_contents' => $comparable_contents,
        ];
    }
}
