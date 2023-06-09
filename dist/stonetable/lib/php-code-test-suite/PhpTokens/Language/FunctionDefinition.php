<?php

/**
 * Object representing a function definition.
 *
 * PHP version 8.1
 *
 * @package   PHP Code Test Suite
 * @author    Tomas Bagdanavičius <tomas.bagdanavicius@lwis.net>
 * @license   MIT License
 * @copyright Copyright (c) 2023 LWIS Technologies <info@lwis.net>
 *            (https://www.lwis.net/)
 * @version   1.0.7
 * @since     1.0.0
 */

declare(strict_types=1);

namespace PCTS\PhpTokens\Language;

class FunctionDefinition extends AbstractDefinition {

    /**
     * @param string                 $function_name      Function name.
     * @param array                  $parameters         Function parameter.s
     * @param array                  $return_types       A list of return types.
     * @param CompoundStatement|null $compound_statement Succeeding compound
     *                                                   statement.
     */
    public function __construct(
        public readonly string $function_name,
        public readonly array $parameters,
        public readonly array $return_types,
        public readonly ?CompoundStatement $compound_statement = null
    ) {

    }
}
