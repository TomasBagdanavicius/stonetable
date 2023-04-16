<?php

/**
 * Object representing an anonymous function definition.
 *
 * PHP version 8.1
 *
 * @package   PHP Code Test Suite
 * @author    Tomas Bagdanavičius <tomas.bagdanavicius@lwis.net>
 * @license   MIT License
 * @copyright Copyright (c) 2023 LWIS Technologies <info@lwis.net>
 *            (https://www.lwis.net/)
 * @version   1.0.3
 * @since     1.0.0
 */

declare(strict_types=1);

namespace PCTS\PhpTokens\Language;

class AnonymousFunctionDefinition extends AbstractDefinition {

    /**
     * @param array                  $parameters         Function parameters.
     * @param array                  $return_types       A list of return types.
     * @param CompoundStatement|null $compound_statement Function's compound
     *                                                   statement.
     */
    public function __construct(
        public readonly array $parameters,
        public readonly array $return_types,
        public readonly ?CompoundStatement $compound_statement = null,
    ) {

    }
}
