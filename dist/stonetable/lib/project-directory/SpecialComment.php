<?php

/**
 * Object representing an abstract special comment.
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

abstract class SpecialComment {

    /** Special comment's prefix. */
    public const PREFIX = '#';

    /** Substring that goes after a line prefix. */
    public const AFTER_LINE_PREFIX = ': ';

    /**
     * @param string $line_prefix Chosen line prefix (should be short).
     * @throws \ValueError When given line prefix is an empty string.
     */
    public function __construct(
        public readonly string $line_prefix
    ) {

        if( $line_prefix === '' ) {
            throw new \ValueError("Line prefix cannot be empty.");
        }
    }

    /** Comment's content is up to individual implementation. */
    abstract public function getContent(): ?string;

    /** Returns full comment line. */
    public function getLine(): ?string {

        $contents = $this->getContent();

        if( $contents === null ) {
            return null;
        }

        return (
            self::PREFIX
            . $this->line_prefix
            . self::AFTER_LINE_PREFIX
            . $contents
        );
    }
}
