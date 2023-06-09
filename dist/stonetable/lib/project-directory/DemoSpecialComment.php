<?php

/**
 * Object representing a demo special comment.
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

class DemoSpecialComment extends SpecialComment {

    /**
     * A prefix to the demo file link at the beginning of the demo comment line.
     */
    public const LINE_PREFIX = "Demo";

    public function __construct(
        public readonly SourceFile $project_file
    ) {

        parent::__construct(self::LINE_PREFIX);
    }

    /** Gets the content part of the comment line. */
    public function getContent(): ?string {

        $demo_file_pathname = $this->project_file->getDemoFilePathname();

        if( !$demo_file_pathname ) {
            return null;
        }

        return ('file://' . $demo_file_pathname);
    }
}
