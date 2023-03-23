<?php

declare(strict_types=1);

namespace PD;

class LinkSpecialComment extends SpecialComment {

    /** A prefix to a file URL link in the link comment line. */
    public const LINE_PREFIX = "Link";

    public function __construct(
        public readonly TestFile $project_file
    ) {

        parent::__construct(self::LINE_PREFIX);
    }

    /** Gets the content part of the comment line. */
    public function getContent(): ?string {

        return $this->project_file->getUrl();
    }
}
