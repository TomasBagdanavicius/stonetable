<?php

/**
 * Object representing a compound statement.
 *
 * Requires PHP 8.1 or higher.
 *
 * @package PHP Code Test Suite
 * @author Tomas Bagdanavičius <tomas.bagdanavicius@lwis.net>
 * @license MIT License
 * @copyright Copyright (c) 2023 LWIS Technologies <info@lwis.net>
 *            (https://www.lwis.net/)
 * @version 1.0.0
 * @since 1.0.0
 */

declare(strict_types=1);

namespace PCTS\PhpTokens\Language;

class CompoundStatement extends AbstractStatement {

    /**
     * @param int       $open_level  Level at which statement was opened.
     * @param \PhpToken $open_token  PHP token representing the opening bracket.
     * @param \PhpToken $close_token PHP token representing the closing bracket.
     */
    public function __construct(
        public readonly int $open_level,
        public readonly \PhpToken $open_token,
        public readonly \PhpToken $close_token,
    ) {

    }
}
