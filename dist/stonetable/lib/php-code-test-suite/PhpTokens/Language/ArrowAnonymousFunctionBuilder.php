<?php

/**
 * Arrow function builder.
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

use PCTS\PhpTokens\Tokenizer;

class ArrowAnonymousFunctionBuilder extends AnonymousFunctionBuilder {

    /**
     * @param bool      $has_reference Defines if function has reference symbol.
     * @param Tokenizer $tokenizer     Tokenizer dependency.
     */
    public function __construct(
        bool $has_reference,
        Tokenizer $tokenizer
    ) {

        parent::__construct($has_reference, $tokenizer);
    }
}
