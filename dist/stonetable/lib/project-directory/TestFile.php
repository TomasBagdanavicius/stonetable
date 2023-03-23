<?php

/**
 * A generic test file object.
 *
 * Requires PHP 8.1 or higher.
 *
 * @package Project Directory
 * @author Tomas Bagdanavičius <tomas.bagdanavicius@lwis.net>
 * @license MIT License
 * @copyright Copyright (c) 2023 LWIS Technologies <info@lwis.net>
 *            (https://www.lwis.net/)
 * @version 1.0.0
 * @since 1.0.0
 */

declare(strict_types=1);

namespace PD;

class TestFile extends ProjectFile {

    public function __construct(
        string $filename,
        ProjectDirectory $root_directory
    ) {

        parent::__construct($filename, $root_directory);
    }

    /** Adds in special comments required by this file. */
    public function setupSpecialComments(
        array $special_comment_storage
    ): array {

        $special_comment_storage['link'] = new LinkSpecialComment($this);

        return parent::setupSpecialComments($special_comment_storage);
    }

    /** Generates the link comment line. */
    public function buildLinkCommentLine(): ?string {

        return $this->getSpecialComment('link')->getLine();
    }

    /** Tells if file contains the link comment line. */
    public function hasLinkCommentLine(): bool {

        return boolval(
            $this->containsSpecialComment($this->getSpecialComment('link'))
        );
    }

    /** Rebuilds the link comment line. */
    public function rebuildLinkCommentLine(): bool {

        return $this->rebuildSpecialComment(
            $this->getSpecialComment('link')
        );
    }
}
