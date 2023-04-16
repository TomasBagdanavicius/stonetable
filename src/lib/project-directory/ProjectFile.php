<?php

/**
 * Object representing a regular project file (i.e., not a directory).
 *
 * PHP version 8.1
 *
 * @package   Project Directory
 * @author    Tomas Bagdanavičius <tomas.bagdanavicius@lwis.net>
 * @license   MIT License
 * @copyright Copyright (c) 2023 LWIS Technologies <info@lwis.net>
 *            (https://www.lwis.net/)
 * @version   1.0.3
 * @since     1.0.0
 */

declare(strict_types=1);

namespace PD;

use UnexpectedValueException;

/**
 * Low abstraction level regular file object that extends into the lowest
 * abstraction level file object ProjectFileObject.
 */
class ProjectFile extends ProjectFileObject {

    /** SplFileObject representation of this file. */
    protected ?\SplFileObject $file;

    /** Collection of special comment objects. */
    protected array $special_comments = [];

    use FileTrait;

    /**
     * Connects to the parent and sets up a SplFileObject equivalent.
     *
     * @param string           $filename       File's path name.
     * @param ProjectDirectory $root_directory Directory object of the file's
     *                                         base directory.
     */
    public function __construct(
        public readonly string $filename,
        public readonly ProjectDirectory $root_directory
    ) {

        parent::__construct(
            $filename,
            $root_directory->dirname,
            $root_directory->on_description_data
        );

        $this->file = new \SplFileObject($filename);
        $this->special_comments = static::setupSpecialComments([]);
    }

    /** Closes the SplFileObject file handler. */
    public function fileClose(): void {

        $this->file = null;
    }

    /** Getter for the "file" property. */
    public function getFile(): ?\SplFileObject {

        return $this->file;
    }

    /** Gets data describing the file. */
    public function getDescriptionData(): array {

        $is_supported = $this->isSupportedFileType();

        $data = [
            'IdeUri' => $this->getIdeUri(),
            'isSupportedFileType' => $is_supported,
        ];

        return [
            ...parent::getDescriptionData(),
            ...$data,
        ];
    }

    /**
     * Collects special comments into the special comment storage array.
     *
     * @param array $special_comment_storage A named list of special comments.
     * @return array Amended data storage.
     * @throws \UnexpectedValueException When the result returned from the
     *                                   callback is an array.
     * @throws \LogicException           When callback result contains element
     *                                   that isn't instance of SpecialComment.
     * @throws \Exception                For a duplicate special comment name.
     */
    public function setupSpecialComments(
        array $special_comment_storage
    ): array {

        if( $this->root_directory->on_special_comment_setup ) {

            $callback = $this->root_directory->on_special_comment_setup;
            $special_comments = ($callback)($special_comment_storage, $this);

            // Null implies no results.
            if( $special_comments !== null ) {

                if( !is_array($special_comments) ) {
                    throw new \UnexpectedValueException(
                        "Special comments callback must return an array."
                    );
                }

                foreach( $special_comments as $name => $special_comment ):

                    if( !($special_comment instanceof SpecialComment) ) {
                        throw new \LogicException(
                            "Special comment must be of SpecialComment type."
                        );
                    }

                    if( isset($special_comment_storage[$name]) ) {
                        throw new \Exception(
                            "Special comment named \"$name\" already exists."
                        );
                    }

                    $special_comment_storage[$name] = $special_comment;

                endforeach;
            }
        }

        return $special_comment_storage;
    }

    /**
     * Gets an enumeration reference telling what main category this file falls
     * under - source or test.
     */
    public function getMainDirectory(): ?MainDirectoriesEnum {

        $relative_pathname = $this->getRelativePathname();

        $pos = strpos($relative_pathname, '/');

        $directory_name = ( $pos !== false )
            ? substr($relative_pathname, 0, $pos)
            : $relative_pathname;

        $main_directory_cases = MainDirectoriesEnum::cases();
        $main_directory = null;

        foreach( $main_directory_cases as $main_directory_case ) {

            if( $main_directory_case->value === $directory_name ) {
                $main_directory = $main_directory_case;
                break;
            }
        }

        return $main_directory;
    }

    /**
     * Replaces a substring in the file by given position coordinates with a
     * given replacement string.
     *
     * @param array  $coords      Start position and end position.
     * @param string $replacement A string that will replace the searchable
     *                            substring.
     * @return boolean True on success and false on failure.
     */
    public function replaceByCoords(
        array $coords,
        string $replacement
    ): bool {

        $this->file->rewind();
        clearstatcache();

        $before_portion = $this->file->fread($coords[0]);

        if( $before_portion === false ) {
            return false;
        }

        $data = ($before_portion . $replacement);

        $file_size = $this->file->getSize();

        if( $coords[1] < $file_size ) {

            if( $this->file->fseek($coords[1]) !== 0 ) {
                return false;
            }

            $after_portion = $this->file->fread(
                ($this->file->getSize() - $coords[1])
            );

            if( $after_portion === false ) {
                return false;
            }

            $data .= $after_portion;
        }

        return ($this->replaceContents($data) === strlen($data));
    }

    /** Returns all attached special comments. */
    public function getAllSpecialComments(): array {

        return $this->special_comments;
    }

    /** Retrieves a special comment by a query name. */
    public function getSpecialComment( string $name ): ?SpecialComment {

        return ( $this->special_comments[$name] ?? null );
    }

    /** Tells if special comment with given name exists. */
    public function hasSpecialComment( string $name ): bool {

        return isset($this->special_comments[$name]);
    }

