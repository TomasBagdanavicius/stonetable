<?php

/**
 * Object representing a static special comment.
 *
 * PHP version 8.1
 *
 * @package   Project Directory
 * @author    Tomas Bagdanavičius <tomas.bagdanavicius@lwis.net>
 * @license   MIT License
 * @copyright Copyright (c) 2023 LWIS Technologies <info@lwis.net>
 *            (https://www.lwis.net/)
 * @version   1.0.6
 * @since     1.0.4
 */

declare(strict_types=1);

namespace PD;

class StaticSpecialComment extends SpecialComment {

    /**
     * A prefix to the static file link at the beginning of the static comment
     * line.
     */
    public const LINE_PREFIX = "Static";

    public function __construct(
        public readonly PlaygroundFile $project_file
    ) {

        parent::__construct(self::LINE_PREFIX);
    }

    /** Gets the content part of the comment line. */
    public function getContent(): ?string {

        $static_file_pathname = $this->project_file->getStaticFilePathname();

        if( !$static_file_pathname ) {
            return null;
        }

        return ('file://' . $static_file_pathname);
    }
}
