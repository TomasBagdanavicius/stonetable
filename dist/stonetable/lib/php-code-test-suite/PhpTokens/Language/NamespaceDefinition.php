<?php

/**
 * Object representing a namespace definition.
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

class NamespaceDefinition extends AbstractDefinition {

    /**
     * @param string                 $namespace_name     Namespace name.
     * @param CompoundStatement|null $compound_statement Succeeding compound
     *                                                   statement.
     */
    public function __construct(
        public readonly string $namespace_name,
        public readonly ?CompoundStatement $compound_statement = null
    ) {

    }
}