    /**
     * Tells if file contains the given special comment.
     *
     * @param SpecialComment $special_comment Special comment object.
     * @return false|array False on failure or array containing comment's start
     *                     and end positions on success.
     */
    public function containsSpecialComment(
        SpecialComment $special_comment
    ): false|array {

        $comment_prefix = ($special_comment::PREFIX
            . $special_comment->line_prefix);

        $this->file->rewind();
        $this->file->setFlags(
            \SplFileObject::DROP_NEW_LINE
            | \SplFileObject::SKIP_EMPTY
        );

        while( !$this->file->eof() ):

            $line = $this->file->current();

            // Empty file; or empty first line, which is not valid.
            if( !$line ) {
                return false;
            }

            if( str_starts_with($line, $comment_prefix) ) {

                $tell = $this->file->ftell();
                return [($tell - strlen($line) - 1), $tell];

            } elseif( str_starts_with($line, 'declare(') ) {

                return false;
            }

            $this->file->next();

        endwhile;

        return false;
    }

    /**
     * Injects special comment line into the file.
     *
     * @param SpecialComment $special_comment Special comment object.
     * @param string $comment_line_suffix     Substring to be added to the end
     *                                        of comment line.
     * @return bool False on failure and true on success.
     */
    public function injectSpecialComment(
        SpecialComment $special_comment,
        string $comment_line_suffix = "\n"
    ): ?bool {

        $line = $special_comment->getLine();

        if( $line === null ) {
            return null;
        }

        $comment_line = ($special_comment->getLine() . $comment_line_suffix);

        $this->file->rewind();
        clearstatcache();

        // Start by adding in the first line (the php open tag line).
        $data = $this->file->fgets();

        if( $data === false ) {
            return false;
        }

        $pointer_pos = $this->file->ftell();

        if( $pointer_pos === false ) {
            return false;
        }

        $data .= ("\n\n" . $comment_line);

        if( !$this->file->eof() ) {

            $non_empty_line = null;

            // Find next non-empty line.
            while( !$this->file->eof() ):

                $line = $this->file->fgets();

                if( $line === false ) {
                    return false;
                }

                $line = trim($line);

                if( $line !== '' ) {
                    $non_empty_line = $line;
                    break;
                }

            endwhile;

            $seek = $this->file->fseek($pointer_pos);

            if( $seek !== 0 ) {
                return false;
            }

            $file_size = $this->file->getSize();

            if( $file_size === false ) {
                return false;
            }

            $remaining_bytes = ($file_size - $pointer_pos);

            if( $remaining_bytes ) {

                $remaining_data = $this->file->fread($remaining_bytes);

                if( $remaining_data === false ) {
                    return false;
                }

                if(
                    isset($non_empty_line)
                    && str_starts_with($non_empty_line, SpecialComment::PREFIX)
                ) {
                    $remaining_data = ltrim($remaining_data);
                }

                $data .= $remaining_data;
            }
        }

        return ( $this->replaceContents($data) === strlen($data) );
    }

    /** Rebuilds given special comment. */
    public function rebuildSpecialComment(
        SpecialComment $special_comment
    ): ?bool {

        if( $this->file->getSize() && $this->isSupportedFileType() ) {

            $line = $special_comment->getLine();

            if( !$line ) {
                return null;
            }

            $coords = $this->containsSpecialComment($special_comment);

            return ( $coords )
                // Replace.
                ? $this->replaceByCoords($coords, ($line . "\n"))
                // Add.
                : $this->injectSpecialComment($special_comment);

        // File is empty.
        } else {

            return null;
        }
    }

    /** Rebuilds all special comments. */
    public function rebuildAllSpecialCommentLines(): ?bool {

        if( $this->file->getSize() ) {

            foreach( $this->special_comments as $special_comment ):

                if( $this->rebuildSpecialComment($special_comment) === false ) {
                    return false;
                }

            endforeach;

            return true;

        // File is empty.
        } else {

            return null;
        }
    }

    /** Retrieves full contents of this file. */
    public function getContents(): string|false {

        return file_get_contents($this->getPathname());
    }

    /**
     * Replaces file contents.
     *
     * @param string $data New file contents.
     * @return int Length of the data that was written.
     * @throws \Error Whens fails to write the new file contents.
     * @throws \LengthException When file write length issue occurs.
     */
    public function replaceContents( string $data ): int {

        $data_len_written = file_put_contents(
            $this->filename,
            $data,
            flags: LOCK_EX
        );

        if( !$data_len_written ) {

            throw new \Error(sprintf(
                "Could not replace file %s contents.",
                $this->filename
            ));
        }

        $data_length = strlen($data);

        if( $data_len_written !== $data_length ) {

            throw new \LengthException(sprintf(
                "The length of the data to write (%d) does not match the "
                    . "written data length (%d)",
                $data_length,
                $data_len_written
            ));
        }

        return $data_len_written;
    }

    /**
     * Tells whether this file should receive special comments.
     *
     * To exclude a file from receiving special comments, one can add file's
     * path name relative to the main directory to the config file of a project.
     */
    public function isSpecialCommentIgnored(): bool {

        $main_directory = $this->getMainDirectory();

        if( !$main_directory ) {
            return false;
        }

        $config = $this->root_directory->config['special_comments'];
        $ignore_files = $config[$main_directory->value]['ignore_files'];

        $main_directory_relative_pathname = substr(
            $this->getRelativePathname(),
            (strlen($main_directory->value) + 1)
        );

        return in_array(
            $main_directory_relative_pathname,
            $ignore_files
        );
    }

    /**
     * Tells if this file is supported for source reading and other file
     * manipulation tasks.
     */
    public function isSupportedFileType(): bool {

        return ( $this->getExtension() === 'php' );
    }
}
