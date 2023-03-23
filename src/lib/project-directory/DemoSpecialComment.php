<?php

declare(strict_types=1);

namespace PD;

class DemoSpecialComment extends SpecialComment {

    /** A prefix to the demo file link in the demo comment line. */
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
