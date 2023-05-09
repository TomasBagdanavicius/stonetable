<?php

/**
 * An object representing source special comment.
 *
 * PHP version 8.1
 *
 * @package   Project Directory
 * @author    Tomas Bagdanavičius <tomas.bagdanavicius@lwis.net>
 * @license   MIT License
 * @copyright Copyright (c) 2023 LWIS Technologies <info@lwis.net>
 *            (https://www.lwis.net/)
 * @version   1.0.6
 * @since     1.0.0
 */

declare(strict_types=1);

namespace PD;

class SourceSpecialComment extends SpecialComment {

    /** A prefix to the source file link in the source comment line. */
    public const LINE_PREFIX = "Source";

    public function __construct(
        public readonly StaticFile $project_file
    ) {

        parent::__construct(self::LINE_PREFIX);
    }

    /** Gets the content part of the comment line. */
    public function getContent(): ?string {

        $source_file_pathname = $this->project_file->getSourceFilePathname();

        if( !$source_file_pathname ) {
            return null;
        }

        return ('file://' . $source_file_pathname);
    }
}
