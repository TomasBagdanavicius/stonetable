<?php

/**
 * A generic test file object.
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

class TestFile extends ProjectFile {

    public function __construct(
        string $filename,
        ProjectDirectory $root_directory
    ) {

        parent::__construct($filename, $root_directory);
    }

    /** Gets all data describing this test file. */
    public function getDescriptionData(): array {

        $my_data = [];

        $tests_dirname = $this->root_directory->tests_dirname;
        $path = $this->pathname;
        $ds = DIRECTORY_SEPARATOR;
        $prefix = ($tests_dirname . $ds);

        $my_data['category'] = match(true) {
            str_starts_with($path, ($prefix . 'demo' . $ds)) => 'demo',
            str_starts_with($path, ($prefix . 'units' . $ds)) => 'unit',
            default => 'test'
        };

        $my_data['group'] = 'test';

        return [...parent::getDescriptionData(), ...$my_data];
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
    public function rebuildLinkCommentLine(): ?bool {

        return $this->rebuildSpecialComment(
            $this->getSpecialComment('link')
        );
    }
}
